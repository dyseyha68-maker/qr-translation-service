<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TelegramUser;
use Carbon\Carbon;

class TelegramUserService
{
    /**
     * Find an existing Telegram user by external ID or create a new one.
     * Always updates changed metadata (username, names, language) and updates interaction time.
     *
     * @param  array<string, mixed>  $fromData
     */
    public function findOrCreate(array $fromData): TelegramUser
    {
        $telegramId = (int) ($fromData['id'] ?? 0);

        /** @var TelegramUser $user */
        $user = TelegramUser::firstOrNew(['telegram_id' => $telegramId]);

        $user->fill([
            'username' => isset($fromData['username']) ? (string) $fromData['username'] : null,
            'first_name' => isset($fromData['first_name']) ? (string) $fromData['first_name'] : null,
            'last_name' => isset($fromData['last_name']) ? (string) $fromData['last_name'] : null,
            'language_code' => isset($fromData['language_code']) ? (string) $fromData['language_code'] : null,
            'is_bot' => (bool) ($fromData['is_bot'] ?? false),
            'last_interaction_at' => Carbon::now(),
        ]);

        $user->save();

        return $user;
    }

    /**
     * Touch the last interaction timestamp of a Telegram user.
     */
    public function recordInteraction(TelegramUser $user): void
    {
        $user->update([
            'last_interaction_at' => Carbon::now(),
        ]);
    }
}
