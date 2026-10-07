<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Order, Payment } from '@/types';

const props = defineProps<{
    order: Order;
    paymentMethods: string[];
    can: {
        update: boolean;
        confirm: boolean;
        cancel: boolean;
        complete: boolean;
        delete: boolean;
        recordPayment: boolean;
        refundPayment: boolean;
    };
}>();

// Modals
const showPaymentModal = ref(false);
const showCancelModal = ref(false);
const showRefundModal = ref(false);
const selectedPaymentToRefund = ref<Payment | null>(null);

// Payment form
const paymentForm = useForm({
    amount: props.order.outstanding_amount > 0 ? props.order.outstanding_amount : 0,
    payment_method: 'cash',
    payment_reference: '',
    paid_at: new Date().toISOString().split('T')[0],
    notes: '',
});

const submitPayment = () => {
    paymentForm.post(route('orders.payments.store', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
            paymentForm.reset();
        },
    });
};

// Cancel form
const cancelForm = useForm({
    reason: '',
});

const submitCancel = () => {
    cancelForm.patch(route('orders.cancel', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCancelModal.value = false;
            cancelForm.reset();
        },
    });
};

// Refund form
const refundForm = useForm({
    refund_reason: '',
});

const openRefundModal = (payment: Payment) => {
    selectedPaymentToRefund.value = payment;
    refundForm.refund_reason = '';
    showRefundModal.value = true;
};

const submitRefund = () => {
    if (!selectedPaymentToRefund.value) return;

    refundForm.patch(route('payments.refund', selectedPaymentToRefund.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRefundModal.value = false;
            selectedPaymentToRefund.value = null;
            refundForm.reset();
        },
    });
};

const confirmOrder = () => {
    if (confirm('Are you sure you want to confirm this order?')) {
        router.patch(route('orders.confirm', props.order.id), {}, { preserveScroll: true });
    }
};

const completeOrder = () => {
    if (confirm('Are you sure you want to mark this order as completed?')) {
        router.patch(route('orders.complete', props.order.id), {}, { preserveScroll: true });
    }
};

const getOrderStatusBadge = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
        case 'confirmed':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-200 dark:border-blue-800';
        case 'completed':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
        case 'cancelled':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300';
    }
};

const getPaymentStatusBadge = (status: string) => {
    switch (status) {
        case 'paid':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
        case 'partial':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
        case 'unpaid':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
        case 'refunded':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300 border border-purple-200 dark:border-purple-800';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300';
    }
};
</script>

