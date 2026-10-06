<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ReferralPartner } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    partners: ReferralPartner[];
    statuses: string[];
}>();

const form = useForm({
    referral_partner_id: props.partners.length > 0 ? props.partners[0].id : '',
    name: '',
    phone: '',
    email: '',
    national_id: '',
    address: '',
    notes: '',
    status: 'ACTIVE',
});

function submit() {
    form.post(route('drivers.store'));
}
</script>

<template>
    <Head title="Register Driver" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Register Driver
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Enroll a new driver under an active referral partner.
                    </p>
                </div>
                <Link
                    :href="route('drivers.index')"
                    class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                >
                    &larr; Back to Drivers
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Partner Selection -->
                        <div>
                            <InputLabel for="referral_partner_id" value="Referral Partner *" />
                            <select
                                id="referral_partner_id"
                                v-model="form.referral_partner_id"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                required
                            >
                                <option value="" disabled>Select an active referral partner</option>
                                <option v-for="p in props.partners" :key="p.id" :value="p.id">
                                    {{ p.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.referral_partner_id" />
                        </div>

                        <!-- Driver Name & National ID -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="name" value="Driver Full Name *" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    placeholder="e.g. Sokha Chhim"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="national_id" value="National ID / Passport No." />
                                <TextInput
                                    id="national_id"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.national_id"
                                    placeholder="e.g. 010203040"
                                />
                                <InputError class="mt-2" :message="form.errors.national_id" />
                            </div>
                        </div>

                        <!-- Phone & Email -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="phone" value="Phone Number (Mobile / Telegram)" />
                                <TextInput
                                    id="phone"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.phone"
                                    placeholder="+855 12 345 678"
                                />
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>

                            <div>
                                <InputLabel for="email" value="Email Address" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.email"
                                    placeholder="driver@example.com"
                                />
                                <InputError class="mt-2" :message="form.errors.email" />
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

                        <!-- Address -->
                        <div>
                            <InputLabel for="address" value="Residential / Base Address" />
                            <textarea
                                id="address"
                                v-model="form.address"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                placeholder="Sangkat, Khan, Phnom Penh"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.address" />
                        </div>

                        <!-- Operational Notes -->
                        <div>
                            <InputLabel for="notes" value="Operational Notes" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                placeholder="Preferred routes, airport station, shift notes..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <Link
                                :href="route('drivers.index')"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </Link>

                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Register Driver
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
