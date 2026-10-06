<script setup lang="ts">
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();
</script>

<template>
    <div>
        <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100">
            <nav class="border-b border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between items-center">
                        <div class="flex items-center">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="flex items-center gap-2">
                                    <ApplicationLogo class="block h-8 w-auto fill-current text-blue-600 dark:text-blue-400" />
                                    <span class="font-bold tracking-tight text-lg text-slate-900 dark:text-white">
                                        Translation<span class="text-blue-600 dark:text-blue-400">Admin</span>
                                    </span>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Dashboard
                                </NavLink>

                                <!-- Phase 2 Navigation: Referral Partners -->
                                <NavLink
                                    v-if="$page.props.auth.can.viewPartners"
                                    :href="route('referral-partners.index')"
                                    :active="route().current('referral-partners.*')"
                                >
                                    Partners
                                </NavLink>

                                <!-- Phase 2 Navigation: Drivers -->
                                <NavLink
                                    v-if="$page.props.auth.can.viewDrivers"
                                    :href="route('drivers.index')"
                                    :active="route().current('drivers.*')"
                                >
                                    Drivers
                                </NavLink>

                                <!-- Phase 2 Navigation: Vehicles -->
                                <NavLink
                                    v-if="$page.props.auth.can.viewVehicles"
                                    :href="route('vehicles.index')"
                                    :active="route().current('vehicles.*')"
                                >
                                    Vehicles
                                </NavLink>

                                <!-- Phase 3 Navigation: Referral Codes -->
                                <NavLink
                                    v-if="$page.props.auth.can.viewReferralCodes"
                                    :href="route('referral-codes.index')"
                                    :active="route().current('referral-codes.*')"
                                >
                                    Referral Codes
                                </NavLink>

                                <!-- Phase 4 Navigation: Leads -->
                                <NavLink
                                    v-if="$page.props.auth.can.viewLeads"
                                    :href="route('leads.index')"
                                    :active="route().current('leads.*')"
                                >
                                    Leads
                                </NavLink>

                                <!-- Phase 1 Navigation: Users -->
                                <NavLink
                                    v-if="$page.props.auth.can.viewUsers"
                                    :href="route('users.index')"
                                    :active="route().current('users.*')"
                                >
                                    Users
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <!-- Role Badge -->
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold mr-3 bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200 border border-blue-200 dark:border-blue-700">
                                {{ $page.props.auth.user.role }}
                            </span>

                            <!-- Settings Dropdown -->
                            <div class="relative ms-2">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-slate-200 dark:border-slate-700 bg-white px-3 py-2 text-sm font-medium leading-4 text-slate-700 transition duration-150 ease-in-out hover:text-slate-900 hover:bg-slate-50 focus:outline-none dark:bg-slate-800 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-700"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4 text-slate-500"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition duration-150 ease-in-out hover:bg-slate-100 hover:text-slate-500 focus:bg-slate-100 focus:text-slate-500 focus:outline-none dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-400 dark:focus:bg-slate-800 dark:focus:text-slate-400"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden border-t border-slate-200 dark:border-slate-800"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Dashboard
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="$page.props.auth.can.viewPartners"
                            :href="route('referral-partners.index')"
                            :active="route().current('referral-partners.*')"
                        >
                            Partners
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="$page.props.auth.can.viewDrivers"
                            :href="route('drivers.index')"
                            :active="route().current('drivers.*')"
                        >
                            Drivers
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="$page.props.auth.can.viewVehicles"
                            :href="route('vehicles.index')"
                            :active="route().current('vehicles.*')"
                        >
                            Vehicles
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="$page.props.auth.can.viewReferralCodes"
                            :href="route('referral-codes.index')"
                            :active="route().current('referral-codes.*')"
                        >
                            Referral Codes
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="$page.props.auth.can.viewLeads"
                            :href="route('leads.index')"
                            :active="route().current('leads.*')"
                        >
                            Leads
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="$page.props.auth.can.viewUsers"
                            :href="route('users.index')"
                            :active="route().current('users.*')"
                        >
                            Users
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-slate-200 pb-1 pt-4 dark:border-slate-700">
                        <div class="px-4">
                            <div class="text-base font-medium text-slate-800 dark:text-slate-200">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-slate-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $page.props.auth.user.role }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow-xs dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Flash Alerts -->
            <div v-if="$page.props.flash.success" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-4">
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950/50 p-4 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between text-emerald-800 dark:text-emerald-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
                    </div>
                </div>
            </div>

            <div v-if="$page.props.flash.error" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-4">
                <div class="rounded-lg bg-rose-50 dark:bg-rose-950/50 p-4 border border-rose-200 dark:border-rose-800 flex items-center justify-between text-rose-800 dark:text-rose-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
