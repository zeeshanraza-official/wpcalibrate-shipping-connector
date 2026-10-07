# WPCalibrate Shipping Connector (Delhivery B2C)

[![WordPress 6.4+](https://img.shields.io/badge/WordPress-6.4%2B-blue.svg)](https://wordpress.org)
[![WooCommerce 8.5+](https://img.shields.io/badge/WooCommerce-8.5%2B-purple.svg)](https://woocommerce.com)
[![PHP 8.2 & 8.3](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777bb4.svg)](https://php.net)
[![HPOS Certified](https://img.shields.io/badge/HPOS-Certified-success.svg)](https://woocommerce.com)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](LICENSE)

An enterprise-grade, high-performance WooCommerce shipping integration connecting your online store directly with **Delhivery Domestic Logistics** (B2C Express India). 

Deliver live checkout shipping rates, instant pin code serviceability checks, 1-click waybill (AWB) generation, standard 4×6 inch thermal shipping labels, automated courier pickup requests, real-time tracking, Non-Delivery Report (NDR) exception handling, and customer reverse returns — directly inside WordPress without middleman fees.

---

## Table of Contents

- [About](#about)
- [Key Features](#key-features)
- [Architecture & Shipment Lifecycle](#architecture--shipment-lifecycle)
- [Requirements](#requirements)
- [Installation](#installation)
- [Dashboard Updates via GitHub](#dashboard-updates-via-github)
- [How to Use & Configuration](#how-to-use--configuration)
  - [Step 1: Connect API Credentials](#step-1-connect-api-credentials)
  - [Step 2: Configure Origin Warehouses](#step-2-configure-origin-warehouses)
  - [Step 3: Enable Shipping Zone Rate Method](#step-3-enable-shipping-zone-rate-method)
  - [Step 4: Configure Webhooks & Automation](#step-4-configure-webhooks--automation)
- [Order Operations & Bulk Actions](#order-operations--bulk-actions)
- [Developer Hooks & API Filters](#developer-hooks--api-filters)
- [Frequently Asked Questions (FAQ)](#frequently-asked-questions-faq)
- [Changelog](#changelog)
- [License & Support](#license--support)

---

## About

**WPCalibrate Shipping Connector** enables Indian direct-to-consumer (D2C) and B2C eCommerce businesses to integrate directly with Delhivery’s logistics APIs without relying on expensive third-party shipping aggregators.

By connecting directly to your registered commercial Delhivery account:
- You pay carrier rates negotiated directly with Delhivery.
- You avoid middleman platform subscription fees and markup percentages.
- You maintain complete ownership of customer tracking data and shipping logs.
- You streamline fulfillment workflows natively within the WooCommerce admin.

The plugin is architected for strict High-Performance Order Storage (HPOS) compliance, WCAG 2.2 AA accessibility, zero DOM leaks, and reliable asynchronous processing via Action Scheduler.

---

## Key Features

- **⚡ Live Dynamic Shipping Rates**:
  - Real-time rate calculations based on dead weight and volumetric dimensions (`L × W × H / 5000`).
  - Customizable handling fees: Flat fee addition or percentage markup.
  - Safe fallback rates in case carrier APIs experience temporary latency.
- **📍 Real-Time Pincode Serviceability & COD Validation**:
  - Validates delivery pin codes against Delhivery’s live serviceability matrix across India.
  - Automatically disables Cash on Delivery (COD) payment method when unserviceable for the recipient's pin code.
  - 12-hour transient caching for instant checkout responses without redundant API calls.
- **📦 1-Click Waybill Generation & Manifesting**:
  - Generate official Delhivery AWB numbers manually from the order metabox or automatically when an order reaches "Processing" status.
  - Idempotent request locking prevents accidental duplicate waybill creation.
- **🏷️ Standard 4×6 Inch Thermal Barcode Labels**:
  - Generates ready-to-print thermal shipping labels with high-density Code 128 barcodes, routing codes, customer address, return address, and declared values.
  - Supports both direct PDF download and browser-printable HTML slips.
- **🚚 Scheduled Pickup Requests**:
  - Dispatch courier pickup requests to your nearest Delhivery hub directly from WooCommerce.
  - Multi-warehouse support with dedicated pickup location tokens.
- **🔄 Real-Time Webhooks & Background Tracking Sync**:
  - Inbound REST API listener (`/wp-json/wpcalibrate-shipping/v1/webhook`) for Delhivery Scan-Push events.
  - Cryptographic token authentication and transient-based event deduplication.
  - Automatic WooCommerce order status transitions (e.g. Completed on "Delivered").
- **⚠️ NDR (Non-Delivery Report) Management**:
  - Actionable NDR exception management: trigger customer re-attempt, address edit, reschedule, or return to origin (RTO) directly from the order screen.
- **↩️ Customer Returns & Reverse Logistics**:
  - Generate reverse pickup waybills for customer returns with optional Quality Check (QC) validation.
- **⚡ Safe Bulk Order Operations**:
  - Bulk manifest generation, bulk thermal label downloads, and bulk pickup requests directly from the WooCommerce orders list screen.
- **🏢 Multi-Warehouse & Multi-Origin Support**:
  - Register multiple pickup centers and return hubs across different cities.
  - Assign default dispatch warehouses or route per order.

---

## Architecture & Shipment Lifecycle

```
[Customer Checkout]
       │
       ▼
Pincode Serviceability & COD Verification
       │
       ▼
Live Rate Calculation (Dead Weight vs Volumetric) + Markup
       │
       ▼
[Order Created in WooCommerce]
       │
       ▼
Waybill (AWB) Assigned (Manual 1-Click or Action Scheduler on "Processing")
       │
       ▼
Thermal Barcode Label Generated (4×6 inch format)
       │
       ▼
Courier Pickup Dispatched (/fm/request/new/)
       │
       ▼
In-Transit Tracking Updates via Delhivery Scan-Push Webhook
       │
       ├───► If Delivered: Order Completed & Customer Notified
       └───► If Delivery Fails: NDR Management (Re-attempt / RTO)
```

---

## Requirements

| Component | Minimum Version | Recommended |
|---|---|---|
| **WordPress** | 6.4.0+ | 6.7+ |
| **WooCommerce** | 8.5.0+ | 9.6+ / 11.1.2+ |
| **PHP** | 8.2.0 | 8.3+ |
| **HPOS** | Supported | Custom Orders Tables Active |
| **Carrier Account** | Delhivery B2C Domestic | Active API Token & Client Name |

---

## Installation

### Method 1: Manual ZIP Upload (Recommended)
1. Download `wpcalibrate-shipping-connector-1.0.0.zip` from the [Latest Release](https://github.com/zeeshanraza-official/wpcalibrate-shipping-connector/releases/latest).
2. Log into your WordPress Dashboard.
3. Navigate to **Plugins > Add New > Upload Plugin**.
4. Choose the downloaded ZIP file and click **Install Now**.
5. Click **Activate Plugin**.

### Method 2: Git Clone Installation
```bash
cd wp-content/plugins/
git clone https://github.com/zeeshanraza-official/wpcalibrate-shipping-connector.git
```

---

## Dashboard Updates via GitHub

WPCalibrate Shipping Connector features native in-dashboard update detection powered by GitHub Releases.

1. When a new release is published on GitHub, WordPress will automatically display an update notification under **Dashboard > Updates** and in the **Plugins** screen.
2. Click **Update Now** to update the plugin with a single click directly from your WordPress admin dashboard.
3. Fully compatible with the standard WordPress Upgrader and [Git Updater](https://github.com/afragen/git-updater).

---

## How to Use & Configuration

### Step 1: Connect API Credentials
1. In your WordPress admin menu, go to **WPCalibrate > Shipping Connector**.
2. Select the **API Connection** tab.
3. Enter your **Delhivery Client Name** and **API Authentication Token**.
4. Choose **Staging / Sandbox** for testing or **Production** for live fulfillments.
5. Click **Test Connection** to verify API communication.

### Step 2: Configure Origin Warehouses
1. Navigate to the **Warehouses** tab.
2. Click **Add New Warehouse**.
3. Enter your pickup address, city, state, pincode, and contact details.
4. *Important*: The origin warehouse name must match your registered pickup location in the Delhivery Client Portal.
5. Set your primary warehouse as **Default Origin**.

### Step 3: Enable Shipping Zone Rate Method
1. Go to **WooCommerce > Settings > Shipping > Shipping Zones**.
2. Select your domestic shipping zone (e.g. India).
3. Click **Add shipping method** and choose **Delhivery B2C Express**.
4. Return to **WPCalibrate > Shipping Connector > Shipping** tab to configure handling markups (flat fee or percentage) and fallback shipping rates.

### Step 4: Configure Webhooks & Automation
1. Navigate to the **Tracking & Webhooks** tab.
2. Copy your unique **Webhook URL** (`https://yourdomain.com/wp-json/wpcalibrate-shipping/v1/webhook`).
3. Copy your **Webhook Secret Token**.
4. Paste these credentials into your Delhivery UC / Unified Portal under Webhook Notifications for automatic real-time tracking sync.

---

## Order Operations & Bulk Actions

### Single Order Management
Open any order in WooCommerce admin (supporting both HPOS and classic postmeta screens). The **WPCalibrate: Delhivery Logistics** metabox provides:
- **1-Click Manifest**: Instantly books the shipment and assigns an AWB.
- **Print Shipping Label**: Opens a 4×6 inch thermal barcode label.
- **Schedule Pickup**: Books an on-demand courier pickup.
- **Track Shipment**: Displays live location timeline and scan history.
- **NDR Actions**: Re-attempt instructions (e.g. customer requested evening delivery).
- **Create Return**: Dispatches customer reverse return AWB.

### Bulk Actions
From the WooCommerce Orders list, select multiple orders and choose:
- **Delhivery: Create Shipments (Bulk Manifest)**
- **Delhivery: Print Shipping Labels**
- **Delhivery: Schedule Courier Pickup**

---

## Developer Hooks & API Filters

```php
// Customize package weight or dimensions before querying Delhivery API
add_filter( 'wpcalibrate_shipping_package_data', function( array $package, \WC_Order $order ): array {
    $package['weight'] += 0.1; // Add 100g packaging padding
    return $package;
}, 10, 2 );

// Modify live shipping rate before displaying at checkout
add_filter( 'wpcalibrate_shipping_calculated_rate', function( float $rate, array $delhivery_response ): float {
    return $rate;
}, 10, 2 );

// Custom logic when a shipment status changes via webhook
add_action( 'wpcalibrate_shipping_status_updated', function( int $order_id, string $new_status, string $waybill ): void {
    // Custom SMS or CRM notification dispatch
}, 10, 3 );
```

---

## Frequently Asked Questions (FAQ)

#### Does this plugin require an active Delhivery commercial account?
Yes. You need an active Delhivery B2C Domestic account with API credentials (client name and token).

#### Is High-Performance Order Storage (HPOS) supported?
Yes. WPCalibrate Shipping Connector is 100% HPOS certified and declared compatible via `FeaturesUtil::declare_compatibility( 'custom_order_tables', ... )`.

#### Does it support volumetric weight?
Yes. Rates automatically evaluate the greater of actual dead weight and volumetric weight (`L × W × H / 5000` in cm and kg).

#### What happens if Delhivery API is temporarily unreachable?
The checkout seamlessly falls back to your configured fallback rate, ensuring your customer can complete checkout without interruption.

---

## Changelog

### Version 1.0.3 (2026-10-07)
- **Overview Tab Hardening**: Resolved fatal undefined class constant in Overview template.
- **REST Route Constants**: Formally declared `REST_NAMESPACE` and `REST_ROUTE_WEBHOOK` in `Constants.php`.
- **Packaging Suite**: Updated standalone distribution packaging and test harness.

### Version 1.0.2 (2026-10-07)
- **Overview Onboarding Tab**: Added dedicated interactive overview tab with live status pills, purpose documentation, lifecycle method flow, and 4-step quick start guide.
- **Admin Sidebar Logo**: Hooked `admin_head` to strictly enforce 20×20px sidebar menu icon constraints.
- **Tab Layout Polish**: Removed `.card` conflicts and refined design system tokens in `admin.css`.
- **Automated FTP Sync**: Added automated deployment tools in `tools/ftp-deploy.php`.

### Version 1.0.1 (2026-10-07)
- **IDE Static Analysis**: Resolved all 294 Intelephense diagnostics with `.vscode/settings.json` and `stubs/wordpress-woocommerce-stubs.php`.

### Version 1.0.0 (2026-10-07)
- **Initial Release**: Complete commercial-grade Delhivery B2C domestic logistics integration for WooCommerce.

---

## License & Support

- **License**: [GNU General Public License v2.0 or later (GPL-2.0-or-later)](LICENSE)
- **Author**: [WPCalibrate](https://wpcalibrate.com)
- **Email Support**: [support@wpcalibrate.com](mailto:support@wpcalibrate.com)
- **Marketplace**: [https://marketplace.wpcalibrate.com/](https://marketplace.wpcalibrate.com/)
- **Phone / WhatsApp**: [+447474795976](https://wa.me/447474795976)
