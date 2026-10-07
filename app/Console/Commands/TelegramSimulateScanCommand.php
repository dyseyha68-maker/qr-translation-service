<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ReferralCode;
use App\Services\ReferralAttributionService;
use Illuminate\Console\Command;

class TelegramSimulateScanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:simulate-scan
                            {code? : The referral code scanned (e.g. REF_TK874M)}
                            {--telegram-id= : Custom Telegram user ID}
                            {--name=John Doe : Customer display name}
                            {--username=johndoe : Telegram username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate a customer scanning a driver QR code in Telegram';

    public function handle(ReferralAttributionService $attributionService): int
    {
        $code = $this->argument('code');

        if (empty($code)) {
            $referralCode = ReferralCode::with(['driver', 'vehicle'])->active()->first();
            if (! $referralCode) {
                $this->error('No active referral code found in database. Create one first or pass a code argument.');

                return self::FAILURE;
            }
            $code = $referralCode->code;
            $this->info("Using existing active referral code: {$code} (Driver: {$referralCode->driver->name}, Vehicle: {$referralCode->vehicle->plate_number})");
        }

        $telegramId = (int) ($this->option('telegram-id') ?: random_int(100000000, 999999999));
        $fullName = (string) $this->option('name');
        $username = (string) $this->option('username');

        $nameParts = explode(' ', trim($fullName), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? 'Passenger';

        $updateId = random_int(10000000, 99999999);

        $this->info("Simulating QR Scan for code [{$code}] by {$fullName} (@{$username}, ID: {$telegramId})...");

        $mockUpdate = [
            'update_id' => $updateId,
            'message' => [
                'message_id' => random_int(1000, 9999),
                'from' => [
                    'id' => $telegramId,
                    'is_bot' => false,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'username' => $username,
                    'language_code' => 'en',
                ],
                'chat' => [
                    'id' => $telegramId,
                    'type' => 'private',
                    'first_name' => $firstName,
                    'username' => $username,
                ],
                'date' => time(),
                'text' => "/start {$code}",
            ],
        ];

        $result = $attributionService->processWebhookUpdate($mockUpdate);

        $outcome = $result['outcome'] ?? $result['status'] ?? 'unknown';

        $this->info("✅ Scan processed successfully! Outcome: {$outcome}");

        if (isset($result['lead'])) {
            $this->line("   Lead ID: #{$result['lead']->id} ({$result['lead']->title})");
        }
        if (isset($result['event'])) {
            $this->line("   Referral Event ID: #{$result['event']->id} ({$result['event']->event_type->value})");
        }

        return self::SUCCESS;
    }
}
