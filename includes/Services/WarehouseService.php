<?php
/**
 * Warehouse and pickup location management service.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Services;

use WPCalibrate\ShippingConnector\Api\DelhiveryClient;
use WPCalibrate\ShippingConnector\Api\ApiResult;
use WPCalibrate\ShippingConnector\Support\Constants;
use WPCalibrate\ShippingConnector\Logging\Logger;

/**
 * Class WarehouseService
 */
class WarehouseService {

	/**
	 * Delhivery client.
	 *
	 * @var DelhiveryClient
	 */
	private DelhiveryClient $client;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param DelhiveryClient $client
	 * @param Logger          $logger
	 */
	public function __construct( DelhiveryClient $client, Logger $logger ) {
		$this->client = $client;
		$this->logger = $logger;
	}

	/**
	 * Get all configured warehouses.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_warehouses(): array {
		$warehouses = get_option( Constants::OPTION_WAREHOUSES, array() );
		return is_array( $warehouses ) ? $warehouses : array();
	}

	/**
	 * Get a specific warehouse by ID.
	 *
	 * @param string $warehouse_id
	 * @return array<string, mixed>|null
	 */
	public function get_warehouse( string $warehouse_id ): ?array {
		$warehouses = $this->get_warehouses();
		return $warehouses[ $warehouse_id ] ?? null;
	}

	/**
	 * Get the default warehouse.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_default_warehouse(): ?array {
		$warehouses = $this->get_warehouses();

		foreach ( $warehouses as $wh ) {
			if ( ! empty( $wh['is_default'] ) ) {
				return $wh;
			}
		}

		if ( ! empty( $warehouses ) ) {
			return reset( $warehouses );
		}

		return null;
	}

	/**
	 * Save or update a warehouse locally.
	 *
	 * @param array<string, mixed> $data
	 * @return bool
	 */
	public function save_warehouse( array $data ): bool {
		$id = sanitize_key( (string) ( $data['id'] ?? '' ) );
		if ( empty( $id ) ) {
			$id = sanitize_key( (string) ( $data['name'] ?? '' ) );
		}

		if ( empty( $id ) ) {
			return false;
		}

		$warehouses = $this->get_warehouses();

		$record = array(
			'id'             => $id,
			'name'           => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'address'        => sanitize_textarea_field( (string) ( $data['address'] ?? '' ) ),
			'pin'            => preg_replace( '/\D/', '', (string) ( $data['pin'] ?? '' ) ),
			'city'           => sanitize_text_field( (string) ( $data['city'] ?? '' ) ),
			'state'          => sanitize_text_field( (string) ( $data['state'] ?? '' ) ),
			'country'        => 'India',
			'phone'          => sanitize_text_field( (string) ( $data['phone'] ?? '' ) ),
			'return_address' => sanitize_textarea_field( (string) ( $data['return_address'] ?? $data['address'] ?? '' ) ),
			'return_pin'     => preg_replace( '/\D/', '', (string) ( $data['return_pin'] ?? $data['pin'] ?? '' ) ),
			'return_city'    => sanitize_text_field( (string) ( $data['return_city'] ?? $data['city'] ?? '' ) ),
			'return_state'   => sanitize_text_field( (string) ( $data['return_state'] ?? $data['state'] ?? '' ) ),
			'return_country' => 'India',
			'is_default'     => ! empty( $data['is_default'] ),
		);

		// If setting as default, clear others.
		if ( $record['is_default'] ) {
			foreach ( $warehouses as &$wh ) {
				$wh['is_default'] = false;
			}
			unset( $wh );
		}

		$warehouses[ $id ] = $record;

		// Ensure at least one default.
		$has_default = false;
		foreach ( $warehouses as $wh ) {
			if ( ! empty( $wh['is_default'] ) ) {
				$has_default = true;
				break;
			}
		}
		if ( ! $has_default && ! empty( $warehouses ) ) {
			$first_key                         = array_key_first( $warehouses );
			$warehouses[ $first_key ]['is_default'] = true;
		}

		return update_option( Constants::OPTION_WAREHOUSES, $warehouses, 'no' );
	}

	/**
	 * Delete a warehouse locally.
	 *
	 * @param string $warehouse_id
	 * @return bool
	 */
	public function delete_warehouse( string $warehouse_id ): bool {
		$warehouses = $this->get_warehouses();
		if ( ! isset( $warehouses[ $warehouse_id ] ) ) {
			return false;
		}

		$was_default = ! empty( $warehouses[ $warehouse_id ]['is_default'] );
		unset( $warehouses[ $warehouse_id ] );

		if ( $was_default && ! empty( $warehouses ) ) {
			$first_key                         = array_key_first( $warehouses );
			$warehouses[ $first_key ]['is_default'] = true;
		}

		return update_option( Constants::OPTION_WAREHOUSES, $warehouses, 'no' );
	}

	/**
	 * Register or sync a warehouse with Delhivery Client Warehouse API.
	 *
	 * @param array<string, mixed> $data
	 * @return ApiResult
	 */
	public function register_with_carrier( array $data ): ApiResult {
		$payload = array(
			'name'           => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'address'        => sanitize_textarea_field( (string) ( $data['address'] ?? '' ) ),
			'pin'            => preg_replace( '/\D/', '', (string) ( $data['pin'] ?? '' ) ),
			'phone'          => sanitize_text_field( (string) ( $data['phone'] ?? '' ) ),
			'city'           => sanitize_text_field( (string) ( $data['city'] ?? '' ) ),
			'state'          => sanitize_text_field( (string) ( $data['state'] ?? '' ) ),
			'country'        => 'India',
			'return_address' => sanitize_textarea_field( (string) ( $data['return_address'] ?? $data['address'] ?? '' ) ),
			'return_pin'     => preg_replace( '/\D/', '', (string) ( $data['return_pin'] ?? $data['pin'] ?? '' ) ),
			'return_city'    => sanitize_text_field( (string) ( $data['return_city'] ?? $data['city'] ?? '' ) ),
			'return_state'   => sanitize_text_field( (string) ( $data['return_state'] ?? $data['state'] ?? '' ) ),
			'return_country' => 'India',
		);

		return $this->client->post_json( 'api/backend/clientwarehouse/create/', $payload );
	}
}
