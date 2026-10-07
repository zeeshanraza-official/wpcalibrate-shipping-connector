<?php
/**
 * Settings Tab: Shipping Rates
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$available_gateways = WC()->payment_gateways ? WC()->payment_gateways->get_available_payment_gateways() : array();
$selected_cod       = (array) ( $settings['cod_mapping'] ?? array( 'cod' ) );
?>

<div class="wpcalibrate-shipping-settings">
	<h2><?php esc_html_e( 'Live Shipping Rate Calculation', 'wpcalibrate-shipping-connector' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Configure carrier live rate rules, markup adjustments, fallback options, and COD handling.', 'wpcalibrate-shipping-connector' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'wpcalibrate_shipping_settings_nonce', 'wpcalibrate_shipping_nonce' ); ?>
		<input type="hidden" name="wpcalibrate_shipping_action" value="save_shipping_settings" />
		<input type="hidden" name="current_tab" value="shipping" />

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Live Rates', 'wpcalibrate-shipping-connector' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="live_rates_enabled" value="yes" <?php checked( $settings['live_rates_enabled'] ?? 'no', 'yes' ); ?> />
						<?php esc_html_e( 'Enable live carrier rate calculation in eligible WooCommerce Shipping Zones', 'wpcalibrate-shipping-connector' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="shipping_title"><?php esc_html_e( 'Customer Method Title', 'wpcalibrate-shipping-connector' ); ?></label></th>
				<td>
					<input type="text" name="shipping_title" id="shipping_title" value="<?php echo esc_attr( $settings['shipping_title'] ?? 'Delhivery Shipping' ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Title shown to customers during cart and checkout.', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="shipping_mode"><?php esc_html_e( 'Default Transport Mode', 'wpcalibrate-shipping-connector' ); ?></label></th>
				<td>
					<select name="shipping_mode" id="shipping_mode">
						<option value="S" <?php selected( $settings['shipping_mode'] ?? 'S', 'S' ); ?>><?php esc_html_e( 'Surface (Cost-Effective)', 'wpcalibrate-shipping-connector' ); ?></option>
						<option value="E" <?php selected( $settings['shipping_mode'] ?? 'S', 'E' ); ?>><?php esc_html_e( 'Express (Air / Expedited)', 'wpcalibrate-shipping-connector' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="markup_type"><?php esc_html_e( 'Rate Adjustment (Markup/Discount)', 'wpcalibrate-shipping-connector' ); ?></label></th>
				<td>
					<select name="markup_type" id="markup_type">
						<option value="none" <?php selected( $settings['markup_type'] ?? 'none', 'none' ); ?>><?php esc_html_e( 'None (Exact Carrier Rate)', 'wpcalibrate-shipping-connector' ); ?></option>
						<option value="fixed" <?php selected( $settings['markup_type'] ?? 'none', 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount (₹)', 'wpcalibrate-shipping-connector' ); ?></option>
						<option value="percentage" <?php selected( $settings['markup_type'] ?? 'none', 'percentage' ); ?>><?php esc_html_e( 'Percentage (%)', 'wpcalibrate-shipping-connector' ); ?></option>
					</select>
					<input type="number" step="0.01" name="markup_amount" value="<?php echo esc_attr( (string) ( $settings['markup_amount'] ?? 0 ) ); ?>" style="width: 100px; margin-left: 10px;" />
					<p class="description"><?php esc_html_e( 'Adjust calculated rates before presenting to customers. Rates will never be negative.', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="free_shipping_threshold"><?php esc_html_e( 'Free Shipping Subtotal Threshold (₹)', 'wpcalibrate-shipping-connector' ); ?></label></th>
				<td>
					<input type="number" step="0.01" name="free_shipping_threshold" id="free_shipping_threshold" value="<?php echo esc_attr( (string) ( $settings['free_shipping_threshold'] ?? 0 ) ); ?>" class="small-text" />
					<p class="description"><?php esc_html_e( 'Set to 0 to disable. If order subtotal reaches this amount, shipping charge will be ₹0.00.', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Fallback Rate', 'wpcalibrate-shipping-connector' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_fallback_rate" value="yes" <?php checked( $settings['enable_fallback_rate'] ?? 'no', 'yes' ); ?> />
						<?php esc_html_e( 'Use fallback shipping rate if carrier live-rate API temporarily fails', 'wpcalibrate-shipping-connector' ); ?>
					</label>
					<div style="margin-top: 8px;">
						<label for="fallback_rate_amount"><?php esc_html_e( 'Fallback Amount (₹):', 'wpcalibrate-shipping-connector' ); ?></label>
						<input type="number" step="0.01" name="fallback_rate_amount" id="fallback_rate_amount" value="<?php echo esc_attr( (string) ( $settings['fallback_rate_amount'] ?? 0 ) ); ?>" class="small-text" />
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Default Parcel Dimensions & Weight', 'wpcalibrate-shipping-connector' ); ?></th>
				<td>
					<label><?php esc_html_e( 'Default Weight (kg):', 'wpcalibrate-shipping-connector' ); ?>
						<input type="number" step="0.01" name="default_weight_kg" value="<?php echo esc_attr( (string) ( $settings['default_weight_kg'] ?? 0.5 ) ); ?>" class="small-text" />
					</label>
					<span style="margin: 0 10px;">|</span>
					<label>L (cm): <input type="number" step="0.1" name="default_length_cm" value="<?php echo esc_attr( (string) ( $settings['default_length_cm'] ?? 10 ) ); ?>" class="small-text" /></label>
					<label>W (cm): <input type="number" step="0.1" name="default_width_cm" value="<?php echo esc_attr( (string) ( $settings['default_width_cm'] ?? 10 ) ); ?>" class="small-text" /></label>
					<label>H (cm): <input type="number" step="0.1" name="default_height_cm" value="<?php echo esc_attr( (string) ( $settings['default_height_cm'] ?? 10 ) ); ?>" class="small-text" /></label>
					<p class="description"><?php esc_html_e( 'Used when items in cart are missing physical weight or dimensions.', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="missing_dimensions_rule"><?php esc_html_e( 'Missing Dimensions Rule', 'wpcalibrate-shipping-connector' ); ?></label></th>
				<td>
					<select name="missing_dimensions_rule" id="missing_dimensions_rule">
						<option value="fallback" <?php selected( $settings['missing_dimensions_rule'] ?? 'fallback', 'fallback' ); ?>><?php esc_html_e( 'Use default fallback dimensions', 'wpcalibrate-shipping-connector' ); ?></option>
						<option value="disable" <?php selected( $settings['missing_dimensions_rule'] ?? 'fallback', 'disable' ); ?>><?php esc_html_e( 'Do not display rate if products lack dimensions', 'wpcalibrate-shipping-connector' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Cash on Delivery (COD) Gateways', 'wpcalibrate-shipping-connector' ); ?></th>
				<td>
					<fieldset>
						<?php if ( ! empty( $available_gateways ) ) : ?>
							<?php foreach ( $available_gateways as $gw_id => $gw ) : ?>
								<label style="display: block; margin-bottom: 5px;">
									<input type="checkbox" name="cod_mapping[]" value="<?php echo esc_attr( $gw_id ); ?>" <?php checked( in_array( $gw_id, $selected_cod, true ) ); ?> />
									<?php echo esc_html( $gw->get_title() . " ({$gw_id})" ); ?>
								</label>
							<?php endforeach; ?>
						<?php else : ?>
							<p class="text-muted"><?php esc_html_e( 'No active payment gateways detected.', 'wpcalibrate-shipping-connector' ); ?></p>
						<?php endif; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Select which WooCommerce payment methods trigger Delhivery COD calculation and manifestation.', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Shipping Settings', 'wpcalibrate-shipping-connector' ); ?></button>
		</p>
	</form>
</div>
