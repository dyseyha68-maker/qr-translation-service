<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CustomerStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\ReferralAttributionStatus;
use App\Enums\ReferralCodeStatus;
use App\Enums\TelegramEventType;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\ReferralAttribution;
use App\Models\ReferralCode;
use App\Models\ReferralEvent;
use App\Models\TelegramUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ReferralAttributionService
{
    public function __construct(
        protected TelegramBotService $botService,
        protected TelegramUserService $telegramUserService,
    ) {}

    /**
     * Process an incoming Telegram webhook update payload.
     * Enforces webhook idempotency and the "FIRST QUALIFIED REFERRAL WINS" model.
     *
     * @param  array<string, mixed>  $update
     * @return array<string, mixed>
     */
    public function processWebhookUpdate(array $update): array
    {
        $updateId = isset($update['update_id']) ? (int) $update['update_id'] : null;

        // Idempotency: Ignore duplicate Telegram updates
        if ($updateId !== null && ReferralEvent::where('telegram_update_id', $updateId)->exists()) {
            Log::info('Telegram update duplicate ignored', ['update_id' => $updateId]);

            return [
                'status' => 'duplicate',
                'message' => 'Update already processed',
                'update_id' => $updateId,
            ];
        }

        $message = $update['message'] ?? null;
        if (! is_array($message)) {
            return ['status' => 'ignored', 'reason' => 'No message object'];
        }

        $from = $message['from'] ?? null;
        if (! is_array($from)) {
            return ['status' => 'ignored', 'reason' => 'No sender object'];
        }

        $chatId = $message['chat']['id'] ?? $from['id'] ?? null;
        $text = trim((string) ($message['text'] ?? ''));

        // Register or update Telegram user
        $telegramUser = $this->telegramUserService->findOrCreate($from);

        // Parse /start command
        if (! str_starts_with($text, '/start')) {
            if ($text === '/help' || $text === '/contact') {
                $this->botService->sendSupportContactMessage($chatId);

                return ['status' => 'help_sent'];
            }

            return ['status' => 'unsupported_command'];
        }

        // Extract referral token: e.g. /start REF_TK9821ABC
        preg_match('/^\/start(?:\s+([A-Za-z0-9_-]+))?$/', $text, $matches);
        $token = isset($matches[1]) ? trim($matches[1]) : null;

        // Scenario A: Direct inquiry (/start without code)
        if ($token === null || $token === '') {
            $this->recordReferralEvent(
                referralCodeId: null,
                telegramUserId: $telegramUser->id,
                eventType: TelegramEventType::START,
                eventToken: null,
                updateId: $updateId
            );

            $this->botService->sendDirectWelcomeMessage($chatId);

            return ['status' => 'direct_welcome_sent'];
        }

        // Scenario B: Referral token provided
        $normalizedToken = strtoupper($token);

        /** @var ReferralCode|null $referralCode */
        $referralCode = ReferralCode::with(['referralPartner', 'driver', 'vehicle'])
            ->where('code', $normalizedToken)
            ->first();

        // 1. Unknown / non-existent code
        if (! $referralCode) {
            $this->recordReferralEvent(
                referralCodeId: null,
                telegramUserId: $telegramUser->id,
                eventType: TelegramEventType::UNKNOWN_CODE,
                eventToken: $normalizedToken,
                updateId: $updateId
            );

            $this->botService->sendInvalidReferralMessage($chatId);

            return ['status' => 'unknown_referral_code'];
        }

        // 2. Inactive code
        if ($referralCode->status !== ReferralCodeStatus::ACTIVE) {
            $this->recordReferralEvent(
                referralCodeId: $referralCode->id,
                telegramUserId: $telegramUser->id,
                eventType: TelegramEventType::INACTIVE_CODE,
                eventToken: $normalizedToken,
                updateId: $updateId
            );

            $this->botService->sendDisabledReferralMessage($chatId);

            return ['status' => 'inactive_referral_code'];
        }

        // 3. Active code: Validate ownership integrity
        if (
            $referralCode->driver->referral_partner_id !== $referralCode->referral_partner_id ||
            $referralCode->vehicle->driver_id !== $referralCode->driver_id
        ) {
            $this->recordReferralEvent(
                referralCodeId: $referralCode->id,
                telegramUserId: $telegramUser->id,
                eventType: TelegramEventType::INVALID_CODE,
                eventToken: $normalizedToken,
                updateId: $updateId
            );

            $this->botService->sendInvalidReferralMessage($chatId);

            return ['status' => 'invalid_ownership_hierarchy'];
        }

        // Execute attribution & lead resolution inside database transaction
        $result = DB::transaction(function () use (
            $referralCode,
            $telegramUser,
            $normalizedToken,
            $updateId
        ) {
            // Find or create Customer
            /** @var Customer $customer */
            $customer = Customer::firstOrCreate(
                ['telegram_user_id' => $telegramUser->id],
                [
                    'name' => $telegramUser->full_name,
                    'phone' => $telegramUser->phone,
                    'status' => CustomerStatus::ACTIVE,
                ]
            );

            // Check for existing active lead (FIRST QUALIFIED REFERRAL WINS)
            /** @var Lead|null $activeLead */
            $activeLead = Lead::where(function ($query) use ($customer, $telegramUser) {
                $query->where('customer_id', $customer->id)
                    ->orWhere('telegram_user_id', $telegramUser->id);
            })
                ->active()
                ->lockForUpdate()
                ->with(['referralAttribution.driver'])
                ->first();

            if ($activeLead !== null) {
                // Rule: Do NOT overwrite the existing active attribution!
                $event = $this->recordReferralEvent(
                    referralCodeId: $referralCode->id,
                    telegramUserId: $telegramUser->id,
                    eventType: TelegramEventType::REPEAT_SCAN,
                    eventToken: $normalizedToken,
                    updateId: $updateId
                );

                LeadNote::create([
                    'lead_id' => $activeLead->id,
                    'user_id' => $activeLead->assigned_to ?? 1,
                    'note' => "Repeat scan detected for code {$normalizedToken}. Existing attribution to driver {$activeLead->referralAttribution?->driver?->name} preserved.",
                ]);

                return [
                    'outcome' => 'repeat_scan_preserved',
                    'lead' => $activeLead,
                    'customer' => $customer,
                    'event' => $event,
                ];
            }

            // No active lead: Create qualified Lead & Attribution
            $event = $this->recordReferralEvent(
                referralCodeId: $referralCode->id,
                telegramUserId: $telegramUser->id,
                eventType: TelegramEventType::SCAN,
                eventToken: $normalizedToken,
                updateId: $updateId
            );

            /** @var Lead $lead */
            $lead = Lead::create([
                'customer_id' => $customer->id,
                'telegram_user_id' => $telegramUser->id,
                'title' => "Translation Inquiry - Ref {$referralCode->code}",
                'status' => LeadStatus::NEW,
                'source' => LeadSource::TELEGRAM_REFERRAL,
            ]);

            /** @var ReferralAttribution $attribution */
            $attribution = ReferralAttribution::create([
                'referral_event_id' => $event->id,
                'referral_code_id' => $referralCode->id,
                'telegram_user_id' => $telegramUser->id,
                'customer_id' => $customer->id,
                'lead_id' => $lead->id,
                'referral_partner_id' => $referralCode->referral_partner_id,
                'driver_id' => $referralCode->driver_id,
                'vehicle_id' => $referralCode->vehicle_id,
                'attribution_status' => ReferralAttributionStatus::ACTIVE,
                'attributed_at' => Carbon::now(),
            ]);

            $lead->update([
                'referral_attribution_id' => $attribution->id,
            ]);

            LeadNote::create([
                'lead_id' => $lead->id,
                'user_id' => 1,
                'note' => "Lead created via Telegram referral scan (Code: {$referralCode->code}, Driver: {$referralCode->driver->name}, Vehicle: {$referralCode->vehicle->plate_number}).",
            ]);

            return [
                'outcome' => 'attributed_new_lead',
                'lead' => $lead,
                'customer' => $customer,
                'event' => $event,
                'attribution' => $attribution,
            ];
        });

        // Send confirmation message to customer via Telegram
        $this->botService->sendWelcomeReferralMessage($chatId, $referralCode);

        return $result;
    }

    /**
     * Manually change attribution of an existing lead.
     * Preserves complete historical audit trail and enforces mandatory reason.
     */
    public function changeAttribution(
        Lead $lead,
        ReferralCode $newReferralCode,
        User $actor,
        string $reason
    ): ReferralAttribution {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is mandatory when changing lead attribution.');
        }

        if ($newReferralCode->status !== ReferralCodeStatus::ACTIVE) {
            throw new InvalidArgumentException('Cannot attribute to an inactive referral code.');
        }

        return DB::transaction(function () use ($lead, $newReferralCode, $actor, $reason) {
            $now = Carbon::now();

            // Mark previous active attribution as CHANGED
            /** @var ReferralAttribution|null $previousAttribution */
            $previousAttribution = $lead->referralAttribution;

            if ($previousAttribution) {
                $previousAttribution->update([
                    'attribution_status' => ReferralAttributionStatus::CHANGED,
                    'changed_at' => $now,
                    'change_reason' => $reason,
                ]);
            }

            // Create new ACTIVE attribution
            /** @var ReferralAttribution $newAttribution */
            $newAttribution = ReferralAttribution::create([
                'referral_event_id' => $previousAttribution?->referral_event_id ?? $lead->attributions()->first()?->referral_event_id ?? 1,
                'referral_code_id' => $newReferralCode->id,
                'telegram_user_id' => $lead->telegram_user_id,
                'customer_id' => $lead->customer_id,
                'lead_id' => $lead->id,
                'referral_partner_id' => $newReferralCode->referral_partner_id,
                'driver_id' => $newReferralCode->driver_id,
                'vehicle_id' => $newReferralCode->vehicle_id,
                'attribution_status' => ReferralAttributionStatus::ACTIVE,
                'attributed_at' => $now,
                'change_reason' => "Changed from " . ($previousAttribution?->referralCode?->code ?? 'N/A') . ": {$reason}",
                'created_by' => $actor->id,
            ]);

            $lead->update([
                'referral_attribution_id' => $newAttribution->id,
            ]);

            LeadNote::create([
                'lead_id' => $lead->id,
                'user_id' => $actor->id,
                'note' => "Attribution manually changed by {$actor->name} to Code {$newReferralCode->code} (Driver: {$newReferralCode->driver->name}). Reason: {$reason}",
            ]);

            return $newAttribution;
        });
    }

    /**
     * Record a discrete referral event.
     */
    protected function recordReferralEvent(
        ?int $referralCodeId,
        ?int $telegramUserId,
        TelegramEventType $eventType,
        ?string $eventToken,
        ?int $updateId,
        ?array $metadata = null
    ): ReferralEvent {
        return ReferralEvent::create([
            'referral_code_id' => $referralCodeId,
            'telegram_user_id' => $telegramUserId,
            'event_type' => $eventType,
            'event_token' => $eventToken,
            'telegram_update_id' => $updateId,
            'metadata' => $metadata,
            'occurred_at' => Carbon::now(),
        ]);
    }
}
