<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Driver, PaginatedData, ReferralCode, ReferralPartner } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps<{
    referralCodes: PaginatedData<ReferralCode>;
    filters: {
        search?: string;
        status?: string;
        partner_id?: string;
        driver_id?: string;
        vehicle_type?: string;
    };
    partners: ReferralPartner[];
    drivers: Driver[];
    statuses: string[];
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const partnerId = ref(props.filters.partner_id || '');
const driverId = ref(props.filters.driver_id || '');
const vehicleType = ref(props.filters.vehicle_type || '');

let searchDebounceTimeout: ReturnType<typeof setTimeout> | null = null;

function applyFilters() {
    router.get(
        route('referral-codes.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            partner_id: partnerId.value || undefined,
            driver_id: driverId.value || undefined,
            vehicle_type: vehicleType.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

watch(search, () => {
    if (searchDebounceTimeout) clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(applyFilters, 300);
});

watch([status, partnerId, driverId, vehicleType], applyFilters);

function toggleStatus(code: ReferralCode) {
    const action = code.status === 'ACTIVE' ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} referral code "${code.code}"?`)) {
        router.patch(route(`referral-codes.${action}`, code.id), {}, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Referral Codes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Referral Codes & QR Engine
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Manage unique Telegram QR referral codes bound to drivers and vehicles.
                    </p>
                </div>
                <div v-if="$page.props.auth.can?.createReferralCodes">
                    <Link
                        :href="route('referral-codes.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 transition"
                    >
                        <span>+ Generate Referral Code</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Search & Filters Toolbar -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search code, label, driver, plate..."
                                class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            />
                        </div>
                        <div>
                            <select
                                v-model="partnerId"
                                class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="">All Partners</option>
                                <option v-for="p in props.partners" :key="p.id" :value="p.id">
                                    {{ p.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <select
                                v-model="driverId"
                                class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="">All Drivers</option>
                                <option v-for="d in props.drivers" :key="d.id" :value="d.id">
                                    {{ d.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <select
                                v-model="vehicleType"
                                class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="">All Vehicle Types</option>
                                <option value="TAXI">Taxi</option>
                                <option value="TUK_TUK">Tuk-Tuk</option>
                                <option value="OTHER">Other</option>
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

                <!-- Referral Codes Table -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                <tr>
                                    <th class="px-6 py-4">Code / Label</th>
                                    <th class="px-6 py-4">Partner</th>
                                    <th class="px-6 py-4">Driver</th>
                                    <th class="px-6 py-4">Vehicle</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Created</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="rc in referralCodes.data"
                                    :key="rc.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition"
                                >
                                    <td class="px-6 py-4">
                                        <div class="font-mono font-bold text-slate-900 dark:text-white">
                                            <Link :href="route('referral-codes.show', rc.id)" class="hover:underline">
                                                {{ rc.code }}
                                            </Link>
                                        </div>
                                        <div v-if="rc.label" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                            {{ rc.label }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Link
                                            v-if="rc.referral_partner"
                                            :href="route('referral-partners.show', rc.referral_partner.id)"
                                            class="font-medium text-slate-900 hover:text-blue-600 dark:text-slate-200 dark:hover:text-blue-400"
                                        >
                                            {{ rc.referral_partner.name }}
                                        </Link>
                                        <span v-else class="text-slate-400">—</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Link
                                            v-if="rc.driver"
                                            :href="route('drivers.show', rc.driver.id)"
                                            class="font-medium text-slate-900 hover:text-blue-600 dark:text-slate-200 dark:hover:text-blue-400"
                                        >
                                            {{ rc.driver.name }}
                                        </Link>
                                        <div v-if="rc.driver?.phone" class="text-xs font-mono text-slate-400">
                                            {{ rc.driver.phone }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="rc.vehicle" class="flex items-center gap-1.5">
                                            <span class="font-mono font-medium text-slate-900 dark:text-white">
                                                {{ rc.vehicle.plate_number }}
                                            </span>
                                            <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                {{ rc.vehicle.vehicle_type }}
                                            </span>
                                        </div>
                                        <span v-else class="text-slate-400">—</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="[
                                                rc.status === 'ACTIVE'
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold'
                                            ]"
                                        >
                                            {{ rc.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                        {{ rc.created_at ? new Date(rc.created_at).toLocaleDateString() : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <Link
                                            :href="route('referral-codes.show', rc.id)"
                                            class="rounded px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/40"
                                        >
                                            View & QR
                                        </Link>
                                        <Link
                                            :href="route('referral-codes.print', rc.id)"
                                            target="_blank"
                                            class="rounded px-2 py-1 text-xs font-medium text-purple-600 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-950/40"
                                        >
                                            Print
                                        </Link>
                                        <a
                                            :href="route('referral-codes.download', rc.id)"
                                            class="rounded px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40"
                                        >
                                            Download
                                        </a>
                                        <button
                                            v-if="$page.props.auth.can?.createReferralCodes"
                                            @click="toggleStatus(rc)"
                                            type="button"
                                            :class="[
                                                rc.status === 'ACTIVE'
                                                    ? 'text-amber-600 hover:bg-amber-50 dark:text-amber-400'
                                                    : 'text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400',
                                                'rounded px-2 py-1 text-xs font-medium'
                                            ]"
                                        >
                                            {{ rc.status === 'ACTIVE' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!referralCodes.data.length">
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-400">
                                        No referral codes found matching criteria.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="referralCodes.links && referralCodes.links.length > 3"
                        class="flex items-center justify-between border-t border-slate-100 px-6 py-4 dark:border-slate-800"
                    >
                        <div class="text-xs text-slate-500">
                            Showing {{ referralCodes.from || 0 }} to {{ referralCodes.to || 0 }} of {{ referralCodes.total }} referral codes
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="(link, i) in referralCodes.links"
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
