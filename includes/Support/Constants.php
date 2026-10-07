<?php
/**
 * Plugin Constants definition.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Support;

/**
 * Class Constants
 */
final class Constants {

	public const PLUGIN_VERSION     = '1.0.0';
	public const DB_VERSION         = '1.0.0';
	public const MIN_PHP_VERSION    = '8.2.0';
	public const MIN_WP_VERSION     = '6.4.0';
	public const MIN_WC_VERSION     = '8.5.0';

	public const TEXT_DOMAIN        = 'wpcalibrate-shipping-connector';
	public const SLUG               = 'wpcalibrate-shipping-connector';
	public const PARENT_MENU_SLUG   = 'wpcalibrate';
	public const SUBMENU_SLUG       = 'wpcalibrate-shipping-connector';

	// Option Keys
	public const OPTION_SETTINGS    = 'wpcalibrate_shipping_connector_settings';
	public const OPTION_DB_VERSION  = 'wpcalibrate_shipping_connector_db_version';
	public const OPTION_WAREHOUSES  = 'wpcalibrate_shipping_connector_warehouses';
	public const OPTION_LAST_SYNC   = 'wpcalibrate_shipping_connector_last_sync';

	// Order Meta Keys (HPOS and Post meta compatible)
	public const META_WAYBILL              = '_wpcalibrate_delhivery_waybill';
	public const META_SHIPMENT_STATUS      = '_wpcalibrate_delhivery_status';
	public const META_STATUS_CODE          = '_wpcalibrate_delhivery_status_code';
	public const META_STATUS_DATE          = '_wpcalibrate_delhivery_status_date';
	public const META_MANIFESTED_AT        = '_wpcalibrate_delhivery_manifested_at';
	public const META_WAREHOUSE_ID         = '_wpcalibrate_delhivery_warehouse_id';
	public const META_PAYMENT_MODE         = '_wpcalibrate_delhivery_payment_mode';
	public const META_COD_AMOUNT           = '_wpcalibrate_delhivery_cod_amount';
	public const META_LAST_TRACKING_SYNC   = '_wpcalibrate_delhivery_last_tracking_sync';
	public const META_TRACKING_HISTORY     = '_wpcalibrate_delhivery_tracking_history';
	public const META_LABEL_DOWNLOAD_URL   = '_wpcalibrate_delhivery_label_url';
	public const META_PICKUP_REQUEST_ID    = '_wpcalibrate_delhivery_pickup_req_id';
	public const META_PICKUP_SCHEDULED_AT  = '_wpcalibrate_delhivery_pickup_scheduled_at';
	public const META_CANCELLATION_STATUS  = '_wpcalibrate_delhivery_cancelled';
	public const META_CANCELLED_AT         = '_wpcalibrate_delhivery_cancelled_at';
	public const META_RETURN_WAYBILL       = '_wpcalibrate_delhivery_return_waybill';
	public const META_RETURN_STATUS        = '_wpcalibrate_delhivery_return_status';
	public const META_NDR_ACTION           = '_wpcalibrate_delhivery_ndr_action';
	public const META_NDR_HISTORY          = '_wpcalibrate_delhivery_ndr_history';
	public const META_LOCK                 = '_wpcalibrate_delhivery_lock';

	// Transients
	public const TRANSIENT_SERVICEABILITY  = 'wpcalibrate_delhivery_srv_';
	public const TRANSIENT_RATE            = 'wpcalibrate_delhivery_rate_';
	public const TRANSIENT_TEST_CONN       = 'wpcalibrate_delhivery_test_conn';

	// Action Scheduler Hooks
	public const HOOK_ASYNC_MANIFEST       = 'wpcalibrate_shipping_async_manifest';
	public const HOOK_TRACKING_POLL        = 'wpcalibrate_shipping_tracking_poll';
	public const HOOK_PROCESS_WEBHOOK      = 'wpcalibrate_shipping_process_webhook';

	// REST API Routes
	public const REST_NAMESPACE            = 'wpcalibrate-shipping/v1';
	public const REST_ROUTE_WEBHOOK        = 'webhook';

	// Capabilities
	public const CAP_MANAGE_SETTINGS       = 'manage_options';
	public const CAP_MANAGE_OPERATIONS     = 'manage_woocommerce';

	// Environments
	public const ENV_PRODUCTION            = 'production';
	public const ENV_STAGING               = 'staging';

	// Base URLs
	public const URL_PROD_API              = 'https://track.delhivery.com';
	public const URL_STAGING_API           = 'https://staging-express.delhivery.com';
	public const URL_TRACKING_PUBLIC       = 'https://www.delhivery.com/track/package/';

	// Contact & Support Info
	public const SUPPORT_EMAIL             = 'support@wpcalibrate.com';
	public const SUPPORT_WEBSITE           = 'https://wpcalibrate.com';
	public const SUPPORT_MARKETPLACE       = 'https://marketplace.wpcalibrate.com/';
	public const SUPPORT_PHONE             = '+447474795976';
}
