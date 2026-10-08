<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const page = usePage();
const showingMobileSidebar = ref(false);
const isHovered = ref(false);
let hoverLeaveTimeout: ReturnType<typeof setTimeout> | null = null;

const onSidebarMouseEnter = () => {
    if (hoverLeaveTimeout) {
        clearTimeout(hoverLeaveTimeout);
        hoverLeaveTimeout = null;
    }
    isHovered.value = true;
};

const onSidebarMouseLeave = () => {
    if (hoverLeaveTimeout) {
        clearTimeout(hoverLeaveTimeout);
    }
    hoverLeaveTimeout = setTimeout(() => {
        isHovered.value = false;
        hoverLeaveTimeout = null;
    }, 150);
};

// QR Scan In-App Notifications State
export interface ScanNotification {
    id: number;
    event_type: string;
    is_repeat: boolean;
    type_badge: string;
    customer_name: string;
    customer_username?: string | null;
    driver_name: string;
    driver_phone?: string | null;
    vehicle_plate: string;
    vehicle_type: string;
    referral_code: string;
    partner_name?: string | null;
    lead_id?: number | null;
    lead_url?: string | null;
    occurred_at: string;
    time_ago: string;
}

const recentScans = ref<ScanNotification[]>([]);
const unreadScanCount = ref<number>(0);
const lastSeenScanId = ref<number>(Number(page.props.notifications?.latestScanId || 0));
const showingScanDrawer = ref(false);
const activeToastBanners = ref<ScanNotification[]>([]);
let scanPollTimer: ReturnType<typeof setInterval> | null = null;

// Gentle Web Audio API chime on new scan
const playNotificationSound = () => {
    try {
        const AudioCtx = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
        osc.frequency.setValueAtTime(880, ctx.currentTime + 0.08); // A5
        gain.gain.setValueAtTime(0.12, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.35);
    } catch {
        // AudioContext blocked by browser autoplay policy
    }
};

const dismissBanner = (id: number) => {
    activeToastBanners.value = activeToastBanners.value.filter((b) => b.id !== id);
};

const markAllRead = () => {
    unreadScanCount.value = 0;
};

const fetchScanNotifications = async (initial = false) => {
    try {
        const params: Record<string, unknown> = {};
        if (!initial && lastSeenScanId.value > 0) {
            params.since_id = lastSeenScanId.value;
        }

        const response = await axios.get(route('notifications.scans'), { params });
        const data = response.data;

        if (initial) {
            if (Array.isArray(data.scans)) {
                recentScans.value = data.scans;
                if (data.latest_id) {
                    lastSeenScanId.value = Number(data.latest_id);
                }
            }
        } else if (data.has_new && Array.isArray(data.new_scans) && data.new_scans.length > 0) {
            const newItems: ScanNotification[] = data.new_scans;

            // Prepend new items without duplicates
            for (const item of newItems) {
                if (!recentScans.value.some((s) => s.id === item.id)) {
                    recentScans.value.unshift(item);
                }

                // Add to floating toast banners
                if (!activeToastBanners.value.some((b) => b.id === item.id)) {
                    activeToastBanners.value.unshift(item);

                    // Auto dismiss after 10s
                    setTimeout(() => {
                        dismissBanner(item.id);
                    }, 10000);
                }
            }

            unreadScanCount.value += newItems.length;
            if (data.latest_id) {
                lastSeenScanId.value = Math.max(lastSeenScanId.value, Number(data.latest_id));
            }

            playNotificationSound();
        }
    } catch {
        // Network failure / guest session expired
    }
};

// Lifecycle: setup polling & listeners
onMounted(() => {
    // Initial scan fetch
    fetchScanNotifications(true);

    // Polling loop every 8 seconds
    scanPollTimer = setInterval(() => {
        if (document.visibilityState === 'visible') {
            fetchScanNotifications(false);
        }
    }, 8000);

    // Visibility change handler: poll immediately when user returns to tab
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
    if (hoverLeaveTimeout) {
        clearTimeout(hoverLeaveTimeout);
        hoverLeaveTimeout = null;
    }
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    if (scanPollTimer) {
        clearInterval(scanPollTimer);
        scanPollTimer = null;
    }
});

