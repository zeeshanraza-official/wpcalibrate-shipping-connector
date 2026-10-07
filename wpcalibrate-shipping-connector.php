<?php
/**
 * Plugin Name: WPCalibrate Shipping Connector
 * Plugin URI: https://marketplace.wpcalibrate.com/
 * Description: Enterprise WooCommerce integration for Delhivery B2C domestic logistics including live shipping rates, pin code serviceability, manifests, tracking, webhooks, and NDR management.
 * Version: 1.0.0
 * Author: WPCalibrate
 * Author URI: https://wpcalibrate.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpcalibrate-shipping-connector
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * WC requires at least: 8.5
 * WC tested up to: 11.1.2
 * Update URI: https://github.com/zeeshanraza-official/wpcalibrate-shipping-connector
 * GitHub Plugin URI: zeeshanraza-official/wpcalibrate-shipping-connector
 * Primary Branch: main
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector;

use WPCalibrate\ShippingConnector\Core\Autoloader;
use WPCalibrate\ShippingConnector\Core\Plugin;
use WPCalibrate\ShippingConnector\Core\Activator;
use WPCalibrate\ShippingConnector\Core\Deactivator;
use WPCalibrate\ShippingConnector\Core\Requirements;

// Prevent direct script execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin directory constants.
define( 'WPCALIBRATE_SHIPPING_CONNECTOR_FILE', __FILE__ );
define( 'WPCALIBRATE_SHIPPING_CONNECTOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCALIBRATE_SHIPPING_CONNECTOR_URL', plugin_dir_url( __FILE__ ) );

// Load custom PSR-4 Autoloader.
require_once WPCALIBRATE_SHIPPING_CONNECTOR_PATH . 'includes/Core/Autoloader.php';
Autoloader::register( WPCALIBRATE_SHIPPING_CONNECTOR_PATH . 'includes' );

// Register Activation and Deactivation hooks.
register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );

/**
 * Bootstrap the plugin after plugins are loaded.
 *
 * @return void
 */
function wpcalibrate_shipping_connector_init(): void {
	load_plugin_textdomain(
		'wpcalibrate-shipping-connector',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	if ( ! Requirements::check() ) {
		// Display admin notice if requirements fail without crashing the site.
		add_action(
			'admin_notices',
			function () {
				$errors = Requirements::get_errors();
				?>
				<div class="notice notice-error">
					<p><strong><?php esc_html_e( 'WPCalibrate Shipping Connector cannot run:', 'wpcalibrate-shipping-connector' ); ?></strong></p>
					<ul style="list-style: disc; margin-left: 20px;">
						<?php foreach ( $errors as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php
			}
		);
		return;
	}

	// Initialize main plugin instance.
	Plugin::get_instance();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\wpcalibrate_shipping_connector_init' );
