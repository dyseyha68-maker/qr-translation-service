<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { Order, PaginatedData } from '@/types';

const props = defineProps<{
    orders: PaginatedData<Order>;
    filters: {
        search: string;
        status: string;
        payment_status: string;
        partner_id: string;
        date_from: string;
        date_to: string;
    };
    statuses: string[];
    paymentStatuses: string[];
    partners: { id: number; name: string }[];
}>();

const search = ref(props.filters.search);
const selectedStatus = ref(props.filters.status);
const selectedPaymentStatus = ref(props.filters.payment_status);
const selectedPartner = ref(props.filters.partner_id);
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        route('orders.index'),
        {
            search: search.value || undefined,
            status: selectedStatus.value || undefined,
            payment_status: selectedPaymentStatus.value || undefined,
            partner_id: selectedPartner.value || undefined,
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

watch([selectedStatus, selectedPaymentStatus, selectedPartner, dateFrom, dateTo], applyFilters);

const resetFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedPaymentStatus.value = '';
    selectedPartner.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const getOrderStatusBadge = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
        case 'confirmed':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-200 dark:border-blue-800';
        case 'completed':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
        case 'cancelled':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300';
    }
};

const getPaymentStatusBadge = (status: string) => {
    switch (status) {
        case 'paid':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
        case 'partial':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
        case 'unpaid':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
        case 'refunded':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300 border border-purple-200 dark:border-purple-800';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300';
    }
};
</script>

<template>
    <Head title="Orders & Payments" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Orders & Payments
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Track translation orders, customer payment fulfillment, and driver referral attributions
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Search & Multi-Filter Bar -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="md:col-span-2">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search order #, customer, phone, driver, referral code..."
                                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500"
                            />
                        </div>

                        <div>
                            <select
                                v-model="selectedStatus"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                <option value="">All Order Statuses</option>
                                <option v-for="st in statuses" :key="st" :value="st">
                                    {{ st.toUpperCase() }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <select
                                v-model="selectedPaymentStatus"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                <option value="">All Payment States</option>
                                <option v-for="pst in paymentStatuses" :key="pst" :value="pst">
                                    {{ pst.toUpperCase() }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
                        <div>
                            <label class="block text-slate-500 mb-1">Referral Partner</label>
                            <select
                                v-model="selectedPartner"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                <option value="">All Referral Partners</option>
                                <option v-for="partner in partners" :key="partner.id" :value="partner.id">
                                    {{ partner.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">Date From</label>
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-slate-500 mb-1">Date To</label>
                            <input
                                v-model="dateTo"
                                type="date"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                        </div>

                        <div class="flex items-end">
                            <button
                                @click="resetFilters"
                                type="button"
                                class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition"
                            >
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="px-5 py-3.5">Order #</th>
                                    <th class="px-5 py-3.5">Customer</th>
                                    <th class="px-5 py-3.5">Referral Partner / Driver</th>
                                    <th class="px-5 py-3.5 text-right">Total</th>
                                    <th class="px-5 py-3.5 text-right">Paid</th>
                                    <th class="px-5 py-3.5 text-right">Outstanding</th>
                                    <th class="px-5 py-3.5 text-center">Payment</th>
                                    <th class="px-5 py-3.5 text-center">Status</th>
                                    <th class="px-5 py-3.5">Created</th>
                                    <th class="px-5 py-3.5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <tr
                                    v-for="order in orders.data"
                                    :key="order.id"
                                    class="hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors"
                                >
                                    <td class="px-5 py-4 font-mono font-bold text-blue-600 dark:text-blue-400">
                                        <Link :href="route('orders.show', order.id)" class="hover:underline">
                                            {{ order.order_number }}
                                        </Link>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-slate-900 dark:text-white">
                                            {{ order.customer?.name ?? 'Anonymous Customer' }}
                                        </div>
                                        <div class="text-xs text-slate-400" v-if="order.customer?.phone">
                                            {{ order.customer.phone }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div v-if="order.referral_attribution" class="space-y-0.5">
                                            <div class="font-medium text-slate-900 dark:text-white text-xs">
                                                {{ order.referral_attribution.referral_partner?.name ?? 'Direct Referral' }}
                                            </div>
                                            <div class="text-xs text-slate-400">
                                                Driver: {{ order.referral_attribution.driver?.name ?? 'N/A' }}
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-slate-400 italic">
                                            Direct (No Referral)
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono font-semibold text-slate-900 dark:text-white">
                                        ${{ Number(order.total_amount).toFixed(2) }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                        ${{ Number(order.paid_amount).toFixed(2) }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono font-semibold" :class="order.outstanding_amount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400'">
                                        ${{ Number(order.outstanding_amount).toFixed(2) }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span
                                            :class="getPaymentStatusBadge(order.payment_status)"
                                            class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                                        >
                                            {{ order.payment_status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span
                                            :class="getOrderStatusBadge(order.status)"
                                            class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                                        >
                                            {{ order.status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-500 whitespace-nowrap">
                                        {{ new Date(order.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <Link
                                            :href="route('orders.show', order.id)"
                                            class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-400 dark:hover:bg-blue-900/80 transition-colors"
                                        >
                                            View &rarr;
                                        </Link>
                                    </td>
                                </tr>

                                <tr v-if="orders.data.length === 0">
                                    <td colspan="10" class="px-6 py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center">
                                            <span class="text-3xl mb-2">📑</span>
                                            <p class="font-medium">No orders found.</p>
                                            <p class="text-xs text-slate-500 mt-1">Orders are created from qualified customer leads.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="orders.links.length > 3"
                        class="flex items-center justify-between border-t border-slate-200 px-6 py-4 dark:border-slate-800"
                    >
                        <p class="text-xs text-slate-500">
                            Showing {{ orders.from ?? 0 }} to {{ orders.to ?? 0 }} of {{ orders.total }} orders
                        </p>
                        <div class="flex gap-1">
                            <Component
                                :is="link.url ? Link : 'span'"
                                v-for="(link, i) in orders.links"
                                :key="i"
                                :href="link.url || undefined"
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-md transition-colors',
                                    link.active
                                        ? 'bg-blue-600 text-white font-semibold'
                                        : link.url
                                          ? 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'
                                          : 'text-slate-300 dark:text-slate-700 cursor-not-allowed',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
