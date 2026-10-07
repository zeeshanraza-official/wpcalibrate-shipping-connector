<?php
/**
 * Main Plugin Orchestrator.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Core;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WPCalibrate\ShippingConnector\Support\Constants;
use WPCalibrate\ShippingConnector\Logging\Logger;
use WPCalibrate\ShippingConnector\Api\DelhiveryClient;
use WPCalibrate\ShippingConnector\Services\ServiceabilityService;
use WPCalibrate\ShippingConnector\Services\RateService;
use WPCalibrate\ShippingConnector\Services\WarehouseService;
use WPCalibrate\ShippingConnector\Services\ShipmentService;
use WPCalibrate\ShippingConnector\Services\TrackingService;
use WPCalibrate\ShippingConnector\Services\LabelService;
use WPCalibrate\ShippingConnector\Services\PickupService;
use WPCalibrate\ShippingConnector\Services\NdrService;
use WPCalibrate\ShippingConnector\Services\ReturnService;
use WPCalibrate\ShippingConnector\Services\WebhookService;
use WPCalibrate\ShippingConnector\Shipping\PackageCalculator;
use WPCalibrate\ShippingConnector\Shipping\DelhiveryShippingMethod;
use WPCalibrate\ShippingConnector\Shipping\CustomerTracking;
use WPCalibrate\ShippingConnector\Orders\OrderShipmentRepository;
use WPCalibrate\ShippingConnector\Orders\ShipmentMapper;
use WPCalibrate\ShippingConnector\Orders\StatusMapper;
use WPCalibrate\ShippingConnector\Admin\Settings;
use WPCalibrate\ShippingConnector\Admin\Menu;
use WPCalibrate\ShippingConnector\Admin\Notices;
use WPCalibrate\ShippingConnector\Admin\OrderPanel;
use WPCalibrate\ShippingConnector\Admin\BulkActions;
use WPCalibrate\ShippingConnector\Webhooks\WebhookController;
use WPCalibrate\ShippingConnector\Background\ActionSchedulerManager;
use WPCalibrate\ShippingConnector\License\LicenseManagerInterface;
use WPCalibrate\ShippingConnector\License\NullLicenseManager;

/**
 * Class Plugin
 */
final class Plugin {

