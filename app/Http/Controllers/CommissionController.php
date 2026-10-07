<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CommissionPaymentMethod;
use App\Enums\CommissionStatus;
use App\Http\Requests\Commission\RecordCommissionPayoutRequest;
use App\Http\Requests\Commission\ReverseCommissionRequest;
use App\Models\Commission;
use App\Models\Driver;
use App\Models\Order;
use App\Models\ReferralPartner;
use App\Services\CommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CommissionController extends Controller
{
    public function __construct(
        protected CommissionService $commissionService,
    ) {}

    /**
     * Display a listing of commissions with filters and summary statistics.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Commission::class);

        $query = Commission::query()
            ->with([
                'order.customer',
                'referralPartner',
                'driver',
                'vehicle',
                'payments',
            ])
            ->search($request->input('search'))
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->when($request->filled('partner_id'), function ($q) use ($request) {
                $q->where('referral_partner_id', $request->input('partner_id'));
            })
            ->when($request->filled('driver_id'), function ($q) use ($request) {
                $q->where('driver_id', $request->input('driver_id'));
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('earned_at', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('earned_at', '<=', $request->input('date_to'));
            })
            ->orderByDesc('id');

        $commissions = $query->paginate(15)->withQueryString();

        // Calculate summary metrics across the whole dataset (or filtered dataset)
        $metricsQuery = Commission::query()
            ->search($request->input('search'))
            ->when($request->filled('partner_id'), function ($q) use ($request) {
                $q->where('referral_partner_id', $request->input('partner_id'));
            })
            ->when($request->filled('driver_id'), function ($q) use ($request) {
                $q->where('driver_id', $request->input('driver_id'));
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('earned_at', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('earned_at', '<=', $request->input('date_to'));
            });

        $totalEarned = (clone $metricsQuery)->whereIn('status', [
            CommissionStatus::EARNED->value,
            CommissionStatus::APPROVED->value,
            CommissionStatus::PAID->value,
        ])->sum('commission_amount');

        $totalApproved = (clone $metricsQuery)->where('status', CommissionStatus::APPROVED->value)->sum('commission_amount');
        $totalPaid = (clone $metricsQuery)->where('status', CommissionStatus::PAID->value)->sum('commission_amount');
        $totalReversed = (clone $metricsQuery)->where('status', CommissionStatus::REVERSED->value)->sum('commission_amount');
        $outstandingUnpaid = (clone $metricsQuery)->whereIn('status', [
            CommissionStatus::EARNED->value,
            CommissionStatus::APPROVED->value,
        ])->sum('commission_amount');

        $countsByStatus = [
            'all' => (clone $metricsQuery)->count(),
            CommissionStatus::PENDING->value => (clone $metricsQuery)->where('status', CommissionStatus::PENDING->value)->count(),
            CommissionStatus::EARNED->value => (clone $metricsQuery)->where('status', CommissionStatus::EARNED->value)->count(),
            CommissionStatus::APPROVED->value => (clone $metricsQuery)->where('status', CommissionStatus::APPROVED->value)->count(),
            CommissionStatus::PAID->value => (clone $metricsQuery)->where('status', CommissionStatus::PAID->value)->count(),
            CommissionStatus::REVERSED->value => (clone $metricsQuery)->where('status', CommissionStatus::REVERSED->value)->count(),
        ];

        return Inertia::render('Commissions/Index', [
            'commissions' => $commissions,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'partner_id' => $request->input('partner_id', ''),
                'driver_id' => $request->input('driver_id', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
            ],
            'statuses' => array_map(fn (CommissionStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ], CommissionStatus::cases()),
            'partners' => ReferralPartner::query()->select(['id', 'name'])->orderBy('name')->get(),
            'drivers' => Driver::query()->select(['id', 'name', 'referral_partner_id'])->orderBy('name')->get(),
            'metrics' => [
                'total_earned' => number_format((float) $totalEarned, 2, '.', ''),
                'total_approved' => number_format((float) $totalApproved, 2, '.', ''),
                'total_paid' => number_format((float) $totalPaid, 2, '.', ''),
                'total_reversed' => number_format((float) $totalReversed, 2, '.', ''),
                'outstanding_unpaid' => number_format((float) $outstandingUnpaid, 2, '.', ''),
                'counts' => $countsByStatus,
            ],
        ]);
    }

    /**
     * Display detail of a specific commission.
     */
    public function show(Commission $commission): Response
    {
        Gate::authorize('view', $commission);

        $commission->load([
            'order.customer',
            'order.items',
            'order.payments.creator',
            'referralAttribution.referralCode',
            'referralPartner',
            'driver',
            'vehicle',
            'payments.creator',
            'approvedBy',
            'paidBy',
            'reversedBy',
        ]);

        return Inertia::render('Commissions/Show', [
            'commission' => $commission,
            'paymentMethods' => array_map(fn (CommissionPaymentMethod $m) => [
                'value' => $m->value,
                'label' => $m->label(),
            ], CommissionPaymentMethod::cases()),
            'can' => [
                'approve' => request()->user()?->can('approve', $commission) ?? false,
                'pay' => request()->user()?->can('pay', $commission) ?? false,
                'reverse' => request()->user()?->can('reverse', $commission) ?? false,
            ],
        ]);
    }

    /**
     * Approve an earned commission.
     */
    public function approve(Request $request, Commission $commission): RedirectResponse
    {
        Gate::authorize('approve', $commission);

        $success = $this->commissionService->approveCommission($commission, $request->user());

        if (! $success) {
            return back()->with('error', 'Commission could not be approved. Verify its current status.');
        }

        return back()->with('success', "Commission {$commission->commission_reference} successfully approved.");
    }

    /**
     * Record a payout for an approved commission.
     */
    public function recordPayout(RecordCommissionPayoutRequest $request, Commission $commission): RedirectResponse
    {
        $payment = $this->commissionService->recordPayout(
            $commission,
            $request->validated(),
            $request->user()
        );

        if (! $payment) {
            return back()->with('error', 'Commission payout could not be recorded. Verify its current status.');
        }

        return back()->with('success', "Payout {$payment->payment_reference} of \${$payment->amount} recorded successfully.");
    }

    /**
     * Reverse a commission.
     */
    public function reverse(ReverseCommissionRequest $request, Commission $commission): RedirectResponse
    {
        $success = $this->commissionService->reverseCommission(
            $commission,
            $request->user(),
            $request->validated('reversal_reason')
        );

        if (! $success) {
            return back()->with('error', 'Commission could not be reversed.');
        }

        return back()->with('success', "Commission {$commission->commission_reference} has been reversed.");
    }

    /**
     * Trigger manual commission evaluation for an order.
     */
    public function evaluateOrder(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('create', Commission::class);

        $commission = $this->commissionService->evaluateAndEarnForOrder($order, $request->user());

        if (! $commission) {
            $eligibility = $this->commissionService->checkEligibility($order);
            $reason = $eligibility['reason']?->label() ?? 'Order is not eligible for commission.';

            return back()->with('error', "Cannot generate commission: {$reason}");
        }

        return redirect()->route('commissions.show', $commission)
            ->with('success', "Commission {$commission->commission_reference} successfully generated.");
    }

    /**
     * Display commission summary reports.
     */
    public function reports(Request $request): Response
    {
        Gate::authorize('viewReports', Commission::class);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Breakdown by Referral Partner
        $partnerQuery = ReferralPartner::query()
            ->with(['commissions' => function ($q) use ($dateFrom, $dateTo) {
                $q->when($dateFrom, fn ($subQ) => $subQ->whereDate('earned_at', '>=', $dateFrom))
                    ->when($dateTo, fn ($subQ) => $subQ->whereDate('earned_at', '<=', $dateTo));
            }])
            ->get()
            ->map(function (ReferralPartner $partner) {
                $commissions = $partner->commissions;
                $earned = $commissions->whereIn('status', [CommissionStatus::EARNED, CommissionStatus::APPROVED, CommissionStatus::PAID])->sum('commission_amount');
                $approved = $commissions->where('status', CommissionStatus::APPROVED)->sum('commission_amount');
                $paid = $commissions->where('status', CommissionStatus::PAID)->sum('commission_amount');
                $outstanding = $commissions->whereIn('status', [CommissionStatus::EARNED, CommissionStatus::APPROVED])->sum('commission_amount');

                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'type' => $partner->type->value,
                    'commission_rate' => $partner->commission_rate,
                    'total_orders' => $commissions->count(),
                    'earned_amount' => number_format((float) $earned, 2, '.', ''),
                    'approved_amount' => number_format((float) $approved, 2, '.', ''),
                    'paid_amount' => number_format((float) $paid, 2, '.', ''),
                    'outstanding_amount' => number_format((float) $outstanding, 2, '.', ''),
                ];
            })
            ->sortByDesc('earned_amount')
            ->values();

        // Breakdown by Driver
        $driverQuery = Driver::query()
            ->with([
                'referralPartner',
                'commissions' => function ($q) use ($dateFrom, $dateTo) {
                    $q->when($dateFrom, fn ($subQ) => $subQ->whereDate('earned_at', '>=', $dateFrom))
                        ->when($dateTo, fn ($subQ) => $subQ->whereDate('earned_at', '<=', $dateTo));
                },
            ])
            ->get()
            ->map(function (Driver $driver) {
                $commissions = $driver->commissions;
                $earned = $commissions->whereIn('status', [CommissionStatus::EARNED, CommissionStatus::APPROVED, CommissionStatus::PAID])->sum('commission_amount');
                $approved = $commissions->where('status', CommissionStatus::APPROVED)->sum('commission_amount');
                $paid = $commissions->where('status', CommissionStatus::PAID)->sum('commission_amount');
                $outstanding = $commissions->whereIn('status', [CommissionStatus::EARNED, CommissionStatus::APPROVED])->sum('commission_amount');

                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'phone' => $driver->phone,
                    'partner_name' => $driver->referralPartner?->name ?? 'N/A',
                    'total_orders' => $commissions->count(),
                    'earned_amount' => number_format((float) $earned, 2, '.', ''),
                    'approved_amount' => number_format((float) $approved, 2, '.', ''),
                    'paid_amount' => number_format((float) $paid, 2, '.', ''),
                    'outstanding_amount' => number_format((float) $outstanding, 2, '.', ''),
                ];
            })
            ->sortByDesc('earned_amount')
            ->values();

        return Inertia::render('Commissions/Reports', [
            'partnerReports' => $partnerQuery,
            'driverReports' => $driverQuery,
            'filters' => [
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
            ],
        ]);
    }
}
