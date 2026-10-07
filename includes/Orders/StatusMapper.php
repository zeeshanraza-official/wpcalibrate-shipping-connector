<?php
/**
 * Carrier Status Normalization and WooCommerce Order Status Mapping.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Orders;

/**
 * Class StatusMapper
 */
class StatusMapper {

	/**
	 * Configured status mappings (Carrier internal state => WC Order status).
	 *
	 * @var array<string, string>
	 */
	private array $configured_mappings;

	/**
	 * Constructor.
	 *
	 * @param array<string, string> $configured_mappings
	 */
	public function __construct( array $configured_mappings = array() ) {
		$this->configured_mappings = $configured_mappings;
	}

	/**
	 * Normalize Delhivery raw status string or status code to internal status slug.
	 *
	 * @param string $raw_status
	 * @param string $status_code
	 * @return string Internal status slug ('manifested', 'in_transit', 'out_for_delivery', 'delivered', 'failed', 'rto', 'cancelled')
	 */
	public static function normalize_carrier_status( string $raw_status, string $status_code = '' ): string {
		$clean = strtolower( trim( $raw_status ) );
		$code  = strtoupper( trim( $status_code ) );

		if ( str_contains( $clean, 'cancelled' ) || str_contains( $clean, 'canceled' ) || 'cn' === $code ) {
			return 'cancelled';
		}

		if ( str_contains( $clean, 'rto' ) || str_contains( $clean, 'returned' ) || str_contains( $clean, 'return to origin' ) ) {
			return 'rto';
		}

		if ( str_contains( $clean, 'ndr' ) || str_contains( $clean, 'undelivered' ) || str_contains( $clean, 'failed' ) ) {
			return 'ndr';
		}

		if ( 'manifested' === $clean || 'ud' === strtolower( $code ) || 'open' === $clean ) {
			return 'manifested';
		}

		if ( str_contains( $clean, 'delivered' ) ) {
			return 'delivered';
		}

		if ( str_contains( $clean, 'out for delivery' ) || str_contains( $clean, 'pending' ) ) {
			return 'out_for_delivery';
		}

		if ( str_contains( $clean, 'in transit' ) || str_contains( $clean, 'dispatched' ) || 'it' === $code || 'dl' === $code ) {
			return 'in_transit';
		}

		return 'in_transit';
	}

	/**
	 * Get corresponding WooCommerce order status if an explicit mapping is configured.
	 *
	 * @param string $normalized_carrier_status
	 * @return string|null WooCommerce order status (without 'wc-' prefix) or null if no mapping.
	 */
	public function get_wc_status_for( string $normalized_carrier_status ): ?string {
		if ( empty( $this->configured_mappings[ $normalized_carrier_status ] ) ) {
			return null;
		}

		$target = (string) $this->configured_mappings[ $normalized_carrier_status ];

		// If set to 'none' or empty, do not change order status.
		if ( 'none' === $target || empty( $target ) ) {
			return null;
		}

		return preg_replace( '/^wc-/', '', $target );
	}
}
