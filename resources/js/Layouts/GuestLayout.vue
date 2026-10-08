<script setup lang="ts">
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

const logoError = ref(false);
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-900"
    >
        <div>
            <Link href="/" class="flex flex-col items-center gap-2 group">
                <img
                    v-if="$page.props.branding?.systemLogo && !logoError"
                    :src="$page.props.branding.systemLogo"
                    @error="logoError = true"
                    :alt="$page.props.branding?.brandName || 'Brand Logo'"
                    class="h-16 w-16 object-contain rounded-2xl bg-white dark:bg-slate-800 p-2 border border-slate-200/80 dark:border-slate-700/80 shadow-sm"
                />
                <ApplicationLogo v-else class="h-20 w-20 fill-current text-gray-500" />
                <span
                    v-if="$page.props.branding?.brandName"
                    class="text-sm font-bold tracking-tight text-slate-700 dark:text-slate-300"
                >
                    {{ $page.props.branding.brandName }}
                </span>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg dark:bg-gray-800"
        >
            <slot />
        </div>
    </div>
</template>
