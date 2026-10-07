<?php
/**
 * Null License Manager implementation for unconfigured licensing provider.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\License;

use WPCalibrate\ShippingConnector\Support\Constants;
use WPCalibrate\ShippingConnector\Support\Helpers;

/**
 * Class NullLicenseManager
 */
class NullLicenseManager implements LicenseManagerInterface {

	/**
	 * Check if a licensing provider is configured and active.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return false;
	}

	/**
	 * Get current license status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return 'not_configured';
	}

	/**
	 * Get masked license key.
	 *
	 * @return string
	 */
	public function get_masked_license_key(): string {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		$key      = (string) ( $settings['license_key'] ?? '' );
		return Helpers::mask_secret( $key );
	}

	/**
	 * Activate license key.
	 *
	 * @param string $license_key
	 * @return array{success: bool, message: string}
	 */
	public function activate( string $license_key ): array {
		return array(
			'success' => false,
			'message' => __( 'Licensing provider integration is not configured in this environment.', 'wpcalibrate-shipping-connector' ),
		);
	}

	/**
	 * Deactivate current license.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function deactivate(): array {
		return array(
			'success' => false,
			'message' => __( 'Licensing provider integration is not configured in this environment.', 'wpcalibrate-shipping-connector' ),
		);
	}

	/**
	 * Validate license status.
	 *
	 * @return array{success: bool, status: string, message: string}
	 */
	public function validate(): array {
		return array(
			'success' => false,
			'status'  => 'not_configured',
			'message' => __( 'Licensing provider integration is not configured in this environment.', 'wpcalibrate-shipping-connector' ),
		);
	}

	/**
	 * Get license expiry date or notice string.
	 *
	 * @return string
	 */
	public function get_expiry_date(): string {
		return __( 'N/A (Integration Pending)', 'wpcalibrate-shipping-connector' );
	}

	/**
	 * Get renewal URL.
	 *
	 * @return string
	 */
	public function get_renewal_url(): string {
		return Constants::SUPPORT_MARKETPLACE;
	}
}
