<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TelegramEventType;
use App\Models\ReferralEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status->value,
                    'role' => $user->roles->first()?->name ?? 'STAFF',
                    'avatar_url' => $user->avatar_url,
                ] : null,
                'can' => [
                    'viewUsers' => $user?->can('users.view') ?? false,
                    'createUsers' => $user?->can('users.create') ?? false,
                    'viewPartners' => $user?->can('partners.view') ?? false,
                    'createPartners' => $user?->can('partners.create') ?? false,
                    'viewDrivers' => $user?->can('drivers.view') ?? false,
                    'createDrivers' => $user?->can('drivers.create') ?? false,
                    'viewVehicles' => $user?->can('vehicles.view') ?? false,
                    'createVehicles' => $user?->can('vehicles.create') ?? false,
                    'viewReferralCodes' => $user?->can('referral_codes.view') ?? false,
                    'createReferralCodes' => $user?->can('referral_codes.create') ?? false,
                    'viewLeads' => $user?->can('leads.view') ?? false,
                    'changeAttribution' => $user?->can('leads.change_attribution') ?? false,
                    'viewOrders' => $user?->can('orders.view') ?? false,
                    'createOrders' => $user?->can('orders.create') ?? false,
                    'viewPayments' => $user?->can('payments.view') ?? false,
                    'refundPayments' => $user?->can('payments.refund') ?? false,
                    'viewCommissions' => $user?->can('commissions.view') ?? false,
                    'approveCommissions' => $user?->can('commissions.approve') ?? false,
                    'payCommissions' => $user?->can('commissions.pay') ?? false,
                    'reverseCommissions' => $user?->can('commissions.reverse') ?? false,
                    'viewCommissionReports' => $user?->can('commissions.reports') ?? false,
                    'viewReports' => $user?->can('reports.view') ?? false,
                    'exportReports' => $user?->can('reports.export') ?? false,
                    'manageSettings' => ($user?->can('settings.manage') ?? false) || ($user?->isSuperAdmin() ?? false),
                ],
            ],
            'branding' => app(\App\Services\SettingService::class)->getBranding(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'notifications' => [
                'latestScanId' => $user ? (ReferralEvent::whereIn('event_type', [TelegramEventType::SCAN, TelegramEventType::REPEAT_SCAN])->max('id') ?? 0) : 0,
            ],
        ];
    }
}
