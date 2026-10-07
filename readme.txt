=== WPCalibrate Shipping Connector ===
Contributors: wpcalibrate
Tags: woocommerce, shipping, delhivery, logistics, tracking, awb, india, rates
Requires at least: 6.4
Tested up to: 7.1.3
Requires PHP: 8.2
WC requires at least: 8.5
WC tested up to: 11.1.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise WooCommerce shipping integration for Delhivery B2C domestic logistics including live checkout rates, serviceability validation, shipment manifestation, waybills, labels, tracking, webhooks, and NDR management.

== Description ==

WPCalibrate Shipping Connector provides a commercial-grade integration between WooCommerce and Delhivery B2C logistics services across India. Built for speed, reliability, and security, it seamlessly supports High-Performance Order Storage (HPOS), WooCommerce Cart & Checkout Blocks, classic checkout, and Action Scheduler background synchronization.

**Disclaimer:** This plugin is developed and maintained independently by WPCalibrate. Delhivery is a registered trademark of Delhivery Limited. WPCalibrate is not affiliated with, owned by, or officially endorsed by Delhivery Limited. An active Delhivery merchant account and API credentials are required to use this plugin.

= Key Features =

* **Live Checkout Rates:** Calculates real-time Delhivery shipping costs based on origin warehouse, destination PIN code, package weight, dimensions, and shipping mode (Surface or Express).
* **PIN Code Serviceability:** Automatically validates customer postal codes for delivery, Cash on Delivery (COD), and Out of Delivery Area (ODA) status.
* **Shipment Manifestation:** One-click manual shipment creation from the WooCommerce order edit screen or automated manifestation when orders enter designated order statuses (e.g. Processing).
* **Waybill (AWB) Assignment:** Allocates and tracks carrier waybill numbers per order.
* **Printable Shipping Labels:** Generates thermal packing slips (100x150mm / 4x6 inch) complete with Code 128 barcodes directly from the order panel.
* **Shipment Tracking:** Real-time tracking synchronization with detailed scan history; exposes tracking links on customer My Account order screens and WooCommerce transactional emails.
* **Server-to-Server Webhook:** High-performance REST webhook endpoint (`/wp-json/wpcalibrate-shipping/v1/webhook`) acknowledging carrier scan-push updates within 500ms and processing events asynchronously via Action Scheduler.
* **Non-Delivery Report (NDR) Management:** Proactively resolve failed delivery attempts with documented carrier actions: Reattempt delivery, Reschedule to a future date, or Return to Origin (RTO).
* **Reverse Logistics (Returns / RVP):** Create customer return pickups back to the merchant warehouse with optional Quality Check (QC) checklists.
* **Multiple Warehouses:** Manage multiple physical pickup locations with default routing and order-level override.
* **High Performance Order Storage (HPOS):** Fully compatible with WooCommerce Custom Order Tables (HPOS) and legacy post meta.
* **Block Compatibility:** Works seamlessly with WooCommerce Cart & Checkout Blocks and classic shortcode checkouts.

== Installation ==

1. Upload the `wpcalibrate-shipping-connector` folder to your `/wp-content/plugins/` directory, or install the `.zip` file via **Plugins &rarr; Add New &rarr; Upload Plugin**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **WPCalibrate &rarr; Shipping Connector** in your WordPress admin menu.
4. On the **API Connection** tab, enter your Delhivery API Token, Client Identifier, and select your environment (Staging or Production).
5. Click **Run Connection Test** to verify connectivity with Delhivery logistics servers.
6. Navigate to the **Warehouses** tab and configure your primary pickup warehouse. The warehouse name must match your registered pickup location in Delhivery One.
7. Go to **WooCommerce &rarr; Settings &rarr; Shipping &rarr; Shipping Zones**, edit or add an Indian zone, and add the **Delhivery Shipping** method.
8. (Optional) Provide the Webhook URL and Secret shown on the **Tracking & Webhooks** tab to your Delhivery representative to enable real-time scan push.

== Frequently Asked Questions ==

= Does this plugin require an active Delhivery merchant account? =
Yes. You must have an active merchant account with Delhivery and generate an API key / token from the Delhivery One portal or your Delhivery account manager.

= Is Delhivery B2B freight or international shipping supported? =
Version 1.0.0 is dedicated to Delhivery B2C domestic logistics within India. Freight and international services are planned for future major releases.

= Is HPOS (High-Performance Order Storage) supported? =
Yes. The plugin is 100% HPOS-compatible and declares official WooCommerce compatibility flags. All order operations utilize WooCommerce CRUD APIs.

= How are shipping weights and dimensions calculated? =
The plugin calculates total actual weight from individual cart items using WooCommerce's unit conversion utilities (`wc_get_weight()`). If items lack weights or dimensions, merchant-configured fallback defaults are applied.

= What happens during plugin uninstallation? =
By default, all shipment records, waybills, and tracking history are preserved as vital accounting records. You can optionally choose to purge settings on uninstall by toggling the retention setting under **Logs & Tools**.

== Data Sharing & Privacy Notice ==

To calculate shipping rates and manifest parcels, this plugin transmits specific order details to Delhivery servers:
* Recipient name, delivery address, postal code, and contact phone number.
* Total package weight, calculated parcel dimensions, item descriptions, and invoice values.
* Payment mode (Prepaid or Cash on Delivery amount).

No customer passwords, credit card numbers, or unrelated store metadata are ever transmitted to Delhivery. Sensitive API tokens and credentials are automatically redacted before logging.

== Changelog ==

= 1.0.0 =
* Initial commercial release.
* Added live carrier rate calculation with fixed/percentage adjustments and free shipping thresholds.
* Added 6-digit Indian PIN code serviceability validation with transient caching.
* Added manual and automatic shipment manifestation via `api/cmu/create.json`.
* Added AWB tracking, background sync, and customer order view display.
* Added printable packing slip generator with Code 128 barcodes.
* Added REST webhook handler for Delhivery scan-push events with Action Scheduler queueing.
* Added NDR management (Reattempt, Reschedule, RTO).
* Added Reverse Pickup (RVP) manifestation with QC checklist support.
* Added multi-warehouse management and order-level warehouse selection.
* Declared full compatibility with WooCommerce HPOS and Cart & Checkout Blocks.
