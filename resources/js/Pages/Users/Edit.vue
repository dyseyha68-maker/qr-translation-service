<script setup lang="ts">
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface PermissionItem {
    name: string;
    label: string;
}

interface UserData {
    id: number;
    name: string;
    email: string;
    avatar_url: string | null;
    status: string;
    role: string;
    direct_permissions: string[];
    all_permissions: string[];
}

const props = defineProps<{
    user: UserData;
    roles: string[];
    statuses: string[];
    availablePermissions: Record<string, PermissionItem[]>;
    rolePermissionsMap: Record<string, string[]>;
}>();

const avatarInputRef = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(props.user.avatar_url);
const removeAvatarFlag = ref<boolean>(false);
const isProcessing = ref<boolean>(false);
const formErrors = ref<Record<string, string>>({});

const form = ref({
    name: props.user.name,
    email: props.user.email,
    password: '',
    role: props.user.role,
    status: props.user.status,
    permissions: [...(props.user.all_permissions || [])],
    avatar: null as File | null,
});

const onAvatarSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.value.avatar = file;
        avatarPreview.value = URL.createObjectURL(file);
        removeAvatarFlag.value = false;
    }
};

const removeAvatar = () => {
    avatarPreview.value = null;
    form.value.avatar = null;
    removeAvatarFlag.value = true;
};

const applyRoleDefaults = (role: string) => {
    if (props.rolePermissionsMap && props.rolePermissionsMap[role]) {
        form.value.permissions = [...props.rolePermissionsMap[role]];
    }
};

const togglePermission = (permName: string) => {
    const index = form.value.permissions.indexOf(permName);
    if (index > -1) {
        form.value.permissions.splice(index, 1);
    } else {
        form.value.permissions.push(permName);
    }
};

const toggleCategory = (items: PermissionItem[]) => {
    const allSelected = items.every((i) => form.value.permissions.includes(i.name));
    if (allSelected) {
        form.value.permissions = form.value.permissions.filter((p) => !items.some((i) => i.name === p));
    } else {
        for (const item of items) {
            if (!form.value.permissions.includes(item.name)) {
                form.value.permissions.push(item.name);
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
    form.value.permissions = all;
};

const deselectAll = () => {
    form.value.permissions = [];
};

function submit() {
    isProcessing.value = true;
    formErrors.value = {};

    const payload: Record<string, any> = {
        _method: 'PUT',
        name: form.value.name,
        email: form.value.email,
        role: form.value.role,
        status: form.value.status,
        permissions: form.value.permissions,
    };

    if (form.value.password) {
        payload.password = form.value.password;
    }

    if (removeAvatarFlag.value) {
        payload.remove_avatar = true;
    } else if (form.value.avatar) {
        payload.avatar = form.value.avatar;
    }

    router.post(route('users.update', props.user.id), payload as any, {
        preserveScroll: true,
        onError: (errors) => {
            formErrors.value = errors;
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Edit User - ${props.user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Edit User
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Update profile, role, status, and permissions for {{ props.user.name }}.
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
                                <div class="w-16 h-16 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 flex items-center justify-center shrink-0 overflow-hidden shadow-2xs">
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
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="avatarInputRef?.click()"
                                            class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition"
                                        >
                                            Change Photo
                                        </button>
                                        <button
                                            v-if="avatarPreview"
                                            type="button"
                                            @click="removeAvatar"
                                            class="px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-slate-400">JPG, PNG, or WebP up to 2MB</p>
                                </div>
                            </div>
                        </div>

                        <!-- Name & Email Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="name" value="Full Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                />
                                <InputError class="mt-2" :message="formErrors.name" />
                            </div>

                            <div>
                                <InputLabel for="email" value="Email Address" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.email"
                                    required
                                />
                                <InputError class="mt-2" :message="formErrors.email" />
                            </div>
                        </div>

                        <!-- Password (Optional) -->
                        <div>
                            <InputLabel for="password" value="New Password (leave blank to keep current)" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password"
                                placeholder="Enter only if changing password"
                            />
                            <InputError class="mt-2" :message="formErrors.password" />
                        </div>

                        <!-- Role and Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="flex items-center justify-between">
                                    <InputLabel for="role" value="Assigned Primary Role" />
                                    <button
                                        type="button"
                                        @click="applyRoleDefaults(form.role)"
                                        class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        Reset to role defaults
                                    </button>
                                </div>
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
                                <InputError class="mt-2" :message="formErrors.role" />
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
                                <InputError class="mt-2" :message="formErrors.status" />
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

                        <!-- Categorized Permission Cards -->
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

                        <PrimaryButton :class="{ 'opacity-25': isProcessing }" :disabled="isProcessing">
                            {{ isProcessing ? 'Saving...' : 'Save Changes' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
