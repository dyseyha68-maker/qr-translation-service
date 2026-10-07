<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TelegramEventType;
use App\Models\ReferralEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanNotificationController extends Controller
{
    /**
     * Get recent QR scan referral events for in-app notification badge and toasts.
     */
    public function index(Request $request): JsonResponse
    {
        $sinceId = $request->query('since_id');

        $query = ReferralEvent::with([
            'referralCode.driver',
            'referralCode.vehicle',
            'referralCode.referralPartner',
            'telegramUser',
            'attribution.lead',
        ])
            ->whereIn('event_type', [TelegramEventType::SCAN, TelegramEventType::REPEAT_SCAN])
            ->latest('id');

        if ($sinceId !== null && is_numeric($sinceId) && (int) $sinceId > 0) {
            $newEvents = (clone $query)->where('id', '>', (int) $sinceId)->take(10)->get();

            $formattedNew = $newEvents->map(fn (ReferralEvent $event) => $this->formatEvent($event));

            $latestId = ReferralEvent::whereIn('event_type', [TelegramEventType::SCAN, TelegramEventType::REPEAT_SCAN])->max('id') ?? 0;

            return response()->json([
                'new_scans' => $formattedNew,
                'has_new' => $newEvents->isNotEmpty(),
                'latest_id' => $latestId,
            ]);
        }

        $events = $query->take(15)->get();
        $latestId = $events->first()?->id ?? 0;

        $formatted = $events->map(fn (ReferralEvent $event) => $this->formatEvent($event));

        return response()->json([
            'scans' => $formatted,
            'latest_id' => $latestId,
            'total_recent' => $formatted->count(),
        ]);
    }

    /**
     * Format a referral event into a user-friendly notification object.
     *
     * @return array<string, mixed>
     */
    protected function formatEvent(ReferralEvent $event): array
    {
        $telegramUser = $event->telegramUser;
        $referralCode = $event->referralCode;
        $driver = $referralCode?->driver;
        $vehicle = $referralCode?->vehicle;
        $lead = $event->attribution?->lead;

        $customerName = $telegramUser?->full_name;
        if (empty($customerName) && ! empty($telegramUser?->username)) {
            $customerName = '@' . $telegramUser->username;
        }
        if (empty($customerName)) {
            $customerName = 'Guest Passenger';
        }

        $isRepeat = $event->event_type === TelegramEventType::REPEAT_SCAN;

        return [
            'id' => $event->id,
            'event_type' => $event->event_type->value,
            'is_repeat' => $isRepeat,
            'type_badge' => $isRepeat ? 'Repeat Scan' : 'New Scan',
            'customer_name' => $customerName,
            'customer_username' => $telegramUser?->username,
            'driver_name' => $driver?->name ?? 'Unknown Driver',
            'driver_phone' => $driver?->phone,
            'vehicle_plate' => $vehicle?->plate_number ?? 'N/A',
            'vehicle_type' => $vehicle?->vehicle_type ?? 'Tuk-Tuk',
            'referral_code' => $referralCode?->code ?? $event->event_token ?? 'N/A',
            'partner_name' => $referralCode?->referralPartner?->name,
            'lead_id' => $lead?->id,
            'lead_url' => $lead ? route('leads.show', $lead->id) : null,
            'occurred_at' => $event->occurred_at?->toIso8601String() ?? $event->created_at?->toIso8601String(),
            'time_ago' => $event->occurred_at ? Carbon::parse($event->occurred_at)->diffForHumans() : 'Just now',
        ];
    }
}
