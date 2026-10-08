<script setup lang="ts">
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface PermissionItem {
    name: string;
    label: string;
}

const props = defineProps<{
    roles: string[];
    statuses: string[];
    availablePermissions: Record<string, PermissionItem[]>;
    rolePermissionsMap: Record<string, string[]>;
}>();

const avatarInputRef = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(null);

const form = useForm<{
    name: string;
    email: string;
    password: string;
    role: string;
    status: string;
    permissions: string[];
    avatar: File | null;
}>({
    name: '',
    email: '',
    password: '',
    role: props.roles.includes('STAFF') ? 'STAFF' : props.roles[0],
    status: 'ACTIVE',
    permissions: [],
    avatar: null,
});

// Auto-fill default permissions for selected role
const applyRoleDefaults = (role: string) => {
    if (props.rolePermissionsMap && props.rolePermissionsMap[role]) {
        form.permissions = [...props.rolePermissionsMap[role]];
    }
};

// Initial apply
if (form.role) {
    applyRoleDefaults(form.role);
}

// Watch role change to auto-update permissions
watch(() => form.role, (newRole) => {
    applyRoleDefaults(newRole);
});

const onAvatarSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.avatar = file;
        avatarPreview.value = URL.createObjectURL(file);
    }
};

const togglePermission = (permName: string) => {
    const index = form.permissions.indexOf(permName);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(permName);
    }
};

const toggleCategory = (items: PermissionItem[]) => {
    const allSelected = items.every((i) => form.permissions.includes(i.name));
    if (allSelected) {
        // Uncheck all in category
        form.permissions = form.permissions.filter((p) => !items.some((i) => i.name === p));
    } else {
        // Check all in category
        for (const item of items) {
            if (!form.permissions.includes(item.name)) {
                form.permissions.push(item.name);
            }
        }
    }
};

const selectAll = () => {
    const all: string[] = [];
    for (const group of Object.values(props.availablePermissions)) {
        for (const item of group) {
            all.push(item.name);
        }
    }
    form.permissions = all;
};

const deselectAll = () => {
    form.permissions = [];
};

function submit() {
    form.post(route('users.store'), {
        forceFormData: true,
    });
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
                        Register a staff member, assign their system role, and customize direct permissions.
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

        <div class="py-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 md:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- BASIC ACCOUNT DETAILS -->
                    <div class="space-y-6">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                            Account Information
                        </h3>

                        <!-- Avatar Upload -->
                        <div>
                            <InputLabel value="Profile Photo / User Logo" class="mb-2" />
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 flex items-center justify-center shrink-0 overflow-hidden">
                                    <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
                                    <span v-else class="text-slate-400 text-xs font-bold">No Photo</span>
                                </div>
                                <div class="space-y-1">
                                    <input
                                        ref="avatarInputRef"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="hidden"
                                        @change="onAvatarSelected"
                                    />
                                    <button
                                        type="button"
                                        @click="avatarInputRef?.click()"
                                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition"
                                    >
                                        Choose Avatar
                                    </button>
                                    <p class="text-[11px] text-slate-400">JPG, PNG, or WebP up to 2MB</p>
                                </div>
                            </div>
                        </div>

                        <!-- Full Name & Email Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="name" value="Full Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    placeholder="e.g. Sokha Chan"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="email" value="Email Address" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.email"
                                    required
                                    placeholder="sokha@translation.local"
                                />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>
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

                        <!-- Role and Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="role" value="Assigned Primary Role" />
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 text-sm font-medium"
                                    required
                                >
                                    <option v-for="role in props.roles" :key="role" :value="role">
                                        {{ role }}
                                    </option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Changing role automatically pre-fills default permissions below.
                                </p>
                                <InputError class="mt-2" :message="form.errors.role" />
                            </div>

                            <div>
                                <InputLabel for="status" value="Account Status" />
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 text-sm font-medium"
                                    required
                                >
                                    <option v-for="status in props.statuses" :key="status" :value="status">
                                        {{ status }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>
                    </div>

                    <!-- PERMISSION MATRIX SECTION -->
                    <div class="space-y-6 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>Granular Permissions Matrix</span>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300">
                                        {{ form.permissions.length }} active
                                    </span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Customize specific capabilities granted to this user.
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="selectAll"
                                    class="px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950/50 transition"
                                >
                                    Select All
                                </button>
                                <button
                                    type="button"
                                    @click="deselectAll"
                                    class="px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                >
                                    Deselect All
                                </button>
                            </div>
                        </div>

                        <!-- Categorized Permission Accordions / Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="(items, category) in availablePermissions"
                                :key="category"
                                class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 p-4 space-y-3"
                            >
                                <div class="flex items-center justify-between border-b border-slate-200/60 dark:border-slate-800 pb-2">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                                        {{ category }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="toggleCategory(items)"
                                        class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        {{ items.every(i => form.permissions.includes(i.name)) ? 'Deselect group' : 'Select group' }}
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <label
                                        v-for="item in items"
                                        :key="item.name"
                                        class="flex items-start gap-2.5 cursor-pointer text-xs group"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="item.name"
                                            :checked="form.permissions.includes(item.name)"
                                            @change="togglePermission(item.name)"
                                            class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900"
                                        />
                                        <span class="text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white leading-tight">
                                            {{ item.label }}
                                            <span class="block text-[10px] text-slate-400 font-mono">{{ item.name }}</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SUBMIT BUTTONS -->
                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <Link
                            :href="route('users.index')"
                            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 transition"
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
    </AuthenticatedLayout>
</template>
