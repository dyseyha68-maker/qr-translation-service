import { ref, computed } from 'vue';

export type ThemeMode = 'light' | 'dark' | 'system';

const currentTheme = ref<ThemeMode>(
    (typeof localStorage !== 'undefined' && (localStorage.getItem('theme') as ThemeMode)) || 'system'
);

const isDark = ref<boolean>(false);

const updateHtmlClass = () => {
    if (typeof window === 'undefined' || typeof document === 'undefined') return;

    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const shouldBeDark = currentTheme.value === 'dark' || (currentTheme.value === 'system' && prefersDark);

    isDark.value = shouldBeDark;
    if (shouldBeDark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

// Initialize listeners if in browser
if (typeof window !== 'undefined') {
    updateHtmlClass();

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (currentTheme.value === 'system') {
            updateHtmlClass();
        }
    });
}

export function useTheme() {
    const setTheme = (mode: ThemeMode) => {
        currentTheme.value = mode;
        if (typeof localStorage !== 'undefined') {
            if (mode === 'system') {
                localStorage.removeItem('theme');
            } else {
                localStorage.setItem('theme', mode);
            }
        }
        updateHtmlClass();
    };

    const toggleTheme = () => {
        if (isDark.value) {
            setTheme('light');
        } else {
            setTheme('dark');
        }
    };

    return {
        theme: currentTheme,
        isDark: computed(() => isDark.value),
        setTheme,
        toggleTheme,
    };
}
