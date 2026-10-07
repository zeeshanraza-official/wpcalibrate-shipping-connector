<?php
/**
 * Customer Frontend Tracking Display & WooCommerce Email Integration.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Shipping;

use WC_Order;
use WPCalibrate\ShippingConnector\Support\Constants;
use WPCalibrate\ShippingConnector\Support\Helpers;
use WPCalibrate\ShippingConnector\Core\Capabilities;
use WPCalibrate\ShippingConnector\Orders\OrderShipmentRepository;

/**
 * Class CustomerTracking
 */
class CustomerTracking {

	/**
	 * Repository.
	 *
	 * @var OrderShipmentRepository
	 */
	private OrderShipmentRepository $repository;

	/**
	 * Constructor.
	 *
	 * @param OrderShipmentRepository $repository
	 */
	public function __construct( OrderShipmentRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Initialize storefront and email hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'render_my_account_tracking' ) );
		add_action( 'woocommerce_email_order_meta', array( $this, 'render_email_tracking' ), 20, 3 );
	}

	/**
	 * Render tracking details on customer order view.
	 *
	 * @param WC_Order $order
	 * @return void
	 */
	public function render_my_account_tracking( WC_Order $order ): void {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		if ( ( $settings['show_customer_tracking'] ?? 'yes' ) !== 'yes' ) {
			return;
		}

		if ( ! Capabilities::can_view_order_tracking( $order->get_id() ) ) {
			return;
		}

		$waybill = $this->repository->get_waybill( $order );
		if ( empty( $waybill ) || $this->repository->is_cancelled( $order ) ) {
			return;
		}

		$status   = (string) $order->get_meta( Constants::META_SHIPMENT_STATUS, true ) ?: __( 'Dispatched', 'wpcalibrate-shipping-connector' );
		$track_url = Helpers::get_tracking_url( $waybill );

		?>
		<section class="woocommerce-customer-shipment-tracking" style="margin-top: 25px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 6px; background-color: #f8fafc;">
			<h2 style="font-size: 1.1em; margin-top: 0; color: #1e293b;"><?php esc_html_e( 'Shipment Tracking', 'wpcalibrate-shipping-connector' ); ?></h2>
			<p style="margin-bottom: 8px;">
				<strong><?php esc_html_e( 'Carrier:', 'wpcalibrate-shipping-connector' ); ?></strong> Delhivery Express<br>
				<strong><?php esc_html_e( 'Waybill (AWB):', 'wpcalibrate-shipping-connector' ); ?></strong> <code><?php echo esc_html( $waybill ); ?></code><br>
				<strong><?php esc_html_e( 'Shipment Status:', 'wpcalibrate-shipping-connector' ); ?></strong> <?php echo esc_html( $status ); ?>
			</p>
			<p style="margin-bottom: 0;">
				<a href="<?php echo esc_url( $track_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary" style="display: inline-block; padding: 8px 16px; text-decoration: none;">
					<?php esc_html_e( 'Track Package Online &rarr;', 'wpcalibrate-shipping-connector' ); ?>
				</a>
			</p>
		</section>
		<?php
	}

	/**
	 * Render tracking information in WooCommerce transactional emails.
	 *
	 * @param WC_Order $order
	 * @param bool     $sent_to_admin
	 * @param bool     $plain_text
	 * @return void
	 */
	public function render_email_tracking( WC_Order $order, bool $sent_to_admin, bool $plain_text ): void {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		if ( ( $settings['enable_email_tracking'] ?? 'yes' ) !== 'yes' ) {
			return;
		}

		$waybill = $this->repository->get_waybill( $order );
		if ( empty( $waybill ) || $this->repository->is_cancelled( $order ) ) {
			return;
		}

		$track_url = Helpers::get_tracking_url( $waybill );

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'DELHIVERY SHIPMENT TRACKING', 'wpcalibrate-shipping-connector' ) . "\n";
			echo esc_html__( 'Waybill (AWB): ', 'wpcalibrate-shipping-connector' ) . esc_html( $waybill ) . "\n";
			echo esc_html__( 'Track Online: ', 'wpcalibrate-shipping-connector' ) . esc_url( $track_url ) . "\n\n";
		} else {
			?>
			<div style="margin-top: 20px; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;">
				<h3 style="font-size: 15px; margin: 0 0 8px 0; color: #1e293b;"><?php esc_html_e( 'Delhivery Shipment Tracking', 'wpcalibrate-shipping-connector' ); ?></h3>
				<p style="margin: 0 0 8px 0; font-size: 13px;">
					<strong><?php esc_html_e( 'Waybill Number:', 'wpcalibrate-shipping-connector' ); ?></strong> <?php echo esc_html( $waybill ); ?>
				</p>
				<p style="margin: 0;">
					<a href="<?php echo esc_url( $track_url ); ?>" style="color: #2563eb; font-weight: bold; text-decoration: underline;" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Click here to track your package on Delhivery &rarr;', 'wpcalibrate-shipping-connector' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}
}
