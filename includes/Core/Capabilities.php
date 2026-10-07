<?php
/**
 * Role and Capability checks.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Core;

use WPCalibrate\ShippingConnector\Support\Constants;

/**
 * Class Capabilities
 */
final class Capabilities {

	/**
	 * Check if current user can manage plugin settings (credentials, license, etc.).
	 *
	 * @return bool
	 */
	public static function can_manage_settings(): bool {
		$cap = apply_filters( 'wpcalibrate_shipping_manage_settings_capability', Constants::CAP_MANAGE_SETTINGS );
		return current_user_can( $cap );
	}

	/**
	 * Check if current user can manage shipping operations (manifest, tracking, cancel, labels, pickups).
	 *
	 * @return bool
	 */
	public static function can_manage_operations(): bool {
		$cap = apply_filters( 'wpcalibrate_shipping_manage_operations_capability', Constants::CAP_MANAGE_OPERATIONS );
		return current_user_can( $cap ) || current_user_can( 'administrator' );
	}

	/**
	 * Check if current user can view tracking for a specific order.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return bool
	 */
	public static function can_view_order_tracking( int $order_id ): bool {
		if ( self::can_manage_operations() ) {
			return true;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$current_user_id = get_current_user_id();
		$order           = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		return (int) $order->get_customer_id() === (int) $current_user_id;
	}
}
