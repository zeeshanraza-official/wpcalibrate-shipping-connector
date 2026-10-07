<?php
/**
 * HPOS-compatible Order Admin Panel / Metabox.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Admin;

use WC_Order;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WPCalibrate\ShippingConnector\Support\Constants;
use WPCalibrate\ShippingConnector\Support\Helpers;
use WPCalibrate\ShippingConnector\Core\Capabilities;
use WPCalibrate\ShippingConnector\Orders\OrderShipmentRepository;
use WPCalibrate\ShippingConnector\Services\ShipmentService;
use WPCalibrate\ShippingConnector\Services\TrackingService;
use WPCalibrate\ShippingConnector\Services\LabelService;
use WPCalibrate\ShippingConnector\Services\WarehouseService;
use WPCalibrate\ShippingConnector\Services\NdrService;
use WPCalibrate\ShippingConnector\Services\ReturnService;

/**
 * Class OrderPanel
 */
class OrderPanel {

	/**
	 * Repository.
	 *
	 * @var OrderShipmentRepository
	 */
	private OrderShipmentRepository $repository;

	/**
	 * Shipment service.
	 *
	 * @var ShipmentService
	 */
	private ShipmentService $shipment_service;

	/**
	 * Tracking service.
	 *
	 * @var TrackingService
	 */
	private TrackingService $tracking_service;

	/**
	 * Label service.
	 *
	 * @var LabelService
	 */
	private LabelService $label_service;

	/**
	 * Warehouse service.
	 *
	 * @var WarehouseService
	 */
	private WarehouseService $warehouse_service;

	/**
	 * NDR service.
	 *
	 * @var NdrService
	 */
	private NdrService $ndr_service;

	/**
	 * Return service.
	 *
	 * @var ReturnService
	 */
	private ReturnService $return_service;

