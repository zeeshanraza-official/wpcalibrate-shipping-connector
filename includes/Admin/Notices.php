<?php
/**
 * Admin notices handler.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Admin;

use WPCalibrate\ShippingConnector\Core\Requirements;

/**
 * Class Notices
 */
final class Notices {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
	}

	/**
	 * Render admin notices.
	 *
	 * @return void
	 */
	public function render_notices(): void {
		if ( ! Requirements::check() ) {
			$errors = Requirements::get_errors();
			?>
			<div class="notice notice-error">
				<p><strong><?php esc_html_e( 'WPCalibrate Shipping Connector cannot run:', 'wpcalibrate-shipping-connector' ); ?></strong></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<?php foreach ( $errors as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		}
	}
}
