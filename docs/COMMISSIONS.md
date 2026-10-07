# Phase 6: Commission Management & Driver Disbursements

## 1. Executive Summary & Core Business Rule

The **Commission Management System** automates and audits referral rewards earned by drivers and referral partners who introduce translation customers to the company.

### The Core Invariant
> **Source of Truth:** A commission is generated **exclusively** from an eligible, fully paid `Order` and its snapshot `referral_attribution_id`.
> - The attribution binding is established when the order is created or confirmed.
> - Any subsequent change to a customer's lead, referral code status, or driver association **never alters existing commission records**.
> - Financial rates are snapshotted at commission creation time. If a partner's commission policy rate changes later, existing earned commissions remain strictly untouched.

---

## 2. Commission Qualification Rules

An order qualifies for referral commission if and only if **all** of the following conditions are satisfied:

1. **Order Existence & Confirmation:** The order exists and is in `confirmed` or `completed` status (`OrderStatus::CONFIRMED` or `OrderStatus::COMPLETED`). Pending or cancelled orders never qualify.
2. **Referral Attribution Bound:** The order contains a valid `referral_attribution_id` linking to an active `ReferralAttribution` record. Walk-in or direct customers without attribution are ineligible.
3. **Positive Order Total:** Order `total_amount` must be greater than zero (`> 0.00`).
4. **Full Customer Payment:** The order must be **100% paid** (`order.isFullyPaid() === true`). Deposits or partial payments do not unlock driver commissions until the balance reaches $0.00.
5. **Strict Idempotency:** Exactly **one** commission record can exist per order (`UNIQUE(order_id)` constraint). Repeated evaluations return the existing commission.

### Ineligibility Reasons Matrix (`CommissionIneligibilityReason`)
| Code | Label | Resolution / Rule |
| :--- | :--- | :--- |
| `no_referral_attribution` | Direct or unattributed order | No referral code was scanned; no commission owed. |
| `order_not_confirmed` | Order is still pending quote/intake | Must be confirmed by staff before commission accrues. |
| `order_cancelled` | Order was cancelled | No fulfillment took place; zero commission. |
| `order_not_fully_paid` | Order unpaid or partially paid | Driver commission unlocks only upon 100% payment collection. |
| `order_total_zero` | Zero dollar order | Complimentary or void order; zero commission. |
| `commission_already_exists` | Commission already generated | Guarded by database unique constraint. |
| `referral_attribution_invalid` | Referral attribution cancelled | Attribution was flagged as fraudulent or superseded. |

---

## 3. Financial Calculation & Rounding

- **Formula:**
  $$\text{Commission Amount} = \text{round}\left(\frac{\text{Eligible Amount} \times \text{Commission Rate}}{100}, 2\right)$$
- **Precision:**
  - `commission_rate`: `DECIMAL(5, 2)` (e.g., `10.00`%)
  - `eligible_amount`: `DECIMAL(12, 2)` (snapshotted order total)
  - `commission_amount`: `DECIMAL(12, 2)` (stored in USD)
- **Currency:** USD is the primary accounting currency.

---

## 4. Commission Lifecycle & State Transitions

```text
[Order Fully Paid]
        │
        ▼
    ( EARNED )  ───────────────► ( REVERSED )
        │                               ▲
        ▼ (Approve by Finance/Admin)    │
   ( APPROVED ) ────────────────────────┘
        │
        ▼ (Payout Recorded by Cashier)
     ( PAID )
```

### Supported Statuses (`CommissionStatus`)
1. **`EARNED`:** Order reached 100% payment; commission automatically calculated and created.
2. **`APPROVED`:** Authorized by Finance or Admin (`commissions.approve`), verifying the translation order and authorizing cashier disbursement.
3. **`PAID`:** Cashier has handed over physical cash or initiated a bank transfer to the driver; a `CommissionPayment` record is created.
4. **`REVERSED`:** Either automatically reversed due to customer payment refund, or manually reversed by an administrator with a mandatory audit reason.

---

## 5. Automated Hooks & Integration

- **`PaymentService::recordPayment()`:**
  - Whenever a payment is recorded, the system checks if the order has transitioned to `isFullyPaid()`.
  - If fully paid and attributed, `CommissionService::evaluateAndEarnForOrder()` is triggered inside the database transaction.
- **`PaymentService::refundPayment()`:**
  - If an order payment is refunded, `CommissionService::handleOrderRefund()` is automatically triggered, transitioning any active commission to `REVERSED` and logging the audit reason.

---

## 6. Driver Payout Disbursement (`CommissionPayment`)

Driver payouts are recorded manually without third-party bank APIs:
- Supported payment methods: `cash`, `bank_transfer`, `other`.
- Auto-generated payout reference: `CPAY-YYYY-XXXXXX` (e.g., `CPAY-2026-000001`).
- Records recipient name (`paid_to`), disbursement date (`paid_at`), cashier user ID (`created_by`), and external voucher notes.

---

## 7. Role-Based Access Control (RBAC)

| Capability / Action | Super Admin | Admin | Finance | Staff |
| :--- | :---: | :---: | :---: | :---: |
| View Commissions List & Details | Yes | Yes | Yes | Yes (Read-only) |
| Manual Evaluate Commission | Yes | Yes | Yes | No |
| Approve Commission (`commissions.approve`) | Yes | Yes | Yes | No |
| Record Driver Payout (`commissions.pay`) | Yes | Yes | Yes | No |
| Reverse Commission (`commissions.reverse`) | Yes | Yes | Yes | No |
| View Commission Reports (`commissions.reports`)| Yes | Yes | Yes | No |
| Delete Commission Record | No | No | No | No |

---

## 8. Web Routes & Interfaces

- `GET /commissions` &mdash; Paginated commission dashboard with metrics summary cards and multi-column filters.
- `GET /commissions/{commission}` &mdash; Full commission details, financial breakdown, order attribution snapshot, audit timeline, and disbursement history.
- `PATCH /commissions/{commission}/approve` &mdash; Action to transition status to `APPROVED`.
- `POST /commissions/{commission}/payout` &mdash; Modal form to record cash/bank payout.
- `PATCH /commissions/{commission}/reverse` &mdash; Modal form to reverse a commission with audit reason.
- `POST /orders/{order}/evaluate-commission` &mdash; Button on Order detail to evaluate commission on demand.
- `GET /commissions/reports` &mdash; Analytical performance reports grouped by Referral Partner and Driver.
