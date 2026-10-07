<?php
/**
 * Pickup Request Scheduling Service.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Services;

use WPCalibrate\ShippingConnector\Api\DelhiveryClient;
use WPCalibrate\ShippingConnector\Api\ApiResult;
use WPCalibrate\ShippingConnector\Logging\Logger;

/**
 * Class PickupService
 */
class PickupService {

	/**
	 * Delhivery client.
	 *
	 * @var DelhiveryClient
	 */
	private DelhiveryClient $client;

	/**
	 * Warehouse service.
	 *
	 * @var WarehouseService
	 */
	private WarehouseService $warehouse_service;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param DelhiveryClient  $client
	 * @param WarehouseService $warehouse_service
	 * @param Logger           $logger
	 */
	public function __construct(
		DelhiveryClient $client,
		WarehouseService $warehouse_service,
		Logger $logger
	) {
		$this->client            = $client;
		$this->warehouse_service = $warehouse_service;
		$this->logger            = $logger;
	}

	/**
	 * Create a pickup request with Delhivery.
	 *
	 * @param string $warehouse_id
	 * @param string $pickup_date YYYY-MM-DD
	 * @param string $pickup_time HH:MM:SS
	 * @param int    $package_count
	 * @return array{success: bool, pickup_id: string, message: string}
	 */
	public function request_pickup(
		string $warehouse_id,
		string $pickup_date,
		string $pickup_time,
		int $package_count = 1
	): array {
		$warehouse = $this->warehouse_service->get_warehouse( $warehouse_id );
		if ( empty( $warehouse ) ) {
			return array(
				'success'   => false,
				'pickup_id' => '',
				'message'   => __( 'Invalid warehouse specified for pickup.', 'wpcalibrate-shipping-connector' ),
			);
		}

		if ( $package_count < 1 ) {
			return array(
				'success'   => false,
				'pickup_id' => '',
				'message'   => __( 'Package count must be at least 1.', 'wpcalibrate-shipping-connector' ),
			);
		}

		$payload = array(
			'pickup_location'        => (string) $warehouse['name'],
			'pickup_date'            => sanitize_text_field( $pickup_date ),
			'pickup_time'            => sanitize_text_field( $pickup_time ),
			'expected_package_count' => $package_count,
		);

		$response = $this->client->post_json( 'fm/request/new/', $payload );

		if ( ! $response->is_success() ) {
			$err = $response->get_error_message();
			$this->logger->error( "Pickup request failed for {$warehouse['name']}: {$err}" );
			return array(
				'success'   => false,
				'pickup_id' => '',
				'message'   => sprintf( __( 'Pickup request failed: %s', 'wpcalibrate-shipping-connector' ), $err ),
			);
		}

		$data      = $response->get_data();
		$pickup_id = (string) ( $data['pickup_id'] ?? $data['pr_id'] ?? $data['id'] ?? uniqid( 'PR-' ) );

		$this->logger->info( "Pickup scheduled successfully. ID: {$pickup_id} for {$warehouse['name']}" );

		return array(
			'success'   => true,
			'pickup_id' => $pickup_id,
			'message'   => sprintf( __( 'Pickup request scheduled successfully (Reference: %s)', 'wpcalibrate-shipping-connector' ), $pickup_id ),
		);
	}
}
