<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { Commission, PaginatedData } from '@/types';

const props = defineProps<{
    commissions: PaginatedData<Commission>;
    filters: {
        search: string;
        status: string;
        partner_id: string;
        driver_id: string;
        date_from: string;
        date_to: string;
    };
    statuses: { value: string; label: string; color: string }[];
    partners: { id: number; name: string }[];
    drivers: { id: number; name: string; referral_partner_id: number }[];
    metrics: {
        total_earned: string;
        total_approved: string;
        total_paid: string;
        total_reversed: string;
        outstanding_unpaid: string;
        counts: Record<string, number>;
    };
}>();

const search = ref(props.filters.search);
const selectedStatus = ref(props.filters.status);
const selectedPartner = ref(props.filters.partner_id);
const selectedDriver = ref(props.filters.driver_id);
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        route('commissions.index'),
        {
            search: search.value || undefined,
            status: selectedStatus.value || undefined,
            partner_id: selectedPartner.value || undefined,
            driver_id: selectedDriver.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch([selectedStatus, selectedPartner, selectedDriver, dateFrom, dateTo], applyFilters);

const resetFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedPartner.value = '';
    selectedDriver.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const getStatusBadgeClass = (status: string) => {
    switch (status?.toUpperCase()) {
        case 'PAID':
            return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-500/30';
        case 'APPROVED':
            return 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-400 dark:ring-blue-500/30';
        case 'EARNED':
            return 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-400 dark:ring-amber-500/30';
        case 'REVERSED':
            return 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-400 dark:ring-rose-500/30';
        case 'PENDING':
        default:
            return 'bg-slate-50 text-slate-700 ring-slate-600/20 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-500/30';
    }
};

const approveCommission = (commission: Commission) => {
    if (confirm(`Approve commission ${commission.commission_reference} for \$${commission.commission_amount}?`)) {
        router.patch(route('commissions.approve', commission.id));
    }
};
</script>

<template>
    <Head title="Commissions & Driver Payouts" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold leading-tight text-slate-900 dark:text-white">
                        Commissions & Driver Payouts
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Track driver commissions, approve pending disbursements, and view referral earnings.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.can.viewCommissionReports"
                        :href="route('commissions.reports')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition-colors"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Commission Reports
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
            <!-- Metrics Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Total Earned -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Earned</span>
                        <span class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                        ${{ metrics.total_earned }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ (metrics.counts.earned ?? metrics.counts.EARNED) || 0 }} earned, {{ (metrics.counts.approved ?? metrics.counts.APPROVED) || 0 }} approved
                    </div>
                </div>

                <!-- Outstanding Unpaid -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-amber-600 dark:text-amber-400">Outstanding Unpaid</span>
                        <span class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
                        ${{ metrics.outstanding_unpaid }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Awaiting payout
                    </div>
                </div>

                <!-- Approved -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-blue-600 dark:text-blue-400">Approved</span>
                        <span class="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">
                        ${{ metrics.total_approved }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ (metrics.counts.approved ?? metrics.counts.APPROVED) || 0 }} commissions ready for payout
                    </div>
                </div>

                <!-- Total Paid -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Paid Out</span>
                        <span class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        ${{ metrics.total_paid }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ (metrics.counts.paid ?? metrics.counts.PAID) || 0 }} commissions settled
                    </div>
                </div>

                <!-- Reversed -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-rose-600 dark:text-rose-400">Reversed</span>
                        <span class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">
                        ${{ metrics.total_reversed }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ metrics.counts.REVERSED || 0 }} refunded / cancelled
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <!-- Search Input -->
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Ref, order #, customer, driver..."
                                class="w-full pl-9 pr-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                            />
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Status</label>
                        <select
                            v-model="selectedStatus"
                            class="w-full py-2 px-3 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="st in statuses" :key="st.value" :value="st.value">
                                {{ st.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Partner Filter -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Partner</label>
                        <select
                            v-model="selectedPartner"
                            class="w-full py-2 px-3 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                        >
                            <option value="">All Partners</option>
                            <option v-for="p in partners" :key="p.id" :value="p.id">
                                {{ p.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">From Date</label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="w-full py-2 px-3 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                        />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">To Date</label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="w-full py-2 px-3 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                        />
                    </div>
                </div>

                <div v-if="search || selectedStatus || selectedPartner || selectedDriver || dateFrom || dateTo" class="mt-3 flex justify-end">
                    <button
                        @click="resetFilters"
                        class="text-xs font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors"
                    >
                        Clear all filters
                    </button>
                </div>
            </div>

            <!-- Commissions Table -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 font-semibold text-xs uppercase tracking-wider">
                                <th class="py-3 px-4">Commission Ref</th>
                                <th class="py-3 px-4">Order Ref</th>
                                <th class="py-3 px-4">Customer</th>
                                <th class="py-3 px-4">Driver / Partner</th>
                                <th class="py-3 px-4 text-right">Eligible Amount</th>
                                <th class="py-3 px-4 text-center">Rate</th>
                                <th class="py-3 px-4 text-right">Commission</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4">Earned Date</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="c in commissions.data"
                                :key="c.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors"
                            >
                                <!-- Commission Reference -->
                                <td class="py-3.5 px-4 font-mono font-medium">
                                    <Link
                                        :href="route('commissions.show', c.id)"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        {{ c.commission_reference }}
                                    </Link>
                                </td>

                                <!-- Order Reference -->
                                <td class="py-3.5 px-4 font-mono">
                                    <Link
                                        v-if="c.order"
                                        :href="route('orders.show', c.order.id)"
                                        class="text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline"
                                    >
                                        {{ c.order.order_number }}
                                    </Link>
                                    <span v-else class="text-slate-400">N/A</span>
                                </td>

                                <!-- Customer -->
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">
                                        {{ c.order?.customer?.name || 'Walk-in Customer' }}
                                    </div>
                                    <div v-if="c.order?.customer?.phone" class="text-xs text-slate-400 font-mono">
                                        {{ c.order.customer.phone }}
                                    </div>
                                </td>

                                <!-- Driver & Partner -->
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">
                                        {{ c.driver?.name || 'Unknown Driver' }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ c.referral_partner?.name || 'Direct Partner' }}
                                        <span v-if="c.vehicle" class="font-mono">({{ c.vehicle.plate_number }})</span>
                                    </div>
                                </td>

                                <!-- Eligible Amount -->
                                <td class="py-3.5 px-4 text-right font-medium text-slate-700 dark:text-slate-300">
                                    ${{ Number(c.eligible_amount).toFixed(2) }}
                                </td>

                                <!-- Rate -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                        {{ Number(c.commission_rate).toFixed(1) }}%
                                    </span>
                                </td>

                                <!-- Commission Amount -->
                                <td class="py-3.5 px-4 text-right font-bold text-slate-900 dark:text-white">
                                    ${{ Number(c.commission_amount).toFixed(2) }}
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        :class="[
                                            getStatusBadgeClass(c.status),
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset'
                                        ]"
                                    >
                                        {{ c.status }}
                                    </span>
                                </td>

                                <!-- Earned Date -->
                                <td class="py-3.5 px-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ c.earned_at ? new Date(c.earned_at).toLocaleDateString() : 'N/A' }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            v-if="['earned', 'EARNED'].includes(c.status) && $page.props.auth.can.approveCommissions"
                                            @click="approveCommission(c)"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-300 dark:hover:bg-blue-900/60 rounded border border-blue-200 dark:border-blue-800 transition-colors"
                                            title="Approve Commission"
                                        >
                                            Approve
                                        </button>
                                        <Link
                                            v-if="['approved', 'APPROVED'].includes(c.status) && $page.props.auth.can.payCommissions"
                                            :href="route('commissions.show', c.id) + '?action=payout'"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-900/60 rounded border border-emerald-200 dark:border-emerald-800 transition-colors"
                                            title="Record Driver Payout"
                                        >
                                            Payout
                                        </Link>
                                        <Link
                                            :href="route('commissions.show', c.id)"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 rounded transition-colors"
                                        >
                                            View
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="commissions.data.length === 0">
                                <td colspan="10" class="py-12 text-center">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">
                                        No commissions found
                                    </p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                        Commissions are automatically generated when referred orders are fully paid.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="commissions.links && commissions.links.length > 3" class="border-t border-slate-200 dark:border-slate-700 px-4 py-3 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-between">
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        Showing {{ commissions.from || 0 }} to {{ commissions.to || 0 }} of {{ commissions.total }} records
                    </div>
                    <div class="flex gap-1">
                        <template v-for="(link, i) in commissions.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1 text-xs rounded border transition-colors',
                                    link.active
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1 text-xs rounded border border-transparent text-slate-400 dark:text-slate-600"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </AuthenticatedLayout>
</template>
