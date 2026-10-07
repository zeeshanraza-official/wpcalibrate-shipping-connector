<?php
/**
 * Non-Delivery Report (NDR) Management Service.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Services;

use WC_Order;
use WPCalibrate\ShippingConnector\Api\DelhiveryClient;
use WPCalibrate\ShippingConnector\Api\ApiResult;
use WPCalibrate\ShippingConnector\Orders\OrderShipmentRepository;
use WPCalibrate\ShippingConnector\Logging\Logger;
use WPCalibrate\ShippingConnector\Support\Constants;

/**
 * Class NdrService
 */
class NdrService {

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
	 * @param Logger                  $logger
	 */
	public function __construct(
		DelhiveryClient $client,
		OrderShipmentRepository $repository,
		Logger $logger
	) {
		$this->client     = $client;
		$this->repository = $repository;
		$this->logger     = $logger;
	}

	/**
	 * Submit an NDR action for an order.
	 *
	 * @param WC_Order $order
	 * @param string   $action 'REATTEMPT', 'RESCHEDULE', or 'RTO'
	 * @param string   $reschedule_date YYYY-MM-DD
	 * @param string   $remarks
	 * @return array{success: bool, message: string}
	 */
	public function submit_ndr_action(
		WC_Order $order,
		string $action,
		string $reschedule_date = '',
		string $remarks = ''
	): array {
		$waybill = $this->repository->get_waybill( $order );

		if ( empty( $waybill ) ) {
			return array(
				'success' => false,
				'message' => __( 'No active waybill found for NDR action.', 'wpcalibrate-shipping-connector' ),
			);
		}

		$valid_actions = array( 'REATTEMPT', 'RESCHEDULE', 'RTO' );
		$clean_action  = strtoupper( trim( $action ) );

		if ( ! in_array( $clean_action, $valid_actions, true ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid NDR action specified.', 'wpcalibrate-shipping-connector' ),
			);
		}

		$payload = array(
			'waybill' => $waybill,
			'action'  => $clean_action,
			'remarks' => sanitize_text_field( $remarks ),
		);

		if ( 'RESCHEDULE' === $clean_action && ! empty( $reschedule_date ) ) {
			$payload['reschedule_date'] = sanitize_text_field( $reschedule_date );
		}

		$response = $this->client->post_json( 'api/p/update/', $payload );

		if ( ! $response->is_success() ) {
			$err = $response->get_error_message();
			$this->logger->error( "NDR action {$clean_action} failed for waybill {$waybill}: {$err}" );
			return array(
				'success' => false,
				'message' => sprintf( __( 'Carrier NDR action failed: %s', 'wpcalibrate-shipping-connector' ), $err ),
			);
		}

		// Record action in order metadata.
		$raw_history = $order->get_meta( Constants::META_NDR_HISTORY, true );
		$history     = is_array( $raw_history ) ? $raw_history : array();
		$history[]   = array(
			'action'          => $clean_action,
			'reschedule_date' => $reschedule_date,
			'remarks'         => $remarks,
			'timestamp'       => current_time( 'mysql' ),
			'user_id'         => get_current_user_id(),
		);

		$order->update_meta_data( Constants::META_NDR_ACTION, $clean_action );
		$order->update_meta_data( Constants::META_NDR_HISTORY, $history );
		$order->save();

		$order->add_order_note(
			sprintf(
				/* translators: 1: Action, 2: Waybill, 3: Remarks */
				__( 'Delhivery NDR action "%1$s" submitted for waybill %2$s. %3$s', 'wpcalibrate-shipping-connector' ),
				$clean_action,
				$waybill,
				$remarks ? "(Notes: {$remarks})" : ''
			)
		);

		return array(
			'success' => true,
			'message' => sprintf( __( 'NDR action "%s" submitted successfully.', 'wpcalibrate-shipping-connector' ), $clean_action ),
		);
	}
}
