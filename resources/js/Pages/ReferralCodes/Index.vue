<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Driver, PaginatedData, ReferralCode, ReferralPartner } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
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
    unassignedCount?: number;
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

// -------------------------------------------------------------
// BATCH GENERATION MODAL STATE & ACTIONS
// -------------------------------------------------------------
const showBatchModal = ref(false);
const batchForm = useForm({
    quantity: 10,
    prefix: 'REF-TK',
    label: 'Tuk-Tuk Pre-Printed Office Batch',
});

function submitBatchGenerate() {
    batchForm.post(route('referral-codes.batch-generate'), {
        onSuccess: () => {
            showBatchModal.value = false;
        },
    });
}

// -------------------------------------------------------------
// QUICK DRIVER ASSIGNMENT MODAL STATE & ACTIONS
// -------------------------------------------------------------
const showAssignModal = ref(false);
const selectedCode = ref<ReferralCode | null>(null);
const assignForm = useForm({
    driver_name: '',
    driver_phone: '',
    vehicle_plate: '',
    vehicle_type: 'TUK_TUK',
    referral_partner_id: '' as string | number,
    notes: '',
});

function openAssignModal(code: ReferralCode) {
    selectedCode.value = code;
    assignForm.reset();
    assignForm.vehicle_type = 'TUK_TUK';
    assignForm.referral_partner_id = props.partners[0]?.id || '';
    showAssignModal.value = true;
}

function submitAssignDriver() {
    if (!selectedCode.value) return;

    assignForm.post(route('referral-codes.assign-driver', selectedCode.value.id), {
        onSuccess: () => {
            showAssignModal.value = false;
            selectedCode.value = null;
        },
    });
}
</script>

