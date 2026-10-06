<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ReferralCode } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    referralCode: ReferralCode;
}>();

const form = useForm({
    label: props.referralCode.label || '',
    notes: props.referralCode.notes || '',
});

function submit() {
    form.put(route('referral-codes.update', props.referralCode.id));
}
</script>

<template>
    <Head :title="`Edit Metadata - ${referralCode.code}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Edit Referral Code Metadata
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Update descriptive notes and physical mounting labels for code {{ referralCode.code }}.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('referral-codes.show', referralCode.id)"
                        class="text-sm font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        View Code & QR &rarr;
                    </Link>
                    <Link
                        :href="route('referral-codes.index')"
                        class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                    >
                        &larr; Back to Codes
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8 space-y-6">
                <!-- Immutability Notice -->
                <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-5 dark:border-blue-900/50 dark:bg-blue-950/30">
                    <div class="flex items-start gap-3">
                        <span class="text-blue-600 dark:text-blue-400 text-lg">&infin;</span>
                        <div class="text-xs text-blue-900 dark:text-blue-200 space-y-1">
                            <p class="font-bold">Historical Attribution Immutability Notice:</p>
                            <p>
                                To preserve accurate historical referral attribution, a referral code's bound <strong>Partner</strong>, <strong>Driver</strong>, <strong>Vehicle</strong>, and <strong>Code String</strong> are immutable. If this vehicle requires reassignment or a new driver, deactivate this code and create a new one.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bound Ownership Summary Card (Read-Only) -->
                <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-800 dark:bg-slate-800/40">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Locked Ownership Binding</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-xs text-slate-400 block">Referral Partner</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">
                                {{ referralCode.referral_partner?.name || '—' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 block">Driver</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">
                                {{ referralCode.driver?.name || '—' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 block">Vehicle</span>
                            <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">
                                {{ referralCode.vehicle?.plate_number }} ({{ referralCode.vehicle?.vehicle_type }})
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Editable Metadata Form -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Code Identifier (Read-Only) -->
                        <div>
                            <InputLabel for="code" value="Referral Code (Immutable)" />
                            <input
                                id="code"
                                type="text"
                                :value="referralCode.code"
                                disabled
                                class="mt-1 block w-full font-mono font-bold uppercase bg-slate-100 text-slate-500 rounded-md border-slate-300 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 text-sm cursor-not-allowed"
                            />
                        </div>

                        <!-- Label -->
                        <div>
                            <InputLabel for="label" value="Descriptive Mounting Label" />
                            <TextInput
                                id="label"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.label"
                                placeholder="e.g. Tuk-Tuk Side Banner #2"
                            />
                            <InputError class="mt-2" :message="form.errors.label" />
                        </div>

                        <!-- Operational Notes -->
                        <div>
                            <InputLabel for="notes" value="Operational Notes" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                placeholder="Additional notes about sticker placement, dates..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <Link
                                :href="route('referral-codes.show', referralCode.id)"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </Link>

                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Save Metadata
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
