<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver_id' => Driver::factory(),
            'vehicle_type' => fake()->randomElement(VehicleType::cases()),
            'plate_number' => strtoupper(fake()->bothify('PP-#??-####')),
            'make' => fake()->randomElement(['Toyota', 'Bajaj', 'Piaggio', 'Honda', 'Hyundai']),
            'model' => fake()->randomElement(['Prius', 'RE Maxima', 'Ape City', 'Corolla', 'Camry']),
            'color' => fake()->safeColorName(),
            'notes' => fake()->sentence(),
            'status' => VehicleStatus::ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => VehicleStatus::INACTIVE,
        ]);
    }
}
