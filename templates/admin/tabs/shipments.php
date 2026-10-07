<?php
/**
 * Settings Tab: Shipments & Automation
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wc_statuses = wc_get_order_statuses();
$trigger_st  = $settings['auto_shipment_trigger'] ?? 'processing';
?>

<div class="wpcalibrate-shipments-settings">
	<h2><?php esc_html_e( 'Shipment Creation & Automation', 'wpcalibrate-shipping-connector' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Configure automated shipment manifestation triggers and defaults.', 'wpcalibrate-shipping-connector' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'wpcalibrate_shipping_settings_nonce', 'wpcalibrate_shipping_nonce' ); ?>
		<input type="hidden" name="wpcalibrate_shipping_action" value="save_shipment_settings" />
		<input type="hidden" name="current_tab" value="shipments" />

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Automatic Shipment Creation', 'wpcalibrate-shipping-connector' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="auto_shipment_enabled" value="yes" <?php checked( $settings['auto_shipment_enabled'] ?? 'no', 'yes' ); ?> />
						<?php esc_html_e( 'Automatically manifest orders with Delhivery when entering trigger status', 'wpcalibrate-shipping-connector' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Disabled by default. Manifestations are queued safely via Action Scheduler.', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="auto_shipment_trigger"><?php esc_html_e( 'Order Trigger Status', 'wpcalibrate-shipping-connector' ); ?></label></th>
				<td>
					<select name="auto_shipment_trigger" id="auto_shipment_trigger">
						<?php foreach ( $wc_statuses as $status_key => $status_label ) : ?>
							<?php $clean_key = ltrim( $status_key, 'wc-' ); ?>
							<option value="<?php echo esc_attr( $clean_key ); ?>" <?php selected( $trigger_st, $clean_key ); ?>>
								<?php echo esc_html( $status_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Select the WooCommerce status that triggers automatic manifestation (e.g. Processing).', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="default_package_desc"><?php esc_html_e( 'Default Package Content Description', 'wpcalibrate-shipping-connector' ); ?></label></th>
				<td>
					<input type="text" name="default_package_desc" id="default_package_desc" value="<?php echo esc_attr( $settings['default_package_desc'] ?? 'Merchandise' ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Default label passed to Delhivery for customs/consignee description when product names are short.', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Shipment Settings', 'wpcalibrate-shipping-connector' ); ?></button>
		</p>
	</form>
</div>
