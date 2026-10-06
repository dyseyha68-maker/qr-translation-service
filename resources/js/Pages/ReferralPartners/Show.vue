<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ReferralPartner } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    partner: ReferralPartner;
}>();

function toggleStatus() {
    const action = props.partner.status === 'ACTIVE' ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this partner?`)) {
        router.patch(route(`referral-partners.${action}`, props.partner.id));
    }
}
</script>

<template>
    <Head :title="partner.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ partner.name }}
                        </h2>
                        <span
                            :class="[
                                partner.status === 'ACTIVE'
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold'
                            ]"
                        >
                            {{ partner.status }}
                        </span>
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-300">
                            {{ partner.type }}
                        </span>
                    </div>
                    <p v-if="partner.company_name" class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        {{ partner.company_name }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="toggleStatus"
                        type="button"
                        :class="[
                            partner.status === 'ACTIVE'
                                ? 'border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/40'
                                : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-950/40',
                            'rounded-lg border px-3 py-1.5 text-xs font-semibold transition'
                        ]"
                    >
                        {{ partner.status === 'ACTIVE' ? 'Deactivate' : 'Activate' }}
                    </button>
                    <Link
                        :href="route('referral-partners.edit', partner.id)"
                        class="rounded-lg bg-blue-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-500 transition"
                    >
                        Edit Details
                    </Link>
                    <Link
                        :href="route('referral-partners.index')"
                        class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                    >
                        &larr; Partners List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                <!-- Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Contact Details</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Phone</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ partner.phone || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Email</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ partner.email || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Office Address</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ partner.address || '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Operational Summary</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Registered Drivers</dt>
                                <dd class="text-2xl font-bold text-slate-900 dark:text-white">
                                    {{ partner.drivers?.length ?? 0 }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Created At</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ partner.created_at ? new Date(partner.created_at).toLocaleDateString() : '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Operational Notes</h3>
                        <p class="mt-4 text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">
                            {{ partner.notes || 'No specific notes recorded for this partner.' }}
                        </p>
                    </div>
                </div>

                <!-- Associated Drivers Table -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Affiliated Drivers</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Drivers operating under this referral partner.
                            </p>
                        </div>
                        <Link
                            :href="route('drivers.create')"
                            class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-500 transition"
                        >
                            + Register Driver
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                <tr>
                                    <th class="px-6 py-3">Driver Name</th>
                                    <th class="px-6 py-3">Phone</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Assigned Vehicles</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="driver in partner.drivers"
                                    :key="driver.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition"
                                >
                                    <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">
                                        <Link :href="route('drivers.show', driver.id)" class="hover:underline">
                                            {{ driver.name }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs">{{ driver.phone || '—' }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="[
                                                driver.status === 'ACTIVE'
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium'
                                            ]"
                                        >
                                            {{ driver.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="v in driver.vehicles"
                                                :key="v.id"
                                                class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-mono font-medium text-slate-800 dark:bg-slate-800 dark:text-slate-300"
                                            >
                                                {{ v.plate_number }} ({{ v.vehicle_type }})
                                            </span>
                                            <span v-if="!driver.vehicles?.length" class="text-xs text-slate-400">
                                                No vehicles
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <Link
                                            :href="route('drivers.show', driver.id)"
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('drivers.edit', driver.id)"
                                            class="text-xs font-semibold text-slate-600 hover:text-slate-800 dark:text-slate-400"
                                        >
                                            Edit
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!partner.drivers?.length">
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-400">
                                        No drivers registered under this partner yet.
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
