<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReferralCode;

class TelegramReferralLinkService
{
    /**
     * Get the configured Telegram Bot username.
     */
    public function getBotUsername(): string
    {
        $botUsername = (string) config('services.telegram.bot_username', 'DinCoTranslationBot');

        return ltrim(trim($botUsername), '@');
    }

    /**
     * Build the Telegram deep link for a referral code string.
     */
    public function buildForCode(string $code): string
    {
        $bot = $this->getBotUsername();
        $cleanCode = strtoupper(trim($code));

        return "https://t.me/{$bot}?start={$cleanCode}";
    }

    /**
     * Build the Telegram deep link for a ReferralCode model instance.
     */
    public function buildForReferralCode(ReferralCode $referralCode): string
    {
        return $this->buildForCode($referralCode->code);
    }
}
