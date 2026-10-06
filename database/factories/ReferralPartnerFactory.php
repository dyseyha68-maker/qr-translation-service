<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PartnerStatus;
use App\Enums\PartnerType;
use App\Models\ReferralPartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralPartner>
 */
class ReferralPartnerFactory extends Factory
{
    protected $model = ReferralPartner::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(PartnerType::cases()),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'company_name' => fake()->company(),
            'address' => fake()->address(),
            'notes' => fake()->sentence(),
            'status' => PartnerStatus::ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => PartnerStatus::INACTIVE,
        ]);
    }
}
