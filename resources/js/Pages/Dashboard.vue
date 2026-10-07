<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps<{
    activePartnersCount?: number;
    activeDriversCount?: number;
    activeVehiclesCount?: number;
    activeReferralCodesCount?: number;
    newLeadsCount?: number;
    activeLeadsCount?: number;
    ordersSummary?: {
        newOrders: number;
        confirmedOrders: number;
        completedOrders: number;
        cancelledOrders: number;
        unpaidOrders: number;
        partiallyPaidOrders: number;
        fullyPaidOrders: number;
        totalPaidAmount: number;
        totalOutstandingAmount: number;
    };
}>();

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

                <!-- Operational Metrics -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">New Leads</span>
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-blue-600 dark:text-blue-400">
                            {{ props.newLeadsCount ?? 0 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Newly submitted QR leads</p>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800" v-if="$page.props.auth.can.viewLeads">
                            <Link :href="route('leads.index', { status: 'NEW' })" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                View New Leads &rarr;
                            </Link>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Pipeline</span>
                            <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">
                            {{ props.activeLeadsCount ?? 0 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Leads currently in intake</p>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800" v-if="$page.props.auth.can.viewLeads">
                            <Link :href="route('leads.index')" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                Lead Management &rarr;
                            </Link>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Referral Codes</span>
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">
                            {{ props.activeReferralCodesCount ?? 0 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Active QR referral codes</p>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800" v-if="$page.props.auth.can.viewReferralCodes">
                            <Link :href="route('referral-codes.index')" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                View Referral Codes &rarr;
                            </Link>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Partners</span>
                            <span class="h-2.5 w-2.5 rounded-full bg-cyan-500"></span>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">
                            {{ props.activePartnersCount ?? 0 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Companies & associations</p>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800" v-if="$page.props.auth.can.viewPartners">
                            <Link :href="route('referral-partners.index')" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                View Partners &rarr;
                            </Link>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Drivers</span>
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">
                            {{ props.activeDriversCount ?? 0 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Taxi and tuk-tuk referrers</p>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800" v-if="$page.props.auth.can.viewDrivers">
                            <Link :href="route('drivers.index')" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                View Drivers &rarr;
                            </Link>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Vehicles</span>
                            <span class="h-2.5 w-2.5 rounded-full bg-purple-500"></span>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">
                            {{ props.activeVehiclesCount ?? 0 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Registered vehicles</p>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800" v-if="$page.props.auth.can.viewVehicles">
                            <Link :href="route('vehicles.index')" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                View Vehicles &rarr;
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Phase 5: Orders & Payments Metrics -->
                <div v-if="props.ordersSummary" class="space-y-6">
                    <div class="border-b border-slate-200 dark:border-slate-800 pb-2">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                            Orders &amp; Payment Fulfillment
                        </h3>
                        <p class="text-xs text-slate-500">
                            Real-time order pipeline and customer payment reconciliation
                        </p>
                    </div>

                    <!-- Order Status Counts -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider block">Pending Orders</span>
                            <div class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">
                                {{ props.ordersSummary.newOrders }}
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">Awaiting confirmation</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider block">Confirmed Orders</span>
                            <div class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">
                                {{ props.ordersSummary.confirmedOrders }}
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">In translation</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">Completed</span>
                            <div class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">
                                {{ props.ordersSummary.completedOrders }}
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">Delivered</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <span class="text-xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider block">Cancelled</span>
                            <div class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">
                                {{ props.ordersSummary.cancelledOrders }}
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">Voided</span>
                        </div>
                    </div>

                    <!-- Payment Tracking Metrics -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <span class="text-xs font-semibold text-rose-500 uppercase tracking-wider block">Unpaid Orders</span>
                            <div class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">
                                {{ props.ordersSummary.unpaidOrders }}
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">$0 collected</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <span class="text-xs font-semibold text-amber-500 uppercase tracking-wider block">Partially Paid</span>
                            <div class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">
                                {{ props.ordersSummary.partiallyPaidOrders }}
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">Deposit received</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <span class="text-xs font-semibold text-emerald-500 uppercase tracking-wider block">Fully Paid Orders</span>
                            <div class="mt-1 text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">
                                {{ props.ordersSummary.fullyPaidOrders }}
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">100% collected</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Outstanding Balance</span>
                            <div class="mt-1 text-2xl font-extrabold font-mono text-rose-600 dark:text-rose-400">
                                ${{ Number(props.ordersSummary.totalOutstandingAmount).toFixed(2) }}
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">Total Paid: ${{ Number(props.ordersSummary.totalPaidAmount).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
