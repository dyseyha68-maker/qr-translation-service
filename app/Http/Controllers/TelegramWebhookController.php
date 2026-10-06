<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReferralAttributionService;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramBotService $botService,
        protected ReferralAttributionService $attributionService,
    ) {}

    /**
     * Handle incoming Telegram bot webhook updates.
     */
    public function handle(Request $request): JsonResponse
    {
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (! $this->botService->verifyWebhookSecret($secretToken)) {
            Log::warning('Telegram webhook rejected: invalid secret token header.');

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        if (empty($payload)) {
            return response()->json(['ok' => true, 'status' => 'empty_payload']);
        }

        try {
            $result = $this->attributionService->processWebhookUpdate($payload);

            return response()->json([
                'ok' => true,
                'result' => $result,
            ]);
        } catch (Throwable $e) {
            Log::error('Telegram webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Always return HTTP 200 to Telegram to prevent repeated retry flooding, with ok: false internally
            return response()->json([
                'ok' => false,
                'error' => 'Internal processing error',
            ]);
        }
    }
}
