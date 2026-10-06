<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Lead, ReferralCode, User } from '@/types';

const props = defineProps<{
    lead: Lead;
    statuses: string[];
    assignableUsers: { id: number; name: string; email: string }[];
    availableReferralCodes: ReferralCode[];
    can: {
        update: boolean;
        assign: boolean;
        addNote: boolean;
        changeAttribution: boolean;
    };
}>();

// Form: Update Status & Details
const updateForm = useForm({
    status: props.lead.status,
    title: props.lead.title,
    service_type: props.lead.service_type ?? '',
    description: props.lead.description ?? '',
});

const submitUpdate = () => {
    updateForm.patch(route('leads.update', props.lead.id), {
        preserveScroll: true,
    });
};

// Form: Assign Lead
const assignForm = useForm({
    assigned_to: props.lead.assigned_to ?? '',
});

const submitAssign = () => {
    assignForm.post(route('leads.assign', props.lead.id), {
        preserveScroll: true,
    });
};

// Form: Add Note
const noteForm = useForm({
    note: '',
});

const submitNote = () => {
    noteForm.post(route('leads.notes.store', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => {
            noteForm.reset();
        },
    });
};

// Modal & Form: Change Attribution
const showChangeAttributionModal = ref(false);
const attributionForm = useForm({
    referral_code_id: '',
    reason: '',
});

const submitChangeAttribution = () => {
    attributionForm.post(route('leads.change-attribution', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => {
            showChangeAttributionModal.value = false;
            attributionForm.reset();
        },
    });
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'NEW':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-200 dark:border-blue-800';
        case 'CONTACTED':
            return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800';
        case 'QUOTATION':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
        case 'CONFIRMED':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
        case 'LOST':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
        case 'CANCELLED':
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300';
    }
};
</script>

