<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommissionStatus;
use App\Models\Commission;
use App\Models\Driver;
use App\Models\Order;
use App\Models\ReferralAttribution;
use App\Models\ReferralPartner;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commission>
 */
class CommissionFactory extends Factory
{
    protected $model = Commission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eligibleAmount = 100.00;
        $commissionRate = 10.00;
        $commissionAmount = 10.00;

        return [
            'order_id' => Order::factory(),
            'referral_attribution_id' => ReferralAttribution::factory(),
            'referral_partner_id' => ReferralPartner::factory(),
            'driver_id' => Driver::factory(),
            'vehicle_id' => Vehicle::factory(),
            'commission_reference' => 'COM-' . Carbon::now()->format('Y') . '-' . fake()->unique()->numerify('######'),
            'eligible_amount' => $eligibleAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'status' => CommissionStatus::EARNED,
            'earned_at' => Carbon::now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => CommissionStatus::PENDING,
            'earned_at' => null,
        ]);
    }

    public function earned(): static
    {
        return $this->state(fn () => [
            'status' => CommissionStatus::EARNED,
            'earned_at' => Carbon::now(),
        ]);
    }

    public function approved(?User $approver = null): static
    {
        return $this->state(fn () => [
            'status' => CommissionStatus::APPROVED,
            'approved_at' => Carbon::now(),
            'approved_by' => $approver?->id ?? User::factory(),
        ]);
    }

    public function paid(?User $payer = null): static
    {
        return $this->state(fn () => [
            'status' => CommissionStatus::PAID,
            'approved_at' => Carbon::now()->subHour(),
            'approved_by' => User::factory(),
            'paid_at' => Carbon::now(),
            'paid_by' => $payer?->id ?? User::factory(),
        ]);
    }

    public function reversed(string $reason = 'Order refunded', ?User $reverser = null): static
    {
        return $this->state(fn () => [
            'status' => CommissionStatus::REVERSED,
            'reversed_at' => Carbon::now(),
            'reversed_by' => $reverser?->id ?? User::factory(),
            'reversal_reason' => $reason,
        ]);
    }
}
