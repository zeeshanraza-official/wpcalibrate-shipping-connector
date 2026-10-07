<?php
/**
 * Settings Tab: Tracking & Webhooks
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$webhook_url    = rest_url( 'wpcalibrate-shipping/v1/webhook' );
$webhook_secret = (string) ( $settings['webhook_secret'] ?? '' );
?>

<div class="wpcalibrate-tracking-webhooks-settings">
	<h2><?php esc_html_e( 'Tracking Synchronization & Webhook Configuration', 'wpcalibrate-shipping-connector' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Configure background tracking status synchronization and real-time Delhivery Scan-Push webhook listener.', 'wpcalibrate-shipping-connector' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'wpcalibrate_shipping_settings_nonce', 'wpcalibrate_shipping_nonce' ); ?>
		<input type="hidden" name="wpcalibrate_shipping_action" value="save_tracking_settings" />
		<input type="hidden" name="current_tab" value="tracking_webhooks" />

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Background Polling Synchronization', 'wpcalibrate-shipping-connector' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_tracking_sync" value="yes" <?php checked( $settings['enable_tracking_sync'] ?? 'yes', 'yes' ); ?> />
						<?php esc_html_e( 'Enable recurring background synchronization via Action Scheduler for active shipments', 'wpcalibrate-shipping-connector' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Automatically polls Delhivery tracking API in batches for non-terminal active shipments.', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Customer Storefront Display', 'wpcalibrate-shipping-connector' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="show_customer_tracking" value="yes" <?php checked( $settings['show_customer_tracking'] ?? 'yes', 'yes' ); ?> />
						<?php esc_html_e( 'Display AWB tracking link on My Account order view', 'wpcalibrate-shipping-connector' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'WooCommerce Emails', 'wpcalibrate-shipping-connector' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_email_tracking" value="yes" <?php checked( $settings['enable_email_tracking'] ?? 'yes', 'yes' ); ?> />
						<?php esc_html_e( 'Include shipment tracking number and carrier link in customer emails', 'wpcalibrate-shipping-connector' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Tracking Preferences', 'wpcalibrate-shipping-connector' ); ?></button>
		</p>
	</form>

	<hr style="margin: 30px 0;">

	<h3><?php esc_html_e( 'Delhivery Scan-Push Webhook Endpoint', 'wpcalibrate-shipping-connector' ); ?></h3>
	<p class="description">
		<?php esc_html_e( 'Provide the following webhook URL and authorization secret to your Delhivery account manager or enter it in your Delhivery One developer settings.', 'wpcalibrate-shipping-connector' ); ?>
	</p>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'REST Webhook URL', 'wpcalibrate-shipping-connector' ); ?></th>
			<td>
				<input type="text" readonly value="<?php echo esc_url( $webhook_url ); ?>" class="large-text" id="wpcalibrate-webhook-url" onclick="this.select();" />
				<p class="description"><?php esc_html_e( 'Endpoint expects HTTP POST requests. Responds within 500ms and queues event processing.', 'wpcalibrate-shipping-connector' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Webhook Secret Header', 'wpcalibrate-shipping-connector' ); ?></th>
			<td>
				<code><?php echo esc_html( $webhook_secret ); ?></code>
				<p class="description"><?php esc_html_e( 'Header: X-Delhivery-Secret or Bearer Token.', 'wpcalibrate-shipping-connector' ); ?></p>

				<form method="post" action="" style="margin-top: 10px;" onsubmit="return confirm('<?php esc_attr_e( 'Regenerate secret? You must update Delhivery webhook settings afterwards.', 'wpcalibrate-shipping-connector' ); ?>');">
					<?php wp_nonce_field( 'wpcalibrate_shipping_settings_nonce', 'wpcalibrate_shipping_nonce' ); ?>
					<input type="hidden" name="wpcalibrate_shipping_action" value="regenerate_webhook_secret" />
					<input type="hidden" name="current_tab" value="tracking_webhooks" />
					<button type="submit" class="button button-secondary"><?php esc_html_e( 'Regenerate Secret', 'wpcalibrate-shipping-connector' ); ?></button>
				</form>
			</td>
		</tr>
	</table>
</div>
