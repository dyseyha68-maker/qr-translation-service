<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import type { Commission } from '@/types';

const props = defineProps<{
    commission: Commission;
    paymentMethods: { value: string; label: string }[];
    can: {
        approve: boolean;
        pay: boolean;
        reverse: boolean;
        update?: boolean;
    };
}>();

const showPayoutModal = ref(false);
const showReverseModal = ref(false);
const showAdjustModal = ref(false);

const isEarned = computed(() => ['earned', 'EARNED'].includes(props.commission.status));
const isApproved = computed(() => ['approved', 'APPROVED'].includes(props.commission.status));
const isPaid = computed(() => ['paid', 'PAID'].includes(props.commission.status));
const isReversed = computed(() => ['reversed', 'REVERSED'].includes(props.commission.status));

const adjustForm = useForm({
    commission_rate: Number(props.commission.commission_rate),
    reason: '',
});

const previewAdjustAmount = computed(() => {
    const rate = Number(adjustForm.commission_rate) || 0;
    const base = Number(props.commission.eligible_amount) || 0;
    return (base * (rate / 100)).toFixed(2);
});

const submitAdjust = () => {
    adjustForm.patch(route('commissions.adjust-rate', props.commission.id), {
        onSuccess: () => {
            showAdjustModal.value = false;
        },
    });
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'payout' && isApproved.value && props.can.pay) {
        showPayoutModal.value = true;
    }
});

const payoutForm = useForm({
    payment_method: props.paymentMethods[0]?.value || 'cash',
    payment_reference: '',
    paid_at: new Date().toISOString().split('T')[0],
    paid_to: props.commission.driver?.name || props.commission.referral_partner?.name || '',
    notes: '',
});

const reverseForm = useForm({
    reversal_reason: '',
});

const submitPayout = () => {
    payoutForm.post(route('commissions.payout', props.commission.id), {
        onSuccess: () => {
            showPayoutModal.value = false;
            payoutForm.reset();
        },
    });
};

const submitReversal = () => {
    reverseForm.patch(route('commissions.reverse', props.commission.id), {
        onSuccess: () => {
            showReverseModal.value = false;
            reverseForm.reset();
        },
    });
};

const handleApprove = () => {
    if (confirm(`Approve commission ${props.commission.commission_reference} for \$${props.commission.commission_amount}?`)) {
        router.patch(route('commissions.approve', props.commission.id));
    }
};

const getStatusBadgeClass = (status: string) => {
    switch (status?.toUpperCase()) {
        case 'PAID':
            return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-500/30';
        case 'APPROVED':
            return 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-400 dark:ring-blue-500/30';
        case 'EARNED':
            return 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-400 dark:ring-amber-500/30';
        case 'REVERSED':
            return 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-400 dark:ring-rose-500/30';
        case 'PENDING':
        default:
            return 'bg-slate-50 text-slate-700 ring-slate-600/20 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-500/30';
    }
};
</script>

