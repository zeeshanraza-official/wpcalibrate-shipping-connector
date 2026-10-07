<?php
/**
 * Plugin Logger facility using WooCommerce logging.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Logging;

use WPCalibrate\ShippingConnector\Support\Constants;
use WPCalibrate\ShippingConnector\Support\Helpers;

/**
 * Class Logger
 */
final class Logger {

	/**
	 * Log source identifier.
	 */
	public const SOURCE = 'wpcalibrate-delhivery';

	/**
	 * Log levels.
	 */
	public const LEVEL_OFF    = 'off';
	public const LEVEL_ERRORS = 'errors';
	public const LEVEL_DEBUG  = 'debug';

	/**
	 * Configured logging level.
	 *
	 * @var string
	 */
	private string $level;

	/**
	 * Constructor.
	 *
	 * @param string $level Configured level ('off', 'errors', 'debug').
	 */
	public function __construct( string $level = self::LEVEL_ERRORS ) {
		$this->level = $level;
	}

	/**
	 * Set current level.
	 *
	 * @param string $level
	 * @return void
	 */
	public function set_level( string $level ): void {
		$this->level = $level;
	}

	/**
	 * Log an error message.
	 *
	 * @param string               $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		if ( self::LEVEL_OFF === $this->level ) {
			return;
		}

		$this->write( 'error', $message, $context );
	}

	/**
	 * Log an informational/debug message.
	 *
	 * @param string               $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void {
		if ( self::LEVEL_DEBUG !== $this->level ) {
			return;
		}

		$this->write( 'debug', $message, $context );
	}

	/**
	 * Log an informational message.
	 *
	 * @param string               $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void {
		if ( self::LEVEL_DEBUG !== $this->level ) {
			return;
		}

		$this->write( 'info', $message, $context );
	}

	/**
	 * Internal writer to WC logger.
	 *
	 * @param string               $level
	 * @param string               $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	private function write( string $level, string $message, array $context = array() ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$logger          = wc_get_logger();
		$cleaned_context = Helpers::redact_for_logging( $context );
		$context_data    = apply_filters( 'wpcalibrate_shipping_log_context', $cleaned_context, $level, $message );

		$logger->log(
			$level,
			$message,
			array_merge(
				array( 'source' => self::SOURCE ),
				is_array( $context_data ) ? $context_data : array()
			)
		);
	}
}
