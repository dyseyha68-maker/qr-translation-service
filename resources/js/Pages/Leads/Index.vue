<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { Lead, PaginatedData } from '@/types';

const props = defineProps<{
    leads: PaginatedData<Lead>;
    filters: {
        search: string;
        status: string;
    };
    statuses: string[];
}>();

const search = ref(props.filters.search);
const selectedStatus = ref(props.filters.status);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        route('leads.index'),
        {
            search: search.value || undefined,
            status: selectedStatus.value || undefined,
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

watch(selectedStatus, applyFilters);

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'NEW':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-200 dark:border-blue-800';
        case 'CONTACTED':
            return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800';
        case 'QUOTATION':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
        case 'CONFIRMED':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
        case 'LOST':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
        case 'CANCELLED':
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300';
    }
};
</script>

<template>
    <Head title="Leads & Inquiries" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Leads & Opportunities
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Inquiries attributed to driver QR scans & Telegram bot interactions
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Filters & Search Toolbar -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="sr-only">Search</label>
                            <div class="relative">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search by customer, @telegram, driver, or referral code..."
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500"
                                />
                            </div>
                        </div>

                        <div>
                            <select
                                v-model="selectedStatus"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                <option value="">All Pipeline Statuses</option>
                                <option v-for="status in statuses" :key="status" :value="status">
                                    {{ status }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-slate-50 dark:bg-slate-800/50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Lead ID & Title
                                    </th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Customer / Telegram
                                    </th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Referring Driver
                                    </th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Status
                                    </th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Assigned To
                                    </th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Date
                                    </th>
                                    <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900">
                                <tr v-if="leads.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                                        No leads found matching your criteria.
                                    </td>
                                </tr>
                                <tr
                                    v-for="lead in leads.data"
                                    :key="lead.id"
                                    class="hover:bg-slate-50/75 dark:hover:bg-slate-800/50 transition-colors"
                                >
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900 dark:text-white">
                                            #{{ lead.id }} {{ lead.title }}
                                        </div>
                                        <div class="text-xs text-slate-500 mt-0.5">
                                            {{ lead.source }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-900 dark:text-white">
                                            {{ lead.customer?.name ?? 'Anonymous' }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 font-mono" v-if="lead.telegram_user?.username">
                                            @{{ lead.telegram_user.username }}
                                        </div>
                                        <div class="text-xs text-slate-400" v-else-if="lead.telegram_user?.telegram_id">
                                            ID: {{ lead.telegram_user.telegram_id }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="lead.referral_attribution" class="space-y-0.5">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white flex items-center gap-1.5">
                                                <span>{{ lead.referral_attribution.driver?.name ?? 'Unknown Driver' }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs">
                                                <span class="font-mono text-blue-600 dark:text-blue-400 font-semibold">
                                                    {{ lead.referral_attribution.referral_code?.code }}
                                                </span>
                                                <span class="text-slate-400" v-if="lead.referral_attribution.vehicle">
                                                    ({{ lead.referral_attribution.vehicle.plate_number }})
                                                </span>
                                            </div>
                                        </div>
                                        <div v-else class="text-xs text-slate-400 italic">
                                            Direct / Unattributed
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="getStatusBadge(lead.status)"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                        >
                                            {{ lead.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                                        {{ lead.assigned_user?.name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        {{ new Date(lead.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Link
                                            :href="route('leads.show', lead.id)"
                                            class="inline-flex items-center justify-center rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-400 dark:hover:bg-blue-900/80 transition-colors"
                                        >
                                            Manage &rarr;
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="leads.links.length > 3"
                        class="flex items-center justify-between border-t border-slate-200 bg-white px-6 py-3.5 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="text-sm text-slate-500 dark:text-slate-400">
                            Showing {{ leads.from ?? 0 }} to {{ leads.to ?? 0 }} of {{ leads.total }} leads
                        </div>
                        <div class="flex gap-1">
                            <template v-for="(link, idx) in leads.links" :key="idx">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 text-xs font-medium rounded-md transition-colors',
                                        link.active
                                            ? 'bg-blue-600 text-white'
                                            : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
                                    ]"
                                />
                                <span
                                    v-else
                                    v-html="link.label"
                                    class="px-3 py-1 text-xs text-slate-400 cursor-not-allowed"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
