# Database Schema & Data Dictionary

## 1. Overview & Architectural Principles

The database is built on **MySQL 8.0+** (InnoDB engine) with strict foreign key constraints, UTF8MB4 character encoding for complete multilingual support (Khmer, English, Chinese, French, etc.), and transaction safety.

### Financial Precision Standards:
- All monetary columns use exact integer minor units or strict decimals:
  - **USD:** `DECIMAL(12, 2)` (e.g., `150.00` USD) or stored alongside minor unit integer.
  - **KHR (Cambodian Riel):** `DECIMAL(14, 0)` (whole Riel units, e.g., `600000` KHR).
  - **Exchange Rate:** `DECIMAL(10, 4)` (e.g., `4100.0000` KHR/USD).
  - **Commission Percentage:** `DECIMAL(5, 2)` (e.g., `10.00` for 10.00%).

---

## 2. Entity-Relationship Overview

```text
users (staff/admins) ──────┐ (assigned_to / created_by)
                           ▼
drivers ───────▶ referral_qr_codes ───────▶ attribution_logs
   │                      │
   │                      ▼
   ├────────────────▶ leads ◀──────── telegram_users
   │                    │
   │                    ▼
   └───────────────▶ orders ◀─────── customers (or linked via telegram_users)
                        │
         ┌──────────────┴──────────────┐
         ▼                             ▼
      payments                    commissions ──────▶ commission_payouts
```

---

## 3. Schema Definitions

### 3.1 `users` (Staff & Administrative Accounts)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique staff identifier |
| `name` | VARCHAR(255) | NOT NULL | Full name of staff member |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | Staff login email |
| `password` | VARCHAR(255) | NOT NULL | Hashed password |
| `phone_number`| VARCHAR(50) | NULL | Contact phone number |
| `is_active` | BOOLEAN | DEFAULT TRUE | Account active flag |
| `remember_token` | VARCHAR(100) | NULL | Laravel auth token |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Audit timestamps |

*(RBAC implemented via Spatie standard tables: `roles`, `permissions`, `model_has_roles`, `role_has_permissions`)*

---

### 3.2 `referral_partners` (Partner Companies & Associations)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique partner identifier |
| `name` | VARCHAR(255) | NOT NULL, INDEX | Primary name of partner / association |
| `type` | VARCHAR(50) | NOT NULL, INDEX | Enum: `'DRIVER'`, `'COMPANY'`, `'OTHER'` |
| `phone` | VARCHAR(50) | NULL | Contact phone number |
| `email` | VARCHAR(255) | NULL | Contact email address |
| `company_name` | VARCHAR(255) | NULL | Registered corporate name |
| `address` | TEXT | NULL | Physical office or station address |
| `notes` | TEXT | NULL | Staff operational notes |
| `commission_rate` | DECIMAL(5, 2) | NOT NULL, DEFAULT `10.00` | Default referral commission percentage (e.g. 10.00%) |
| `status` | VARCHAR(50) | NOT NULL, DEFAULT `'ACTIVE'`, INDEX | Enum: `'ACTIVE'`, `'INACTIVE'` |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Audit timestamps |

---

### 3.3 `drivers` (Taxi & Tuk-Tuk Drivers)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique driver identifier |
| `referral_partner_id` | BIGINT UNSIGNED | NOT NULL, FK -> `referral_partners(id)`, `ON DELETE RESTRICT` | Associated referral partner |
| `name` | VARCHAR(255) | NOT NULL, INDEX | Driver's legal full name |
| `phone` | VARCHAR(50) | INDEX, NULL | Contact mobile number |
| `email` | VARCHAR(255) | NULL | Contact email address |
| `national_id` | VARCHAR(50) | NULL | Cambodian National ID / Passport No. |
| `address` | TEXT | NULL | Residential or base station address |
| `notes` | TEXT | NULL | Operational notes |
| `status` | VARCHAR(50) | NOT NULL, DEFAULT `'ACTIVE'`, INDEX | Enum: `'ACTIVE'`, `'INACTIVE'` |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Audit timestamps |

