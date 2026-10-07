<script setup lang="ts">
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingMobileSidebar = ref(false);
const page = usePage();

const getRoleBadgeClass = (role: string) => {
    switch (role?.toUpperCase()) {
        case 'SUPER_ADMIN':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300 border-purple-200 dark:border-purple-800';
        case 'ADMIN':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border-blue-200 dark:border-blue-800';
        case 'FINANCE':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
        case 'TRANSLATION_MANAGER':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border-amber-200 dark:border-amber-800';
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
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col">
        <!-- ========================================================================= -->
        <!-- MOBILE SIDEBAR OVERLAY / DRAWER                                           -->
        <!-- ========================================================================= -->
        <div v-if="showingMobileSidebar" class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div
                @click="showingMobileSidebar = false"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            ></div>

            <div class="fixed inset-0 flex">
                <div class="relative mr-16 flex w-full max-w-xs flex-1">
                    <!-- Close button -->
                    <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                        <button
                            type="button"
                            @click="showingMobileSidebar = false"
                            class="-m-2.5 p-2.5 text-white hover:text-slate-200"
                        >
                            <span class="sr-only">Close sidebar</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Mobile Sidebar Component Content -->
                    <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white dark:bg-slate-900 px-5 pb-6 border-r border-slate-200 dark:border-slate-800">
                        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-100 dark:border-slate-800">
                            <ApplicationLogo class="block h-8 w-auto fill-current text-blue-600 dark:text-blue-400" />
                            <div>
                                <span class="font-bold tracking-tight text-base text-slate-900 dark:text-white">
                                    Translation<span class="text-blue-600 dark:text-blue-400">Admin</span>
                                </span>
                                <div class="text-[10px] text-slate-400 font-medium">QR Referral System</div>
                            </div>
                        </div>

                        <!-- Mobile Nav Navigation List -->
                        <nav class="flex flex-1 flex-col">
                            <ul role="list" class="flex flex-1 flex-col gap-y-6 text-xs">
                                <!-- MAIN -->
                                <li>
                                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Main</div>
                                    <ul role="list" class="space-y-1">
                                        <li>
                                            <Link
                                                :href="route('dashboard')"
                                                :class="[
                                                    route().current('dashboard')
                                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 font-semibold'
                                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-200 font-medium',
                                                    'group flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm leading-6 transition-colors'
                                                ]"
                                            >
                                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                                </svg>
                                                Dashboard
                                            </Link>
                                        </li>
                                    </ul>
                                </li>

                                <!-- REFERRALS & FLEET -->
                                <li v-if="$page.props.auth.can.viewPartners || $page.props.auth.can.viewDrivers || $page.props.auth.can.viewVehicles || $page.props.auth.can.viewReferralCodes">
                                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Referrals & Fleet</div>
                                    <ul role="list" class="space-y-1">
                                        <li v-if="$page.props.auth.can.viewPartners">
                                            <Link
                                                :href="route('referral-partners.index')"
                                                :class="[
                                                    route().current('referral-partners.*')
                                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 font-semibold'
                                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-200 font-medium',
                                                    'group flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm leading-6 transition-colors'
                                                ]"
                                            >
                                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                                Partners
                                            </Link>
                                        </li>
                                        <li v-if="$page.props.auth.can.viewDrivers">
                                            <Link
                                                :href="route('drivers.index')"
                                                :class="[
                                                    route().current('drivers.*')
                                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 font-semibold'
                                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-200 font-medium',
                                                    'group flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm leading-6 transition-colors'
                                                ]"
                                            >
                                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                Drivers
                                            </Link>
                                        </li>
                                        <li v-if="$page.props.auth.can.viewVehicles">
                                            <Link
                                                :href="route('vehicles.index')"
                                                :class="[
                                                    route().current('vehicles.*')
                                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 font-semibold'
                                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-200 font-medium',
                                                    'group flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm leading-6 transition-colors'
                                                ]"
                                            >
                                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-4-1a1 1 0 001 1h2" />
                                                </svg>
                                                Vehicles
                                            </Link>
                                        </li>
                                        <li v-if="$page.props.auth.can.viewReferralCodes">
                                            <Link
                                                :href="route('referral-codes.index')"
                                                :class="[
                                                    route().current('referral-codes.*')
                                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 font-semibold'
                                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-200 font-medium',
                                                    'group flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm leading-6 transition-colors'
                                                ]"
                                            >
                                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                                </svg>
                                                QR Referral Codes
                                            </Link>
                                        </li>
                                    </ul>
                                </li>

                                <!-- OPERATIONS PIPELINE -->
                                <li v-if="$page.props.auth.can.viewLeads || $page.props.auth.can.viewOrders">
                                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Operations</div>
                                    <ul role="list" class="space-y-1">
                                        <li v-if="$page.props.auth.can.viewLeads">
                                            <Link
                                                :href="route('leads.index')"
                                                :class="[
                                                    route().current('leads.*')
                                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 font-semibold'
                                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-200 font-medium',
                                                    'group flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm leading-6 transition-colors'
                                                ]"
                                            >
                                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                </svg>
                                                Leads Intake
                                            </Link>
                                        </li>
                                        <li v-if="$page.props.auth.can.viewOrders">
                                            <Link
                                                :href="route('orders.index')"
                                                :class="[
                                                    route().current('orders.*')
                                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 font-semibold'
                                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-200 font-medium',
                                                    'group flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm leading-6 transition-colors'
                                                ]"
                                            >
                                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                Orders & Payments
                                            </Link>
                                        </li>
                                    </ul>
                                </li>

                                <!-- FINANCE & COMMISSIONS -->
                                <li v-if="$page.props.auth.can.viewCommissions">
                                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Finance</div>
                                    <ul role="list" class="space-y-1">
                                        <li>
                                            <Link
                                                :href="route('commissions.index')"
                                                :class="[
                                                    route().current('commissions.*')
                                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 font-semibold'
                                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-200 font-medium',
                                                    'group flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm leading-6 transition-colors'
                                                ]"
                                            >
                                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                Commissions & Payouts
                                            </Link>
                                        </li>
                                    </ul>
                                </li>

                                <!-- REPORTS & SYSTEM -->
                                <li v-if="$page.props.auth.can.viewReports || $page.props.auth.can.viewUsers">
                                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Reports & System</div>
                                    <ul role="list" class="space-y-1">
                                        <li v-if="$page.props.auth.can.viewReports">
                                            <Link
                                                :href="route('reports.performance')"
                                                :class="[
                                                    route().current('reports.*')
                                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 font-semibold'
                                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-200 font-medium',
                                                    'group flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm leading-6 transition-colors'
                                                ]"
                                            >
                                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                </svg>
                                                Performance Reports
                                            </Link>
                                        </li>
                                        <li v-if="$page.props.auth.can.viewUsers">
                                            <Link
                                                :href="route('users.index')"
                                                :class="[
                                                    route().current('users.*')
                                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 font-semibold'
                                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-200 font-medium',
                                                    'group flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm leading-6 transition-colors'
                                                ]"
                                            >
                                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                </svg>
                                                User Accounts
                                            </Link>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </nav>

                        <!-- Mobile User Profile snippet -->
                        <div class="mt-auto border-t border-slate-200 dark:border-slate-800 pt-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs">
                                    {{ getUserInitials($page.props.auth.user.name) }}
                                </div>
                                <div class="truncate">
                                    <div class="text-sm font-semibold truncate">{{ $page.props.auth.user.name }}</div>
                                    <div class="text-xs text-slate-400 truncate">{{ $page.props.auth.user.email }}</div>
                                </div>
                            </div>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="p-2 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition"
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
        <!-- DESKTOP FIXED LEFT SIDEBAR                                                -->
        <!-- ========================================================================= -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:w-64 lg:flex-col">
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white dark:bg-slate-900 px-5 pb-4 border-r border-slate-200 dark:border-slate-800 shadow-xs">
                <!-- Brand Header -->
                <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-100 dark:border-slate-800">
                    <Link :href="route('dashboard')" class="flex items-center gap-2.5">
                        <ApplicationLogo class="block h-8 w-auto fill-current text-blue-600 dark:text-blue-400" />
                        <div>
                            <span class="font-bold tracking-tight text-lg text-slate-900 dark:text-white">
                                Translation<span class="text-blue-600 dark:text-blue-400">Admin</span>
                            </span>
                            <div class="text-[10px] text-slate-400 font-medium leading-none mt-0.5">QR Referral System</div>
                        </div>
                    </Link>
                </div>

                <!-- Role Pill -->
                <div class="px-1">
                    <span :class="[getRoleBadgeClass($page.props.auth.user.role), 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wide uppercase border']">
                        {{ $page.props.auth.user.role }}
                    </span>
                </div>

                <!-- Navigation List -->
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-5 text-xs">
                        <!-- MAIN -->
                        <li>
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-2 mb-1">Overview</div>
                            <ul role="list" class="space-y-0.5">
                                <li>
                                    <Link
                                        :href="route('dashboard')"
                                        :class="[
                                            route().current('dashboard')
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 font-semibold shadow-xs'
                                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100 font-medium',
                                            'group flex items-center gap-x-3 rounded-lg px-2.5 py-2 text-xs leading-5 transition-colors'
                                        ]"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                        Dashboard
                                    </Link>
                                </li>
                            </ul>
                        </li>

                        <!-- REFERRALS & FLEET -->
                        <li v-if="$page.props.auth.can.viewPartners || $page.props.auth.can.viewDrivers || $page.props.auth.can.viewVehicles || $page.props.auth.can.viewReferralCodes">
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-2 mb-1">Referrals & Fleet</div>
                            <ul role="list" class="space-y-0.5">
                                <li v-if="$page.props.auth.can.viewPartners">
                                    <Link
                                        :href="route('referral-partners.index')"
                                        :class="[
                                            route().current('referral-partners.*')
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 font-semibold shadow-xs'
                                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100 font-medium',
                                            'group flex items-center gap-x-3 rounded-lg px-2.5 py-2 text-xs leading-5 transition-colors'
                                        ]"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        Partners
                                    </Link>
                                </li>
                                <li v-if="$page.props.auth.can.viewDrivers">
                                    <Link
                                        :href="route('drivers.index')"
                                        :class="[
                                            route().current('drivers.*')
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 font-semibold shadow-xs'
                                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100 font-medium',
                                            'group flex items-center gap-x-3 rounded-lg px-2.5 py-2 text-xs leading-5 transition-colors'
                                        ]"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Drivers
                                    </Link>
                                </li>
                                <li v-if="$page.props.auth.can.viewVehicles">
                                    <Link
                                        :href="route('vehicles.index')"
                                        :class="[
                                            route().current('vehicles.*')
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 font-semibold shadow-xs'
                                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100 font-medium',
                                            'group flex items-center gap-x-3 rounded-lg px-2.5 py-2 text-xs leading-5 transition-colors'
                                        ]"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-4-1a1 1 0 001 1h2" />
                                        </svg>
                                        Vehicles
                                    </Link>
                                </li>
                                <li v-if="$page.props.auth.can.viewReferralCodes">
                                    <Link
                                        :href="route('referral-codes.index')"
                                        :class="[
                                            route().current('referral-codes.*')
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 font-semibold shadow-xs'
                                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100 font-medium',
                                            'group flex items-center gap-x-3 rounded-lg px-2.5 py-2 text-xs leading-5 transition-colors'
                                        ]"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                        </svg>
                                        QR Referral Codes
                                    </Link>
                                </li>
                            </ul>
                        </li>

                        <!-- OPERATIONS PIPELINE -->
                        <li v-if="$page.props.auth.can.viewLeads || $page.props.auth.can.viewOrders">
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-2 mb-1">Operations</div>
                            <ul role="list" class="space-y-0.5">
                                <li v-if="$page.props.auth.can.viewLeads">
                                    <Link
                                        :href="route('leads.index')"
                                        :class="[
                                            route().current('leads.*')
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 font-semibold shadow-xs'
                                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100 font-medium',
                                            'group flex items-center gap-x-3 rounded-lg px-2.5 py-2 text-xs leading-5 transition-colors'
                                        ]"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                        </svg>
                                        Leads Intake
                                    </Link>
                                </li>
                                <li v-if="$page.props.auth.can.viewOrders">
                                    <Link
                                        :href="route('orders.index')"
                                        :class="[
                                            route().current('orders.*')
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 font-semibold shadow-xs'
                                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100 font-medium',
                                            'group flex items-center gap-x-3 rounded-lg px-2.5 py-2 text-xs leading-5 transition-colors'
                                        ]"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Orders & Payments
                                    </Link>
                                </li>
                            </ul>
                        </li>

                        <!-- FINANCE & COMMISSIONS -->
                        <li v-if="$page.props.auth.can.viewCommissions">
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-2 mb-1">Finance</div>
                            <ul role="list" class="space-y-0.5">
                                <li>
                                    <Link
                                        :href="route('commissions.index')"
                                        :class="[
                                            route().current('commissions.*')
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 font-semibold shadow-xs'
                                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100 font-medium',
                                            'group flex items-center gap-x-3 rounded-lg px-2.5 py-2 text-xs leading-5 transition-colors'
                                        ]"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        Commissions & Payouts
                                    </Link>
                                </li>
                            </ul>
                        </li>

                        <!-- REPORTS & ADMIN -->
                        <li v-if="$page.props.auth.can.viewReports || $page.props.auth.can.viewUsers">
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-2 mb-1">Management</div>
                            <ul role="list" class="space-y-0.5">
                                <li v-if="$page.props.auth.can.viewReports">
                                    <Link
                                        :href="route('reports.performance')"
                                        :class="[
                                            route().current('reports.*')
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 font-semibold shadow-xs'
                                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100 font-medium',
                                            'group flex items-center gap-x-3 rounded-lg px-2.5 py-2 text-xs leading-5 transition-colors'
                                        ]"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        Performance Reports
                                    </Link>
                                </li>
                                <li v-if="$page.props.auth.can.viewUsers">
                                    <Link
                                        :href="route('users.index')"
                                        :class="[
                                            route().current('users.*')
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 font-semibold shadow-xs'
                                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100 font-medium',
                                            'group flex items-center gap-x-3 rounded-lg px-2.5 py-2 text-xs leading-5 transition-colors'
                                        ]"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        User Accounts
                                    </Link>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>

                <!-- Desktop Bottom User Card -->
                <div class="mt-auto border-t border-slate-100 dark:border-slate-800 pt-3">
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                        <Link :href="route('profile.edit')" class="flex items-center gap-2.5 truncate flex-1 min-w-0 group">
                            <div class="h-8 w-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs shrink-0 group-hover:ring-2 group-hover:ring-blue-400 transition">
                                {{ getUserInitials($page.props.auth.user.name) }}
                            </div>
                            <div class="truncate">
                                <div class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="text-[10px] text-slate-400 truncate">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-white dark:hover:bg-slate-800 rounded-lg transition"
                            title="Log Out"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MAIN CONTENT AREA (Offset by Left Sidebar on Desktop)                     -->
        <!-- ========================================================================= -->
        <div class="lg:pl-64 flex flex-col flex-1 min-w-0">
            <!-- Mobile Top Navbar -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 shadow-2xs sm:gap-x-6 sm:px-6 lg:hidden">
                <button
                    type="button"
                    @click="showingMobileSidebar = true"
                    class="-m-2.5 p-2.5 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white"
                >
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="flex flex-1 items-center gap-2">
                    <ApplicationLogo class="block h-7 w-auto fill-current text-blue-600 dark:text-blue-400" />
                    <span class="font-bold tracking-tight text-base text-slate-900 dark:text-white">
                        Translation<span class="text-blue-600 dark:text-blue-400">Admin</span>
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <span :class="[getRoleBadgeClass($page.props.auth.user.role), 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wide uppercase border']">
                        {{ $page.props.auth.user.role }}
                    </span>
                </div>
            </div>

            <!-- Page Heading Slot -->
            <header
                class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Flash Alerts -->
            <div v-if="$page.props.flash.success" class="mx-auto max-w-7xl w-full px-4 sm:px-6 lg:px-8 mt-4">
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950/50 p-4 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between text-emerald-800 dark:text-emerald-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
                    </div>
                </div>
            </div>

            <div v-if="$page.props.flash.error" class="mx-auto max-w-7xl w-full px-4 sm:px-6 lg:px-8 mt-4">
                <div class="rounded-lg bg-rose-50 dark:bg-rose-950/50 p-4 border border-rose-200 dark:border-rose-800 flex items-center justify-between text-rose-800 dark:text-rose-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
                    </div>
                </div>
            </div>

            <!-- Page Main Content Slot -->
            <main class="flex-1 pb-12">
                <slot />
            </main>
        </div>
    </div>
</template>
