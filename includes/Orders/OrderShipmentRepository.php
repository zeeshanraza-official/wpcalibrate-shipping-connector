<?php
/**
 * HPOS-compatible Order Shipment Repository.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Orders;

use WC_Order;
use WPCalibrate\ShippingConnector\Support\Constants;

/**
 * Class OrderShipmentRepository
 */
class OrderShipmentRepository {

	/**
	 * Get order by Delhivery Waybill / AWB.
	 *
	 * @param string $waybill
	 * @return WC_Order|null
	 */
	public function get_order_by_waybill( string $waybill ): ?WC_Order {
		$clean_waybill = preg_replace( '/[^A-Za-z0-9_-]/', '', $waybill );
		if ( empty( $clean_waybill ) ) {
			return null;
		}

		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'meta_key'   => Constants::META_WAYBILL,
				'meta_value' => $clean_waybill,
			)
		);

		if ( ! empty( $orders ) ) {
			return reset( $orders );
		}

		// Also check return waybill.
		$return_orders = wc_get_orders(
			array(
				'limit'      => 1,
				'meta_key'   => Constants::META_RETURN_WAYBILL,
				'meta_value' => $clean_waybill,
			)
		);

		return ! empty( $return_orders ) ? reset( $return_orders ) : null;
	}

	/**
	 * Get waybill for an order.
	 *
	 * @param WC_Order $order
	 * @return string
	 */
	public function get_waybill( WC_Order $order ): string {
		return (string) $order->get_meta( Constants::META_WAYBILL, true );
	}

	/**
	 * Check if order is already manifested.
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
	public function is_manifested( WC_Order $order ): bool {
		$waybill   = $this->get_waybill( $order );
		$cancelled = $this->is_cancelled( $order );
		return ! empty( $waybill ) && ! $cancelled;
	}

	/**
	 * Check if order shipment is cancelled.
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
	public function is_cancelled( WC_Order $order ): bool {
		return 'yes' === (string) $order->get_meta( Constants::META_CANCELLATION_STATUS, true );
	}

	/**
	 * Save shipment manifestation details.
	 *
	 * @param WC_Order             $order
	 * @param string               $waybill
	 * @param string               $warehouse_id
	 * @param string               $payment_mode
	 * @param float                $cod_amount
	 * @param array<string, mixed> $extra
	 * @return void
	 */
	public function save_manifest(
		WC_Order $order,
		string $waybill,
		string $warehouse_id,
		string $payment_mode,
		float $cod_amount = 0.0,
		array $extra = array()
	): void {
		$order->update_meta_data( Constants::META_WAYBILL, sanitize_text_field( $waybill ) );
		$order->update_meta_data( Constants::META_SHIPMENT_STATUS, 'Manifested' );
		$order->update_meta_data( Constants::META_STATUS_CODE, 'UD' );
		$order->update_meta_data( Constants::META_MANIFESTED_AT, current_time( 'mysql' ) );
		$order->update_meta_data( Constants::META_WAREHOUSE_ID, sanitize_text_field( $warehouse_id ) );
		$order->update_meta_data( Constants::META_PAYMENT_MODE, sanitize_text_field( $payment_mode ) );
		$order->update_meta_data( Constants::META_COD_AMOUNT, $cod_amount );
		$order->update_meta_data( Constants::META_CANCELLATION_STATUS, 'no' );

		if ( ! empty( $extra['label_url'] ) ) {
			$order->update_meta_data( Constants::META_LABEL_DOWNLOAD_URL, esc_url_raw( (string) $extra['label_url'] ) );
		}

		$order->save();
	}

	/**
	 * Update shipment status and scans.
	 *
	 * @param WC_Order             $order
	 * @param string               $status
	 * @param string               $status_code
	 * @param array<string, mixed> $scan_details
	 * @return void
	 */
	public function update_tracking_status(
		WC_Order $order,
		string $status,
		string $status_code,
		array $scan_details = array()
	): void {
		$order->update_meta_data( Constants::META_SHIPMENT_STATUS, sanitize_text_field( $status ) );
		$order->update_meta_data( Constants::META_STATUS_CODE, sanitize_text_field( $status_code ) );
		$order->update_meta_data( Constants::META_LAST_TRACKING_SYNC, current_time( 'mysql' ) );

		if ( ! empty( $scan_details ) ) {
			$raw_history = $order->get_meta( Constants::META_TRACKING_HISTORY, true );
			$history     = is_array( $raw_history ) ? $raw_history : array();
			$history[]   = $scan_details;
			// Keep max 50 recent scans.
			if ( count( $history ) > 50 ) {
				$history = array_slice( $history, -50 );
			}
			$order->update_meta_data( Constants::META_TRACKING_HISTORY, $history );
		}

		$order->save();
	}

	/**
	 * Mark shipment as cancelled.
	 *
	 * @param WC_Order $order
	 * @param string   $reason
	 * @return void
	 */
	public function mark_cancelled( WC_Order $order, string $reason = '' ): void {
		$order->update_meta_data( Constants::META_CANCELLATION_STATUS, 'yes' );
		$order->update_meta_data( Constants::META_CANCELLED_AT, current_time( 'mysql' ) );
		$order->update_meta_data( Constants::META_SHIPMENT_STATUS, 'Cancelled' );
		$order->save();
	}

	/**
	 * Acquire an order lock for manifestation to prevent race conditions.
	 *
	 * @param WC_Order $order
	 * @param int      $timeout_seconds
	 * @return bool True if acquired, false if already locked.
	 */
	public function acquire_lock( WC_Order $order, int $timeout_seconds = 60 ): bool {
		$now    = time();
		$locked = (int) $order->get_meta( Constants::META_LOCK, true );

		if ( $locked > 0 && ( $locked + $timeout_seconds ) > $now ) {
			return false; // Still locked.
		}

		$order->update_meta_data( Constants::META_LOCK, $now );
		$order->save();
		return true;
	}

	/**
	 * Release order lock.
	 *
	 * @param WC_Order $order
	 * @return void
	 */
	public function release_lock( WC_Order $order ): void {
		$order->delete_meta_data( Constants::META_LOCK );
		$order->save();
	}
}
