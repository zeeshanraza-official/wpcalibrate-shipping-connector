<?php
/**
 * Plugin Admin Settings Controller and Tab Renderer.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Admin;

use WPCalibrate\ShippingConnector\Support\Constants;
use WPCalibrate\ShippingConnector\Support\Helpers;
use WPCalibrate\ShippingConnector\Core\Capabilities;
use WPCalibrate\ShippingConnector\Api\DelhiveryClient;
use WPCalibrate\ShippingConnector\Services\ServiceabilityService;
use WPCalibrate\ShippingConnector\Services\WarehouseService;
use WPCalibrate\ShippingConnector\License\LicenseManagerInterface;
use WPCalibrate\ShippingConnector\Logging\Logger;

/**
 * Class Settings
 */
class Settings {

	/**
	 * Delhivery client.
	 *
	 * @var DelhiveryClient
	 */
	private DelhiveryClient $client;

	/**
	 * Serviceability service.
	 *
	 * @var ServiceabilityService
	 */
	private ServiceabilityService $serviceability_service;

	/**
	 * Warehouse service.
	 *
	 * @var WarehouseService
	 */
	private WarehouseService $warehouse_service;

	/**
	 * License manager.
	 *
	 * @var LicenseManagerInterface
	 */
	private LicenseManagerInterface $license_manager;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param DelhiveryClient         $client
	 * @param ServiceabilityService   $serviceability_service
	 * @param WarehouseService        $warehouse_service
	 * @param LicenseManagerInterface $license_manager
	 * @param Logger                  $logger
	 */
	public function __construct(
		DelhiveryClient $client,
		ServiceabilityService $serviceability_service,
		WarehouseService $warehouse_service,
		LicenseManagerInterface $license_manager,
		Logger $logger
	) {
		$this->client                 = $client;
		$this->serviceability_service = $serviceability_service;
		$this->warehouse_service      = $warehouse_service;
		$this->license_manager        = $license_manager;
		$this->logger                 = $logger;
	}

