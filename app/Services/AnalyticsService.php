<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReferralCodeStatus;
use App\Models\Commission;
use App\Models\CommissionPayment;
use App\Models\Driver;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ReferralCode;
use App\Models\ReferralEvent;
use App\Models\ReferralPartner;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsService
{
    /**
     * Calculate executive summary KPI metrics across the platform.
     *
     * @return array<string, mixed>
     */
    public function getExecutiveSummary(?string $dateFrom = null, ?string $dateTo = null): array
    {
        // 1. Referral Scans
        $scansQuery = ReferralEvent::query();
        if ($dateFrom) {
            $scansQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $scansQuery->whereDate('created_at', '<=', $dateTo);
        }
        $totalScans = $scansQuery->count();

        // 2. Leads Intake
        $leadsQuery = Lead::query();
        if ($dateFrom) {
            $leadsQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $leadsQuery->whereDate('created_at', '<=', $dateTo);
        }
        $totalLeads = $leadsQuery->count();

        // 3. Orders Fulfillment
        $ordersQuery = Order::query();
        if ($dateFrom) {
            $ordersQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $ordersQuery->whereDate('created_at', '<=', $dateTo);
        }
        $totalOrders = (clone $ordersQuery)->count();
        $confirmedOrders = (clone $ordersQuery)->whereIn('status', [OrderStatus::CONFIRMED->value, OrderStatus::COMPLETED->value])->count();

        $leadConversionRate = $totalLeads > 0
            ? round(($confirmedOrders / $totalLeads) * 100, 1)
            : 0.0;

        // 4. Financial Revenue
        $totalRevenue = (clone $ordersQuery)
            ->whereIn('status', [OrderStatus::CONFIRMED->value, OrderStatus::COMPLETED->value])
            ->sum('total_amount');

        // Customer Payments Collected
        $paymentsQuery = Payment::query()->where('status', PaymentRecordStatus::PAID->value);
        if ($dateFrom) {
            $paymentsQuery->whereDate('paid_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $paymentsQuery->whereDate('paid_at', '<=', $dateTo);
        }
        $totalCollected = $paymentsQuery->sum('amount');

        // 5. Driver Commissions
        $commissionsQuery = Commission::query();
        if ($dateFrom) {
            $commissionsQuery->whereDate('earned_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $commissionsQuery->whereDate('earned_at', '<=', $dateTo);
        }

        $totalCommissionsEarned = (clone $commissionsQuery)
            ->whereIn('status', [CommissionStatus::EARNED->value, CommissionStatus::APPROVED->value, CommissionStatus::PAID->value])
            ->sum('commission_amount');

        $totalCommissionsApproved = (clone $commissionsQuery)
            ->where('status', CommissionStatus::APPROVED->value)
            ->sum('commission_amount');

        $totalCommissionsPaid = (clone $commissionsQuery)
            ->where('status', CommissionStatus::PAID->value)
            ->sum('commission_amount');

        $outstandingCommissions = (clone $commissionsQuery)
            ->whereIn('status', [CommissionStatus::EARNED->value, CommissionStatus::APPROVED->value])
            ->sum('commission_amount');

        return [
            'total_scans' => $totalScans,
            'total_leads' => $totalLeads,
            'total_orders' => $totalOrders,
            'confirmed_orders' => $confirmedOrders,
            'conversion_rate' => $leadConversionRate,
            'total_revenue' => number_format((float) $totalRevenue, 2, '.', ''),
            'total_collected' => number_format((float) $totalCollected, 2, '.', ''),
            'total_commissions_earned' => number_format((float) $totalCommissionsEarned, 2, '.', ''),
            'total_commissions_approved' => number_format((float) $totalCommissionsApproved, 2, '.', ''),
            'total_commissions_paid' => number_format((float) $totalCommissionsPaid, 2, '.', ''),
            'outstanding_commissions' => number_format((float) $outstandingCommissions, 2, '.', ''),
        ];
    }

    /**
     * Aggregate detailed performance analytics for each driver.
     */
    public function getDriverPerformance(?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        return Driver::query()
            ->with([
                'referralPartner',
                'vehicles',
                'referralCodes.referralEvents' => function ($q) use ($dateFrom, $dateTo) {
                    $q->when($dateFrom, fn ($sub) => $sub->whereDate('created_at', '>=', $dateFrom))
                        ->when($dateTo, fn ($sub) => $sub->whereDate('created_at', '<=', $dateTo));
                },
                'commissions' => function ($q) use ($dateFrom, $dateTo) {
                    $q->when($dateFrom, fn ($sub) => $sub->whereDate('earned_at', '>=', $dateFrom))
                        ->when($dateTo, fn ($sub) => $sub->whereDate('earned_at', '<=', $dateTo));
                },
                'commissions.order',
            ])
            ->get()
            ->map(function (Driver $driver) use ($dateFrom, $dateTo) {
                // Scans from all referral codes held by this driver
                $totalScans = $driver->referralCodes->flatMap->referralEvents->count();

                // Leads attributed to this driver
                $leadsCount = Lead::query()
                    ->whereHas('referralAttribution', fn ($q) => $q->where('driver_id', $driver->id))
                    ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                    ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                    ->count();

                // Orders attributed to this driver
                $ordersQuery = Order::query()
                    ->whereHas('referralAttribution', fn ($q) => $q->where('driver_id', $driver->id))
                    ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                    ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));

                $totalOrders = (clone $ordersQuery)->count();
                $confirmedOrders = (clone $ordersQuery)->whereIn('status', [OrderStatus::CONFIRMED->value, OrderStatus::COMPLETED->value])->count();
                $revenue = (clone $ordersQuery)->whereIn('status', [OrderStatus::CONFIRMED->value, OrderStatus::COMPLETED->value])->sum('total_amount');

                $conversionRate = $leadsCount > 0
                    ? round(($confirmedOrders / $leadsCount) * 100, 1)
                    : 0.0;

                // Commissions
                $commissions = $driver->commissions;
                $earned = $commissions->whereIn('status', [CommissionStatus::EARNED, CommissionStatus::APPROVED, CommissionStatus::PAID])->sum('commission_amount');
                $approved = $commissions->where('status', CommissionStatus::APPROVED)->sum('commission_amount');
                $paid = $commissions->where('status', CommissionStatus::PAID)->sum('commission_amount');
                $outstanding = $commissions->whereIn('status', [CommissionStatus::EARNED, CommissionStatus::APPROVED])->sum('commission_amount');

                return [
                    'driver_id' => $driver->id,
                    'name' => $driver->name,
                    'phone' => $driver->phone,
                    'partner_name' => $driver->referralPartner?->name ?? 'Direct Driver',
                    'status' => $driver->status->value,
                    'vehicles' => $driver->vehicles->pluck('plate_number')->implode(', ') ?: 'None',
                    'active_codes_count' => $driver->referralCodes->filter(fn ($c) => $c->status === ReferralCodeStatus::ACTIVE)->count(),
                    'total_scans' => $totalScans,
                    'total_leads' => $leadsCount,
                    'total_orders' => $totalOrders,
                    'confirmed_orders' => $confirmedOrders,
                    'conversion_rate' => $conversionRate,
                    'total_revenue' => number_format((float) $revenue, 2, '.', ''),
                    'commission_earned' => number_format((float) $earned, 2, '.', ''),
                    'commission_approved' => number_format((float) $approved, 2, '.', ''),
                    'commission_paid' => number_format((float) $paid, 2, '.', ''),
                    'commission_outstanding' => number_format((float) $outstanding, 2, '.', ''),
                ];
            })
            ->sortByDesc(fn ($d) => (float) $d['total_revenue'])
            ->values();
    }

    /**
     * Aggregate detailed performance analytics for each referral partner.
     */
    public function getPartnerPerformance(?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        return ReferralPartner::query()
            ->with([
                'drivers',
                'commissions' => function ($q) use ($dateFrom, $dateTo) {
                    $q->when($dateFrom, fn ($sub) => $sub->whereDate('earned_at', '>=', $dateFrom))
                        ->when($dateTo, fn ($sub) => $sub->whereDate('earned_at', '<=', $dateTo));
                },
            ])
            ->get()
            ->map(function (ReferralPartner $partner) use ($dateFrom, $dateTo) {
                // Scans from all referral codes under this partner
                $totalScans = ReferralEvent::query()
                    ->whereHas('referralCode', fn ($q) => $q->where('referral_partner_id', $partner->id))
                    ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                    ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                    ->count();

                // Leads under this partner
                $leadsCount = Lead::query()
                    ->whereHas('referralAttribution', fn ($q) => $q->where('referral_partner_id', $partner->id))
                    ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                    ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                    ->count();

                // Orders under this partner
                $ordersQuery = Order::query()
                    ->whereHas('referralAttribution', fn ($q) => $q->where('referral_partner_id', $partner->id))
                    ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                    ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));

                $totalOrders = (clone $ordersQuery)->count();
                $confirmedOrders = (clone $ordersQuery)->whereIn('status', [OrderStatus::CONFIRMED->value, OrderStatus::COMPLETED->value])->count();
                $revenue = (clone $ordersQuery)->whereIn('status', [OrderStatus::CONFIRMED->value, OrderStatus::COMPLETED->value])->sum('total_amount');

                $conversionRate = $leadsCount > 0
                    ? round(($confirmedOrders / $leadsCount) * 100, 1)
                    : 0.0;

                // Commissions
                $commissions = $partner->commissions;
                $earned = $commissions->whereIn('status', [CommissionStatus::EARNED, CommissionStatus::APPROVED, CommissionStatus::PAID])->sum('commission_amount');
                $approved = $commissions->where('status', CommissionStatus::APPROVED)->sum('commission_amount');
                $paid = $commissions->where('status', CommissionStatus::PAID)->sum('commission_amount');
                $outstanding = $commissions->whereIn('status', [CommissionStatus::EARNED, CommissionStatus::APPROVED])->sum('commission_amount');

                return [
                    'partner_id' => $partner->id,
                    'name' => $partner->name,
                    'type' => $partner->type->value,
                    'status' => $partner->status->value,
                    'commission_rate' => $partner->commission_rate,
                    'drivers_count' => $partner->drivers->count(),
                    'total_scans' => $totalScans,
                    'total_leads' => $leadsCount,
                    'total_orders' => $totalOrders,
                    'confirmed_orders' => $confirmedOrders,
                    'conversion_rate' => $conversionRate,
                    'total_revenue' => number_format((float) $revenue, 2, '.', ''),
                    'commission_earned' => number_format((float) $earned, 2, '.', ''),
                    'commission_approved' => number_format((float) $approved, 2, '.', ''),
                    'commission_paid' => number_format((float) $paid, 2, '.', ''),
                    'commission_outstanding' => number_format((float) $outstanding, 2, '.', ''),
                ];
            })
            ->sortByDesc(fn ($p) => (float) $p['total_revenue'])
            ->values();
    }

    /**
     * Stream CSV export of driver performance report.
     */
    public function exportDriversCsv(?string $dateFrom = null, ?string $dateTo = null): StreamedResponse
    {
        $drivers = $this->getDriverPerformance($dateFrom, $dateTo);
        $filename = 'driver-performance-' . Carbon::now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($drivers) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Header Row
            fputcsv($handle, [
                'Driver ID',
                'Driver Name',
                'Phone Number',
                'Referral Partner',
                'Status',
                'Vehicle Plate(s)',
                'Active QR Codes',
                'Total Scans',
                'Total Leads',
                'Total Orders',
                'Confirmed Orders',
                'Conversion Rate (%)',
                'Total Revenue ($)',
                'Commission Earned ($)',
                'Commission Approved ($)',
                'Commission Paid ($)',
                'Outstanding Commission ($)',
            ]);

            foreach ($drivers as $row) {
                fputcsv($handle, [
                    $row['driver_id'],
                    $row['name'],
                    $row['phone'],
                    $row['partner_name'],
                    $row['status'],
                    $row['vehicles'],
                    $row['active_codes_count'],
                    $row['total_scans'],
                    $row['total_leads'],
                    $row['total_orders'],
                    $row['confirmed_orders'],
                    $row['conversion_rate'],
                    $row['total_revenue'],
                    $row['commission_earned'],
                    $row['commission_approved'],
                    $row['commission_paid'],
                    $row['commission_outstanding'],
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    /**
     * Stream CSV export of commissions ledger.
     */
    public function exportCommissionsCsv(?string $dateFrom = null, ?string $dateTo = null): StreamedResponse
    {
        $commissions = Commission::query()
            ->with(['order.customer', 'referralPartner', 'driver', 'vehicle'])
            ->when($dateFrom, fn ($q) => $q->whereDate('earned_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('earned_at', '<=', $dateTo))
            ->orderByDesc('id')
            ->get();

        $filename = 'commissions-ledger-' . Carbon::now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($commissions) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Commission Reference',
                'Order Number',
                'Customer Name',
                'Driver Name',
                'Driver Phone',
                'Referral Partner',
                'Vehicle Plate',
                'Eligible Order Amount ($)',
                'Commission Rate (%)',
                'Commission Amount ($)',
                'Status',
                'Earned Date',
                'Approved Date',
                'Paid Date',
                'Reversal Reason',
            ]);

            foreach ($commissions as $c) {
                fputcsv($handle, [
                    $c->commission_reference,
                    $c->order?->order_number ?? 'N/A',
                    $c->order?->customer?->name ?? 'Walk-in Customer',
                    $c->driver?->name ?? 'N/A',
                    $c->driver?->phone ?? 'N/A',
                    $c->referralPartner?->name ?? 'N/A',
                    $c->vehicle?->plate_number ?? 'N/A',
                    number_format((float) $c->eligible_amount, 2, '.', ''),
                    number_format((float) $c->commission_rate, 2, '.', ''),
                    number_format((float) $c->commission_amount, 2, '.', ''),
                    $c->status->value,
                    $c->earned_at ? Carbon::parse($c->earned_at)->format('Y-m-d H:i:s') : 'N/A',
                    $c->approved_at ? Carbon::parse($c->approved_at)->format('Y-m-d H:i:s') : 'N/A',
                    $c->paid_at ? Carbon::parse($c->paid_at)->format('Y-m-d H:i:s') : 'N/A',
                    $c->reversal_reason ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    /**
     * Stream CSV export of orders fulfillment ledger.
     */
    public function exportOrdersCsv(?string $dateFrom = null, ?string $dateTo = null): StreamedResponse
    {
        $orders = Order::query()
            ->with(['customer', 'referralAttribution.driver', 'referralAttribution.referralPartner', 'payments'])
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('id')
            ->get();

        $filename = 'orders-fulfillment-' . Carbon::now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Order Number',
                'Customer Name',
                'Customer Phone',
                'Attributed Driver',
                'Attributed Partner',
                'Order Status',
                'Payment Status',
                'Subtotal ($)',
                'Discount ($)',
                'Total Amount ($)',
                'Paid Amount ($)',
                'Outstanding Amount ($)',
                'Created Date',
            ]);

            foreach ($orders as $o) {
                fputcsv($handle, [
                    $o->order_number,
                    $o->customer?->name ?? 'Walk-in Customer',
                    $o->customer?->phone ?? 'N/A',
                    $o->referralAttribution?->driver?->name ?? 'Direct',
                    $o->referralAttribution?->referralPartner?->name ?? 'Direct',
                    $o->status->value,
                    $o->payment_status->value,
                    number_format((float) $o->subtotal, 2, '.', ''),
                    number_format((float) $o->discount, 2, '.', ''),
                    number_format((float) $o->total_amount, 2, '.', ''),
                    number_format((float) $o->paid_amount, 2, '.', ''),
                    number_format((float) $o->outstanding_amount, 2, '.', ''),
                    Carbon::parse($o->created_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    /**
     * Compute comprehensive dashboard metrics and chart series for executive overview.
     *
     * @return array<string, mixed>
     */
    public function getDashboardMetrics(int $days = 14): array
    {
        // 1. Core Summary Numbers
        $orders = Order::with('payments')->get();
        $totalPaid = (float) Payment::where('status', PaymentRecordStatus::PAID->value)->sum('amount');
        $totalOutstanding = (float) $orders->whereIn('status', [OrderStatus::PENDING, OrderStatus::CONFIRMED])
            ->sum(fn (Order $o) => $o->outstanding_amount);

        $activePartnersCount = ReferralPartner::active()->count();
        $activeDriversCount = Driver::active()->count();
        $activeVehiclesCount = Vehicle::active()->count();
        $activeReferralCodesCount = ReferralCode::active()->count();
        $newLeadsCount = Lead::where('status', LeadStatus::NEW)->count();
        $activeLeadsCount = Lead::active()->count();

        $ordersSummary = [
            'newOrders' => $orders->where('status', OrderStatus::PENDING)->count(),
            'confirmedOrders' => $orders->where('status', OrderStatus::CONFIRMED)->count(),
            'completedOrders' => $orders->where('status', OrderStatus::COMPLETED)->count(),
            'cancelledOrders' => $orders->where('status', OrderStatus::CANCELLED)->count(),
            'unpaidOrders' => $orders->where('payment_status', PaymentStatus::UNPAID)->count(),
            'partiallyPaidOrders' => $orders->where('payment_status', PaymentStatus::PARTIAL)->count(),
            'fullyPaidOrders' => $orders->where('payment_status', PaymentStatus::PAID)->count(),
            'totalPaidAmount' => round($totalPaid, 2),
            'totalOutstandingAmount' => round($totalOutstanding, 2),
        ];

        $commissionsSummary = [
            'totalEarned' => round((float) Commission::whereIn('status', [CommissionStatus::EARNED, CommissionStatus::APPROVED, CommissionStatus::PAID])->sum('commission_amount'), 2),
            'approvedAmount' => round((float) Commission::where('status', CommissionStatus::APPROVED)->sum('commission_amount'), 2),
            'paidAmount' => round((float) Commission::where('status', CommissionStatus::PAID)->sum('commission_amount'), 2),
            'outstandingAmount' => round((float) Commission::whereIn('status', [CommissionStatus::EARNED, CommissionStatus::APPROVED])->sum('commission_amount'), 2),
            'earnedCount' => Commission::where('status', CommissionStatus::EARNED)->count(),
            'approvedCount' => Commission::where('status', CommissionStatus::APPROVED)->count(),
            'paidCount' => Commission::where('status', CommissionStatus::PAID)->count(),
        ];

        // 2. Time-series Daily Trends ($days back)
        $startDate = Carbon::today()->subDays($days - 1)->startOfDay();
        $endDate = Carbon::today()->endOfDay();

        $scansByDate = ReferralEvent::where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('occurred_at', [$startDate, $endDate])
                ->orWhereBetween('created_at', [$startDate, $endDate]);
        })
            ->selectRaw('DATE(COALESCE(occurred_at, created_at)) as date_val, COUNT(*) as aggregate_count')
            ->groupBy('date_val')
            ->pluck('aggregate_count', 'date_val');

        $leadsByDate = Lead::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date_val, COUNT(*) as aggregate_count')
            ->groupBy('date_val')
            ->pluck('aggregate_count', 'date_val');

        $ordersByDate = Order::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date_val, COUNT(*) as aggregate_count, SUM(total_amount) as total_rev')
            ->groupBy('date_val')
            ->get()
            ->keyBy('date_val');

        $commissionsByDate = Commission::whereIn('status', [CommissionStatus::EARNED->value, CommissionStatus::APPROVED->value, CommissionStatus::PAID->value])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('earned_at', [$startDate, $endDate])
                    ->orWhereBetween('created_at', [$startDate, $endDate]);
            })
            ->selectRaw('DATE(COALESCE(earned_at, created_at)) as date_val, SUM(commission_amount) as total_comm')
            ->groupBy('date_val')
            ->pluck('total_comm', 'date_val');

        $trendLabels = [];
        $trendDates = [];
        $trendScans = [];
        $trendLeads = [];
        $trendOrders = [];
        $trendRevenue = [];
        $trendCommissions = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $curr = Carbon::today()->subDays($i);
            $ymd = $curr->format('Y-m-d');
            $label = $curr->format('M j');

            $trendDates[] = $ymd;
            $trendLabels[] = $label;
            $trendScans[] = (int) ($scansByDate[$ymd] ?? 0);
            $trendLeads[] = (int) ($leadsByDate[$ymd] ?? 0);
            $trendOrders[] = (int) ($ordersByDate[$ymd]->aggregate_count ?? 0);
            $trendRevenue[] = round((float) ($ordersByDate[$ymd]->total_rev ?? 0), 2);
            $trendCommissions[] = round((float) ($commissionsByDate[$ymd] ?? 0), 2);
        }

        $trends = [
            'days' => $days,
            'labels' => $trendLabels,
            'dates' => $trendDates,
            'scans' => $trendScans,
            'leads' => $trendLeads,
            'orders' => $trendOrders,
            'revenue' => $trendRevenue,
            'commissions' => $trendCommissions,
            'totals' => [
                'scans' => array_sum($trendScans),
                'leads' => array_sum($trendLeads),
                'orders' => array_sum($trendOrders),
                'revenue' => round(array_sum($trendRevenue), 2),
                'commissions' => round(array_sum($trendCommissions), 2),
            ],
        ];

        // 3. Referral Conversion Funnel
        $totalScans = ReferralEvent::count();
        $totalLeads = Lead::count();
        $totalOrdersCount = $orders->count();
        $paidOrdersCount = $orders->where('payment_status', PaymentStatus::PAID)->count();

        $scanToLeadRate = $totalScans > 0 ? round(($totalLeads / $totalScans) * 100, 1) : 0.0;
        $leadToOrderRate = $totalLeads > 0 ? round(($totalOrdersCount / $totalLeads) * 100, 1) : 0.0;
        $orderToPaidRate = $totalOrdersCount > 0 ? round(($paidOrdersCount / $totalOrdersCount) * 100, 1) : 0.0;
        $overallFunnelRate = $totalScans > 0 ? round(($paidOrdersCount / $totalScans) * 100, 1) : 0.0;

        $funnel = [
            'scans' => $totalScans,
            'leads' => $totalLeads,
            'orders' => $totalOrdersCount,
            'paid_orders' => $paidOrdersCount,
            'scan_to_lead_rate' => $scanToLeadRate,
            'lead_to_order_rate' => $leadToOrderRate,
            'order_to_paid_rate' => $orderToPaidRate,
            'overall_rate' => $overallFunnelRate,
        ];

        // 4. Order Status Distribution (for Donut Chart)
        $totalOrdersForDist = max(1, $ordersSummary['newOrders'] + $ordersSummary['confirmedOrders'] + $ordersSummary['completedOrders'] + $ordersSummary['cancelledOrders']);
        $orderDistribution = [
            [
                'label' => 'Completed',
                'count' => $ordersSummary['completedOrders'],
                'percentage' => round(($ordersSummary['completedOrders'] / $totalOrdersForDist) * 100, 1),
                'color' => '#10b981',
            ],
            [
                'label' => 'Confirmed',
                'count' => $ordersSummary['confirmedOrders'],
                'percentage' => round(($ordersSummary['confirmedOrders'] / $totalOrdersForDist) * 100, 1),
                'color' => '#3b82f6',
            ],
            [
                'label' => 'Pending',
                'count' => $ordersSummary['newOrders'],
                'percentage' => round(($ordersSummary['newOrders'] / $totalOrdersForDist) * 100, 1),
                'color' => '#f59e0b',
            ],
            [
                'label' => 'Cancelled',
                'count' => $ordersSummary['cancelledOrders'],
                'percentage' => round(($ordersSummary['cancelledOrders'] / $totalOrdersForDist) * 100, 1),
                'color' => '#ef4444',
            ],
        ];

        // 5. Payment Status Distribution (for Donut Chart)
        $totalPaymentsForDist = max(1, $ordersSummary['fullyPaidOrders'] + $ordersSummary['partiallyPaidOrders'] + $ordersSummary['unpaidOrders']);
        $paymentDistribution = [
            [
                'label' => 'Fully Paid',
                'count' => $ordersSummary['fullyPaidOrders'],
                'percentage' => round(($ordersSummary['fullyPaidOrders'] / $totalPaymentsForDist) * 100, 1),
                'color' => '#10b981',
            ],
            [
                'label' => 'Partially Paid',
                'count' => $ordersSummary['partiallyPaidOrders'],
                'percentage' => round(($ordersSummary['partiallyPaidOrders'] / $totalPaymentsForDist) * 100, 1),
                'color' => '#f59e0b',
            ],
            [
                'label' => 'Unpaid',
                'count' => $ordersSummary['unpaidOrders'],
                'percentage' => round(($ordersSummary['unpaidOrders'] / $totalPaymentsForDist) * 100, 1),
                'color' => '#ef4444',
            ],
        ];

        // 6. Payment Methods Distribution
        $paymentsByMethod = Payment::where('status', PaymentRecordStatus::PAID->value)
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        $methodLabels = [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'khqr_manual' => 'Bakong KHQR',
            'other' => 'Other',
        ];
        $methodColors = [
            'cash' => '#10b981',
            'bank_transfer' => '#3b82f6',
            'khqr_manual' => '#e11d48',
            'other' => '#8b5cf6',
        ];

        $totalPaymentMethodsSum = max(1, (float) $paymentsByMethod->sum('total'));
        $paymentMethodDistribution = $paymentsByMethod->map(function ($pm) use ($methodLabels, $methodColors, $totalPaymentMethodsSum) {
            $key = $pm->payment_method instanceof \BackedEnum ? (string) $pm->payment_method->value : (string) $pm->payment_method;
            return [
                'label' => $methodLabels[$key] ?? ucfirst($key),
                'count' => (int) $pm->count,
                'amount' => round((float) $pm->total, 2),
                'percentage' => round(((float) $pm->total / $totalPaymentMethodsSum) * 100, 1),
                'color' => $methodColors[$key] ?? '#64748b',
            ];
        })->values()->all();

        // 7. Top Drivers Leaderboard (Top 5)
        $topDrivers = $this->getDriverPerformance()
            ->take(5)
            ->map(function ($d) {
                return [
                    'id' => $d['driver_id'],
                    'name' => $d['name'],
                    'phone' => $d['phone'],
                    'partner_name' => $d['partner_name'],
                    'vehicles' => $d['vehicles'],
                    'total_scans' => $d['total_scans'],
                    'total_leads' => $d['total_leads'],
                    'total_orders' => $d['total_orders'],
                    'confirmed_orders' => $d['confirmed_orders'],
                    'total_revenue' => (float) $d['total_revenue'],
                    'commission_earned' => (float) $d['commission_earned'],
                ];
            })
            ->values()
            ->all();

        // 8. Financial Margin Breakdown
        $grossRevenue = (float) Order::whereIn('status', [OrderStatus::CONFIRMED->value, OrderStatus::COMPLETED->value])->sum('total_amount');
        $totalCommissions = (float) Commission::whereIn('status', [CommissionStatus::EARNED->value, CommissionStatus::APPROVED->value, CommissionStatus::PAID->value])->sum('commission_amount');
        $netMargin = max(0.0, $grossRevenue - $totalCommissions);
        $driverSharePercent = $grossRevenue > 0 ? round(($totalCommissions / $grossRevenue) * 100, 1) : 0.0;
        $companyMarginPercent = $grossRevenue > 0 ? round(($netMargin / $grossRevenue) * 100, 1) : 100.0;

        $financialMargin = [
            'gross_revenue' => round($grossRevenue, 2),
            'driver_commissions' => round($totalCommissions, 2),
            'net_revenue' => round($netMargin, 2),
            'driver_share_percent' => $driverSharePercent,
            'company_margin_percent' => $companyMarginPercent,
        ];

        return [
            'selectedDays' => $days,
            'activePartnersCount' => $activePartnersCount,
            'activeDriversCount' => $activeDriversCount,
            'activeVehiclesCount' => $activeVehiclesCount,
            'activeReferralCodesCount' => $activeReferralCodesCount,
            'newLeadsCount' => $newLeadsCount,
            'activeLeadsCount' => $activeLeadsCount,
            'ordersSummary' => $ordersSummary,
            'commissionsSummary' => $commissionsSummary,
            'trends' => $trends,
            'funnel' => $funnel,
            'orderDistribution' => $orderDistribution,
            'paymentDistribution' => $paymentDistribution,
            'paymentMethodDistribution' => $paymentMethodDistribution,
            'topDrivers' => $topDrivers,
            'financialMargin' => $financialMargin,
        ];
    }
}
