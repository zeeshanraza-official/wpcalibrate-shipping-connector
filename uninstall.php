<?php
/**
 * Plugin Uninstaller.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

// Exit if accessed directly or not uninstalling.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check administrator data retention preference.
$settings = get_option( 'wpcalibrate_shipping_connector_settings', array() );
$delete_data = ( $settings['delete_data_on_uninstall'] ?? 'no' ) === 'yes';

if ( $delete_data ) {
	// Clean plugin-owned options.
	delete_option( 'wpcalibrate_shipping_connector_settings' );
	delete_option( 'wpcalibrate_shipping_connector_db_version' );
	delete_option( 'wpcalibrate_shipping_connector_warehouses' );
	delete_option( 'wpcalibrate_shipping_connector_last_sync' );

	// Unschedule Action Scheduler tasks if available.
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( 'wpcalibrate_shipping_async_manifest' );
		as_unschedule_all_actions( 'wpcalibrate_shipping_tracking_poll' );
		as_unschedule_all_actions( 'wpcalibrate_shipping_process_webhook' );
	}

	// Clean transient caches.
	global $wpdb;
	$prefix_srv  = $wpdb->esc_like( '_transient_wpcalibrate_delhivery_' ) . '%';
	$prefix_rate = $wpdb->esc_like( '_transient_wpcalibrate_delhivery_rate_' ) . '%';
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $prefix_srv, $prefix_rate ) );

	// NOTE: WooCommerce order shipment metadata, AWBs, and tracking audit history
	// are treated as financial/logistics business records and are preserved.
}