---

### 3.4 `vehicles` (Taxis, Tuk-Tuks & Transportation Units)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique vehicle identifier |
| `driver_id` | BIGINT UNSIGNED | NOT NULL, FK -> `drivers(id)`, `ON DELETE RESTRICT` | Assigned driver |
| `vehicle_type` | VARCHAR(50) | NOT NULL, INDEX | Enum: `'TAXI'`, `'TUK_TUK'`, `'OTHER'` |
| `plate_number` | VARCHAR(50) | NOT NULL, INDEX | Normalized license plate number |
| `make` | VARCHAR(100) | NULL | Vehicle manufacturer (e.g. Toyota, Bajaj) |
| `model` | VARCHAR(100) | NULL | Vehicle model (e.g. Prius, RE Maxima) |
| `color` | VARCHAR(50) | NULL | Vehicle exterior color |
| `notes` | TEXT | NULL | Vehicle notes (e.g. sticker placement) |
| `status` | VARCHAR(50) | NOT NULL, DEFAULT `'ACTIVE'`, INDEX | Enum: `'ACTIVE'`, `'INACTIVE'` |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Audit timestamps |

> [!IMPORTANT]
> **Historical Integrity & Snapshot Rules:**
> 1. Foreign key constraints enforce `ON DELETE RESTRICT` to prevent destructive cascades of partners or drivers with dependent records.
> 2. Deactivation via `status = 'INACTIVE'` is mandatory for retiring partners, drivers, or vehicles. Historical records remain fully queryable.
> 3. Duplicate active vehicle plate numbers are checked and prevented application-wide with uppercase trimmed normalization.
> 4. **Future Phases Recommendation (Phase 3 & 4):** When `referral_qr_codes`, `leads`, and `orders` are created in upcoming phases, attribution records MUST snapshot `referral_partner_id`, `driver_id`, `vehicle_id`, and `vehicle_plate_number` at transaction time. This ensures that any subsequent vehicle reassignment never modifies past referral history.

---

### 3.5 `referral_codes` (Unique QR Codes & Telegram Deep Links)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique referral code record ID |
| `referral_partner_id` | BIGINT UNSIGNED | NOT NULL, FK -> `referral_partners(id)`, `ON DELETE RESTRICT` | Partner hierarchy anchor |
| `driver_id` | BIGINT UNSIGNED | NOT NULL, FK -> `drivers(id)`, `ON DELETE RESTRICT` | Assigned driver |
| `vehicle_id` | BIGINT UNSIGNED | NOT NULL, FK -> `vehicles(id)`, `ON DELETE RESTRICT` | Assigned vehicle (at code issuance) |
| `code` | VARCHAR(64) | UNIQUE, NOT NULL, INDEX | Collision-resistant alphanumeric token (e.g. `REF_TK9821ABC`) |
| `label` | VARCHAR(255) | NULL | Descriptive flyer/campaign label (e.g. `Rear Seat Flyer - Phnom Penh Central`) |
| `notes` | TEXT | NULL | Operational deployment notes |
| `status` | VARCHAR(50) | NOT NULL, DEFAULT `'ACTIVE'`, INDEX | Enum: `'ACTIVE'`, `'INACTIVE'` |
| `deactivated_at` | TIMESTAMP | NULL | Timestamp of deactivation (preserved for attribution history) |
| `created_by` | BIGINT UNSIGNED | NULL, FK -> `users(id)`, `ON DELETE RESTRICT` | Staff member who issued the code |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Audit timestamps |

