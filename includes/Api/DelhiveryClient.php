<?php
/**
 * Low-level HTTP Client for Delhivery Express / B2C APIs.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Api;

use WPCalibrate\ShippingConnector\Logging\Logger;
use WPCalibrate\ShippingConnector\Support\Constants;
use WPCalibrate\ShippingConnector\Support\Helpers;

/**
 * Class DelhiveryClient
 */
class DelhiveryClient {

	/**
	 * Configured environment.
	 *
	 * @var string
	 */
	private string $environment;

	/**
	 * API Authentication Token.
	 *
	 * @var string
	 */
	private string $api_token;

	/**
	 * Client / Account Name.
	 *
	 * @var string
	 */
	private string $client_name;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param string $environment Environment ('production' or 'staging').
	 * @param string $api_token   Delhivery API key.
	 * @param string $client_name Delhivery Client name.
	 * @param Logger $logger      Logger instance.
	 */
	public function __construct( string $environment, string $api_token, string $client_name, Logger $logger ) {
		$this->environment = $environment === Constants::ENV_PRODUCTION ? Constants::ENV_PRODUCTION : Constants::ENV_STAGING;
		$this->api_token   = trim( $api_token );
		$this->client_name = trim( $client_name );
		$this->logger      = $logger;
	}

	/**
	 * Update credentials dynamically.
	 *
	 * @param string $environment
	 * @param string $api_token
	 * @param string $client_name
	 * @return void
	 */
	public function set_credentials( string $environment, string $api_token, string $client_name ): void {
		$this->environment = $environment === Constants::ENV_PRODUCTION ? Constants::ENV_PRODUCTION : Constants::ENV_STAGING;
		$this->api_token   = trim( $api_token );
		$this->client_name = trim( $client_name );
	}

	/**
	 * Check if client has required token configured.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return ! empty( $this->api_token );
	}

	/**
	 * Get current base URL.
	 *
	 * @return string
	 */
	public function get_base_url(): string {
		$url = $this->environment === Constants::ENV_PRODUCTION ? Constants::URL_PROD_API : Constants::URL_STAGING_API;
		return (string) apply_filters( 'wpcalibrate_shipping_api_base_url', $url, $this->environment );
	}

	/**
	 * Get request timeout in seconds.
	 *
	 * @return int
	 */
	public function get_timeout(): int {
		return (int) apply_filters( 'wpcalibrate_shipping_api_timeout', 25 );
	}

	/**
	 * Execute a GET request.
	 *
	 * @param string               $path
	 * @param array<string, mixed> $query_params
	 * @return ApiResult
	 */
	public function get( string $path, array $query_params = array() ): ApiResult {
		$url = $this->build_url( $path );
		if ( ! empty( $query_params ) ) {
			$url = add_query_arg( $query_params, $url );
		}

		$args = array(
			'method'  => 'GET',
			'timeout' => $this->get_timeout(),
			'headers' => $this->build_headers( 'application/json' ),
		);

		return $this->execute( $url, $args, 'GET', $path );
	}

	/**
	 * Execute a POST request with URL-encoded form data (e.g. for api/cmu/create.json).
	 *
	 * @param string               $path
	 * @param array<string, mixed> $form_data
	 * @return ApiResult
	 */
	public function post_form( string $path, array $form_data = array() ): ApiResult {
		$url  = $this->build_url( $path );
		$args = array(
			'method'  => 'POST',
			'timeout' => $this->get_timeout(),
			'headers' => $this->build_headers( 'application/x-www-form-urlencoded' ),
			'body'    => http_build_query( $form_data ),
		);

		return $this->execute( $url, $args, 'POST_FORM', $path );
	}

	/**
	 * Execute a POST request with raw JSON body (e.g. for edit, pickup, warehouse).
	 *
	 * @param string               $path
	 * @param array<string, mixed> $json_data
	 * @return ApiResult
	 */
	public function post_json( string $path, array $json_data = array() ): ApiResult {
		$url  = $this->build_url( $path );
		$args = array(
			'method'  => 'POST',
			'timeout' => $this->get_timeout(),
			'headers' => $this->build_headers( 'application/json' ),
			'body'    => wp_json_encode( $json_data ),
		);

		return $this->execute( $url, $args, 'POST_JSON', $path );
	}

	/**
	 * Build full URL for an endpoint path.
	 *
	 * @param string $path
	 * @return string
	 */
	private function build_url( string $path ): string {
		$base = rtrim( $this->get_base_url(), '/' );
		$sub  = '/' . ltrim( $path, '/' );
		return $base . $sub;
	}

