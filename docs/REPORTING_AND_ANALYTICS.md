# REPORTING_AND_ANALYTICS.md - Analytics, Performance & Export Engine

## Overview

The **Reporting & Analytics Module** provides real-time business intelligence, driver and partner conversion metrics, commission liabilities, and CSV streaming exports for the translation referral business in Phnom Penh, Cambodia.

It answers the core business questions:
1. *Which taxi/tuk-tuk drivers and referral partners drive the highest customer volume and conversion rates?*
2. *What is our total gross revenue, collected revenue, and uncollected balance?*
3. *What is our outstanding commission liability owed to drivers?*

---

## 1. Executive Summary KPIs

The top-level executive metrics are aggregated platform-wide or across a specified date range (`date_from`, `date_to`):

| Metric | Source | Calculation / Description |
| :--- | :--- | :--- |
| **Total Scans** | `referral_events` | Total QR scans and Telegram bot `/start` events recorded. |
| **Total Leads** | `leads` | Total inquiries/leads created across all channels. |
| **Total Orders** | `orders` | All translation service orders created. |
| **Confirmed Orders** | `orders` | Orders with status `confirmed` or `completed`. |
| **Conversion Rate (%)** | Aggregated | `(Confirmed Orders / Total Leads) * 100` (rounded to 1 decimal place; `0.0%` if no leads). |
| **Total Gross Revenue** | `orders` | Sum of `total_amount` for confirmed/completed orders. |
| **Total Collected** | `payments` | Sum of verified, paid customer payments (`status = 'paid'`). |
| **Commissions Earned** | `commissions` | Sum of all commissions earned (`earned`, `approved`, `paid`). |
| **Commissions Paid** | `commissions` | Sum of all disbursed driver commissions (`paid`). |
| **Outstanding Liability** | `commissions` | Sum of earned/approved commissions awaiting payout disbursement (`earned`, `approved`). |

---

## 2. Driver Performance Analytics

The driver performance view combines vehicle registrations, active QR codes, referral events, customer pipeline conversions, and commission earnings:

- **Attribution Source**: `referral_attributions.driver_id` linked to `leads` and `orders`.
- **Conversion Metric**:
  $$\text{Conversion Rate} = \frac{\text{Confirmed Orders}}{\text{Total Leads}} \times 100$$
- **Commission Split**:
  - `commission_earned`: Total commission accrued from all valid completed orders.
  - `commission_approved`: Commission verified and ready for cashier payout.
  - `commission_paid`: Commission successfully disbursed to driver.
  - `commission_outstanding`: `commission_earned - commission_paid`.

---

## 3. Referral Partner Performance Analytics

The partner performance view groups aggregate driver metrics by company/association:
- Total registered drivers under the partner.
- Default partner commission rate (`commission_rate_percentage`).
- Aggregate scans, leads, orders, revenue, and earned/paid/outstanding commissions across all assigned drivers.

---

## 4. High-Performance CSV Streaming Exports

To handle large datasets without hitting PHP memory exhaustion limits (`memory_limit`), all reporting exports use Laravel streamed responses (`response()->streamDownload`):

### Export Endpoints:
1. **Driver Performance Export**: `GET /reports/export/drivers`
   - File format: `driver-performance-YYYY-MM-DD.csv`
   - Includes: Driver ID, Name, Phone, Partner Name, Status, Vehicle Plates, Active Codes, Scans, Leads, Orders, Confirmed Orders, Conversion Rate (%), Revenue ($), Earned ($), Approved ($), Paid ($), Outstanding ($).
2. **Commission Ledger Export**: `GET /reports/export/commissions`
   - File format: `commissions-ledger-YYYY-MM-DD.csv`
   - Includes: Reference, Order Number, Driver Name, Partner, Eligible Amount, Commission Rate (%), Commission Amount, Currency, Status, Earned Date, Approved Date, Paid Date, Cashier/Payer, Payment Reference.
3. **Orders Fulfillment Export**: `GET /reports/export/orders`
   - File format: `orders-fulfillment-YYYY-MM-DD.csv`
   - Includes: Order Number, Customer Name, Phone, Attributed Driver, Partner, Status, Currency, Subtotal, Discount, Total Amount, Paid Amount, Outstanding Balance, Date Created.

### UTF-8 BOM Encoding:
All CSV streams output an initial UTF-8 Byte Order Mark (`\xEF\xBB\xBF`) before CSV content so that Microsoft Excel properly decodes Khmer Unicode characters, driver names, and symbols without mojibake.

---

## 5. Security & RBAC Enforcement

- **Viewing Reports**: Governed by permission `reports.view`. Assigned to `Super Admin`, `Admin`, and `Finance`.
- **Exporting Reports**: Governed by permission `reports.export`. Assigned to `Super Admin`, `Admin`, and `Finance`.
- Front desk `Staff` without `reports.view` are strictly forbidden (HTTP 403) from accessing reporting endpoints or viewing commission liabilities.
