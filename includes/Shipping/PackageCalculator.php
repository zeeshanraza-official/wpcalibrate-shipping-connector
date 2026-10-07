<?php
/**
 * Package weight and dimension calculation component.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Shipping;

use WC_Order;

/**
 * Class PackageCalculator
 */
class PackageCalculator {

	/**
	 * Default fallback weight in kg.
	 *
	 * @var float
	 */
	private float $fallback_weight_kg;

	/**
	 * Default dimensions in cm.
	 *
	 * @var array{length: float, width: float, height: float}
	 */
	private array $fallback_dimensions_cm;

	/**
	 * Rule when dimensions missing ('fallback' or 'disable').
	 *
	 * @var string
	 */
	private string $missing_rule;

	/**
	 * Constructor.
	 *
	 * @param float                                             $fallback_weight_kg
	 * @param array{length: float, width: float, height: float} $fallback_dimensions_cm
	 * @param string                                            $missing_rule
	 */
	public function __construct(
		float $fallback_weight_kg = 0.5,
		array $fallback_dimensions_cm = array( 'length' => 10.0, 'width' => 10.0, 'height' => 10.0 ),
		string $missing_rule = 'fallback'
	) {
		$this->fallback_weight_kg     = max( 0.05, $fallback_weight_kg );
		$this->fallback_dimensions_cm = array(
			'length' => max( 1.0, (float) ( $fallback_dimensions_cm['length'] ?? 10.0 ) ),
			'width'  => max( 1.0, (float) ( $fallback_dimensions_cm['width'] ?? 10.0 ) ),
			'height' => max( 1.0, (float) ( $fallback_dimensions_cm['height'] ?? 10.0 ) ),
		);
		$this->missing_rule           = $missing_rule;
	}

	/**
	 * Calculate total package weight in grams and dimensions in cm from cart package contents.
	 *
	 * @param array<int, array<string, mixed>> $cart_contents
	 * @return array{
	 *     weight_grams: int,
	 *     weight_kg: float,
	 *     length_cm: float,
	 *     width_cm: float,
	 *     height_cm: float,
	 *     dimensions_str: string,
	 *     item_count: int,
	 *     is_valid: bool,
	 *     descriptions: string
	 * }
	 */
	public function calculate_from_cart( array $cart_contents ): array {
		$total_weight_kg = 0.0;
		$item_count      = 0;
		$descriptions    = array();
		$max_length      = 0.0;
		$max_width       = 0.0;
		$total_height    = 0.0;
		$missing_dim     = false;

		$wc_weight_unit = get_option( 'woocommerce_weight_unit', 'kg' );
		$wc_dim_unit    = get_option( 'woocommerce_dimension_unit', 'cm' );

		foreach ( $cart_contents as $item ) {
			/** @var \WC_Product|null $product */
			$product  = $item['data'] ?? null;
			$quantity = (int) ( $item['quantity'] ?? 1 );

			if ( ! $product || ! $product->needs_shipping() ) {
				continue;
			}

			$item_count += $quantity;
			$descriptions[] = $product->get_name() . ( $quantity > 1 ? " (x{$quantity})" : '' );

			// Handle Weight.
			$product_weight = (float) $product->get_weight();
			if ( $product_weight > 0 ) {
				$weight_in_kg = wc_get_weight( $product_weight, 'kg', $wc_weight_unit );
			} else {
				$weight_in_kg = $this->fallback_weight_kg;
			}
			$total_weight_kg += ( $weight_in_kg * $quantity );

			// Handle Dimensions.
			$l = (float) $product->get_length();
			$w = (float) $product->get_width();
			$h = (float) $product->get_height();

			if ( $l > 0 && $w > 0 && $h > 0 ) {
				$l_cm = wc_get_dimension( $l, 'cm', $wc_dim_unit );
				$w_cm = wc_get_dimension( $w, 'cm', $wc_dim_unit );
				$h_cm = wc_get_dimension( $h, 'cm', $wc_dim_unit );

				$max_length   = max( $max_length, $l_cm );
				$max_width    = max( $max_width, $w_cm );
				$total_height += ( $h_cm * $quantity );
			} else {
				$missing_dim = true;
			}
		}

		if ( $missing_dim || 0.0 === $max_length ) {
			if ( 'disable' === $this->missing_rule ) {
				return array(
					'weight_grams'   => 0,
					'weight_kg'      => 0.0,
					'length_cm'      => 0.0,
					'width_cm'       => 0.0,
					'height_cm'      => 0.0,
					'dimensions_str' => '',
					'item_count'     => $item_count,
					'is_valid'       => false,
					'descriptions'   => implode( ', ', $descriptions ),
				);
			}

			$max_length   = $this->fallback_dimensions_cm['length'];
			$max_width    = $this->fallback_dimensions_cm['width'];
			$total_height = $this->fallback_dimensions_cm['height'];
		}

		$weight_grams = (int) ceil( $total_weight_kg * 1000 );
		if ( $weight_grams < 50 ) {
			$weight_grams    = (int) ceil( $this->fallback_weight_kg * 1000 );
			$total_weight_kg = $this->fallback_weight_kg;
		}

		$length_rounded = round( max( 1.0, $max_length ), 1 );
		$width_rounded  = round( max( 1.0, $max_width ), 1 );
		$height_rounded = round( max( 1.0, $total_height ), 1 );

		return array(
			'weight_grams'   => $weight_grams,
			'weight_kg'      => round( $total_weight_kg, 3 ),
			'length_cm'      => $length_rounded,
			'width_cm'       => $width_rounded,
			'height_cm'      => $height_rounded,
			'dimensions_str' => "{$length_rounded}x{$width_rounded}x{$height_rounded}",
			'item_count'     => $item_count,
			'is_valid'       => ( $item_count > 0 && $weight_grams > 0 ),
			'descriptions'   => implode( ', ', array_slice( $descriptions, 0, 5 ) ),
		);
	}

	/**
	 * Calculate package parameters from a WooCommerce Order.
	 *
	 * @param WC_Order $order
	 * @return array{
	 *     weight_grams: int,
	 *     weight_kg: float,
	 *     length_cm: float,
	 *     width_cm: float,
	 *     height_cm: float,
	 *     dimensions_str: string,
	 *     item_count: int,
	 *     is_valid: bool,
	 *     descriptions: string
	 * }
	 */
	public function calculate_from_order( WC_Order $order ): array {
		$items_data = array();

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product && $product->needs_shipping() ) {
				$items_data[] = array(
					'data'     => $product,
					'quantity' => $item->get_quantity(),
				);
			}
		}

		return $this->calculate_from_cart( $items_data );
	}
}
