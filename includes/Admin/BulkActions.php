<?php
/**
 * Safe Bulk Order Actions for WooCommerce Orders List.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Admin;

use WPCalibrate\ShippingConnector\Services\ShipmentService;
use WPCalibrate\ShippingConnector\Services\TrackingService;
use WPCalibrate\ShippingConnector\Core\Capabilities;

/**
 * Class BulkActions
 */
class BulkActions {

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
	 * Constructor.
	 *
	 * @param ShipmentService $shipment_service
	 * @param TrackingService $tracking_service
	 */
	public function __construct( ShipmentService $shipment_service, TrackingService $tracking_service ) {
		$this->shipment_service = $shipment_service;
		$this->tracking_service = $tracking_service;
	}

	/**
	 * Initialize bulk action hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'register_bulk_actions' ) );
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'register_bulk_actions' ) );

		add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_actions' ), 10, 3 );

		add_action( 'admin_notices', array( $this, 'render_bulk_notices' ) );
	}

	/**
	 * Register Delhivery bulk actions in order list dropdown.
	 *
	 * @param array<string, string> $actions
	 * @return array<string, string>
	 */
	public function register_bulk_actions( array $actions ): array {
		if ( Capabilities::can_manage_operations() ) {
			$actions['wpcalibrate_manifest_shipments'] = __( 'Delhivery: Manifest Shipments', 'wpcalibrate-shipping-connector' );
			$actions['wpcalibrate_refresh_tracking']   = __( 'Delhivery: Refresh Tracking', 'wpcalibrate-shipping-connector' );
		}
		return $actions;
	}

	/**
	 * Handle execution of bulk actions.
	 *
	 * @param string             $redirect_to
	 * @param string             $action
	 * @param array<int, string> $order_ids
	 * @return string
	 */
	public function handle_bulk_actions( string $redirect_to, string $action, array $order_ids ): string {
		if ( ! in_array( $action, array( 'wpcalibrate_manifest_shipments', 'wpcalibrate_refresh_tracking' ), true ) ) {
			return $redirect_to;
		}

		if ( ! Capabilities::can_manage_operations() ) {
			return $redirect_to;
		}

		$success_count = 0;
		$skipped_count = 0;
		$failed_count  = 0;

		if ( 'wpcalibrate_manifest_shipments' === $action ) {
			foreach ( $order_ids as $id ) {
				$order = wc_get_order( (int) $id );
				if ( ! $order ) {
					$failed_count++;
					continue;
				}

				$res = $this->shipment_service->create_shipment( $order );
				if ( $res['success'] ) {
					$success_count++;
				} elseif ( str_contains( $res['message'], 'already manifested' ) ) {
					$skipped_count++;
				} else {
					$failed_count++;
				}
			}
		} elseif ( 'wpcalibrate_refresh_tracking' === $action ) {
			foreach ( $order_ids as $id ) {
				$order = wc_get_order( (int) $id );
				if ( ! $order ) {
					$failed_count++;
					continue;
				}

				$res = $this->tracking_service->refresh_order_tracking( $order );
				if ( $res['success'] ) {
					$success_count++;
				} else {
					$skipped_count++;
				}
			}
		}

		return add_query_arg(
			array(
				'wpcalibrate_bulk_action'  => $action,
				'wpcalibrate_bulk_success' => $success_count,
				'wpcalibrate_bulk_skipped' => $skipped_count,
				'wpcalibrate_bulk_failed'  => $failed_count,
			),
			$redirect_to
		);
	}

	/**
	 * Render bulk action notice.
	 *
	 * @return void
	 */
	public function render_bulk_notices(): void {
		if ( ! isset( $_GET['wpcalibrate_bulk_action'] ) ) {
			return;
		}

		$success = (int) ( $_GET['wpcalibrate_bulk_success'] ?? 0 );
		$skipped = (int) ( $_GET['wpcalibrate_bulk_skipped'] ?? 0 );
		$failed  = (int) ( $_GET['wpcalibrate_bulk_failed'] ?? 0 );

		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Delhivery Bulk Operation Completed:', 'wpcalibrate-shipping-connector' ); ?></strong>
				<?php
				printf(
					/* translators: 1: Success count, 2: Skipped count, 3: Failed count */
					esc_html__( '%1$d succeeded, %2$d skipped, %3$d failed.', 'wpcalibrate-shipping-connector' ),
					$success,
					$skipped,
					$failed
				);
				?>
			</p>
		</div>
		<?php
	}
}
