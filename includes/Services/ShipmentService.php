<?php
/**
 * Shipment Creation, Update and Cancellation Service.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Services;

use WC_Order;
use WPCalibrate\ShippingConnector\Api\DelhiveryClient;
use WPCalibrate\ShippingConnector\Api\ApiResult;
use WPCalibrate\ShippingConnector\Orders\OrderShipmentRepository;
use WPCalibrate\ShippingConnector\Orders\ShipmentMapper;
use WPCalibrate\ShippingConnector\Logging\Logger;
use WPCalibrate\ShippingConnector\Support\Constants;

/**
 * Class ShipmentService
 */
class ShipmentService {

	/**
	 * Delhivery client.
	 *
	 * @var DelhiveryClient
	 */
	private DelhiveryClient $client;

	/**
	 * Order repository.
	 *
	 * @var OrderShipmentRepository
	 */
	private OrderShipmentRepository $repository;

	/**
	 * Shipment mapper.
	 *
	 * @var ShipmentMapper
	 */
	private ShipmentMapper $mapper;

	/**
	 * Warehouse service.
	 *
	 * @var WarehouseService
	 */
	private WarehouseService $warehouse_service;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param DelhiveryClient         $client
	 * @param OrderShipmentRepository $repository
	 * @param ShipmentMapper          $mapper
	 * @param WarehouseService        $warehouse_service
	 * @param Logger                  $logger
	 */
	public function __construct(
		DelhiveryClient $client,
		OrderShipmentRepository $repository,
		ShipmentMapper $mapper,
		WarehouseService $warehouse_service,
		Logger $logger
	) {
		$this->client            = $client;
		$this->repository        = $repository;
		$this->mapper            = $mapper;
		$this->warehouse_service = $warehouse_service;
		$this->logger            = $logger;
	}

