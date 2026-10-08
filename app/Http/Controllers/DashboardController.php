<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService,
    ) {}

    /**
     * Display the executive dashboard with visual charts and operational KPIs.
     */
    public function index(Request $request): Response
    {
        $days = (int) $request->input('days', 14);
        if (! in_array($days, [7, 14, 30], true)) {
            $days = 14;
        }

        $dashboardData = $this->analyticsService->getDashboardMetrics($days);

        return Inertia::render('Dashboard', $dashboardData);
    }
}
