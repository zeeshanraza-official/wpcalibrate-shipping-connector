<?php
/**
 * Plugin Activator.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Core;

use WPCalibrate\ShippingConnector\Support\Constants;

/**
 * Class Activator
 */
final class Activator {

	/**
	 * Run activation logic.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Store DB schema version if not set.
		if ( false === get_option( Constants::OPTION_DB_VERSION ) ) {
			add_option( Constants::OPTION_DB_VERSION, Constants::DB_VERSION, '', 'no' );
		}

		// Initialize default options conservatively without auto-enabling live rates or auto-shipments.
		$current_settings = get_option( Constants::OPTION_SETTINGS );
		if ( false === $current_settings ) {
			$defaults = array(
				'environment'              => Constants::ENV_STAGING,
				'api_token'                => '',
				'client_name'              => '',
				'live_rates_enabled'       => 'no',
				'shipping_title'           => 'Delhivery Shipping',
				'shipping_mode'            => 'S', // Surface default
				'markup_type'              => 'none', // none, fixed, percentage
				'markup_amount'            => 0,
				'enable_fallback_rate'     => 'no',
				'fallback_rate_amount'     => 0,
				'free_shipping_threshold'  => 0,
				'default_weight_kg'        => 0.5,
				'default_length_cm'        => 10,
				'default_width_cm'         => 10,
				'default_height_cm'        => 10,
				'missing_dimensions_rule'  => 'fallback', // fallback or disable
				'cod_mapping'              => array( 'cod' ),
				'cache_ttl_serviceability' => 86400, // 24 hours
				'cache_ttl_rates'          => 3600,  // 1 hour
				'auto_shipment_enabled'    => 'no',
				'auto_shipment_trigger'    => 'processing',
				'default_package_desc'     => 'Merchandise',
				'webhook_secret'           => wp_generate_password( 32, false ),
				'enable_tracking_sync'     => 'yes',
				'tracking_sync_interval'   => 'hourly',
				'show_customer_tracking'   => 'yes',
				'enable_email_tracking'    => 'yes',
				'logging_level'            => 'errors', // off, errors, debug
				'delete_data_on_uninstall' => 'no',
			);
			add_option( Constants::OPTION_SETTINGS, $defaults, '', 'no' );
		}

		// Initialize default empty warehouses list if not exists.
		if ( false === get_option( Constants::OPTION_WAREHOUSES ) ) {
			add_option( Constants::OPTION_WAREHOUSES, array(), '', 'no' );
		}
	}
}
