# Phased Implementation Plan & Execution Roadmap

## Executive Overview
This implementation plan outlines the step-by-step engineering progression for the **Telegram Referral & Commission Management System**.

In accordance with `AGENTS.md`, development progresses **strictly phase by phase**. Each phase concludes with automated testing, review, and approval before starting the next phase.

---

## Roadmap Summary

```text
Phase 0: Environment & Core Foundations [COMPLETED]
           ↓
Phase 1: Authentication & Role-Based Access Control (RBAC) [COMPLETED]
           ↓
Phase 2: Driver Management & QR Code Generation Engine [PENDING APPROVAL]
           ↓
Phase 3: Telegram Bot & Lead Attribution Pipeline
           ↓
Phase 4: Customer Lead & Order Management (Staff Portal)
           ↓
Phase 5: Payment Processing & Commission Calculation Engine
           ↓
Phase 6: Reporting, Polish & Production Readiness
```

---

## Detailed Phases

### Phase 0: Environment Setup & Core Foundations
- **Status:** **COMPLETED & VERIFIED**
- **Deliverables:**
  * Host toolchain configured (Git 2.48, PHP 8.4.26, Composer 2.11, Node.js 22 LTS).
  * Laravel 12.69 + Inertia.js Vue 3 + TypeScript + Tailwind CSS initialized.
  * Pest PHP 3.8 testing harness configured.
  * Working specifications in `docs/` and `AGENTS.md`.

---

### Phase 1: Authentication & Role-Based Access Control (RBAC)
- **Status:** **COMPLETED & VERIFIED**
- **Objective:** Secure staff portal with role-based access for Super Admin, Admin, Staff, and Finance personas.
- **Implemented Architecture:**
  1. **Authentication & Inactive User Protection:**
     - `LoginRequest` checks user status: Inactive users cannot authenticate.
     - `EnsureUserIsActive` middleware: Inactive sessions are invalidated immediately and redirected.
  2. **Role & Permission Infrastructure (Spatie):**
     - Roles: `SUPER_ADMIN`, `ADMIN`, `STAFF`, `FINANCE`.
     - Permissions: `users.view`, `users.create`, `users.update`, `users.delete`, `roles.view`, `roles.assign`, `dashboard.view`.
     - Seeders: `RoleAndPermissionSeeder` and `UserSeeder`.
  3. **Super Admin Safety Safeguards:**
     - Enforced in `UserPolicy` and `UserManagementService`.
     - Final active `SUPER_ADMIN` cannot be deleted.
     - Final active `SUPER_ADMIN` cannot be deactivated.
     - Unauthorized users cannot assign `SUPER_ADMIN` role or modify a `SUPER_ADMIN` account.
     - Unauthorized users cannot strip `SUPER_ADMIN` privileges.
  4. **User Management Interface (Vue 3 + Inertia):**
     - `Users/Index.vue`: High-contrast table with role/status badges and action buttons respecting permissions.
     - `Users/Create.vue`: Validation, role assignment, and status selection.
     - `Users/Edit.vue`: Update credentials, roles, and status (with duplicate email handling).
     - `Dashboard.vue`: Welcome greeting, user role badge, and system health status.
     - `AuthenticatedLayout.vue`: Phase 1 navigation (`Dashboard`, `Users` [permission-gated]).
- **Verification Gate:**
  * **47 automated tests passed (112 assertions), 100% green.**
  * Clean `npm run build` production asset compilation (819 modules transformed).

---

