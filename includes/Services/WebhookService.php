<?php
/**
 * Webhook Event Processing Service.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Services;

use WC_Order;
use WPCalibrate\ShippingConnector\Orders\OrderShipmentRepository;
use WPCalibrate\ShippingConnector\Services\TrackingService;
use WPCalibrate\ShippingConnector\Logging\Logger;

/**
 * Class WebhookService
 */
class WebhookService {

	/**
	 * Order repository.
	 *
	 * @var OrderShipmentRepository
	 */
	private OrderShipmentRepository $repository;

	/**
	 * Tracking service.
	 *
	 * @var TrackingService
	 */
	private TrackingService $tracking_service;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param OrderShipmentRepository $repository
	 * @param TrackingService         $tracking_service
	 * @param Logger                  $logger
	 */
	public function __construct(
		OrderShipmentRepository $repository,
		TrackingService $tracking_service,
		Logger $logger
	) {
		$this->repository       = $repository;
		$this->tracking_service = $tracking_service;
		$this->logger           = $logger;
	}

	/**
	 * Process a validated webhook event payload idempotently.
	 *
	 * @param array<string, mixed> $payload
	 * @return array{success: bool, message: string}
	 */
	public function process_event( array $payload ): array {
		$shipment_data = $payload['Shipment'] ?? $payload;
		$awb           = (string) ( $shipment_data['AWB'] ?? '' );

		if ( empty( $awb ) ) {
			return array(
				'success' => false,
				'message' => 'Missing AWB in webhook payload.',
			);
		}

		$status_obj  = $shipment_data['Status'] ?? array();
		$status_text = (string) ( $status_obj['Status'] ?? '' );
		$status_date = (string) ( $status_obj['StatusDateTime'] ?? '' );
		$status_code = (string) ( $status_obj['StatusCode'] ?? $status_obj['StatusType'] ?? '' );
		$location    = (string) ( $status_obj['StatusLocation'] ?? '' );

		// Compute deterministic event fingerprint for deduplication.
		$event_fingerprint = 'delhivery_wh_' . md5( "{$awb}_{$status_text}_{$status_date}_{$location}" );

		// Check if event was already processed.
		if ( get_transient( $event_fingerprint ) ) {
			$this->logger->debug( "Duplicate webhook event skipped: {$event_fingerprint} for AWB {$awb}" );
			return array(
				'success' => true,
				'message' => 'Duplicate event skipped.',
			);
		}

		// Find order associated with this AWB.
		$order = $this->repository->get_order_by_waybill( $awb );

		if ( ! $order ) {
			$this->logger->info( "Webhook received for untracked AWB {$awb}. No matching order found." );
			return array(
				'success' => true,
				'message' => 'AWB not found in local orders.',
			);
		}

		$track_info = array(
			'awb'          => $awb,
			'status'       => $status_text,
			'status_code'  => $status_code,
			'location'     => $location,
			'date_time'    => $status_date,
			'instructions' => (string) ( $status_obj['Instructions'] ?? '' ),
			'scans'        => array(),
		);

		// Apply tracking update and order status transitions.
		$this->tracking_service->apply_tracking_update( $order, $track_info );

		// Mark event as processed for 7 days (604,800 seconds).
		set_transient( $event_fingerprint, '1', 7 * 86400 );

		do_action( 'wpcalibrate_shipping_webhook_processed', $order, $awb, $payload );

		return array(
			'success' => true,
			'message' => "Order #{$order->get_id()} updated via webhook scan push.",
		);
	}
}
