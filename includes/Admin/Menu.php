<?php
/**
 * Admin Menu registration.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Admin;

use WPCalibrate\ShippingConnector\Support\Constants;
use WPCalibrate\ShippingConnector\Core\Capabilities;

/**
 * Class Menu
 */
final class Menu {

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Initialize menu hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
		add_action( 'admin_head', array( $this, 'output_menu_icon_styles' ) );
	}

	/**
	 * Output CSS in admin_head to strictly constrain the WPCalibrate sidebar icon dimensions.
	 *
	 * @return void
	 */
	public function output_menu_icon_styles(): void {
		?>
		<style id="wpcalibrate-menu-icon-styles">
			#adminmenu .toplevel_page_wpcalibrate div.wp-menu-image img,
			#adminmenu a[href*="wpcalibrate"] div.wp-menu-image img {
				width: 20px !important;
				height: 20px !important;
				max-width: 20px !important;
				max-height: 20px !important;
				padding-top: 7px !important;
				padding-bottom: 0 !important;
				box-sizing: content-box !important;
				object-fit: contain !important;
				vertical-align: top !important;
				opacity: 0.8;
				transition: opacity 0.15s ease-in-out;
			}
			#adminmenu .toplevel_page_wpcalibrate:hover div.wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.wp-has-current-submenu div.wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.current div.wp-menu-image img {
				opacity: 1 !important;
			}
		</style>
		<?php
	}

	/**
	 * Register the admin menu and submenu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		global $menu;

		$parent_slug = Constants::PARENT_MENU_SLUG;
		$has_parent  = false;

		// Check if a top-level WPCalibrate menu already exists to prevent duplicates.
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && $item[2] === $parent_slug ) {
					$has_parent = true;
					break;
				}
			}
		}

		$icon_white_url = plugin_dir_url( dirname( __DIR__ ) ) . 'branding/icon-white.png';

		// If no other WPCalibrate plugin registered the parent menu, register it.
		if ( ! $has_parent ) {
			add_menu_page(
				'WPCalibrate',
				'WPCalibrate',
				Constants::CAP_MANAGE_OPERATIONS,
				$parent_slug,
				array( $this->settings, 'render' ),
				$icon_white_url,
				58
			);
		}

		// Register the dedicated submenu for Shipping Connector.
		add_submenu_page(
			$parent_slug,
			__( 'WPCalibrate Shipping Connector', 'wpcalibrate-shipping-connector' ),
			__( 'Shipping Connector', 'wpcalibrate-shipping-connector' ),
			Constants::CAP_MANAGE_OPERATIONS,
			Constants::SUBMENU_SLUG,
			array( $this->settings, 'render' )
		);
	}
}
