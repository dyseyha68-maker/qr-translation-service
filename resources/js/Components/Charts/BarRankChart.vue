<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

export interface DriverRankItem {
    id: number;
    name: string;
    phone?: string;
    partner_name: string;
    vehicles?: string;
    total_scans: number;
    total_leads: number;
    total_orders: number;
    confirmed_orders: number;
    total_revenue: number;
    commission_earned: number;
}

const props = defineProps<{
    drivers: DriverRankItem[];
}>();

const maxRevenue = computed(() => {
    if (props.drivers.length === 0) return 1;
    const max = Math.max(...props.drivers.map(d => d.total_revenue));
    return max > 0 ? max : 1;
});

function getRankBadge(rank: number): { text: string; bg: string; textCol: string } {
    switch (rank) {
        case 1:
            return { text: '🥇 #1', bg: 'bg-amber-100 dark:bg-amber-950/70 border-amber-300 dark:border-amber-700', textCol: 'text-amber-800 dark:text-amber-300' };
        case 2:
            return { text: '🥈 #2', bg: 'bg-slate-200 dark:bg-slate-800 border-slate-300 dark:border-slate-700', textCol: 'text-slate-800 dark:text-slate-200' };
        case 3:
            return { text: '🥉 #3', bg: 'bg-orange-100 dark:bg-orange-950/70 border-orange-300 dark:border-orange-800', textCol: 'text-orange-800 dark:text-orange-300' };
        default:
            return { text: `#${rank}`, bg: 'bg-slate-100 dark:bg-slate-850 border-slate-200 dark:border-slate-800', textCol: 'text-slate-600 dark:text-slate-400' };
    }
}
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>Top Performing Referral Drivers</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Highest revenue generating drivers and earned commission rankings
                </p>
            </div>
            <Link
                :href="route('drivers.index')"
                class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400"
            >
                View All Drivers &rarr;
            </Link>
        </div>

        <div v-if="drivers.length === 0" class="py-8 text-center text-xs text-slate-400">
            No driver referral transactions recorded yet.
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="(driver, index) in drivers"
                :key="driver.id"
                class="rounded-lg border border-slate-100 bg-slate-50/50 p-3.5 transition-colors hover:border-slate-300 dark:border-slate-800 dark:bg-slate-850/60 dark:hover:border-slate-700"
            >
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-xs font-bold border"
                            :class="[getRankBadge(index + 1).bg, getRankBadge(index + 1).textCol]"
                        >
                            {{ getRankBadge(index + 1).text }}
                        </span>
                        <div>
                            <Link
                                :href="route('drivers.show', driver.id)"
                                class="text-sm font-bold text-slate-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400"
                            >
                                {{ driver.name }}
                            </Link>
                            <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                                <span>{{ driver.partner_name }}</span>
                                <span v-if="driver.vehicles" class="font-mono text-slate-400">&bull; {{ driver.vehicles }}</span>
                                <span v-if="driver.phone" class="font-mono text-slate-400">&bull; {{ driver.phone }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 text-xs font-mono">
                        <div class="text-right">
                            <span class="text-[10px] uppercase tracking-wider text-slate-400 block font-sans">Leads / Orders</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300">
                                {{ driver.total_leads }} / {{ driver.confirmed_orders }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] uppercase tracking-wider text-slate-400 block font-sans">Revenue</span>
                            <span class="font-bold text-slate-900 dark:text-white">
                                ${{ driver.total_revenue.toFixed(2) }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] uppercase tracking-wider text-slate-400 block font-sans">Earned Comm.</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                ${{ driver.commission_earned.toFixed(2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Progress fill comparison bar -->
                <div class="mt-2.5 w-full bg-slate-200/70 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                    <div
                        class="h-full rounded-full bg-linear-to-r from-blue-500 to-indigo-600 dark:from-blue-400 dark:to-indigo-500 transition-all duration-500"
                        :style="{
                            width: `${Math.max(5, (driver.total_revenue / maxRevenue) * 100)}%`,
                        }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>
