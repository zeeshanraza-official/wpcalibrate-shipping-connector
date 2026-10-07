<?php
/**
 * Pincode Serviceability Service.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Services;

use WPCalibrate\ShippingConnector\Api\DelhiveryClient;
use WPCalibrate\ShippingConnector\Api\ApiResult;
use WPCalibrate\ShippingConnector\Support\Constants;
use WPCalibrate\ShippingConnector\Logging\Logger;

/**
 * Class ServiceabilityService
 */
class ServiceabilityService {

	/**
	 * Delhivery client.
	 *
	 * @var DelhiveryClient
	 */
	private DelhiveryClient $client;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Cache TTL in seconds.
	 *
	 * @var int
	 */
	private int $cache_ttl;

	/**
	 * Constructor.
	 *
	 * @param DelhiveryClient $client
	 * @param Logger          $logger
	 * @param int             $cache_ttl
	 */
	public function __construct( DelhiveryClient $client, Logger $logger, int $cache_ttl = 86400 ) {
		$this->client    = $client;
		$this->logger    = $logger;
		$this->cache_ttl = $cache_ttl;
	}

	/**
	 * Check serviceability for an Indian PIN code.
	 *
	 * @param string $pincode 6-digit postal code.
	 * @param bool   $bypass_cache
	 * @return array{
	 *     serviceable: bool,
	 *     prepaid: bool,
	 *     cod: bool,
	 *     pickup: bool,
	 *     oda: bool,
	 *     state_code: string,
	 *     raw: array<string, mixed>
	 * }
	 */
	public function check_pincode( string $pincode, bool $bypass_cache = false ): array {
		$clean_pin = preg_replace( '/\D/', '', $pincode );

		if ( strlen( $clean_pin ) !== 6 ) {
			return array(
				'serviceable' => false,
				'prepaid'     => false,
				'cod'         => false,
				'pickup'      => false,
				'oda'         => false,
				'state_code'  => '',
				'raw'         => array( 'error' => __( 'Invalid 6-digit Indian PIN code.', 'wpcalibrate-shipping-connector' ) ),
			);
		}

		$transient_key = Constants::TRANSIENT_SERVICEABILITY . $clean_pin;

		if ( ! $bypass_cache ) {
			$cached = get_transient( $transient_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$response = $this->client->get(
			'c/api/pin-codes/json/',
			array( 'filter_codes' => $clean_pin )
		);

		if ( ! $response->is_success() ) {
			$this->logger->error(
				'Failed to fetch serviceability from Delhivery',
				array(
					'pincode' => $clean_pin,
					'error'   => $response->get_error_message(),
				)
			);
			return array(
				'serviceable' => false,
				'prepaid'     => false,
				'cod'         => false,
				'pickup'      => false,
				'oda'         => false,
				'state_code'  => '',
				'raw'         => $response->get_data(),
			);
		}

		$data = $response->get_data();
		$info = array(
			'serviceable' => false,
			'prepaid'     => false,
			'cod'         => false,
			'pickup'      => false,
			'oda'         => false,
			'state_code'  => '',
			'raw'         => $data,
		);

		// Delhivery response format contains delivery_codes array.
		if ( ! empty( $data['delivery_codes'] ) && is_array( $data['delivery_codes'] ) ) {
			foreach ( $data['delivery_codes'] as $item ) {
				$code_data = $item['postal_code'] ?? $item;
				$pin_str   = (string) ( $code_data['pin'] ?? '' );

				if ( $pin_str === $clean_pin || empty( $pin_str ) ) {
					$prepaid = ( $code_data['pre_paid'] ?? '' ) === 'Y';
					$cod     = ( $code_data['cod'] ?? '' ) === 'Y';
					$pickup  = ( $code_data['pickup'] ?? '' ) === 'Y';
					$oda     = ( $code_data['is_oda'] ?? '' ) === 'Y';

					$info['serviceable'] = ( $prepaid || $cod );
					$info['prepaid']     = $prepaid;
					$info['cod']         = $cod;
					$info['pickup']      = $pickup;
					$info['oda']         = $oda;
					$info['state_code']  = (string) ( $code_data['state_code'] ?? '' );
					break;
				}
			}
		}

		if ( $this->cache_ttl > 0 ) {
			set_transient( $transient_key, $info, $this->cache_ttl );
		}

		return $info;
	}

	/**
	 * Clear all cached serviceability transients.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		global $wpdb;
		$like = $wpdb->esc_like( '_transient_' . Constants::TRANSIENT_SERVICEABILITY ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
	}
}
