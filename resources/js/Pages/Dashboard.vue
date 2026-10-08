<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AreaTrendChart from '@/Components/Charts/AreaTrendChart.vue';
import DonutChart, { type DonutItem } from '@/Components/Charts/DonutChart.vue';
import FunnelChart from '@/Components/Charts/FunnelChart.vue';
import BarRankChart, { type DriverRankItem } from '@/Components/Charts/BarRankChart.vue';
import MarginBreakdownChart from '@/Components/Charts/MarginBreakdownChart.vue';

interface TrendData {
    days: number;
    labels: string[];
    dates: string[];
    scans: number[];
    leads: number[];
    orders: number[];
    revenue: number[];
    commissions: number[];
    totals: {
        scans: number;
        leads: number;
        orders: number;
        revenue: number;
        commissions: number;
    };
}

interface FunnelData {
    scans: number;
    leads: number;
    orders: number;
    paid_orders: number;
    scan_to_lead_rate: number;
    lead_to_order_rate: number;
    order_to_paid_rate: number;
    overall_rate: number;
}

interface FinancialMarginData {
    gross_revenue: number;
    driver_commissions: number;
    net_revenue: number;
    driver_share_percent: number;
    company_margin_percent: number;
}

const props = defineProps<{
    selectedDays?: number;
    activePartnersCount?: number;
    activeDriversCount?: number;
    activeVehiclesCount?: number;
    activeReferralCodesCount?: number;
    newLeadsCount?: number;
    activeLeadsCount?: number;
    ordersSummary?: {
        newOrders: number;
        confirmedOrders: number;
        completedOrders: number;
        cancelledOrders: number;
        unpaidOrders: number;
        partiallyPaidOrders: number;
        fullyPaidOrders: number;
        totalPaidAmount: number;
        totalOutstandingAmount: number;
    };
    commissionsSummary?: {
        totalEarned: number;
        approvedAmount: number;
        paidAmount: number;
        outstandingAmount: number;
        earnedCount: number;
        approvedCount: number;
        paidCount: number;
    };
    trends?: TrendData;
    funnel?: FunnelData;
    orderDistribution?: DonutItem[];
    paymentDistribution?: DonutItem[];
    paymentMethodDistribution?: DonutItem[];
    topDrivers?: DriverRankItem[];
    financialMargin?: FinancialMarginData;
}>();

const page = usePage();

const trendMode = ref<'traffic' | 'financial'>('traffic');
const paymentDonutTab = ref<'fulfillment' | 'methods'>('fulfillment');
const currentDays = ref<number>(props.selectedDays ?? 14);

