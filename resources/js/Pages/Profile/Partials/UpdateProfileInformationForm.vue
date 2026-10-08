<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = usePage().props.auth.user;

const avatarInputRef = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(user.avatar_url || null);

const form = useForm<{
    name: string;
    email: string;
    avatar: File | null;
}>({
    name: user.name,
    email: user.email,
    avatar: null,
});

const onAvatarSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.avatar = file;
        avatarPreview.value = URL.createObjectURL(file);
    }
};

const triggerAvatarPicker = () => {
    avatarInputRef.value?.click();
};

const removeAvatar = () => {
    if (confirm('Are you sure you want to remove your profile photo?')) {
        router.delete(route('profile.avatar.destroy'), {
            preserveScroll: true,
            onSuccess: () => {
                avatarPreview.value = null;
                form.avatar = null;
            },
        });
    }
};

const getUserInitials = (name: string) => {
    if (!name) return 'U';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const submitForm = () => {
    if (form.avatar) {
        // Send multipart POST with _method PATCH for PHP multipart support
        router.post(route('profile.update'), {
            _method: 'PATCH',
            name: form.name,
            email: form.email,
            avatar: form.avatar,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('avatar');
            },
        });
    } else {
        form.patch(route('profile.update'), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                Profile Information & Avatar
            </h2>

            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Update your account's profile photo, name, and email address.
            </p>
        </header>

        <form @submit.prevent="submitForm" class="mt-6 space-y-6">
            <!-- Avatar Upload Section -->
            <div>
                <InputLabel value="Profile Photo / User Logo" class="mb-2" />
                <div class="flex items-center gap-5">
                    <!-- Avatar Preview -->
                    <div class="relative shrink-0">
                        <img
                            v-if="avatarPreview"
                            :src="avatarPreview"
                            @error="avatarPreview = null"
                            alt="Avatar"
                            class="w-16 h-16 rounded-2xl object-cover ring-2 ring-indigo-500/30 shadow-xs"
                        />
                        <div
                            v-else
                            class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center font-bold text-lg shadow-xs"
                        >
                            {{ getUserInitials(form.name) }}
                        </div>
                    </div>

                    <div class="space-y-1.5">
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
                                @click="triggerAvatarPicker"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-900 dark:bg-indigo-600 hover:bg-slate-800 text-white transition shadow-xs"
                            >
                                Upload Photo
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
                        <p class="text-[11px] text-slate-400">
                            Square image recommended (JPG, PNG, or WebP up to 2MB).
                        </p>
                        <InputError class="mt-1" :message="form.errors.avatar" />
                    </div>
                </div>
            </div>

            <!-- Name Input -->
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- Email Input -->
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-slate-800 dark:text-slate-200">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-slate-600 underline hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-slate-400 dark:hover:text-slate-100 dark:focus:ring-offset-slate-800"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600 dark:text-green-400"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save Profile</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-slate-600 dark:text-slate-400"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