<template>
    <Head :title="`Commission ${commission.commission_reference}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('commissions.index')"
                        class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        title="Back to Commissions"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-bold leading-tight text-slate-900 dark:text-white font-mono">
                                {{ commission.commission_reference }}
                            </h2>
                            <span
                                :class="[
                                    getStatusBadgeClass(commission.status),
                                    'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ring-1 ring-inset'
                                ]"
                            >
                                {{ commission.status }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Earned on {{ commission.earned_at ? new Date(commission.earned_at).toLocaleString() : 'N/A' }}
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <!-- Adjust Rate Button (Super Admin / Admin) -->
                    <button
                        v-if="isEarned && can.update"
                        @click="showAdjustModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 rounded-lg shadow-sm transition-colors"
                        title="Adjust Commission Rate %"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Adjust Rate (%)
                    </button>

                    <!-- Approve Button -->
                    <button
                        v-if="isEarned && can.approve"
                        @click="handleApprove"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Approve Commission
                    </button>

                    <!-- Payout Button -->
                    <button
                        v-if="isApproved && can.pay"
                        @click="showPayoutModal = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Record Driver Payout
                    </button>

                    <!-- Reverse Button -->
                    <button
                        v-if="(isEarned || isApproved) && can.reverse"
                        @click="showReverseModal = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800 rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reverse
                    </button>

                    <!-- Order Link -->
                    <Link
                        v-if="commission.order"
                        :href="route('orders.show', commission.order.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition-colors"
                    >
                        View Order
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
            <!-- Informational Status Banners -->
            <!-- 1. Reversal Banner if reversed -->
            <div
                v-if="isReversed"
                class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-800 dark:text-rose-300 text-sm"
            >
                <div class="font-bold flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    This commission was reversed on {{ commission.reversed_at ? new Date(commission.reversed_at).toLocaleString() : 'N/A' }}
                </div>
                <p class="mt-1 text-xs">
                    <strong>Reason:</strong> {{ commission.reversal_reason || 'No specific reason provided.' }}
                </p>
            </div>

            <!-- 2. Earned Banner: Pending Review / Approval -->
            <div
                v-if="isEarned"
                class="p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl text-amber-900 dark:text-amber-200 text-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
            >
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold">Pending Approval for Driver Payout</div>
                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                            This referral commission is earned. An authorized administrator or manager must review and approve it before driver payout disbursement can be executed.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button
                        v-if="can.update"
                        @click="showAdjustModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-white dark:bg-slate-900 hover:bg-indigo-50 dark:hover:bg-slate-800 border border-indigo-300 dark:border-indigo-700 rounded-lg shadow-xs transition-colors"
                    >
                        Adjust Rate (%)
                    </button>
                    <button
                        v-if="can.approve"
                        @click="handleApprove"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Approve Now
                    </button>
                </div>
            </div>

            <!-- 3. Approved Banner: Ready for Cashier Payout -->
            <div
                v-if="isApproved"
                class="p-4 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 rounded-xl text-blue-900 dark:text-blue-200 text-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
            >
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold">Approved — Ready for Driver Disbursement</div>
                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5">
                            Approved on {{ commission.approved_at ? new Date(commission.approved_at).toLocaleString() : 'N/A' }}. Finance / Cashier can now record the payout disbursement of ${{ Number(commission.commission_amount).toFixed(2) }} to {{ commission.driver?.name || 'the driver' }}.
                        </p>
                    </div>
                </div>
                <button
                    v-if="can.pay"
                    @click="showPayoutModal = true"
                    class="shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Record Driver Payout
                </button>
            </div>

            <!-- 4. Paid Banner: Disbursed -->
            <div
                v-if="isPaid"
                class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-900 dark:text-emerald-200 text-sm flex items-center gap-3"
            >
                <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <div class="font-semibold">Payout Settled</div>
                    <p class="text-xs text-emerald-700 dark:text-emerald-300 mt-0.5">
                        Disbursed ${{ Number(commission.commission_amount).toFixed(2) }} to {{ commission.driver?.name || 'the driver' }} on {{ commission.paid_at ? new Date(commission.paid_at).toLocaleString() : 'N/A' }}. See disbursement records below.
                    </p>
                </div>
            </div>

            <!-- 5. Rate Adjustment Audit Trail (if modified) -->
            <div
                v-if="commission.adjustment_notes"
                class="p-4 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-700 dark:text-slate-300"
            >
                <div class="font-bold flex items-center gap-1.5 text-slate-900 dark:text-white mb-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Rate Adjustment History
                </div>
                <div class="font-mono text-xs whitespace-pre-wrap text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-750">{{ commission.adjustment_notes }}</div>
            </div>

            <!-- Top Cards: Financial Breakdown -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <!-- Commission Amount Card -->
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-700 text-white rounded-xl p-6 shadow-md">
                    <div class="text-xs uppercase tracking-wider font-semibold opacity-80">
                        Commission Due to Driver
                    </div>
                    <div class="text-3xl font-extrabold mt-2 font-mono">
                        ${{ Number(commission.commission_amount).toFixed(2) }}
                    </div>
                    <div class="text-xs opacity-80 mt-2">
                        Calculated at {{ Number(commission.commission_rate).toFixed(1) }}% of ${{ Number(commission.eligible_amount).toFixed(2) }}
                    </div>
                </div>

                <!-- Eligible Order Base Card -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 shadow-sm">
                    <div class="text-xs uppercase tracking-wider font-semibold text-slate-500 dark:text-slate-400">
                        Eligible Base (Order Total)
                    </div>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white mt-2 font-mono">
                        ${{ Number(commission.eligible_amount).toFixed(2) }}
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        Snapshotted from Order {{ commission.order?.order_number || 'N/A' }}
                    </div>
                </div>

                <!-- Commission Rate Card -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 shadow-sm">
                    <div class="text-xs uppercase tracking-wider font-semibold text-slate-500 dark:text-slate-400">
                        Snapshotted Commission Rate
                    </div>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white mt-2 font-mono">
                        {{ Number(commission.commission_rate).toFixed(1) }}%
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        Partner Policy at time of order payment
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Cols: Referral Attribution & Order Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Referral Attribution Ownership -->
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 shadow-sm">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Attributed Referral Beneficiary
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Commission is assigned to the driver and partner bound to the order at confirmation time.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                            <!-- Driver -->
                            <div>
                                <span class="text-xs text-slate-400 block">Driver (Beneficiary)</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 text-sm">
                                    {{ commission.driver?.name || 'Unknown Driver' }}
                                </span>
                                <div v-if="commission.driver?.phone" class="text-xs text-slate-500 font-mono mt-0.5">
                                    {{ commission.driver.phone }}
                                </div>
                            </div>

                            <!-- Partner -->
                            <div>
                                <span class="text-xs text-slate-400 block">Referral Partner</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 text-sm">
                                    {{ commission.referral_partner?.name || 'Direct Driver' }}
                                </span>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Type: {{ commission.referral_partner?.type || 'DRIVER' }}
                                </div>
                            </div>

                            <!-- Vehicle -->
                            <div>
                                <span class="text-xs text-slate-400 block">Vehicle</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 text-sm font-mono">
                                    {{ commission.vehicle?.plate_number || 'N/A' }}
                                </span>
                                <div v-if="commission.vehicle" class="text-xs text-slate-500 mt-0.5">
                                    {{ commission.vehicle.vehicle_type }}
                                </div>
                            </div>
                        </div>

                        <!-- Attribution Token -->
                        <div v-if="commission.referral_attribution" class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between text-xs text-slate-500">
                            <div>
                                <span>Referral Token Code:</span>
                                <strong class="ml-1 text-slate-700 dark:text-slate-300 font-mono">
                                    {{ commission.referral_attribution.referral_code?.code || 'N/A' }}
                                </strong>
                            </div>
                            <div>
                                <span>Attribution Date:</span>
                                <span class="ml-1">
                                    {{ new Date(commission.referral_attribution.attributed_at).toLocaleDateString() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Associated Order Details -->
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Associated Order #{{ commission.order?.order_number || 'N/A' }}
                            </h3>
                            <Link
                                v-if="commission.order"
                                :href="route('orders.show', commission.order.id)"
                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                            >
                                Open Full Order &rarr;
                            </Link>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 text-sm">
                            <div>
                                <span class="text-xs text-slate-400 block">Customer</span>
                                <span class="font-medium text-slate-800 dark:text-slate-200">
                                    {{ commission.order?.customer?.name || 'Walk-in Customer' }}
                                </span>
                                <div class="text-xs text-slate-500 font-mono mt-0.5">
                                    {{ commission.order?.customer?.phone || 'No phone' }}
                                </div>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block">Order Status</span>
                                <span class="font-medium text-slate-800 dark:text-slate-200">
                                    {{ commission.order?.status || 'N/A' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block">Customer Payment</span>
                                <span class="font-medium text-emerald-600 dark:text-emerald-400">
                                    ${{ Number(commission.order?.paid_amount || 0).toFixed(2) }} (Fully Paid)
                                </span>
                            </div>
                        </div>

                        <!-- Order Items Mini-table -->
                        <div v-if="commission.order?.items && commission.order.items.length > 0" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-2">Order Line Items</span>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead class="text-slate-400 border-b border-slate-100 dark:border-slate-700">
                                        <tr>
                                            <th class="text-left py-1.5">Description</th>
                                            <th class="text-center py-1.5">Qty</th>
                                            <th class="text-right py-1.5">Unit Price</th>
                                            <th class="text-right py-1.5">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="item in commission.order.items" :key="item.id">
                                            <td class="py-1.5 text-slate-700 dark:text-slate-300">{{ item.description }}</td>
                                            <td class="py-1.5 text-center text-slate-500">{{ item.quantity }}</td>
                                            <td class="py-1.5 text-right font-mono text-slate-500">${{ Number(item.unit_price).toFixed(2) }}</td>
                                            <td class="py-1.5 text-right font-mono font-medium text-slate-800 dark:text-slate-200">${{ Number(item.subtotal).toFixed(2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Disbursements History -->
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Payout & Disbursement Records
                            </h3>
                            <button
                                v-if="isApproved && can.pay"
                                @click="showPayoutModal = true"
                                class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline"
                            >
                                + Record Payout
                            </button>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                            <div v-if="commission.payments && commission.payments.length > 0" class="overflow-x-auto">
                                <table class="w-full text-xs text-left">
                                    <thead class="text-slate-400 border-b border-slate-100 dark:border-slate-700">
                                        <tr>
                                            <th class="py-2 px-3">Payout Ref</th>
                                            <th class="py-2 px-3">Method</th>
                                            <th class="py-2 px-3">Paid To</th>
                                            <th class="py-2 px-3">Date</th>
                                            <th class="py-2 px-3">Recorded By</th>
                                            <th class="py-2 px-3 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="p in commission.payments" :key="p.id">
                                            <td class="py-2.5 px-3 font-mono font-medium text-slate-800 dark:text-slate-200">
                                                {{ p.payment_reference }}
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                                    {{ p.payment_method }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-700 dark:text-slate-300">
                                                {{ p.paid_to }}
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-500 whitespace-nowrap">
                                                {{ new Date(p.paid_at).toLocaleDateString() }}
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-500">
                                                {{ p.creator?.name || 'Staff' }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                                ${{ Number(p.amount).toFixed(2) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-6 text-slate-400 text-xs">
                                No payout disbursements recorded yet.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Col: Audit Timeline & Status Progress -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 shadow-sm">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Lifecycle & Audit Trail
                        </h3>

                        <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-700">
                            <!-- Step 1: Earned -->
                            <div class="relative">
                                <div class="absolute -left-6 top-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-800 bg-amber-500"></div>
                                <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">Earned</div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    {{ commission.earned_at ? new Date(commission.earned_at).toLocaleString() : 'N/A' }}
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Automatically generated upon full payment of order.
                                </div>
                            </div>

                            <!-- Step 2: Approved -->
                            <div class="relative">
                                <div
                                    :class="[
                                        'absolute -left-6 top-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-800',
                                        commission.approved_at ? 'bg-blue-500' : 'bg-slate-300 dark:bg-slate-600'
                                    ]"
                                ></div>
                                <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">Approved</div>
                                <div v-if="commission.approved_at" class="text-xs text-slate-400 mt-0.5">
                                    {{ new Date(commission.approved_at).toLocaleString() }}
                                </div>
                                <div v-if="commission.approved_by_user" class="text-xs text-slate-500 mt-0.5">
                                    Approved by {{ commission.approved_by_user.name }}
                                </div>
                                <div v-else-if="isEarned" class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                                    Pending finance/admin review.
                                </div>
                            </div>

                            <!-- Step 3: Paid -->
                            <div class="relative">
                                <div
                                    :class="[
                                        'absolute -left-6 top-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-800',
                                        commission.paid_at ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'
                                    ]"
                                ></div>
                                <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">Paid Out</div>
                                <div v-if="commission.paid_at" class="text-xs text-slate-400 mt-0.5">
                                    {{ new Date(commission.paid_at).toLocaleString() }}
                                </div>
                                <div v-if="commission.paid_by_user" class="text-xs text-slate-500 mt-0.5">
                                    Disbursed by {{ commission.paid_by_user.name }}
                                </div>
                                <div v-else-if="isApproved" class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">
                                    Ready for cashier payout.
                                </div>
                            </div>

                            <!-- Step 4: Reversal (if occurred) -->
                            <div v-if="commission.reversed_at" class="relative">
                                <div class="absolute -left-6 top-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-800 bg-rose-500"></div>
                                <div class="text-xs font-semibold text-rose-600 dark:text-rose-400">Reversed</div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    {{ new Date(commission.reversed_at).toLocaleString() }}
                                </div>
                                <div v-if="commission.reversed_by_user" class="text-xs text-slate-500 mt-0.5">
                                    By {{ commission.reversed_by_user.name }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <!-- Payout Modal -->
        <div v-if="showPayoutModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-700">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Record Driver Payout</h3>
                    <button @click="showPayoutModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitPayout" class="mt-4 space-y-4">
                    <!-- Amount info -->
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                        <div class="text-xs text-emerald-800 dark:text-emerald-300 font-medium">Disbursement Amount</div>
                        <div class="text-2xl font-bold font-mono text-emerald-700 dark:text-emerald-400 mt-1">
                            ${{ Number(commission.commission_amount).toFixed(2) }}
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Disbursement Method *</label>
                        <select
                            v-model="payoutForm.payment_method"
                            class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:text-white"
                            required
                        >
                            <option v-for="m in paymentMethods" :key="m.value" :value="m.value">
                                {{ m.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Paid To -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Recipient Name *</label>
                        <input
                            v-model="payoutForm.paid_to"
                            type="text"
                            class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:text-white"
                            required
                        />
                    </div>

                    <!-- Payment Date -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Payment Date *</label>
                        <input
                            v-model="payoutForm.paid_at"
                            type="date"
                            class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:text-white"
                            required
                        />
                    </div>

                    <!-- Reference -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">External Reference / Receipt #</label>
                        <input
                            v-model="payoutForm.payment_reference"
                            type="text"
                            placeholder="Optional cashier voucher / receipt number"
                            class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:text-white"
                        />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                        <textarea
                            v-model="payoutForm.notes"
                            rows="2"
                            placeholder="Additional payout details..."
                            class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <button
                            type="button"
                            @click="showPayoutModal = false"
                            class="px-4 py-2 text-sm text-slate-700 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 rounded-lg"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="payoutForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm disabled:opacity-50"
                        >
                            Confirm Payout
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reverse Modal -->
        <div v-if="showReverseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-700">
                    <h3 class="text-lg font-bold text-rose-600">Reverse Commission</h3>
                    <button @click="showReverseModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitReversal" class="mt-4 space-y-4">
                    <div class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300">
                        This action will mark commission <strong>{{ commission.commission_reference }}</strong> as reversed and prevent any driver payout.
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Reason for Reversal *</label>
                        <textarea
                            v-model="reverseForm.reversal_reason"
                            rows="3"
                            placeholder="State reason (e.g., customer refund, cancellation, erroneous attribution)..."
                            class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-rose-500 dark:text-white"
                            required
                        ></textarea>
                        <div v-if="reverseForm.errors.reversal_reason" class="text-xs text-rose-600 mt-1">
                            {{ reverseForm.errors.reversal_reason }}
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <button
                            type="button"
                            @click="showReverseModal = false"
                            class="px-4 py-2 text-sm text-slate-700 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 rounded-lg"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="reverseForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm disabled:opacity-50"
                        >
                            Confirm Reversal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Adjust Commission Rate Modal (Super Admin / Admin) -->
        <div v-if="showAdjustModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-700">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Adjust Commission Rate
                    </h3>
                    <button @click="showAdjustModal = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
                </div>

                <form @submit.prevent="submitAdjust" class="mt-4 space-y-4">
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 rounded-lg">
                        <div class="text-xs text-indigo-700 dark:text-indigo-300 font-medium">Eligible Base (Order Total)</div>
                        <div class="text-xl font-bold font-mono text-indigo-900 dark:text-white mt-0.5">
                            ${{ Number(commission.eligible_amount).toFixed(2) }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">New Commission Rate (%) *</label>
                        <div class="relative">
                            <input
                                v-model.number="adjustForm.commission_rate"
                                type="number"
                                step="0.1"
                                min="0"
                                max="100"
                                required
                                class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white font-mono"
                            />
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-sm font-bold">
                                %
                            </div>
                        </div>

                        <!-- Presets -->
                        <div class="flex items-center gap-1.5 mt-2">
                            <span class="text-xs text-slate-400">Presets:</span>
                            <button
                                v-for="preset in [0, 3, 5, 7, 10, 15]"
                                :key="preset"
                                type="button"
                                @click="adjustForm.commission_rate = preset"
                                class="px-2 py-0.5 text-xs rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono transition"
                            >
                                {{ preset }}%
                            </button>
                        </div>
                    </div>

                    <!-- Live Recalculated Amount -->
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg flex items-center justify-between">
                        <span class="text-xs font-medium text-emerald-800 dark:text-emerald-300">Recalculated Commission:</span>
                        <span class="text-xl font-extrabold font-mono text-emerald-700 dark:text-emerald-400">
                            ${{ previewAdjustAmount }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Reason / Note (Audit Trail)</label>
                        <textarea
                            v-model="adjustForm.reason"
                            rows="2"
                            placeholder="e.g. Returning client bulk order rate discount"
                            class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                        <button
                            type="button"
                            @click="showAdjustModal = false"
                            class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="adjustForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition disabled:opacity-50"
                        >
                            Save & Recalculate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
