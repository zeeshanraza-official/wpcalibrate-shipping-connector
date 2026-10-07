<?php
/**
 * Settings Tab: Logs & Diagnostic Tools
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wc_logs_url = admin_url( 'admin.php?page=wc-status&tab=logs' );
?>

<div class="wpcalibrate-logs-tools">
	<h2><?php esc_html_e( 'Diagnostics, Logging & Maintenance Tools', 'wpcalibrate-shipping-connector' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Test carrier serviceability live, manage plugin caches, and configure sanitized logging.', 'wpcalibrate-shipping-connector' ); ?>
	</p>

	<!-- Diagnostic Pincode Tool -->
	<div class="card" style="margin-top: 20px; padding: 15px;">
		<h3><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Pincode Serviceability Diagnostic', 'wpcalibrate-shipping-connector' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Directly query Delhivery serviceability API for any Indian 6-digit PIN code (bypasses local cache).', 'wpcalibrate-shipping-connector' ); ?></p>
		
		<form method="post" action="" style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
			<?php wp_nonce_field( 'wpcalibrate_shipping_settings_nonce', 'wpcalibrate_shipping_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_shipping_action" value="test_pincode" />
			<input type="hidden" name="current_tab" value="logs_tools" />
			<input type="text" name="test_pin" placeholder="e.g. 110001" maxlength="6" pattern="[0-9]{6}" required class="small-text" style="width: 140px;" />
			<button type="submit" class="button button-secondary"><?php esc_html_e( 'Check Serviceability', 'wpcalibrate-shipping-connector' ); ?></button>
		</form>
	</div>

	<!-- Cache Management Tool -->
	<div class="card" style="margin-top: 20px; padding: 15px;">
		<h3><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Plugin Cache Management', 'wpcalibrate-shipping-connector' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Purge temporary cached rate quotes and PIN serviceability responses from WordPress options transients.', 'wpcalibrate-shipping-connector' ); ?></p>
		
		<form method="post" action="" style="margin-top: 10px;">
			<?php wp_nonce_field( 'wpcalibrate_shipping_settings_nonce', 'wpcalibrate_shipping_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_shipping_action" value="clear_caches" />
			<input type="hidden" name="current_tab" value="logs_tools" />
			<button type="submit" class="button button-secondary"><?php esc_html_e( 'Purge Shipping Caches', 'wpcalibrate-shipping-connector' ); ?></button>
		</form>
	</div>

	<!-- Logging & Retention Settings -->
	<div class="card" style="margin-top: 20px; padding: 15px;">
		<h3><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Logging & Retention Policy', 'wpcalibrate-shipping-connector' ); ?></h3>

		<form method="post" action="">
			<?php wp_nonce_field( 'wpcalibrate_shipping_settings_nonce', 'wpcalibrate_shipping_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_shipping_action" value="save_logging_settings" />
			<input type="hidden" name="current_tab" value="logs_tools" />

			<table class="form-table">
				<tr>
					<th scope="row"><label for="logging_level"><?php esc_html_e( 'Logging Level', 'wpcalibrate-shipping-connector' ); ?></label></th>
					<td>
						<select name="logging_level" id="logging_level">
							<option value="off" <?php selected( $settings['logging_level'] ?? 'errors', 'off' ); ?>><?php esc_html_e( 'Off', 'wpcalibrate-shipping-connector' ); ?></option>
							<option value="errors" <?php selected( $settings['logging_level'] ?? 'errors', 'errors' ); ?>><?php esc_html_e( 'Errors Only (Recommended)', 'wpcalibrate-shipping-connector' ); ?></option>
							<option value="debug" <?php selected( $settings['logging_level'] ?? 'errors', 'debug' ); ?>><?php esc_html_e( 'Debug (Full Operational Traces)', 'wpcalibrate-shipping-connector' ); ?></option>
						</select>
						<p class="description">
							<?php
							printf(
								/* translators: %s: WooCommerce log screen link */
								esc_html__( 'Logs are written to WooCommerce system logs under prefix "wpcalibrate-delhivery". Sensitive API tokens and customer passwords are automatically redacted. View logs in %s.', 'wpcalibrate-shipping-connector' ),
								'<a href="' . esc_url( $wc_logs_url ) . '" target="_blank">' . esc_html__( 'WooCommerce &rarr; Status &rarr; Logs', 'wpcalibrate-shipping-connector' ) . '</a>'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Uninstall Data Retention', 'wpcalibrate-shipping-connector' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="delete_data_on_uninstall" value="yes" <?php checked( $settings['delete_data_on_uninstall'] ?? 'no', 'yes' ); ?> />
							<?php esc_html_e( 'Delete plugin settings and warehouses upon uninstalling this plugin', 'wpcalibrate-shipping-connector' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Order shipment metadata and historical waybills are preserved as essential accounting records.', 'wpcalibrate-shipping-connector' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Logging Preferences', 'wpcalibrate-shipping-connector' ); ?></button>
			</p>
		</form>
	</div>
</div>