	/**
	 * Create and manifest a shipment with Delhivery.
	 *
	 * @param WC_Order    $order
	 * @param string|null $warehouse_id Optional warehouse identifier override.
	 * @param string|null $payment_mode_override Optional payment mode override.
	 * @return array{
	 *     success: bool,
	 *     waybill: string,
	 *     message: string
	 * }
	 */
	public function create_shipment(
		WC_Order $order,
		?string $warehouse_id = null,
		?string $payment_mode_override = null
	): array {
		$order_id = $order->get_id();

		// Check if already manifested.
		if ( $this->repository->is_manifested( $order ) ) {
			$existing_awb = $this->repository->get_waybill( $order );
			return array(
				'success' => false,
				'waybill' => $existing_awb,
				'message' => sprintf(
					/* translators: %s: Waybill number */
					__( 'Shipment is already manifested with AWB %s.', 'wpcalibrate-shipping-connector' ),
					$existing_awb
				),
			);
		}

		// Acquire idempotency lock.
		if ( ! $this->repository->acquire_lock( $order ) ) {
			return array(
				'success' => false,
				'waybill' => '',
				'message' => __( 'Shipment creation is currently in progress for this order. Please wait.', 'wpcalibrate-shipping-connector' ),
			);
		}

		try {
			// Resolve warehouse.
			$warehouse = null;
			if ( ! empty( $warehouse_id ) ) {
				$warehouse = $this->warehouse_service->get_warehouse( $warehouse_id );
			}
			if ( empty( $warehouse ) ) {
				$warehouse = $this->warehouse_service->get_default_warehouse();
			}

			if ( empty( $warehouse ) ) {
				$this->repository->release_lock( $order );
				return array(
					'success' => false,
					'waybill' => '',
					'message' => __( 'No active warehouse configured. Please configure a warehouse in settings.', 'wpcalibrate-shipping-connector' ),
				);
			}

			// Resolve Payment mode.
			$payment_mode = $payment_mode_override;
			if ( empty( $payment_mode ) ) {
				$settings     = get_option( Constants::OPTION_SETTINGS, array() );
				$cod_gateways = (array) ( $settings['cod_mapping'] ?? array( 'cod' ) );
				$payment_mode = in_array( $order->get_payment_method(), $cod_gateways, true ) ? 'COD' : 'Prepaid';
			}

			// Construct payload via mapper.
			$payload = $this->mapper->map_forward_shipment( $order, $warehouse, $payment_mode );

			// Format required body: format=json&data={JSON_STRING}.
			$form_data = array(
				'format' => 'json',
				'data'   => wp_json_encode( $payload ),
			);

			$response = $this->client->post_form( 'api/cmu/create.json', $form_data );

			if ( ! $response->is_success() ) {
				$err_msg = $response->get_error_message();
				$this->logger->error(
					"Shipment creation failed for order #{$order_id}: {$err_msg}",
					array( 'order_id' => $order_id, 'status' => $response->get_status_code() )
				);
				$order->add_order_note(
					sprintf(
						/* translators: %s: Error message */
						__( 'Delhivery shipment manifestation failed: %s', 'wpcalibrate-shipping-connector' ),
						esc_html( $err_msg )
					)
				);
				$this->repository->release_lock( $order );
				return array(
					'success' => false,
					'waybill' => '',
					'message' => $err_msg,
				);
			}

			$data = $response->get_data();
			$waybill = '';

			// Parse assigned packages and waybill.
			if ( ! empty( $data['packages'] ) && is_array( $data['packages'] ) ) {
				$first_pkg = $data['packages'][0] ?? array();
				$pkg_status = $first_pkg['status'] ?? '';

				if ( 'Fail' === $pkg_status ) {
					$remarks = is_array( $first_pkg['remarks'] ?? null ) ? implode( '; ', $first_pkg['remarks'] ) : (string) ( $first_pkg['remarks'] ?? 'Manifestation rejected by carrier.' );
					$this->repository->release_lock( $order );
					$order->add_order_note( sprintf( __( 'Delhivery rejected package: %s', 'wpcalibrate-shipping-connector' ), esc_html( $remarks ) ) );
					return array(
						'success' => false,
						'waybill' => '',
						'message' => $remarks,
					);
				}

				$waybill = (string) ( $first_pkg['waybill'] ?? '' );
			} elseif ( ! empty( $data['upload_wbn'] ) ) {
				$waybill = (string) $data['upload_wbn'];
			}

			if ( empty( $waybill ) ) {
				$this->repository->release_lock( $order );
				$order->add_order_note( __( 'Delhivery manifestation succeeded but no waybill was returned in response.', 'wpcalibrate-shipping-connector' ) );
				return array(
					'success' => false,
					'waybill' => '',
					'message' => __( 'Carrier response did not contain an assigned waybill.', 'wpcalibrate-shipping-connector' ),
				);
			}

			// Persist manifest details.
			$cod_amount = 'COD' === $payment_mode ? (float) $order->get_total() : 0.0;
			$this->repository->save_manifest(
				$order,
				$waybill,
				(string) $warehouse['id'],
				$payment_mode,
				$cod_amount
			);

			$this->repository->release_lock( $order );

			$order->add_order_note(
				sprintf(
					/* translators: 1: Waybill number, 2: Warehouse name */
					__( 'Delhivery shipment manifested successfully. Waybill: %1$s (Origin: %2$s)', 'wpcalibrate-shipping-connector' ),
					$waybill,
					$warehouse['name'] ?? ''
				)
			);

			do_action( 'wpcalibrate_shipping_shipment_created', $order, $waybill, $payload );

			return array(
				'success' => true,
				'waybill' => $waybill,
				'message' => sprintf( __( 'Shipment successfully created with AWB %s.', 'wpcalibrate-shipping-connector' ), $waybill ),
			);
		} catch ( \Throwable $e ) {
			$this->repository->release_lock( $order );
			$this->logger->error( "Exception manifesting order #{$order_id}: " . $e->getMessage() );
			return array(
				'success' => false,
				'waybill' => '',
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Cancel a manifested shipment with Delhivery.
	 *
	 * @param WC_Order $order
	 * @param string   $reason
	 * @return array{success: bool, message: string}
	 */
	public function cancel_shipment( WC_Order $order, string $reason = '' ): array {
		$waybill = $this->repository->get_waybill( $order );

		if ( empty( $waybill ) ) {
			return array(
				'success' => false,
				'message' => __( 'No active waybill found for this order.', 'wpcalibrate-shipping-connector' ),
			);
		}

		if ( $this->repository->is_cancelled( $order ) ) {
			return array(
				'success' => false,
				'message' => __( 'Shipment is already cancelled.', 'wpcalibrate-shipping-connector' ),
			);
		}

		$payload = array(
			'waybill'      => $waybill,
			'cancellation' => 'true',
		);

		$response = $this->client->post_json( 'api/p/edit', $payload );

		if ( ! $response->is_success() ) {
			$err = $response->get_error_message();
			$this->logger->error( "Cancellation failed for waybill {$waybill}: {$err}" );
			return array(
				'success' => false,
				'message' => sprintf( __( 'Carrier cancellation failed: %s', 'wpcalibrate-shipping-connector' ), $err ),
			);
		}

		$this->repository->mark_cancelled( $order, $reason );

		$order->add_order_note(
			sprintf(
				/* translators: 1: Waybill number, 2: Reason */
				__( 'Delhivery shipment %1$s has been cancelled. %2$s', 'wpcalibrate-shipping-connector' ),
				$waybill,
				$reason ? "(Reason: {$reason})" : ''
			)
		);

		do_action( 'wpcalibrate_shipping_shipment_cancelled', $order, $waybill );

		return array(
			'success' => true,
			'message' => sprintf( __( 'Shipment %s cancelled successfully.', 'wpcalibrate-shipping-connector' ), $waybill ),
		);
	}

	/**
	 * Update an existing shipment with Delhivery.
	 *
	 * @param WC_Order             $order
	 * @param array<string, mixed> $update_fields
	 * @return array{success: bool, message: string}
	 */
	public function update_shipment( WC_Order $order, array $update_fields ): array {
		$waybill = $this->repository->get_waybill( $order );

		if ( empty( $waybill ) || $this->repository->is_cancelled( $order ) ) {
			return array(
				'success' => false,
				'message' => __( 'Shipment is not active for updates.', 'wpcalibrate-shipping-connector' ),
			);
		}

		$payload = array_merge(
			array( 'waybill' => $waybill ),
			$update_fields
		);

		$response = $this->client->post_json( 'api/p/edit', $payload );

		if ( ! $response->is_success() ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$order->add_order_note( sprintf( __( 'Delhivery shipment %s details updated with carrier.', 'wpcalibrate-shipping-connector' ), $waybill ) );

		return array(
			'success' => true,
			'message' => __( 'Shipment updated successfully with Delhivery.', 'wpcalibrate-shipping-connector' ),
		);
	}
}