<template>
    <Head :title="`Order #${order.order_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('orders.index')"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400"
                        >
                            &larr; Back to Orders
                        </Link>
                    </div>
                    <div class="flex items-center gap-3 mt-1">
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white font-mono">
                            {{ order.order_number }}
                        </h2>
                        <span :class="getOrderStatusBadge(order.status)" class="px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize">
                            {{ order.status }}
                        </span>
                        <span :class="getPaymentStatusBadge(order.payment_status)" class="px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize">
                            Payment: {{ order.payment_status }}
                        </span>
                    </div>
                </div>

                <!-- Action Buttons Toolbar -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-if="can.confirm"
                        @click="confirmOrder"
                        type="button"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-blue-500 transition"
                    >
                        ✓ Confirm Order
                    </button>

                    <button
                        v-if="can.complete"
                        @click="completeOrder"
                        type="button"
                        class="inline-flex items-center rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500 transition"
                    >
                        ✓ Mark Completed
                    </button>

                    <button
                        v-if="can.recordPayment"
                        @click="showPaymentModal = true"
                        type="button"
                        class="inline-flex items-center rounded-lg bg-emerald-50 border border-emerald-200 px-3.5 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:border-emerald-800 dark:text-emerald-300 transition"
                    >
                        💳 Record Payment
                    </button>

                    <button
                        v-if="can.cancel"
                        @click="showCancelModal = true"
                        type="button"
                        class="inline-flex items-center rounded-lg border border-rose-300 bg-white px-3.5 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-800 dark:bg-slate-900 dark:text-rose-400 transition"
                    >
                        Cancel Order
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Top Row: Financial Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <span class="text-xs font-semibold uppercase text-slate-400 block">Total Order Amount</span>
                        <div class="text-2xl font-bold font-mono text-slate-900 dark:text-white mt-1">
                            ${{ Number(order.total_amount).toFixed(2) }}
                        </div>
                        <span class="text-xs text-slate-400 block mt-1" v-if="Number(order.discount) > 0">
                            Includes ${{ Number(order.discount).toFixed(2) }} discount
                        </span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <span class="text-xs font-semibold uppercase text-emerald-600 dark:text-emerald-400 block">Total Paid</span>
                        <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                            ${{ Number(order.paid_amount).toFixed(2) }}
                        </div>
                        <span class="text-xs text-slate-400 block mt-1">
                            {{ order.payments ? order.payments.filter(p => p.status === 'paid').length : 0 }} valid payments
                        </span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <span class="text-xs font-semibold uppercase block" :class="order.outstanding_amount > 0 ? 'text-rose-500' : 'text-slate-400'">
                            Outstanding Balance
                        </span>
                        <div class="text-2xl font-bold font-mono mt-1" :class="order.outstanding_amount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400'">
                            ${{ Number(order.outstanding_amount).toFixed(2) }}
                        </div>
                        <span class="text-xs block mt-1 font-semibold" :class="order.outstanding_amount > 0 ? 'text-amber-600' : 'text-emerald-600'">
                            {{ order.outstanding_amount > 0 ? 'Awaiting Payment' : 'Fully Settled' }}
                        </span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <span class="text-xs font-semibold uppercase text-blue-600 dark:text-blue-400 block">Commission Readiness</span>
                        <div class="mt-1 flex items-center gap-1.5">
                            <span v-if="order.status === 'confirmed' && order.paid_amount >= Number(order.total_amount) && order.referral_attribution_id" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                ⭐ Commission Eligible
                            </span>
                            <span v-else-if="!order.referral_attribution_id" class="text-xs text-slate-400 italic">
                                Direct Customer (No Referral)
                            </span>
                            <span v-else class="text-xs text-amber-600 dark:text-amber-400 font-medium">
                                Incomplete (Requires Full Payment &amp; Confirmation)
                            </span>
                        </div>
                        <span class="text-xs text-slate-400 block mt-1">
                            Phase 6 Commission Trigger Target
                        </span>
                    </div>
                </div>

                <!-- Customer & Referral Attribution Details -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Customer Information -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 pb-2 border-b border-slate-100 dark:border-slate-800">
                            Customer Profile
                        </h3>
                        <div class="mt-3 space-y-2 text-sm">
                            <div>
                                <span class="text-xs text-slate-400 block">Name</span>
                                <span class="font-bold text-slate-900 dark:text-white">
                                    {{ order.customer?.name ?? 'Anonymous Customer' }}
                                </span>
                            </div>
                            <div v-if="order.customer?.phone">
                                <span class="text-xs text-slate-400 block">Phone</span>
                                <span class="text-slate-700 dark:text-slate-300">{{ order.customer.phone }}</span>
                            </div>
                            <div v-if="order.customer?.email">
                                <span class="text-xs text-slate-400 block">Email</span>
                                <span class="text-slate-700 dark:text-slate-300">{{ order.customer.email }}</span>
                            </div>
                            <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                                <span class="text-xs text-slate-400 block">Originating Lead</span>
                                <Link
                                    v-if="order.lead_id"
                                    :href="route('leads.show', order.lead_id)"
                                    class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400"
                                >
                                    View Lead #{{ order.lead_id }} &rarr;
                                </Link>
                                <span v-else class="text-xs text-slate-400">Direct Intake</span>
                            </div>
                        </div>
                    </div>

                    <!-- Permanent Referral Attribution Binding (2 cols) -->
                    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Permanent Referral Attribution Binding
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Immutable referral ownership preserved from lead creation
                                </p>
                            </div>
                            <span class="text-xs px-2.5 py-0.5 rounded font-mono font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                ID: #{{ order.referral_attribution_id ?? 'NONE' }}
                            </span>
                        </div>

                        <div v-if="order.referral_attribution" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                                <span class="text-xs font-semibold uppercase text-slate-400 block">Referring Driver</span>
                                <div class="font-bold text-slate-900 dark:text-white mt-1">
                                    {{ order.referral_attribution.driver?.name ?? 'Unknown Driver' }}
                                </div>
                                <div class="text-xs text-slate-500" v-if="order.referral_attribution.driver?.phone">
                                    📞 {{ order.referral_attribution.driver.phone }}
                                </div>
                            </div>

                            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                                <span class="text-xs font-semibold uppercase text-slate-400 block">Vehicle & QR Code</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="font-mono font-bold text-blue-600 dark:text-blue-400 text-sm">
                                        {{ order.referral_attribution.referral_code?.code ?? 'N/A' }}
                                    </span>
                                    <span v-if="order.referral_attribution.vehicle" class="text-xs px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 font-semibold text-slate-800 dark:text-slate-200">
                                        Plate: {{ order.referral_attribution.vehicle.plate_number }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5" v-if="order.referral_attribution.referral_partner">
                                    Partner: {{ order.referral_attribution.referral_partner.name }}
                                </div>
                            </div>

                            <div class="md:col-span-2 text-xs text-slate-500 flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                                <span>Attribution Date: {{ new Date(order.referral_attribution.attributed_at).toLocaleString() }}</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-medium">Status: {{ order.referral_attribution.attribution_status }}</span>
                            </div>

                            <!-- Driver Commission Status -->
                            <div class="md:col-span-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                <div v-if="order.commission" class="p-3.5 rounded-lg bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold uppercase text-indigo-700 dark:text-indigo-300">Driver Commission</span>
                                            <span class="text-xs px-2 py-0.5 rounded-full font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                                {{ order.commission.status }}
                                            </span>
                                        </div>
                                        <div class="mt-1 text-sm font-mono font-bold text-slate-900 dark:text-white flex items-center gap-3">
                                            <span>${{ Number(order.commission.commission_amount).toFixed(2) }}</span>
                                            <span class="text-xs text-slate-500 font-normal">({{ Number(order.commission.commission_rate).toFixed(1) }}% of ${{ Number(order.commission.eligible_amount).toFixed(2) }})</span>
                                        </div>
                                    </div>
                                    <Link
                                        :href="route('commissions.show', order.commission.id)"
                                        class="inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors"
                                    >
                                        View Commission &rarr;
                                    </Link>
                                </div>
                                <div v-else-if="order.status === 'confirmed' || order.status === 'completed'" class="p-3 rounded-lg bg-amber-50/70 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div>
                                        <span class="text-xs font-semibold text-amber-800 dark:text-amber-400 block">No Commission Recorded Yet</span>
                                        <span class="text-xs text-slate-500">
                                            {{ order.payment_status === 'paid' ? 'Order is fully paid and eligible for commission evaluation.' : 'Commission will be generated once order is fully paid.' }}
                                        </span>
                                    </div>
                                    <button
                                        v-if="order.payment_status === 'paid' && $page.props.auth.can.viewCommissions"
                                        @click="router.post(route('orders.evaluate-commission', order.id))"
                                        class="px-3 py-1.5 text-xs font-medium text-amber-900 bg-amber-200 hover:bg-amber-300 rounded-lg transition-colors whitespace-nowrap"
                                    >
                                        Evaluate Commission
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-xs text-slate-400 italic py-6 text-center">
                            Direct Order &mdash; No referral driver or partner is bound to this order.
                        </div>
                    </div>
                </div>

                <!-- Order Line Items -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Order Details</h3>
                        <span class="text-xs text-slate-400">Currency: {{ order.currency }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="px-6 py-3">#</th>
                                    <th class="px-6 py-3">Item Description</th>
                                    <th class="px-6 py-3 text-right">Quantity</th>
                                    <th class="px-6 py-3 text-right">Unit Price</th>
                                    <th class="px-6 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="(item, idx) in order.items" :key="item.id">
                                    <td class="px-6 py-3.5 text-xs text-slate-400">{{ idx + 1 }}</td>
                                    <td class="px-6 py-3.5 font-medium text-slate-900 dark:text-white">
                                        {{ item.description }}
                                        <p v-if="item.notes" class="text-xs text-slate-400 font-normal mt-0.5">
                                            {{ item.notes }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-3.5 text-right font-mono">{{ item.quantity }}</td>
                                    <td class="px-6 py-3.5 text-right font-mono">${{ Number(item.unit_price).toFixed(2) }}</td>
                                    <td class="px-6 py-3.5 text-right font-mono font-bold text-slate-900 dark:text-white">
                                        ${{ Number(item.subtotal).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                                <tr>
                                    <td colspan="4" class="px-6 py-2.5 text-right text-xs font-semibold uppercase text-slate-500">Subtotal:</td>
                                    <td class="px-6 py-2.5 text-right font-mono font-semibold text-slate-900 dark:text-white">${{ Number(order.subtotal).toFixed(2) }}</td>
                                </tr>
                                <tr v-if="Number(order.discount) > 0">
                                    <td colspan="4" class="px-6 py-2.5 text-right text-xs font-semibold uppercase text-slate-500">Discount:</td>
                                    <td class="px-6 py-2.5 text-right font-mono font-semibold text-rose-600">-${{ Number(order.discount).toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="px-6 py-3 text-right text-sm font-bold uppercase text-slate-900 dark:text-white">Total Amount:</td>
                                    <td class="px-6 py-3 text-right font-mono font-bold text-base text-emerald-600 dark:text-emerald-400">${{ Number(order.total_amount).toFixed(2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Payment History & Recording -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Payment Transactions</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Historical ledger of all customer payments and approved refunds</p>
                        </div>
                        <button
                            v-if="can.recordPayment"
                            @click="showPaymentModal = true"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500 transition"
                        >
                            + Record Payment
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="px-6 py-3">Reference</th>
                                    <th class="px-6 py-3">Paid Date</th>
                                    <th class="px-6 py-3">Method</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3 text-center">Status</th>
                                    <th class="px-6 py-3">Received By</th>
                                    <th class="px-6 py-3">Notes</th>
                                    <th class="px-6 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="payment in order.payments" :key="payment.id" :class="payment.status === 'refunded' ? 'opacity-60 bg-slate-50/50 dark:bg-slate-800/30' : ''">
                                    <td class="px-6 py-3.5 font-mono font-bold text-slate-900 dark:text-white">
                                        {{ payment.payment_reference }}
                                    </td>
                                    <td class="px-6 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                                        {{ new Date(payment.paid_at).toLocaleDateString() }}
                                    </td>
                                    <td class="px-6 py-3.5 text-xs font-medium uppercase">
                                        {{ payment.payment_method.replace('_', ' ') }}
                                    </td>
                                    <td class="px-6 py-3.5 text-right font-mono font-bold" :class="payment.status === 'refunded' ? 'line-through text-slate-400' : 'text-emerald-600 dark:text-emerald-400'">
                                        ${{ Number(payment.amount).toFixed(2) }}
                                    </td>
                                    <td class="px-6 py-3.5 text-center">
                                        <span
                                            :class="payment.status === 'paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300'"
                                            class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                                        >
                                            {{ payment.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-xs">
                                        {{ payment.receiver?.name ?? 'Staff' }}
                                    </td>
                                    <td class="px-6 py-3.5 text-xs text-slate-500 max-w-xs truncate">
                                        <span v-if="payment.status === 'refunded'">
                                            Refund reason: {{ payment.refund_reason }} (by {{ payment.refunder?.name }})
                                        </span>
                                        <span v-else>
                                            {{ payment.notes || '—' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-right">
                                        <button
                                            v-if="payment.status === 'paid' && can.refundPayment"
                                            @click="openRefundModal(payment)"
                                            type="button"
                                            class="inline-flex items-center text-xs font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400"
                                        >
                                            Refund
                                        </button>
                                        <span v-else-if="payment.status === 'refunded'" class="text-xs text-slate-400 italic">
                                            Refunded
                                        </span>
                                    </td>
                                </tr>

                                <tr v-if="!order.payments || order.payments.length === 0">
                                    <td colspan="8" class="px-6 py-8 text-center text-slate-400 text-sm">
                                        No customer payments recorded yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Record Payment -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900 dark:border dark:border-slate-800">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Record Customer Payment</h3>
                    <button @click="showPaymentModal = false" type="button" class="text-slate-400 hover:text-slate-500">&times;</button>
                </div>

                <form @submit.prevent="submitPayment" class="mt-4 space-y-4">
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Order Total:</span>
                            <span class="font-mono font-bold">${{ Number(order.total_amount).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Current Outstanding:</span>
                            <span class="font-mono font-bold text-rose-600 dark:text-rose-400">${{ Number(order.outstanding_amount).toFixed(2) }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Amount ($) *</label>
                        <input
                            v-model.number="paymentForm.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :max="order.outstanding_amount"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <InputError :message="paymentForm.errors.amount" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Method *</label>
                        <select
                            v-model="paymentForm.payment_method"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        >
                            <option v-for="method in paymentMethods" :key="method" :value="method">
                                {{ method.replace('_', ' ').toUpperCase() }}
                            </option>
                        </select>
                        <InputError :message="paymentForm.errors.payment_method" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Custom Reference / Receipt #</label>
                        <input
                            v-model="paymentForm.payment_reference"
                            type="text"
                            placeholder="Leave blank for auto-generated reference (PAY-YYYY-XXXXXX)"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <InputError :message="paymentForm.errors.payment_reference" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Paid Date</label>
                        <input
                            v-model="paymentForm.paid_at"
                            type="date"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                        <input
                            v-model="paymentForm.notes"
                            type="text"
                            placeholder="Optional payment notes"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button
                            @click="showPaymentModal = false"
                            type="button"
                            class="rounded-lg border border-slate-300 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="paymentForm.processing"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500 disabled:opacity-50 transition"
                        >
                            Save Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Cancel Order -->
        <div v-if="showCancelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900 dark:border dark:border-slate-800">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Cancel Order</h3>
                <p class="text-xs text-slate-500 mt-1">Please provide a reason for cancelling this order.</p>

                <form @submit.prevent="submitCancel" class="mt-4 space-y-4">
                    <div>
                        <textarea
                            v-model="cancelForm.reason"
                            rows="3"
                            placeholder="Reason for cancellation..."
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-slate-900 focus:border-rose-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button
                            @click="showCancelModal = false"
                            type="button"
                            class="rounded-lg border border-slate-300 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 transition"
                        >
                            Back
                        </button>
                        <button
                            type="submit"
                            :disabled="cancelForm.processing"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-500 transition"
                        >
                            Confirm Cancellation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Refund Payment -->
        <div v-if="showRefundModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900 dark:border dark:border-slate-800">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Process Payment Refund</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Refunding payment <span class="font-mono font-bold">{{ selectedPaymentToRefund?.payment_reference }}</span> of ${{ Number(selectedPaymentToRefund?.amount).toFixed(2) }}.
                </p>

                <form @submit.prevent="submitRefund" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Refund Reason *
                        </label>
                        <textarea
                            v-model="refundForm.refund_reason"
                            rows="3"
                            placeholder="Specify reason for refund (e.g. customer cancelled order, overcharge correction)..."
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-slate-900 focus:border-rose-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        ></textarea>
                        <InputError :message="refundForm.errors.refund_reason" class="mt-1" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <button
                            @click="showRefundModal = false"
                            type="button"
                            class="rounded-lg border border-slate-300 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 transition"
                        >
                            Back
                        </button>
                        <button
                            type="submit"
                            :disabled="refundForm.processing"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-500 transition"
                        >
                            Confirm Refund
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
