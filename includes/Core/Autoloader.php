<?php
/**
 * Custom PSR-4 Autoloader for WPCalibrate Shipping Connector.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Core;

/**
 * Class Autoloader
 */
final class Autoloader {

	/**
	 * Base namespace prefix.
	 *
	 * @var string
	 */
	private static string $prefix = 'WPCalibrate\\ShippingConnector\\';

	/**
	 * Base directory path for classes.
	 *
	 * @var string
	 */
	private static string $base_dir = '';

	/**
	 * Register the autoloader.
	 *
	 * @param string $includes_path Absolute path to the includes directory.
	 * @return void
	 */
	public static function register( string $includes_path ): void {
		self::$base_dir = trailingslashit( $includes_path );

		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Autoload handler.
	 *
	 * @param string $class Fully qualified class name.
	 * @return bool True if loaded, false otherwise.
	 */
	public static function load( string $class ): bool {
		if ( ! str_starts_with( $class, self::$prefix ) ) {
			return false;
		}

		$relative_class = substr( $class, strlen( self::$prefix ) );
		$file           = self::$base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
			return true;
		}

		return false;
	}
}