const handleVisibilityChange = () => {
    if (document.visibilityState === 'visible') {
        fetchScanNotifications(false);
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
                {
                    label: 'Branding & Settings',
                    route: 'settings.index',
                    activePattern: 'settings.*',
                    permission: authCan.manageSettings,
                    icon: 'settings',
                },
            ],
        },
    ];

    return sections
        .map((sec) => ({
            ...sec,
            items: sec.items.filter((item) => item.permission === undefined || item.permission),
        }))
        .filter((sec) => sec.items.length > 0);
});
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC] dark:bg-[#090D16] text-slate-800 dark:text-slate-100 flex flex-col font-sans antialiased relative">
        <!-- ========================================================================= -->
        <!-- FLOATING REAL-TIME SCAN NOTIFICATION TOAST BANNERS                        -->
        <!-- ========================================================================= -->
        <div class="fixed top-5 right-5 z-50 flex flex-col gap-3 max-w-sm sm:max-w-md w-full pointer-events-none">
            <div
                v-for="banner in activeToastBanners"
                :key="banner.id"
                class="pointer-events-auto bg-white/95 dark:bg-[#0E131F]/95 backdrop-blur-md rounded-2xl border-2 border-indigo-500/80 dark:border-indigo-500 p-4 shadow-2xl transition-all animate-in slide-in-from-top-3 duration-300 ring-4 ring-indigo-500/10"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-amber-500 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-sm shrink-0">
                            ⚡
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black tracking-wide uppercase text-indigo-600 dark:text-indigo-400">
                                    {{ banner.type_badge }} Detected!
                                </span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            </div>
                            <div class="text-[11px] text-slate-400 font-medium">Just now via QR Scan</div>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="dismissBanner(banner.id)"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                        title="Dismiss"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-3 text-xs text-slate-700 dark:text-slate-200 space-y-1 bg-slate-50 dark:bg-slate-850 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Customer:</span>
                        <span class="font-bold text-slate-900 dark:text-white truncate max-w-[200px]">
                            {{ banner.customer_name }}
                            <span v-if="banner.customer_username" class="text-indigo-500 font-normal">(@{{ banner.customer_username }})</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Attributed Driver:</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">
                            {{ banner.driver_name }} ({{ banner.vehicle_plate }})
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Referral Code:</span>
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">
                            {{ banner.referral_code }}
                        </span>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="dismissBanner(banner.id)"
                        class="px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 transition"
                    >
                        Dismiss
                    </button>
                    <Link
                        v-if="banner.lead_url"
                        :href="banner.lead_url"
                        @click="dismissBanner(banner.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition"
                    >
                        <span>View Lead #{{ banner.lead_id }}</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SCAN ACTIVITY SLIDE-OVER DRAWER (Activity History)                        -->
        <!-- ========================================================================= -->
        <div v-if="showingScanDrawer" class="relative z-50" role="dialog" aria-modal="true">
            <div
                @click="showingScanDrawer = false"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity animate-in fade-in duration-200"
            ></div>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <div class="pointer-events-auto w-screen max-w-md animate-in slide-in-from-right duration-200 bg-white dark:bg-[#0E131F] shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800">
                            <!-- Drawer Header -->
                            <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                            <span>QR Scan Activity</span>
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        </h3>
                                        <p class="text-xs text-slate-400">Real-time driver referral detections</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="unreadScanCount > 0"
                                        type="button"
                                        @click="markAllRead"
                                        class="px-2 py-1 text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 rounded-lg transition"
                                    >
                                        Mark read
                                    </button>
                                    <button
                                        type="button"
                                        @click="showingScanDrawer = false"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                                    >
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Drawer Body: List of scans -->
                            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                                <div v-if="recentScans.length === 0" class="text-center py-16 text-slate-400 text-xs">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                        </svg>
                                    </div>
                                    <div class="font-semibold text-slate-600 dark:text-slate-300">No scan activity yet</div>
                                    <div class="text-[11px] text-slate-400 mt-1">Scans via driver QR codes will appear here in real-time.</div>
                                </div>

                                <div
                                    v-for="scan in recentScans"
                                    :key="scan.id"
                                    class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 hover:border-indigo-400/50 dark:hover:border-indigo-500/50 transition-all space-y-2.5"
                                >
                                    <div class="flex items-center justify-between text-xs">
                                        <span
                                            :class="[
                                                scan.is_repeat
                                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border-amber-200'
                                                    : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-200',
                                                'px-2 py-0.5 rounded-full font-bold text-[10px] border'
                                            ]"
                                        >
                                            {{ scan.type_badge }}
                                        </span>
                                        <span class="text-[11px] text-slate-400">{{ scan.time_ago }}</span>
                                    </div>

                                    <div>
                                        <div class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                            {{ scan.customer_name }}
                                            <span v-if="scan.customer_username" class="text-indigo-500 font-normal">(@{{ scan.customer_username }})</span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                            Driver: <span class="font-medium text-slate-800 dark:text-slate-200">{{ scan.driver_name }}</span>
                                            <span class="text-slate-400"> ({{ scan.vehicle_plate }})</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 dark:border-slate-800/80 text-[11px]">
                                        <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ scan.referral_code }}
                                        </div>
                                        <Link
                                            v-if="scan.lead_url"
                                            :href="scan.lead_url"
                                            @click="showingScanDrawer = false"
                                            class="font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1"
                                        >
                                            <span>View Lead</span>
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Mobile Sidebar Content -->
                    <div class="flex grow flex-col gap-y-4 overflow-y-auto bg-white dark:bg-[#0E131F] px-4 pb-6 border-r border-slate-200/80 dark:border-slate-800">
                        <!-- Mobile Workspace Header -->
                        <div class="pt-5 pb-2">
                            <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-200/60 dark:border-slate-800 flex items-center gap-3">
                                <img
                                    v-if="$page.props.branding?.systemLogo"
                                    :src="$page.props.branding.systemLogo"
                                    alt="Logo"
                                    class="w-10 h-10 rounded-xl object-contain bg-white dark:bg-slate-900 p-1 border border-slate-200/60 dark:border-slate-800 shadow-xs shrink-0"
                                />
                                <div v-else class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-blue-600 to-sky-500 text-white flex items-center justify-center font-black shadow-md shadow-indigo-500/25 shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1 flex items-center gap-1.5">
                                    <span class="text-sm font-bold text-slate-900 dark:text-white truncate">
                                        {{ $page.props.branding?.brandName || 'DIN & CO' }}
                                    </span>
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded-md text-[9px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200/50 dark:border-indigo-800/60">
                                        PRO
                                    </span>
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
                                                        <svg v-else-if="item.icon === 'users'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                        </svg>
                                                        <!-- Settings -->
                                                        <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
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
                                    <img
                                        v-if="$page.props.auth.user.avatar_url"
                                        :src="$page.props.auth.user.avatar_url"
                                        alt="Avatar"
                                        class="w-9 h-9 rounded-xl object-cover ring-1 ring-slate-200 dark:ring-slate-700 shadow-xs"
                                    />
                                    <div
                                        v-else
                                        class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center font-bold text-xs shadow-xs"
                                    >
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
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- ========================================================================= -->
        <!-- DESKTOP FIXED LEFT SIDEBAR (Buttery-Smooth Auto Collapse/Expand on Hover) -->
        <!-- ========================================================================= -->
        <aside
            @mouseenter="onSidebarMouseEnter"
            @mouseleave="onSidebarMouseLeave"
            :class="[
                isHovered ? 'w-72 shadow-2xl' : 'w-20 shadow-xs',
                'hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:flex lg:flex-col transition-[width,box-shadow] duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] will-change-[width] border-r border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-[#0E131F] overflow-x-hidden select-none'
            ]"
        >
            <div class="flex grow flex-col overflow-y-auto overflow-x-hidden px-2.5 pb-4">
                <!-- TOP WORKSPACE SELECTOR (Stationary icon at x=40px, smooth label fade) -->
                <div class="pt-4 pb-2">
                    <Link
                        :href="route('dashboard')"
                        class="flex items-center h-12 px-2 rounded-2xl border border-slate-200/60 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-900/60 hover:bg-slate-100 dark:hover:bg-slate-850 transition-colors duration-150 w-full overflow-hidden shadow-2xs group"
                        :title="$page.props.branding?.brandName || 'DIN & CO Translation'"
                    >
                        <!-- Stationary App Icon (w-11 in 60px slot = centered at 40px) -->
                        <div class="w-11 h-11 flex items-center justify-center shrink-0">
                            <img
                                v-if="$page.props.branding?.systemLogo"
                                :src="$page.props.branding.systemLogo"
                                alt="Logo"
                                class="w-9 h-9 rounded-xl object-contain bg-white dark:bg-slate-900 p-0.5 border border-slate-200/60 dark:border-slate-800 shadow-xs shrink-0"
                            />
                            <div v-else class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 via-blue-600 to-sky-500 text-white flex items-center justify-center font-black shadow-md shadow-indigo-500/25 shrink-0 group-hover:scale-102 transition-transform">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                </svg>
                            </div>
                        </div>

                        <!-- Brand Label: Smooth Fade & Slide -->
                        <div
                            class="flex items-center gap-1.5 min-w-0 ml-1.5 transition-all duration-200 ease-out whitespace-nowrap overflow-hidden"
                            :class="[
                                isHovered
                                    ? 'opacity-100 translate-x-0'
                                    : 'opacity-0 -translate-x-2 pointer-events-none'
                            ]"
                        >
                            <span class="font-bold text-sm text-slate-900 dark:text-white truncate">
                                {{ $page.props.branding?.brandName || 'DIN & CO' }}
                            </span>
                            <span class="inline-flex items-center px-1.5 py-0.2 rounded-md text-[9px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200/50 dark:border-indigo-800/60">
                                PRO
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- NAVIGATION SECTIONS -->
                <nav class="flex-1 space-y-3">
                    <div v-for="section in navSections" :key="section.title" class="space-y-1">
                        <!-- Section Header: Strictly constant height h-6 (zero vertical jump) -->
                        <div class="h-6 flex items-center px-2 relative my-1">
                            <!-- Collapsed: subtle divider bar centered in the rail -->
                            <div
                                class="absolute inset-0 flex items-center justify-center transition-opacity duration-200"
                                :class="isHovered ? 'opacity-0 pointer-events-none' : 'opacity-100'"
                            >
                                <span class="w-5 h-px bg-slate-200/80 dark:bg-slate-800"></span>
                            </div>

                            <!-- Expanded: Section Title text -->
                            <div
                                class="transition-all duration-200 ease-out whitespace-nowrap overflow-hidden text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider"
                                :class="isHovered ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-2 pointer-events-none'"
                            >
                                {{ section.title }}
                            </div>
                        </div>

                        <!-- Nav Items -->
                        <ul role="list" class="space-y-1">
                            <li v-for="item in section.items" :key="item.route">
                                <Link
                                    :href="route(item.route)"
                                    :class="[
                                        route().current(item.activePattern)
                                            ? 'bg-slate-900 text-white dark:bg-indigo-600 dark:text-white shadow-xs font-semibold'
                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100/90 dark:hover:bg-slate-850/80 hover:text-slate-900 dark:hover:text-white font-medium',
                                        'group flex items-center rounded-2xl text-[13px] h-11 px-2 w-full transition-colors duration-150 relative overflow-hidden'
                                    ]"
                                >
                                    <!-- Stationary Icon Container (Centered horizontally at 40px) -->
                                    <div class="w-11 h-11 flex items-center justify-center shrink-0">
                                        <span
                                            :class="[
                                                route().current(item.activePattern)
                                                    ? 'text-white'
                                                    : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-800 dark:group-hover:text-slate-200',
                                                'transition-colors shrink-0'
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
                                            <svg v-else-if="item.icon === 'users'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            <!-- Settings -->
                                            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </span>
                                    </div>

                                    <!-- Label & Badge: Smooth Fade & Slide (flex-1 with zero max-w animation) -->
                                    <div
                                        class="flex items-center justify-between flex-1 min-w-0 pr-1 transition-all duration-200 ease-out whitespace-nowrap overflow-hidden"
                                        :class="[
                                            isHovered
                                                ? 'opacity-100 translate-x-0'
                                                : 'opacity-0 -translate-x-2 pointer-events-none'
                                        ]"
                                    >
                                        <span class="truncate font-semibold text-[13px]">{{ item.label }}</span>
                                        <span
                                            v-if="item.badge"
                                            :class="[
                                                route().current(item.activePattern)
                                                    ? 'bg-white/20 text-white'
                                                    : item.badgeColor || 'bg-slate-100 text-slate-600',
                                                'px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 ml-2'
                                            ]"
                                        >
                                            {{ item.badge }}
                                        </span>
                                    </div>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- BOTTOM USER PROFILE (Stationary avatar at x=40px, smooth user info fade) -->
                <div class="mt-auto pt-3 border-t border-slate-200/60 dark:border-slate-800/80">
                    <div class="p-1.5 rounded-2xl border border-slate-200/60 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-900/60 flex items-center justify-between transition-colors duration-150 w-full overflow-hidden shadow-2xs">
                        <Link
                            :href="route('profile.edit')"
                            class="flex items-center min-w-0 flex-1 group"
                        >
                            <!-- Stationary Avatar Container (Centered at 40px) -->
                            <div class="w-11 h-11 flex items-center justify-center shrink-0">
                                <div class="relative">
                                    <img
                                        v-if="$page.props.auth.user.avatar_url"
                                        :src="$page.props.auth.user.avatar_url"
                                        alt="Avatar"
                                        class="w-9 h-9 rounded-xl object-cover ring-1 ring-slate-200 dark:ring-slate-700 shadow-xs"
                                    />
                                    <div
                                        v-else
                                        class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center font-bold text-xs shadow-xs group-hover:ring-2 group-hover:ring-indigo-400 transition"
                                    >
                                        {{ getUserInitials($page.props.auth.user.name) }}
                                    </div>
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900 absolute -bottom-0.5 -right-0.5"></span>
                                </div>
                            </div>

                            <!-- User Info: Smooth Fade & Slide -->
                            <div
                                class="min-w-0 ml-1.5 transition-all duration-200 ease-out whitespace-nowrap overflow-hidden"
                                :class="[
                                    isHovered
                                        ? 'opacity-100 translate-x-0'
                                        : 'opacity-0 -translate-x-2 pointer-events-none'
                                ]"
                            >
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

                        <!-- Logout Button: Smooth Fade & Slide -->
                        <div
                            class="transition-all duration-200 ease-out shrink-0 overflow-hidden"
                            :class="[
                                isHovered
                                    ? 'opacity-100 translate-x-0 ml-1'
                                    : 'opacity-0 translate-x-2 pointer-events-none'
                            ]"
                        >
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition shrink-0 block"
                                title="Sign out"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ========================================================================= -->
        <!-- MAIN CONTENT CONTAINER (Offset by collapsed rail w-20 on desktop)         -->
        <!-- ========================================================================= -->
        <div class="lg:pl-20 flex flex-col flex-1 min-w-0">
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
                        <img
                            v-if="$page.props.branding?.systemLogo"
                            :src="$page.props.branding.systemLogo"
                            alt="Logo"
                            class="w-7 h-7 rounded-lg object-contain bg-white dark:bg-slate-900 p-0.5 border border-slate-200/60 dark:border-slate-800 shadow-xs"
                        />
                        <div v-else class="w-7 h-7 rounded-lg bg-gradient-to-tr from-indigo-600 to-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                            </svg>
                        </div>
                        <span class="font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ $page.props.branding?.brandName || 'DIN & CO' }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Dark Mode Toggle -->
                    <ThemeToggle />

                    <!-- Notification Bell (Mobile) -->
                    <button
                        type="button"
                        @click="showingScanDrawer = true"
                        class="relative p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        title="QR Scan Activity"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span v-if="unreadScanCount > 0" class="absolute top-1.5 right-1.5 flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                        </span>
                    </button>

                    <span :class="[getRoleBadgeClass($page.props.auth.user.role), 'px-2 py-0.5 rounded text-[10px] font-bold uppercase border']">
                        {{ $page.props.auth.user.role }}
                    </span>
                </div>
            </div>

            <!-- Page Heading Slot with Scan Bell Trigger & Theme Toggle -->
            <header
                v-if="$slots.header"
                class="bg-white/70 dark:bg-[#0E131F]/70 backdrop-blur-md border-b border-slate-200/70 dark:border-slate-800/80 sticky top-0 z-30 hidden lg:block"
            >
                <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8 flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <slot name="header" />
                    </div>

                    <!-- Top Header Actions: Theme Toggle & QR Scan Bell -->
                    <div class="flex items-center gap-3 shrink-0 ml-4">
                        <ThemeToggle />

                        <button
                            type="button"
                            @click="showingScanDrawer = true"
                            class="relative flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100/90 hover:bg-slate-200/80 dark:bg-slate-850 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/60 dark:border-slate-800 transition shadow-2xs group"
                            title="QR Referral Scans"
                        >
                            <svg class="w-4 h-4 text-slate-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            <span class="text-xs font-semibold">Scans</span>
                            <span
                                v-if="unreadScanCount > 0"
                                class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-rose-500 text-white animate-bounce"
                            >
                                {{ unreadScanCount }}
                            </span>
                            <span v-else class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </button>
                    </div>
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
