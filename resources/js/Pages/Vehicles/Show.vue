<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Vehicle } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    vehicle: Vehicle;
}>();

function toggleStatus() {
    const action = props.vehicle.status === 'ACTIVE' ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} vehicle "${props.vehicle.plate_number}"?`)) {
        router.patch(route(`vehicles.${action}`, props.vehicle.id));
    }
}
</script>

<template>
    <Head :title="vehicle.plate_number" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-bold tracking-tight font-mono text-slate-900 dark:text-white">
                            {{ vehicle.plate_number }}
                        </h2>
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
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-300">
                            {{ vehicle.vehicle_type }}
                        </span>
                    </div>
                    <p v-if="vehicle.driver" class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Driven by
                        <Link
                            :href="route('drivers.show', vehicle.driver.id)"
                            class="font-medium text-blue-600 hover:underline dark:text-blue-400"
                        >
                            {{ vehicle.driver.name }}
                        </Link>
                        <span v-if="vehicle.driver.referral_partner">
                            ({{ vehicle.driver.referral_partner.name }})
                        </span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="toggleStatus"
                        type="button"
                        :class="[
                            vehicle.status === 'ACTIVE'
                                ? 'border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/40'
                                : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-950/40',
                            'rounded-lg border px-3 py-1.5 text-xs font-semibold transition'
                        ]"
                    >
                        {{ vehicle.status === 'ACTIVE' ? 'Deactivate' : 'Activate' }}
                    </button>
                    <Link
                        :href="route('vehicles.edit', vehicle.id)"
                        class="rounded-lg bg-blue-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-500 transition"
                    >
                        Edit Details
                    </Link>
                    <Link
                        :href="route('vehicles.index')"
                        class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                    >
                        &larr; Vehicles List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                <!-- Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Vehicle Specifications</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Plate Number</dt>
                                <dd class="font-mono font-bold text-slate-900 dark:text-white">{{ vehicle.plate_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Vehicle Type</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ vehicle.vehicle_type }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Make / Model</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">
                                    {{ [vehicle.make, vehicle.model].filter(Boolean).join(' ') || '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Color</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ vehicle.color || '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Assignment & Affiliation</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Assigned Driver</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">
                                    <Link
                                        v-if="vehicle.driver"
                                        :href="route('drivers.show', vehicle.driver.id)"
                                        class="text-blue-600 hover:underline dark:text-blue-400"
                                    >
                                        {{ vehicle.driver.name }}
                                    </Link>
                                    <span v-else>—</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Driver Phone</dt>
                                <dd class="font-mono text-slate-900 dark:text-white">{{ vehicle.driver?.phone || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Referral Partner</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">
                                    <Link
                                        v-if="vehicle.driver?.referral_partner"
                                        :href="route('referral-partners.show', vehicle.driver.referral_partner.id)"
                                        class="text-blue-600 hover:underline dark:text-blue-400"
                                    >
                                        {{ vehicle.driver.referral_partner.name }}
                                    </Link>
                                    <span v-else>—</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Registered On</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">
                                    {{ vehicle.created_at ? new Date(vehicle.created_at).toLocaleDateString() : '—' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Operational Notes</h3>
                        <p class="mt-4 text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">
                            {{ vehicle.notes || 'No specific operational notes recorded for this vehicle.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
