<?php
/**
 * Settings Tab: Dashboard
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_token    = ! empty( $settings['api_token'] );
$env          = $settings['environment'] ?? 'staging';
$default_wh   = $this->warehouse_service->get_default_warehouse();
$webhook_url  = rest_url( 'wpcalibrate-shipping/v1/webhook' );
$last_sync    = get_option( \WPCalibrate\ShippingConnector\Support\Constants::OPTION_LAST_SYNC, __( 'Never', 'wpcalibrate-shipping-connector' ) );
$rates_on     = ( $settings['live_rates_enabled'] ?? 'no' ) === 'yes';
$auto_on      = ( $settings['auto_shipment_enabled'] ?? 'no' ) === 'yes';
?>

<div class="wpcalibrate-dashboard">
	<h2><?php esc_html_e( 'Logistics Operations Dashboard', 'wpcalibrate-shipping-connector' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Operational overview of your Delhivery B2C connection and shipping workflows.', 'wpcalibrate-shipping-connector' ); ?>
	</p>

	<div class="wpcalibrate-cards-grid">
		<!-- API Connection Card -->
		<div class="wpcalibrate-card">
			<div class="card-header">
				<span class="dashicons dashicons-admin-network"></span>
				<h3><?php esc_html_e( 'API Connection', 'wpcalibrate-shipping-connector' ); ?></h3>
			</div>
			<div class="card-body">
				<p>
					<strong><?php esc_html_e( 'Environment:', 'wpcalibrate-shipping-connector' ); ?></strong>
					<span class="badge badge-<?php echo esc_attr( $env ); ?>"><?php echo esc_html( strtoupper( $env ) ); ?></span>
				</p>
				<p>
					<strong><?php esc_html_e( 'Token Configured:', 'wpcalibrate-shipping-connector' ); ?></strong>
					<?php if ( $has_token ) : ?>
						<span class="text-success"><?php esc_html_e( 'Configured', 'wpcalibrate-shipping-connector' ); ?></span>
					<?php else : ?>
						<span class="text-danger"><?php esc_html_e( 'Missing Token', 'wpcalibrate-shipping-connector' ); ?></span>
					<?php endif; ?>
				</p>
				<p>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wpcalibrate-shipping-connector', 'tab' => 'api_connection' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Configure API &rarr;', 'wpcalibrate-shipping-connector' ); ?>
					</a>
				</p>
			</div>
		</div>

		<!-- Warehouse Card -->
		<div class="wpcalibrate-card">
			<div class="card-header">
				<span class="dashicons dashicons-store"></span>
				<h3><?php esc_html_e( 'Active Warehouse', 'wpcalibrate-shipping-connector' ); ?></h3>
			</div>
			<div class="card-body">
				<?php if ( $default_wh ) : ?>
					<p><strong><?php echo esc_html( $default_wh['name'] ); ?></strong></p>
					<p class="text-muted"><?php echo esc_html( $default_wh['city'] . ' (' . $default_wh['pin'] . ')' ); ?></p>
				<?php else : ?>
					<p class="text-danger"><?php esc_html_e( 'No default warehouse configured.', 'wpcalibrate-shipping-connector' ); ?></p>
				<?php endif; ?>
				<p>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wpcalibrate-shipping-connector', 'tab' => 'warehouses' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Manage Warehouses &rarr;', 'wpcalibrate-shipping-connector' ); ?>
					</a>
				</p>
			</div>
		</div>

		<!-- Webhook & Sync Card -->
		<div class="wpcalibrate-card">
			<div class="card-header">
				<span class="dashicons dashicons-update"></span>
				<h3><?php esc_html_e( 'Tracking & Webhooks', 'wpcalibrate-shipping-connector' ); ?></h3>
			</div>
			<div class="card-body">
				<p>
					<strong><?php esc_html_e( 'Last Background Sync:', 'wpcalibrate-shipping-connector' ); ?></strong><br>
					<code><?php echo esc_html( (string) $last_sync ); ?></code>
				</p>
				<p>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wpcalibrate-shipping-connector', 'tab' => 'tracking_webhooks' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Webhook Settings &rarr;', 'wpcalibrate-shipping-connector' ); ?>
					</a>
				</p>
			</div>
		</div>

		<!-- Live Rates & Automation Card -->
		<div class="wpcalibrate-card">
			<div class="card-header">
				<span class="dashicons dashicons-cart"></span>
				<h3><?php esc_html_e( 'Automation Status', 'wpcalibrate-shipping-connector' ); ?></h3>
			</div>
			<div class="card-body">
				<p>
					<strong><?php esc_html_e( 'Live Checkout Rates:', 'wpcalibrate-shipping-connector' ); ?></strong>
					<?php echo $rates_on ? '<span class="text-success">Enabled</span>' : '<span class="text-muted">Disabled</span>'; ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Auto Shipment Creation:', 'wpcalibrate-shipping-connector' ); ?></strong>
					<?php echo $auto_on ? '<span class="text-success">Enabled</span>' : '<span class="text-muted">Disabled</span>'; ?>
				</p>
				<p>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wpcalibrate-shipping-connector', 'tab' => 'shipments' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Automation Settings &rarr;', 'wpcalibrate-shipping-connector' ); ?>
					</a>
				</p>
			</div>
		</div>
	</div>
</div>
