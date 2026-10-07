<?php
/**
 * WooCommerce Shipping Zone Method: Delhivery Shipping.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Shipping;

use WC_Shipping_Method;
use WPCalibrate\ShippingConnector\Support\Constants;
use WPCalibrate\ShippingConnector\Services\ServiceabilityService;
use WPCalibrate\ShippingConnector\Services\RateService;
use WPCalibrate\ShippingConnector\Services\WarehouseService;

/**
 * Class DelhiveryShippingMethod
 */
class DelhiveryShippingMethod extends WC_Shipping_Method {

	/**
	 * Serviceability service.
	 *
	 * @var ServiceabilityService|null
	 */
	protected ?ServiceabilityService $serviceability_service = null;

	/**
	 * Rate service.
	 *
	 * @var RateService|null
	 */
	protected ?RateService $rate_service = null;

	/**
	 * Warehouse service.
	 *
	 * @var WarehouseService|null
	 */
	protected ?WarehouseService $warehouse_service = null;

	/**
	 * Package calculator.
	 *
	 * @var PackageCalculator|null
	 */
	protected ?PackageCalculator $package_calculator = null;

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping zone instance ID.
	 */
	public function __construct( int $instance_id = 0 ) {
		$this->id                 = 'wpcalibrate_delhivery';
		$this->instance_id        = $instance_id;
		$this->method_title       = __( 'Delhivery Shipping', 'wpcalibrate-shipping-connector' );
		$this->method_description = __( 'Live shipping rates and serviceability powered by Delhivery B2C logistics.', 'wpcalibrate-shipping-connector' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
		);

		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->get_option( 'title', __( 'Delhivery Shipping', 'wpcalibrate-shipping-connector' ) );
		$this->enabled = $this->get_option( 'enabled', 'yes' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Inject required service dependencies.
	 *
	 * @param ServiceabilityService $serviceability_service
	 * @param RateService           $rate_service
	 * @param WarehouseService      $warehouse_service
	 * @param PackageCalculator     $package_calculator
	 * @return void
	 */
	public function set_services(
		ServiceabilityService $serviceability_service,
		RateService $rate_service,
		WarehouseService $warehouse_service,
		PackageCalculator $package_calculator
	): void {
		$this->serviceability_service = $serviceability_service;
		$this->rate_service           = $rate_service;
		$this->warehouse_service      = $warehouse_service;
		$this->package_calculator     = $package_calculator;
	}

	/**
	 * Initialize instance settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields(): void {
		$this->instance_form_fields = array(
			'title' => array(
				'title'       => __( 'Method Title', 'wpcalibrate-shipping-connector' ),
				'type'        => 'text',
				'description' => __( 'Title displayed to customers during checkout.', 'wpcalibrate-shipping-connector' ),
				'default'     => __( 'Delhivery Shipping', 'wpcalibrate-shipping-connector' ),
				'desc_tip'    => true,
			),
			'shipping_mode' => array(
				'title'       => __( 'Shipping Mode', 'wpcalibrate-shipping-connector' ),
				'type'        => 'select',
				'description' => __( 'Select Delhivery transport mode for this zone.', 'wpcalibrate-shipping-connector' ),
				'default'     => 'S',
				'options'     => array(
					'S' => __( 'Surface', 'wpcalibrate-shipping-connector' ),
					'E' => __( 'Express', 'wpcalibrate-shipping-connector' ),
				),
			),
		);
	}

	/**
	 * Calculate shipping rates for a cart package.
	 *
	 * @param array<string, mixed> $package
	 * @return void
	 */
	public function calculate_shipping( $package = array() ): void {
		$destination = $package['destination'] ?? array();
		$country     = $destination['country'] ?? '';
		$postcode    = $destination['postcode'] ?? '';

		// Restrict initial scope to India domestic shipping.
		if ( 'IN' !== $country || empty( $postcode ) ) {
			return;
		}

		// Ensure services are available.
		if ( ! $this->serviceability_service || ! $this->rate_service || ! $this->warehouse_service || ! $this->package_calculator ) {
			return;
		}

		// Check serviceability of destination PIN code.
		$serviceability = $this->serviceability_service->check_pincode( (string) $postcode );
		if ( empty( $serviceability['serviceable'] ) ) {
			return;
		}

		// Determine default warehouse for origin PIN code.
		$warehouse = $this->warehouse_service->get_default_warehouse();
		if ( empty( $warehouse ) || empty( $warehouse['pin'] ) ) {
			return;
		}

		$origin_pin = (string) $warehouse['pin'];

		// Calculate package weight and dimensions.
		$contents   = $package['contents'] ?? array();
		$calc_info  = $this->package_calculator->calculate_from_cart( $contents );

		if ( ! $calc_info['is_valid'] ) {
			return;
		}

		// Determine payment mode (Prepaid or COD).
		$chosen_gateway = WC()->session ? WC()->session->get( 'chosen_payment_method' ) : '';
		$settings       = get_option( Constants::OPTION_SETTINGS, array() );
		$cod_gateways   = (array) ( $settings['cod_mapping'] ?? array( 'cod' ) );
		$payment_mode   = in_array( $chosen_gateway, $cod_gateways, true ) ? 'COD' : 'Pre-paid';

		if ( 'COD' === $payment_mode && empty( $serviceability['cod'] ) ) {
			// If COD is requested but PIN does not support COD, do not offer COD rate.
			return;
		}

		$mode          = (string) $this->get_option( 'shipping_mode', 'S' );
		$cart_subtotal = (float) ( $package['contents_cost'] ?? 0.0 );

		$rate_result = $this->rate_service->calculate_rate(
			$origin_pin,
			(string) $postcode,
			(int) $calc_info['weight_grams'],
			$mode,
			$payment_mode,
			$cart_subtotal
		);

		if ( ! $rate_result['success'] ) {
			return;
		}

		$rate_args = array(
			'id'        => $this->get_rate_id(),
			'label'     => $this->title,
			'cost'      => $rate_result['cost'],
			'package'   => $package,
			'meta_data' => array(
				'delhivery_mode'        => $mode,
				'delhivery_chargeable_g'=> $calc_info['weight_grams'],
				'delhivery_dimensions'  => $calc_info['dimensions_str'],
				'delhivery_warehouse'   => $warehouse['name'] ?? '',
			),
		);

		$this->add_rate( $rate_args );
	}
}