### Phase 2: Referral Partners, Drivers & Vehicles
- **Status:** **COMPLETED**
- **Objective:** Establish the fleet hierarchy (`ReferralPartner -> Driver -> Vehicle`) with status-based deactivation, normalized plate identification, strict authorization, and historical data preservation.
- **Implemented Scope:**
  1. **Domain Enums & Schemas:**
     - Enums: `PartnerType` (`DRIVER`, `COMPANY`, `OTHER`), `PartnerStatus` (`ACTIVE`, `INACTIVE`), `DriverStatus` (`ACTIVE`, `INACTIVE`), `VehicleType` (`TAXI`, `TUK_TUK`, `OTHER`), `VehicleStatus` (`ACTIVE`, `INACTIVE`).
     - Tables: `referral_partners`, `drivers` (foreign key `referral_partner_id` with `restrictOnDelete()`), `vehicles` (foreign key `driver_id` with `restrictOnDelete()`).
     - Models: `ReferralPartner`, `Driver`, `Vehicle` with custom scopes and mutators (plate number uppercase trimming normalization).
  2. **Domain Services & Authorization:**
     - `ReferralPartnerService`, `DriverService`, `VehicleService`.
     - Deletion protection: partners with drivers and drivers with vehicles cannot be deleted; soft status deactivation is enforced.
     - Policies: `ReferralPartnerPolicy`, `DriverPolicy`, `VehiclePolicy` protecting `view`, `create`, `update`, `delete`, and status toggles.
     - RBAC: `SUPER_ADMIN` and `ADMIN` hold full management rights; `STAFF` has read-only access; `FINANCE` restricted.
  3. **User Interface (Vue 3 + Inertia):**
     - Full CRUD views for Partners (`Index`, `Create`, `Edit`, `Show`).
     - Full CRUD views for Drivers (`Index`, `Create`, `Edit`, `Show`).
     - Full CRUD views for Vehicles (`Index`, `Create`, `Edit`, `Show`).
     - Reassignment warning when transferring vehicles between drivers.
     - Operational dashboard cards for Active Partners, Active Drivers, and Active Vehicles.
  4. **Verification Gate:**
     - **83 automated tests passed (196 assertions), 100% green.**
     - TypeScript strict checks and Vite production asset build passed (`✓ built in 18.42s`).

---

### Phase 3: Referral Codes & QR Generation Engine (Completed)
- **Objective:** Generate collision-resistant referral codes and vector QR codes bound to the Partner -> Driver -> Vehicle hierarchy, producing actionable Telegram deep links.
- **Implemented Deliverables:**
  1. **Database & Schema:**
     - `referral_codes` table with strict foreign keys (`referral_partner_id`, `driver_id`, `vehicle_id`, `created_by` on delete restrict).
     - Unique index on `code`.
     - Status enum: `ACTIVE`, `INACTIVE` with `deactivated_at` audit timestamp.
  2. **Domain Services:**
     - `TelegramReferralLinkService`: dynamically formats `https://t.me/{BOT_USERNAME}?start={CODE}`.
     - `QrCodeService`: vector SVG generation, data URIs, and direct attachment download responses.
     - `ReferralCodeService`: enforces hierarchical ownership validity (active partner -> active driver belonging to partner -> active vehicle belonging to driver), collision-resistant token generation, metadata updates with attribution immutability, safe deactivation, and deletion protection.
  3. **User Interface (Vue 3 + Inertia):**
     - Full CRUD views (`Index`, `Create`, `Edit`, `Show`, `Print`).
     - Cascading partner -> driver -> vehicle dropdown selection with validation.
     - 1-click clipboard copy of Telegram deep link.
     - High-contrast, printable flyer view formatted for vehicle seat/dashboard display.
  4. **Verification Gate:**
     - **102 automated tests passed (239 assertions), 100% green.**
     - TypeScript strict checks and Vite production asset build passed (`✓ built in 13.14s`).

---