<template>
    <Head :title="`Lead #${lead.id} - ${lead.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('leads.index')"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400"
                        >
                            &larr; Back to Leads
                        </Link>
                    </div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
                        Lead #{{ lead.id }}: {{ lead.title }}
                    </h2>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="getStatusBadge(lead.status)" class="px-3 py-1 rounded-full text-xs font-semibold">
                        Status: {{ lead.status }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Grid: Customer & Referral Attribution Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Customer Information (1 col) -->
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Customer Profile</h3>
                            <span class="text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 font-semibold">
                                {{ lead.customer?.status ?? 'ACTIVE' }}
                            </span>
                        </div>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs font-semibold text-slate-400 uppercase">Customer Name</dt>
                                <dd class="font-medium text-slate-900 dark:text-white mt-0.5">
                                    {{ lead.customer?.name ?? 'Anonymous Customer' }}
                                </dd>
                            </div>

                            <div v-if="lead.telegram_user">
                                <dt class="text-xs font-semibold text-slate-400 uppercase">Telegram Account</dt>
                                <dd class="mt-0.5 font-medium text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <span v-if="lead.telegram_user.username" class="font-mono text-blue-600 dark:text-blue-400">
                                        @{{ lead.telegram_user.username }}
                                    </span>
                                    <span v-else>
                                        {{ lead.telegram_user.first_name }} {{ lead.telegram_user.last_name }}
                                    </span>
                                </dd>
                                <dd class="text-xs text-slate-400 font-mono mt-0.5">
                                    Telegram ID: {{ lead.telegram_user.telegram_id }}
                                </dd>
                            </div>

                            <div v-if="lead.customer?.phone">
                                <dt class="text-xs font-semibold text-slate-400 uppercase">Phone Number</dt>
                                <dd class="font-medium text-slate-900 dark:text-white mt-0.5">
                                    {{ lead.customer.phone }}
                                </dd>
                            </div>

                            <div v-if="lead.customer?.email">
                                <dt class="text-xs font-semibold text-slate-400 uppercase">Email Address</dt>
                                <dd class="font-medium text-slate-900 dark:text-white mt-0.5">
                                    {{ lead.customer.email }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold text-slate-400 uppercase">Intake Source</dt>
                                <dd class="mt-0.5 font-medium text-slate-900 dark:text-white">
                                    {{ lead.source }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Middle & Right: Referral Attribution Details (2 cols) -->
                    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Active Referral Attribution</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    Qualified driver & vehicle binding for this inquiry
                                </p>
                            </div>
                            <button
                                v-if="can.changeAttribution"
                                @click="showChangeAttributionModal = true"
                                type="button"
                                class="inline-flex items-center rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 dark:hover:bg-amber-900/80 transition-colors"
                            >
                                ✏️ Change Attribution
                            </button>
                        </div>

                        <div v-if="lead.referral_attribution" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-3.5 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                <span class="text-xs font-semibold uppercase text-slate-400 block">Referring Driver</span>
                                <span class="text-base font-bold text-slate-900 dark:text-white block mt-1">
                                    {{ lead.referral_attribution.driver?.name ?? 'Unknown Driver' }}
                                </span>
                                <span class="text-xs text-slate-500 block mt-0.5" v-if="lead.referral_attribution.driver?.phone">
                                    📞 {{ lead.referral_attribution.driver.phone }}
                                </span>
                            </div>

                            <div class="p-3.5 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                <span class="text-xs font-semibold uppercase text-slate-400 block">Vehicle & QR Code</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-sm font-mono font-bold text-blue-600 dark:text-blue-400">
                                        {{ lead.referral_attribution.referral_code?.code }}
                                    </span>
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200" v-if="lead.referral_attribution.vehicle">
                                        Plate: {{ lead.referral_attribution.vehicle.plate_number }}
                                    </span>
                                </div>
                                <span class="text-xs text-slate-500 block mt-0.5" v-if="lead.referral_attribution.referral_partner">
                                    Partner: {{ lead.referral_attribution.referral_partner.name }}
                                </span>
                            </div>

                            <div class="md:col-span-2 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                                <span>Attributed on: {{ new Date(lead.referral_attribution.attributed_at).toLocaleString() }}</span>
                                <span class="font-medium text-emerald-600 dark:text-emerald-400">Attribution Status: {{ lead.referral_attribution.attribution_status }}</span>
                            </div>
                        </div>

                        <div v-else class="mt-6 text-center py-6 text-sm text-slate-400 italic">
                            No driver referral attributed to this lead (Direct Inquiry).
                        </div>

                        <!-- Historical Attributions (if attribution was manually changed) -->
                        <div v-if="lead.attributions && lead.attributions.length > 1" class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                                Attribution Audit History
                            </h4>
                            <div class="space-y-2">
                                <div
                                    v-for="item in lead.attributions"
                                    :key="item.id"
                                    class="p-2.5 rounded bg-slate-50 dark:bg-slate-800/40 text-xs flex flex-col md:flex-row md:items-center justify-between gap-1"
                                >
                                    <div>
                                        <span class="font-bold text-slate-900 dark:text-white">{{ item.driver?.name }}</span>
                                        <span class="font-mono text-blue-600 dark:text-blue-400 ml-1">({{ item.referral_code?.code }})</span>
                                        <span class="ml-2 px-1.5 py-0.5 rounded text-[10px] font-semibold" :class="item.attribution_status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'">
                                            {{ item.attribution_status }}
                                        </span>
                                    </div>
                                    <div class="text-slate-400 text-right">
                                        <span v-if="item.change_reason" class="italic mr-2">"{{ item.change_reason }}"</span>
                                        <span>{{ new Date(item.attributed_at).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pipeline & Workflow Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Lead Pipeline Form & Assignee (1 col) -->
                    <div class="space-y-6">
                        <!-- Update Status Form -->
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800">
                                Pipeline Status & Details
                            </h3>

                            <form @submit.prevent="submitUpdate" class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Status</label>
                                    <select
                                        v-model="updateForm.status"
                                        :disabled="!can.update"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    >
                                        <option v-for="st in statuses" :key="st" :value="st">
                                            {{ st }}
                                        </option>
                                    </select>
                                    <InputError :message="updateForm.errors.status" class="mt-1" />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Service Type</label>
                                    <input
                                        v-model="updateForm.service_type"
                                        type="text"
                                        placeholder="e.g. Legal Contract, Visa, License"
                                        :disabled="!can.update"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    />
                                    <InputError :message="updateForm.errors.service_type" class="mt-1" />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Description / Inquiry Notes</label>
                                    <textarea
                                        v-model="updateForm.description"
                                        rows="3"
                                        placeholder="Customer document requirements..."
                                        :disabled="!can.update"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    ></textarea>
                                    <InputError :message="updateForm.errors.description" class="mt-1" />
                                </div>

                                <button
                                    v-if="can.update"
                                    type="submit"
                                    :disabled="updateForm.processing"
                                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50 transition-colors"
                                >
                                    {{ updateForm.processing ? 'Saving...' : 'Update Lead' }}
                                </button>
                            </form>
                        </div>

                        <!-- Assignment Form -->
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800">
                                Staff Assignment
                            </h3>

                            <form @submit.prevent="submitAssign" class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Assigned Staff</label>
                                    <select
                                        v-model="assignForm.assigned_to"
                                        :disabled="!can.assign"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    >
                                        <option value="">Unassigned</option>
                                        <option v-for="user in assignableUsers" :key="user.id" :value="user.id">
                                            {{ user.name }} ({{ user.email }})
                                        </option>
                                    </select>
                                    <InputError :message="assignForm.errors.assigned_to" class="mt-1" />
                                </div>

                                <button
                                    v-if="can.assign"
                                    type="submit"
                                    :disabled="assignForm.processing"
                                    class="w-full rounded-lg bg-slate-800 dark:bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:hover:bg-slate-600 disabled:opacity-50 transition-colors"
                                >
                                    {{ assignForm.processing ? 'Assigning...' : 'Save Assignment' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Right: Notes & Activity Timeline (2 cols) -->
                    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800">
                            Internal Notes & Audit Log
                        </h3>

                        <!-- Add Note Input -->
                        <form v-if="can.addNote" @submit.prevent="submitNote" class="mt-4">
                            <div>
                                <label class="sr-only">Add Note</label>
                                <textarea
                                    v-model="noteForm.note"
                                    rows="2"
                                    placeholder="Write an operational note (call summary, translation quote, customer preference)..."
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white placeholder-slate-400"
                                ></textarea>
                                <InputError :message="noteForm.errors.note" class="mt-1" />
                            </div>
                            <div class="mt-2 flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="noteForm.processing || !noteForm.note.trim()"
                                    class="rounded-lg bg-blue-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-blue-500 disabled:opacity-50 transition-colors"
                                >
                                    {{ noteForm.processing ? 'Posting...' : 'Post Note' }}
                                </button>
                            </div>
                        </form>

                        <!-- Notes Timeline -->
                        <div class="mt-6 space-y-4">
                            <div v-if="!lead.notes || lead.notes.length === 0" class="text-center py-8 text-sm text-slate-400">
                                No notes recorded for this lead yet.
                            </div>

                            <div
                                v-for="note in lead.notes"
                                :key="note.id"
                                class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-sm"
                            >
                                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pb-1.5 border-b border-slate-200/50 dark:border-slate-700/50">
                                    <span class="font-bold text-slate-900 dark:text-white">
                                        {{ note.user?.name ?? 'System' }}
                                    </span>
                                    <span>
                                        {{ new Date(note.created_at).toLocaleString() }}
                                    </span>
                                </div>
                                <div class="mt-2 text-slate-800 dark:text-slate-200 whitespace-pre-wrap">
                                    {{ note.note }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Change Attribution -->
        <div
            v-if="showChangeAttributionModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Change Referral Attribution
                    </h3>
                    <button
                        @click="showChangeAttributionModal = false"
                        class="text-slate-400 hover:text-slate-600 text-lg"
                    >
                        &times;
                    </button>
                </div>

                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                    Attribution history is strictly audited. You must select an active referral code and provide a mandatory business reason.
                </p>

                <form @submit.prevent="submitChangeAttribution" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">
                            New Active Referral Code
                        </label>
                        <select
                            v-model="attributionForm.referral_code_id"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        >
                            <option value="">Select Referral Code...</option>
                            <option v-for="rc in availableReferralCodes" :key="rc.id" :value="rc.id">
                                {{ rc.code }} — Driver: {{ rc.driver?.name }} ({{ rc.vehicle?.plate_number }})
                            </option>
                        </select>
                        <InputError :message="attributionForm.errors.referral_code_id" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">
                            Mandatory Change Reason
                        </label>
                        <textarea
                            v-model="attributionForm.reason"
                            rows="3"
                            placeholder="State reason (e.g. customer corrected referring driver name, wrong QR scanned)..."
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        ></textarea>
                        <InputError :message="attributionForm.errors.reason" class="mt-1" />
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="showChangeAttributionModal = false"
                            class="rounded-lg px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="attributionForm.processing || !attributionForm.referral_code_id || !attributionForm.reason.trim()"
                            class="rounded-lg bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-500 disabled:opacity-50 transition-colors"
                        >
                            {{ attributionForm.processing ? 'Changing...' : 'Confirm Change' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
