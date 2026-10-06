<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Driver, Vehicle } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    vehicle: Vehicle;
    drivers: Driver[];
    types: string[];
    statuses: string[];
}>();

const form = useForm({
    driver_id: props.vehicle.driver_id,
    vehicle_type: props.vehicle.vehicle_type,
    plate_number: props.vehicle.plate_number,
    make: props.vehicle.make || '',
    model: props.vehicle.model || '',
    color: props.vehicle.color || '',
    notes: props.vehicle.notes || '',
    status: props.vehicle.status,
});

const isDriverChanged = computed(() => {
    return form.driver_id !== props.vehicle.driver_id;
});

function submit() {
    if (isDriverChanged.value) {
        if (!confirm('You are reassigning this vehicle to a different driver. Are you sure you want to proceed?')) {
            return;
        }
    }
    form.put(route('vehicles.update', props.vehicle.id));
}
</script>

<template>
    <Head :title="`Edit Vehicle ${vehicle.plate_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Edit Vehicle
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Update vehicle details or status for {{ vehicle.plate_number }}.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('vehicles.show', vehicle.id)"
                        class="text-sm font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        View Vehicle &rarr;
                    </Link>
                    <Link
                        :href="route('vehicles.index')"
                        class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                    >
                        &larr; Back to Vehicles
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Driver Assignment -->
                        <div>
                            <InputLabel for="driver_id" value="Assigned Driver *" />
                            <select
                                id="driver_id"
                                v-model="form.driver_id"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                required
                            >
                                <option v-for="d in props.drivers" :key="d.id" :value="d.id">
                                    {{ d.name }} {{ d.referral_partner ? `(${d.referral_partner.name})` : '' }}
                                </option>
                            </select>
                            <div v-if="isDriverChanged" class="mt-2 rounded-lg bg-amber-50 p-3 text-xs text-amber-800 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800">
                                <strong>Warning:</strong> You are reassigning this vehicle from its previous driver. Ensure the historical referral logs will not be affected.
                            </div>
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
                                />
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
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <Link
                                :href="route('vehicles.show', vehicle.id)"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </Link>

                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Update Vehicle
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
