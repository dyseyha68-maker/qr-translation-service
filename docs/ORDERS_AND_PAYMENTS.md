# ORDERS AND CUSTOMER PAYMENT TRACKING SPECIFICATION

**Module:** Phase 5 — Orders & Customer Payment Tracking  
**Status:** IMPLEMENTED & VERIFIED  
**Date:** October 2026  

---

## 1. Domain Overview & Purpose

Phase 5 bridges customer opportunity intake (Phase 4) with financial settlement and order fulfillment. It establishes the reliable tracking of:
1. **Orders**: Created from qualified leads with line items, server-calculated totals, and an immutable snapshot of driver referral attribution.
2. **Customer Payments**: Real-world cash, bank transfer, and manual KHQR inflows entered by front-desk staff and finance cashiers.
3. **Full Payment Determination**: Exact mathematical determination of when an order is settled, qualifying it for future commission evaluation in Phase 6.

```text
Lead (with qualified driver attribution)
  │
  ▼
Order (copies lead.referral_attribution_id permanently)
  │
  ├─▶ Line Items (Description, Quantity, Unit Price, Subtotal)
  ├─▶ Financials (Subtotal, Discount, Total Amount)
  │
  ▼
Customer Payments (Transactions: Cash, Bank Transfer, KHQR Manual)
  │
  ▼
Order Settlement Calculation:
  Paid Amount = SUM(valid payments)
  Outstanding = MAX(total_amount - paid_amount, 0)
  State: UNPAID | PARTIAL | PAID | REFUNDED
  │
  ▼
Fully Paid Determination (isFullyPaid(): paid_amount >= total_amount)
  │
  ▼ (Phase 6 Target Trigger)
Eligible for Driver Referral Commission
```

---

## 2. Order Lifecycle & Status Workflow

Orders follow a controlled finite-state lifecycle represented by the `OrderStatus` enum:

```text
[ PENDING ] ────────▶ [ CONFIRMED ] ────────▶ [ COMPLETED ]
     │                     │
     ▼                     ▼
[ CANCELLED ]         [ CANCELLED ]
```

### Lifecycle Rules:
1. **PENDING**: Initial state upon creation from a lead. Staff can edit items, apply validated discounts, or cancel if the customer changes their mind.
2. **CONFIRMED**: Work on translation has begun. Payments can be recorded against confirmed orders.
3. **COMPLETED**: Translation service has been fully fulfilled and delivered to the customer. Completed orders are immutable historical records.
4. **CANCELLED**: Order voided. Allowed only from `PENDING` or `CONFIRMED`.
   - **Protection Rule**: An order *cannot* be cancelled if active (non-refunded) customer payments exist (`paid_amount > 0`). Payments must be refunded first.
   - **Terminal State**: `COMPLETED` orders cannot be cancelled or reverted to pending.

---

## 3. Critical Referral Attribution Preservation

Every order created from a lead permanently preserves the referral attribution:

```text
order.customer_id = lead.customer_id
order.lead_id = lead.id
order.referral_attribution_id = lead.referral_attribution_id
```

### Strict Security Invariants:
1. **Server-Side Enforcement**: The backend controller and `OrderService` determine `referral_attribution_id` exclusively from `lead.referral_attribution_id`.
2. **Zero Client Trust**: Any client-submitted `referral_attribution_id` is discarded during validation.
3. **Historical Immutability**: If a lead's active attribution is manually changed or reassigned *after* an order has been created, the order's `referral_attribution_id` remains unchanged. The historical record reflects the referral partner and driver that produced that specific commercial order.
4. **Duplicate Prevention**: Only one active (non-cancelled) order can exist per lead.

---

## 4. Payment Domain & Settlement Calculations

Customer payments are represented by the `Payment` model.

### Payment Methods (`PaymentMethod`):
- `cash`: Physical USD cash received at the reception desk.
- `bank_transfer`: Direct bank wire / ABA account transfer.
- `khqr_manual`: Customer scanned a physical counter KHQR code, verified by staff on mobile banking before manual entry.
- `other`: Specialized payment arrangements.

*(Note: Automated KHQR/Bakong or ABA API integrations belong outside Phase 5).*

### Calculation Formulas:
- **Valid Payments**: Any payment record where `status === 'paid'`.
- **Paid Amount**:
  $$\text{paid\_amount} = \sum_{\text{valid payments}} \text{amount}$$
- **Outstanding Amount**:
  $$\text{outstanding\_amount} = \max(0.00, \text{round}(\text{total\_amount} - \text{paid\_amount}, 2))$$

### Derived Order Payment States (`PaymentStatus`):
| Payment Status | Condition |
| :--- | :--- |
| `UNPAID` | `paid_amount == 0` and no payments exist |
| `PARTIAL` | `0 < paid_amount < total_amount` |
| `PAID` | `paid_amount >= total_amount` and `total_amount > 0` |
| `REFUNDED` | Payments exist and 100% of payment records are `status === 'refunded'` |

### Full Payment Rule (`isFullyPaid()`):
An order is considered fully paid when:
$$\text{paid\_amount} \ge \text{total\_amount} \quad \text{and} \quad \text{total\_amount} > 0.00$$

---

## 5. Overpayment Prevention & Concurrency Safety

1. **No Overpayments**: `PaymentService` rejects any payment where $\text{amount} > \text{outstanding\_amount}$.
2. **Zero-Balance Locking**: Once an order has $\text{outstanding\_amount} \le 0.0001$, no further payment records may be submitted.
3. **Concurrency Protection**: During payment recording, `Order::lockForUpdate()` is held inside a database transaction to prevent race conditions from concurrent staff submissions.
4. **Reference Uniqueness**: Payment references (`PAY-YYYY-XXXXXX` or custom manual receipts) are unique in the database.

---

## 6. Financial History & Refund Preservation

Financial transaction records are **never hard-deleted**.
- When a payment must be reversed, an authorized finance user or admin records a refund.
- The payment record is updated with:
  - `status = 'refunded'`
  - `refunded_at = now()`
  - `refund_reason = text`
  - `refunded_by = user_id`
- Re-calculating the order immediately decreases `paid_amount` by the refunded sum. If the order was previously `PAID`, it immediately reverts to `PARTIAL` or `UNPAID` / `REFUNDED`.

---

## 7. Role-Based Access Control (RBAC)

| Role | View Orders | Create Order | Update / Confirm / Complete | Record Payment | Refund Payment |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Super Admin** | Yes | Yes | Yes | Yes | Yes |
| **Admin** | Yes | Yes | Yes | Yes | Yes |
| **Staff** | Yes | Yes | Yes | Yes | No |
| **Finance** | Yes | No | No | Yes | Yes |

- **Staff**: Can intake leads, create orders, and record initial deposits/counter payments, but cannot refund payments.
- **Finance**: Can audit orders, verify payment logs, record payments, and process authorized refunds, but does not alter customer pipeline leads.
