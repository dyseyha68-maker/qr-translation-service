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

### 3.3 `referral_qr_codes` (Unique QR Codes & Deep Links)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary Key |
| `driver_id` | BIGINT UNSIGNED | FK -> `drivers(id)`, ON DELETE CASCADE | Owner of the QR code |
| `code` | VARCHAR(32) | UNIQUE, NOT NULL | Unique token (e.g., `REF_TK9821`) |
| `telegram_deep_link` | VARCHAR(255) | NOT NULL | `https://t.me/<bot>?start=REF_{code}` |
| `qr_image_path` | VARCHAR(255) | NULL | Stored vector/image path |
| `batch_label` | VARCHAR(100) | NULL | Print batch (e.g., `Batch 1 - Oct 2026`) |
| `scan_count` | INT UNSIGNED | DEFAULT 0 | Counter for total scans |
| `is_active` | BOOLEAN | DEFAULT TRUE | Revocable status |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Timestamps |

---

### 3.4 `telegram_users` (Prospective & Existing Customers on Telegram)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary Key |
| `telegram_id` | BIGINT | UNIQUE, NOT NULL | Telegram user ID |
| `first_name` | VARCHAR(255) | NULL | Telegram first name |
| `last_name` | VARCHAR(255) | NULL | Telegram last name |
| `username` | VARCHAR(255) | NULL, INDEX | Telegram `@handle` |
| `phone_number` | VARCHAR(50) | NULL | Shared phone number |
| `language_code`| VARCHAR(10) | NULL | Language preference code (en, km, zh) |
| `last_interaction_at` | TIMESTAMP | NULL | Latest message timestamp |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Timestamps |

---

### 3.5 `leads` (Customer Inquiries from QR Referrals)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary Key |
| `lead_number` | VARCHAR(32) | UNIQUE, NOT NULL | E.g., `LEAD-202610-0001` |
| `telegram_user_id` | BIGINT UNSIGNED | FK -> `telegram_users(id)`, NOT NULL | Customer's Telegram profile |
| `referral_qr_code_id`| BIGINT UNSIGNED | FK -> `referral_qr_codes(id)`, NULL | Scanned QR code |
| `driver_id` | BIGINT UNSIGNED | FK -> `drivers(id)`, NULL | Attributed driver |
| `assigned_user_id`| BIGINT UNSIGNED | FK -> `users(id)`, NULL | Staff member assigned |
| `status` | ENUM | DEFAULT `'new'` | `'new'`, `'contacted'`, `'quoted'`, `'converted'`, `'lost'` |
| `attribution_expires_at` | TIMESTAMP | NULL | 30-day window expiry |
| `initial_service_interest`| VARCHAR(255) | NULL | E.g., "Driver License Translation" |
| `notes` | TEXT | NULL | Internal conversation summary |
| `converted_at` | TIMESTAMP | NULL | When converted to order |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Timestamps |

---

### 3.6 `orders` (Confirmed Translation Jobs)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary Key |
| `order_number` | VARCHAR(32) | UNIQUE, NOT NULL | E.g., `ORD-202610-0042` |
| `lead_id` | BIGINT UNSIGNED | FK -> `leads(id)`, NULL | Originating lead |
| `telegram_user_id` | BIGINT UNSIGNED | FK -> `telegram_users(id)`, NULL | Customer profile |
| `customer_name` | VARCHAR(255) | NOT NULL | Customer name on invoice |
| `customer_phone` | VARCHAR(50) | NULL | Phone number |
| `driver_id` | BIGINT UNSIGNED | FK -> `drivers(id)`, NULL | Attributed driver (locked) |
| `service_category` | VARCHAR(100) | NOT NULL | E.g., Legal, Notarized, Visa, General |
| `source_language` | VARCHAR(50) | NOT NULL | E.g., Khmer, Chinese, English, French |
| `target_language` | VARCHAR(50) | NOT NULL | Target language |
| `page_count` | INT UNSIGNED | DEFAULT 1 | Total pages/documents |
| `currency` | ENUM('USD','KHR')| DEFAULT `'USD'` | Transaction currency |
| `total_amount` | DECIMAL(12, 2) | NOT NULL | Total quoted price |
| `exchange_rate` | DECIMAL(10, 4) | DEFAULT 4100.0000 | KHR/USD rate at time of order |
| `status` | ENUM | DEFAULT `'draft'` | `'draft'`, `'confirmed'`, `'in_translation'`, `'completed'`, `'cancelled'` |
| `due_date` | DATE | NULL | Delivery promise date |
| `created_by_user_id`| BIGINT UNSIGNED | FK -> `users(id)`, NOT NULL | Staff who opened order |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Timestamps |

