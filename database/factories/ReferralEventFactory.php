<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TelegramEventType;
use App\Models\ReferralCode;
use App\Models\ReferralEvent;
use App\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralEvent>
 */
class ReferralEventFactory extends Factory
{
    protected $model = ReferralEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'referral_code_id' => ReferralCode::factory(),
            'telegram_user_id' => TelegramUser::factory(),
            'event_type' => TelegramEventType::SCAN,
            'event_token' => 'REF-' . fake()->lexify('??????'),
            'telegram_update_id' => fake()->unique()->numberBetween(1000000, 9999999),
            'metadata' => null,
            'occurred_at' => now(),
        ];
    }
}
