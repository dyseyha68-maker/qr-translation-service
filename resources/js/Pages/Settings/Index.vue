<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useTheme, type ThemeMode } from '@/Composables/useTheme';

interface BrandingProp {
    brandName: string;
    themeColor: string;
    systemLogo: string | null;
}

interface ColorPalette {
    id: string;
    name: string;
    hex: string;
    class: string;
    badgeClass: string;
}

const props = defineProps<{
    branding: BrandingProp;
    colorPalettes: ColorPalette[];
    canManage: boolean;
}>();

const { theme, setTheme } = useTheme();

const logoInputRef = ref<HTMLInputElement | null>(null);
const logoPreviewUrl = ref<string | null>(props.branding.systemLogo);
const previewLoadFailed = ref(false);

const form = useForm<{
    brand_name: string;
    theme_color: string;
    logo: File | null;
}>({
    brand_name: props.branding.brandName,
    theme_color: props.branding.themeColor,
    logo: null,
});

const onFileSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo = file;
        previewLoadFailed.value = false;
        logoPreviewUrl.value = URL.createObjectURL(file);
    }
};

const triggerLogoPicker = () => {
    logoInputRef.value?.click();
};

const removeCustomLogo = () => {
    if (confirm('Are you sure you want to restore the default logo mark?')) {
        router.delete(route('settings.logo.destroy'), {
            onSuccess: () => {
                logoPreviewUrl.value = null;
                form.logo = null;
            },
        });
    }
};

