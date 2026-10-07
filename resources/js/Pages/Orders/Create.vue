<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Lead } from '@/types';

const props = defineProps<{
    lead: Lead;
}>();

interface ItemInput {
    description: string;
    quantity: number;
    unit_price: number;
    notes: string;
}

const form = useForm({
    lead_id: props.lead.id,
    discount: 0,
    notes: '',
    items: [
        {
            description: props.lead.title ? `Translation Service: ${props.lead.title}` : 'Document Translation Service',
            quantity: 1,
            unit_price: 10,
            notes: '',
        },
    ] as ItemInput[],
});

const addItem = () => {
    form.items.push({
        description: '',
        quantity: 1,
        unit_price: 10,
        notes: '',
    });
};

const removeItem = (index: number) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const calculatedSubtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        const qty = Number(item.quantity) || 0;
        const price = Number(item.unit_price) || 0;
        return sum + (qty * price);
    }, 0);
});

const calculatedTotal = computed(() => {
    const sub = calculatedSubtotal.value;
    const disc = Number(form.discount) || 0;
    return Math.max(0, sub - disc);
});

const submit = () => {
    form.post(route('orders.store'));
};
</script>

<template>
    <Head :title="`Create Order for Lead #${lead.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('leads.show', lead.id)"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400"
                        >
                            &larr; Back to Lead #{{ lead.id }}
                        </Link>
                    </div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
                        Create Order from Lead #{{ lead.id }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8 space-y-6">
                <!-- Read-Only Context: Customer & Referral Attribution Verification -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Customer Information Card -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2 mb-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Customer Details (Read-only)</h3>
                            <span class="text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 font-semibold">
                                Verified
                            </span>
                        </div>
                        <div class="space-y-1 text-sm">
                            <p class="font-bold text-slate-900 dark:text-white text-base">
                                {{ lead.customer?.name ?? 'Anonymous Customer' }}
                            </p>
                            <p class="text-xs text-slate-500" v-if="lead.customer?.phone">
                                Phone: {{ lead.customer.phone }}
                            </p>
                            <p class="text-xs text-slate-500" v-if="lead.customer?.email">
                                Email: {{ lead.customer.email }}
                            </p>
                            <p class="text-xs text-slate-400 mt-1">
                                Lead Reference: #{{ lead.id }} &bull; {{ lead.title }}
                            </p>
                        </div>
                    </div>

                    <!-- Permanent Referral Attribution Binding Card -->
                    <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-5 shadow-xs dark:border-blue-900/50 dark:bg-blue-950/20">
                        <div class="flex items-center justify-between border-b border-blue-100 dark:border-blue-900/50 pb-2 mb-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-blue-800 dark:text-blue-300">
                                Permanent Referral Attribution (Read-only)
                            </h3>
                            <span class="text-xs px-2 py-0.5 rounded bg-blue-200 text-blue-900 dark:bg-blue-900 dark:text-blue-200 font-mono font-bold">
                                Locked
                            </span>
                        </div>
                        <div v-if="lead.referral_attribution" class="space-y-1.5 text-xs text-slate-700 dark:text-slate-300">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Referral Partner:</span>
                                <span class="font-semibold text-slate-900 dark:text-white">
                                    {{ lead.referral_attribution.referral_partner?.name ?? 'Direct' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Referring Driver:</span>
                                <span class="font-semibold text-slate-900 dark:text-white">
                                    {{ lead.referral_attribution.driver?.name ?? 'Unknown Driver' }}
                                    <span v-if="lead.referral_attribution.driver?.phone" class="text-slate-400 font-normal">
                                        ({{ lead.referral_attribution.driver.phone }})
                                    </span>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Vehicle:</span>
                                <span class="font-semibold text-slate-900 dark:text-white">
                                    {{ lead.referral_attribution.vehicle?.plate_number ?? 'N/A' }}
                                    <span v-if="lead.referral_attribution.vehicle?.vehicle_type" class="text-slate-400 font-normal">
                                        [{{ lead.referral_attribution.vehicle.vehicle_type }}]
                                    </span>
                                </span>
                            </div>
                            <div class="flex justify-between pt-1 border-t border-blue-100 dark:border-blue-900/40">
                                <span class="text-slate-500">Referral Code:</span>
                                <span class="font-mono font-bold text-blue-700 dark:text-blue-300">
                                    {{ lead.referral_attribution.referral_code?.code ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-500 italic py-3">
                            Direct Inquiry &mdash; No referring driver or vehicle attributed to this customer.
                        </div>
                    </div>
                </div>

                <!-- Main Order Creation Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Order Line Items</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    Specify translation documents, page counts, and service charges
                                </p>
                            </div>
                            <button
                                @click="addItem"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-400 dark:hover:bg-blue-900 transition"
                            >
                                <span>+</span> Add Line Item
                            </button>
                        </div>

                        <InputError :message="form.errors.items" class="mt-2" />

                        <!-- Line Items List -->
                        <div class="space-y-3">
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-3"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase text-slate-400">Item #{{ index + 1 }}</span>
                                    <button
                                        v-if="form.items.length > 1"
                                        @click="removeItem(index)"
                                        type="button"
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400"
                                    >
                                        Remove
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                    <div class="md:col-span-6">
                                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                            Description *
                                        </label>
                                        <input
                                            v-model="item.description"
                                            type="text"
                                            required
                                            placeholder="e.g. Birth Certificate Translation (English to Khmer)"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                            Qty *
                                        </label>
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            required
                                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                            Unit Price ($) *
                                        </label>
                                        <input
                                            v-model.number="item.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        />
                                    </div>

                                    <div class="md:col-span-2 flex flex-col justify-end">
                                        <label class="block text-xs font-semibold text-slate-400 mb-1">
                                            Subtotal
                                        </label>
                                        <div class="font-mono font-bold text-sm text-slate-900 dark:text-white py-2">
                                            ${{ ((Number(item.quantity) || 0) * (Number(item.unit_price) || 0)).toFixed(2) }}
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <input
                                        v-model="item.notes"
                                        type="text"
                                        placeholder="Optional line notes (e.g. 3 copies, notarized stamps)"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Financial Calculation Summary Card -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Order Notes / Customer Instructions
                                    </label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        placeholder="Optional remarks regarding delivery deadlines, certification requirements..."
                                        class="w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    ></textarea>
                                    <InputError :message="form.errors.notes" class="mt-1" />
                                </div>

                                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-500">Subtotal:</span>
                                        <span class="font-mono font-semibold text-slate-900 dark:text-white">
                                            ${{ calculatedSubtotal.toFixed(2) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between text-sm">
                                        <label for="discount" class="text-slate-500">Discount ($):</label>
                                        <input
                                            id="discount"
                                            v-model.number="form.discount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            :max="calculatedSubtotal"
                                            class="w-28 text-right font-mono rounded-lg border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        />
                                    </div>
                                    <InputError :message="form.errors.discount" class="text-right" />

                                    <div class="flex justify-between text-base font-bold border-t border-slate-200 dark:border-slate-700 pt-3">
                                        <span class="text-slate-900 dark:text-white">Total Order Amount:</span>
                                        <span class="font-mono text-emerald-600 dark:text-emerald-400 text-lg">
                                            ${{ calculatedTotal.toFixed(2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submission Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <Link
                                :href="route('leads.show', lead.id)"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                            >
                                Cancel
                            </Link>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 disabled:opacity-50 transition"
                            >
                                <span v-if="form.processing">Creating Order...</span>
                                <span v-else>Save &amp; Generate Order</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
