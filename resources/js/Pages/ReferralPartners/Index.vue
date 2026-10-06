<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface PartnerItem {
    id: number;
    name: string;
    type: string;
    phone?: string | null;
    email?: string | null;
    company_name?: string | null;
    status: 'ACTIVE' | 'INACTIVE';
    drivers_count: number;
    created_at: string;
    can: {
        view: boolean;
        edit: boolean;
        delete: boolean;
    };
}

interface PaginatedData<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
}

const props = defineProps<{
    partners: PaginatedData<PartnerItem>;
    filters: {
        search?: string;
        type?: string;
        status?: string;
    };
    types: string[];
    statuses: string[];
    can: {
        create_partner: boolean;
    };
}>();

const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');
const statusFilter = ref(props.filters.status || '');

function applyFilters() {
    router.get(
        route('referral-partners.index'),
        {
            search: search.value || undefined,
            type: typeFilter.value || undefined,
            status: statusFilter.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

function resetFilters() {
    search.value = '';
    typeFilter.value = '';
    statusFilter.value = '';
    applyFilters();
}
</script>

<template>
    <Head title="Referral Partners" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Referral Partners
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Taxi fleets, companies, and transportation associations referring customers.
                    </p>
                </div>
                <div v-if="props.can.create_partner">
                    <Link
                        :href="route('referral-partners.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600/50 transition duration-150"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Partner
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Search & Filters -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-2">
                            <input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Search by name, phone, email, or company..."
                                class="w-full rounded-lg border-slate-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            />
                        </div>

                        <div>
                            <select
                                v-model="typeFilter"
                                @change="applyFilters"
                                class="w-full rounded-lg border-slate-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="">All Partner Types</option>
                                <option v-for="t in props.types" :key="t" :value="t">{{ t }}</option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <select
                                v-model="statusFilter"
                                @change="applyFilters"
                                class="w-full rounded-lg border-slate-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="">All Statuses</option>
                                <option v-for="s in props.statuses" :key="s" :value="s">{{ s }}</option>
                            </select>

                            <button
                                @click="resetFilters"
                                class="px-3 py-2 text-xs rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
                                title="Reset filters"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-semibold">Partner</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Type</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Contact</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Drivers</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Created</th>
                                    <th scope="col" class="px-6 py-4 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <tr
                                    v-for="partner in props.partners.data"
                                    :key="partner.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-slate-900 dark:text-white">
                                            {{ partner.name }}
                                        </div>
                                        <div v-if="partner.company_name" class="text-xs text-slate-500">
                                            {{ partner.company_name }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                            {{ partner.type }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        <div v-if="partner.phone" class="font-medium text-slate-800 dark:text-slate-200">
                                            {{ partner.phone }}
                                        </div>
                                        <div v-if="partner.email" class="text-slate-500">
                                            {{ partner.email }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                            {{ partner.drivers_count }} drivers
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="{
                                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800': partner.status === 'ACTIVE',
                                                'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800': partner.status === 'INACTIVE',
                                            }"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                                        >
                                            <span
                                                :class="partner.status === 'ACTIVE' ? 'bg-emerald-500' : 'bg-rose-500'"
                                                class="h-1.5 w-1.5 rounded-full"
                                            ></span>
                                            {{ partner.status }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                        {{ partner.created_at }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <Link
                                                v-if="partner.can.view"
                                                :href="route('referral-partners.show', partner.id)"
                                                class="text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white px-2 py-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                            >
                                                View
                                            </Link>
                                            <Link
                                                v-if="partner.can.edit"
                                                :href="route('referral-partners.edit', partner.id)"
                                                class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 px-2 py-1 rounded hover:bg-blue-50 dark:hover:bg-blue-950/40"
                                            >
                                                Edit
                                            </Link>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="props.partners.data.length === 0">
                                    <td colspan="7" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                        No referral partners found matching the selected criteria.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="props.partners.total > 15" class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center text-xs">
                        <span class="text-slate-500">Total {{ props.partners.total }} partners</span>
                        <div class="flex gap-1">
                            <template v-for="(link, idx) in props.partners.links" :key="idx">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="{
                                        'bg-blue-600 text-white': link.active,
                                        'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300': !link.active
                                    }"
                                    class="px-2.5 py-1 rounded border border-transparent hover:border-slate-300"
                                />
                                <span v-else v-html="link.label" class="px-2.5 py-1 text-slate-400" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
