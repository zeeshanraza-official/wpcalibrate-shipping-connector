<?php
/**
 * REST API Webhook Controller for Delhivery Scan-Push events.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Webhooks;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WPCalibrate\ShippingConnector\Services\WebhookService;
use WPCalibrate\ShippingConnector\Logging\Logger;
use WPCalibrate\ShippingConnector\Support\Constants;

/**
 * Class WebhookController
 */
class WebhookController extends WP_REST_Controller {

	/**
	 * Webhook service.
	 *
	 * @var WebhookService
	 */
	private WebhookService $service;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param WebhookService $service
	 * @param Logger         $logger
	 */
	public function __construct( WebhookService $service, Logger $logger ) {
		$this->namespace = 'wpcalibrate-shipping/v1';
		$this->rest_base = 'webhook';
		$this->service   = $service;
		$this->logger    = $logger;
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_webhook' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);
	}

	/**
	 * Permission callback verifying webhook request headers.
	 *
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	public function check_permissions( WP_REST_Request $request ) {
		$settings        = get_option( Constants::OPTION_SETTINGS, array() );
		$expected_secret = (string) ( $settings['webhook_secret'] ?? '' );

		// If no secret configured, reject requests.
		if ( empty( $expected_secret ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Webhook endpoint secret is not configured.', 'wpcalibrate-shipping-connector' ), array( 'status' => 403 ) );
		}

		// Check X-Delhivery-Secret, Authorization, or secret query arg.
		$received_header = $request->get_header( 'x-delhivery-secret' )
			?: $request->get_header( 'x-webhook-secret' )
			?: $request->get_param( 'secret' );

		if ( empty( $received_header ) ) {
			// Check bearer authorization header.
			$auth_header = $request->get_header( 'authorization' );
			if ( ! empty( $auth_header ) && str_starts_with( $auth_header, 'Bearer ' ) ) {
				$received_header = substr( $auth_header, 7 );
			}
		}

		if ( empty( $received_header ) || ! hash_equals( $expected_secret, (string) $received_header ) ) {
			$this->logger->error( 'Unauthorized webhook attempt rejected.' );
			return new WP_Error( 'rest_forbidden', __( 'Invalid webhook authentication secret.', 'wpcalibrate-shipping-connector' ), array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Handle incoming webhook payload.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function handle_webhook( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();

		if ( empty( $body ) || ! is_array( $body ) ) {
			return new WP_REST_Response( array( 'status' => 'error', 'message' => 'Malformed JSON payload.' ), 400 );
		}

		// Fast response: Queue into Action Scheduler if available to return HTTP 200 within 500ms.
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action(
				Constants::HOOK_PROCESS_WEBHOOK,
				array( 'payload' => $body ),
				'wpcalibrate-shipping'
			);
		} else {
			// Fallback: process immediately.
			$this->service->process_event( $body );
		}

		return new WP_REST_Response(
			array(
				'status'  => 'success',
				'message' => 'Event acknowledged.',
			),
			200
		);
	}
}