	/**
	 * Initialize settings handlers and actions.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_init', array( $this, 'process_post_actions' ) );
	}

	/**
	 * Process form submissions and diagnostic actions.
	 *
	 * @return void
	 */
	public function process_post_actions(): void {
		if ( ! isset( $_POST['wpcalibrate_shipping_action'] ) ) {
			return;
		}

		if ( ! Capabilities::can_manage_operations() ) {
			wp_die( esc_html__( 'Unauthorized user capability.', 'wpcalibrate-shipping-connector' ) );
		}

		check_admin_referer( 'wpcalibrate_shipping_settings_nonce', 'wpcalibrate_shipping_nonce' );

		$action = sanitize_text_field( wp_unslash( $_POST['wpcalibrate_shipping_action'] ) );
		$tab    = sanitize_text_field( wp_unslash( $_POST['current_tab'] ?? 'dashboard' ) );

		switch ( $action ) {
			case 'save_api_settings':
				$this->save_api_settings();
				break;
			case 'save_shipping_settings':
				$this->save_shipping_settings();
				break;
			case 'save_shipment_settings':
				$this->save_shipment_settings();
				break;
			case 'save_tracking_settings':
				$this->save_tracking_settings();
				break;
			case 'save_warehouse':
				$this->save_warehouse_action();
				break;
			case 'delete_warehouse':
				$this->delete_warehouse_action();
				break;
			case 'test_connection':
				$this->test_connection_action();
				break;
			case 'test_pincode':
				$this->test_pincode_action();
				break;
			case 'clear_caches':
				$this->clear_caches_action();
				break;
			case 'regenerate_webhook_secret':
				$this->regenerate_webhook_secret_action();
				break;
			case 'save_logging_settings':
				$this->save_logging_settings();
				break;
		}

		$redirect_url = add_query_arg(
			array(
				'page' => Constants::SUBMENU_SLUG,
				'tab'  => $tab,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Save API connection settings.
	 *
	 * @return void
	 */
	private function save_api_settings(): void {
		if ( ! Capabilities::can_manage_settings() ) {
			return;
		}

		$settings = get_option( Constants::OPTION_SETTINGS, array() );

		$env         = sanitize_text_field( wp_unslash( $_POST['environment'] ?? Constants::ENV_STAGING ) );
		$client_name = sanitize_text_field( wp_unslash( $_POST['client_name'] ?? '' ) );
		$raw_token   = trim( (string) wp_unslash( $_POST['api_token'] ?? '' ) );

		$settings['environment'] = $env === Constants::ENV_PRODUCTION ? Constants::ENV_PRODUCTION : Constants::ENV_STAGING;
		$settings['client_name'] = $client_name;

		// Only update token if a new one was provided (not masked string or empty).
		if ( ! empty( $raw_token ) && ! str_contains( $raw_token, '****' ) ) {
			$settings['api_token'] = $raw_token;
			$this->client->set_credentials( $settings['environment'], $raw_token, $client_name );
		}

		update_option( Constants::OPTION_SETTINGS, $settings, 'no' );
		set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'success', 'msg' => __( 'API settings saved successfully.', 'wpcalibrate-shipping-connector' ) ), 30 );
	}

	/**
	 * Save Live Shipping rate settings.
	 *
	 * @return void
	 */
	private function save_shipping_settings(): void {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );

		$settings['live_rates_enabled']      = isset( $_POST['live_rates_enabled'] ) ? 'yes' : 'no';
		$settings['shipping_title']          = sanitize_text_field( wp_unslash( $_POST['shipping_title'] ?? 'Delhivery Shipping' ) );
		$settings['shipping_mode']           = in_array( $_POST['shipping_mode'] ?? 'S', array( 'S', 'E' ), true ) ? sanitize_text_field( wp_unslash( $_POST['shipping_mode'] ) ) : 'S';
		$settings['markup_type']             = in_array( $_POST['markup_type'] ?? 'none', array( 'none', 'fixed', 'percentage' ), true ) ? sanitize_text_field( wp_unslash( $_POST['markup_type'] ) ) : 'none';
		$settings['markup_amount']           = max( 0.0, (float) ( $_POST['markup_amount'] ?? 0.0 ) );
		$settings['enable_fallback_rate']    = isset( $_POST['enable_fallback_rate'] ) ? 'yes' : 'no';
		$settings['fallback_rate_amount']    = max( 0.0, (float) ( $_POST['fallback_rate_amount'] ?? 0.0 ) );
		$settings['free_shipping_threshold'] = max( 0.0, (float) ( $_POST['free_shipping_threshold'] ?? 0.0 ) );
		$settings['default_weight_kg']       = max( 0.05, (float) ( $_POST['default_weight_kg'] ?? 0.5 ) );
		$settings['default_length_cm']       = max( 1.0, (float) ( $_POST['default_length_cm'] ?? 10.0 ) );
		$settings['default_width_cm']        = max( 1.0, (float) ( $_POST['default_width_cm'] ?? 10.0 ) );
		$settings['default_height_cm']       = max( 1.0, (float) ( $_POST['default_height_cm'] ?? 10.0 ) );
		$settings['missing_dimensions_rule'] = ( $_POST['missing_dimensions_rule'] ?? 'fallback' ) === 'disable' ? 'disable' : 'fallback';
		$settings['cache_ttl_rates']         = max( 60, (int) ( $_POST['cache_ttl_rates'] ?? 3600 ) );
		$settings['cache_ttl_serviceability']= max( 300, (int) ( $_POST['cache_ttl_serviceability'] ?? 86400 ) );

		// COD Gateways.
		$cod_gateways = isset( $_POST['cod_mapping'] ) && is_array( $_POST['cod_mapping'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['cod_mapping'] ) ) : array();
		$settings['cod_mapping'] = $cod_gateways;

		update_option( Constants::OPTION_SETTINGS, $settings, 'no' );
		set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'success', 'msg' => __( 'Shipping rate settings updated.', 'wpcalibrate-shipping-connector' ) ), 30 );
	}

	/**
	 * Save Shipment manifestation settings.
	 *
	 * @return void
	 */
	private function save_shipment_settings(): void {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );

		$settings['auto_shipment_enabled'] = isset( $_POST['auto_shipment_enabled'] ) ? 'yes' : 'no';
		$settings['auto_shipment_trigger'] = sanitize_text_field( wp_unslash( $_POST['auto_shipment_trigger'] ?? 'processing' ) );
		$settings['default_package_desc']  = sanitize_text_field( wp_unslash( $_POST['default_package_desc'] ?? 'Merchandise' ) );

		update_option( Constants::OPTION_SETTINGS, $settings, 'no' );
		set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'success', 'msg' => __( 'Shipment manifestation options saved.', 'wpcalibrate-shipping-connector' ) ), 30 );
	}

	/**
	 * Save Tracking & Webhook settings.
	 *
	 * @return void
	 */
	private function save_tracking_settings(): void {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );

		$settings['enable_tracking_sync']   = isset( $_POST['enable_tracking_sync'] ) ? 'yes' : 'no';
		$settings['show_customer_tracking'] = isset( $_POST['show_customer_tracking'] ) ? 'yes' : 'no';
		$settings['enable_email_tracking']  = isset( $_POST['enable_email_tracking'] ) ? 'yes' : 'no';

		update_option( Constants::OPTION_SETTINGS, $settings, 'no' );
		set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'success', 'msg' => __( 'Tracking settings saved.', 'wpcalibrate-shipping-connector' ) ), 30 );
	}

	/**
	 * Save warehouse action.
	 *
	 * @return void
	 */
	private function save_warehouse_action(): void {
		$data = array(
			'id'             => sanitize_key( wp_unslash( $_POST['warehouse_id'] ?? '' ) ),
			'name'           => sanitize_text_field( wp_unslash( $_POST['warehouse_name'] ?? '' ) ),
			'address'        => sanitize_textarea_field( wp_unslash( $_POST['warehouse_address'] ?? '' ) ),
			'pin'            => preg_replace( '/\D/', '', (string) wp_unslash( $_POST['warehouse_pin'] ?? '' ) ),
			'city'           => sanitize_text_field( wp_unslash( $_POST['warehouse_city'] ?? '' ) ),
			'state'          => sanitize_text_field( wp_unslash( $_POST['warehouse_state'] ?? '' ) ),
			'phone'          => sanitize_text_field( wp_unslash( $_POST['warehouse_phone'] ?? '' ) ),
			'is_default'     => isset( $_POST['warehouse_is_default'] ),
		);

		$this->warehouse_service->save_warehouse( $data );
		set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'success', 'msg' => __( 'Warehouse saved.', 'wpcalibrate-shipping-connector' ) ), 30 );
	}

	/**
	 * Delete warehouse action.
	 *
	 * @return void
	 */
	private function delete_warehouse_action(): void {
		$id = sanitize_key( wp_unslash( $_POST['warehouse_id'] ?? '' ) );
		$this->warehouse_service->delete_warehouse( $id );
		set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'success', 'msg' => __( 'Warehouse removed.', 'wpcalibrate-shipping-connector' ) ), 30 );
	}

	/**
	 * Test connection diagnostic action.
	 *
	 * @return void
	 */
	private function test_connection_action(): void {
		if ( ! $this->client->is_configured() ) {
			set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'error', 'msg' => __( 'Please enter and save an API token first.', 'wpcalibrate-shipping-connector' ) ), 30 );
			return;
		}

		// Perform safe harmless API check: pin-code check for New Delhi 110001.
		$res = $this->client->get( 'c/api/pin-codes/json/', array( 'filter_codes' => '110001' ) );

		if ( $res->is_success() ) {
			set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'success', 'msg' => __( 'Connection successful! Successfully communicated with Delhivery API.', 'wpcalibrate-shipping-connector' ) ), 30 );
		} else {
			set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'error', 'msg' => sprintf( __( 'Connection failed: %s', 'wpcalibrate-shipping-connector' ), esc_html( $res->get_error_message() ) ) ), 30 );
		}
	}

	/**
	 * Test pincode serviceability diagnostic action.
	 *
	 * @return void
	 */
	private function test_pincode_action(): void {
		$pin = preg_replace( '/\D/', '', (string) wp_unslash( $_POST['test_pin'] ?? '' ) );
		$info = $this->serviceability_service->check_pincode( $pin, true );

		if ( $info['serviceable'] ) {
			$msg = sprintf(
				/* translators: 1: PIN, 2: State, 3: Prepaid, 4: COD */
				__( 'PIN code %1$s is SERVICEABLE (State: %2$s, Prepaid: %3$s, COD: %4$s).', 'wpcalibrate-shipping-connector' ),
				$pin,
				$info['state_code'],
				$info['prepaid'] ? 'Yes' : 'No',
				$info['cod'] ? 'Yes' : 'No'
			);
			set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'success', 'msg' => $msg ), 30 );
		} else {
			set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'error', 'msg' => sprintf( __( 'PIN code %s is NOT serviceable or invalid.', 'wpcalibrate-shipping-connector' ), $pin ) ), 30 );
		}
	}

	/**
	 * Clear rate and serviceability caches.
	 *
	 * @return void
	 */
	private function clear_caches_action(): void {
		$this->serviceability_service->clear_cache();
		set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'success', 'msg' => __( 'Plugin rate and serviceability caches cleared.', 'wpcalibrate-shipping-connector' ) ), 30 );
	}

	/**
	 * Regenerate Webhook Secret.
	 *
	 * @return void
	 */
	private function regenerate_webhook_secret_action(): void {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		$settings['webhook_secret'] = wp_generate_password( 32, false );
		update_option( Constants::OPTION_SETTINGS, $settings, 'no' );
		set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'success', 'msg' => __( 'New webhook secret generated. Please update Delhivery Scan-Push settings.', 'wpcalibrate-shipping-connector' ) ), 30 );
	}

	/**
	 * Save logging level settings.
	 *
	 * @return void
	 */
	private function save_logging_settings(): void {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		$settings['logging_level'] = in_array( $_POST['logging_level'] ?? 'errors', array( 'off', 'errors', 'debug' ), true ) ? sanitize_text_field( wp_unslash( $_POST['logging_level'] ) ) : 'errors';
		$settings['delete_data_on_uninstall'] = isset( $_POST['delete_data_on_uninstall'] ) ? 'yes' : 'no';

		update_option( Constants::OPTION_SETTINGS, $settings, 'no' );
		set_transient( 'wpcalibrate_shipping_notice', array( 'type' => 'success', 'msg' => __( 'Logging & retention options updated.', 'wpcalibrate-shipping-connector' ) ), 30 );
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render(): void {
		$current_tab = sanitize_key( $_GET['tab'] ?? 'overview' );
		$settings    = get_option( Constants::OPTION_SETTINGS, array() );
		$notice      = get_transient( 'wpcalibrate_shipping_notice' );

		if ( $notice ) {
			delete_transient( 'wpcalibrate_shipping_notice' );
		}

		$tabs = array(
			'overview'          => __( 'Overview', 'wpcalibrate-shipping-connector' ),
			'dashboard'         => __( 'Dashboard', 'wpcalibrate-shipping-connector' ),
			'api_connection'    => __( 'API Connection', 'wpcalibrate-shipping-connector' ),
			'shipping'          => __( 'Shipping', 'wpcalibrate-shipping-connector' ),
			'shipments'         => __( 'Shipments', 'wpcalibrate-shipping-connector' ),
			'warehouses'        => __( 'Warehouses', 'wpcalibrate-shipping-connector' ),
			'tracking_webhooks' => __( 'Tracking & Webhooks', 'wpcalibrate-shipping-connector' ),
			'ndr_returns'       => __( 'NDR & Returns', 'wpcalibrate-shipping-connector' ),
			'logs_tools'        => __( 'Logs & Tools', 'wpcalibrate-shipping-connector' ),
			'license'           => __( 'License', 'wpcalibrate-shipping-connector' ),
			'support'           => __( 'Support', 'wpcalibrate-shipping-connector' ),
		);

		include dirname( dirname( __DIR__ ) ) . '/templates/admin/settings-page.php';
	}
}
