<?php
/**
 * License Manager Interface.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\License;

/**
 * Interface LicenseManagerInterface
 */
interface LicenseManagerInterface {

	/**
	 * Check if a licensing provider is configured and active.
	 *
	 * @return bool
	 */
	public function is_configured(): bool;

	/**
	 * Get current license status ('active', 'inactive', 'expired', 'not_configured').
	 *
	 * @return string
	 */
	public function get_status(): string;

	/**
	 * Get masked license key.
	 *
	 * @return string
	 */
	public function get_masked_license_key(): string;

	/**
	 * Activate license key.
	 *
	 * @param string $license_key
	 * @return array{success: bool, message: string}
	 */
	public function activate( string $license_key ): array;

	/**
	 * Deactivate current license.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function deactivate(): array;

	/**
	 * Validate license status.
	 *
	 * @return array{success: bool, status: string, message: string}
	 */
	public function validate(): array;

	/**
	 * Get license expiry date or notice string.
	 *
	 * @return string
	 */
	public function get_expiry_date(): string;

	/**
	 * Get renewal URL.
	 *
	 * @return string
	 */
	public function get_renewal_url(): string;
}
