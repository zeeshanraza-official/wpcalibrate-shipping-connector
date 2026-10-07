# Delhivery B2C API Mapping Specification

This document details the official Delhivery Express / B2C APIs integrated into **WPCalibrate Shipping Connector**.

---

## 1. Environments & Base URLs

| Environment | Base URL |
|---|---|
| **Production** | `https://track.delhivery.com` |
| **Staging / Test** | `https://staging-express.delhivery.com` |

---

## 2. Authentication Standard

- **Header Format:** `Authorization: Token {API_TOKEN}`
- **Alternative / Legacy Query Parameter:** `token={API_TOKEN}` (supported by certain endpoints such as tracking and waybill allocation).
- **Rule:** Never transmit authentication tokens in frontend scripts, never display unmasked tokens in UI, and always redact authorization headers before logging.

---

## 3. Integrated Endpoints & Specifications

### 3.1 Pincode Serviceability API
- **Endpoint:** `GET /c/api/pin-codes/json/`
- **Authentication:** `Authorization: Token {API_TOKEN}`
- **Query Parameters:**
  - `filter_codes`: 6-digit Indian PIN code (e.g. `110001`)
- **Key Response Fields:**
  - `delivery_codes`: Array of objects containing:
    - `postal_code`: Pin code record
    - `pin`: PIN code integer/string
    - `pre_paid`: Serviceability for prepaid shipments (`Y` / `N`)
    - `cod`: Serviceability for cash-on-delivery shipments (`Y` / `N`)
    - `pickup`: Pickup serviceability (`Y` / `N`)
    - `is_oda`: Out of Delivery Area flag (`Y` / `N`)
    - `state_code`: 2-letter state code

### 3.2 Shipping Rate Calculator API (Kinko)
- **Endpoint:** `GET /api/kinko/v1/invoice/charges/.json`
- **Authentication:** `Authorization: Token {API_TOKEN}`
- **Query Parameters:**
  - `md`: Shipping mode (`S` for Surface, `E` for Express)
  - `cgm`: Chargeable weight in grams (e.g., `500` for 0.5kg)
  - `o_pin`: Origin warehouse pincode (e.g., `110001`)
  - `d_pin`: Destination customer pincode (e.g., `400001`)
  - `ss`: Status of shipment (`Delivered` used for estimating forward delivery charges)
  - `pt`: Payment type (`Pre-paid` or `COD`)
- **Key Response Fields:**
  - Array containing charge breakdown objects:
    - `total_amount`: Total estimated shipping charges in INR
    - `gross_amount`: Base freight charge
    - `charge_COD`: COD handling fee (if COD)
    - `charge_ROV`: Risk of voyage / fuel surcharge

### 3.3 Bulk Waybill Fetching API
- **Endpoint:** `GET /waybill/api/bulk/json/`
- **Query Parameters:**
  - `cl`: Client / Account name
  - `token`: API Token
  - `count`: Number of waybills to allocate (typically `1` for single order)
- **Response Format:**
  - Returns waybill identifier or array of AWB numbers.
  - *Note:* In Delhivery B2C, forward shipments can auto-allocate a waybill dynamically if not pre-allocated.

### 3.4 B2C Shipment Creation / Manifestation API
- **Endpoint:** `POST /api/cmu/create.json`
- **Authentication:** `Authorization: Token {API_TOKEN}`
- **Request Headers:**
  - `Content-Type: application/x-www-form-urlencoded`
- **Body Format:** `format=json&data={JSON_STRING}`
- **Payload Schema:**
```json
{
  "pickup_location": {
    "name": "Warehouse Name (Must match registered pickup location)",
    "add": "Origin Address Line",
    "city": "Origin City",
    "pin": "110001",
    "country": "India",
    "phone": "9876543210"
  },
  "shipments": [
    {
      "order": "WC_ORDER_ID_OR_NUMBER",
      "waybill": "OPTIONAL_PRE_ALLOCATED_AWB",
      "name": "Consignee Full Name",
      "add": "Delivery Address",
      "pin": "400001",
      "city": "Mumbai",
      "state": "Maharashtra",
      "country": "India",
      "phone": "9876543211",
      "payment_mode": "Prepaid | COD",
      "total_amount": 1250.00,
      "cod_amount": 1250.00,
      "products_desc": "Cotton T-Shirt, Blue - Size L",
      "order_date": "2026-10-07 10:00:00",
      "weight": 500,
      "dimensions": "20x15x5",
      "quantity": "1",
      "seller_gst_tin": "GSTIN12345",
      "hsn_code": "61091000"
    }
  ]
}
```
- **Response Format:**
```json
{
  "cash_pickups_count": 0,
  "package_count": 1,
  "upload_wbn": "...",
  "replacement_count": 0,
  "rmk": "...",
  "packages": [
    {
      "status": "Success",
      "waybill": "84303710000066",
      "refnum": "WC_ORDER_ID",
      "remarks": [ ... ],
      "service": "B2C"
    }
  ],
  "success": true
}
```

### 3.5 Shipment Tracking API
- **Endpoint:** `GET /api/v1/packages/json/`
- **Authentication:** `Authorization: Token {API_TOKEN}` or `token={API_TOKEN}`
- **Query Parameters:**
  - `waybill`: Single AWB or comma-separated list of AWBs (supports batching)
  - `verbose`: `0` for summary, `1` or `2` for full scans history
