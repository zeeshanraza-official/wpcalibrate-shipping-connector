<?php
/**
 * Shipment Tracking and Status Synchronization Service.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Services;

use WC_Order;
use WPCalibrate\ShippingConnector\Api\DelhiveryClient;
use WPCalibrate\ShippingConnector\Api\ApiResult;
use WPCalibrate\ShippingConnector\Orders\OrderShipmentRepository;
use WPCalibrate\ShippingConnector\Orders\StatusMapper;
use WPCalibrate\ShippingConnector\Logging\Logger;
use WPCalibrate\ShippingConnector\Support\Constants;

/**
 * Class TrackingService
 */
class TrackingService {

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
	 * Status mapper.
	 *
	 * @var StatusMapper
	 */
	private StatusMapper $status_mapper;

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
	 * @param StatusMapper            $status_mapper
	 * @param Logger                  $logger
	 */
	public function __construct(
		DelhiveryClient $client,
		OrderShipmentRepository $repository,
		StatusMapper $status_mapper,
		Logger $logger
	) {
		$this->client        = $client;
		$this->repository    = $repository;
		$this->status_mapper = $status_mapper;
		$this->logger        = $logger;
	}

	/**
	 * Refresh tracking information for an individual order.
	 *
	 * @param WC_Order $order
	 * @return array{success: bool, status: string, message: string}
	 */
	public function refresh_order_tracking( WC_Order $order ): array {
		$waybill = $this->repository->get_waybill( $order );

		if ( empty( $waybill ) ) {
			return array(
				'success' => false,
				'status'  => '',
				'message' => __( 'Order has no active waybill to track.', 'wpcalibrate-shipping-connector' ),
			);
		}

		$results = $this->track_waybills( array( $waybill ) );

		if ( empty( $results[ $waybill ] ) ) {
			return array(
				'success' => false,
				'status'  => '',
				'message' => __( 'No tracking details returned by carrier for this waybill.', 'wpcalibrate-shipping-connector' ),
			);
		}

		$track_info = $results[ $waybill ];
		$this->apply_tracking_update( $order, $track_info );

		return array(
			'success' => true,
			'status'  => $track_info['status'],
			'message' => sprintf( __( 'Tracking refreshed. Current status: %s', 'wpcalibrate-shipping-connector' ), $track_info['status'] ),
		);
	}

	/**
	 * Fetch tracking details for multiple waybills in batch.
	 *
	 * @param array<int, string> $waybills
	 * @return array<string, array{
	 *     awb: string,
	 *     status: string,
	 *     status_code: string,
	 *     location: string,
	 *     date_time: string,
	 *     instructions: string,
	 *     scans: list<array<string, mixed>>
	 * }> Keyed by waybill.
	 */
	public function track_waybills( array $waybills ): array {
		$clean_waybills = array_filter( array_map( fn( $w ) => preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $w ), $waybills ) );

		if ( empty( $clean_waybills ) ) {
			return array();
		}

		// Delhivery supports comma-separated waybill query.
		$wb_param = implode( ',', array_slice( $clean_waybills, 0, 50 ) );

		$response = $this->client->get(
			'api/v1/packages/json/',
			array(
				'waybill' => $wb_param,
				'verbose' => 2,
			)
		);

		if ( ! $response->is_success() ) {
			$this->logger->error( 'Batch tracking failed: ' . $response->get_error_message() );
			return array();
		}

		$data    = $response->get_data();
		$results = array();

		// Delhivery structure: ShipmentData array.
		$shipment_data = $data['ShipmentData'] ?? array();
		if ( ! is_array( $shipment_data ) ) {
			return array();
		}

