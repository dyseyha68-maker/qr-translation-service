<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Driver } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    driver: Driver;
}>();

function toggleStatus() {
    const action = props.driver.status === 'ACTIVE' ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this driver?`)) {
        router.patch(route(`drivers.${action}`, props.driver.id));
    }
}
</script>

<template>
    <Head :title="driver.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ driver.name }}
                        </h2>
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
                    </div>
                    <p v-if="driver.referral_partner" class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Affiliated with
                        <Link
                            :href="route('referral-partners.show', driver.referral_partner.id)"
                            class="font-medium text-blue-600 hover:underline dark:text-blue-400"
                        >
                            {{ driver.referral_partner.name }}
                        </Link>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="toggleStatus"
                        type="button"
                        :class="[
                            driver.status === 'ACTIVE'
                                ? 'border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/40'
                                : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-950/40',
                            'rounded-lg border px-3 py-1.5 text-xs font-semibold transition'
                        ]"
                    >
                        {{ driver.status === 'ACTIVE' ? 'Deactivate' : 'Activate' }}
                    </button>
                    <Link
                        :href="route('drivers.edit', driver.id)"
                        class="rounded-lg bg-blue-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-500 transition"
                    >
                        Edit Details
                    </Link>
                    <Link
                        :href="route('drivers.index')"
                        class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                    >
                        &larr; Drivers List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                <!-- Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Personal & Contact Info</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Phone</dt>
                                <dd class="font-mono font-medium text-slate-900 dark:text-white">{{ driver.phone || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Email</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ driver.email || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">National ID / Passport</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ driver.national_id || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Residential Address</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ driver.address || '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Affiliation & Fleet</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Referral Partner</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">
                                    <Link
                                        v-if="driver.referral_partner"
                                        :href="route('referral-partners.show', driver.referral_partner.id)"
                                        class="text-blue-600 hover:underline dark:text-blue-400"
                                    >
                                        {{ driver.referral_partner.name }}
                                    </Link>
                                    <span v-else>—</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Registered Vehicles</dt>
                                <dd class="text-2xl font-bold text-slate-900 dark:text-white">
                                    {{ driver.vehicles?.length ?? 0 }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Registered On</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">
                                    {{ driver.created_at ? new Date(driver.created_at).toLocaleDateString() : '—' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Driver Notes</h3>
                        <p class="mt-4 text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">
                            {{ driver.notes || 'No specific notes recorded for this driver.' }}
                        </p>
                    </div>
                </div>

                <!-- Vehicles Table -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Assigned Vehicles</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Vehicles registered and operated by this driver.
                            </p>
                        </div>
                        <Link
                            :href="route('vehicles.create')"
                            class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-500 transition"
                        >
                            + Register Vehicle
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                <tr>
                                    <th class="px-6 py-3">Plate Number</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Make / Model</th>
                                    <th class="px-6 py-3">Color</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="vehicle in driver.vehicles"
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
                                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium'
                                            ]"
                                        >
                                            {{ vehicle.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <Link
                                            :href="route('vehicles.show', vehicle.id)"
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('vehicles.edit', vehicle.id)"
                                            class="text-xs font-semibold text-slate-600 hover:text-slate-800 dark:text-slate-400"
                                        >
                                            Edit
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!driver.vehicles?.length">
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-400">
                                        No vehicles assigned to this driver yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
