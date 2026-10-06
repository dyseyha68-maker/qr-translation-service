<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    roles: string[];
    statuses: string[];
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: props.roles.includes('STAFF') ? 'STAFF' : props.roles[0],
    status: 'ACTIVE',
});

function submit() {
    form.post(route('users.store'));
}
</script>

<template>
    <Head title="Create User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Create New User
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Register a staff member and assign their system role.
                    </p>
                </div>
                <Link
                    :href="route('users.index')"
                    class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                >
                    &larr; Back to Users
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Full Name -->
                        <div>
                            <InputLabel for="name" value="Full Name" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                                autofocus
                                placeholder="e.g. John Doe"
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Email Address -->
                        <div>
                            <InputLabel for="email" value="Email Address" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                                placeholder="name@translation.local"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <!-- Password -->
                        <div>
                            <InputLabel for="password" value="Initial Password" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password"
                                required
                                placeholder="At least 8 characters"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <!-- Grid: Role and Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Role Selection -->
                            <div>
                                <InputLabel for="role" value="Assigned Role" />
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                    required
                                >
                                    <option v-for="role in props.roles" :key="role" :value="role">
                                        {{ role }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.role" />
                            </div>

                            <!-- Status Selection -->
                            <div>
                                <InputLabel for="status" value="Account Status" />
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 text-sm"
                                    required
                                >
                                    <option v-for="status in props.statuses" :key="status" :value="status">
                                        {{ status }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <Link
                                :href="route('users.index')"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </Link>

                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Create Account
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
