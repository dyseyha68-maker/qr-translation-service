# Phased Implementation Plan & Execution Roadmap

## Executive Overview
This implementation plan outlines the step-by-step engineering progression for the **Telegram Referral & Commission Management System**.

In accordance with `AGENTS.md`, development progresses **strictly phase by phase**. Each phase concludes with automated testing, review, and approval before starting the next phase.

---

## Roadmap Summary

```text
Phase 0: Environment & Core Foundations
           ↓
Phase 1: Authentication & Role-Based Access Control (RBAC)
           ↓
Phase 2: Driver Management & QR Code Generation Engine
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
- **Objective:** Provision runtime environment, initialize Laravel 12 application scaffold, and configure frontend and testing frameworks.
- **Tasks:**
  1. Verify and configure local runtime toolchain (Git, PHP 8.4+, Composer, Node.js 20+).
  2. Scaffold clean Laravel 12 with Inertia.js, Vue 3, TypeScript, and Tailwind CSS.
  3. Configure database connection (MySQL 8+ / SQLite for tests).
  4. Setup testing framework (Pest / PHPUnit) with database refresh traits.
  5. Commit initial working documentation (`AGENTS.md` and all `docs/*` files).
- **Verification Gate:**
  * Clean `php artisan test` execution.
  * Successful `npm run build` and `npm run dev` compilation.

---

### Phase 1: Authentication & Role-Based Access Control (RBAC)
- **Objective:** Secure staff portal with role-based access for Admin, Staff, and Finance personas.
- **Tasks:**
  1. Implement authentication (login, logout, password reset, session security).
  2. Install and configure `spatie/laravel-permission`.
  3. Seed initial roles (`Super Admin`, `Staff`, `Finance`) and default administrative user.
  4. Build responsive, high-contrast staff portal layout (Sidebar navigation, user profile menu, notification banner).
  5. Implement role-protected route middleware.
- **Verification Gate:**
  * Automated feature tests verifying role permissions and unauthorized route blocking.

---

### Phase 2: Driver Management & QR Code Generation Engine
- **Objective:** Enable staff to register taxi/tuk-tuk drivers and generate unique, printable referral QR codes.
- **Tasks:**
  1. Create `drivers` and `referral_qr_codes` database migrations and Eloquent models.
  2. Implement Driver CRUD (Driver profile, vehicle type, plate number, contact details, Bakong payout info, commission rates).
  3. Build collision-resistant referral token generator (`REF_XXXXXX`).
  4. Integrate QR code generation library (`simplesoftwareio/simple-qrcode`).
  5. Create driver detail view with QR code preview, SVG/PNG download, and printable QR sticker/card layout.
- **Verification Gate:**
  * Automated unit tests asserting token uniqueness, collision prevention, and correct Telegram URL deep link format (`https://t.me/<bot>?start=REF_{code}`).

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