### Phase 4: Telegram Bot Integration & Lead Attribution Pipeline (Completed)
- **Objective:** Ingest customer Telegram QR scans, enforce the "First Qualified Referral Wins" attribution model, create customers and leads, and provide staff lead management with full auditability.
- **Implemented Deliverables:**
  1. **Database & Schema:**
     - `telegram_users` table with unique `telegram_id` index and interaction timestamps.
     - `customers` table with status enum (`ACTIVE`, `INACTIVE`).
     - `referral_events` table with unique `telegram_update_id` for webhook idempotency and event type tracking.
     - `referral_attributions` table capturing snapshotted referral hierarchy (`referral_partner_id`, `driver_id`, `vehicle_id`, `referral_code_id`), status (`ACTIVE`, `CHANGED`, `CANCELLED`), and mandatory audit reasons for reassignments.
     - `leads` table with statuses (`NEW`, `CONTACTED`, `QUOTATION`, `CONFIRMED`, `LOST`, `CANCELLED`) and active referral attribution link.
     - `lead_notes` table for internal staff conversation notes and automated audit logs.
  2. **Domain Services & Telegram Bot:**
     - `TelegramBotService`: encapsulates Telegram API calls, welcome/error messages, and `X-Telegram-Bot-Api-Secret-Token` verification; network failures never abort database transactions.
     - `TelegramUserService`: idempotent profile discovery and update.
     - `ReferralAttributionService`: strictly enforces webhook update idempotency, FIRST QUALIFIED REFERRAL WINS for active opportunities, distinct opportunity attribution for returning customers with closed leads, and manual administrative attribution changes with mandatory reasons.
  3. **Staff Interface & RBAC:**
     - Webhook endpoint: `POST /api/telegram/webhook`.
     - Lead Management Controller & Form Requests (`index`, `show`, `update`, `assign`, `notes`, `change-attribution`).
     - Vue 3 + Inertia views: `Leads/Index.vue` and `Leads/Show.vue`.
     - Strict RBAC: Super Admin & Admin can reassign attribution; Staff can manage leads and notes; Finance cannot modify attributions or access lead pipeline.
  4. **Verification Gate:**
     - **121 automated tests passed (313 assertions), 100% green.**
     - TypeScript strict checks and Vite production asset build passed (`✓ built in 14.72s`).

---

### Phase 5: Orders & Customer Payment Tracking (Completed)
- **Objective:** Convert qualified leads into confirmed translation orders with immutable referral attribution preservation, record customer payments across payment methods, calculate outstanding balances, and determine full payment readiness.
- **Implemented Deliverables:**
  1. **Database & Schema:**
     - `orders` table with collision-resistant order numbers (`ORD-YYYY-XXXXXX`), immutable `referral_attribution_id` binding, status enum (`pending`, `confirmed`, `completed`, `cancelled`), and `DECIMAL(15, 2)` monetary precision.
     - `order_items` table with line item descriptions, unit prices, quantities, and subtotals.
     - `payments` table supporting manual counter methods (`cash`, `bank_transfer`, `khqr_manual`, `other`), unique transaction references (`PAY-YYYY-XXXXXX`), overpayment prevention, and non-destructive refund auditing.
  2. **Domain Services:**
     - `OrderService`: generates unique order numbers with row locking, calculates item totals and validates discounts server-side, enforces `lead.referral_attribution_id -> order.referral_attribution_id` copying, prevents duplicate active orders per lead, and validates status transitions (`PENDING -> CONFIRMED -> COMPLETED`).
     - `PaymentService`: validates positive non-overpaying amounts, applies database concurrency locking (`Order::lockForUpdate()`), records customer payments, handles payment refunds with reasons and staff auditing, and recalculates order settlement state.
  3. **User Interface (Vue 3 + Inertia):**
     - `Orders/Index.vue`: multi-filter search by order number, customer, driver, order status, payment status, partner, and date range.
     - `Orders/Create.vue`: pre-populated from Lead with read-only attribution card, dynamic line items, real-time total calculation, and server-validated discount.
     - `Orders/Show.vue`: full order details, line items table, financial metrics, payment recording modal, and payment history ledger with refund actions.
     - `Leads/Show.vue`: integrated Order & Payment fulfillment card with 1-click "Convert to Order" / "View Order" navigation.
     - `Dashboard.vue`: added real-time Order pipeline counts and Payment fulfillment / outstanding balance metrics.
  4. **Security & RBAC:**
     - Form Requests: `StoreOrderRequest`, `UpdateOrderRequest`, `RecordPaymentRequest`, `RefundPaymentRequest`.
     - Policies: `OrderPolicy` and `PaymentPolicy`.
     - Staff can view/create orders and record payments. Finance can view/record payments and execute refunds. Zero client trust on `referral_attribution_id`.
  5. **Verification Gate:**
     - **137 automated tests passed (382 assertions), 100% green.**
     - TypeScript strict checks and Vite production asset build passed (`✓ built in 22.80s`, 864 modules).
     - Clean `migrate:fresh --seed` execution.

