<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    grossRevenue: number;
    driverCommissions: number;
    netRevenue: number;
    driverSharePercent?: number;
    companyMarginPercent?: number;
}>();

const driverPct = computed(() => {
    if (props.driverSharePercent !== undefined) return props.driverSharePercent;
    if (props.grossRevenue <= 0) return 0;
    return Math.round((props.driverCommissions / props.grossRevenue) * 1000) / 10;
});

const companyPct = computed(() => {
    if (props.companyMarginPercent !== undefined) return props.companyMarginPercent;
    if (props.grossRevenue <= 0) return 100;
    return Math.round((props.netRevenue / props.grossRevenue) * 1000) / 10;
});
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                Revenue &amp; Commission Margin Breakdown
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Financial share distribution between company net revenue and driver referral commissions
            </p>
        </div>

        <!-- 3 KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
            <div class="rounded-lg border border-slate-100 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-800/40">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Gross Revenue</span>
                <div class="text-lg font-black text-slate-900 dark:text-white font-mono mt-0.5">
                    ${{ grossRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
                <span class="text-[11px] text-slate-400 mt-0.5 block">100% Client volume</span>
            </div>

            <div class="rounded-lg border border-emerald-100 bg-emerald-50/50 p-3 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider block">Net Company Revenue</span>
                    <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300">
                        {{ companyPct }}%
                    </span>
                </div>
                <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5">
                    ${{ netRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
                <span class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 block">Translation gross margin</span>
            </div>

            <div class="rounded-lg border border-amber-100 bg-amber-50/50 p-3 dark:border-amber-900/40 dark:bg-amber-950/20">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider block">Driver Commission</span>
                    <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">
                        {{ driverPct }}%
                    </span>
                </div>
                <div class="text-lg font-black text-amber-600 dark:text-amber-400 font-mono mt-0.5">
                    ${{ driverCommissions.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
                <span class="text-[11px] text-amber-600/80 dark:text-amber-400/80 mt-0.5 block">Referral partner payouts</span>
            </div>
        </div>

        <!-- Stacked Horizontal Distribution Bar -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Net Margin: {{ companyPct }}%
                </span>
                <span class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400 font-semibold">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    Driver Share: {{ driverPct }}%
                </span>
            </div>
            <div class="h-4 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden flex shadow-inner">
                <div
                    class="h-full bg-linear-to-r from-emerald-500 to-teal-500 transition-all duration-500"
                    :style="{ width: `${companyPct}%` }"
                    :title="`Net Margin: ${companyPct}%`"
                ></div>
                <div
                    class="h-full bg-linear-to-r from-amber-400 to-amber-500 transition-all duration-500"
                    :style="{ width: `${driverPct}%` }"
                    :title="`Driver Commission: ${driverPct}%`"
                ></div>
            </div>
        </div>
    </div>
</template>