> [!IMPORTANT]
> **Referral Code Integrity & Immutability Rules:**
> 1. **Complete Ownership Tuple:** Every referral code binds directly to `referral_partner_id`, `driver_id`, and `vehicle_id`. The vehicle MUST belong to the driver, and the driver MUST belong to the partner.
> 2. **Immutability of Attribution Anchor:** Once created, `referral_partner_id`, `driver_id`, `vehicle_id`, and `code` cannot be altered. Only `label` and `notes` can be edited.
> 3. **Non-Recyclable Codes:** Deactivation sets `status = 'INACTIVE'` and timestamps `deactivated_at`. Codes are never deleted if historical leads or orders exist, and strings are never recycled.
> 4. **Deep Link Formula:** `https://t.me/{BOT_USERNAME}?start={code}`. Bot username is read dynamically from `config('services.telegram.bot_username')`.

---

### 3.6 `telegram_users` (Prospective & Existing Customers on Telegram)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Internal primary key |
| `telegram_id` | BIGINT | UNIQUE, NOT NULL, INDEX | External Telegram user identifier |
| `username` | VARCHAR(255) | NULL, INDEX | Telegram `@handle` (mutable) |
| `first_name` | VARCHAR(255) | NULL | Telegram first name |
| `last_name` | VARCHAR(255) | NULL | Telegram last name |
| `language_code`| VARCHAR(16) | NULL | Client language code (e.g. en, km, zh) |
| `is_bot` | BOOLEAN | DEFAULT FALSE | Bot identity flag |
| `phone` | VARCHAR(50) | NULL | Phone number shared via contact card |
| `last_interaction_at` | TIMESTAMP | NULL, INDEX | Latest incoming message timestamp |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Timestamps |

---

### 3.7 `customers` (Service Clients)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Customer identifier |
| `telegram_user_id` | BIGINT UNSIGNED | NULL, FK -> `telegram_users(id)`, `ON DELETE RESTRICT` | Associated Telegram user |
| `name` | VARCHAR(255) | NOT NULL, INDEX | Customer display name |
| `phone` | VARCHAR(50) | NULL, INDEX | Primary contact number |
| `email` | VARCHAR(255) | NULL | Contact email |
| `notes` | TEXT | NULL | Customer notes |
| `status` | VARCHAR(32) | NOT NULL, DEFAULT `'ACTIVE'`, INDEX | Enum: `'ACTIVE'`, `'INACTIVE'` |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Audit timestamps |

---

### 3.8 `referral_events` (Discrete Referral Interactions)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Event record ID |
| `referral_code_id` | BIGINT UNSIGNED | NULL, FK -> `referral_codes(id)`, `ON DELETE RESTRICT` | Scanned code |
| `telegram_user_id` | BIGINT UNSIGNED | NULL, FK -> `telegram_users(id)`, `ON DELETE RESTRICT` | Telegram scanner |
| `event_type` | VARCHAR(32) | NOT NULL, INDEX | Enum: `'SCAN'`, `'START'`, `'REPEAT_SCAN'`, `'INVALID_CODE'`, `'INACTIVE_CODE'`, `'UNKNOWN_CODE'` |
| `event_token` | VARCHAR(64) | NULL, INDEX | Raw token extracted from message |
| `telegram_update_id` | BIGINT UNSIGNED | NULL, UNIQUE, INDEX | Telegram update ID (enforces webhook idempotency) |
| `metadata` | JSON | NULL | Context metadata |
| `occurred_at` | TIMESTAMP | NOT NULL, INDEX | Timestamp of interaction |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Audit timestamps |

---

