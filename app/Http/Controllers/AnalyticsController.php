<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService,
    ) {}

    /**
     * Display the performance and analytics dashboard.
     */
    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()?->can(PermissionName::REPORTS_VIEW->value),
            403,
            'Unauthorized to view performance analytics reports.'
        );

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $executiveSummary = $this->analyticsService->getExecutiveSummary($dateFrom, $dateTo);
        $driverPerformance = $this->analyticsService->getDriverPerformance($dateFrom, $dateTo);
        $partnerPerformance = $this->analyticsService->getPartnerPerformance($dateFrom, $dateTo);

        return Inertia::render('Reports/Performance', [
            'summary' => $executiveSummary,
            'driverPerformance' => $driverPerformance,
            'partnerPerformance' => $partnerPerformance,
            'filters' => [
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
            ],
            'can' => [
                'export' => $request->user()?->can(PermissionName::REPORTS_EXPORT->value) ?? false,
            ],
        ]);
    }

    /**
     * Export driver performance metrics as CSV.
     */
    public function exportDrivers(Request $request): StreamedResponse
    {
        abort_unless(
            $request->user()?->can(PermissionName::REPORTS_EXPORT->value),
            403,
            'Unauthorized to export performance data.'
        );

        return $this->analyticsService->exportDriversCsv(
            $request->input('date_from'),
            $request->input('date_to')
        );
    }

    /**
     * Export commissions ledger as CSV.
     */
    public function exportCommissions(Request $request): StreamedResponse
    {
        abort_unless(
            $request->user()?->can(PermissionName::REPORTS_EXPORT->value),
            403,
            'Unauthorized to export commission records.'
        );

        return $this->analyticsService->exportCommissionsCsv(
            $request->input('date_from'),
            $request->input('date_to')
        );
    }

    /**
     * Export orders fulfillment ledger as CSV.
     */
    public function exportOrders(Request $request): StreamedResponse
    {
        abort_unless(
            $request->user()?->can(PermissionName::REPORTS_EXPORT->value),
            403,
            'Unauthorized to export orders data.'
        );

        return $this->analyticsService->exportOrdersCsv(
            $request->input('date_from'),
            $request->input('date_to')
        );
    }
}
