<?php
/**
 * Settings Tab: NDR & Returns
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wpcalibrate-ndr-returns-settings">
	<h2><?php esc_html_e( 'NDR Exceptions & Reverse Logistics (Returns)', 'wpcalibrate-shipping-connector' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Manage non-delivery exception workflows and doorstep customer return pickups.', 'wpcalibrate-shipping-connector' ); ?>
	</p>

	<div class="wpcalibrate-cards-grid" style="margin-top: 20px;">
		<div class="wpcalibrate-card">
			<div class="card-header">
				<span class="dashicons dashicons-warning"></span>
				<h3><?php esc_html_e( 'Non-Delivery Report (NDR) Management', 'wpcalibrate-shipping-connector' ); ?></h3>
			</div>
			<div class="card-body">
				<p><?php esc_html_e( 'When a courier executive is unable to deliver a parcel (e.g. customer unavailable, address incomplete), Delhivery places the shipment into an NDR exception state.', 'wpcalibrate-shipping-connector' ); ?></p>
				<p><strong><?php esc_html_e( 'Supported Documented Actions:', 'wpcalibrate-shipping-connector' ); ?></strong></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><strong>REATTEMPT:</strong> <?php esc_html_e( 'Request another delivery attempt with updated remarks/consignee notes.', 'wpcalibrate-shipping-connector' ); ?></li>
					<li><strong>RESCHEDULE:</strong> <?php esc_html_e( 'Defer delivery attempt to a specific future date requested by customer.', 'wpcalibrate-shipping-connector' ); ?></li>
					<li><strong>RTO (Return to Origin):</strong> <?php esc_html_e( 'Instruct Delhivery to terminate delivery attempts and return the parcel to the origin warehouse.', 'wpcalibrate-shipping-connector' ); ?></li>
				</ul>
				<p class="text-muted"><?php esc_html_e( 'NDR action controls are accessible directly on individual order screens whenever a shipment enters an NDR state.', 'wpcalibrate-shipping-connector' ); ?></p>
			</div>
		</div>

		<div class="wpcalibrate-card">
			<div class="card-header">
				<span class="dashicons dashicons-undo"></span>
				<h3><?php esc_html_e( 'Reverse Pickups (RVP & QC)', 'wpcalibrate-shipping-connector' ); ?></h3>
			</div>
			<div class="card-body">
				<p><?php esc_html_e( 'WPCalibrate supports creating domestic reverse pickup shipments from the customer address back to your warehouse.', 'wpcalibrate-shipping-connector' ); ?></p>
				<p><strong><?php esc_html_e( 'Key Safety Principles:', 'wpcalibrate-shipping-connector' ); ?></strong></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><?php esc_html_e( 'Reverse shipments are logistics actions decoupled from WooCommerce refunds. A reverse shipment does not automatically debit or refund customer money.', 'wpcalibrate-shipping-connector' ); ?></li>
					<li><?php esc_html_e( 'Reverse pickups are scheduled automatically by Delhivery without requiring separate manual pickup requests.', 'wpcalibrate-shipping-connector' ); ?></li>
					<li><?php esc_html_e( 'Optional Quality Check (QC) checklists are supported where enabled on your merchant Delhivery account.', 'wpcalibrate-shipping-connector' ); ?></li>
				</ul>
				<p class="text-muted"><?php esc_html_e( 'Reverse pickup actions are available on the order management sidebar for fulfilled shipments.', 'wpcalibrate-shipping-connector' ); ?></p>
			</div>
		</div>
	</div>
</div>
