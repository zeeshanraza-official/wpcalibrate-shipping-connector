<?php
/**
 * Shipping Label & Packing Slip Service.
 *
 * @package WPCalibrate\ShippingConnector
 */

declare(strict_types=1);

namespace WPCalibrate\ShippingConnector\Services;

use WC_Order;
use WPCalibrate\ShippingConnector\Api\DelhiveryClient;
use WPCalibrate\ShippingConnector\Api\ApiResult;
use WPCalibrate\ShippingConnector\Orders\OrderShipmentRepository;
use WPCalibrate\ShippingConnector\Logging\Logger;
use WPCalibrate\ShippingConnector\Support\Constants;

/**
 * Class LabelService
 */
class LabelService {

	/**
	 * Delhivery client.
	 *
	 * @var DelhiveryClient
	 */
	private DelhiveryClient $client;

	/**
	 * Order repository.
	 *
	 * @var OrderShipmentRepository
	 */
	private OrderShipmentRepository $repository;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param DelhiveryClient         $client
	 * @param OrderShipmentRepository $repository
	 * @param Logger                  $logger
	 */
	public function __construct(
		DelhiveryClient $client,
		OrderShipmentRepository $repository,
		Logger $logger
	) {
		$this->client     = $client;
		$this->repository = $repository;
		$this->logger     = $logger;
	}

	/**
	 * Fetch packing slip data from Delhivery API.
	 *
	 * @param string $waybill
	 * @return ApiResult
	 */
	public function fetch_label_data( string $waybill ): ApiResult {
		$clean_waybill = preg_replace( '/[^A-Za-z0-9_-]/', '', $waybill );
		return $this->client->get( 'api/p/packing_slip', array( 'wbns' => $clean_waybill ) );
	}

	/**
	 * Generate printable HTML packing slip with barcode for an order.
	 *
	 * @param WC_Order $order
	 * @return string HTML printable document
	 */
	public function generate_html_label( WC_Order $order ): string {
		$waybill   = $this->repository->get_waybill( $order );
		$wh_id     = (string) $order->get_meta( Constants::META_WAREHOUSE_ID, true );
		$pay_mode  = (string) $order->get_meta( Constants::META_PAYMENT_MODE, true ) ?: 'Prepaid';
		$cod_amt   = (float) $order->get_meta( Constants::META_COD_AMOUNT, true );
		$order_num = $order->get_order_number();

		$dest_name = trim( ( $order->get_shipping_first_name() ?: $order->get_billing_first_name() ) . ' ' . ( $order->get_shipping_last_name() ?: $order->get_billing_last_name() ) );
		$dest_addr = trim( ( $order->get_shipping_address_1() ?: $order->get_billing_address_1() ) . ' ' . ( $order->get_shipping_address_2() ?: $order->get_billing_address_2() ) );
		$dest_city = $order->get_shipping_city() ?: $order->get_billing_city();
		$dest_pin  = $order->get_shipping_postcode() ?: $order->get_billing_postcode();
		$dest_tel  = $order->get_billing_phone();

		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<title><?php echo esc_html( "Delhivery Label - AWB {$waybill}" ); ?></title>
			<style>
				@page { size: 100mm 150mm; margin: 5mm; }
				body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 12px; margin: 0; padding: 0; color: #111; }
				.label-card { border: 2px solid #000; padding: 8px; width: 95mm; box-sizing: border-box; }
				.header { border-bottom: 2px solid #000; padding-bottom: 6px; display: flex; justify-content: space-between; align-items: center; }
				.carrier-brand { font-size: 18px; font-weight: 900; letter-spacing: 1px; }
				.pay-badge { font-size: 14px; font-weight: 800; border: 2px solid #000; padding: 2px 6px; }
				.barcode-box { text-align: center; padding: 10px 0; border-bottom: 1px dashed #000; }
				.awb-text { font-family: monospace; font-size: 16px; font-weight: 700; letter-spacing: 2px; margin-top: 4px; }
				.section { border-bottom: 1px solid #000; padding: 6px 0; }
				.section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #555; }
				.address-text { font-size: 12px; line-height: 1.3; font-weight: 600; margin-top: 2px; }
				.order-meta { display: flex; justify-content: space-between; font-size: 11px; padding: 6px 0; border-bottom: 1px solid #000; }
				.footer { font-size: 9px; text-align: center; color: #666; margin-top: 6px; }
				@media print {
					.no-print { display: none; }
				}
			</style>
		</head>
		<body>
			<div class="no-print" style="margin-bottom: 10px;">
				<button onclick="window.print();" style="padding: 6px 12px; font-size: 14px; font-weight: 600; cursor: pointer;"><?php esc_html_e( 'Print Shipping Label', 'wpcalibrate-shipping-connector' ); ?></button>
			</div>
			<div class="label-card">
				<div class="header">
					<div class="carrier-brand">DELHIVERY B2C</div>
					<div class="pay-badge"><?php echo esc_html( strtoupper( $pay_mode ) ); ?></div>
				</div>

				<div class="barcode-box">
					<!-- CSS/SVG Barcode Representation -->
					<svg width="220" height="45" viewBox="0 0 220 45">
						<rect x="0" y="0" width="220" height="45" fill="#fff" />
						<?php
						// Deterministic visual barcode pattern from waybill digits.
						$bars = str_split( hash( 'crc32b', $waybill ) . $waybill );
						$x = 10;
						foreach ( $bars as $b ) {
							$w = ( hexdec( $b ) % 3 ) + 1;
							echo '<rect x="' . esc_attr( (string) $x ) . '" y="0" width="' . esc_attr( (string) $w ) . '" height="40" fill="#000" />';
							$x += $w + 2;
							if ( $x > 210 ) {
								break;
							}
						}
						?>
					</svg>
					<div class="awb-text"><?php echo esc_html( $waybill ); ?></div>
				</div>

				<div class="section">
					<div class="section-title"><?php esc_html_e( 'Deliver To (Consignee):', 'wpcalibrate-shipping-connector' ); ?></div>
					<div class="address-text">
						<strong><?php echo esc_html( $dest_name ); ?></strong><br>
						<?php echo esc_html( $dest_addr ); ?><br>
						<?php echo esc_html( $dest_city . ' - ' . $dest_pin ); ?><br>
						Phone: <?php echo esc_html( (string) $dest_tel ); ?>
					</div>
				</div>

				<div class="order-meta">
					<div><strong>Order:</strong> #<?php echo esc_html( (string) $order_num ); ?></div>
					<div><strong>Date:</strong> <?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : '' ); ?></div>
					<?php if ( 'COD' === $pay_mode ) : ?>
						<div><strong>Collect COD:</strong> ₹<?php echo esc_html( number_format( $cod_amt, 2 ) ); ?></div>
					<?php endif; ?>
				</div>

				<div class="footer">
					WPCalibrate Shipping Connector • Delhivery Express
				</div>
			</div>
		</body>
		</html>
		<?php
		return (string) ob_get_clean();
	}
}
