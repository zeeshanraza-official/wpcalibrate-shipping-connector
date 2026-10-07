<?php
/**
 * Plugin Deactivator.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Core;

use WPCalibrate\ShippingConnector\Support\Constants;

/**
 * Class Deactivator
 */
final class Deactivator {

	/**
	 * Run deactivation logic.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Unschedule Action Scheduler tasks if Action Scheduler is available.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( Constants::HOOK_TRACKING_POLL );
			as_unschedule_all_actions( Constants::HOOK_ASYNC_MANIFEST );
			as_unschedule_all_actions( Constants::HOOK_PROCESS_WEBHOOK );
		}

		// Clear transients.
		delete_transient( Constants::TRANSIENT_TEST_CONN );

		// Preserve settings, order shipment metadata, tracking history, AWBs, and warehouses.
	}
}
