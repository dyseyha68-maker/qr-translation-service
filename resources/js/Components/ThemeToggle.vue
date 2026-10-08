<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useTheme, type ThemeMode } from '@/Composables/useTheme';

const { theme, isDark, setTheme } = useTheme();
const showingDropdown = ref(false);
const toggleButtonRef = ref<HTMLElement | null>(null);

const toggleDropdown = () => {
    showingDropdown.value = !showingDropdown.value;
};

const selectTheme = (mode: ThemeMode) => {
    setTheme(mode);
    showingDropdown.value = false;
};

const handleClickOutside = (e: MouseEvent) => {
    if (toggleButtonRef.value && !toggleButtonRef.value.contains(e.target as Node)) {
        showingDropdown.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    window.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" ref="toggleButtonRef">
        <!-- Toggle Trigger Button -->
        <button
            type="button"
            @click.stop="toggleDropdown"
            class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-800 transition focus:outline-none"
            :title="`Current theme: ${theme} (Click to switch)`"
            aria-label="Theme switcher"
        >
            <!-- Sun Icon (Light Mode) -->
            <svg
                v-if="!isDark"
                class="w-5 h-5 text-amber-500 transition-transform duration-200 hover:rotate-45"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
            </svg>

            <!-- Moon Icon (Dark Mode) -->
            <svg
                v-else
                class="w-5 h-5 text-indigo-400 transition-transform duration-200 hover:-rotate-12"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="showingDropdown"
                class="absolute right-0 mt-2 w-36 rounded-2xl bg-white dark:bg-[#0E131F] shadow-xl border border-slate-200/80 dark:border-slate-800 p-1.5 z-50 text-xs font-semibold"
            >
                <!-- Light -->
                <button
                    type="button"
                    @click="selectTheme('light')"
                    :class="[
                        theme === 'light'
                            ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-white',
                        'w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl transition'
                    ]"
                >
                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                    <span>Light</span>
                    <span v-if="theme === 'light'" class="ml-auto text-amber-600">✓</span>
                </button>

                <!-- Dark -->
                <button
                    type="button"
                    @click="selectTheme('dark')"
                    :class="[
                        theme === 'dark'
                            ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-white',
                        'w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl transition'
                    ]"
                >
                    <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                    <span>Dark</span>
                    <span v-if="theme === 'dark'" class="ml-auto text-indigo-600 dark:text-indigo-400">✓</span>
                </button>

                <!-- System -->
                <button
                    type="button"
                    @click="selectTheme('system')"
                    :class="[
                        theme === 'system'
                            ? 'bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-white',
                        'w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl transition'
                    ]"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                    </svg>
                    <span>System</span>
                    <span v-if="theme === 'system'" class="ml-auto text-slate-700 dark:text-slate-300">✓</span>
                </button>
            </div>
        </transition>
    </div>
</template>
