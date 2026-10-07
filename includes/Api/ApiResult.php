<?php
/**
 * Standardized API Result value object.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Api;

/**
 * Class ApiResult
 */
final class ApiResult {

	/**
	 * Success flag.
	 *
	 * @var bool
	 */
	private bool $success;

	/**
	 * HTTP status code.
	 *
	 * @var int
	 */
	private int $status_code;

	/**
	 * Parsed response payload.
	 *
	 * @var array<string, mixed>|list<mixed>
	 */
	private array $data;

	/**
	 * Error message if request failed.
	 *
	 * @var string
	 */
	private string $error_message;

	/**
	 * Error code string.
	 *
	 * @var string
	 */
	private string $error_code;

	/**
	 * Raw body response (sanitized).
	 *
	 * @var string
	 */
	private string $raw_body;

	/**
	 * Constructor.
	 *
	 * @param bool                              $success
	 * @param int                               $status_code
	 * @param array<string, mixed>|list<mixed>  $data
	 * @param string                            $error_message
	 * @param string                            $error_code
	 * @param string                            $raw_body
	 */
	public function __construct(
		bool $success,
		int $status_code,
		array $data = array(),
		string $error_message = '',
		string $error_code = '',
		string $raw_body = ''
	) {
		$this->success       = $success;
		$this->status_code   = $status_code;
		$this->data          = $data;
		$this->error_message = $error_message;
		$this->error_code    = $error_code;
		$this->raw_body      = $raw_body;
	}

	/**
	 * Static factory for successful responses.
	 *
	 * @param int                              $status_code
	 * @param array<string, mixed>|list<mixed> $data
	 * @param string                           $raw_body
	 * @return self
	 */
	public static function success( int $status_code, array $data, string $raw_body = '' ): self {
		return new self( true, $status_code, $data, '', '', $raw_body );
	}

	/**
	 * Static factory for failed responses.
	 *
	 * @param int                              $status_code
	 * @param string                           $error_message
	 * @param string                           $error_code
	 * @param array<string, mixed>|list<mixed> $data
	 * @param string                           $raw_body
	 * @return self
	 */
	public static function failure(
		int $status_code,
		string $error_message,
		string $error_code = 'API_ERROR',
		array $data = array(),
		string $raw_body = ''
	): self {
		return new self( false, $status_code, $data, $error_message, $error_code, $raw_body );
	}

	/**
	 * Check if API call succeeded.
	 *
	 * @return bool
	 */
	public function is_success(): bool {
		return $this->success;
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
	 * Get parsed response data.
	 *
	 * @return array<string, mixed>|list<mixed>
	 */
	public function get_data(): array {
		return $this->data;
	}

	/**
	 * Get error message.
	 *
	 * @return string
	 */
	public function get_error_message(): string {
		return $this->error_message;
	}

	/**
	 * Get error code.
	 *
	 * @return string
	 */
	public function get_error_code(): string {
		return $this->error_code;
	}

	/**
	 * Get raw sanitized response body.
	 *
	 * @return string
	 */
	public function get_raw_body(): string {
		return $this->raw_body;
	}
}
