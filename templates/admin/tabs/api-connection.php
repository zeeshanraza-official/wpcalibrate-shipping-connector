<?php
/**
 * Settings Tab: API Connection
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$masked_token = ! empty( $settings['api_token'] ) ? \WPCalibrate\ShippingConnector\Support\Helpers::mask_secret( (string) $settings['api_token'] ) : '';
$env          = $settings['environment'] ?? 'staging';
$client_name  = $settings['client_name'] ?? '';
?>

<div class="wpcalibrate-api-connection">
	<h2><?php esc_html_e( 'Delhivery API Connection Settings', 'wpcalibrate-shipping-connector' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Configure your Delhivery API credentials. Tokens are stored securely and masked after saving.', 'wpcalibrate-shipping-connector' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'wpcalibrate_shipping_settings_nonce', 'wpcalibrate_shipping_nonce' ); ?>
		<input type="hidden" name="wpcalibrate_shipping_action" value="save_api_settings" />
		<input type="hidden" name="current_tab" value="api_connection" />

		<table class="form-table">
			<tr>
				<th scope="row"><label for="environment"><?php esc_html_e( 'API Environment', 'wpcalibrate-shipping-connector' ); ?></label></th>
				<td>
					<select name="environment" id="environment">
						<option value="staging" <?php selected( $env, 'staging' ); ?>><?php esc_html_e( 'Staging / Sandbox (staging-express.delhivery.com)', 'wpcalibrate-shipping-connector' ); ?></option>
						<option value="production" <?php selected( $env, 'production' ); ?>><?php esc_html_e( 'Production (track.delhivery.com)', 'wpcalibrate-shipping-connector' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Switch between Delhivery staging sandbox and live production logistics servers.', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="client_name"><?php esc_html_e( 'Delhivery Client Name', 'wpcalibrate-shipping-connector' ); ?></label></th>
				<td>
					<input type="text" name="client_name" id="client_name" value="<?php echo esc_attr( $client_name ); ?>" class="regular-text" placeholder="e.g. YOUR_ACCOUNT_NAME" />
					<p class="description"><?php esc_html_e( 'Your registered Delhivery account / client identifier.', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="api_token"><?php esc_html_e( 'API Authentication Token', 'wpcalibrate-shipping-connector' ); ?></label></th>
				<td>
					<input type="password" name="api_token" id="api_token" value="<?php echo esc_attr( $masked_token ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $masked_token ?: 'Enter your API Token' ); ?>" autocomplete="new-password" />
					<p class="description"><?php esc_html_e( 'Your Delhivery API license key. Leave unchanged to keep the existing token.', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Credentials', 'wpcalibrate-shipping-connector' ); ?></button>
		</p>
	</form>

	<hr style="margin: 30px 0;">

	<h3><?php esc_html_e( 'Test API Connection', 'wpcalibrate-shipping-connector' ); ?></h3>
	<p class="description"><?php esc_html_e( 'Verify that your configured API token and environment can communicate with Delhivery without creating parcels.', 'wpcalibrate-shipping-connector' ); ?></p>
	
	<form method="post" action="">
		<?php wp_nonce_field( 'wpcalibrate_shipping_settings_nonce', 'wpcalibrate_shipping_nonce' ); ?>
		<input type="hidden" name="wpcalibrate_shipping_action" value="test_connection" />
		<input type="hidden" name="current_tab" value="api_connection" />
		<p>
			<button type="submit" class="button button-secondary"><?php esc_html_e( 'Run Connection Test', 'wpcalibrate-shipping-connector' ); ?></button>
		</p>
	</form>
</div>
