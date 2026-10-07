<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook 
                            {--set : Register the webhook URL with Telegram}
                            {--delete : Remove the webhook from Telegram}
                            {--info : Display the current webhook status (default)}
                            {--url= : Explicit webhook URL (defaults to APP_URL/api/telegram/webhook)}
                            {--insecure : Bypass SSL certificate verification for local development}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect, set, or delete the Telegram Bot webhook';

    public function handle(TelegramBotService $botService): int
    {
        if ($this->option('insecure') || ! config('services.telegram.verify_ssl', true)) {
            $botService->setVerifySsl(false);
            $this->warn('⚠️ SSL verification bypassed for local development.');
        }

        $botUsername = $botService->getBotUsername();
        $this->info("🤖 Telegram Bot: @{$botUsername}");

        if ($this->option('set')) {
            $url = $this->option('url') ?: url('/api/telegram/webhook');

            if (! str_starts_with($url, 'https://')) {
                $this->error('Telegram Webhook URL must use HTTPS (e.g. https://yourdomain.com/api/telegram/webhook)');
                $this->warn('For local development on localhost, use "php artisan telegram:poll" instead of webhooks.');

                return self::FAILURE;
            }

            $this->info("Registering webhook: {$url}...");
            $response = $botService->setWebhook($url);

            if ($response['ok'] ?? false) {
                $this->info('✅ Webhook registered successfully!');
                $this->line(json_encode($response, JSON_PRETTY_PRINT));

                return self::SUCCESS;
            }

            $this->error('Failed to set webhook: ' . ($response['description'] ?? 'Unknown error'));
            $this->line(json_encode($response, JSON_PRETTY_PRINT));

            return self::FAILURE;
        }

        if ($this->option('delete')) {
            $this->info('Removing webhook from Telegram...');
            $response = $botService->deleteWebhook();

            if ($response['ok'] ?? false) {
                $this->info('✅ Webhook deleted successfully.');
                $this->line(json_encode($response, JSON_PRETTY_PRINT));

                return self::SUCCESS;
            }

            $this->error('Failed to delete webhook: ' . ($response['description'] ?? 'Unknown error'));

            return self::FAILURE;
        }

        // Default: display webhook info
        $this->info('Querying Telegram Webhook Status...');
        $info = $botService->getWebhookInfo();

        if ($info['ok'] ?? false) {
            $data = $info['result'] ?? [];
            $this->table(
                ['Property', 'Value'],
                [
                    ['URL', $data['url'] ?: '(None - webhook not configured)'],
                    ['Custom Certificate', $data['has_custom_certificate'] ? 'Yes' : 'No'],
                    ['Pending Update Count', $data['pending_update_count'] ?? 0],
                    ['Last Error Date', isset($data['last_error_date']) ? date('Y-m-d H:i:s', $data['last_error_date']) : 'None'],
                    ['Last Error Message', $data['last_error_message'] ?? 'None'],
                    ['Max Connections', $data['max_connections'] ?? 40],
                ]
            );

            return self::SUCCESS;
        }

        $this->error('Could not retrieve webhook info: ' . ($info['description'] ?? 'Unknown error'));

        return self::FAILURE;
    }
}
