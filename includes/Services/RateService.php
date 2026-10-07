<?php
/**
 * Live Rate calculation service.
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
 * Class RateService
 */
class RateService {

	/**
	 * Delhivery client.
	 *
	 * @var DelhiveryClient
	 */
	private DelhiveryClient $client;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Service settings.
	 *
	 * @var array<string, mixed>
	 */
	private array $settings;

	/**
	 * Constructor.
	 *
	 * @param DelhiveryClient      $client
	 * @param Logger               $logger
	 * @param array<string, mixed> $settings
	 */
	public function __construct( DelhiveryClient $client, Logger $logger, array $settings = array() ) {
		$this->client   = $client;
		$this->logger   = $logger;
		$this->settings = $settings;
	}

	/**
	 * Calculate shipping rate for a package.
	 *
	 * @param string $origin_pin      Warehouse pincode.
	 * @param string $dest_pin        Customer destination pincode.
	 * @param int    $weight_grams    Chargeable weight in grams.
	 * @param string $mode            Shipping mode ('S' or 'E').
	 * @param string $payment_type    Payment type ('Pre-paid' or 'COD').
	 * @param float  $cart_subtotal   Cart subtotal for free shipping threshold checks.
	 * @param bool   $bypass_cache
	 * @return array{
	 *     success: bool,
	 *     cost: float,
	 *     original_cost: float,
	 *     is_free: bool,
	 *     is_fallback: bool,
	 *     error_message: string
	 * }
	 */
	public function calculate_rate(
		string $origin_pin,
		string $dest_pin,
		int $weight_grams,
		string $mode = 'S',
		string $payment_type = 'Pre-paid',
		float $cart_subtotal = 0.0,
		bool $bypass_cache = false
	): array {
		$origin_pin   = preg_replace( '/\D/', '', $origin_pin );
		$dest_pin     = preg_replace( '/\D/', '', $dest_pin );
		$weight_grams = max( 50, $weight_grams );
		$mode         = in_array( $mode, array( 'S', 'E' ), true ) ? $mode : 'S';

		// Check Free Shipping Threshold.
		$free_threshold = (float) ( $this->settings['free_shipping_threshold'] ?? 0 );
		if ( $free_threshold > 0 && $cart_subtotal >= $free_threshold ) {
			return array(
				'success'       => true,
				'cost'          => 0.0,
				'original_cost' => 0.0,
				'is_free'       => true,
				'is_fallback'   => false,
				'error_message' => '',
			);
		}

		if ( empty( $origin_pin ) || empty( $dest_pin ) ) {
			return $this->handle_fallback( __( 'Missing origin or destination PIN code.', 'wpcalibrate-shipping-connector' ) );
		}

		// Rate Caching.
		$cache_key = Constants::TRANSIENT_RATE . md5( "{$origin_pin}_{$dest_pin}_{$weight_grams}_{$mode}_{$payment_type}" );
		$ttl       = (int) ( $this->settings['cache_ttl_rates'] ?? 3600 );

		if ( ! $bypass_cache ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				$cached['cost'] = $this->apply_adjustments( (float) $cached['original_cost'] );
				return $cached;
			}
		}

		$params = array(
			'md'    => $mode,
			'cgm'   => $weight_grams,
			'o_pin' => (int) $origin_pin,
			'd_pin' => (int) $dest_pin,
			'ss'    => 'Delivered',
			'pt'    => $payment_type,
		);

		$response = $this->client->get( 'api/kinko/v1/invoice/charges/.json', $params );

		if ( ! $response->is_success() ) {
			$this->logger->error(
				'Delhivery Rate Calculation API error',
				array(
					'params' => $params,
					'error'  => $response->get_error_message(),
				)
			);
			return $this->handle_fallback( $response->get_error_message() );
		}

		$data = $response->get_data();
		$raw_cost = 0.0;

		// Parse Delhivery charge array.
		if ( ! empty( $data ) && is_array( $data ) ) {
			$first_record = is_array( $data[0] ?? null ) ? $data[0] : $data;
			$raw_cost     = (float) ( $first_record['total_amount'] ?? $first_record['gross_amount'] ?? 0.0 );
		}

		if ( $raw_cost <= 0.0 ) {
			return $this->handle_fallback( __( 'Invalid charge returned by carrier.', 'wpcalibrate-shipping-connector' ) );
		}

		$final_cost = $this->apply_adjustments( $raw_cost );

		$result = array(
			'success'       => true,
			'cost'          => $final_cost,
			'original_cost' => $raw_cost,
			'is_free'       => false,
			'is_fallback'   => false,
			'error_message' => '',
		);

		if ( $ttl > 0 ) {
			set_transient( $cache_key, $result, $ttl );
		}

		return $result;
	}

	/**
	 * Apply fixed or percentage adjustments, ensuring non-negative total.
	 *
	 * @param float $base_cost
	 * @return float
	 */
	public function apply_adjustments( float $base_cost ): float {
		$markup_type   = (string) ( $this->settings['markup_type'] ?? 'none' );
		$markup_amount = (float) ( $this->settings['markup_amount'] ?? 0.0 );

		$adjusted = $base_cost;

		if ( 'fixed' === $markup_type ) {
			$adjusted = $base_cost + $markup_amount;
		} elseif ( 'percentage' === $markup_type ) {
			$adjusted = $base_cost + ( $base_cost * ( $markup_amount / 100 ) );
		}

		$final_cost = max( 0.0, round( $adjusted, 2 ) );

		return (float) apply_filters( 'wpcalibrate_shipping_calculated_rate', $final_cost, $base_cost, $this->settings );
	}

	/**
	 * Handle fallback rate when live calculation fails.
	 *
	 * @param string $error_message
	 * @return array{
	 *     success: bool,
	 *     cost: float,
	 *     original_cost: float,
	 *     is_free: bool,
	 *     is_fallback: bool,
	 *     error_message: string
	 * }
	 */
	private function handle_fallback( string $error_message ): array {
		$fallback_enabled = ( $this->settings['enable_fallback_rate'] ?? 'no' ) === 'yes';
		$fallback_amount  = (float) ( $this->settings['fallback_rate_amount'] ?? 0.0 );

		if ( $fallback_enabled && $fallback_amount >= 0.0 ) {
			$this->logger->info(
				'Using configured fallback rate due to carrier rate failure',
				array( 'error' => $error_message, 'fallback_amount' => $fallback_amount )
			);
			return array(
				'success'       => true,
				'cost'          => $fallback_amount,
				'original_cost' => $fallback_amount,
				'is_free'       => false,
				'is_fallback'   => true,
				'error_message' => $error_message,
			);
		}

		return array(
			'success'       => false,
			'cost'          => 0.0,
			'original_cost' => 0.0,
			'is_free'       => false,
			'is_fallback'   => false,
			'error_message' => $error_message,
		);
	}
}
