# AGENTS.md - Permanent Development Rules & Standards

## Project Identity & Context
- **Project Name:** Telegram Referral & Commission Management System
- **Client / Domain:** Translation Service Company (Phnom Penh, Cambodia)
- **Primary Business Objective:** Accurately track translation customers referred by taxi and tuk-tuk drivers via physical QR codes and Telegram, manage the customer lead pipeline through to order fulfillment and payment, and calculate and disburse driver referral commissions reliably.
- **Core Business Question:** *Which taxi/tuk-tuk referral generated this customer, which order resulted from that referral, and how much commission is owed?*

---

## 1. Architectural Principles & Philosophy

1. **Incremental Phase Development:**
   - Never attempt to build the entire system in one monolithic step.
   - Strictly follow the development cycle:
     `Inspect -> Understand Architecture -> Design -> Document -> Implement Phase -> Test -> Review -> Approve -> Next Phase`.
   - Never implement features beyond the currently approved phase.

2. **Domain-Driven Service Separation:**
   - Keep controllers thin. All core business rules, attribution logic, currency conversions, and commission calculations must reside in dedicated domain service classes (`app/Services/...`).
   - Business logic must never leak into Blade views, Vue frontend components, or raw SQL queries.

3. **Financial Precision & Multi-Currency Rules:**
   - Both **USD ($)** and **Cambodian Riel (KHR)** are actively used in Cambodian commercial operations.
   - Money values must be stored as minor units / integers (e.g., USD in cents `amount_cents` or strict `DECIMAL(12, 2)` / `DECIMAL(14, 0)`).
   - Never use floating-point types (`float`, `double`) for money or commission rates.
   - Every financial record (`Order`, `Payment`, `Commission`, `CommissionPayout`) must snapshot the active currency and exchange rate at transaction time.

4. **Reliable Attribution & Fraud Prevention:**
   - Every referral event originates from an immutable, collision-resistant token encoded into `t.me/<BotUsername>?start=REF_{CODE}`.
   - Attribution must be idempotent: repeated scans by the same Telegram user must not duplicate leads or corrupt attribution history.
   - Store an immutable `attribution_logs` record for every QR scan and Telegram bot interaction.

5. **Strict Test-Driven Discipline:**
   - Referral attribution, order payment triggers, and commission calculations are critical financial logic and must be covered by automated tests before marking any phase as complete.
   - All tests must pass cleanly without regressions.

---

## 2. Technology Stack Specifications

- **Backend Framework:** Laravel 12
- **Language:** PHP 8.4+ (strict types: `declare(strict_types=1);` on all classes)
- **Database Engine:** MySQL 8+
- **Frontend Architecture:** Vue 3 (Composition API `<script setup lang="ts">`), TypeScript
- **Frontend Bridge:** Inertia.js (Single Page Application UX without redundant REST endpoints)
- **Styling & UI:** Tailwind CSS, Headless UI, Heroicons / Lucide Icons
- **Authorization & RBAC:** Spatie Laravel Permission (`spatie/laravel-permission`)
- **Telegram Integration:** Telegram Bot API (supporting both Webhooks and local artisan Polling)
- **QR Code Engine:** Vector SVG / high-resolution PNG generation (`simplesoftwareio/simple-qrcode`)

---

## 3. Working Documentation & Maintenance Rules

The project maintains the following synchronized specifications in `docs/`:
- `AGENTS.md`: Permanent development rules and architectural standards.
- `docs/PROJECT_REQUIREMENTS.md`: Business requirements and scope.
- `docs/MVP.md`: Minimum Viable Product boundaries and acceptance criteria.
- `docs/REFERRAL_ATTRIBUTION.md`: Rules of referral attribution, token lifetimes, and conflict resolution.
- `docs/DATABASE.md`: Schema definition, table structures, and relationships.
- `docs/TELEGRAM_BOT.md`: Bot lifecycle, deep linking, commands, and notification architecture.
- `docs/IMPLEMENTATION_PLAN.md`: Phased execution steps with deliverables and test gates.

**Rule:** Any change to database schemas, attribution rules, or commission workflows must be reflected in the respective documentation before or alongside code commits.

---

## 4. User Roles & Permission Hierarchy

1. **Super Admin:** Full system control, user management, global commission policies, system configuration, audit logs.
2. **Operations / Translation Manager:** Manage staff, oversee lead pipeline, assign translation jobs, review completed translation orders.
3. **Front Desk / Reception Staff:** Intake Telegram leads, chat with customers, create orders, quote pricing, deliver translated documents.
4. **Finance / Cashier:** Verify customer payments (Cash, Bakong/KHQR, Bank Transfer), approve commissions, record and disburse driver payouts.

---

## 5. Coding & Quality Standards

- **Strict Typing:** All PHP files must declare `strict_types=1`. All methods must have parameter and return type hints.
- **Form Requests:** All input validation must be encapsulated in dedicated Laravel `FormRequest` classes.
- **Database Transactions:** All multi-step mutations (e.g., verifying a payment and creating commission records) must be wrapped inside `DB::transaction()`.
- **Error Handling:** Graceful error responses with user-friendly alerts on the frontend; detailed logging to `storage/logs/laravel.log`.
- **Code Comments & Documentation Integrity:** Preserve existing meaningful comments and write clear docblocks for domain services.
- **Zero Placeholder Data:** Never leave incomplete mock values or hardcoded tokens in production code paths.
