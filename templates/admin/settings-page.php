<?php
/**
 * Admin Settings Page Main Template.
 *
 * @package WPCalibrate\ShippingConnector
 *
 * @var string               $current_tab
 * @var array<string, string> $tabs
 * @var array<string, mixed>  $settings
 * @var array<string, mixed>|null $notice
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$branding_icon_url = plugin_dir_url( dirname( __DIR__ ) ) . 'branding/icon-dark.png';
$orders_url        = admin_url( 'admin.php?page=wc-orders' );
$env               = $settings['environment'] ?? 'staging';
$has_token         = ! empty( $settings['api_token'] );
?>

<div class="wrap wpcalibrate-shipping-wrap">

	<!-- Header with Branding, Version, and Quick Actions -->
	<header class="wpcalibrate-header">
		<div class="wpcalibrate-branding">
			<img src="<?php echo esc_url( $branding_icon_url ); ?>" alt="WPCalibrate" class="wpcalibrate-logo" width="36" height="36" />
			<div class="wpcalibrate-title-group">
				<h1 class="wpcalibrate-title"><?php esc_html_e( 'WPCalibrate Shipping Connector', 'wpcalibrate-shipping-connector' ); ?></h1>
				<span class="wpcalibrate-version"><?php echo esc_html( 'v' . \WPCalibrate\ShippingConnector\Support\Constants::PLUGIN_VERSION ); ?></span>
			</div>
		</div>

		<div class="wpcalibrate-header-actions">
			<div class="wpcalibrate-badge">
				<span class="status-indicator <?php echo $has_token ? 'active' : 'inactive'; ?>"></span>
				<span><?php echo 'production' === $env ? esc_html__( 'Delhivery Live Production', 'wpcalibrate-shipping-connector' ) : esc_html__( 'Delhivery Sandbox / Staging', 'wpcalibrate-shipping-connector' ); ?></span>
			</div>
			<a href="<?php echo esc_url( $orders_url ); ?>" class="button button-secondary wpcalibrate-header-btn">
				<span class="dashicons dashicons-cart" aria-hidden="true"></span>
				<?php esc_html_e( 'View Orders', 'wpcalibrate-shipping-connector' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-shipping-connector&tab=support' ) ); ?>" class="button button-secondary wpcalibrate-header-btn">
				<span class="dashicons dashicons-sos" aria-hidden="true"></span>
				<?php esc_html_e( 'Support', 'wpcalibrate-shipping-connector' ); ?>
			</a>
		</div>
	</header>

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ?? 'info' ); ?> is-dismissible wpcalibrate-admin-notice">
			<p><?php echo esc_html( $notice['msg'] ?? '' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Navigation Tabs -->
	<nav class="nav-tab-wrapper wpcalibrate-nav-tabs" aria-label="<?php esc_attr_e( 'Shipping Connector Tabs', 'wpcalibrate-shipping-connector' ); ?>">
		<?php foreach ( $tabs as $tab_key => $tab_title ) : ?>
			<?php
			$tab_url = add_query_arg(
				array(
					'page' => \WPCalibrate\ShippingConnector\Support\Constants::SUBMENU_SLUG,
					'tab'  => $tab_key,
				),
				admin_url( 'admin.php' )
			);
			$active_class = ( $current_tab === $tab_key ) ? 'nav-tab-active' : '';
			?>
			<a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab <?php echo esc_attr( $active_class ); ?>">
				<?php echo esc_html( $tab_title ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<!-- Tab Content Container -->
	<div class="wpcalibrate-tab-content">
		<?php
		$tab_file = __DIR__ . '/tabs/' . str_replace( '_', '-', $current_tab ) . '.php';
		if ( file_exists( $tab_file ) ) {
			include $tab_file;
		} else {
			include __DIR__ . '/tabs/overview.php';
		}
		?>
	</div>
</div>
