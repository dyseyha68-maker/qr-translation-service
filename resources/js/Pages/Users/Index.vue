<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface UserItem {
    id: number;
    name: string;
    email: string;
    avatar_url?: string | null;
    status: 'ACTIVE' | 'INACTIVE';
    role: string;
    permissions_count: number;
    created_at: string;
    can: {
        edit: boolean;
        delete: boolean;
    };
}

const props = defineProps<{
    users: UserItem[];
    can: {
        create_user: boolean;
    };
}>();

const confirmingDeletion = ref(false);
const userToDelete = ref<UserItem | null>(null);

function confirmDelete(user: UserItem) {
    userToDelete.value = user;
    confirmingDeletion.value = true;
}

function deleteUser() {
    if (!userToDelete.value) return;

    router.delete(route('users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            confirmingDeletion.value = false;
            userToDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        User Management
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Manage system accounts, assign roles, and monitor user statuses.
                    </p>
                </div>
                <div v-if="props.can.create_user">
                    <Link
                        :href="route('users.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600/50 transition duration-150"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create User
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-semibold">User</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Role</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Created At</th>
                                    <th scope="col" class="px-6 py-4 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <tr
                                    v-for="user in props.users"
                                    :key="user.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-xl overflow-hidden shrink-0">
                                                <img
                                                    v-if="user.avatar_url"
                                                    :src="user.avatar_url"
                                                    alt="Avatar"
                                                    class="h-full w-full object-cover"
                                                />
                                                <div
                                                    v-else
                                                    class="h-full w-full bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center font-bold text-xs"
                                                >
                                                    {{ user.name.charAt(0).toUpperCase() }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-900 dark:text-white">
                                                    {{ user.name }}
                                                </div>
                                                <div class="text-xs text-slate-500">
                                                    {{ user.email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span
                                                :class="{
                                                    'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800': user.role === 'SUPER_ADMIN',
                                                    'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800': user.role === 'ADMIN',
                                                    'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800': user.role === 'FINANCE',
                                                    'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700': user.role === 'STAFF',
                                                }"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                                            >
                                                {{ user.role }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200/60 dark:border-slate-700">
                                                {{ user.permissions_count }} perms
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="{
                                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800': user.status === 'ACTIVE',
                                                'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800': user.status === 'INACTIVE',
                                            }"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                                        >
                                            <span
                                                :class="user.status === 'ACTIVE' ? 'bg-emerald-500' : 'bg-rose-500'"
                                                class="h-1.5 w-1.5 rounded-full"
                                            ></span>
                                            {{ user.status }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                        {{ user.created_at }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <Link
                                                v-if="user.can.edit"
                                                :href="route('users.edit', user.id)"
                                                class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 px-2 py-1 rounded hover:bg-blue-50 dark:hover:bg-blue-950/40"
                                            >
                                                Edit
                                            </Link>

                                            <button
                                                v-if="user.can.delete"
                                                @click="confirmDelete(user)"
                                                class="text-xs font-semibold text-rose-600 hover:text-rose-500 dark:text-rose-400 dark:hover:text-rose-300 px-2 py-1 rounded hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="confirmingDeletion"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                    Confirm Deletion
                </h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    Are you sure you want to delete the user account
                    <strong class="text-slate-900 dark:text-white">{{ userToDelete?.name }}</strong>?
                    This action cannot be undone.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="confirmingDeletion = false"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="deleteUser"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-rose-500"
                    >
                        Delete User
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
