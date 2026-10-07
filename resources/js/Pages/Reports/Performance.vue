<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface DriverStat {
    driver_id: number;
    name: string;
    phone: string;
    partner_name: string;
    status: string;
    vehicles: string;
    active_codes_count: number;
    total_scans: number;
    total_leads: number;
    total_orders: number;
    confirmed_orders: number;
    conversion_rate: number;
    total_revenue: string;
    commission_earned: string;
    commission_approved: string;
    commission_paid: string;
    commission_outstanding: string;
}

interface PartnerStat {
    partner_id: number;
    name: string;
    type: string;
    status: string;
    commission_rate: string | number;
    drivers_count: number;
    total_scans: number;
    total_leads: number;
    total_orders: number;
    confirmed_orders: number;
    conversion_rate: number;
    total_revenue: string;
    commission_earned: string;
    commission_approved: string;
    commission_paid: string;
    commission_outstanding: string;
}

interface SummaryData {
    total_scans: number;
    total_leads: number;
    total_orders: number;
    confirmed_orders: number;
    conversion_rate: number;
    total_revenue: string;
    total_collected: string;
    total_commissions_earned: string;
    total_commissions_approved: string;
    total_commissions_paid: string;
    outstanding_commissions: string;
}

const props = defineProps<{
    summary: SummaryData;
    driverPerformance: DriverStat[];
    partnerPerformance: PartnerStat[];
    filters: {
        date_from: string;
        date_to: string;
    };
    can: {
        export: boolean;
    };
}>();

const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);
const activeTab = ref<'drivers' | 'partners'>('drivers');
const searchFilter = ref('');
const showExportMenu = ref(false);

