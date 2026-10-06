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

### Phase 5: Customer Lead & Order Management (Staff Portal)
- **Objective:** Provide counter staff with an intuitive lead pipeline and convert leads into confirmed translation orders.
- **Tasks:**
  1. Build Lead Inbox and Kanban board (`New`, `Contacted`, `Quoted`, `Converted`, `Lost`).
  2. Implement "Convert Lead to Order" modal/workflow carrying forward customer and referring driver metadata.
  3. Create `orders` migration and model with service category, language pairs, document count, quoted price, and currency (USD/KHR).
  4. Build Order management views (Order list, filtering, order detail page, status updates).
- **Verification Gate:**
  * Automated tests for lead status transitions, order creation with driver linkage, and attribution lock-in.

---

### Phase 6: Payment Processing & Commission Calculation Engine
- **Objective:** Verify customer payments, automatically calculate referral commissions, and manage driver payouts.
- **Tasks:**
  1. Create `payments`, `commissions`, and `commission_payouts` migrations and models.
  2. Implement Payment registration & verification workflow (Cash, ABA/KHQR, Bank Transfer) with receipt upload.
  3. Build `CommissionCalculationService`:
     - Triggers automatically upon payment verification.
     - Calculates commission based on snapshotted rate (percentage or fixed).
     - Handles USD and KHR without decimal rounding errors.
  4. Build Finance Commission Ledger (`Pending Approval` → `Approved` → `Paid`).
  5. Implement Driver Payout disbursement workflow (group unpaid commissions, record Bakong/Cash transaction reference, mark as Paid).
- **Verification Gate:**
  * Comprehensive automated tests validating exact commission calculations, multiple orders per driver, partial/split payments, and payout reconciliation.

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