### 3.9 `referral_attributions` (Opportunity Referral Ownership)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Attribution ID |
| `referral_event_id` | BIGINT UNSIGNED | NOT NULL, FK -> `referral_events(id)`, `ON DELETE RESTRICT` | Originating event |
| `referral_code_id` | BIGINT UNSIGNED | NOT NULL, FK -> `referral_codes(id)`, `ON DELETE RESTRICT` | Attributed referral code |
| `telegram_user_id` | BIGINT UNSIGNED | NULL, FK -> `telegram_users(id)`, `ON DELETE RESTRICT` | Scanner profile |
| `customer_id` | BIGINT UNSIGNED | NOT NULL, FK -> `customers(id)`, `ON DELETE RESTRICT` | Client record |
| `lead_id` | BIGINT UNSIGNED | NULL, FK -> `leads(id)`, `ON DELETE RESTRICT` | Target opportunity |
| `referral_partner_id` | BIGINT UNSIGNED | NOT NULL, FK -> `referral_partners(id)`, `ON DELETE RESTRICT` | Snapshotted Partner anchor |
| `driver_id` | BIGINT UNSIGNED | NOT NULL, FK -> `drivers(id)`, `ON DELETE RESTRICT` | Snapshotted Driver |
| `vehicle_id` | BIGINT UNSIGNED | NOT NULL, FK -> `vehicles(id)`, `ON DELETE RESTRICT` | Snapshotted Vehicle |
| `attribution_status` | VARCHAR(32) | NOT NULL, DEFAULT `'ACTIVE'`, INDEX | Enum: `'ACTIVE'`, `'CHANGED'`, `'CANCELLED'` |
| `attributed_at` | TIMESTAMP | NOT NULL, INDEX | Attribution establishment timestamp |
| `changed_at` | TIMESTAMP | NULL | Timestamp of manual reassignment |
| `change_reason` | TEXT | NULL | Mandatory operational explanation for change |
| `created_by` | BIGINT UNSIGNED | NULL, FK -> `users(id)`, `ON DELETE RESTRICT` | User who made manual attribution |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Audit timestamps |

---

### 3.10 `leads` (Customer Inquiry Opportunities)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Lead opportunity ID |
| `customer_id` | BIGINT UNSIGNED | NOT NULL, FK -> `customers(id)`, `ON DELETE RESTRICT` | Associated customer |
| `telegram_user_id` | BIGINT UNSIGNED | NULL, FK -> `telegram_users(id)`, `ON DELETE RESTRICT` | Associated Telegram user |
| `referral_attribution_id` | BIGINT UNSIGNED | NULL, FK -> `referral_attributions(id)`, `ON DELETE RESTRICT` | Current active attribution |
| `title` | VARCHAR(255) | NOT NULL | Opportunity title |
| `description` | TEXT | NULL | Inquiry requirements |
| `service_type` | VARCHAR(100) | NULL | Document or translation type |
| `status` | VARCHAR(32) | NOT NULL, DEFAULT `'NEW'`, INDEX | Enum: `'NEW'`, `'CONTACTED'`, `'QUOTATION'`, `'CONFIRMED'`, `'LOST'`, `'CANCELLED'` |
| `assigned_to` | BIGINT UNSIGNED | NULL, FK -> `users(id)`, `ON DELETE RESTRICT` | Assigned staff member |
| `source` | VARCHAR(32) | NOT NULL, DEFAULT `'TELEGRAM_REFERRAL'`, INDEX | Enum: `'TELEGRAM_REFERRAL'` |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Audit timestamps |

---

