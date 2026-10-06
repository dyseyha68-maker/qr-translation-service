# Minimum Viable Product (MVP) Scope & Acceptance Criteria

## 1. MVP Vision & Core Focus
The primary purpose of the MVP release is to prove and validate the unbroken core business chain:
```text
Driver QR Code 
  → Telegram Scan 
  → Referral Identification 
  → Customer Lead 
  → Staff Order Creation 
  → Customer Payment Verification 
  → Commission Calculation & Driver Payout
```

The MVP must answer with 100% certainty:
> *Which taxi/tuk-tuk referral generated this customer, which order resulted from that referral, and how much commission is owed?*

---

## 2. In-Scope for MVP

### A. Staff Authentication & RBAC (Implemented in Phase 1)
- Secure login and session authentication for staff members.
- Role separation: `SUPER_ADMIN`, `ADMIN`, `STAFF`, `FINANCE`.
- Protected routes and action policies (`UserPolicy`).
- Inactive user protection (`status = ACTIVE | INACTIVE`).
- Super Admin safety safeguards against deletion, deactivation, and unauthorized role escalation.

### B. Driver Management & QR Generation (Target: Phase 2)
- Register and manage drivers (Name, Phone number, Vehicle number/type, ABA/Bakong payout account, Notes).
- Generate collision-resistant unique referral codes (e.g., `REF_TK9821`).
- Dynamic QR code generation with download options (SVG / PNG) and printable card template.
- Direct link preview: `https://t.me/<BotUsername>?start=REF_{CODE}`.

### C. Telegram Bot Integration & Referral Attribution (Target: Phase 3)
- Telegram bot webhook and local polling support.
- Processing `/start REF_{CODE}` deep links.
- Creating / updating `TelegramUser` records.
- Creating `Lead` records attributed to the referring driver.
- Sending a customized, warm welcome greeting to the customer.
- Sending an automated alert to the translation staff channel/dashboard when a new referral arrives.
- Immutable scan and attribution logging (`AttributionLog`).

### D. Lead Management (Staff Portal) (Target: Phase 4)
- Unified Lead Inbox with referral origin badges (Driver Name, Referral Code).
- Lead status progression: `New` → `Contacted` → `Quoted` → `Converted` → `Lost`.
- Option to convert an active Lead directly into a Translation Order.

### E. Translation Order Lifecycle (Target: Phase 4)
- Order creation tied to Customer and Lead.
- Fields: Order Number, Service Type (Legal, General, Notary, Visa), Source/Target Languages, Document Count/Page Count, Quoted Amount, Currency (USD / KHR), Due Date, Notes.
- Order statuses: `Draft` → `Confirmed` → `In Translation` → `Completed` → `Cancelled`.

### F. Payment Verification & Multi-Currency (Target: Phase 5)
- Recording customer payments: Amount, Currency (USD / KHR), Payment Method (Cash, ABA/KHQR, Bank Transfer), Transaction Reference / Slip, Paid Date.
- Payment status: `Pending Verification` → `Verified / Paid` → `Refunded`.

### G. Commission Calculation & Driver Payout (Target: Phase 5)
- Automatic commission generation upon order payment verification.
- Flexible commission model: Default percentage (e.g., 10%) or custom driver-specific override.
- Commission ledger: `Pending Approval` → `Approved` → `Paid`.
- Driver payout recording: Finance marks commission as paid, recording Payout Date, Payment Method (Cash or Bakong), and Reference Slip.

---

## 3. Explicitly Out-of-Scope for MVP (Deferred to Phase 2+)

1. **Automated Telegram Document File Processing:** Customers sending PDF files to the bot will be handled directly by human staff in chat, not by an automated AI parsing bot.
2. **Automated Banking API Integration:** Automated ABA PayWay / Bakong direct API webhooks are deferred; staff will manually verify and approve payment slips.
3. **Driver Self-Service Portal / Native App:** Drivers will not log in to a web portal in MVP. Staff can print statements or send Telegram summary messages to drivers.
4. **Complex Multi-Tier Affiliate Schemes:** Multi-level marketing (referring other drivers) is out of scope. One driver per referred customer only.
5. **Customer Invoicing PDF Generator:** Standard on-screen receipt and printable summary is sufficient for MVP.

---

## 4. MVP Acceptance Criteria

| ID | Test Scenario | Acceptance Criteria | Status |
| :--- | :--- | :--- | :--- |
| **AC-0** | Authentication & User RBAC | Secure authentication, inactive protection, Spatie roles (`SUPER_ADMIN`, `ADMIN`, `STAFF`, `FINANCE`), and Super Admin safeguards. | ✅ PASSED (Phase 1) |
| **AC-1** | Driver QR Generation | Staff can create a driver and download a high-res QR code linking to `https://t.me/<bot>?start=REF_<driver_code>`. | Planned (Phase 2) |
| **AC-2** | Deep Link Attribution | When a simulated or real user clicks `/start REF_<driver_code>`, the system creates a `Lead` attributed to that specific driver without errors. | Planned (Phase 3) |
| **AC-3** | Duplicate Scan Idempotency | If the same Telegram user clicks the QR code multiple times within 24 hours, the existing lead is updated or linked without duplicating open leads or overwriting historical attributions incorrectly. | Planned (Phase 3) |
| **AC-4** | Lead to Order Conversion | Staff can click "Convert to Order" on a lead; all customer and referral attribution data carry over automatically. | Planned (Phase 4) |
| **AC-5** | Payment Verification Trigger | Marking an order payment as "Verified / Paid" automatically instantiates a `Commission` record with correct calculations. | Planned (Phase 5) |
| **AC-6** | Financial Precision | Currency amounts in USD and KHR calculate without decimal floating-point rounding errors. | Planned (Phase 5) |
| **AC-7** | Driver Payout Workflow | Finance can view all unpaid commissions grouped by driver, approve them, and mark them as disbursed with payout notes. | Planned (Phase 5) |
| **AC-8** | Audit Trail | Every state transition (Lead conversion, Payment verification, Commission disbursement) records the executing user ID and timestamp. | Planned (Phase 5) |