const applyFilter = () => {
    router.get(
        route('reports.performance'),
        {
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const clearFilter = () => {
    dateFrom.value = '';
    dateTo.value = '';
    applyFilter();
};

const setPreset = (preset: 'today' | 'this_month' | 'all') => {
    const today = new Date();
    if (preset === 'today') {
        const formatted = today.toISOString().split('T')[0];
        dateFrom.value = formatted;
        dateTo.value = formatted;
    } else if (preset === 'this_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
        dateFrom.value = firstDay;
        dateTo.value = lastDay;
    } else if (preset === 'all') {
        dateFrom.value = '';
        dateTo.value = '';
    }
    applyFilter();
};

const filteredDrivers = computed(() => {
    if (!searchFilter.value) return props.driverPerformance;
    const term = searchFilter.value.toLowerCase();
    return props.driverPerformance.filter(
        d => d.name.toLowerCase().includes(term) ||
             d.phone.toLowerCase().includes(term) ||
             d.partner_name.toLowerCase().includes(term) ||
             d.vehicles.toLowerCase().includes(term)
    );
});

const filteredPartners = computed(() => {
    if (!searchFilter.value) return props.partnerPerformance;
    const term = searchFilter.value.toLowerCase();
    return props.partnerPerformance.filter(
        p => p.name.toLowerCase().includes(term) ||
             p.type.toLowerCase().includes(term)
    );
});

const getExportUrl = (type: 'drivers' | 'commissions' | 'orders') => {
    const params = new URLSearchParams();
    if (dateFrom.value) params.append('date_from', dateFrom.value);
    if (dateTo.value) params.append('date_to', dateTo.value);
    const queryString = params.toString() ? `?${params.toString()}` : '';

    if (type === 'drivers') return route('reports.export.drivers') + queryString;
    if (type === 'commissions') return route('reports.export.commissions') + queryString;
    return route('reports.export.orders') + queryString;
};
</script>

<template>
    <Head title="Performance & Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold leading-tight text-slate-900 dark:text-white">
                        Performance &amp; Referral Analytics
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Executive insight into QR scans, lead conversion, revenue, and driver commissions.
                    </p>
                </div>

                <!-- Export CSV Dropdown -->
                <div v-if="can.export" class="relative">
                    <button
                        @click="showExportMenu = !showExportMenu"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export CSV Reports
                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        v-if="showExportMenu"
                        @click="showExportMenu = false"
                        class="absolute right-0 mt-2 w-56 rounded-xl bg-white dark:bg-slate-800 shadow-xl border border-slate-200 dark:border-slate-700 py-1.5 z-50 text-sm"
                    >
                        <a
                            :href="getExportUrl('drivers')"
                            class="flex items-center gap-2.5 px-4 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                        >
                            <span>📊 Driver Performance CSV</span>
                        </a>
                        <a
                            :href="getExportUrl('commissions')"
                            class="flex items-center gap-2.5 px-4 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                        >
                            <span>💰 Commissions Ledger CSV</span>
                        </a>
                        <a
                            :href="getExportUrl('orders')"
                            class="flex items-center gap-2.5 px-4 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                        >
                            <span>📦 Orders Fulfillment CSV</span>
                        </a>
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filter Bar with Presets -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <!-- Presets -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold uppercase text-slate-400">Presets:</span>
                    <button
                        @click="setPreset('today')"
                        class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 transition-colors"
                    >
                        Today
                    </button>
                    <button
                        @click="setPreset('this_month')"
                        class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 transition-colors"
                    >
                        This Month
                    </button>
                    <button
                        @click="setPreset('all')"
                        class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 transition-colors"
                    >
                        All Time
                    </button>
                </div>

                <!-- Date Inputs -->
                <div class="flex items-center gap-2">
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="text-xs py-1.5 px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                        placeholder="Start Date"
                    />
                    <span class="text-xs text-slate-400">to</span>
                    <input
                        v-model="dateTo"
                        type="date"
                        class="text-xs py-1.5 px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                        placeholder="End Date"
                    />
                    <button
                        @click="applyFilter"
                        class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors"
                    >
                        Apply
                    </button>
                    <button
                        v-if="dateFrom || dateTo"
                        @click="clearFilter"
                        class="px-2.5 py-1.5 text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Executive Summary KPI Tiles -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- QR Scans -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Scans</span>
                    <div class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-white">
                        {{ summary.total_scans }}
                    </div>
                    <span class="text-xs text-slate-400 mt-1 block">Physical QR scans</span>
                </div>

                <!-- Total Leads -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Telegram Leads</span>
                    <div class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-white">
                        {{ summary.total_leads }}
                    </div>
                    <span class="text-xs text-slate-400 mt-1 block">Customer conversations</span>
                </div>

                <!-- Conversion Rate -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
                    <span class="text-xs font-medium uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Conversion Rate</span>
                    <div class="mt-2 text-2xl font-extrabold text-indigo-600 dark:text-indigo-400">
                        {{ summary.conversion_rate }}%
                    </div>
                    <span class="text-xs text-slate-400 mt-1 block">{{ summary.confirmed_orders }} confirmed orders</span>
                </div>

                <!-- Total Revenue -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Order Revenue</span>
                    <div class="mt-2 text-2xl font-bold font-mono text-slate-900 dark:text-white">
                        ${{ summary.total_revenue }}
                    </div>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 block">${{ summary.total_collected }} collected</span>
                </div>

                <!-- Commissions Earned -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
                    <span class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Commissions Paid</span>
                    <div class="mt-2 text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                        ${{ summary.total_commissions_paid }}
                    </div>
                    <span class="text-xs text-slate-400 mt-1 block">Total earned: ${{ summary.total_commissions_earned }}</span>
                </div>

                <!-- Outstanding Commissions -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
                    <span class="text-xs font-medium uppercase tracking-wider text-amber-600 dark:text-amber-400">Outstanding Liability</span>
                    <div class="mt-2 text-2xl font-bold font-mono text-amber-600 dark:text-amber-400">
                        ${{ summary.outstanding_commissions }}
                    </div>
                    <span class="text-xs text-slate-400 mt-1 block">Earned & approved unpaid</span>
                </div>
            </div>

            <!-- Table Navigation & Search -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-700 pb-3">
                <!-- Tabs -->
                <div class="flex gap-4">
                    <button
                        @click="activeTab = 'drivers'"
                        :class="[
                            'pb-2 text-sm font-semibold transition-colors border-b-2',
                            activeTab === 'drivers'
                                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'
                        ]"
                    >
                        Driver Performance ({{ filteredDrivers.length }})
                    </button>
                    <button
                        @click="activeTab = 'partners'"
                        :class="[
                            'pb-2 text-sm font-semibold transition-colors border-b-2',
                            activeTab === 'partners'
                                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'
                        ]"
                    >
                        Partner Performance ({{ filteredPartners.length }})
                    </button>
                </div>

                <!-- Instant Search Filter -->
                <div class="w-full sm:w-64">
                    <input
                        v-model="searchFilter"
                        type="text"
                        placeholder="Filter list..."
                        class="w-full py-1.5 px-3 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg dark:text-white"
                    />
                </div>
            </div>

            <!-- Driver Breakdown Table -->
            <div v-if="activeTab === 'drivers'" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 font-semibold text-xs uppercase tracking-wider">
                                <th class="py-3 px-4">Driver Name</th>
                                <th class="py-3 px-4">Partner &amp; Vehicles</th>
                                <th class="py-3 px-4 text-center">Scans</th>
                                <th class="py-3 px-4 text-center">Leads</th>
                                <th class="py-3 px-4 text-center">Orders</th>
                                <th class="py-3 px-4 text-center">Conversion</th>
                                <th class="py-3 px-4 text-right">Revenue ($)</th>
                                <th class="py-3 px-4 text-right">Earned ($)</th>
                                <th class="py-3 px-4 text-right">Paid ($)</th>
                                <th class="py-3 px-4 text-right">Outstanding ($)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="d in filteredDrivers" :key="d.driver_id" class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30">
                                <td class="py-3.5 px-4 font-medium text-slate-800 dark:text-slate-200">
                                    {{ d.name }}
                                    <div class="text-xs text-slate-400 font-mono">{{ d.phone }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-500">
                                    {{ d.partner_name }}
                                    <div class="font-mono text-slate-400">Plate: {{ d.vehicles }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {{ d.total_scans }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {{ d.total_leads }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {{ d.confirmed_orders }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400">
                                        {{ d.conversion_rate }}%
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    ${{ d.total_revenue }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                    ${{ d.commission_earned }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-medium text-emerald-600 dark:text-emerald-400">
                                    ${{ d.commission_paid }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-amber-600 dark:text-amber-400">
                                    ${{ d.commission_outstanding }}
                                </td>
                            </tr>
                            <tr v-if="filteredDrivers.length === 0">
                                <td colspan="10" class="py-8 text-center text-xs text-slate-400">
                                    No driver performance records match the current filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Partner Breakdown Table -->
            <div v-if="activeTab === 'partners'" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 font-semibold text-xs uppercase tracking-wider">
                                <th class="py-3 px-4">Partner Name</th>
                                <th class="py-3 px-4">Type</th>
                                <th class="py-3 px-4 text-center">Drivers</th>
                                <th class="py-3 px-4 text-center">Rate</th>
                                <th class="py-3 px-4 text-center">Scans</th>
                                <th class="py-3 px-4 text-center">Leads</th>
                                <th class="py-3 px-4 text-center">Orders</th>
                                <th class="py-3 px-4 text-center">Conversion</th>
                                <th class="py-3 px-4 text-right">Revenue ($)</th>
                                <th class="py-3 px-4 text-right">Earned ($)</th>
                                <th class="py-3 px-4 text-right">Outstanding ($)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="p in filteredPartners" :key="p.partner_id" class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30">
                                <td class="py-3.5 px-4 font-medium text-slate-800 dark:text-slate-200">
                                    {{ p.name }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-500">
                                    {{ p.type }}
                                </td>
                                <td class="py-3.5 px-4 text-center text-slate-700 dark:text-slate-300">
                                    {{ p.drivers_count }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                        {{ Number(p.commission_rate).toFixed(1) }}%
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {{ p.total_scans }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {{ p.total_leads }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {{ p.confirmed_orders }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400">
                                        {{ p.conversion_rate }}%
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    ${{ p.total_revenue }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                    ${{ p.commission_earned }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-amber-600 dark:text-amber-400">
                                    ${{ p.commission_outstanding }}
                                </td>
                            </tr>
                            <tr v-if="filteredPartners.length === 0">
                                <td colspan="11" class="py-8 text-center text-xs text-slate-400">
                                    No partner performance records match the current filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
