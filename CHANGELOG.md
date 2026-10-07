# Changelog - WPCalibrate Shipping Connector

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-10-07

### Added
- **Core Architecture:**
  - Strict PHP 8.2 and PHP 8.3 compatibility with zero deprecated dynamic properties.
  - Custom PSR-4 autoloader with zero production vendor bloat.
  - Centralized constants, options, capabilities, and migrations framework.
  - Single shared WPCalibrate parent menu integration with collision prevention across multiple WPCalibrate plugins.
  - Dedicated isolated submenu `wpcalibrate-shipping-connector` with 10 administrative tabs.
- **Branding:**
  - Centralized branding using official WPCalibrate dark and white iconography.
  - Responsive, WCAG-accessible administrative interface with visible focus rings and RTL support.
- **Delhivery B2C API Client:**
  - Dedicated low-level HTTP client with staging (`staging-express.delhivery.com`) and production (`track.delhivery.com`) endpoints.
  - Header token authentication (`Authorization: Token <key>`) with sensitive token masking.
  - Standardized `ApiResult` value objects and `ApiException` classes.
  - Rate-limit (429), authorization failure (401/403), transport error, and timeout handling.
- **Shipping Rates & Serviceability:**
  - Registered WooCommerce Shipping Zone method `wpcalibrate_delhivery`.
  - Compatible with WooCommerce Cart & Checkout Blocks and classic checkout.
  - Live rate calculation via `/api/kinko/v1/invoice/charges/.json`.
  - PIN code serviceability validation with transient caching.
  - Configurable rate adjustments (fixed / percentage markup), fallback rate quotes, and free shipping thresholds.
  - Package calculation component with automatic unit conversion (kg, grams, cm, inches, lbs).
  - Mapping for WooCommerce Cash on Delivery (COD) payment gateways.
- **Shipment Management:**
  - Manual manifestation directly from WooCommerce order edit screen.
  - Optional automatic manifestation on configurable order status change (e.g. Processing).
  - Idempotency locking to prevent race conditions and duplicate parcel creation.
  - Integration with B2C manifestation endpoint `/api/cmu/create.json`.
  - Post-manifestation updates and cancellation via `/api/p/edit`.
- **Labels & Packing Slips:**
  - Thermal label generator (100x150mm / 4x6 inch) with Code 128 barcodes.
  - Secure AJAX print view with nonce and capability verification.
- **Shipment Tracking:**
  - Batch tracking queries via `/api/v1/packages/json/`.
  - Delhivery carrier status normalization to WooCommerce order statuses.
  - Storefront tracking view on customer My Account order details.
  - Tracking link integration into WooCommerce transactional emails.
- **Webhooks & Background Processing:**
  - Dedicated REST API webhook route `/wp-json/wpcalibrate-shipping/v1/webhook`.
  - High-performance response under 500ms with Action Scheduler async queueing.
  - Deterministic event fingerprinting for duplicate delivery suppression.
  - WooCommerce Action Scheduler integration for recurring tracking sync.
- **Exceptions & Reverse Logistics:**
  - Non-Delivery Report (NDR) actions: Reattempt, Reschedule, and Return to Origin (RTO).
  - Reverse pickup (RVP) shipment manifestation with QC checklist parameters.
- **Warehouses:**
  - Multi-warehouse configuration and default warehouse routing.
  - Remote warehouse registration via `/api/backend/clientwarehouse/create/`.
- **Diagnostics & Tools:**
  - API Connection Test tool.
  - Live PIN code serviceability query tool.
  - Plugin cache purger.
  - WooCommerce logger integration under prefix `wpcalibrate-delhivery` with PII and secret redaction.
- **Licensing:**
  - Provider-agnostic `LicenseManagerInterface` and `NullLicenseManager` implementation with explicit development status notice (zero fake activations).
