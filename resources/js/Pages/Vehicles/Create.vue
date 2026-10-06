<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Driver } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    drivers: Driver[];
    types: string[];
    statuses: string[];
}>();

const form = useForm({
    driver_id: props.drivers.length > 0 ? props.drivers[0].id : '',
    vehicle_type: 'TUK_TUK',
    plate_number: '',
    make: '',
    model: '',
    color: '',
    notes: '',
    status: 'ACTIVE',
});

function submit() {
    form.post(route('vehicles.store'));
}
</script>

<template>
    <Head title="Register Vehicle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Register Vehicle
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Register a vehicle and assign it to an active driver.
                    </p>
                </div>
                <Link
                    :href="route('vehicles.index')"
                    class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                >
                    &larr; Back to Vehicles
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Driver Selection -->
                        <div>
                            <InputLabel for="driver_id" value="Assigned Driver *" />
                            <select
                                id="driver_id"
                                v-model="form.driver_id"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                required
                            >
                                <option value="" disabled>Select an active driver</option>
                                <option v-for="d in props.drivers" :key="d.id" :value="d.id">
                                    {{ d.name }} {{ d.referral_partner ? `(${d.referral_partner.name})` : '' }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.driver_id" />
                        </div>

                        <!-- Grid: Vehicle Type & Plate Number -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="vehicle_type" value="Vehicle Type *" />
                                <select
                                    id="vehicle_type"
                                    v-model="form.vehicle_type"
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                    required
                                >
                                    <option v-for="t in props.types" :key="t" :value="t">{{ t }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.vehicle_type" />
                            </div>

                            <div>
                                <InputLabel for="plate_number" value="Plate Number *" />
                                <TextInput
                                    id="plate_number"
                                    type="text"
                                    class="mt-1 block w-full font-mono uppercase"
                                    v-model="form.plate_number"
                                    required
                                    autofocus
                                    placeholder="e.g. PP 2BC-1234"
                                />
                                <p class="text-xs text-slate-400 mt-1">Normalized automatically (uppercase, trim spaces).</p>
                                <InputError class="mt-2" :message="form.errors.plate_number" />
                            </div>
                        </div>

                        <!-- Grid: Make, Model & Color -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <InputLabel for="make" value="Make / Brand" />
                                <TextInput
                                    id="make"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.make"
                                    placeholder="e.g. Toyota / Bajaj"
                                />
                                <InputError class="mt-2" :message="form.errors.make" />
                            </div>

                            <div>
                                <InputLabel for="model" value="Model" />
                                <TextInput
                                    id="model"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.model"
                                    placeholder="e.g. Prius / RE Maxima"
                                />
                                <InputError class="mt-2" :message="form.errors.model" />
                            </div>

                            <div>
                                <InputLabel for="color" value="Color" />
                                <TextInput
                                    id="color"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.color"
                                    placeholder="e.g. White / Yellow"
                                />
                                <InputError class="mt-2" :message="form.errors.color" />
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="status" value="Status *" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                required
                            >
                                <option v-for="s in props.statuses" :key="s" :value="s">{{ s }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>

                        <!-- Operational Notes -->
                        <div>
                            <InputLabel for="notes" value="Operational Notes" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                placeholder="Sticker / banner placement notes, condition..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <Link
                                :href="route('vehicles.index')"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </Link>

                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Register Vehicle
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