---

### Phase 6: Commission Management & Driver Disbursements
- **Status:** **COMPLETED & VERIFIED**
- **Objective:** Manage driver commissions, enforce qualification criteria, snapshot historical policies, approve disbursements, and record manual driver payouts.
- **Implemented Architecture:**
  1. **Database Schema & Migrations:**
     - `referral_partners.commission_rate`: `DECIMAL(5, 2)` defaulting to `10.00%`.
     - `commissions`: `UNIQUE(order_id)`, foreign keys to `orders`, `referral_attributions`, `referral_partners`, `drivers`, `vehicles`, and users; status index (`pending`, `earned`, `approved`, `paid`, `reversed`); snapshotted rates and amounts.
     - `commission_payments`: `UNIQUE(payment_reference)`, foreign keys to `commissions` and `users`; manual methods (`cash`, `bank_transfer`, `other`); disbursement timestamp and recipient.
  2. **Domain Services & Hooks:**
     - `CommissionService`:
       - `checkEligibility()`: strictly validates order confirmation, positive total, 100% full payment, valid attribution, and uniqueness.
       - `evaluateAndEarnForOrder()`: concurrency locked (`lockForUpdate()`), creates commission idempotently upon full payment.
       - `approveCommission()`: transitions from `EARNED` to `APPROVED` with auditor tracking.
       - `recordPayout()`: transitions from `APPROVED` to `PAID` and logs `CommissionPayment`.
       - `reverseCommission()`: cancels commission with mandatory audit reason.
       - `handleOrderRefund()`: automatically reverses commission if customer payment is refunded.
     - `PaymentService` Integration: auto-triggers commission evaluation upon `order.isFullyPaid() === true` and reversal upon payment refund.
  3. **User Interface (Vue 3 + Inertia):**
     - `Commissions/Index.vue`: summary cards (Total Earned, Approved, Paid, Reversed, Outstanding Liability), search and status/partner/driver/date filters, and quick approve actions.
     - `Commissions/Show.vue`: full financial breakdown, order attribution snapshot, lifecycle timeline, payout modal, and reversal modal.
     - `Commissions/Reports.vue`: analytical summaries grouped by Referral Partner and Driver.
     - `Orders/Show.vue`: integrated Driver Commission card and manual commission evaluation button.
     - `Dashboard.vue`: added real-time Commission metrics and liability tracking.
  4. **Security & RBAC:**
     - Form Requests: `RecordCommissionPayoutRequest`, `ReverseCommissionRequest`.
     - Policy: `CommissionPolicy` with Spatie permissions (`commissions.*`, `commission_payments.*`).
     - Staff has view-only access; Admin and Finance have approval and payout privileges.
  5. **Verification Gate:**
     - **154 automated tests passed (457 assertions), 100% green.**
     - TypeScript strict checks and Vite production build passed with 0 errors (`✓ built in 10.37s`, 870 modules).
     - Clean `migrate:fresh --seed` execution.

---

### Phase 7: Reporting, Polish & Production Readiness
- **Objective:** Provide business insights to management, polish user interface, and ensure production readiness.
- **Tasks:**
  1. Driver Performance Dashboard (total scans, lead conversion rate, total revenue generated, total commissions paid).
  2. Export capabilities (CSV/Excel report for monthly accounting).
  3. Security hardening (rate limiting, CSRF verification, Telegram webhook secret verification).
  4. Final documentation sync and end-to-end regression test suite execution.
- **Verification Gate:**
  * Complete test suite passing with 100% green status.
  * Operational sign-off from project stakeholders.