<template>
    <Head title="Referral Codes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Referral Codes & QR Engine
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Manage Telegram QR codes for passenger referral attribution and commission payouts.
                    </p>
                </div>
                <div v-if="$page.props.auth.can?.createReferralCodes" class="flex flex-wrap items-center gap-2.5">
                    <!-- Batch Print Link -->
                    <Link
                        :href="route('referral-codes.batch-print')"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-purple-300 bg-purple-50 px-3.5 py-2 text-xs font-semibold text-purple-700 hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-950/40 dark:text-purple-300 transition"
                    >
                        <span>🖨️ Batch Print (A4)</span>
                    </Link>

                    <!-- Pre-Print Batch Button -->
                    <button
                        @click="showBatchModal = true"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white transition cursor-pointer"
                    >
                        <span>⚡ Pre-Print Batch</span>
                    </button>

                    <!-- Single Generate Link -->
                    <Link
                        :href="route('referral-codes.create')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-blue-500 transition"
                    >
                        <span>+ Generate Single Code</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Pre-Printed Pool Alert Banner (if unassigned codes exist) -->
                <div
                    v-if="unassignedCount && unassignedCount > 0"
                    class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 dark:border-amber-900/50 dark:bg-amber-950/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📦</span>
                        <div>
                            <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                                {{ unassignedCount }} Pre-Printed QR Code{{ unassignedCount > 1 ? 's' : '' }} Ready in Office Pool
                            </h4>
                            <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
                                Print them in your office, carry them to the street, and assign them directly to drivers when they agree!
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="status = 'UNASSIGNED'"
                            type="button"
                            class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-amber-500 transition cursor-pointer"
                        >
                            Filter Unassigned Codes
                        </button>
                        <Link
                            :href="route('referral-codes.batch-print')"
                            target="_blank"
                            class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50 transition"
                        >
                            Print Batch &rarr;
                        </Link>
                    </div>
                </div>

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
                                <option value="">All Referral Partners</option>
                                <option v-for="p in partners" :key="p.id" :value="p.id">
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
                                <option v-for="d in drivers" :key="d.id" :value="d.id">
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
                                <option value="TUK_TUK">Tuk-Tuk (India Bajaj/TVS)</option>
                                <option value="TAXI">Taxi (Car / Sedan)</option>
                                <option value="OTHER">Other</option>
                            </select>
                        </div>
                        <div>
                            <select
                                v-model="status"
                                class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="">All Code Statuses</option>
                                <option value="UNASSIGNED">Unassigned (Pre-Printed)</option>
                                <option value="ACTIVE">Active (Assigned to Vehicle)</option>
                                <option value="INACTIVE">Inactive (Disabled)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                <tr>
                                    <th class="px-6 py-4">Code & Label</th>
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
                                    <!-- Code & Label -->
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

                                    <!-- Partner -->
                                    <td class="px-6 py-4">
                                        <Link
                                            v-if="rc.referral_partner"
                                            :href="route('referral-partners.show', rc.referral_partner.id)"
                                            class="font-medium text-slate-900 hover:text-blue-600 dark:text-slate-200 dark:hover:text-blue-400"
                                        >
                                            {{ rc.referral_partner.name }}
                                        </Link>
                                        <span v-else-if="rc.status === 'UNASSIGNED'" class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200">
                                            Office Pool
                                        </span>
                                        <span v-else class="text-slate-400">—</span>
                                    </td>

                                    <!-- Driver -->
                                    <td class="px-6 py-4">
                                        <div v-if="rc.driver">
                                            <Link
                                                :href="route('drivers.show', rc.driver.id)"
                                                class="font-medium text-slate-900 hover:text-blue-600 dark:text-slate-200 dark:hover:text-blue-400"
                                            >
                                                {{ rc.driver.name }}
                                            </Link>
                                            <div v-if="rc.driver?.phone" class="text-xs font-mono text-slate-400">
                                                {{ rc.driver.phone }}
                                            </div>
                                        </div>
                                        <span v-else-if="rc.status === 'UNASSIGNED'" class="text-xs text-slate-400 italic">
                                            Ready for Driver
                                        </span>
                                        <span v-else class="text-slate-400">—</span>
                                    </td>

                                    <!-- Vehicle -->
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

                                    <!-- Status Badge -->
                                    <td class="px-6 py-4">
                                        <span
                                            v-if="rc.status === 'UNASSIGNED'"
                                            class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                                        >
                                            PRE-PRINTED
                                        </span>
                                        <span
                                            v-else
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

                                    <!-- Created Date -->
                                    <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                        {{ rc.created_at ? new Date(rc.created_at).toLocaleDateString() : '—' }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <!-- Assign Driver button for Unassigned codes -->
                                        <button
                                            v-if="rc.status === 'UNASSIGNED' && $page.props.auth.can?.createReferralCodes"
                                            @click="openAssignModal(rc)"
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500 transition cursor-pointer"
                                        >
                                            <span>+ Assign Driver</span>
                                        </button>

                                        <Link
                                            :href="route('referral-codes.show', rc.id)"
                                            class="rounded px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/40"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('referral-codes.print', rc.id)"
                                            target="_blank"
                                            class="rounded px-2 py-1 text-xs font-medium text-purple-600 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-950/40"
                                        >
                                            Print
                                        </Link>

                                        <button
                                            v-if="rc.status !== 'UNASSIGNED' && $page.props.auth.can?.createReferralCodes"
                                            @click="toggleStatus(rc)"
                                            type="button"
                                            :class="[
                                                rc.status === 'ACTIVE'
                                                    ? 'text-amber-600 hover:bg-amber-50 dark:text-amber-400'
                                                    : 'text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400',
                                                'rounded px-2 py-1 text-xs font-medium cursor-pointer'
                                            ]"
                                        >
                                            {{ rc.status === 'ACTIVE' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="!referralCodes.data.length">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                        No referral codes found matching the criteria.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 1: BATCH GENERATE PRE-PRINTED CODES                    -->
        <!-- ============================================================= -->
        <div
            v-if="showBatchModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs"
        >
            <div class="w-full max-w-md bg-white rounded-2xl p-6 shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Pre-Print QR Batch Studio</h3>
                        <p class="text-xs text-slate-500">Generate blank stickers to print in your office</p>
                    </div>
                    <button
                        @click="showBatchModal = false"
                        type="button"
                        class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer"
                    >
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitBatchGenerate" class="space-y-4 text-xs">
                    <!-- Quantity -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Number of Stickers to Generate</label>
                        <div class="grid grid-cols-4 gap-2 mb-2">
                            <button
                                v-for="q in [5, 10, 20, 50]"
                                :key="q"
                                @click="batchForm.quantity = q"
                                type="button"
                                :class="[batchForm.quantity === q ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-700']"
                                class="py-2 rounded-lg text-center font-medium transition cursor-pointer"
                            >
                                {{ q }} Codes
                            </button>
                        </div>
                        <input
                            v-model.number="batchForm.quantity"
                            type="number"
                            min="1"
                            max="100"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500"
                        />
                    </div>

                    <!-- Prefix -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Code Prefix</label>
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <button
                                @click="batchForm.prefix = 'REF-TK'"
                                type="button"
                                :class="[batchForm.prefix === 'REF-TK' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-700']"
                                class="py-1.5 rounded-lg text-center font-medium transition cursor-pointer"
                            >
                                🛺 REF-TK (Tuk-Tuk)
                            </button>
                            <button
                                @click="batchForm.prefix = 'REF-TX'"
                                type="button"
                                :class="[batchForm.prefix === 'REF-TX' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-700']"
                                class="py-1.5 rounded-lg text-center font-medium transition cursor-pointer"
                            >
                                🚕 REF-TX (Taxi)
                            </button>
                        </div>
                        <input
                            v-model="batchForm.prefix"
                            type="text"
                            class="w-full rounded-lg border-slate-300 text-sm uppercase font-mono focus:border-blue-500"
                        />
                    </div>

                    <!-- Label -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Batch Label / Description</label>
                        <input
                            v-model="batchForm.label"
                            type="text"
                            placeholder="e.g. Tuk-Tuk Phnom Penh Batch Nov 2026"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500"
                        />
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button
                            @click="showBatchModal = false"
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="batchForm.processing"
                            class="rounded-xl bg-blue-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-500 transition cursor-pointer disabled:opacity-50"
                        >
                            Generate {{ batchForm.quantity }} Codes Now
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 2: QUICK ASSIGN PRE-PRINTED CODE TO DRIVER (ON STREET) -->
        <!-- ============================================================= -->
        <div
            v-if="showAssignModal && selectedCode"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs"
        >
            <div class="w-full max-w-lg bg-white rounded-2xl p-6 shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Assign Sticker to Driver</h3>
                        <p class="text-xs text-slate-500">Link physical code to the vehicle driver on the street</p>
                    </div>
                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-sm font-mono font-bold text-blue-700 border border-blue-200">
                        {{ selectedCode.code }}
                    </span>
                </div>

                <form @submit.prevent="submitAssignDriver" class="space-y-3.5 text-xs">
                    <!-- Driver Name -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Driver Name *</label>
                        <input
                            v-model="assignForm.driver_name"
                            type="text"
                            required
                            placeholder="e.g. Bong Sokha"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 font-['Kantumruy_Pro',sans-serif]"
                        />
                    </div>

                    <!-- Driver Phone & Plate in 2 cols -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Driver Phone / Telegram *</label>
                            <input
                                v-model="assignForm.driver_phone"
                                type="text"
                                required
                                placeholder="012 345 678"
                                class="w-full rounded-lg border-slate-300 text-sm font-mono focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tuk-Tuk Plate Number *</label>
                            <input
                                v-model="assignForm.vehicle_plate"
                                type="text"
                                required
                                placeholder="e.g. 1AB-2345 or PP-1AB-2345"
                                class="w-full rounded-lg border-slate-300 text-sm uppercase font-mono focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Vehicle Type -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Vehicle Type</label>
                            <select
                                v-model="assignForm.vehicle_type"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500"
                            >
                                <option value="TUK_TUK">Tuk-Tuk (India Bajaj/TVS)</option>
                                <option value="TAXI">Taxi (Car)</option>
                                <option value="OTHER">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Referral Partner / Fleet</label>
                            <select
                                v-model="assignForm.referral_partner_id"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500"
                            >
                                <option value="">Independent Tuk-Tuk Drivers (Default)</option>
                                <option v-for="p in partners" :key="p.id" :value="p.id">
                                    {{ p.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Bank / ABA / Notes -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Commission Payment Notes (ABA / Bakong)</label>
                        <input
                            v-model="assignForm.notes"
                            type="text"
                            placeholder="e.g. ABA Account: 001 234 567 (Sokha Chan)"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500"
                        />
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button
                            @click="showAssignModal = false"
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="assignForm.processing"
                            class="rounded-xl bg-emerald-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 transition cursor-pointer disabled:opacity-50"
                        >
                            Save & Activate Sticker
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
