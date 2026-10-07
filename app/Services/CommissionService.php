<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CommissionIneligibilityReason;
use App\Enums\CommissionPaymentMethod;
use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Enums\ReferralAttributionStatus;
use App\Models\Commission;
use App\Models\CommissionPayment;
use App\Models\Order;
use App\Models\ReferralAttribution;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionService
{
    /**
     * Generate a unique sequential commission reference (e.g. COM-2026-000001).
     */
    public function generateCommissionReference(): string
    {
        $year = Carbon::now()->format('Y');
        $prefix = "COM-{$year}-";

        return DB::transaction(function () use ($prefix) {
            $latest = Commission::where('commission_reference', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $sequence = 1;
            if ($latest && preg_match('/-(\d+)$/', $latest->commission_reference, $matches)) {
                $sequence = (int) $matches[1] + 1;
            }

            return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Generate a unique sequential commission payout reference (e.g. CPAY-2026-000001).
     */
    public function generatePaymentReference(): string
    {
        $year = Carbon::now()->format('Y');
        $prefix = "CPAY-{$year}-";

        return DB::transaction(function () use ($prefix) {
            $latest = CommissionPayment::where('payment_reference', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $sequence = 1;
            if ($latest && preg_match('/-(\d+)$/', $latest->payment_reference, $matches)) {
                $sequence = (int) $matches[1] + 1;
            }

            return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Evaluate whether an order qualifies for commission earning according to business invariants.
     *
     * @return array{eligible: bool, reason: ?CommissionIneligibilityReason}
     */
    public function checkEligibility(Order $order): array
    {
        // 1. One order = at most one commission
        if ($order->relationLoaded('commission') && $order->commission !== null) {
            return [
                'eligible' => false,
                'reason' => CommissionIneligibilityReason::COMMISSION_ALREADY_EXISTS,
            ];
        }

        if (Commission::where('order_id', $order->id)->exists()) {
            return [
                'eligible' => false,
                'reason' => CommissionIneligibilityReason::COMMISSION_ALREADY_EXISTS,
            ];
        }

        // 2. Order must have a referral attribution
        if (! $order->referral_attribution_id) {
            return [
                'eligible' => false,
                'reason' => CommissionIneligibilityReason::NO_REFERRAL_ATTRIBUTION,
            ];
        }

        // 3. Order status check
        if ($order->status === OrderStatus::CANCELLED) {
            return [
                'eligible' => false,
                'reason' => CommissionIneligibilityReason::ORDER_CANCELLED,
            ];
        }

        if ($order->status === OrderStatus::PENDING) {
            return [
                'eligible' => false,
                'reason' => CommissionIneligibilityReason::ORDER_NOT_CONFIRMED,
            ];
        }

        // 4. Order total > 0
        if ((float) $order->total_amount <= 0.0) {
            return [
                'eligible' => false,
                'reason' => CommissionIneligibilityReason::ORDER_TOTAL_ZERO,
            ];
        }

        // 5. Must be fully paid
        if (! $order->isFullyPaid()) {
            return [
                'eligible' => false,
                'reason' => CommissionIneligibilityReason::ORDER_NOT_FULLY_PAID,
            ];
        }

        // 6. Referral attribution validity
        $attribution = $order->referralAttribution;
        if (! $attribution || $attribution->attribution_status === ReferralAttributionStatus::CANCELLED) {
            return [
                'eligible' => false,
                'reason' => CommissionIneligibilityReason::REFERRAL_ATTRIBUTION_INVALID,
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
        ];
    }

    /**
     * Evaluate an order and create an EARNED commission record if eligible.
     * Strict idempotency and concurrency locking ensure at most one commission is created.
     */
    public function evaluateAndEarnForOrder(Order $order, ?User $actor = null): ?Commission
    {
        return DB::transaction(function () use ($order, $actor) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)
                ->with(['payments', 'referralAttribution.referralPartner', 'referralAttribution.driver', 'referralAttribution.vehicle'])
                ->lockForUpdate()
                ->firstOrFail();

            // Strict idempotency: if commission already exists for this order, return it
            $existing = Commission::where('order_id', $lockedOrder->id)->first();
            if ($existing) {
                return $existing;
            }

            $eligibility = $this->checkEligibility($lockedOrder);
            if (! $eligibility['eligible']) {
                return null;
            }

            /** @var ReferralAttribution $attribution */
            $attribution = $lockedOrder->referralAttribution;

            // Partner commission rate at time of order earning (historical snapshot)
            $commissionRate = (float) ($attribution->referralPartner?->commission_rate ?? 10.00);
            $eligibleAmount = round((float) $lockedOrder->total_amount, 2);
            $commissionAmount = round($eligibleAmount * $commissionRate / 100, 2);

            $reference = $this->generateCommissionReference();

            return Commission::create([
                'commission_reference' => $reference,
                'order_id' => $lockedOrder->id,
                'referral_attribution_id' => $attribution->id,
                'referral_partner_id' => $attribution->referral_partner_id,
                'driver_id' => $attribution->driver_id,
                'vehicle_id' => $attribution->vehicle_id,
                'commission_rate' => $commissionRate,
                'eligible_amount' => $eligibleAmount,
                'commission_amount' => $commissionAmount,
                'currency' => $lockedOrder->currency,
                'status' => CommissionStatus::EARNED,
                'earned_at' => Carbon::now(),
                'created_by' => $actor?->id,
            ]);
        });
    }

    /**
     * Transition a commission from EARNED to APPROVED.
     *
     * @throws ValidationException
     */
    public function approveCommission(Commission $commission, User $approver): Commission
    {
        return DB::transaction(function () use ($commission, $approver) {
            /** @var Commission $locked */
            $locked = Commission::where('id', $commission->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(CommissionStatus::APPROVED)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot approve commission with status '{$locked->status->value}'. Only EARNED commissions can be approved.",
                ]);
            }

            $locked->update([
                'status' => CommissionStatus::APPROVED,
                'approved_at' => Carbon::now(),
                'approved_by' => $approver->id,
            ]);

            return $locked->fresh(['order', 'referralPartner', 'driver', 'vehicle', 'approver']);
        });
    }

    /**
     * Record disbursement/payout of an APPROVED commission to the partner/driver.
     * Transitions commission status to PAID.
     *
     * @param array{
     *     payment_method: CommissionPaymentMethod|string,
     *     payment_reference?: ?string,
     *     paid_at?: ?string,
     *     paid_to?: ?string,
     *     notes?: ?string
     * } $data
     * @throws ValidationException
     */
    public function recordPayout(Commission $commission, array $data, User $payer): CommissionPayment
    {
        return DB::transaction(function () use ($commission, $data, $payer) {
            /** @var Commission $locked */
            $locked = Commission::where('id', $commission->id)
                ->with(['driver', 'referralPartner'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== CommissionStatus::APPROVED) {
                throw ValidationException::withMessages([
                    'status' => "Commission must be APPROVED before payment can be recorded. Current status is '{$locked->status->value}'.",
                ]);
            }

            $reference = !empty($data['payment_reference'])
                ? trim((string) $data['payment_reference'])
                : $this->generatePaymentReference();

            if (CommissionPayment::where('payment_reference', $reference)->exists()) {
                throw ValidationException::withMessages([
                    'payment_reference' => "Payout reference '{$reference}' has already been used.",
                ]);
            }

            $method = $data['payment_method'] instanceof CommissionPaymentMethod
                ? $data['payment_method']
                : CommissionPaymentMethod::from((string) $data['payment_method']);

            $paidAt = !empty($data['paid_at'])
                ? Carbon::parse($data['paid_at'])
                : Carbon::now();

            $recipient = !empty($data['paid_to'])
                ? trim((string) $data['paid_to'])
                : ($locked->driver?->name ?? $locked->referralPartner?->name);

            $payment = CommissionPayment::create([
                'commission_id' => $locked->id,
                'payment_reference' => $reference,
                'amount' => $locked->commission_amount,
                'currency' => $locked->currency,
                'payment_method' => $method,
                'paid_at' => $paidAt,
                'paid_to' => $recipient,
                'notes' => $data['notes'] ?? null,
                'created_by' => $payer->id,
            ]);

            $locked->update([
                'status' => CommissionStatus::PAID,
                'paid_at' => Carbon::now(),
                'paid_by' => $payer->id,
            ]);

            return $payment;
        });
    }

    /**
     * Process an audit reversal for a commission.
     * Preserves financial and calculation history.
     *
     * @throws ValidationException
     */
    public function reverseCommission(Commission $commission, User $reverser, string $reason): Commission
    {
        return DB::transaction(function () use ($commission, $reverser, $reason) {
            /** @var Commission $locked */
            $locked = Commission::where('id', $commission->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === CommissionStatus::REVERSED) {
                throw ValidationException::withMessages([
                    'status' => 'This commission has already been reversed.',
                ]);
            }

            $locked->update([
                'status' => CommissionStatus::REVERSED,
                'reversed_at' => Carbon::now(),
                'reversal_reason' => trim($reason),
                'reversed_by' => $reverser->id,
            ]);

            return $locked->fresh(['order', 'referralPartner', 'driver', 'reverser']);
        });
    }

    /**
     * Handle commission adjustment when customer order refund occurs.
     */
    public function handleOrderRefund(Order $order, User $actor, string $reason): ?Commission
    {
        $commission = Commission::where('order_id', $order->id)->first();
        if (! $commission || $commission->status === CommissionStatus::REVERSED) {
            return null;
        }

        // If order is no longer fully paid, reverse commission with audit trail
        if (! $order->isFullyPaid()) {
            return $this->reverseCommission(
                commission: $commission,
                reverser: $actor,
                reason: "Reversed due to customer order refund: {$reason}"
            );
        }

        return null;
    }
}
