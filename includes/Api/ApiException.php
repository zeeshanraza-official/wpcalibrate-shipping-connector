<?php
/**
 * API Exception.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Api;

use Exception;

/**
 * Class ApiException
 */
class ApiException extends Exception {

	/**
	 * HTTP status code.
	 *
	 * @var int
	 */
	private int $status_code;

	/**
	 * Error code string.
	 *
	 * @var string
	 */
	private string $error_code;

	/**
	 * Constructor.
	 *
	 * @param string $message
	 * @param int    $status_code
	 * @param string $error_code
	 */
	public function __construct( string $message, int $status_code = 0, string $error_code = 'API_ERROR' ) {
		parent::__construct( $message, $status_code );
		$this->status_code = $status_code;
		$this->error_code  = $error_code;
	}

	/**
	 * Get HTTP status code.
	 *
	 * @return int
	 */
	public function get_status_code(): int {
		return $this->status_code;
	}

	/**
	 * Get error code.
	 *
	 * @return string
	 */
	public function get_error_code(): string {
		return $this->error_code;
	}
}