- **Response Schema:**
```json
{
  "ShipmentData": [
    {
      "Shipment": {
        "AWB": "84303710000066",
        "ReferenceNo": "1042",
        "PickUpDate": "2026-10-07 11:00:00",
        "Status": {
          "Status": "In Transit",
          "StatusCode": "IT",
          "StatusDateTime": "2026-10-07T12:00:00.000",
          "StatusType": "DL",
          "StatusLocation": "Delhi_Hub",
          "Instructions": "Departed Delhi Facility"
        },
        "Scans": [
          {
            "ScanDetail": {
              "ScanDateTime": "2026-10-07T11:30:00",
              "ScanType": "UD",
              "Scan": "Manifested",
              "ScannedLocation": "Gurgaon",
              "Instructions": "Shipment Picked Up"
            }
          }
        ]
      }
    }
  ]
}
```

### 3.6 Packing Slip / Label API
- **Endpoint:** `GET /api/p/packing_slip`
- **Authentication:** `Authorization: Token {API_TOKEN}`
- **Query Parameters:**
  - `wbns`: Comma-separated list of waybills (e.g. `84303710000066`)
- **Response Format:** JSON containing package metadata (`packages` array) with barcode representation for label generation.
- **Client Fallback / Rendering:** If Delhivery returns JSON, plugin renders high-resolution HTML packing slip with standard Code128 barcode suitable for 4x6 thermal printing or A4 sheet printing.

### 3.7 Shipment Edit & Cancellation API
- **Endpoint:** `POST /api/p/edit`
- **Authentication:** `Authorization: Token {API_TOKEN}`
- **Request Headers:** `Content-Type: application/json`
- **Cancellation Payload:**
```json
{
  "waybill": "84303710000066",
  "cancellation": "true"
}
```
- **Update Payload:**
```json
{
  "waybill": "84303710000066",
  "name": "Updated Consignee Name",
  "add": "Updated Delivery Address",
  "phone": "9876543212",
  "pt": "COD",
  "cod_amount": 1300.00
}
```
- **Allowed States for Cancellation / Edit:** `Manifested`, `In Transit`, `Pending`, `Scheduled`.

### 3.8 Pickup Request Creation API
- **Endpoint:** `POST /fm/request/new/`
- **Authentication:** `Authorization: Token {API_TOKEN}`
- **Request Headers:** `Content-Type: application/json`
- **Payload Schema:**
```json
{
  "pickup_location": "Main Warehouse",
  "pickup_date": "2026-10-08",
  "pickup_time": "14:00:00",
  "expected_package_count": 5
}
```
- **Response:** Returns pickup request ID and scheduled pickup reference.

### 3.9 Client Warehouse Creation / Registration API
- **Endpoint:** `POST /api/backend/clientwarehouse/create/`
- **Authentication:** `Authorization: Token {API_TOKEN}`
- **Payload Schema:**
```json
{
  "name": "Warehouse North",
  "address": "Plot 12, Industrial Area Phase 2",
  "pin": "110020",
  "phone": "9876543210",
  "city": "New Delhi",
  "state": "Delhi",
  "country": "India",
  "return_address": "Plot 12, Industrial Area Phase 2",
  "return_pin": "110020",
  "return_city": "New Delhi",
  "return_state": "Delhi",
  "return_country": "India"
}
```

### 3.10 NDR (Non-Delivery Report) Action API
- **Endpoint:** `POST /api/p/update/` (or Asynchronous NDR Package Action)
- **Supported Documented Actions:**
  - `REATTEMPT`: Request another delivery attempt with optional delivery instructions.
  - `RESCHEDULE` / `DEFERRED`: Reschedule delivery to a specified date (`rescheduleDate`).
  - `RTO` / `RETURN_TO_ORIGIN`: Instruct carrier to abort delivery and return package to origin.
- **Payload Schema:**
```json
{
  "waybill": "84303710000066",
  "action": "REATTEMPT | RESCHEDULE | RTO",
  "reschedule_date": "2026-10-10",
  "remarks": "Customer was unavailable; reattempt requested."
}
```

### 3.11 Reverse Shipments (RVP) & Quality Check (QC)
- **Endpoint:** `POST /api/cmu/create.json`
- **Configuration:**
  - `payment_mode`: Set to `"Pickup"`
  - `pickup_location`: Customer pickup address (or registered location)
  - `return_address`: Merchant return warehouse
  - Optional `qc` object:
```json
{
  "item": "Sneakers",
  "brand": "Nike",
  "color": "Black",
  "reason": "Size Mismatch"
}
```
- Note: Reverse pickups are scheduled automatically by Delhivery upon manifestation without requiring a separate pickup request.

### 3.12 Webhook / Scan-Push Specification
- **Notification Method:** Server-to-Server HTTP `POST` from Delhivery to plugin REST endpoint `/wp-json/wpcalibrate-shipping/v1/webhook`.
- **Response Requirement:** Must return HTTP 200 within 500ms; work must be queued to WooCommerce Action Scheduler.
- **Payload Schema:**
```json
{
  "Shipment": {
    "AWB": "84303710000066",
    "ReferenceNo": "1042",
    "PickUpDate": "2026-10-07 17:10:42",
    "Status": {
      "Status": "Delivered",
      "StatusDateTime": "2026-10-07T17:10:42.000",
      "StatusType": "DL",
      "StatusLocation": "Mumbai_South_DC",
      "Instructions": "Delivered to recipient"
    }
  }
}
```
- **Security:** Secret comparison via configured header (e.g. `X-Delhivery-Secret` or bearer token comparison) and signature verification if configured.
