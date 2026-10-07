<?php
/**
 * Settings Tab: Support & Contact
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wpcalibrate-support-settings">
	<h2><?php esc_html_e( 'WPCalibrate Support & Contact', 'wpcalibrate-shipping-connector' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Need assistance configuring Delhivery or customizing your shipping workflows? Our engineering team is here to assist.', 'wpcalibrate-shipping-connector' ); ?>
	</p>

	<div class="wpcalibrate-cards-grid" style="margin-top: 25px;">
		<div class="wpcalibrate-card">
			<div class="card-header">
				<span class="dashicons dashicons-email-alt"></span>
				<h3><?php esc_html_e( 'Technical Support Email', 'wpcalibrate-shipping-connector' ); ?></h3>
			</div>
			<div class="card-body">
				<p><?php esc_html_e( 'Direct email support for commercial plugin customers:', 'wpcalibrate-shipping-connector' ); ?></p>
				<p>
					<a href="<?php echo esc_url( 'mailto:' . \WPCalibrate\ShippingConnector\Support\Constants::SUPPORT_EMAIL ); ?>" class="button button-primary">
						<?php echo esc_html( \WPCalibrate\ShippingConnector\Support\Constants::SUPPORT_EMAIL ); ?>
					</a>
				</p>
			</div>
		</div>

		<div class="wpcalibrate-card">
			<div class="card-header">
				<span class="dashicons dashicons-phone"></span>
				<h3><?php esc_html_e( 'Phone & WhatsApp', 'wpcalibrate-shipping-connector' ); ?></h3>
			</div>
			<div class="card-body">
				<p><?php esc_html_e( 'Urgent support and enterprise consultation:', 'wpcalibrate-shipping-connector' ); ?></p>
				<p>
					<a href="<?php echo esc_url( 'https://wa.me/447474795976' ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary">
						<?php echo esc_html( \WPCalibrate\ShippingConnector\Support\Constants::SUPPORT_PHONE ); ?>
					</a>
				</p>
			</div>
		</div>

		<div class="wpcalibrate-card">
			<div class="card-header">
				<span class="dashicons dashicons-admin-site"></span>
				<h3><?php esc_html_e( 'Official Website', 'wpcalibrate-shipping-connector' ); ?></h3>
			</div>
			<div class="card-body">
				<p><?php esc_html_e( 'Browse plugin documentation, developer updates, and guides:', 'wpcalibrate-shipping-connector' ); ?></p>
				<p>
					<a href="<?php echo esc_url( \WPCalibrate\ShippingConnector\Support\Constants::SUPPORT_WEBSITE ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary">
						<?php echo esc_html( \WPCalibrate\ShippingConnector\Support\Constants::SUPPORT_WEBSITE ); ?> &rarr;
					</a>
				</p>
			</div>
		</div>

		<div class="wpcalibrate-card">
			<div class="card-header">
				<span class="dashicons dashicons-cart"></span>
				<h3><?php esc_html_e( 'WPCalibrate Marketplace', 'wpcalibrate-shipping-connector' ); ?></h3>
			</div>
			<div class="card-body">
				<p><?php esc_html_e( 'Explore our suite of WooCommerce performance and logistics extensions:', 'wpcalibrate-shipping-connector' ); ?></p>
				<p>
					<a href="<?php echo esc_url( \WPCalibrate\ShippingConnector\Support\Constants::SUPPORT_MARKETPLACE ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary">
						<?php echo esc_html( \WPCalibrate\ShippingConnector\Support\Constants::SUPPORT_MARKETPLACE ); ?> &rarr;
					</a>
				</p>
			</div>
		</div>
	</div>
</div>
