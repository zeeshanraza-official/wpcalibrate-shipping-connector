<?php
/**
 * Settings Tab: License
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$license_key = $this->license_manager->get_masked_license_key();
$status      = $this->license_manager->get_status();
$expiry      = $this->license_manager->get_expiry_date();
$renewal_url = $this->license_manager->get_renewal_url();
?>

<div class="wpcalibrate-license-settings">
	<h2><?php esc_html_e( 'Plugin License Management', 'wpcalibrate-shipping-connector' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Manage your WPCalibrate commercial license and plugin update entitlement.', 'wpcalibrate-shipping-connector' ); ?>
	</p>

	<div class="notice notice-warning inline" style="margin: 20px 0; padding: 12px;">
		<p>
			<strong><?php esc_html_e( 'Notice:', 'wpcalibrate-shipping-connector' ); ?></strong>
			<?php esc_html_e( 'The WPCalibrate licensing provider integration is currently pending configuration for this development environment. All core shipping, rate, manifest, and tracking features remain fully functional.', 'wpcalibrate-shipping-connector' ); ?>
		</p>
	</div>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'License Status', 'wpcalibrate-shipping-connector' ); ?></th>
			<td>
				<span class="badge badge-warning"><?php esc_html_e( 'Integration Pending (Development Build)', 'wpcalibrate-shipping-connector' ); ?></span>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="license_key"><?php esc_html_e( 'License Key', 'wpcalibrate-shipping-connector' ); ?></label></th>
			<td>
				<input type="text" name="license_key" id="license_key" value="<?php echo esc_attr( $license_key ); ?>" class="regular-text" placeholder="XXXX-XXXX-XXXX-XXXX" disabled />
				<p class="description"><?php esc_html_e( 'License activation controls will be enabled once the licensing provider is connected.', 'wpcalibrate-shipping-connector' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Marketplace & Renewals', 'wpcalibrate-shipping-connector' ); ?></th>
			<td>
				<a href="<?php echo esc_url( $renewal_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary">
					<?php esc_html_e( 'Visit WPCalibrate Marketplace &rarr;', 'wpcalibrate-shipping-connector' ); ?>
				</a>
			</td>
		</tr>
	</table>
</div>