	/**
	 * Single instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Services and managers.
	 */
	private Logger $logger;
	private DelhiveryClient $client;
	private ServiceabilityService $serviceability_service;
	private RateService $rate_service;
	private WarehouseService $warehouse_service;
	private PackageCalculator $package_calculator;
	private OrderShipmentRepository $order_repository;
	private ShipmentMapper $shipment_mapper;
	private StatusMapper $status_mapper;
	private ShipmentService $shipment_service;
	private TrackingService $tracking_service;
	private LabelService $label_service;
	private PickupService $pickup_service;
	private NdrService $ndr_service;
	private ReturnService $return_service;
	private WebhookService $webhook_service;
	private WebhookController $webhook_controller;
	private ActionSchedulerManager $action_scheduler;
	private LicenseManagerInterface $license_manager;
	private Settings $settings;
	private Menu $menu;
	private Notices $notices;
	private OrderPanel $order_panel;
	private BulkActions $bulk_actions;
	private CustomerTracking $customer_tracking;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->bootstrap();
	}

	/**
	 * Bootstrap plugin components and dependency injection.
	 *
	 * @return void
	 */
	private function bootstrap(): void {
		$options = get_option( Constants::OPTION_SETTINGS, array() );

		// 1. Logger.
		$log_level    = (string) ( $options['logging_level'] ?? Logger::LEVEL_ERRORS );
		$this->logger = new Logger( $log_level );

		// 2. HTTP Client.
		$env         = (string) ( $options['environment'] ?? Constants::ENV_STAGING );
		$token       = (string) ( $options['api_token'] ?? '' );
		$client_name = (string) ( $options['client_name'] ?? '' );
		$this->client = new DelhiveryClient( $env, $token, $client_name, $this->logger );

		// 3. Foundation Services.
		$srv_ttl                      = (int) ( $options['cache_ttl_serviceability'] ?? 86400 );
		$this->serviceability_service = new ServiceabilityService( $this->client, $this->logger, $srv_ttl );
		$this->rate_service           = new RateService( $this->client, $this->logger, $options );
		$this->warehouse_service      = new WarehouseService( $this->client, $this->logger );

		// 4. Calculations & Repositories.
		$fallback_wt = (float) ( $options['default_weight_kg'] ?? 0.5 );
		$fallback_dim = array(
			'length' => (float) ( $options['default_length_cm'] ?? 10.0 ),
			'width'  => (float) ( $options['default_width_cm'] ?? 10.0 ),
			'height' => (float) ( $options['default_height_cm'] ?? 10.0 ),
		);
		$missing_rule             = (string) ( $options['missing_dimensions_rule'] ?? 'fallback' );
		$this->package_calculator = new PackageCalculator( $fallback_wt, $fallback_dim, $missing_rule );
		$this->order_repository   = new OrderShipmentRepository();
		$this->shipment_mapper     = new ShipmentMapper( $this->package_calculator );

		// 5. Status & Operations.
		$mappings            = (array) ( $options['status_mappings'] ?? array() );
		$this->status_mapper = new StatusMapper( $mappings );

		$this->shipment_service = new ShipmentService(
			$this->client,
			$this->order_repository,
			$this->shipment_mapper,
			$this->warehouse_service,
			$this->logger
		);

		$this->tracking_service = new TrackingService(
			$this->client,
			$this->order_repository,
			$this->status_mapper,
			$this->logger
		);

		$this->label_service  = new LabelService( $this->client, $this->order_repository, $this->logger );
		$this->pickup_service = new PickupService( $this->client, $this->warehouse_service, $this->logger );
		$this->ndr_service    = new NdrService( $this->client, $this->order_repository, $this->logger );
		$this->return_service = new ReturnService(
			$this->client,
			$this->order_repository,
			$this->shipment_mapper,
			$this->warehouse_service,
			$this->logger
		);

		// 6. Webhooks & Background processing.
		$this->webhook_service    = new WebhookService( $this->order_repository, $this->tracking_service, $this->logger );
		$this->webhook_controller = new WebhookController( $this->webhook_service, $this->logger );
		$this->action_scheduler   = new ActionSchedulerManager(
			$this->shipment_service,
			$this->tracking_service,
			$this->webhook_service,
			$this->logger
		);

		// 7. Licensing.
		$this->license_manager = new NullLicenseManager();

		// 8. Admin Controllers.
		$this->settings   = new Settings(
			$this->client,
			$this->serviceability_service,
			$this->warehouse_service,
			$this->license_manager,
			$this->logger
		);
		$this->menu       = new Menu( $this->settings );
		$this->notices    = new Notices();
		$this->order_panel = new OrderPanel(
			$this->order_repository,
			$this->shipment_service,
			$this->tracking_service,
			$this->label_service,
			$this->warehouse_service,
			$this->ndr_service,
			$this->return_service
		);
		$this->bulk_actions      = new BulkActions( $this->shipment_service, $this->tracking_service );
		$this->customer_tracking = new CustomerTracking( $this->order_repository );

		$this->init_hooks();
	}

	/**
	 * Register WordPress and WooCommerce hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Declare HPOS and Blocks compatibility before WooCommerce initializes features.
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );

		// Run migrations on admin_init.
		add_action( 'admin_init', array( Migrations::class, 'run' ) );

		// Register Shipping method.
		add_action( 'woocommerce_shipping_init', array( $this, 'shipping_method_init' ) );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'register_shipping_method' ) );

		// Initialize controllers.
		$this->menu->init();
		$this->settings->init();
		$this->notices->init();
		$this->order_panel->init();
		$this->bulk_actions->init();
		$this->customer_tracking->init();
		$this->action_scheduler->init();

		// Register REST routes.
		add_action( 'rest_api_init', array( $this->webhook_controller, 'register_routes' ) );

		// Admin Assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Declare HPOS and Cart/Checkout Blocks compatibility.
	 *
	 * @return void
	 */
	public function declare_compatibility(): void {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', dirname( dirname( __DIR__ ) ) . '/wpcalibrate-shipping-connector.php', true );
			FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', dirname( dirname( __DIR__ ) ) . '/wpcalibrate-shipping-connector.php', true );
		}
	}

	/**
	 * Initialize shipping method class.
	 *
	 * @return void
	 */
	public function shipping_method_init(): void {
		require_once dirname( __DIR__ ) . '/Shipping/DelhiveryShippingMethod.php';
	}

	/**
	 * Register shipping method in WooCommerce.
	 *
	 * @param array<string, string> $methods
	 * @return array<string, string>
	 */
	public function register_shipping_method( array $methods ): array {
		$methods['wpcalibrate_delhivery'] = DelhiveryShippingMethod::class;
		return $methods;
	}

	/**
	 * Enqueue admin scripts and styles selectively.
	 *
	 * @param string $hook
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Only enqueue on this plugin's settings page and order edit screens.
		$is_settings = str_contains( $hook, Constants::SUBMENU_SLUG );
		$is_order    = str_contains( $hook, 'shop_order' ) || str_contains( $hook, 'wc-orders' );

		if ( ! $is_settings && ! $is_order ) {
			return;
		}

		$base_url = plugin_dir_url( dirname( __DIR__ ) );

		wp_enqueue_style(
			'wpcalibrate-shipping-admin',
			$base_url . 'assets/css/admin.css',
			array(),
			Constants::PLUGIN_VERSION
		);

		wp_enqueue_script(
			'wpcalibrate-shipping-admin',
			$base_url . 'assets/js/admin.js',
			array( 'jquery' ),
			Constants::PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'wpcalibrate-shipping-admin',
			'wpcalibrateShippingAdmin',
			array(
				'confirmCancel' => __( 'Are you sure you want to cancel this Delhivery shipment? This action cannot be undone.', 'wpcalibrate-shipping-connector' ),
			)
		);
	}
}
