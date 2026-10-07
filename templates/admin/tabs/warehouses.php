<?php
/**
 * Settings Tab: Warehouses
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$warehouses = $this->warehouse_service->get_warehouses();
?>

<div class="wpcalibrate-warehouses-settings">
	<h2><?php esc_html_e( 'Warehouse & Pickup Location Management', 'wpcalibrate-shipping-connector' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Configure pickup locations for Delhivery. The warehouse name must match your registered pickup location in Delhivery One.', 'wpcalibrate-shipping-connector' ); ?>
	</p>

	<h3><?php esc_html_e( 'Configured Warehouses', 'wpcalibrate-shipping-connector' ); ?></h3>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name / Identifier', 'wpcalibrate-shipping-connector' ); ?></th>
				<th><?php esc_html_e( 'Address', 'wpcalibrate-shipping-connector' ); ?></th>
				<th><?php esc_html_e( 'City / State', 'wpcalibrate-shipping-connector' ); ?></th>
				<th><?php esc_html_e( 'PIN Code', 'wpcalibrate-shipping-connector' ); ?></th>
				<th><?php esc_html_e( 'Phone', 'wpcalibrate-shipping-connector' ); ?></th>
				<th><?php esc_html_e( 'Default', 'wpcalibrate-shipping-connector' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wpcalibrate-shipping-connector' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $warehouses ) ) : ?>
				<?php foreach ( $warehouses as $wh_id => $wh ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $wh['name'] ); ?></strong> (<code><?php echo esc_html( $wh['id'] ); ?></code>)</td>
						<td><?php echo esc_html( $wh['address'] ); ?></td>
						<td><?php echo esc_html( $wh['city'] . ', ' . $wh['state'] ); ?></td>
						<td><code><?php echo esc_html( $wh['pin'] ); ?></code></td>
						<td><?php echo esc_html( $wh['phone'] ); ?></td>
						<td>
							<?php if ( ! empty( $wh['is_default'] ) ) : ?>
								<span class="dashicons dashicons-yes-alt text-success"></span> <strong><?php esc_html_e( 'Default', 'wpcalibrate-shipping-connector' ); ?></strong>
							<?php else : ?>
								<span class="text-muted">&mdash;</span>
							<?php endif; ?>
						</td>
						<td>
							<form method="post" action="" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this warehouse?', 'wpcalibrate-shipping-connector' ); ?>');">
								<?php wp_nonce_field( 'wpcalibrate_shipping_settings_nonce', 'wpcalibrate_shipping_nonce' ); ?>
								<input type="hidden" name="wpcalibrate_shipping_action" value="delete_warehouse" />
								<input type="hidden" name="current_tab" value="warehouses" />
								<input type="hidden" name="warehouse_id" value="<?php echo esc_attr( $wh_id ); ?>" />
								<button type="submit" class="button button-link-delete" style="color: #b32d2e;"><?php esc_html_e( 'Delete', 'wpcalibrate-shipping-connector' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="7"><?php esc_html_e( 'No warehouses configured yet. Add your primary warehouse below.', 'wpcalibrate-shipping-connector' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<hr style="margin: 30px 0;">

	<h3><?php esc_html_e( 'Add / Update Warehouse', 'wpcalibrate-shipping-connector' ); ?></h3>

	<form method="post" action="">
		<?php wp_nonce_field( 'wpcalibrate_shipping_settings_nonce', 'wpcalibrate_shipping_nonce' ); ?>
		<input type="hidden" name="wpcalibrate_shipping_action" value="save_warehouse" />
		<input type="hidden" name="current_tab" value="warehouses" />

		<table class="form-table">
			<tr>
				<th scope="row"><label for="warehouse_name"><?php esc_html_e( 'Warehouse / Pickup Location Name', 'wpcalibrate-shipping-connector' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="text" name="warehouse_name" id="warehouse_name" class="regular-text" required placeholder="e.g. Primary Warehouse" />
					<p class="description"><?php esc_html_e( 'Must match your registered warehouse name in Delhivery One exactly.', 'wpcalibrate-shipping-connector' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="warehouse_address"><?php esc_html_e( 'Address', 'wpcalibrate-shipping-connector' ); ?> <span class="required">*</span></label></th>
				<td>
					<textarea name="warehouse_address" id="warehouse_address" rows="3" class="large-text" required placeholder="Street address line"></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="warehouse_pin"><?php esc_html_e( '6-Digit Indian PIN Code', 'wpcalibrate-shipping-connector' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="text" name="warehouse_pin" id="warehouse_pin" class="small-text" required maxlength="6" pattern="[0-9]{6}" placeholder="110001" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="warehouse_city"><?php esc_html_e( 'City', 'wpcalibrate-shipping-connector' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="text" name="warehouse_city" id="warehouse_city" class="regular-text" required placeholder="e.g. New Delhi" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="warehouse_state"><?php esc_html_e( 'State', 'wpcalibrate-shipping-connector' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="text" name="warehouse_state" id="warehouse_state" class="regular-text" required placeholder="e.g. Delhi" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="warehouse_phone"><?php esc_html_e( 'Contact Phone Number', 'wpcalibrate-shipping-connector' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="text" name="warehouse_phone" id="warehouse_phone" class="regular-text" required placeholder="10-digit mobile number" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Default Warehouse', 'wpcalibrate-shipping-connector' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="warehouse_is_default" value="1" />
						<?php esc_html_e( 'Set as default warehouse for rate estimation and new orders', 'wpcalibrate-shipping-connector' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Warehouse', 'wpcalibrate-shipping-connector' ); ?></button>
		</p>
	</form>
</div>