	/**
	 * Build standard headers with authentication token.
	 *
	 * @param string $content_type
	 * @return array<string, string>
	 */
	private function build_headers( string $content_type ): array {
		$headers = array(
			'Accept'       => 'application/json',
			'Content-Type' => $content_type,
			'User-Agent'   => 'WPCalibrate-Shipping-Connector/' . Constants::PLUGIN_VERSION . '; ' . home_url(),
		);

		if ( ! empty( $this->api_token ) ) {
			$headers['Authorization'] = 'Token ' . $this->api_token;
		}

		return apply_filters( 'wpcalibrate_shipping_api_headers', $headers, $this->environment );
	}

	/**
	 * Execute HTTP request and normalize the response.
	 *
	 * @param string               $url
	 * @param array<string, mixed> $args
	 * @param string               $op_name
	 * @param string               $path
	 * @return ApiResult
	 */
	private function execute( string $url, array $args, string $op_name, string $path ): ApiResult {
		if ( ! $this->is_configured() ) {
			$this->logger->error( 'Delhivery API call aborted: API token is not configured.', array( 'path' => $path ) );
			return ApiResult::failure( 401, __( 'Delhivery API token is not configured.', 'wpcalibrate-shipping-connector' ), 'AUTH_MISSING' );
		}

		$start_time = microtime( true );
		$response   = wp_remote_request( $url, $args );
		$duration   = round( ( microtime( true ) - $start_time ) * 1000, 2 );

		// Check for WordPress HTTP transport failure (cURL errors, DNS failure, timeout).
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->logger->error(
				"Delhivery HTTP transport failure: {$error_message}",
				array(
					'path'     => $path,
					'duration' => "{$duration}ms",
				)
			);
			return ApiResult::failure( 0, $error_message, 'HTTP_TRANSPORT_ERROR' );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$raw_body    = (string) wp_remote_retrieve_body( $response );

		$this->logger->debug(
			"Delhivery API response received: HTTP {$status_code}",
			array(
				'path'     => $path,
				'status'   => $status_code,
				'duration' => "{$duration}ms",
			)
		);

		// Handle 401/403 Authentication failures.
		if ( 401 === $status_code || 403 === $status_code ) {
			$msg = __( 'Delhivery authentication failed. Please verify your API token.', 'wpcalibrate-shipping-connector' );
			$this->logger->error( "Delhivery Auth Failed ({$status_code})", array( 'path' => $path ) );
			return ApiResult::failure( $status_code, $msg, 'AUTH_FAILED', array(), $raw_body );
		}

		// Handle 429 Rate Limiting.
		if ( 429 === $status_code ) {
			$msg = __( 'Delhivery API rate limit exceeded. Please retry after a brief delay.', 'wpcalibrate-shipping-connector' );
			$this->logger->error( 'Delhivery rate limit (429) hit', array( 'path' => $path ) );
			return ApiResult::failure( $status_code, $msg, 'RATE_LIMIT_EXCEEDED', array(), $raw_body );
		}

		// Decode JSON payload.
		$decoded = json_decode( $raw_body, true );

		if ( null === $decoded && ! empty( $raw_body ) ) {
			// Some endpoints might return plain text or error strings.
			$this->logger->error(
				'Delhivery API returned non-JSON response',
				array(
					'path'        => $path,
					'status_code' => $status_code,
					'sample'      => substr( $raw_body, 0, 200 ),
				)
			);
			return ApiResult::failure( $status_code, __( 'Invalid response format received from carrier API.', 'wpcalibrate-shipping-connector' ), 'INVALID_JSON', array(), $raw_body );
		}

		$parsed_data = is_array( $decoded ) ? $decoded : array();

		// Check for HTTP 4xx or 5xx failures.
		if ( $status_code >= 400 ) {
			$error_message = $this->extract_error_message( $parsed_data, $status_code );
			return ApiResult::failure( $status_code, $error_message, 'HTTP_' . $status_code, $parsed_data, $raw_body );
		}

		// Success.
		return ApiResult::success( $status_code, $parsed_data, $raw_body );
	}

	/**
	 * Extract human-readable error from carrier error payload.
	 *
	 * @param array<string, mixed> $data
	 * @param int                  $status_code
	 * @return string
	 */
	private function extract_error_message( array $data, int $status_code ): string {
		if ( ! empty( $data['error'] ) && is_string( $data['error'] ) ) {
			return $data['error'];
		}
		if ( ! empty( $data['message'] ) && is_string( $data['message'] ) ) {
			return $data['message'];
		}
		if ( ! empty( $data['detail'] ) && is_string( $data['detail'] ) ) {
			return $data['detail'];
		}
		if ( ! empty( $data['rmk'] ) && is_string( $data['rmk'] ) ) {
			return $data['rmk'];
		}

		return sprintf(
			/* translators: %d: HTTP status code */
			__( 'Carrier request failed with status code %d', 'wpcalibrate-shipping-connector' ),
			$status_code
		);
	}
}
