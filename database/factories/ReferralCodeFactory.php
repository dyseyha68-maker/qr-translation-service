<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReferralCodeStatus;
use App\Models\Driver;
use App\Models\ReferralCode;
use App\Models\ReferralPartner;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReferralCode>
 */
class ReferralCodeFactory extends Factory
{
    protected $model = ReferralCode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'REF-' . strtoupper(Str::random(6)),
            'referral_partner_id' => ReferralPartner::factory(),
            'driver_id' => function (array $attributes) {
                return Driver::factory()->create([
                    'referral_partner_id' => $attributes['referral_partner_id'],
                ])->id;
            },
            'vehicle_id' => function (array $attributes) {
                return Vehicle::factory()->create([
                    'driver_id' => $attributes['driver_id'],
                ])->id;
            },
            'status' => ReferralCodeStatus::ACTIVE,
            'label' => fake()->optional()->words(3, true),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'deactivated_at' => null,
        ];
    }

    /**
     * Indicate that the referral code is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReferralCodeStatus::INACTIVE,
            'deactivated_at' => now(),
        ]);
    }
}
