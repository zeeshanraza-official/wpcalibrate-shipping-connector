<?php
/**
 * Order Metabox Panel Template.
 *
 * @package WPCalibrate\ShippingConnector
 *
 * @var WC_Order $order
 * @var int      $order_id
 * @var string   $waybill
 * @var bool     $is_manifested
 * @var bool     $is_cancelled
 * @var string   $status_text
 * @var array<string, array<string, mixed>> $warehouses
 * @var string   $selected_wh
 * @var array<string, mixed>|null $notice
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pay_mode       = (string) $order->get_meta( \WPCalibrate\ShippingConnector\Support\Constants::META_PAYMENT_MODE, true ) ?: 'Prepaid';
$cod_amt        = (float) $order->get_meta( \WPCalibrate\ShippingConnector\Support\Constants::META_COD_AMOUNT, true );
$return_waybill = (string) $order->get_meta( \WPCalibrate\ShippingConnector\Support\Constants::META_RETURN_WAYBILL, true );
$last_sync      = (string) $order->get_meta( \WPCalibrate\ShippingConnector\Support\Constants::META_LAST_TRACKING_SYNC, true );
?>

<div class="wpcalibrate-order-panel">
	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> inline" style="margin-bottom: 12px; padding: 8px;">
			<p style="margin: 0;"><?php echo esc_html( $notice['msg'] ); ?></p>
		</div>
	<?php endif; ?>

	<div class="panel-section status-section">
		<p>
			<strong><?php esc_html_e( 'Status:', 'wpcalibrate-shipping-connector' ); ?></strong>
			<?php if ( $is_cancelled ) : ?>
				<span class="badge badge-danger"><?php esc_html_e( 'Cancelled', 'wpcalibrate-shipping-connector' ); ?></span>
			<?php elseif ( $is_manifested ) : ?>
				<span class="badge badge-success"><?php echo esc_html( $status_text ); ?></span>
			<?php else : ?>
				<span class="badge badge-secondary"><?php esc_html_e( 'Ready to Manifest', 'wpcalibrate-shipping-connector' ); ?></span>
			<?php endif; ?>
		</p>

		<?php if ( ! empty( $waybill ) ) : ?>
			<p>
				<strong><?php esc_html_e( 'AWB / Waybill:', 'wpcalibrate-shipping-connector' ); ?></strong><br>
				<code style="font-size: 13px; font-weight: bold;"><?php echo esc_html( $waybill ); ?></code>
				<a href="<?php echo esc_url( \WPCalibrate\ShippingConnector\Support\Helpers::get_tracking_url( $waybill ) ); ?>" target="_blank" rel="noopener noreferrer" style="margin-left: 6px;" title="<?php esc_attr_e( 'Track Online', 'wpcalibrate-shipping-connector' ); ?>">
					<span class="dashicons dashicons-external"></span>
				</a>
			</p>
			<?php if ( ! empty( $last_sync ) ) : ?>
				<p class="text-muted" style="font-size: 11px;">
					<?php printf( esc_html__( 'Last Synced: %s', 'wpcalibrate-shipping-connector' ), esc_html( $last_sync ) ); ?>
				</p>
			<?php endif; ?>
		<?php endif; ?>

		<p>
			<strong><?php esc_html_e( 'Payment Mode:', 'wpcalibrate-shipping-connector' ); ?></strong>
			<?php echo esc_html( $pay_mode ); ?>
			<?php if ( 'COD' === $pay_mode ) : ?>
				(₹<?php echo esc_html( number_format( $cod_amt ?: (float) $order->get_total(), 2 ) ); ?>)
			<?php endif; ?>
		</p>

		<?php if ( ! empty( $return_waybill ) ) : ?>
			<p style="border-top: 1px dashed #cbd5e1; padding-top: 8px;">
				<strong><?php esc_html_e( 'Return AWB:', 'wpcalibrate-shipping-connector' ); ?></strong><br>
				<code><?php echo esc_html( $return_waybill ); ?></code>
			</p>
		<?php endif; ?>
	</div>

	<hr style="margin: 12px 0;">

	<!-- Action Forms -->
	<?php if ( ! $is_manifested ) : ?>
		<!-- Unmanifested: Create Shipment -->
		<form method="post" action="">
			<?php wp_nonce_field( 'wpcalibrate_order_action_' . $order_id, 'wpcalibrate_order_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_order_action" value="create_shipment" />
			<input type="hidden" name="order_id" value="<?php echo esc_attr( (string) $order_id ); ?>" />

			<p>
				<label for="selected_warehouse"><strong><?php esc_html_e( 'Origin Warehouse:', 'wpcalibrate-shipping-connector' ); ?></strong></label>
				<select name="selected_warehouse" id="selected_warehouse" style="width: 100%; margin-top: 4px;">
					<?php foreach ( $warehouses as $wh_id => $wh ) : ?>
						<option value="<?php echo esc_attr( $wh_id ); ?>" <?php selected( $selected_wh, $wh_id ); ?>>
							<?php echo esc_html( $wh['name'] . ( ! empty( $wh['is_default'] ) ? ' (Default)' : '' ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<button type="submit" class="button button-primary" style="width: 100%;">
					<?php esc_html_e( 'Manifest with Delhivery', 'wpcalibrate-shipping-connector' ); ?>
				</button>
			</p>
		</form>
	<?php else : ?>
		<!-- Manifested: Tracking & Labels & Cancel -->
		<div class="manifested-actions" style="display: flex; flex-direction: column; gap: 8px;">
			<!-- Refresh Tracking -->
			<form method="post" action="">
				<?php wp_nonce_field( 'wpcalibrate_order_action_' . $order_id, 'wpcalibrate_order_nonce' ); ?>
				<input type="hidden" name="wpcalibrate_order_action" value="refresh_tracking" />
				<input type="hidden" name="order_id" value="<?php echo esc_attr( (string) $order_id ); ?>" />
				<button type="submit" class="button button-secondary" style="width: 100%;">
					<span class="dashicons dashicons-update" style="vertical-align: middle;"></span> <?php esc_html_e( 'Refresh Tracking', 'wpcalibrate-shipping-connector' ); ?>
				</button>
			</form>

			<!-- Print Label (Direct HTML printable window) -->
			<?php
			$label_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'   => 'wpcalibrate_print_label',
						'order_id' => $order_id,
					),
					admin_url( 'admin-ajax.php' )
				),
				'wpcalibrate_print_label_' . $order_id
			);
			?>
			<a href="<?php echo esc_url( $label_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="text-align: center;">
				<span class="dashicons dashicons-printer" style="vertical-align: middle;"></span> <?php esc_html_e( 'Print Shipping Label', 'wpcalibrate-shipping-connector' ); ?>
			</a>

			<?php if ( ! $is_cancelled ) : ?>
				<!-- Cancel Shipment -->
				<form method="post" action="" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to cancel this Delhivery shipment?', 'wpcalibrate-shipping-connector' ); ?>');">
					<?php wp_nonce_field( 'wpcalibrate_order_action_' . $order_id, 'wpcalibrate_order_nonce' ); ?>
					<input type="hidden" name="wpcalibrate_order_action" value="cancel_shipment" />
					<input type="hidden" name="order_id" value="<?php echo esc_attr( (string) $order_id ); ?>" />
					<button type="submit" class="button button-link-delete" style="color: #b32d2e; width: 100%; text-align: center; margin-top: 4px;">
						<?php esc_html_e( 'Cancel Shipment with Delhivery', 'wpcalibrate-shipping-connector' ); ?>
					</button>
				</form>
			<?php endif; ?>

			<?php if ( empty( $return_waybill ) && ! $is_cancelled ) : ?>
				<!-- Reverse Pickup (Return) -->
				<details style="margin-top: 10px; border-top: 1px solid #e2e8f0; padding-top: 8px;">
					<summary style="cursor: pointer; font-weight: bold;"><?php esc_html_e( 'Create Reverse Pickup (RVP)', 'wpcalibrate-shipping-connector' ); ?></summary>
					<form method="post" action="" style="margin-top: 8px;">
						<?php wp_nonce_field( 'wpcalibrate_order_action_' . $order_id, 'wpcalibrate_order_nonce' ); ?>
						<input type="hidden" name="wpcalibrate_order_action" value="create_return" />
						<input type="hidden" name="order_id" value="<?php echo esc_attr( (string) $order_id ); ?>" />
						
						<label for="return_warehouse" style="font-size: 11px;">Destination Warehouse:</label>
						<select name="return_warehouse" id="return_warehouse" style="width: 100%; margin: 4px 0 8px 0;">
							<?php foreach ( $warehouses as $wh_id => $wh ) : ?>
								<option value="<?php echo esc_attr( $wh_id ); ?>"><?php echo esc_html( $wh['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="button button-secondary" style="width: 100%;">
							<?php esc_html_e( 'Generate Return Pickup', 'wpcalibrate-shipping-connector' ); ?>
						</button>
					</form>
				</details>
			<?php endif; ?>

			<!-- NDR Management (Visible when undelivered / NDR) -->
			<?php if ( str_contains( strtolower( $status_text ), 'ndr' ) || str_contains( strtolower( $status_text ), 'undelivered' ) || str_contains( strtolower( $status_text ), 'failed' ) ) : ?>
				<details open style="margin-top: 10px; border: 1px solid #f59e0b; padding: 8px; border-radius: 4px; background: #fffbeb;">
					<summary style="cursor: pointer; font-weight: bold; color: #b45309;"><?php esc_html_e( 'Action NDR Delivery Exception', 'wpcalibrate-shipping-connector' ); ?></summary>
					<form method="post" action="" style="margin-top: 8px;">
						<?php wp_nonce_field( 'wpcalibrate_order_action_' . $order_id, 'wpcalibrate_order_nonce' ); ?>
						<input type="hidden" name="wpcalibrate_order_action" value="submit_ndr" />
						<input type="hidden" name="order_id" value="<?php echo esc_attr( (string) $order_id ); ?>" />

						<p style="margin: 4px 0;">
							<label for="ndr_action_type" style="font-size: 11px; font-weight: bold;">Action:</label>
							<select name="ndr_action_type" id="ndr_action_type" style="width: 100%;">
								<option value="REATTEMPT"><?php esc_html_e( 'Reattempt Delivery', 'wpcalibrate-shipping-connector' ); ?></option>
								<option value="RESCHEDULE"><?php esc_html_e( 'Reschedule to Date', 'wpcalibrate-shipping-connector' ); ?></option>
								<option value="RTO"><?php esc_html_e( 'Return to Origin (RTO)', 'wpcalibrate-shipping-connector' ); ?></option>
							</select>
						</p>

						<p style="margin: 4px 0;">
							<label for="ndr_reschedule_date" style="font-size: 11px;">Reschedule Date (If Rescheduling):</label>
							<input type="date" name="ndr_reschedule_date" id="ndr_reschedule_date" style="width: 100%;" />
						</p>

						<p style="margin: 4px 0;">
							<label for="ndr_remarks" style="font-size: 11px;">Delivery Instructions / Remarks:</label>
							<input type="text" name="ndr_remarks" id="ndr_remarks" placeholder="e.g. Call customer before arrival" style="width: 100%;" />
						</p>

						<button type="submit" class="button button-primary" style="width: 100%; margin-top: 6px;">
							<?php esc_html_e( 'Submit NDR Action', 'wpcalibrate-shipping-connector' ); ?>
						</button>
					</form>
				</details>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
