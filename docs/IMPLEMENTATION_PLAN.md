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

### Phase 3: Telegram Bot & Lead Attribution Pipeline
- **Objective:** Capture customer QR scans from Telegram, parse referral tokens, and create attributed leads in real time.
- **Tasks:**
  1. Create `telegram_users`, `leads`, and `attribution_logs` migrations and models.
  2. Implement `TelegramBotService` supporting both webhook updates and `php artisan telegram:poll`.
  3. Implement `ReferralAttributionService` with 30-day attribution window logic, idempotent repeat-scan handling, and audit logging.
  4. Implement automated customer welcome response in English, Khmer, and Chinese.
  5. Implement internal staff notification alert (Telegram channel alert + web dashboard notification).
  6. Implement local scan simulation command (`php artisan telegram:simulate-scan`).
- **Verification Gate:**
  * Automated tests simulating Telegram `/start REF_xxx` updates, verifying correct driver lead attribution and audit log creation without live internet dependencies.

---

### Phase 4: Customer Lead & Order Management (Staff Portal)
- **Objective:** Provide counter staff with an intuitive lead pipeline and convert leads into confirmed translation orders.
- **Tasks:**
  1. Build Lead Inbox and Kanban board (`New`, `Contacted`, `Quoted`, `Converted`, `Lost`).
  2. Implement "Convert Lead to Order" modal/workflow carrying forward customer and referring driver metadata.
  3. Create `orders` migration and model with service category, language pairs, document count, quoted price, and currency (USD/KHR).
  4. Build Order management views (Order list, filtering, order detail page, status updates).
- **Verification Gate:**
  * Automated tests for lead status transitions, order creation with driver linkage, and attribution lock-in.

---

### Phase 5: Payment Processing & Commission Calculation Engine
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

### Phase 6: Reporting, Polish & Production Readiness
- **Objective:** Provide business insights to management, polish user interface, and ensure production readiness.
- **Tasks:**
  1. Driver Performance Dashboard (total scans, lead conversion rate, total revenue generated, total commissions paid).
  2. Export capabilities (CSV/Excel report for monthly accounting).
  3. Security hardening (rate limiting, CSRF verification, Telegram webhook secret verification).
  4. Final documentation sync and end-to-end regression test suite execution.
- **Verification Gate:**
  * Complete test suite passing with 100% green status.
  * Operational sign-off from project stakeholders.
