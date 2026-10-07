<?php
/**
 * Settings Tab: Overview, Purpose, Method & How-To Guide.
 *
 * @package WPCalibrate\ShippingConnector
 *
 * @var array<string, mixed>  $settings
 * @var array<string, string> $tabs
 * @var string               $current_tab
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_token       = ! empty( $settings['api_token'] );
$env             = $settings['environment'] ?? 'staging';
$client_name     = $settings['client_name'] ?? '';
$warehouses      = get_option( \WPCalibrate\ShippingConnector\Support\Constants::OPTION_WAREHOUSES, array() );
$warehouse_count = is_array( $warehouses ) ? count( $warehouses ) : 0;
$default_wh      = 'Default';
if ( is_array( $warehouses ) ) {
	foreach ( $warehouses as $wh ) {
		if ( ! empty( $wh['is_default'] ) ) {
			$default_wh = $wh['name'] ?? 'Primary';
			break;
		}
	}
}

$webhook_url = rest_url( 'wpcalibrate-shipping/v1/webhook' );
?>

<div class="wpcalibrate-overview-container">

	<!-- 1. Hero Card with Live Status Strip -->
	<div class="wpcalibrate-section-card wpcalibrate-overview-hero">
		<div class="wpcalibrate-overview-hero-content">
			<div class="wpcalibrate-badge-pill">
				<span class="status-indicator active"></span>
				<span><?php esc_html_e( 'Enterprise Logistics Integration', 'wpcalibrate-shipping-connector' ); ?></span>
			</div>
			<h2><?php esc_html_e( 'Welcome to WPCalibrate Shipping Connector', 'wpcalibrate-shipping-connector' ); ?></h2>
			<p class="wpcalibrate-overview-hero-desc">
				<?php esc_html_e( 'A high-performance, enterprise-grade bridge connecting WooCommerce with Delhivery Domestic Logistics. Deliver live checkout rates, instant pincode serviceability, 1-click waybill generation, thermal label printing, automated tracking, NDR management, and customer reverse returns.', 'wpcalibrate-shipping-connector' ); ?>
			</p>
		</div>

		<!-- Live System Status Strip -->
		<div class="wpcalibrate-overview-status-strip">
			<div class="wpcalibrate-status-pill">
				<span class="wpcalibrate-status-dot <?php echo $has_token ? 'wpcalibrate-dot-active' : 'wpcalibrate-dot-warning'; ?>"></span>
				<div class="wpcalibrate-status-info">
					<span class="wpcalibrate-status-label"><?php esc_html_e( 'API Connection', 'wpcalibrate-shipping-connector' ); ?></span>
					<strong>
						<?php if ( $has_token ) : ?>
							<?php echo 'production' === $env ? esc_html__( 'Live Production', 'wpcalibrate-shipping-connector' ) : esc_html__( 'Staging Sandbox', 'wpcalibrate-shipping-connector' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Not Configured', 'wpcalibrate-shipping-connector' ); ?>
						<?php endif; ?>
					</strong>
				</div>
			</div>

			<div class="wpcalibrate-status-pill">
				<span class="dashicons dashicons-location-alt" aria-hidden="true"></span>
				<div class="wpcalibrate-status-info">
					<span class="wpcalibrate-status-label"><?php esc_html_e( 'Origin Warehouses', 'wpcalibrate-shipping-connector' ); ?></span>
					<strong>
						<?php
						printf(
							/* translators: 1: warehouse count, 2: default warehouse name */
							esc_html__( '%1$d Active (%2$s)', 'wpcalibrate-shipping-connector' ),
							$warehouse_count,
							esc_html( $default_wh )
						);
						?>
					</strong>
				</div>
			</div>

			<div class="wpcalibrate-status-pill">
				<span class="dashicons dashicons-database" aria-hidden="true"></span>
				<div class="wpcalibrate-status-info">
					<span class="wpcalibrate-status-label"><?php esc_html_e( 'HPOS Compatibility', 'wpcalibrate-shipping-connector' ); ?></span>
					<strong class="text-success"><?php esc_html_e( 'Enabled & Certified', 'wpcalibrate-shipping-connector' ); ?></strong>
				</div>
			</div>

			<div class="wpcalibrate-status-pill">
				<span class="dashicons dashicons-rest-api" aria-hidden="true"></span>
				<div class="wpcalibrate-status-info">
					<span class="wpcalibrate-status-label"><?php esc_html_e( 'Inbound Webhooks', 'wpcalibrate-shipping-connector' ); ?></span>
					<strong><?php esc_html_e( 'REST Endpoint Ready', 'wpcalibrate-shipping-connector' ); ?></strong>
				</div>
			</div>
		</div>
	</div>

	<!-- 2. Purpose of the Plugin -->
	<div class="wpcalibrate-section-card">
		<div class="wpcalibrate-card-title-group">
			<span class="dashicons dashicons-lightbulb wpcalibrate-title-icon" aria-hidden="true"></span>
			<div>
				<h2><?php esc_html_e( 'Purpose & Key Capabilities', 'wpcalibrate-shipping-connector' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Why WPCalibrate Shipping Connector is built for Indian eCommerce merchants.', 'wpcalibrate-shipping-connector' ); ?></p>
			</div>
		</div>

		<div class="wpcalibrate-purpose-grid">
			<div class="wpcalibrate-purpose-item">
				<div class="wpcalibrate-purpose-icon"><span class="dashicons dashicons-money-alt"></span></div>
				<h3><?php esc_html_e( 'Direct Carrier Rates (No Middlemen)', 'wpcalibrate-shipping-connector' ); ?></h3>
				<p><?php esc_html_e( 'Eliminate expensive third-party shipping aggregator commissions. Connect directly to your Delhivery account for actual pre-negotiated commercial rates.', 'wpcalibrate-shipping-connector' ); ?></p>
			</div>
			<div class="wpcalibrate-purpose-item">
				<div class="wpcalibrate-purpose-icon"><span class="dashicons dashicons-yes-alt"></span></div>
				<h3><?php esc_html_e( 'Real-Time Pincode Serviceability & COD', 'wpcalibrate-shipping-connector' ); ?></h3>
				<p><?php esc_html_e( 'Validate customer delivery pin codes instantly. Automatically toggle Cash on Delivery (COD) availability based on live Delhivery serviceability matrices.', 'wpcalibrate-shipping-connector' ); ?></p>
			</div>
			<div class="wpcalibrate-purpose-item">
				<div class="wpcalibrate-purpose-icon"><span class="dashicons dashicons-forms"></span></div>
				<h3><?php esc_html_e( '1-Click Waybills & Thermal Labels', 'wpcalibrate-shipping-connector' ); ?></h3>
				<p><?php esc_html_e( 'Generate AWB numbers and print 4x6 inch thermal shipping labels complete with high-density Code 128 barcodes directly from the order edit screen.', 'wpcalibrate-shipping-connector' ); ?></p>
			</div>
			<div class="wpcalibrate-purpose-item">
				<div class="wpcalibrate-purpose-icon"><span class="dashicons dashicons-backup"></span></div>
				<h3><?php esc_html_e( 'NDR Management & Reverse Returns', 'wpcalibrate-shipping-connector' ); ?></h3>
				<p><?php esc_html_e( 'Reduce RTO rates with built-in Non-Delivery Report actions (re-attempt, address fix, reschedule) and process customer return pickups seamlessly.', 'wpcalibrate-shipping-connector' ); ?></p>
			</div>
		</div>
	</div>

	<!-- 3. Operational Method: How It Works -->
	<div class="wpcalibrate-section-card">
		<div class="wpcalibrate-card-title-group">
			<span class="dashicons dashicons-networking wpcalibrate-title-icon" aria-hidden="true"></span>
			<div>
				<h2><?php esc_html_e( 'Method & Shipment Lifecycle', 'wpcalibrate-shipping-connector' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Understanding the automated operational flow from customer checkout to delivery.', 'wpcalibrate-shipping-connector' ); ?></p>
			</div>
		</div>

		<div class="wpcalibrate-method-flow">
			<div class="wpcalibrate-flow-step">
				<div class="wpcalibrate-step-number">1</div>
				<div class="wpcalibrate-step-content">
					<h4><?php esc_html_e( 'Checkout & Rate Fetching', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'Customer enters pincode. The plugin queries Delhivery API for pincode serviceability, calculates dead/volumetric weight, applies your markups, and returns live rates.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</div>
			<div class="wpcalibrate-flow-step">
				<div class="wpcalibrate-step-number">2</div>
				<div class="wpcalibrate-step-content">
					<h4><?php esc_html_e( 'Order Created & Metas Stored', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'WooCommerce creates order. Package details, COD flags, and assigned origin warehouse are safely stored in HPOS / postmeta.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</div>
			<div class="wpcalibrate-flow-step">
				<div class="wpcalibrate-step-number">3</div>
				<div class="wpcalibrate-step-content">
					<h4><?php esc_html_e( 'Shipment Creation (AWB)', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'Click "Create Shipment" or let Action Scheduler automate it on "Processing". Delhivery generates a verified Waybill / AWB number.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</div>
			<div class="wpcalibrate-flow-step">
				<div class="wpcalibrate-step-number">4</div>
				<div class="wpcalibrate-step-content">
					<h4><?php esc_html_e( 'Thermal Label & Pickup Request', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'Print 4x6 inch thermal barcode label. Dispatch scheduled courier pickup request to your nearest Delhivery hub.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</div>
			<div class="wpcalibrate-flow-step">
				<div class="wpcalibrate-step-number">5</div>
				<div class="wpcalibrate-step-content">
					<h4><?php esc_html_e( 'Live Tracking & Webhooks', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'Delhivery pushes real-time tracking events via Webhook. Status updates to Dispatched, In Transit, and Delivered automatically.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</div>
			<div class="wpcalibrate-flow-step">
				<div class="wpcalibrate-step-number">6</div>
				<div class="wpcalibrate-step-content">
					<h4><?php esc_html_e( 'NDR Resolution & Returns', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'If customer is unavailable, trigger NDR re-attempts or instructions directly from the order panel to avoid costly RTOs.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- 4. How to Use: 4-Step Quick Start Guide -->
	<div class="wpcalibrate-section-card">
		<div class="wpcalibrate-card-title-group">
			<span class="dashicons dashicons-controls-play wpcalibrate-title-icon" aria-hidden="true"></span>
			<div>
				<h2><?php esc_html_e( 'How to Use: 4-Step Quick Start Guide', 'wpcalibrate-shipping-connector' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Follow these four simple steps to get up and running with live shipments.', 'wpcalibrate-shipping-connector' ); ?></p>
			</div>
		</div>

		<div class="wpcalibrate-guide-steps">
			<div class="wpcalibrate-guide-card">
				<div class="wpcalibrate-guide-header">
					<span class="wpcalibrate-guide-badge"><?php esc_html_e( 'Step 1', 'wpcalibrate-shipping-connector' ); ?></span>
					<h3><?php esc_html_e( 'Connect API Credentials', 'wpcalibrate-shipping-connector' ); ?></h3>
				</div>
				<p><?php esc_html_e( 'Go to the API Connection tab. Enter your registered Delhivery Client Name and API Token. Choose Staging for testing or Production for live orders. Click "Test Connection" to verify.', 'wpcalibrate-shipping-connector' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=api_connection' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-admin-network" aria-hidden="true"></span>
					<?php esc_html_e( 'Configure API Connection', 'wpcalibrate-shipping-connector' ); ?>
				</a>
			</div>

			<div class="wpcalibrate-guide-card">
				<div class="wpcalibrate-guide-header">
					<span class="wpcalibrate-guide-badge"><?php esc_html_e( 'Step 2', 'wpcalibrate-shipping-connector' ); ?></span>
					<h3><?php esc_html_e( 'Set Origin Warehouses', 'wpcalibrate-shipping-connector' ); ?></h3>
				</div>
				<p><?php esc_html_e( 'Navigate to Warehouses tab. Add your primary dispatch address, contact person, phone number, and origin pincode. This location must match your registered Delhivery pickup center.', 'wpcalibrate-shipping-connector' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=warehouses' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-building" aria-hidden="true"></span>
					<?php esc_html_e( 'Manage Warehouses', 'wpcalibrate-shipping-connector' ); ?>
				</a>
			</div>

			<div class="wpcalibrate-guide-card">
				<div class="wpcalibrate-guide-header">
					<span class="wpcalibrate-guide-badge"><?php esc_html_e( 'Step 3', 'wpcalibrate-shipping-connector' ); ?></span>
					<h3><?php esc_html_e( 'Enable WooCommerce Shipping Zone', 'wpcalibrate-shipping-connector' ); ?></h3>
				</div>
				<p><?php esc_html_e( 'Go to WooCommerce > Settings > Shipping > Shipping Zones. Add "Delhivery B2C Express" method to your domestic zone (India). In the Shipping tab, configure handling fees or percentage markups.', 'wpcalibrate-shipping-connector' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=shipping' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-cart" aria-hidden="true"></span>
					<?php esc_html_e( 'Configure Shipping Rates', 'wpcalibrate-shipping-connector' ); ?>
				</a>
			</div>

			<div class="wpcalibrate-guide-card">
				<div class="wpcalibrate-guide-header">
					<span class="wpcalibrate-guide-badge"><?php esc_html_e( 'Step 4', 'wpcalibrate-shipping-connector' ); ?></span>
					<h3><?php esc_html_e( 'Enable Webhooks & Automation', 'wpcalibrate-shipping-connector' ); ?></h3>
				</div>
				<p><?php esc_html_e( 'Copy your unique Webhook URL and Secret from Tracking & Webhooks tab into your Delhivery UC / Client Portal to enable real-time order tracking status updates automatically.', 'wpcalibrate-shipping-connector' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=tracking_webhooks' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-update" aria-hidden="true"></span>
					<?php esc_html_e( 'View Webhook Settings', 'wpcalibrate-shipping-connector' ); ?>
				</a>
			</div>
		</div>
	</div>

	<!-- 5. Feature Navigation Matrix -->
	<div class="wpcalibrate-section-card">
		<div class="wpcalibrate-card-title-group">
			<span class="dashicons dashicons-grid-view wpcalibrate-title-icon" aria-hidden="true"></span>
			<div>
				<h2><?php esc_html_e( 'Feature Matrix & Tab Directory', 'wpcalibrate-shipping-connector' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Direct access to all plugin subsystems and settings.', 'wpcalibrate-shipping-connector' ); ?></p>
			</div>
		</div>

		<div class="wpcalibrate-features-directory">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=dashboard' ) ); ?>" class="wpcalibrate-dir-card">
				<span class="dashicons dashicons-dashboard" aria-hidden="true"></span>
				<div class="wpcalibrate-dir-info">
					<h4><?php esc_html_e( 'Dashboard', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'System health, quick shipment counters, recent logs, and quick actions.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</a>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=api_connection' ) ); ?>" class="wpcalibrate-dir-card">
				<span class="dashicons dashicons-admin-network" aria-hidden="true"></span>
				<div class="wpcalibrate-dir-info">
					<h4><?php esc_html_e( 'API Connection', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'Credentials, sandbox environment toggle, and API connection diagnostic test.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</a>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=shipping' ) ); ?>" class="wpcalibrate-dir-card">
				<span class="dashicons dashicons-airplane" aria-hidden="true"></span>
				<div class="wpcalibrate-dir-info">
					<h4><?php esc_html_e( 'Shipping & Rates', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'Rate markup rules, COD charges, fallback rates, and volumetric divisor.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</a>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=shipments' ) ); ?>" class="wpcalibrate-dir-card">
				<span class="dashicons dashicons-media-document" aria-hidden="true"></span>
				<div class="wpcalibrate-dir-info">
					<h4><?php esc_html_e( 'Shipment Automation', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'Auto-create on processing, label sizes (4x6 thermal), and return warehouse routing.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</a>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=warehouses' ) ); ?>" class="wpcalibrate-dir-card">
				<span class="dashicons dashicons-building" aria-hidden="true"></span>
				<div class="wpcalibrate-dir-info">
					<h4><?php esc_html_e( 'Multi-Warehouses', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'Manage pickup locations, return centers, and origin pincode configurations.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</a>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=tracking_webhooks' ) ); ?>" class="wpcalibrate-dir-card">
				<span class="dashicons dashicons-update" aria-hidden="true"></span>
				<div class="wpcalibrate-dir-info">
					<h4><?php esc_html_e( 'Tracking & Webhooks', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'Webhook URL, secret token, Action Scheduler polling, and status mappings.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</a>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=ndr_returns' ) ); ?>" class="wpcalibrate-dir-card">
				<span class="dashicons dashicons-undo" aria-hidden="true"></span>
				<div class="wpcalibrate-dir-info">
					<h4><?php esc_html_e( 'NDR & Returns', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'Non-Delivery Report workflows, auto re-attempts, and reverse logistics QC.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</a>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=logs_tools' ) ); ?>" class="wpcalibrate-dir-card">
				<span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
				<div class="wpcalibrate-dir-info">
					<h4><?php esc_html_e( 'Logs & Diagnostics', 'wpcalibrate-shipping-connector' ); ?></h4>
					<p><?php esc_html_e( 'Interactive pincode tester, rate simulation tool, and cache flush utility.', 'wpcalibrate-shipping-connector' ); ?></p>
				</div>
			</a>
		</div>
	</div>

</div>
