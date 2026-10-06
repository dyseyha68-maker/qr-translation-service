<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Driver, ReferralPartner, Vehicle } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface PartnerWithHierarchy extends ReferralPartner {
    drivers?: (Driver & { vehicles?: Vehicle[] })[];
}

const props = defineProps<{
    partners: PartnerWithHierarchy[];
    suggestedCode: string;
    statuses: string[];
}>();

const form = useForm({
    referral_partner_id: props.partners.length > 0 ? props.partners[0].id : '',
    driver_id: '',
    vehicle_id: '',
    code: props.suggestedCode,
    label: '',
    notes: '',
    status: 'ACTIVE',
});

// Cascading Drivers list based on selected partner
const availableDrivers = computed(() => {
    if (!form.referral_partner_id) return [];
    const partner = props.partners.find((p) => p.id === Number(form.referral_partner_id));
    return partner?.drivers || [];
});

// Cascading Vehicles list based on selected driver
const availableVehicles = computed(() => {
    if (!form.driver_id) return [];
    const driver = availableDrivers.value.find((d) => d.id === Number(form.driver_id));
    return driver?.vehicles || [];
});

// When partner changes, reset driver and vehicle
watch(
    () => form.referral_partner_id,
    () => {
        form.driver_id = '';
        form.vehicle_id = '';
        if (availableDrivers.value.length > 0) {
            form.driver_id = String(availableDrivers.value[0].id);
        }
    },
    { immediate: true }
);

// When driver changes, reset vehicle
watch(
    () => form.driver_id,
    () => {
        form.vehicle_id = '';
        if (availableVehicles.value.length > 0) {
            form.vehicle_id = String(availableVehicles.value[0].id);
        }
    },
    { immediate: true }
);

function submit() {
    form.post(route('referral-codes.store'));
}
</script>

<template>
    <Head title="Generate Referral Code" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Generate Referral Code & QR
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Bind a unique referral code to a Partner, Driver, and Vehicle source.
                    </p>
                </div>
                <Link
                    :href="route('referral-codes.index')"
                    class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                >
                    &larr; Back to Referral Codes
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Step 1: Partner Selection -->
                        <div>
                            <InputLabel for="referral_partner_id" value="1. Referral Partner *" />
                            <select
                                id="referral_partner_id"
                                v-model="form.referral_partner_id"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                required
                            >
                                <option value="" disabled>Select an active partner</option>
                                <option v-for="p in props.partners" :key="p.id" :value="p.id">
                                    {{ p.name }} ({{ p.type }})
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.referral_partner_id" />
                        </div>

                        <!-- Step 2: Driver Selection (Cascaded) -->
                        <div>
                            <InputLabel for="driver_id" value="2. Affiliated Driver *" />
                            <select
                                id="driver_id"
                                v-model="form.driver_id"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                required
                                :disabled="!availableDrivers.length"
                            >
                                <option value="" disabled>
                                    {{ availableDrivers.length ? 'Select a driver' : 'No active drivers for this partner' }}
                                </option>
                                <option v-for="d in availableDrivers" :key="d.id" :value="d.id">
                                    {{ d.name }} {{ d.phone ? `(${d.phone})` : '' }}
                                </option>
                            </select>
                            <p v-if="!availableDrivers.length && form.referral_partner_id" class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                This partner has no registered active drivers. Please register a driver first.
                            </p>
                            <InputError class="mt-2" :message="form.errors.driver_id" />
                        </div>

                        <!-- Step 3: Vehicle Selection (Cascaded) -->
                        <div>
                            <InputLabel for="vehicle_id" value="3. Assigned Vehicle *" />
                            <select
                                id="vehicle_id"
                                v-model="form.vehicle_id"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                required
                                :disabled="!availableVehicles.length"
                            >
                                <option value="" disabled>
                                    {{ availableVehicles.length ? 'Select a vehicle' : 'No active vehicles for this driver' }}
                                </option>
                                <option v-for="v in availableVehicles" :key="v.id" :value="v.id">
                                    {{ v.plate_number }} ({{ v.vehicle_type }} {{ [v.make, v.model].filter(Boolean).join(' ') }})
                                </option>
                            </select>
                            <p v-if="!availableVehicles.length && form.driver_id" class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                This driver has no registered active vehicles. Please assign a vehicle to this driver first.
                            </p>
                            <InputError class="mt-2" :message="form.errors.vehicle_id" />
                        </div>

                        <!-- Code Identifier & Label -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="code" value="Referral Code Identifier *" />
                                <TextInput
                                    id="code"
                                    type="text"
                                    class="mt-1 block w-full font-mono font-bold uppercase tracking-wider"
                                    v-model="form.code"
                                    required
                                    placeholder="e.g. REF-TK001"
                                />
                                <p class="text-xs text-slate-400 mt-1">
                                    Pre-generated unique code. Can be customized before creation.
                                </p>
                                <InputError class="mt-2" :message="form.errors.code" />
                            </div>

                            <div>
                                <InputLabel for="label" value="Descriptive Label (e.g. Card Location)" />
                                <TextInput
                                    id="label"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.label"
                                    placeholder="e.g. Backseat Headrest Poster #1"
                                />
                                <InputError class="mt-2" :message="form.errors.label" />
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <InputLabel for="notes" value="Operational Notes" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                placeholder="Sticker distribution date, campaign info, physical batch details..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <Link
                                :href="route('referral-codes.index')"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </Link>

                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing || !availableVehicles.length }"
                                :disabled="form.processing || !availableVehicles.length"
                            >
                                Generate Code & QR
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
