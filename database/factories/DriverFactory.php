<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DriverStatus;
use App\Models\Driver;
use App\Models\ReferralPartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'referral_partner_id' => ReferralPartner::factory(),
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'national_id' => fake()->numerify('ID-#########'),
            'address' => fake()->address(),
            'notes' => fake()->sentence(),
            'status' => DriverStatus::ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => DriverStatus::INACTIVE,
        ]);
    }
}
