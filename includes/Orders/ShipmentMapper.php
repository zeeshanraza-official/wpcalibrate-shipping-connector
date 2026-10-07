<?php
/**
 * Shipment Data Mapper for Delhivery Manifestation Payload.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Orders;

use WC_Order;
use WPCalibrate\ShippingConnector\Shipping\PackageCalculator;
use WPCalibrate\ShippingConnector\Support\Helpers;

/**
 * Class ShipmentMapper
 */
class ShipmentMapper {

	/**
	 * Package calculator.
	 *
	 * @var PackageCalculator
	 */
	private PackageCalculator $package_calculator;

	/**
	 * Constructor.
	 *
	 * @param PackageCalculator $package_calculator
	 */
	public function __construct( PackageCalculator $package_calculator ) {
		$this->package_calculator = $package_calculator;
	}

	/**
	 * Map WooCommerce order and warehouse into Delhivery CMU payload structure.
	 *
	 * @param WC_Order             $order
	 * @param array<string, mixed> $warehouse
	 * @param string               $payment_mode 'Prepaid' or 'COD'
	 * @param string               $custom_waybill Optional pre-allocated waybill
	 * @return array{
	 *     pickup_location: array<string, mixed>,
	 *     shipments: list<array<string, mixed>>
	 * }
	 */
	public function map_forward_shipment(
		WC_Order $order,
		array $warehouse,
		string $payment_mode = 'Prepaid',
		string $custom_waybill = ''
	): array {
		$calc = $this->package_calculator->calculate_from_order( $order );

		$address_1 = $order->get_shipping_address_1() ?: $order->get_billing_address_1();
		$address_2 = $order->get_shipping_address_2() ?: $order->get_billing_address_2();
		$full_addr = trim( "{$address_1} {$address_2}" );

		$consignee_name = trim( ( $order->get_shipping_first_name() ?: $order->get_billing_first_name() ) . ' ' . ( $order->get_shipping_last_name() ?: $order->get_billing_last_name() ) );
		if ( empty( $consignee_name ) ) {
			$consignee_name = 'Customer ' . $order->get_id();
		}

		$pin   = preg_replace( '/\D/', '', (string) ( $order->get_shipping_postcode() ?: $order->get_billing_postcode() ) );
		$city  = $order->get_shipping_city() ?: $order->get_billing_city();
		$state = $order->get_shipping_state() ?: $order->get_billing_state();
		$phone = Helpers::clean_phone( (string) $order->get_billing_phone() );

		$order_total = (float) $order->get_total();
		$cod_amount  = 'COD' === $payment_mode ? $order_total : 0.0;

		$shipment_item = array(
			'order'         => (string) $order->get_order_number(),
			'name'          => $consignee_name,
			'add'           => $full_addr,
			'pin'           => $pin,
			'city'          => $city,
			'state'         => $state,
			'country'       => 'India',
			'phone'         => $phone,
			'payment_mode'  => $payment_mode,
			'total_amount'  => $order_total,
			'cod_amount'    => $cod_amount,
			'products_desc' => substr( $calc['descriptions'] ?: 'Order Items', 0, 250 ),
			'order_date'    => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : current_time( 'mysql' ),
			'weight'        => $calc['weight_grams'],
			'dimensions'    => $calc['dimensions_str'],
			'quantity'      => (string) max( 1, $calc['item_count'] ),
		);

		if ( ! empty( $custom_waybill ) ) {
			$shipment_item['waybill'] = $custom_waybill;
		}

		$pickup_location = array(
			'name'    => (string) ( $warehouse['name'] ?? '' ),
			'add'     => (string) ( $warehouse['address'] ?? '' ),
			'city'    => (string) ( $warehouse['city'] ?? '' ),
			'pin'     => (string) ( $warehouse['pin'] ?? '' ),
			'country' => 'India',
			'phone'   => (string) ( $warehouse['phone'] ?? '' ),
		);

		$payload = array(
			'pickup_location' => $pickup_location,
			'shipments'       => array( $shipment_item ),
		);

		return apply_filters( 'wpcalibrate_shipping_forward_payload', $payload, $order, $warehouse );
	}

	/**
	 * Map reverse pickup shipment payload (RVP).
	 *
	 * @param WC_Order             $order
	 * @param array<string, mixed> $warehouse Return warehouse destination.
	 * @param array<string, mixed> $qc_data   Optional QC checklist.
	 * @return array{
	 *     pickup_location: array<string, mixed>,
	 *     shipments: list<array<string, mixed>>
	 * }
	 */
	public function map_reverse_shipment( WC_Order $order, array $warehouse, array $qc_data = array() ): array {
		$calc = $this->package_calculator->calculate_from_order( $order );

		$address_1 = $order->get_shipping_address_1() ?: $order->get_billing_address_1();
		$address_2 = $order->get_shipping_address_2() ?: $order->get_billing_address_2();
		$full_addr = trim( "{$address_1} {$address_2}" );

		$consignee_name = trim( ( $order->get_shipping_first_name() ?: $order->get_billing_first_name() ) . ' ' . ( $order->get_shipping_last_name() ?: $order->get_billing_last_name() ) );
		$pin            = preg_replace( '/\D/', '', (string) ( $order->get_shipping_postcode() ?: $order->get_billing_postcode() ) );
		$phone          = Helpers::clean_phone( (string) $order->get_billing_phone() );

		$shipment_item = array(
			'order'          => 'RET-' . $order->get_order_number(),
			'name'           => $consignee_name,
			'add'            => $full_addr,
			'pin'            => $pin,
			'city'           => $order->get_shipping_city() ?: $order->get_billing_city(),
			'state'          => $order->get_shipping_state() ?: $order->get_billing_state(),
			'country'        => 'India',
			'phone'          => $phone,
			'payment_mode'   => 'Pickup', // Mandatory for reverse
			'total_amount'   => 0.0,
			'cod_amount'     => 0.0,
			'products_desc'  => 'Return: ' . substr( $calc['descriptions'] ?: 'Merchandise', 0, 200 ),
			'order_date'     => current_time( 'mysql' ),
			'weight'         => $calc['weight_grams'],
			'dimensions'     => $calc['dimensions_str'],
			'quantity'       => (string) max( 1, $calc['item_count'] ),
			'return_add'     => (string) ( $warehouse['return_address'] ?? $warehouse['address'] ?? '' ),
			'return_pin'     => (string) ( $warehouse['return_pin'] ?? $warehouse['pin'] ?? '' ),
			'return_city'    => (string) ( $warehouse['return_city'] ?? $warehouse['city'] ?? '' ),
			'return_state'   => (string) ( $warehouse['return_state'] ?? $warehouse['state'] ?? '' ),
			'return_country' => 'India',
		);

		if ( ! empty( $qc_data ) ) {
			$shipment_item['qc'] = $qc_data;
		}

		$pickup_location = array(
			'name'    => (string) ( $warehouse['name'] ?? '' ),
			'add'     => (string) ( $warehouse['address'] ?? '' ),
			'city'    => (string) ( $warehouse['city'] ?? '' ),
			'pin'     => (string) ( $warehouse['pin'] ?? '' ),
			'country' => 'India',
			'phone'   => (string) ( $warehouse['phone'] ?? '' ),
		);

		$payload = array(
			'pickup_location' => $pickup_location,
			'shipments'       => array( $shipment_item ),
		);

		return apply_filters( 'wpcalibrate_shipping_reverse_payload', $payload, $order, $warehouse );
	}
}
