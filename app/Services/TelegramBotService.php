<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReferralCode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramBotService
{
    protected ?string $botToken;
    protected string $botUsername;
    protected ?string $webhookSecret;
    protected string $supportUsername;
    protected bool $verifySsl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->botUsername = ltrim((string) config('services.telegram.bot_username', 'DinCoTranslationBot'), '@');
        $this->webhookSecret = config('services.telegram.webhook_secret');
        $this->supportUsername = ltrim((string) config('services.telegram.support_username', 'DinCoSupport'), '@');
        $this->verifySsl = (bool) config('services.telegram.verify_ssl', true);
    }

    public function setVerifySsl(bool $verify): self
    {
        $this->verifySsl = $verify;

        return $this;
    }

    /**
     * Create configured HTTP client instance.
     */
    protected function httpClient(int $timeout = 10): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::timeout($timeout);

        if (! $this->verifySsl) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * Verify whether the incoming webhook secret token matches configuration.
     */
    public function verifyWebhookSecret(?string $receivedSecret): bool
    {
        // If no secret configured in environment, permit (local development)
        if (empty($this->webhookSecret)) {
            return true;
        }

        if (empty($receivedSecret)) {
            return false;
        }

        return hash_equals($this->webhookSecret, $receivedSecret);
    }

    /**
     * Send an arbitrary message via Telegram Bot API safely.
     * Guaranteed to never throw uncaught exceptions or break caller database transactions.
     *
     * @param  array<string, mixed>|null  $replyMarkup
     */
    public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null): bool
    {
        if (empty($this->botToken)) {
            Log::info('TelegramBotService: bot_token is not configured. Message simulated.', [
                'chat_id' => $chatId,
                'snippet' => mb_substr($text, 0, 80),
            ]);

            return true;
        }

        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];

            if ($replyMarkup !== null) {
                $payload['reply_markup'] = $replyMarkup;
            }

            $response = $this->httpClient(5)
                ->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", $payload);

            if (! $response->successful()) {
                Log::warning('TelegramBotService: Failed to deliver message to Telegram API', [
                    'status' => $response->status(),
                    'error' => $response->json('description'),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('TelegramBotService: Exception sending Telegram message', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send welcome message for a successfully verified referral scan.
     */
    public function sendWelcomeReferralMessage(int|string $chatId, ReferralCode $referralCode): bool
    {
        $companyName = config('app.name', 'Translation & Legalization Services');

        $message = "👋 <b>Welcome to {$companyName}!</b>\n";
        $message .= "សូមស្វាគមន៍មកកាន់សេវាកម្មបកប្រែឯកសារ!\n";
        $message .= "欢迎使用专业翻译与认证服务！\n\n";
        $message .= "✅ You were successfully referred via our driver partner network (Code: <code>{$referralCode->code}</code>).\n\n";
        $message .= "We specialize in:\n";
        $message .= "• Certified Legal & Business Document Translation\n";
        $message .= "• Visa & Embassy Document Translation\n";
        $message .= "• Driving License & Notarized Official Documents\n\n";
        $message .= "Our translation specialist has received your inquiry and will message you right here shortly to assist you with pricing and documents.";

        $supportUrl = 'https://t.me/'.$this->getSupportUsername();
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💬 Contact Support', 'url' => $supportUrl],
                ],
            ],
        ];

        return $this->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Send friendly response when user starts the bot with an invalid or unknown referral code.
     */
    public function sendInvalidReferralMessage(int|string $chatId): bool
    {
        $message = "👋 <b>Welcome to our Translation Service!</b>\n\n";
        $message .= "Sorry, this referral link is not recognized or is no longer valid.\n";
        $message .= "Our staff will be delighted to assist you directly.";

        $supportUrl = 'https://t.me/'.$this->getSupportUsername();
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💬 Chat With Staff', 'url' => $supportUrl],
                ],
            ],
        ];

        return $this->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Send friendly response when referral code is currently inactive.
     */
    public function sendDisabledReferralMessage(int|string $chatId): bool
    {
        $message = "👋 <b>Welcome to our Translation Service!</b>\n\n";
        $message .= "This referral promotion code is currently inactive.\n";
        $message .= "Please feel free to connect with our staff directly for any translation assistance.";

        $supportUrl = 'https://t.me/'.$this->getSupportUsername();
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💬 Chat With Staff', 'url' => $supportUrl],
                ],
            ],
        ];

        return $this->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Send friendly welcome when user starts the bot without a referral code (organic/direct).
     */
    public function sendDirectWelcomeMessage(int|string $chatId): bool
    {
        $companyName = (string) \App\Models\AppSetting::getValue('brand_name', config('app.name', 'DIN & CO Translation'));

        $message = "👋 <b>Welcome to {$companyName}!</b>\n\n";
        $message .= "How can we help you with your translation or notarization needs today?\n";
        $message .= "Please send us your inquiry or contact our support team below.";

        $supportUrl = 'https://t.me/'.$this->getSupportUsername();
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💬 Chat With Staff', 'url' => $supportUrl],
                ],
            ],
        ];

        return $this->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Send direct support contact message with interactive buttons.
     */
    public function sendSupportContactMessage(int|string $chatId): bool
    {
        $companyName = (string) \App\Models\AppSetting::getValue('brand_name', config('app.name', 'DIN & CO Translation'));
        $supportPhone = \App\Models\AppSetting::getValue('support_phone');
        $supportEmail = \App\Models\AppSetting::getValue('support_email');
        $supportHandle = $this->getSupportUsername();

        $message = "📞 <b>Contact {$companyName} Support</b>\n";
        $message .= "សូមទាក់ទងមកកាន់ផ្នែកបម្រើអតិថិជន!\n\n";
        $message .= "Our translation specialists are ready to assist you right now.\n\n";

        if ($supportHandle) {
            $message .= "💬 <b>Telegram:</b> @{$supportHandle}\n";
        }
        if ($supportPhone) {
            $message .= "📱 <b>Phone / Hotline:</b> {$supportPhone}\n";
        }
        if ($supportEmail) {
            $message .= "✉️ <b>Email:</b> {$supportEmail}\n";
        }

        $message .= "\nYou can also send your documents and photos directly into this chat, and our staff will message you shortly.";

        $buttons = [];
        if ($supportHandle) {
            $buttons[] = ['text' => '💬 Chat With Specialist', 'url' => "https://t.me/{$supportHandle}"];
        }
        if ($supportPhone) {
            $cleanPhone = preg_replace('/[^\d+]/', '', (string) $supportPhone);
            if ($cleanPhone) {
                $buttons[] = ['text' => '📞 Call Hotline', 'url' => "tel:{$cleanPhone}"];
            }
        }

        $keyboard = ! empty($buttons) ? ['inline_keyboard' => [$buttons]] : null;

        return $this->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Send acknowledgment when customer sends an inquiry or general message in chat.
     */
    public function sendCustomerInquiryAcknowledgment(int|string $chatId, string $customerMessage): bool
    {
        $companyName = (string) \App\Models\AppSetting::getValue('brand_name', config('app.name', 'DIN & CO Translation'));
        $supportHandle = $this->getSupportUsername();

        $message = "✅ <b>Thank you for your message!</b>\n";
        $message .= "សាររបស់អ្នកត្រូវបានបញ្ជូនទៅកាន់ក្រុមការងាររួចរាល់ហើយ!\n\n";
        $message .= "Our translation specialist has received your inquiry and will message you back right here shortly.\n\n";
        $message .= "If urgent, you can also reach out to our support team directly:";

        $buttons = [];
        if ($supportHandle) {
            $buttons[] = ['text' => '💬 Chat With Specialist', 'url' => "https://t.me/{$supportHandle}"];
        }

        $keyboard = ! empty($buttons) ? ['inline_keyboard' => [$buttons]] : null;

        return $this->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Register the webhook URL with Telegram Bot API.
     *
     * @return array<string, mixed>
     */
    public function setWebhook(string $url, ?string $secret = null): array
    {
        if (empty($this->botToken)) {
            return ['ok' => false, 'description' => 'TELEGRAM_BOT_TOKEN is not configured in .env'];
        }

        $params = ['url' => $url];
        $secretToken = $secret ?? $this->webhookSecret;
        if (! empty($secretToken)) {
            $params['secret_token'] = $secretToken;
        }

        try {
            $response = $this->httpClient(10)
                ->post("https://api.telegram.org/bot{$this->botToken}/setWebhook", $params);

            return $response->json() ?? ['ok' => false, 'description' => 'Invalid response from Telegram API'];
        } catch (Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Get information about the currently configured webhook.
     *
     * @return array<string, mixed>
     */
    public function getWebhookInfo(): array
    {
        if (empty($this->botToken)) {
            return ['ok' => false, 'description' => 'TELEGRAM_BOT_TOKEN is not configured in .env'];
        }

        try {
            $response = $this->httpClient(10)
                ->get("https://api.telegram.org/bot{$this->botToken}/getWebhookInfo");

            return $response->json() ?? ['ok' => false, 'description' => 'Invalid response from Telegram API'];
        } catch (Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Remove the webhook from Telegram Bot API.
     *
     * @return array<string, mixed>
     */
    public function deleteWebhook(bool $dropPendingUpdates = false): array
    {
        if (empty($this->botToken)) {
            return ['ok' => false, 'description' => 'TELEGRAM_BOT_TOKEN is not configured in .env'];
        }

        try {
            $response = $this->httpClient(10)
                ->post("https://api.telegram.org/bot{$this->botToken}/deleteWebhook", [
                    'drop_pending_updates' => $dropPendingUpdates,
                ]);

            return $response->json() ?? ['ok' => false, 'description' => 'Invalid response from Telegram API'];
        } catch (Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Fetch updates directly via Telegram getUpdates (long polling).
     *
     * @return array<string, mixed>
     */
    public function getUpdates(int $offset = 0, int $limit = 100, int $timeout = 10): array
    {
        if (empty($this->botToken)) {
            return ['ok' => false, 'description' => 'TELEGRAM_BOT_TOKEN is not configured in .env'];
        }

        try {
            $response = $this->httpClient($timeout + 15)
                ->get("https://api.telegram.org/bot{$this->botToken}/getUpdates", [
                    'offset' => $offset,
                    'limit' => $limit,
                    'timeout' => $timeout,
                ]);

            return $response->json() ?? ['ok' => false, 'description' => 'Invalid response from Telegram API'];
        } catch (Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    public function getBotUsername(): string
    {
        return $this->botUsername;
    }

    public function getSupportUsername(): string
    {
        $setting = \App\Models\AppSetting::getValue('telegram_support_username');
        if (! empty($setting)) {
            return ltrim((string) $setting, '@');
        }

        $config = config('services.telegram.support_username');
        if (! empty($config)) {
            return ltrim((string) $config, '@');
        }

        return $this->supportUsername;
    }
}