	/**
	 * Constructor.
	 *
	 * @param OrderShipmentRepository $repository
	 * @param ShipmentService         $shipment_service
	 * @param TrackingService         $tracking_service
	 * @param LabelService            $label_service
	 * @param WarehouseService        $warehouse_service
	 * @param NdrService              $ndr_service
	 * @param ReturnService           $return_service
	 */
	public function __construct(
		OrderShipmentRepository $repository,
		ShipmentService $shipment_service,
		TrackingService $tracking_service,
		LabelService $label_service,
		WarehouseService $warehouse_service,
		NdrService $ndr_service,
		ReturnService $return_service
	) {
		$this->repository        = $repository;
		$this->shipment_service  = $shipment_service;
		$this->tracking_service  = $tracking_service;
		$this->label_service     = $label_service;
		$this->warehouse_service = $warehouse_service;
		$this->ndr_service       = $ndr_service;
		$this->return_service    = $return_service;
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'admin_init', array( $this, 'process_order_action' ) );
		add_action( 'wp_ajax_wpcalibrate_print_label', array( $this, 'handle_print_label' ) );
	}

	/**
	 * AJAX handler for printing shipping labels.
	 *
	 * @return void
	 */
	public function handle_print_label(): void {
		if ( ! Capabilities::can_manage_operations() ) {
			wp_die( esc_html__( 'Unauthorized user.', 'wpcalibrate-shipping-connector' ) );
		}

		$order_id = (int) ( $_GET['order_id'] ?? 0 );
		check_admin_referer( 'wpcalibrate_print_label_' . $order_id );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_die( esc_html__( 'Order not found.', 'wpcalibrate-shipping-connector' ) );
		}

		echo $this->label_service->generate_html_label( $order );
		exit;
	}

	/**
	 * Register HPOS and legacy order metaboxes.
	 *
	 * @return void
	 */
	public function register_metabox(): void {
		$screen = class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled()
			? wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';

		add_meta_box(
			'wpcalibrate_delhivery_order_panel',
			__( 'WPCalibrate: Delhivery Logistics', 'wpcalibrate-shipping-connector' ),
			array( $this, 'render_metabox' ),
			$screen,
			'side',
			'high'
		);
	}

	/**
	 * Process order actions (manifest, tracking, cancel, label, NDR, return).
	 *
	 * @return void
	 */
	public function process_order_action(): void {
		if ( ! isset( $_POST['wpcalibrate_order_action'] ) || ! isset( $_POST['order_id'] ) ) {
			return;
		}

		if ( ! Capabilities::can_manage_operations() ) {
			wp_die( esc_html__( 'Unauthorized user.', 'wpcalibrate-shipping-connector' ) );
		}

		$order_id = (int) $_POST['order_id'];
		check_admin_referer( 'wpcalibrate_order_action_' . $order_id, 'wpcalibrate_order_nonce' );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['wpcalibrate_order_action'] ) );

		switch ( $action ) {
			case 'create_shipment':
				$warehouse_id = sanitize_key( wp_unslash( $_POST['selected_warehouse'] ?? '' ) );
				$res = $this->shipment_service->create_shipment( $order, $warehouse_id );
				set_transient( 'wpcalibrate_order_notice_' . $order_id, array( 'type' => $res['success'] ? 'success' : 'error', 'msg' => $res['message'] ), 30 );
				break;

			case 'refresh_tracking':
				$res = $this->tracking_service->refresh_order_tracking( $order );
				set_transient( 'wpcalibrate_order_notice_' . $order_id, array( 'type' => $res['success'] ? 'success' : 'error', 'msg' => $res['message'] ), 30 );
				break;

			case 'cancel_shipment':
				$reason = sanitize_text_field( wp_unslash( $_POST['cancellation_reason'] ?? '' ) );
				$res = $this->shipment_service->cancel_shipment( $order, $reason );
				set_transient( 'wpcalibrate_order_notice_' . $order_id, array( 'type' => $res['success'] ? 'success' : 'error', 'msg' => $res['message'] ), 30 );
				break;

			case 'submit_ndr':
				$ndr_action = sanitize_text_field( wp_unslash( $_POST['ndr_action_type'] ?? '' ) );
				$date       = sanitize_text_field( wp_unslash( $_POST['ndr_reschedule_date'] ?? '' ) );
				$remarks    = sanitize_text_field( wp_unslash( $_POST['ndr_remarks'] ?? '' ) );
				$res = $this->ndr_service->submit_ndr_action( $order, $ndr_action, $date, $remarks );
				set_transient( 'wpcalibrate_order_notice_' . $order_id, array( 'type' => $res['success'] ? 'success' : 'error', 'msg' => $res['message'] ), 30 );
				break;

			case 'create_return':
				$warehouse_id = sanitize_key( wp_unslash( $_POST['return_warehouse'] ?? '' ) );
				$res = $this->return_service->create_return_shipment( $order, $warehouse_id );
				set_transient( 'wpcalibrate_order_notice_' . $order_id, array( 'type' => $res['success'] ? 'success' : 'error', 'msg' => $res['message'] ), 30 );
				break;
		}

		wp_safe_redirect( wp_get_referer() ?: admin_url( 'post.php?post=' . $order_id . '&action=edit' ) );
		exit;
	}

	/**
	 * Render order metabox content.
	 *
	 * @param mixed $post_or_order_object
	 * @return void
	 */
	public function render_metabox( mixed $post_or_order_object ): void {
		$order = $post_or_order_object instanceof WC_Order
			? $post_or_order_object
			: wc_get_order( $post_or_order_object->ID ?? 0 );

		if ( ! $order ) {
			echo '<p>' . esc_html__( 'Unable to load order details.', 'wpcalibrate-shipping-connector' ) . '</p>';
			return;
		}

		$order_id   = $order->get_id();
		$waybill    = $this->repository->get_waybill( $order );
		$is_manifested = $this->repository->is_manifested( $order );
		$is_cancelled  = $this->repository->is_cancelled( $order );
		$status_text   = (string) $order->get_meta( Constants::META_SHIPMENT_STATUS, true ) ?: 'Unmanifested';
		$warehouses    = $this->warehouse_service->get_warehouses();
		$selected_wh   = (string) $order->get_meta( Constants::META_WAREHOUSE_ID, true );
		$notice        = get_transient( 'wpcalibrate_order_notice_' . $order_id );

		if ( $notice ) {
			delete_transient( 'wpcalibrate_order_notice_' . $order_id );
		}

		include dirname( dirname( __DIR__ ) ) . '/templates/admin/order-metabox.php';
	}
}
