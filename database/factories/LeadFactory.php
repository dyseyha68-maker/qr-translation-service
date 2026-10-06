<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'telegram_user_id' => function (array $attributes) {
                return Customer::find($attributes['customer_id'])?->telegram_user_id;
            },
            'title' => 'Translation Inquiry - ' . fake()->words(2, true),
            'description' => fake()->optional()->paragraph(),
            'service_type' => fake()->randomElement(['Legal Translation', 'Visa Document', 'Notarized Contract']),
            'status' => LeadStatus::NEW,
            'assigned_to' => null,
            'source' => LeadSource::TELEGRAM_REFERRAL,
        ];
    }
}
