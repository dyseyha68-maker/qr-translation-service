# Referral Attribution Specification & Business Rules

## 1. Overview & Business Rationale
Referral attribution is the critical trust foundation of this system. If attribution is ambiguous, drivers lose trust and staff face disputes over owed commissions.

This document establishes the exact rules for:
1. QR Code & Referral Token Generation
2. Telegram `/start` Deep-Link Payload Processing
3. Attribution Attribution Windows & Policy (First-Touch vs. Last-Touch)
4. Duplicate Scans & Returning Customers
5. Fraud Prevention & Tamper Proofing

---

## 2. Referral Token Generation

### Format & Structure
- Every driver is issued one or more unique referral tokens.
- **Prefix:** `REF_`
- **Identifier:** 6-8 alphanumeric uppercase characters (excluding easily confused characters `O`, `0`, `I`, `1`, `L`).
- **Example:** `REF_TK874M`, `REF_TAX299`
- **Database Index:** `UNIQUE` index on `referral_qr_codes.code`.

### Telegram Deep Link Construction
Telegram Bots support deep linking via the standard `start` parameter:
```text
https://t.me/<BotUsername>?start=REF_{CODE}
```
When a smartphone camera scans the physical QR code containing this URL, Telegram opens automatically and sends the message `/start REF_{CODE}` on behalf of the customer.

---

## 3. Attribution Ingestion Pipeline

```text
Passenger Scans Physical QR Card
               ↓
Telegram opens with: /start REF_TK874M
               ↓
Telegram Bot API delivers Webhook/Poll Update to Laravel
               ↓
ReferralAttributionService intercepts Update:
  1. Extract Telegram User Metadata (telegram_id, username, first_name, last_name, language_code)
  2. Parse Payload: Extract token ("REF_TK874M")
  3. Validate Token: Check active status in `referral_qr_codes`
  4. Fetch Referring Driver Record
               ↓
Check Customer History & Apply Attribution Rules (see Section 4)
               ↓
Create/Update Lead record:
  - Linked to TelegramUser
  - Linked to Driver & QR Code
  - Status = 'new'
  - Snapshot active commission rate
               ↓
Write Immutable Record to `attribution_logs`
               ↓
Dispatch Staff Notification & Customer Welcome Message
```

---

## 4. Attribution Rules & Edge Cases

### Policy A: Active Attribution Window (30 Days)
- When a customer first interacts with the Telegram bot via a driver's QR code, an **Attribution Window** is opened for **30 calendar days** from the scan date.
- Any order placed and confirmed by this customer within this 30-day window is credited to the referring driver.
- If an order is created on Day 31 without any new scans, no referral commission is owed (attributed to direct company organic acquisition).

### Policy B: Returning Customers & Multiple Scans (First-Touch vs Last-Touch)
- **Scenario 1: Customer scans Driver A, places Order 1 within 30 days.**
  * Result: Driver A receives commission on Order 1.
- **Scenario 2: Same customer scans Driver B 10 days later, before placing any order.**
  * **Rule (Last-Touch for Unconverted Leads):** If the customer has not yet confirmed an order, scanning Driver B's QR code shifts attribution to Driver B (since Driver B was the proximate ride that brought the customer in to place the order).
- **Scenario 3: Customer already completed an order with Driver A, and 2 months later scans Driver B.**
  * Result: The new scan opens a new 30-day attribution window credited to Driver B for any subsequent orders.
- **Scenario 4: Duplicate scans by the same user on the same driver within 24 hours.**
  * Result: **Idempotent.** Do not create duplicate leads. Log the scan in `attribution_logs` and refresh the lead's `last_interaction_at` timestamp.

### Policy C: Direct Customers (No QR Code / Raw `/start`)
- If a customer opens the Telegram bot without a `start` parameter (direct search), attribution is marked as `DIRECT / ORGANIC` (driver_id = NULL).
- No commission is calculated or owed.

---

## 5. Fraud Prevention & Audit Logging

1. **Self-Referral Prevention:**
   - Drivers are registered with their phone number and (if provided) their personal Telegram ID.
   - If a driver scans their own QR code using their registered Telegram account, the system logs the interaction but marks attribution as `SELF_SCAN_EXCLUDED`.

2. **Immutable Attribution Logging:**
   - Every scan update received from Telegram creates an append-only row in `attribution_logs`:
     - `telegram_user_id`
     - `referral_qr_code_id`
     - `driver_id`
     - `raw_payload`
     - `ip_address` (if web redirect used)
     - `created_at`
   - Even if a lead or order is subsequently modified or cancelled, the raw attribution log remains immutable.

3. **Commission Rate Locking:**
   - Commission rates are snapshotted onto the `orders` and `commissions` tables at the time of order creation.
   - Any future change to a driver's base commission rate or global company settings will never retroactively modify previously created orders.