const submitSettings = () => {
    form.post(route('settings.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="System Branding & Settings" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                        <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <span>Branding & Appearance</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Customize company branding, system logo, theme color palettes, and default appearance.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- APPEARANCE / DARK MODE SECTION -->
            <div class="bg-white dark:bg-[#0E131F] rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 md:p-8 shadow-xs">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 dark:border-slate-800/80 pb-5">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Display Theme</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                Instant
                            </span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Choose between Light, Dark, or System mode. Applies immediately across all views.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
                    <!-- Light Option -->
                    <button
                        type="button"
                        @click="setTheme('light')"
                        :class="[
                            theme === 'light'
                                ? 'border-amber-500 bg-amber-50/50 dark:bg-amber-950/20 ring-2 ring-amber-500/20'
                                : 'border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 hover:border-slate-300',
                            'p-4 rounded-2xl border text-left transition relative flex flex-col justify-between'
                        ]"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span class="p-2.5 rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950/80 dark:text-amber-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                </svg>
                            </span>
                            <span v-if="theme === 'light'" class="text-xs font-black text-amber-600 dark:text-amber-400">Active</span>
                        </div>
                        <div>
                            <div class="font-bold text-sm text-slate-900 dark:text-white">Light Mode</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Crisp daytime contrast</div>
                        </div>
                    </button>

                    <!-- Dark Option -->
                    <button
                        type="button"
                        @click="setTheme('dark')"
                        :class="[
                            theme === 'dark'
                                ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20 ring-2 ring-indigo-500/20'
                                : 'border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 hover:border-slate-300',
                            'p-4 rounded-2xl border text-left transition relative flex flex-col justify-between'
                        ]"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span class="p-2.5 rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950/80 dark:text-indigo-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                                </svg>
                            </span>
                            <span v-if="theme === 'dark'" class="text-xs font-black text-indigo-600 dark:text-indigo-400">Active</span>
                        </div>
                        <div>
                            <div class="font-bold text-sm text-slate-900 dark:text-white">Dark Mode</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Sleek, low-light OLED UI</div>
                        </div>
                    </button>

                    <!-- System Option -->
                    <button
                        type="button"
                        @click="setTheme('system')"
                        :class="[
                            theme === 'system'
                                ? 'border-slate-500 bg-slate-100 dark:bg-slate-850 ring-2 ring-slate-500/20'
                                : 'border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 hover:border-slate-300',
                            'p-4 rounded-2xl border text-left transition relative flex flex-col justify-between'
                        ]"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span class="p-2.5 rounded-xl bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                                </svg>
                            </span>
                            <span v-if="theme === 'system'" class="text-xs font-black text-slate-700 dark:text-slate-300">Active</span>
                        </div>
                        <div>
                            <div class="font-bold text-sm text-slate-900 dark:text-white">System Auto</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Sync with OS appearance</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- BRANDING & COLOR SETTINGS FORM -->
            <form @submit.prevent="submitSettings" class="space-y-8">
                <!-- LOGO UPLOAD & BRAND NAME -->
                <div class="bg-white dark:bg-[#0E131F] rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 md:p-8 shadow-xs space-y-6">
                    <div class="border-b border-slate-100 dark:border-slate-800/80 pb-5">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Company Brand & Logo
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Upload your organization's custom vector or raster logo to display in the sidebar and headers.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                        <!-- Logo Upload Section -->
                        <div>
                            <InputLabel value="System Logo" class="mb-2" />
                            <div class="flex items-center gap-5">
                                <!-- Logo Preview Box -->
                                <div class="w-20 h-20 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 p-2 flex items-center justify-center shrink-0 overflow-hidden shadow-2xs">
                                    <img
                                        v-if="logoPreviewUrl && !previewLoadFailed"
                                        :src="logoPreviewUrl"
                                        @error="previewLoadFailed = true"
                                        alt="Logo preview"
                                        class="max-w-full max-h-full object-contain"
                                    />
                                    <div
                                        v-else
                                        class="w-full h-full rounded-xl bg-gradient-to-tr from-indigo-600 via-blue-600 to-sky-500 text-white flex items-center justify-center font-black text-lg"
                                    >
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <input
                                        ref="logoInputRef"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                        class="hidden"
                                        @change="onFileSelected"
                                    />
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="triggerLogoPicker"
                                            :disabled="!canManage"
                                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition disabled:opacity-50"
                                        >
                                            Choose Logo
                                        </button>
                                        <button
                                            v-if="logoPreviewUrl"
                                            type="button"
                                            @click="removeCustomLogo"
                                            :disabled="!canManage"
                                            class="px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition disabled:opacity-50"
                                        >
                                            Reset Default
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-slate-400">
                                        SVG, PNG, or JPG. Recommended: square or horizontal, max 2MB.
                                    </p>
                                    <InputError :message="form.errors.logo" />
                                </div>
                            </div>
                        </div>

                        <!-- Brand Name Input -->
                        <div>
                            <InputLabel for="brand_name" value="Organization / Service Name" />
                            <TextInput
                                id="brand_name"
                                type="text"
                                v-model="form.brand_name"
                                class="mt-1.5 block w-full"
                                placeholder="e.g. DIN & CO"
                                :disabled="!canManage"
                                required
                            />
                            <p class="text-[11px] text-slate-400 mt-1">
                                Displayed in the sidebar workspace header and browser title.
                            </p>
                            <InputError class="mt-2" :message="form.errors.brand_name" />
                        </div>
                    </div>
                </div>

                <!-- THEME ACCENT COLOR SECTION -->
                <div class="bg-white dark:bg-[#0E131F] rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 md:p-8 shadow-xs space-y-6">
                    <div class="border-b border-slate-100 dark:border-slate-800/80 pb-5">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Primary Theme Accent Color
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Select the primary color accent for active links, badges, highlights, and buttons.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div
                            v-for="palette in colorPalettes"
                            :key="palette.id"
                            @click="canManage && (form.theme_color = palette.id)"
                            :class="[
                                form.theme_color === palette.id
                                    ? 'border-indigo-600 dark:border-indigo-400 ring-2 ring-indigo-500/25 bg-slate-50 dark:bg-slate-900'
                                    : 'border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-[#0E131F]',
                                'p-4 rounded-2xl border transition-all cursor-pointer relative flex flex-col justify-between h-28 group'
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <div
                                    class="w-6 h-6 rounded-full shadow-xs"
                                    :style="{ backgroundColor: palette.hex }"
                                ></div>
                                <span v-if="form.theme_color === palette.id" class="text-indigo-600 dark:text-indigo-400 font-black text-sm">
                                    ✓
                                </span>
                            </div>

                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                    {{ palette.name }}
                                </div>
                                <div class="text-[10px] font-mono text-slate-400 uppercase mt-0.5">
                                    {{ palette.hex }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <InputError :message="form.errors.theme_color" />
                </div>

                <!-- SAVE BUTTON -->
                <div v-if="canManage" class="flex items-center justify-end gap-3 pt-2">
                    <PrimaryButton
                        :disabled="form.processing"
                        :class="{ 'opacity-25': form.processing }"
                        class="px-6 py-2.5 rounded-xl font-bold text-sm shadow-md"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Branding Changes' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