const changePeriod = (days: number) => {
    currentDays.value = days;
    router.get(
        route('dashboard'),
        { days },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

// Fallback arrays if trends not yet loaded
const safeLabels = computed(() => props.trends?.labels ?? []);
const safeDates = computed(() => props.trends?.dates ?? []);
const safeScans = computed(() => props.trends?.scans ?? []);
const safeLeads = computed(() => props.trends?.leads ?? []);
const safeRevenue = computed(() => props.trends?.revenue ?? []);
const safeCommissions = computed(() => props.trends?.commissions ?? []);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Executive Dashboard
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Telegram Referral &amp; Commission System Analytics &amp; Pipeline Overview
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        System Operational
                    </span>
                    <Link
                        v-if="$page.props.auth.can.viewReports"
                        :href="route('reports.performance')"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors"
                    >
                        <span>Full Report &rarr;</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Welcome Banner -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                Welcome back, {{ $page.props.auth.user.name }}!
                            </h3>
                            <p class="text-slate-600 dark:text-slate-400 mt-0.5 text-xs">
                                Real-time operational tracking of tuk-tuk and taxi driver referrals, customer leads, and translation orders.
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold block">Active Role</span>
                                <span class="text-xs font-bold text-blue-600 dark:text-blue-400">
                                    {{ $page.props.auth.user.role }}
                                </span>
                            </div>
                            <div class="h-9 w-9 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold text-sm">
                                {{ $page.props.auth.user.name.charAt(0) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Operational KPI Cards Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <!-- New Leads -->
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">New Leads</span>
                            <span class="h-2 w-2 rounded-full bg-blue-500 animate-ping"></span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-blue-600 dark:text-blue-400 font-mono">
                            {{ props.newLeadsCount ?? 0 }}
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Fresh QR scans</p>
                    </div>

                    <!-- Active Pipeline -->
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Pipeline</span>
                            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white font-mono">
                            {{ props.activeLeadsCount ?? 0 }}
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Leads in intake</p>
                    </div>

                    <!-- Referral Codes -->
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">QR Codes</span>
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white font-mono">
                            {{ props.activeReferralCodesCount ?? 0 }}
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Active physical QRs</p>
                    </div>

                    <!-- Active Partners -->
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Partners</span>
                            <span class="h-2 w-2 rounded-full bg-cyan-500"></span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white font-mono">
                            {{ props.activePartnersCount ?? 0 }}
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Associations &amp; co.</p>
                    </div>

                    <!-- Active Drivers -->
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Drivers</span>
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white font-mono">
                            {{ props.activeDriversCount ?? 0 }}
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Referrers</p>
                    </div>

                    <!-- Active Vehicles -->
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Vehicles</span>
                            <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white font-mono">
                            {{ props.activeVehiclesCount ?? 0 }}
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Plates registered</p>
                    </div>
                </div>

                <!-- SECTION 1: Interactive Trend Area Chart with Period & Metric Controls -->
                <div class="space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <!-- Mode Selector (Traffic vs Financial) -->
                        <div class="inline-flex rounded-lg border border-slate-200 p-1 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900 text-xs">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-md font-semibold transition-all flex items-center gap-1.5"
                                :class="trendMode === 'traffic'
                                    ? 'bg-blue-600 text-white shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                                @click="trendMode = 'traffic'"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                <span>Referral Traffic &amp; Leads</span>
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-md font-semibold transition-all flex items-center gap-1.5"
                                :class="trendMode === 'financial'
                                    ? 'bg-blue-600 text-white shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                                @click="trendMode = 'financial'"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Revenue &amp; Commissions</span>
                            </button>
                        </div>

                        <!-- Time Period Selector (7d, 14d, 30d) -->
                        <div class="inline-flex rounded-lg border border-slate-200 p-1 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900 text-xs">
                            <button
                                type="button"
                                class="px-2.5 py-1 rounded-md font-medium transition-all"
                                :class="currentDays === 7
                                    ? 'bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white font-bold'
                                    : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                                @click="changePeriod(7)"
                            >
                                7 Days
                            </button>
                            <button
                                type="button"
                                class="px-2.5 py-1 rounded-md font-medium transition-all"
                                :class="currentDays === 14
                                    ? 'bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white font-bold'
                                    : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                                @click="changePeriod(14)"
                            >
                                14 Days
                            </button>
                            <button
                                type="button"
                                class="px-2.5 py-1 rounded-md font-medium transition-all"
                                :class="currentDays === 30
                                    ? 'bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white font-bold'
                                    : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                                @click="changePeriod(30)"
                            >
                                30 Days
                            </button>
                        </div>
                    </div>

                    <!-- Area Chart Component -->
                    <AreaTrendChart
                        v-if="trendMode === 'traffic'"
                        title="Referral Activity &amp; Customer Acquisition Trends"
                        :subtitle="`Daily breakdown of physical QR scans and Telegram leads over the last ${currentDays} days`"
                        :labels="safeLabels"
                        :dates="safeDates"
                        :series1-data="safeScans"
                        :series2-data="safeLeads"
                        :series1-config="{
                            name: 'QR Scans',
                            color: '#6366f1',
                            colorLight: '#a5b4fc',
                            isCurrency: false,
                        }"
                        :series2-config="{
                            name: 'Telegram Leads',
                            color: '#0ea5e9',
                            colorLight: '#7dd3fc',
                            isCurrency: false,
                        }"
                    />

                    <AreaTrendChart
                        v-else
                        title="Financial Revenue &amp; Driver Commission Volume"
                        :subtitle="`Daily confirmed translation revenue vs driver commission disbursements over the last ${currentDays} days`"
                        :labels="safeLabels"
                        :dates="safeDates"
                        :series1-data="safeRevenue"
                        :series2-data="safeCommissions"
                        :series1-config="{
                            name: 'Order Revenue',
                            color: '#10b981',
                            colorLight: '#6ee7b7',
                            isCurrency: true,
                        }"
                        :series2-config="{
                            name: 'Driver Commissions',
                            color: '#f59e0b',
                            colorLight: '#fcd34d',
                            isCurrency: true,
                        }"
                    />
                </div>

                <!-- SECTION 2: Conversion Funnel & Top Drivers Leaderboard (2 Columns) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Funnel Chart -->
                    <FunnelChart
                        :scans="props.funnel?.scans ?? 0"
                        :leads="props.funnel?.leads ?? (props.activeLeadsCount ?? 0)"
                        :orders="props.funnel?.orders ?? 0"
                        :paid-orders="props.funnel?.paid_orders ?? 0"
                        :scan-to-lead-rate="props.funnel?.scan_to_lead_rate"
                        :lead-to-order-rate="props.funnel?.lead_to_order_rate"
                        :order-to-paid-rate="props.funnel?.order_to_paid_rate"
                        :overall-rate="props.funnel?.overall_rate"
                    />

                    <!-- Top Drivers Leaderboard Chart -->
                    <BarRankChart
                        :drivers="props.topDrivers ?? []"
                    />
                </div>

                <!-- SECTION 3: Distribution Donut Charts & Financial Margin -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Donut 1: Order Status Breakdown -->
                    <DonutChart
                        title="Order Status Distribution"
                        subtitle="Fulfillment pipeline status breakdown"
                        :items="props.orderDistribution ?? []"
                        center-label="Orders"
                    />

                    <!-- Donut 2: Payment Status & Methods Breakdown -->
                    <div class="flex flex-col justify-between">
                        <DonutChart
                            v-if="paymentDonutTab === 'fulfillment'"
                            title="Payment Fulfillment"
                            subtitle="Reconciliation status of orders"
                            :items="props.paymentDistribution ?? []"
                            center-label="Orders"
                        />
                        <DonutChart
                            v-else
                            title="Payment Methods Share"
                            subtitle="Settlement channels by volume"
                            :items="props.paymentMethodDistribution ?? []"
                            center-label="Paid Total"
                            :is-currency="true"
                        />
                        <!-- Tab switcher underneath donut 2 -->
                        <div class="mt-2 flex justify-center">
                            <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-white dark:border-slate-800 dark:bg-slate-900 text-[11px]">
                                <button
                                    type="button"
                                    class="px-2.5 py-1 rounded font-medium transition-colors"
                                    :class="paymentDonutTab === 'fulfillment' ? 'bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white font-bold' : 'text-slate-500'"
                                    @click="paymentDonutTab = 'fulfillment'"
                                >
                                    Fulfillment Status
                                </button>
                                <button
                                    type="button"
                                    class="px-2.5 py-1 rounded font-medium transition-colors"
                                    :class="paymentDonutTab === 'methods' ? 'bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white font-bold' : 'text-slate-500'"
                                    @click="paymentDonutTab = 'methods'"
                                >
                                    Payment Methods
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Margin Breakdown Chart -->
                    <div class="lg:col-span-1 md:col-span-2">
                        <MarginBreakdownChart
                            :gross-revenue="props.financialMargin?.gross_revenue ?? (props.ordersSummary?.totalPaidAmount ?? 0)"
                            :driver-commissions="props.financialMargin?.driver_commissions ?? (props.commissionsSummary?.totalEarned ?? 0)"
                            :net-revenue="props.financialMargin?.net_revenue ?? 0"
                            :driver-share-percent="props.financialMargin?.driver_share_percent"
                            :company-margin-percent="props.financialMargin?.company_margin_percent"
                        />
                    </div>
                </div>

                <!-- SECTION 4: Detailed Order & Commission Ledgers Quick Summary -->
                <div v-if="props.ordersSummary" class="space-y-4">
                    <div class="border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                Orders Pipeline &amp; Customer Collections Summary
                            </h3>
                            <p class="text-xs text-slate-500">
                                Detailed reconciliation balances and payout accruals
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Link
                                v-if="$page.props.auth.can.viewOrders"
                                :href="route('orders.index')"
                                class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400"
                            >
                                All Orders &rarr;
                            </Link>
                            <Link
                                v-if="props.commissionsSummary && $page.props.auth.can.viewCommissions"
                                :href="route('commissions.index')"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            >
                                All Commissions &rarr;
                            </Link>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <span class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider block">Pending Confirmation</span>
                            <div class="mt-1 text-2xl font-black text-slate-900 dark:text-white font-mono">
                                {{ props.ordersSummary.newOrders }}
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">Awaiting customer approve</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <span class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider block">In Translation</span>
                            <div class="mt-1 text-2xl font-black text-slate-900 dark:text-white font-mono">
                                {{ props.ordersSummary.confirmedOrders }}
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">Active translation work</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">Completed &amp; Delivered</span>
                            <div class="mt-1 text-2xl font-black text-slate-900 dark:text-white font-mono">
                                {{ props.ordersSummary.completedOrders }}
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">Delivered documents</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <span class="text-[11px] font-semibold text-rose-500 uppercase tracking-wider block">Outstanding Uncollected</span>
                            <div class="mt-1 text-2xl font-black font-mono text-rose-600 dark:text-rose-400">
                                ${{ Number(props.ordersSummary.totalOutstandingAmount).toFixed(2) }}
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">Total Paid: ${{ Number(props.ordersSummary.totalPaidAmount).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
