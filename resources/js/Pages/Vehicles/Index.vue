<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Driver, PaginatedData, ReferralPartner, Vehicle } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps<{
    vehicles: PaginatedData<Vehicle>;
    filters: {
        search?: string;
        vehicle_type?: string;
        status?: string;
        driver_id?: string;
        partner_id?: string;
    };
    types?: string[];
    vehicleTypes?: string[];
    statuses?: string[];
    drivers: Driver[];
    partners?: ReferralPartner[];
}>();

const availableTypes = computed(() => {
    return (props.types && props.types.length > 0)
        ? props.types
        : (props.vehicleTypes && props.vehicleTypes.length > 0)
            ? props.vehicleTypes
            : ['TAXI', 'TUK_TUK', 'OTHER'];
});

function formatType(type: string): string {
    switch (type) {
        case 'TUK_TUK':
            return 'Tuk-Tuk';
        case 'TAXI':
            return 'Taxi';
        case 'OTHER':
            return 'Other';
        default:
            return type;
    }
}

const search = ref(props.filters.search || '');
const vehicleType = ref(props.filters.vehicle_type || '');
const status = ref(props.filters.status || '');
const driverId = ref(props.filters.driver_id || '');
const partnerId = ref(props.filters.partner_id || '');

let searchDebounceTimeout: ReturnType<typeof setTimeout> | null = null;

function applyFilters() {
    router.get(
        route('vehicles.index'),
        {
            search: search.value || undefined,
            vehicle_type: vehicleType.value || undefined,
            status: status.value || undefined,
            driver_id: driverId.value || undefined,
            partner_id: partnerId.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

watch(search, () => {
    if (searchDebounceTimeout) clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(applyFilters, 300);
});

watch([vehicleType, status, driverId, partnerId], applyFilters);

function toggleStatus(vehicle: Vehicle) {
    const action = vehicle.status === 'ACTIVE' ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} vehicle with plate "${vehicle.plate_number}"?`)) {
        router.patch(route(`vehicles.${action}`, vehicle.id), {}, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Vehicles" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Vehicles
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Manage registered taxis, tuk-tuks, and other transportation referral vehicles.
                    </p>
                </div>
                <div v-if="$page.props.auth.can?.createVehicles">
                    <Link
                        :href="route('vehicles.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 transition"
                    >
                        <span>+ Register Vehicle</span>
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
                                placeholder="Search plate, make, model..."
                                class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            />
                        </div>
                        <div>
                            <select
                                v-model="vehicleType"
                                class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="">All Vehicle Types</option>
                                <option v-for="t in availableTypes" :key="t" :value="t">{{ formatType(t) }}</option>
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
                                v-model="status"
                                class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="">All Statuses</option>
                                <option v-for="s in props.statuses" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Vehicles Table -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                <tr>
                                    <th class="px-6 py-4">Plate Number</th>
                                    <th class="px-6 py-4">Type</th>
                                    <th class="px-6 py-4">Driver</th>
                                    <th class="px-6 py-4">Partner</th>
                                    <th class="px-6 py-4">Make / Model</th>
                                    <th class="px-6 py-4">Color</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="vehicle in vehicles.data"
                                    :key="vehicle.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition"
                                >
                                    <td class="px-6 py-4 font-mono font-bold text-slate-900 dark:text-white">
                                        <Link :href="route('vehicles.show', vehicle.id)" class="hover:underline">
                                            {{ vehicle.plate_number }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-300">
                                            {{ vehicle.vehicle_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Link
                                            v-if="vehicle.driver"
                                            :href="route('drivers.show', vehicle.driver.id)"
                                            class="font-medium text-slate-900 hover:text-blue-600 dark:text-slate-200 dark:hover:text-blue-400"
                                        >
                                            {{ vehicle.driver.name }}
                                        </Link>
                                        <span v-else class="text-slate-400">—</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Link
                                            v-if="vehicle.driver?.referral_partner"
                                            :href="route('referral-partners.show', vehicle.driver.referral_partner.id)"
                                            class="text-xs text-slate-600 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400"
                                        >
                                            {{ vehicle.driver.referral_partner.name }}
                                        </Link>
                                        <span v-else class="text-slate-400">—</span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-900 dark:text-white">
                                        {{ [vehicle.make, vehicle.model].filter(Boolean).join(' ') || '—' }}
                                    </td>
                                    <td class="px-6 py-4">{{ vehicle.color || '—' }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="[
                                                vehicle.status === 'ACTIVE'
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold'
                                            ]"
                                        >
                                            {{ vehicle.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <Link
                                            :href="route('vehicles.show', vehicle.id)"
                                            class="rounded px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/40"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('vehicles.edit', vehicle.id)"
                                            class="rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="toggleStatus(vehicle)"
                                            type="button"
                                            :class="[
                                                vehicle.status === 'ACTIVE'
                                                    ? 'text-amber-600 hover:bg-amber-50 dark:text-amber-400'
                                                    : 'text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400',
                                                'rounded px-2 py-1 text-xs font-medium'
                                            ]"
                                        >
                                            {{ vehicle.status === 'ACTIVE' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!vehicles.data.length">
                                    <td colspan="8" class="px-6 py-12 text-center text-sm text-slate-400">
                                        No vehicles found matching criteria.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="vehicles.links && vehicles.links.length > 3"
                        class="flex items-center justify-between border-t border-slate-100 px-6 py-4 dark:border-slate-800"
                    >
                        <div class="text-xs text-slate-500">
                            Showing {{ vehicles.from || 0 }} to {{ vehicles.to || 0 }} of {{ vehicles.total }} vehicles
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="(link, i) in vehicles.links"
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
