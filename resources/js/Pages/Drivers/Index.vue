<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Driver, PaginatedData, ReferralPartner } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps<{
    drivers: PaginatedData<Driver>;
    filters: {
        search?: string;
        partner_id?: string;
        status?: string;
    };
    partners: ReferralPartner[];
    statuses: string[];
}>();

const search = ref(props.filters.search || '');
const partnerId = ref(props.filters.partner_id || '');
const status = ref(props.filters.status || '');

let searchDebounceTimeout: ReturnType<typeof setTimeout> | null = null;

function applyFilters() {
    router.get(
        route('drivers.index'),
        {
            search: search.value || undefined,
            partner_id: partnerId.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

watch(search, () => {
    if (searchDebounceTimeout) clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(applyFilters, 300);
});

watch([partnerId, status], applyFilters);

function toggleStatus(driver: Driver) {
    const action = driver.status === 'ACTIVE' ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} driver "${driver.name}"?`)) {
        router.patch(route(`drivers.${action}`, driver.id), {}, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Drivers" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Drivers
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Manage registered taxi and tuk-tuk drivers affiliated with referral partners.
                    </p>
                </div>
                <div v-if="$page.props.auth.can?.createDrivers">
                    <Link
                        :href="route('drivers.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 transition"
                    >
                        <span>+ Register Driver</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Search & Filters Toolbar -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search driver name, phone, national ID..."
                                class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            />
                        </div>
                        <div>
                            <select
                                v-model="partnerId"
                                class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="">All Referral Partners</option>
                                <option v-for="p in props.partners" :key="p.id" :value="p.id">
                                    {{ p.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <select
                                v-model="status"
                                class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="">All Statuses</option>
                                <option v-for="s in props.statuses" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Drivers Table -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                <tr>
                                    <th class="px-6 py-4">Driver Name</th>
                                    <th class="px-6 py-4">Partner</th>
                                    <th class="px-6 py-4">Phone</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Vehicles</th>
                                    <th class="px-6 py-4">Registered</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="driver in drivers.data"
                                    :key="driver.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition"
                                >
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900 dark:text-white">
                                            <Link :href="route('drivers.show', driver.id)" class="hover:underline">
                                                {{ driver.name }}
                                            </Link>
                                        </div>
                                        <div v-if="driver.national_id" class="text-xs text-slate-400">
                                            ID: {{ driver.national_id }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Link
                                            v-if="driver.referral_partner"
                                            :href="route('referral-partners.show', driver.referral_partner.id)"
                                            class="font-medium text-slate-900 hover:text-blue-600 dark:text-slate-200 dark:hover:text-blue-400"
                                        >
                                            {{ driver.referral_partner.name }}
                                        </Link>
                                        <span v-else class="text-slate-400">—</span>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs">{{ driver.phone || '—' }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="[
                                                driver.status === 'ACTIVE'
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold'
                                            ]"
                                        >
                                            {{ driver.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">
                                        {{ driver.vehicles_count ?? (driver.vehicles?.length ?? 0) }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                        {{ driver.created_at ? new Date(driver.created_at).toLocaleDateString() : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <Link
                                            :href="route('drivers.show', driver.id)"
                                            class="rounded px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/40"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('drivers.edit', driver.id)"
                                            class="rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="toggleStatus(driver)"
                                            type="button"
                                            :class="[
                                                driver.status === 'ACTIVE'
                                                    ? 'text-amber-600 hover:bg-amber-50 dark:text-amber-400'
                                                    : 'text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400',
                                                'rounded px-2 py-1 text-xs font-medium'
                                            ]"
                                        >
                                            {{ driver.status === 'ACTIVE' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!drivers.data.length">
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-400">
                                        No drivers found matching criteria.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="drivers.links && drivers.links.length > 3"
                        class="flex items-center justify-between border-t border-slate-100 px-6 py-4 dark:border-slate-800"
                    >
                        <div class="text-xs text-slate-500">
                            Showing {{ drivers.from || 0 }} to {{ drivers.to || 0 }} of {{ drivers.total }} drivers
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="(link, i) in drivers.links"
                                :key="i"
                                :href="link.url || '#'"
                                :class="[
                                    link.active
                                        ? 'bg-blue-600 text-white'
                                        : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800',
                                    !link.url ? 'pointer-events-none opacity-40' : '',
                                    'rounded px-3 py-1 text-xs font-medium'
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
