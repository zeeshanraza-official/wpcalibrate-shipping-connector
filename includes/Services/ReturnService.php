<?php
/**
 * Reverse Pickup (RVP) and Return Logistics Service.
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
 * Class ReturnService
 */
class ReturnService {

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
	 * Create a reverse pickup (RVP) shipment for an order.
	 *
	 * @param WC_Order             $order
	 * @param string|null          $warehouse_id Destination return warehouse.
	 * @param array<string, mixed> $qc_data      Optional QC parameters.
	 * @return array{success: bool, waybill: string, message: string}
	 */
	public function create_return_shipment(
		WC_Order $order,
		?string $warehouse_id = null,
		array $qc_data = array()
	): array {
		$order_id = $order->get_id();

		// Check if a return shipment already exists.
		$existing_return = (string) $order->get_meta( Constants::META_RETURN_WAYBILL, true );
		if ( ! empty( $existing_return ) ) {
			return array(
				'success' => false,
				'waybill' => $existing_return,
				'message' => sprintf( __( 'Return shipment already created with AWB %s.', 'wpcalibrate-shipping-connector' ), $existing_return ),
			);
		}

		$warehouse = null;
		if ( ! empty( $warehouse_id ) ) {
			$warehouse = $this->warehouse_service->get_warehouse( $warehouse_id );
		}
		if ( empty( $warehouse ) ) {
			$warehouse = $this->warehouse_service->get_default_warehouse();
		}

		if ( empty( $warehouse ) ) {
			return array(
				'success' => false,
				'waybill' => '',
				'message' => __( 'No return warehouse available.', 'wpcalibrate-shipping-connector' ),
			);
		}

		$payload = $this->mapper->map_reverse_shipment( $order, $warehouse, $qc_data );

		$form_data = array(
			'format' => 'json',
			'data'   => wp_json_encode( $payload ),
		);

		$response = $this->client->post_form( 'api/cmu/create.json', $form_data );

		if ( ! $response->is_success() ) {
			$err = $response->get_error_message();
			$this->logger->error( "Return shipment creation failed for order #{$order_id}: {$err}" );
			return array(
				'success' => false,
				'waybill' => '',
				'message' => sprintf( __( 'Failed to manifest return: %s', 'wpcalibrate-shipping-connector' ), $err ),
			);
		}

		$data    = $response->get_data();
		$waybill = '';

		if ( ! empty( $data['packages'] ) && is_array( $data['packages'] ) ) {
			$first_pkg = $data['packages'][0] ?? array();
			$waybill   = (string) ( $first_pkg['waybill'] ?? '' );
		} elseif ( ! empty( $data['upload_wbn'] ) ) {
			$waybill = (string) $data['upload_wbn'];
		}

		if ( empty( $waybill ) ) {
			return array(
				'success' => false,
				'waybill' => '',
				'message' => __( 'Carrier response did not return a reverse AWB.', 'wpcalibrate-shipping-connector' ),
			);
		}

		// Store reverse shipment AWB separately.
		$order->update_meta_data( Constants::META_RETURN_WAYBILL, $waybill );
		$order->update_meta_data( Constants::META_RETURN_STATUS, 'Pickup Pending' );
		$order->save();

		$order->add_order_note(
			sprintf(
				/* translators: 1: Return waybill, 2: Destination warehouse */
				__( 'Delhivery Reverse Pickup created successfully. Return AWB: %1$s (Destination: %2$s). Note: This does not affect store refund balances.', 'wpcalibrate-shipping-connector' ),
				$waybill,
				$warehouse['name'] ?? ''
			)
		);

		do_action( 'wpcalibrate_shipping_return_created', $order, $waybill );

		return array(
			'success' => true,
			'waybill' => $waybill,
			'message' => sprintf( __( 'Reverse shipment created with AWB %s.', 'wpcalibrate-shipping-connector' ), $waybill ),
		);
	}
}