		foreach ( $shipment_data as $item ) {
			$shipment = $item['Shipment'] ?? $item;
			$awb      = (string) ( $shipment['AWB'] ?? '' );

			if ( empty( $awb ) ) {
				continue;
			}

			$status_obj   = $shipment['Status'] ?? array();
			$status_text  = (string) ( $status_obj['Status'] ?? 'In Transit' );
			$status_code  = (string) ( $status_obj['StatusCode'] ?? $status_obj['StatusType'] ?? '' );
			$location     = (string) ( $status_obj['StatusLocation'] ?? '' );
			$date_time    = (string) ( $status_obj['StatusDateTime'] ?? '' );
			$instructions = (string) ( $status_obj['Instructions'] ?? '' );

			// Parse scans array.
			$scans = array();
			if ( ! empty( $shipment['Scans'] ) && is_array( $shipment['Scans'] ) ) {
				foreach ( $shipment['Scans'] as $scan_item ) {
					$detail = $scan_item['ScanDetail'] ?? $scan_item;
					$scans[] = array(
						'date_time'    => (string) ( $detail['ScanDateTime'] ?? '' ),
						'scan'         => (string) ( $detail['Scan'] ?? '' ),
						'location'     => (string) ( $detail['ScannedLocation'] ?? '' ),
						'instructions' => (string) ( $detail['Instructions'] ?? '' ),
						'scan_type'    => (string) ( $detail['ScanType'] ?? '' ),
					);
				}
			}

			$results[ $awb ] = array(
				'awb'          => $awb,
				'status'       => $status_text,
				'status_code'  => $status_code,
				'location'     => $location,
				'date_time'    => $date_time,
				'instructions' => $instructions,
				'scans'        => $scans,
			);
		}

		return $results;
	}

	/**
	 * Apply tracking updates to an order.
	 *
	 * @param WC_Order             $order
	 * @param array<string, mixed> $track_info
	 * @return void
	 */
	public function apply_tracking_update( WC_Order $order, array $track_info ): void {
		$status_text = (string) ( $track_info['status'] ?? '' );
		$status_code = (string) ( $track_info['status_code'] ?? '' );

		$latest_scan = array(
			'status'       => $status_text,
			'status_code'  => $status_code,
			'location'     => $track_info['location'] ?? '',
			'date_time'    => $track_info['date_time'] ?? current_time( 'mysql' ),
			'instructions' => $track_info['instructions'] ?? '',
		);

		$this->repository->update_tracking_status( $order, $status_text, $status_code, $latest_scan );

		// Normalize status and evaluate WC order status mapping.
		$normalized = StatusMapper::normalize_carrier_status( $status_text, $status_code );
		$new_wc_status = $this->status_mapper->get_wc_status_for( $normalized );

		if ( ! empty( $new_wc_status ) && $order->get_status() !== $new_wc_status ) {
			$this->logger->info(
				"Updating WC Order #{$order->get_id()} status to {$new_wc_status} based on Delhivery carrier status: {$status_text}"
			);
			$order->update_status(
				$new_wc_status,
				sprintf(
					/* translators: 1: New WC status, 2: Carrier status */
					__( 'Order status automatically updated to %1$s via Delhivery tracking: %2$s', 'wpcalibrate-shipping-connector' ),
					$new_wc_status,
					$status_text
				)
			);
		}

		do_action( 'wpcalibrate_shipping_tracking_updated', $order, $track_info );
	}

	/**
	 * Run background polling for active non-terminal shipments.
	 *
	 * @return int Number of shipments updated.
	 */
	public function sync_active_shipments(): int {
		// Find active orders that have an AWB and are not Delivered or Cancelled.
		$orders = wc_get_orders(
			array(
				'limit'        => 30,
				'meta_key'     => Constants::META_WAYBILL,
				'meta_compare' => 'EXISTS',
				'status'       => array( 'processing', 'on-hold', 'pending' ),
			)
		);

		if ( empty( $orders ) ) {
			return 0;
		}

		$waybill_map = array();
		foreach ( $orders as $order ) {
			$waybill = $this->repository->get_waybill( $order );
			$status  = strtolower( (string) $order->get_meta( Constants::META_SHIPMENT_STATUS, true ) );

			// Skip terminal shipments.
			if ( empty( $waybill ) || 'delivered' === $status || 'cancelled' === $status || 'rto' === $status ) {
				continue;
			}

			$waybill_map[ $waybill ] = $order;
		}

		if ( empty( $waybill_map ) ) {
			return 0;
		}

		$results = $this->track_waybills( array_keys( $waybill_map ) );
		$updated_count = 0;

		foreach ( $results as $awb => $track_info ) {
			if ( isset( $waybill_map[ $awb ] ) ) {
				$this->apply_tracking_update( $waybill_map[ $awb ], $track_info );
				$updated_count++;
			}
		}

		update_option( Constants::OPTION_LAST_SYNC, current_time( 'mysql' ), 'no' );
		return $updated_count;
	}
}