### 3.11 `lead_notes` (Internal Lead Conversation & Audit Notes)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Note record ID |
| `lead_id` | BIGINT UNSIGNED | NOT NULL, FK -> `leads(id)`, `ON DELETE CASCADE` | Parent lead |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users(id)`, `ON DELETE RESTRICT` | Author staff member |
| `note` | TEXT | NOT NULL | Note content |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Timestamps |

---

### 3.6 `orders` (Confirmed Translation Orders)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Order ID |
| `order_number` | VARCHAR(32) | UNIQUE, NOT NULL, INDEX | Human-readable identifier (e.g. `ORD-2026-000001`) |
| `customer_id` | BIGINT UNSIGNED | NOT NULL, FK -> `customers(id)`, `ON DELETE RESTRICT` | Associated customer |
| `lead_id` | BIGINT UNSIGNED | NULL, FK -> `leads(id)`, `ON DELETE SET NULL`, INDEX | Originating lead |
| `referral_attribution_id` | BIGINT UNSIGNED | NULL, FK -> `referral_attributions(id)`, `ON DELETE RESTRICT`, INDEX | Permanent referral attribution binding |
| `status` | VARCHAR(32) | NOT NULL, DEFAULT `'pending'`, INDEX | Enum: `'pending'`, `'confirmed'`, `'cancelled'`, `'completed'` |
| `currency` | VARCHAR(3) | NOT NULL, DEFAULT `'USD'` | Transaction currency |
| `subtotal` | DECIMAL(15, 2) | NOT NULL | Calculated sum of line items |
| `discount` | DECIMAL(15, 2) | NOT NULL, DEFAULT `0.00` | Discount applied to order |
| `total_amount` | DECIMAL(15, 2) | NOT NULL, INDEX | Net payable: `subtotal - discount` |
| `notes` | TEXT | NULL | Special instructions or remarks |
| `confirmed_at` | TIMESTAMP | NULL | Confirmation timestamp |
| `completed_at` | TIMESTAMP | NULL | Completion timestamp |
| `cancelled_at` | TIMESTAMP | NULL | Cancellation timestamp |
| `created_by` | BIGINT UNSIGNED | NOT NULL, FK -> `users(id)`, `ON DELETE RESTRICT` | Staff who created the order |
| `updated_by` | BIGINT UNSIGNED | NULL, FK -> `users(id)`, `ON DELETE RESTRICT` | Last staff member who modified order |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Audit timestamps |

---

### 3.7 `order_items` (Order Line Items & Service Pricing)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Item ID |
| `order_id` | BIGINT UNSIGNED | NOT NULL, FK -> `orders(id)`, `ON DELETE CASCADE` | Parent order |
| `description` | VARCHAR(255) | NOT NULL | Service / document description |
| `quantity` | INT UNSIGNED | NOT NULL, DEFAULT 1 | Number of units / pages |
| `unit_price` | DECIMAL(15, 2) | NOT NULL | Unit price per document/page |
| `subtotal` | DECIMAL(15, 2) | NOT NULL | Line subtotal: `quantity * unit_price` |
| `notes` | VARCHAR(500) | NULL | Item-level notes |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Timestamps |

---

### 3.8 `payments` (Customer Payments & Manual KHQR Records)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Payment ID |
| `order_id` | BIGINT UNSIGNED | NOT NULL, FK -> `orders(id)`, `ON DELETE RESTRICT`, INDEX | Associated order |
| `payment_reference` | VARCHAR(64) | UNIQUE, NOT NULL, INDEX | Unique transaction ref (e.g. `PAY-2026-000001`) |
| `amount` | DECIMAL(15, 2) | NOT NULL | Validated payment amount (no overpayments allowed) |
| `currency` | VARCHAR(3) | NOT NULL, DEFAULT `'USD'` | Payment currency |
| `payment_method` | VARCHAR(32) | NOT NULL | Enum: `'cash'`, `'bank_transfer'`, `'khqr_manual'`, `'other'` |
| `status` | VARCHAR(32) | NOT NULL, DEFAULT `'paid'`, INDEX | Enum: `'paid'`, `'refunded'` |
| `paid_at` | TIMESTAMP | NOT NULL, INDEX | Transaction settlement date/time |
| `received_by` | BIGINT UNSIGNED | NOT NULL, FK -> `users(id)`, `ON DELETE RESTRICT` | Cashier/staff who received payment |
| `notes` | TEXT | NULL | Staff remarks / manual bank receipt note |
| `refunded_at` | TIMESTAMP | NULL | Timestamp of refund |
| `refund_reason` | TEXT | NULL | Audit reason for refund |
| `refunded_by` | BIGINT UNSIGNED | NULL, FK -> `users(id)`, `ON DELETE RESTRICT` | Finance user who processed refund |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Timestamps |

---

### 3.8 `commissions` (Driver Referral Commissions)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary Key |
| `commission_reference` | VARCHAR(64) | UNIQUE, NOT NULL | E.g., `COM-2026-000001` |
| `order_id` | BIGINT UNSIGNED | UNIQUE, NOT NULL, FK -> `orders(id)` | Strict 1:1 relationship with paid order |
| `referral_attribution_id` | BIGINT UNSIGNED | NOT NULL, FK -> `referral_attributions(id)` | Snapshot of referral attribution source |
| `referral_partner_id` | BIGINT UNSIGNED | NOT NULL, FK -> `referral_partners(id)` | Snapshot of partner entity |
| `driver_id` | BIGINT UNSIGNED | NOT NULL, FK -> `drivers(id)` | Snapshot of driver beneficiary |
| `vehicle_id` | BIGINT UNSIGNED | NOT NULL, FK -> `vehicles(id)` | Snapshot of referring vehicle |
| `commission_rate` | DECIMAL(5, 2) | NOT NULL | Policy percentage at transaction time (e.g., 10.00%) |
| `eligible_amount` | DECIMAL(12, 2) | NOT NULL | Order base total eligible for commission |
| `commission_amount` | DECIMAL(12, 2) | NOT NULL | Final calculated commission |
| `currency` | VARCHAR(3) | NOT NULL, DEFAULT `'USD'` | Transaction currency |
| `status` | VARCHAR(32) | NOT NULL, DEFAULT `'earned'`, INDEX | Enum: `'pending'`, `'earned'`, `'approved'`, `'paid'`, `'reversed'` |
| `earned_at` | TIMESTAMP | NULL, INDEX | Timestamp when commission was earned |
| `approved_at` | TIMESTAMP | NULL | Approval timestamp |
| `paid_at` | TIMESTAMP | NULL | Disbursement timestamp |
| `reversed_at` | TIMESTAMP | NULL | Reversal timestamp |
| `reversal_reason` | TEXT | NULL | Reason for cancellation/refund reversal |
| `notes` | TEXT | NULL | Administrative remarks |
| `created_by` | BIGINT UNSIGNED | NULL, FK -> `users(id)` | User or system actor |
| `approved_by` | BIGINT UNSIGNED | NULL, FK -> `users(id)` | Manager/admin who approved commission |
| `paid_by` | BIGINT UNSIGNED | NULL, FK -> `users(id)` | Finance user who recorded payout |
| `reversed_by` | BIGINT UNSIGNED | NULL, FK -> `users(id)` | User who processed reversal |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Audit timestamps |

---

### 3.9 `commission_payments` (Commission Payout Disbursements)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary Key |
| `commission_id` | BIGINT UNSIGNED | NOT NULL, FK -> `commissions(id)`, `ON DELETE CASCADE` | Linked commission record |
| `payment_reference` | VARCHAR(64) | UNIQUE, NOT NULL | E.g., `CPAY-2026-000001` or cashier voucher # |
| `amount` | DECIMAL(12, 2) | NOT NULL | Amount disbursed |
| `payment_method` | VARCHAR(32) | NOT NULL | Enum: `'cash'`, `'bank_transfer'`, `'other'` |
| `paid_at` | TIMESTAMP | NOT NULL | Date and time payout was given |
| `paid_to` | VARCHAR(255) | NOT NULL | Recipient name (driver or representative) |
| `notes` | TEXT | NULL | Additional disbursement details |
| `created_by` | BIGINT UNSIGNED | NOT NULL, FK -> `users(id)` | Cashier/finance user recording payout |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Audit timestamps |

---

### 3.10 `attribution_logs` (Append-Only Audit Trail)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary Key |
| `telegram_user_id` | BIGINT UNSIGNED | NULL | Interacting user |
| `referral_qr_code_id`| BIGINT UNSIGNED| NULL | Scanned QR code |
| `driver_id` | BIGINT UNSIGNED | NULL | Inferred driver |
| `action_type` | VARCHAR(50) | NOT NULL | `'qr_scan'`, `'bot_start'`, `'lead_attributed'`, `'commission_created'` |
| `raw_payload` | JSON | NULL | Full JSON payload received from Telegram |
| `ip_address` | VARCHAR(45) | NULL | IP address (if applicable) |
| `created_at` | TIMESTAMP | NOT NULL | Immutable event timestamp |
