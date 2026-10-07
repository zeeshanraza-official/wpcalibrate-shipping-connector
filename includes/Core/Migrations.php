<?php
/**
 * Versioned Migrations runner.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Core;

use WPCalibrate\ShippingConnector\Support\Constants;

/**
 * Class Migrations
 */
final class Migrations {

	/**
	 * Run migrations if version discrepancy exists.
	 *
	 * @return void
	 */
	public static function run(): void {
		$installed_version = get_option( Constants::OPTION_DB_VERSION, '0.0.0' );

		if ( version_compare( (string) $installed_version, Constants::DB_VERSION, '<' ) ) {
			self::migrate( (string) $installed_version );
			update_option( Constants::OPTION_DB_VERSION, Constants::DB_VERSION, 'no' );
		}
	}

	/**
	 * Execute specific migration steps idempotently.
	 *
	 * @param string $from_version
	 * @return void
	 */
	private static function migrate( string $from_version ): void {
		// Example: 1.0.0 baseline initialization
		if ( version_compare( $from_version, '1.0.0', '<' ) ) {
			// Baseline migrations ensure default settings exist.
			Activator::activate();
		}
	}
}
