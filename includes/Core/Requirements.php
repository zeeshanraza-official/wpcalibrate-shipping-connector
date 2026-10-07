<?php
/**
 * Requirements verification class.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Core;

use WPCalibrate\ShippingConnector\Support\Constants;

/**
 * Class Requirements
 */
final class Requirements {

	/**
	 * Errors collected during checks.
	 *
	 * @var array<int, string>
	 */
	private static array $errors = array();

	/**
	 * Verify that all host requirements are met.
	 *
	 * @return bool True if compatible, false otherwise.
	 */
	public static function check(): bool {
		self::$errors = array();

		// Check PHP version.
		if ( version_compare( PHP_VERSION, Constants::MIN_PHP_VERSION, '<' ) ) {
			self::$errors[] = sprintf(
				/* translators: 1: Required PHP version, 2: Current PHP version */
				__( 'WPCalibrate Shipping Connector requires PHP %1$s or higher. Your server is running PHP %2$s.', 'wpcalibrate-shipping-connector' ),
				Constants::MIN_PHP_VERSION,
				PHP_VERSION
			);
		}

		// Check WordPress version.
		global $wp_version;
		if ( ! empty( $wp_version ) && version_compare( $wp_version, Constants::MIN_WP_VERSION, '<' ) ) {
			self::$errors[] = sprintf(
				/* translators: 1: Required WP version, 2: Current WP version */
				__( 'WPCalibrate Shipping Connector requires WordPress %1$s or higher. Your site is running WordPress %2$s.', 'wpcalibrate-shipping-connector' ),
				Constants::MIN_WP_VERSION,
				$wp_version
			);
		}

		// Check WooCommerce active.
		if ( ! self::is_woocommerce_active() ) {
			self::$errors[] = __( 'WPCalibrate Shipping Connector requires WooCommerce to be installed and active.', 'wpcalibrate-shipping-connector' );
		} elseif ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, Constants::MIN_WC_VERSION, '<' ) ) {
			self::$errors[] = sprintf(
				/* translators: 1: Required WC version, 2: Current WC version */
				__( 'WPCalibrate Shipping Connector requires WooCommerce %1$s or higher. Your site is running WooCommerce %2$s.', 'wpcalibrate-shipping-connector' ),
				Constants::MIN_WC_VERSION,
				WC_VERSION
			);
		}

		return empty( self::$errors );
	}

	/**
	 * Check if WooCommerce is installed and active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' ) || in_array(
			'woocommerce/woocommerce.php',
			apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ),
			true
		) || ( is_multisite() && array_key_exists(
			'woocommerce/woocommerce.php',
			apply_filters( 'active_sitewide_plugins', get_site_option( 'active_sitewide_plugins', array() ) )
		) );
	}

	/**
	 * Get collected requirement errors.
	 *
	 * @return array<int, string>
	 */
	public static function get_errors(): array {
		return self::$errors;
	}
}