---

### 3.7 `payments` (Customer Payments & Inflows)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary Key |
| `order_id` | BIGINT UNSIGNED | FK -> `orders(id)`, NOT NULL | Related order |
| `payment_reference`| VARCHAR(64) | UNIQUE, NOT NULL | Slip number or TX ID |
| `payment_method` | ENUM | NOT NULL | `'cash'`, `'aba_khqr'`, `'bank_transfer'`, `'other'` |
| `currency` | ENUM('USD','KHR')| NOT NULL | Currency paid |
| `amount` | DECIMAL(12, 2) | NOT NULL | Amount paid |
| `status` | ENUM | DEFAULT `'pending'` | `'pending'`, `'verified'`, `'rejected'`, `'refunded'` |
| `receipt_slip_path`| VARCHAR(255) | NULL | Uploaded bank slip image |
| `verified_by_user_id`| BIGINT UNSIGNED| FK -> `users(id)`, NULL | Cashier/Finance user |
| `verified_at` | TIMESTAMP | NULL | Verification timestamp |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Timestamps |

---

### 3.8 `commissions` (Driver Referral Commissions)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary Key |
| `driver_id` | BIGINT UNSIGNED | FK -> `drivers(id)`, NOT NULL | Driver to receive commission |
| `order_id` | BIGINT UNSIGNED | FK -> `orders(id)`, NOT NULL | Eligible paid order |
| `payment_id` | BIGINT UNSIGNED | FK -> `payments(id)`, NOT NULL | Triggering payment |
| `commission_type` | ENUM | NOT NULL | `'percentage'`, `'fixed'` |
| `rate_applied` | DECIMAL(8, 2) | NOT NULL | Snapshot of rate applied (e.g. 10.00%) |
| `currency` | ENUM('USD','KHR')| NOT NULL | Commission currency |
| `commission_amount`| DECIMAL(12, 2) | NOT NULL | Final calculated commission |
| `status` | ENUM | DEFAULT `'pending'` | `'pending'`, `'approved'`, `'paid'`, `'cancelled'` |
| `payout_id` | BIGINT UNSIGNED | FK -> `commission_payouts(id)`, NULL | Linked payout batch |
| `approved_by_user_id`| BIGINT UNSIGNED| FK -> `users(id)`, NULL | Finance approver |
| `approved_at` | TIMESTAMP | NULL | Approval timestamp |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Timestamps |

---

### 3.9 `commission_payouts` (Driver Disbursements)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary Key |
| `payout_reference` | VARCHAR(64) | UNIQUE, NOT NULL | E.g., `PAYOUT-202610-0012` |
| `driver_id` | BIGINT UNSIGNED | FK -> `drivers(id)`, NOT NULL | Recipient driver |
| `currency` | ENUM('USD','KHR')| NOT NULL | Payout currency |
| `total_amount` | DECIMAL(12, 2) | NOT NULL | Total disbursed amount |
| `payout_method` | ENUM | NOT NULL | `'cash'`, `'bakong_khqr'`, `'aba_transfer'` |
| `proof_file_path` | VARCHAR(255) | NULL | Uploaded receipt slip / photo |
| `disbursed_by_user_id`| BIGINT UNSIGNED| FK -> `users(id)`, NOT NULL | Cashier who disbursed funds |
| `disbursed_at` | TIMESTAMP | NOT NULL | Disbursed date and time |
| `notes` | TEXT | NULL | Payout notes |
| `created_at` / `updated_at` | TIMESTAMP | NULL | Timestamps |

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
