<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ReferralCode } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    referralCode: ReferralCode;
    telegramDeepLink: string;
    qrSvg: string;
}>();

const copied = ref(false);

function copyLink() {
    navigator.clipboard.writeText(props.telegramDeepLink).then(() => {
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    });
}

function toggleStatus() {
    const action = props.referralCode.status === 'ACTIVE' ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} referral code "${props.referralCode.code}"?`)) {
        router.patch(route(`referral-codes.${action}`, props.referralCode.id));
    }
}
</script>

<template>
    <Head :title="referralCode.code" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-bold tracking-tight font-mono text-slate-900 dark:text-white">
                            {{ referralCode.code }}
                        </h2>
                        <span
                            :class="[
                                referralCode.status === 'ACTIVE'
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold'
                            ]"
                        >
                            {{ referralCode.status }}
                        </span>
                        <span v-if="referralCode.label" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ referralCode.label }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Bound to driver <strong>{{ referralCode.driver?.name }}</strong> &middot; Vehicle <strong>{{ referralCode.vehicle?.plate_number }}</strong>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('referral-codes.print', referralCode.id)"
                        target="_blank"
                        class="rounded-lg border border-purple-300 px-3 py-1.5 text-xs font-semibold text-purple-700 hover:bg-purple-50 dark:border-purple-700 dark:text-purple-300 dark:hover:bg-purple-950/40 transition"
                    >
                        Print Layout &nearr;
                    </Link>
                    <a
                        :href="route('referral-codes.download', referralCode.id)"
                        class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500 transition"
                    >
                        Download SVG
                    </a>
                    <button
                        v-if="$page.props.auth.can?.createReferralCodes"
                        @click="toggleStatus"
                        type="button"
                        :class="[
                            referralCode.status === 'ACTIVE'
                                ? 'border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/40'
                                : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-950/40',
                            'rounded-lg border px-3 py-1.5 text-xs font-semibold transition'
                        ]"
                    >
                        {{ referralCode.status === 'ACTIVE' ? 'Deactivate' : 'Activate' }}
                    </button>
                    <Link
                        v-if="$page.props.auth.can?.createReferralCodes"
                        :href="route('referral-codes.edit', referralCode.id)"
                        class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-500 transition"
                    >
                        Edit Metadata
                    </Link>
                    <Link
                        :href="route('referral-codes.index')"
                        class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                    >
                        &larr; Codes List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                <!-- Top Grid: QR Code & Telegram Deep Link -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <!-- QR Code Card -->
                    <div class="lg:col-span-5 rounded-xl border border-slate-200 bg-white p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col items-center text-center">
                        <span class="text-xs uppercase font-semibold text-slate-400 tracking-wider mb-4">
                            Telegram QR Code Vector Preview
                        </span>

                        <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-xs dark:border-slate-700 flex items-center justify-center">
                            <!-- SVG QR render -->
                            <div class="w-64 h-64 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full" v-html="qrSvg"></div>
                        </div>

                        <div class="mt-6 font-mono font-extrabold text-xl text-slate-900 dark:text-white tracking-widest">
                            {{ referralCode.code }}
                        </div>

                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Scan with phone camera or Telegram scanner to open bot deep link.
                        </p>

                        <div class="mt-6 flex gap-3 w-full justify-center">
                            <a
                                :href="route('referral-codes.download', referralCode.id)"
                                class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 transition text-center"
                            >
                                Download Vector (.svg)
                            </a>
                            <Link
                                :href="route('referral-codes.print', referralCode.id)"
                                target="_blank"
                                class="flex-1 rounded-lg border border-purple-300 px-4 py-2 text-xs font-semibold text-purple-700 hover:bg-purple-50 dark:border-purple-700 dark:text-purple-300 dark:hover:bg-purple-950/40 transition text-center"
                            >
                                Print Vehicle Card &nearr;
                            </Link>
                        </div>
                    </div>

                    <!-- Destination & Deep Link Card -->
                    <div class="lg:col-span-7 space-y-6">
                        <!-- Telegram Deep Link Details -->
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Encoded Telegram Destination URL
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                This immutable URL is directly encoded into the QR code above.
                            </p>

                            <div class="mt-4 flex items-center gap-2">
                                <input
                                    type="text"
                                    readonly
                                    :value="telegramDeepLink"
                                    class="w-full rounded-lg bg-slate-50 border-slate-300 font-mono text-xs text-slate-800 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 select-all"
                                />
                                <button
                                    @click="copyLink"
                                    type="button"
                                    class="shrink-0 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-blue-500 transition"
                                >
                                    {{ copied ? 'Copied!' : 'Copy Link' }}
                                </button>
                            </div>
                        </div>

                        <!-- Bound Hierarchy Details -->
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Verified Ownership Binding
                            </h3>
                            <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt class="text-xs text-slate-400">Referral Partner</dt>
                                    <dd class="font-medium text-slate-900 dark:text-white mt-0.5">
                                        <Link
                                            v-if="referralCode.referral_partner"
                                            :href="route('referral-partners.show', referralCode.referral_partner.id)"
                                            class="text-blue-600 hover:underline dark:text-blue-400"
                                        >
                                            {{ referralCode.referral_partner.name }}
                                        </Link>
                                        <span v-else>—</span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs text-slate-400">Driver</dt>
                                    <dd class="font-medium text-slate-900 dark:text-white mt-0.5">
                                        <Link
                                            v-if="referralCode.driver"
                                            :href="route('drivers.show', referralCode.driver.id)"
                                            class="text-blue-600 hover:underline dark:text-blue-400"
                                        >
                                            {{ referralCode.driver.name }}
                                        </Link>
                                        <span v-else>—</span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs text-slate-400">Vehicle Plate Number</dt>
                                    <dd class="font-mono font-bold text-slate-900 dark:text-white mt-0.5">
                                        <Link
                                            v-if="referralCode.vehicle"
                                            :href="route('vehicles.show', referralCode.vehicle.id)"
                                            class="hover:underline"
                                        >
                                            {{ referralCode.vehicle.plate_number }}
                                        </Link>
                                        <span v-else>—</span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs text-slate-400">Vehicle Type & Model</dt>
                                    <dd class="font-medium text-slate-900 dark:text-white mt-0.5">
                                        {{ referralCode.vehicle?.vehicle_type }}
                                        <span v-if="referralCode.vehicle?.make || referralCode.vehicle?.model">
                                            ({{ [referralCode.vehicle?.make, referralCode.vehicle?.model].filter(Boolean).join(' ') }})
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs text-slate-400">Created By</dt>
                                    <dd class="text-slate-700 dark:text-slate-300 mt-0.5">
                                        {{ referralCode.creator?.name || 'Staff' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs text-slate-400">Created At</dt>
                                    <dd class="text-slate-700 dark:text-slate-300 mt-0.5">
                                        {{ referralCode.created_at ? new Date(referralCode.created_at).toLocaleString() : '—' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Operational Notes -->
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-xs uppercase font-semibold text-slate-400 tracking-wider">
                                Operational Notes
                            </h3>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">
                                {{ referralCode.notes || 'No specific operational notes recorded for this referral code.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
