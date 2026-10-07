<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface PartnerReport {
    id: number;
    name: string;
    type: string;
    commission_rate: string | number;
    total_orders: number;
    earned_amount: string;
    approved_amount: string;
    paid_amount: string;
    outstanding_amount: string;
}

interface DriverReport {
    id: number;
    name: string;
    phone: string;
    partner_name: string;
    total_orders: number;
    earned_amount: string;
    approved_amount: string;
    paid_amount: string;
    outstanding_amount: string;
}

const props = defineProps<{
    partnerReports: PartnerReport[];
    driverReports: DriverReport[];
    filters: {
        date_from: string;
        date_to: string;
    };
}>();

const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);
const activeTab = ref<'partners' | 'drivers'>('partners');

const applyFilter = () => {
    router.get(
        route('commissions.reports'),
        {
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilter = () => {
    dateFrom.value = '';
    dateTo.value = '';
    applyFilter();
};
</script>

<template>
    <Head title="Commission Reports" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('commissions.index')"
                        class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold leading-tight text-slate-900 dark:text-white">
                            Commission Reports & Summaries
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Aggregate referral commissions and payout performance by partner and driver.
                        </p>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="flex items-center gap-2">
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="text-xs py-1.5 px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                        placeholder="Start Date"
                    />
                    <span class="text-xs text-slate-400">to</span>
                    <input
                        v-model="dateTo"
                        type="date"
                        class="text-xs py-1.5 px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                        placeholder="End Date"
                    />
                    <button
                        @click="applyFilter"
                        class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors"
                    >
                        Filter
                    </button>
                    <button
                        v-if="dateFrom || dateTo"
                        @click="resetFilter"
                        class="px-2.5 py-1.5 text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400"
                    >
                        Clear
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tabs -->
            <div class="flex border-b border-slate-200 dark:border-slate-700">
                <button
                    @click="activeTab = 'partners'"
                    :class="[
                        'pb-3 px-4 text-sm font-semibold transition-colors border-b-2',
                        activeTab === 'partners'
                            ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'
                    ]"
                >
                    By Referral Partner ({{ partnerReports.length }})
                </button>
                <button
                    @click="activeTab = 'drivers'"
                    :class="[
                        'pb-3 px-4 text-sm font-semibold transition-colors border-b-2',
                        activeTab === 'drivers'
                            ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'
                    ]"
                >
                    By Driver ({{ driverReports.length }})
                </button>
            </div>

            <!-- Partner Breakdown Table -->
            <div v-if="activeTab === 'partners'" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 font-semibold text-xs uppercase tracking-wider">
                                <th class="py-3 px-4">Partner Name</th>
                                <th class="py-3 px-4">Type</th>
                                <th class="py-3 px-4 text-center">Commission Rate</th>
                                <th class="py-3 px-4 text-center">Orders Referred</th>
                                <th class="py-3 px-4 text-right">Total Earned</th>
                                <th class="py-3 px-4 text-right">Total Paid</th>
                                <th class="py-3 px-4 text-right">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="p in partnerReports" :key="p.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30">
                                <td class="py-3.5 px-4 font-medium text-slate-800 dark:text-slate-200">
                                    {{ p.name }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-500">
                                    {{ p.type }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                        {{ Number(p.commission_rate).toFixed(1) }}%
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {{ p.total_orders }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    ${{ p.earned_amount }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-medium text-emerald-600 dark:text-emerald-400">
                                    ${{ p.paid_amount }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-amber-600 dark:text-amber-400">
                                    ${{ p.outstanding_amount }}
                                </td>
                            </tr>
                            <tr v-if="partnerReports.length === 0">
                                <td colspan="7" class="py-8 text-center text-xs text-slate-400">
                                    No referral partner records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Driver Breakdown Table -->
            <div v-if="activeTab === 'drivers'" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 font-semibold text-xs uppercase tracking-wider">
                                <th class="py-3 px-4">Driver Name</th>
                                <th class="py-3 px-4">Phone</th>
                                <th class="py-3 px-4">Affiliated Partner</th>
                                <th class="py-3 px-4 text-center">Orders Referred</th>
                                <th class="py-3 px-4 text-right">Total Earned</th>
                                <th class="py-3 px-4 text-right">Total Paid</th>
                                <th class="py-3 px-4 text-right">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="d in driverReports" :key="d.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30">
                                <td class="py-3.5 px-4 font-medium text-slate-800 dark:text-slate-200">
                                    {{ d.name }}
                                </td>
                                <td class="py-3.5 px-4 text-xs font-mono text-slate-500">
                                    {{ d.phone }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-500">
                                    {{ d.partner_name }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {{ d.total_orders }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    ${{ d.earned_amount }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-medium text-emerald-600 dark:text-emerald-400">
                                    ${{ d.paid_amount }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-amber-600 dark:text-amber-400">
                                    ${{ d.outstanding_amount }}
                                </td>
                            </tr>
                            <tr v-if="driverReports.length === 0">
                                <td colspan="7" class="py-8 text-center text-xs text-slate-400">
                                    No driver records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
