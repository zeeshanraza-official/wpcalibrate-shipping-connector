<?php
/**
 * Action Scheduler Background Jobs Manager.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Background;

use WC_Order;
use WPCalibrate\ShippingConnector\Support\Constants;
use WPCalibrate\ShippingConnector\Services\ShipmentService;
use WPCalibrate\ShippingConnector\Services\TrackingService;
use WPCalibrate\ShippingConnector\Services\WebhookService;
use WPCalibrate\ShippingConnector\Logging\Logger;

/**
 * Class ActionSchedulerManager
 */
class ActionSchedulerManager {

	/**
	 * Shipment service.
	 *
	 * @var ShipmentService
	 */
	private ShipmentService $shipment_service;

	/**
	 * Tracking service.
	 *
	 * @var TrackingService
	 */
	private TrackingService $tracking_service;

	/**
	 * Webhook service.
	 *
	 * @var WebhookService
	 */
	private WebhookService $webhook_service;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param ShipmentService $shipment_service
	 * @param TrackingService $tracking_service
	 * @param WebhookService  $webhook_service
	 * @param Logger          $logger
	 */
	public function __construct(
		ShipmentService $shipment_service,
		TrackingService $tracking_service,
		WebhookService $webhook_service,
		Logger $logger
	) {
		$this->shipment_service = $shipment_service;
		$this->tracking_service = $tracking_service;
		$this->webhook_service  = $webhook_service;
		$this->logger           = $logger;
	}

	/**
	 * Initialize Action Scheduler and order hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( Constants::HOOK_ASYNC_MANIFEST, array( $this, 'handle_async_manifest' ), 10, 2 );
		add_action( Constants::HOOK_TRACKING_POLL, array( $this, 'handle_tracking_poll' ) );
		add_action( Constants::HOOK_PROCESS_WEBHOOK, array( $this, 'handle_webhook_job' ), 10, 1 );

		// Hook into WooCommerce order status change for optional automatic shipment creation.
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status_changed' ), 20, 4 );

		// Register recurring tracking sync schedule.
		$this->ensure_recurring_schedule();
	}

	/**
	 * Ensure recurring tracking schedule exists in Action Scheduler.
	 *
	 * @return void
	 */
	public function ensure_recurring_schedule(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) || ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		$enabled  = ( $settings['enable_tracking_sync'] ?? 'yes' ) === 'yes';

		if ( ! $enabled ) {
			as_unschedule_all_actions( Constants::HOOK_TRACKING_POLL );
			return;
		}

		if ( ! as_has_scheduled_action( Constants::HOOK_TRACKING_POLL ) ) {
			// Schedule hourly (3600 seconds).
			as_schedule_recurring_action(
				time() + 300,
				3600,
				Constants::HOOK_TRACKING_POLL,
				array(),
				'wpcalibrate-shipping'
			);
		}
	}

	/**
	 * Trigger automatic shipment creation when an order enters configured status.
	 *
	 * @param int      $order_id
	 * @param string   $old_status
	 * @param string   $new_status
	 * @param WC_Order $order
	 * @return void
	 */
	public function on_order_status_changed( int $order_id, string $old_status, string $new_status, WC_Order $order ): void {
		$settings     = get_option( Constants::OPTION_SETTINGS, array() );
		$auto_enabled = ( $settings['auto_shipment_enabled'] ?? 'no' ) === 'yes';
		$trigger      = (string) ( $settings['auto_shipment_trigger'] ?? 'processing' );

		if ( ! $auto_enabled || $new_status !== $trigger ) {
			return;
		}

		// Only shippable orders with Indian address.
		if ( 'IN' !== $order->get_shipping_country() && 'IN' !== $order->get_billing_country() ) {
			return;
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action(
				Constants::HOOK_ASYNC_MANIFEST,
				array( 'order_id' => $order_id, 'attempt' => 1 ),
				'wpcalibrate-shipping'
			);
		} else {
			$this->shipment_service->create_shipment( $order );
		}
	}

	/**
	 * Background job for asynchronous shipment manifestation.
	 *
	 * @param int $order_id
	 * @param int $attempt
	 * @return void
	 */
	public function handle_async_manifest( int $order_id, int $attempt = 1 ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$result = $this->shipment_service->create_shipment( $order );

		if ( ! $result['success'] && $attempt < 3 ) {
			// Schedule retry with exponential backoff (5 min, 15 min).
			$delay = $attempt * 300;
			if ( function_exists( 'as_schedule_single_action' ) ) {
				as_schedule_single_action(
					time() + $delay,
					Constants::HOOK_ASYNC_MANIFEST,
					array( 'order_id' => $order_id, 'attempt' => $attempt + 1 ),
					'wpcalibrate-shipping'
				);
			}
		}
	}

	/**
	 * Recurring tracking sync handler.
	 *
	 * @return void
	 */
	public function handle_tracking_poll(): void {
		$count = $this->tracking_service->sync_active_shipments();
		$this->logger->debug( "Action Scheduler tracking poll finished: {$count} shipments updated." );
	}

	/**
	 * Background job for webhook event processing.
	 *
	 * @param array<string, mixed> $payload
	 * @return void
	 */
	public function handle_webhook_job( array $payload ): void {
		$this->webhook_service->process_event( $payload );
	}
}
