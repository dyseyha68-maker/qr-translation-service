<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ReferralAttributionService;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Throwable;

class TelegramPollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll
                            {--timeout=10 : Polling timeout in seconds}
                            {--drop-pending : Drop existing unhandled updates before starting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Telegram Bot in long-polling mode (ideal for localhost development and QR testing)';

    public function handle(TelegramBotService $botService, ReferralAttributionService $attributionService): int
    {
        $botUsername = $botService->getBotUsername();
        $this->info("🤖 Starting Telegram Polling for @{$botUsername}...");
        $this->warn('Press Ctrl+C to terminate the polling loop.');

        if ($this->option('drop-pending')) {
            $this->info('Dropping pending updates...');
            $botService->deleteWebhook(dropPendingUpdates: true);
        }

        $offset = 0;
        $timeout = (int) $this->option('timeout');

        while (true) {
            try {
                $response = $botService->getUpdates($offset, limit: 50, timeout: $timeout);

                if (! ($response['ok'] ?? false)) {
                    $this->error('Telegram API error: ' . ($response['description'] ?? 'Unknown error'));
                    sleep(2);
                    continue;
                }

                $updates = $response['result'] ?? [];

                foreach ($updates as $update) {
                    $updateId = $update['update_id'];
                    $offset = $updateId + 1;

                    $user = $update['message']['from']['first_name'] ?? 'User';
                    $text = $update['message']['text'] ?? '(no text)';

                    $this->line("<fg=cyan>[Update #{$updateId}]</> From: {$user} | Message: {$text}");

                    try {
                        $result = $attributionService->processWebhookUpdate($update);
                        $this->info("   ↳ Handled: {$result['event_type']} (Lead: " . ($result['lead_id'] ?? 'none') . ")");
                    } catch (Throwable $e) {
                        $this->error("   ↳ Processing exception: {$e->getMessage()}");
                    }
                }
            } catch (Throwable $e) {
                $this->error("Polling loop exception: {$e->getMessage()}");
                sleep(2);
            }
        }

        return self::SUCCESS;
    }
}
