<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const showingMobileSidebar = ref(false);
const isCollapsed = ref(false);
const searchQuery = ref('');
const searchInputRef = ref<HTMLInputElement | null>(null);

// Initialize collapse state from localStorage
onMounted(() => {
    try {
        const saved = localStorage.getItem('core_sidebar_collapsed');
        if (saved !== null) {
            isCollapsed.value = saved === 'true';
        }
    } catch {
        // LocalStorage disabled or SSR
    }

    // Keyboard shortcut: Ctrl+K or Cmd+K to focus search
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

const handleKeydown = (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        if (isCollapsed.value) {
            isCollapsed.value = false;
        }
        searchInputRef.value?.focus();
    }
};

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
    try {
        localStorage.setItem('core_sidebar_collapsed', String(isCollapsed.value));
    } catch {
        // Ignore
    }
};

const getRoleBadgeClass = (role: string) => {
    switch (role?.toUpperCase()) {
        case 'SUPER_ADMIN':
            return 'bg-purple-100 text-purple-700 dark:bg-purple-950/70 dark:text-purple-300 border-purple-200 dark:border-purple-800';
        case 'ADMIN':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border-blue-200 dark:border-blue-800';
        case 'FINANCE':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
        case 'TRANSLATION_MANAGER':
            return 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border-amber-200 dark:border-amber-800';
        default:
            return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
};

const getUserInitials = (name: string) => {
    if (!name) return 'U';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

interface NavItem {
    label: string;
    route: string;
    activePattern: string;
    permission?: boolean;
    badge?: string;
    badgeColor?: string;
    icon: string;
}

interface NavSection {
    title: string;
    items: NavItem[];
}

const navSections = computed<NavSection[]>(() => {
    const authCan = page.props.auth.can;

    const sections: NavSection[] = [
        {
            title: 'Overview',
            items: [
                {
                    label: 'Dashboard',
                    route: 'dashboard',
                    activePattern: 'dashboard',
                    icon: 'dashboard',
                },
            ],
        },
        {
            title: 'Referrals & Fleet',
            items: [
                {
                    label: 'Partners',
                    route: 'referral-partners.index',
                    activePattern: 'referral-partners.*',
                    permission: authCan.viewPartners,
                    icon: 'partners',
                },
                {
                    label: 'Drivers',
                    route: 'drivers.index',
                    activePattern: 'drivers.*',
                    permission: authCan.viewDrivers,
                    icon: 'drivers',
                },
                {
                    label: 'Vehicles',
                    route: 'vehicles.index',
                    activePattern: 'vehicles.*',
                    permission: authCan.viewVehicles,
                    icon: 'vehicles',
                },
                {
                    label: 'QR Referral Codes',
                    route: 'referral-codes.index',
                    activePattern: 'referral-codes.*',
                    permission: authCan.viewReferralCodes,
                    badge: 'Active',
                    badgeColor: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/50',
                    icon: 'qr',
                },
            ],
        },
        {
            title: 'Operations',
            items: [
                {
                    label: 'Leads Intake',
                    route: 'leads.index',
                    activePattern: 'leads.*',
                    permission: authCan.viewLeads,
                    badge: 'Live',
                    badgeColor: 'bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-200/50',
                    icon: 'leads',
                },
                {
                    label: 'Orders & Payments',
                    route: 'orders.index',
                    activePattern: 'orders.*',
                    permission: authCan.viewOrders,
                    icon: 'orders',
                },
            ],
        },
        {
            title: 'Finance & Payouts',
            items: [
                {
                    label: 'Commissions',
                    route: 'commissions.index',
                    activePattern: 'commissions.*',
                    permission: authCan.viewCommissions,
                    badge: 'Payouts',
                    badgeColor: 'bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400 border border-purple-200/50',
                    icon: 'commissions',
                },
            ],
        },
        {
            title: 'System Management',
            items: [
                {
                    label: 'Performance Reports',
                    route: 'reports.performance',
                    activePattern: 'reports.*',
                    permission: authCan.viewReports,
                    icon: 'reports',
                },
                {
                    label: 'User Accounts',
                    route: 'users.index',
                    activePattern: 'users.*',
                    permission: authCan.viewUsers,
                    icon: 'users',
                },
            ],
        },
    ];

    // Filter by permissions and search query
    const q = searchQuery.value.trim().toLowerCase();

    return sections
        .map((sec) => ({
            ...sec,
            items: sec.items.filter((item) => {
                if (item.permission !== undefined && !item.permission) return false;
                if (!q) return true;
                return item.label.toLowerCase().includes(q);
            }),
        }))
        .filter((sec) => sec.items.length > 0);
});
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC] dark:bg-[#090D16] text-slate-800 dark:text-slate-100 flex flex-col font-sans antialiased">
        <!-- ========================================================================= -->
        <!-- MOBILE SIDEBAR OVERLAY / DRAWER                                           -->
        <!-- ========================================================================= -->
        <div v-if="showingMobileSidebar" class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div
                @click="showingMobileSidebar = false"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity animate-in fade-in duration-200"
            ></div>

            <div class="fixed inset-0 flex">
                <div class="relative mr-16 flex w-full max-w-xs flex-1 animate-in slide-in-from-left duration-200">
                    <!-- Close button -->
                    <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                        <button
                            type="button"
                            @click="showingMobileSidebar = false"
                            class="-m-2.5 p-2.5 text-white/90 hover:text-white transition"
                        >
                            <span class="sr-only">Close sidebar</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Mobile Sidebar Content -->
                    <div class="flex grow flex-col gap-y-4 overflow-y-auto bg-white dark:bg-[#0E131F] px-4 pb-6 border-r border-slate-200/80 dark:border-slate-800">
                        <!-- Mobile Workspace Header -->
                        <div class="pt-5 pb-2">
                            <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-200/60 dark:border-slate-800 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-blue-600 to-sky-500 text-white flex items-center justify-center font-black shadow-md shadow-indigo-500/25 shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-bold text-slate-900 dark:text-white truncate">DIN & CO Translation</div>
                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Phnom Penh HQ</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Nav List -->
                        <nav class="flex flex-1 flex-col">
                            <div class="space-y-6 text-xs">
                                <div v-for="section in navSections" :key="section.title">
                                    <div class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2">
                                        {{ section.title }}
                                    </div>
                                    <ul role="list" class="space-y-1">
                                        <li v-for="item in section.items" :key="item.route">
                                            <Link
                                                :href="route(item.route)"
                                                @click="showingMobileSidebar = false"
                                                :class="[
                                                    route().current(item.activePattern)
                                                        ? 'bg-slate-900 text-white dark:bg-indigo-600 dark:text-white font-semibold shadow-xs'
                                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100/80 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100 font-medium',
                                                    'group flex items-center justify-between rounded-xl px-3 py-2.5 text-sm transition-all duration-150'
                                                ]"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <!-- Icon renderer -->
                                                    <span
                                                        :class="[
                                                            route().current(item.activePattern)
                                                                ? 'text-white'
                                                                : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200',
                                                            'shrink-0 transition-colors'
                                                        ]"
                                                    >
                                                        <!-- Dashboard -->
                                                        <svg v-if="item.icon === 'dashboard'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                                        </svg>
                                                        <!-- Partners -->
                                                        <svg v-else-if="item.icon === 'partners'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                        </svg>
                                                        <!-- Drivers -->
                                                        <svg v-else-if="item.icon === 'drivers'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                        <!-- Vehicles -->
                                                        <svg v-else-if="item.icon === 'vehicles'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-4-1a1 1 0 001 1h2" />
                                                        </svg>
                                                        <!-- QR -->
                                                        <svg v-else-if="item.icon === 'qr'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                                        </svg>
                                                        <!-- Leads -->
                                                        <svg v-else-if="item.icon === 'leads'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                        </svg>
                                                        <!-- Orders -->
                                                        <svg v-else-if="item.icon === 'orders'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        <!-- Commissions -->
                                                        <svg v-else-if="item.icon === 'commissions'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                        </svg>
                                                        <!-- Reports -->
                                                        <svg v-else-if="item.icon === 'reports'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                        </svg>
                                                        <!-- Users -->
                                                        <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                        </svg>
                                                    </span>
                                                    <span>{{ item.label }}</span>
                                                </div>
                                                <span v-if="item.badge" :class="[item.badgeColor || 'bg-slate-100 text-slate-600', 'px-2 py-0.5 rounded-full text-[10px] font-bold']">
                                                    {{ item.badge }}
                                                </span>
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </nav>

                        <!-- Mobile User Profile -->
                        <div class="mt-auto border-t border-slate-200/80 dark:border-slate-800 pt-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                        {{ getUserInitials($page.props.auth.user.name) }}
                                    </div>
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900 absolute -bottom-0.5 -right-0.5"></span>
                                </div>
                                <div class="truncate">
                                    <div class="text-sm font-semibold truncate">{{ $page.props.auth.user.name }}</div>
                                    <div class="text-xs text-slate-400 truncate">{{ $page.props.auth.user.role }}</div>
                                </div>
                            </div>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="p-2 text-slate-400 hover:text-rose-600 transition"
                                title="Log Out"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- DESKTOP FIXED LEFT SIDEBAR (Core 2.0 Dashboard Builder Style)              -->
        <!-- ========================================================================= -->
        <aside
            :class="[
                isCollapsed ? 'w-20' : 'w-72',
                'hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:flex-col transition-all duration-300 ease-in-out border-r border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-[#0E131F]'
            ]"
        >
            <div class="flex grow flex-col overflow-y-auto px-4 pb-4">
                <!-- TOP WORKSPACE SELECTOR PILL -->
                <div class="pt-4 pb-3">
                    <!-- Expanded Workspace Pill -->
                    <div
                        v-if="!isCollapsed"
                        class="p-2 rounded-2xl border border-slate-200/60 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-900/60 hover:bg-slate-100/90 dark:hover:bg-slate-850/90 transition-all duration-150 flex items-center justify-between group cursor-default shadow-2xs"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-blue-600 to-sky-500 text-white flex items-center justify-center font-black shadow-md shadow-indigo-500/25 shrink-0 group-hover:scale-102 transition-transform">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-sm text-slate-900 dark:text-white truncate">DIN & CO</span>
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded-md text-[9px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200/50 dark:border-indigo-800/60">
                                        PRO
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-400 font-medium truncate flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    <span class="truncate">Referral HQ • Live</span>
                                </div>
                            </div>
                        </div>

                        <!-- Selector Icon -->
                        <div class="p-1 text-slate-400 dark:text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-300 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                            </svg>
                        </div>
                    </div>

                    <!-- Collapsed Workspace Icon -->
                    <div v-else class="flex justify-center">
                        <Link
                            :href="route('dashboard')"
                            class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 via-blue-600 to-sky-500 text-white flex items-center justify-center font-black shadow-md shadow-indigo-500/25 hover:scale-105 transition-transform"
                            title="DIN & CO Translation"
                        >
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- CORE 2.0 SEARCH / QUICK JUMP PILL -->
                <div class="mb-3">
                    <div
                        v-if="!isCollapsed"
                        class="relative flex items-center rounded-xl bg-slate-100/80 dark:bg-slate-900/80 border border-slate-200/50 dark:border-slate-800/80 px-3 py-1.5 focus-within:bg-white dark:focus-within:bg-slate-850 focus-within:border-indigo-500 dark:focus-within:border-indigo-500 focus-within:shadow-xs transition"
                    >
                        <svg class="w-4 h-4 text-slate-400 shrink-0 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Quick search..."
                            class="w-full bg-transparent p-0 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 border-none focus:ring-0 focus:outline-none"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            type="button"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 ml-1"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <div v-else class="pointer-events-none flex items-center gap-0.5 pl-1.5">
                            <kbd class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-mono">⌘K</kbd>
                        </div>
                    </div>

                    <!-- Collapsed Search Button -->
                    <div v-else class="flex justify-center">
                        <button
                            @click="toggleCollapse"
                            type="button"
                            class="w-10 h-10 rounded-xl bg-slate-100/80 hover:bg-slate-200/80 dark:bg-slate-850 dark:hover:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-slate-900 dark:hover:text-white transition"
                            title="Search menu (Ctrl+K)"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- NAVIGATION SECTIONS -->
                <nav class="flex-1 space-y-5">
                    <div v-for="section in navSections" :key="section.title" class="space-y-1">
                        <!-- Section Title (only if expanded) -->
                        <div
                            v-if="!isCollapsed"
                            class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 py-1"
                        >
                            {{ section.title }}
                        </div>

                        <!-- Section Items -->
                        <ul role="list" class="space-y-1">
                            <li v-for="item in section.items" :key="item.route">
                                <Link
                                    :href="route(item.route)"
                                    :title="isCollapsed ? item.label : undefined"
                                    :class="[
                                        route().current(item.activePattern)
                                            ? 'bg-slate-900 text-white dark:bg-indigo-600 dark:text-white shadow-xs font-semibold'
                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100/90 dark:hover:bg-slate-850/80 hover:text-slate-900 dark:hover:text-white font-medium',
                                        isCollapsed
                                            ? 'justify-center px-0 h-11 w-11 mx-auto'
                                            : 'justify-between px-3.5 py-2.5 w-full',
                                        'group flex items-center rounded-2xl text-[13px] transition-all duration-150'
                                    ]"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <!-- Icon -->
                                        <span
                                            :class="[
                                                route().current(item.activePattern)
                                                    ? 'text-white'
                                                    : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-800 dark:group-hover:text-slate-200',
                                                'shrink-0 transition-colors'
                                            ]"
                                        >
                                            <!-- Dashboard -->
                                            <svg v-if="item.icon === 'dashboard'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                            </svg>
                                            <!-- Partners -->
                                            <svg v-else-if="item.icon === 'partners'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            <!-- Drivers -->
                                            <svg v-else-if="item.icon === 'drivers'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <!-- Vehicles -->
                                            <svg v-else-if="item.icon === 'vehicles'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-4-1a1 1 0 001 1h2" />
                                            </svg>
                                            <!-- QR -->
                                            <svg v-else-if="item.icon === 'qr'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                            </svg>
                                            <!-- Leads -->
                                            <svg v-else-if="item.icon === 'leads'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                            </svg>
                                            <!-- Orders -->
                                            <svg v-else-if="item.icon === 'orders'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <!-- Commissions -->
                                            <svg v-else-if="item.icon === 'commissions'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <!-- Reports -->
                                            <svg v-else-if="item.icon === 'reports'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                            <!-- Users -->
                                            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </span>

                                        <span v-if="!isCollapsed" class="truncate">{{ item.label }}</span>
                                    </div>

                                    <!-- Right Badge (only when expanded) -->
                                    <span
                                        v-if="!isCollapsed && item.badge"
                                        :class="[
                                            route().current(item.activePattern)
                                                ? 'bg-white/20 text-white'
                                                : item.badgeColor || 'bg-slate-100 text-slate-600',
                                            'px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 transition-colors'
                                        ]"
                                    >
                                        {{ item.badge }}
                                    </span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- BOTTOM CONTROLS & USER PROFILE -->
                <div class="mt-auto pt-4 border-t border-slate-200/60 dark:border-slate-800/80 space-y-3">
                    <!-- Collapse Toggle & Telegram Bot Pill (Core 2.0 Utility Row) -->
                    <div v-if="!isCollapsed" class="flex items-center justify-between px-1">
                        <!-- Telegram Bot Status Pill -->
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-850 text-slate-600 dark:text-slate-300 border border-slate-200/50 dark:border-slate-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Bot Active</span>
                        </div>

                        <!-- Collapse Button -->
                        <button
                            @click="toggleCollapse"
                            type="button"
                            class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            title="Collapse sidebar"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
                            </svg>
                        </button>
                    </div>

                    <!-- Collapsed Expand Button -->
                    <div v-else class="flex justify-center">
                        <button
                            @click="toggleCollapse"
                            type="button"
                            class="w-10 h-10 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition"
                            title="Expand sidebar"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>

                    <!-- USER PROFILE CARD (Expanded) -->
                    <div
                        v-if="!isCollapsed"
                        class="p-2.5 rounded-2xl border border-slate-200/60 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-900/60 hover:bg-slate-100/90 dark:hover:bg-slate-850/90 transition-all duration-150 flex items-center justify-between gap-2.5 shadow-2xs"
                    >
                        <Link :href="route('profile.edit')" class="flex items-center gap-2.5 min-w-0 flex-1 group">
                            <!-- Avatar with online indicator -->
                            <div class="relative shrink-0">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center font-bold text-xs shadow-xs group-hover:ring-2 group-hover:ring-indigo-400 transition">
                                    {{ getUserInitials($page.props.auth.user.name) }}
                                </div>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900 absolute -bottom-0.5 -right-0.5"></span>
                            </div>

                            <!-- Name & Role -->
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span :class="[getRoleBadgeClass($page.props.auth.user.role), 'px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider border']">
                                        {{ $page.props.auth.user.role }}
                                    </span>
                                </div>
                            </div>
                        </Link>

                        <!-- Logout Button -->
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition shrink-0"
                            title="Sign out"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                            </svg>
                        </Link>
                    </div>

                    <!-- USER PROFILE (Collapsed) -->
                    <div v-else class="flex justify-center">
                        <Link
                            :href="route('profile.edit')"
                            class="relative group"
                            :title="`${$page.props.auth.user.name} (${$page.props.auth.user.role})`"
                        >
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center font-bold text-xs shadow-xs group-hover:scale-105 transition">
                                {{ getUserInitials($page.props.auth.user.name) }}
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900 absolute -bottom-0.5 -right-0.5"></span>
                        </Link>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ========================================================================= -->
        <!-- MAIN CONTENT CONTAINER (Adjusts padding dynamically based on isCollapsed)  -->
        <!-- ========================================================================= -->
        <div
            :class="[
                isCollapsed ? 'lg:pl-20' : 'lg:pl-72',
                'flex flex-col flex-1 min-w-0 transition-all duration-300 ease-in-out'
            ]"
        >
            <!-- Mobile Top Navbar -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center justify-between border-b border-slate-200/80 dark:border-slate-800/80 bg-white/95 dark:bg-[#0E131F]/95 backdrop-blur-md px-4 sm:px-6 lg:hidden">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="showingMobileSidebar = true"
                        class="-m-2 p-2 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white"
                    >
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-indigo-600 to-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                            </svg>
                        </div>
                        <span class="font-bold tracking-tight text-slate-900 dark:text-white">
                            DIN & CO
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span :class="[getRoleBadgeClass($page.props.auth.user.role), 'px-2 py-0.5 rounded text-[10px] font-bold uppercase border']">
                        {{ $page.props.auth.user.role }}
                    </span>
                </div>
            </div>

            <!-- Page Heading Slot (Core 2.0 Minimalist Header) -->
            <header
                v-if="$slots.header"
                class="bg-white/70 dark:bg-[#0E131F]/70 backdrop-blur-md border-b border-slate-200/70 dark:border-slate-800/80 sticky top-0 z-30 hidden lg:block"
            >
                <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Heading for Mobile -->
            <header
                v-if="$slots.header"
                class="bg-white dark:bg-[#0E131F] border-b border-slate-200/70 dark:border-slate-800/80 lg:hidden"
            >
                <div class="px-4 py-3 sm:px-6">
                    <slot name="header" />
                </div>
            </header>

            <!-- Flash Alerts -->
            <div v-if="$page.props.flash.success" class="mx-auto max-w-7xl w-full px-4 sm:px-6 lg:px-8 mt-4">
                <div class="rounded-2xl bg-emerald-50/90 dark:bg-emerald-950/50 p-4 border border-emerald-200/80 dark:border-emerald-800 flex items-center justify-between text-emerald-800 dark:text-emerald-200 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center shrink-0 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold">{{ $page.props.flash.success }}</span>
                    </div>
                </div>
            </div>

            <div v-if="$page.props.flash.error" class="mx-auto max-w-7xl w-full px-4 sm:px-6 lg:px-8 mt-4">
                <div class="rounded-2xl bg-rose-50/90 dark:bg-rose-950/50 p-4 border border-rose-200/80 dark:border-rose-800 flex items-center justify-between text-rose-800 dark:text-rose-200 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-rose-100 dark:bg-rose-900/60 flex items-center justify-center shrink-0 text-rose-600 dark:text-rose-400">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold">{{ $page.props.flash.error }}</span>
                    </div>
                </div>
            </div>

            <!-- Page Main Content Slot -->
            <main class="flex-1 pb-16">
                <slot />
            </main>
        </div>
    </div>
</template>
