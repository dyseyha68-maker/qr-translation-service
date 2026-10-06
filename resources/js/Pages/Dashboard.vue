<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Dashboard
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Telegram Referral & Commission System Overview
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        System Status: Operational
                    </span>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Welcome Card -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                                Welcome, {{ $page.props.auth.user.name }}!
                            </h3>
                            <p class="text-slate-600 dark:text-slate-400 mt-1 text-sm">
                                You are signed in as an authenticated team member.
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Active Role</span>
                                <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                    {{ $page.props.auth.user.role }}
                                </span>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold text-base">
                                {{ $page.props.auth.user.name.charAt(0) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information Card -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Account Status</div>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-lg font-semibold text-slate-900 dark:text-white">
                                {{ $page.props.auth.user.status }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Authenticated session active</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned Role</div>
                        <div class="mt-2 text-lg font-semibold text-blue-600 dark:text-blue-400">
                            {{ $page.props.auth.user.role }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Role-based access control enabled</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">System Health</div>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-lg font-semibold text-emerald-600 dark:text-emerald-400">Operational</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Phase 1 Authentication & Security active</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
