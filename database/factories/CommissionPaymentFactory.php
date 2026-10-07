<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommissionPaymentMethod;
use App\Models\Commission;
use App\Models\CommissionPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionPayment>
 */
class CommissionPaymentFactory extends Factory
{
    protected $model = CommissionPayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commission_id' => Commission::factory()->approved(),
            'payment_reference' => 'CPAY-' . Carbon::now()->format('Y') . '-' . fake()->unique()->numerify('######'),
            'amount' => 10.00,
            'payment_method' => CommissionPaymentMethod::CASH,
            'paid_at' => Carbon::now(),
            'paid_to' => fake()->name(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function bankTransfer(): static
    {
        return $this->state(fn () => [
            'payment_method' => CommissionPaymentMethod::BANK_TRANSFER,
        ]);
    }
}
