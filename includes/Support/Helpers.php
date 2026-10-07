<?php
/**
 * Helper utilities.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Support;

/**
 * Class Helpers
 */
final class Helpers {

	/**
	 * Mask sensitive strings (e.g. API tokens, secrets).
	 *
	 * @param string $secret Plain secret string.
	 * @param int    $visible_chars Number of visible characters at start and end.
	 * @return string Masked string.
	 */
	public static function mask_secret( string $secret, int $visible_chars = 4 ): string {
		$length = strlen( $secret );
		if ( $length === 0 ) {
			return '';
		}
		if ( $length <= $visible_chars * 2 ) {
			return str_repeat( '*', $length );
		}
		$start = substr( $secret, 0, $visible_chars );
		$end   = substr( $secret, -$visible_chars );
		return $start . str_repeat( '*', max( 4, $length - ( $visible_chars * 2 ) ) ) . $end;
	}

	/**
	 * Sanitize data array recursively.
	 *
	 * @param mixed $data Input data.
	 * @return mixed Sanitized output.
	 */
	public static function sanitize_recursive( mixed $data ): mixed {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = self::sanitize_recursive( $value );
			}
			return $data;
		}

		if ( is_string( $data ) ) {
			return sanitize_text_field( $data );
		}

		return $data;
	}

	/**
	 * Get carrier public tracking URL.
	 *
	 * @param string $waybill Waybill / AWB.
	 * @return string Safe tracking URL.
	 */
	public static function get_tracking_url( string $waybill ): string {
		$clean_waybill = preg_replace( '/[^A-Za-z0-9_-]/', '', $waybill );
		return Constants::URL_TRACKING_PUBLIC . rawurlencode( (string) $clean_waybill );
	}

	/**
	 * Format an Indian phone number safely (10 digits).
	 *
	 * @param string $phone Raw phone string.
	 * @return string 10-digit number.
	 */
	public static function clean_phone( string $phone ): string {
		$digits = preg_replace( '/\D/', '', $phone );
		if ( str_starts_with( $digits, '91' ) && strlen( $digits ) === 12 ) {
			$digits = substr( $digits, 2 );
		} elseif ( str_starts_with( $digits, '0' ) && strlen( $digits ) === 11 ) {
			$digits = substr( $digits, 1 );
		}
		return substr( $digits, -10 );
	}

	/**
	 * Redact sensitive fields from an array for logging.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	public static function redact_for_logging( array $data ): array {
		$sensitive_keys = array(
			'token',
			'api_token',
			'password',
			'secret',
			'webhook_secret',
			'license_key',
			'Authorization',
			'authorization',
			'card',
			'cvv',
		);

		foreach ( $data as $key => $value ) {
			if ( is_string( $key ) && in_array( strtolower( $key ), array_map( 'strtolower', $sensitive_keys ), true ) ) {
				$data[ $key ] = '***REDACTED***';
			} elseif ( is_array( $value ) ) {
				$data[ $key ] = self::redact_for_logging( $value );
			}
		}

		return $data;
	}
}
