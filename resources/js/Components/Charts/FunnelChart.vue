<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    scans: number;
    leads: number;
    orders: number;
    paidOrders: number;
    scanToLeadRate?: number;
    leadToOrderRate?: number;
    orderToPaidRate?: number;
    overallRate?: number;
}>();

const maxVal = computed(() => Math.max(props.scans, props.leads, props.orders, props.paidOrders, 1));

const stages = computed(() => [
    {
        name: 'QR Code Scans',
        step: 1,
        count: props.scans,
        desc: 'Direct tuk-tuk/taxi QR scans',
        color: '#6366f1', // indigo-500
        bgGradient: 'from-indigo-500/10 to-indigo-500/5 dark:from-indigo-950/40 dark:to-indigo-950/20',
        borderColor: 'border-indigo-200 dark:border-indigo-800/60',
        widthPct: 100,
        rateFromPrev: null,
    },
    {
        name: 'Telegram Leads',
        step: 2,
        count: props.leads,
        desc: 'Inbound chat inquiries opened',
        color: '#3b82f6', // blue-500
        bgGradient: 'from-blue-500/10 to-blue-500/5 dark:from-blue-950/40 dark:to-blue-950/20',
        borderColor: 'border-blue-200 dark:border-blue-800/60',
        widthPct: props.scans > 0 ? Math.max(12, Math.min(100, (props.leads / props.scans) * 100)) : (props.leads > 0 ? 80 : 12),
        rateFromPrev: props.scanToLeadRate ?? (props.scans > 0 ? Math.round((props.leads / props.scans) * 1000) / 10 : 0),
    },
    {
        name: 'Translation Orders',
        step: 3,
        count: props.orders,
        desc: 'Quoted & translation orders',
        color: '#0ea5e9', // sky-500
        bgGradient: 'from-sky-500/10 to-sky-500/5 dark:from-sky-950/40 dark:to-sky-950/20',
        borderColor: 'border-sky-200 dark:border-sky-800/60',
        widthPct: props.scans > 0 ? Math.max(10, Math.min(100, (props.orders / props.scans) * 100)) : (props.orders > 0 ? 60 : 10),
        rateFromPrev: props.leadToOrderRate ?? (props.leads > 0 ? Math.round((props.orders / props.leads) * 1000) / 10 : 0),
    },
    {
        name: 'Paid & Fulfilled',
        step: 4,
        count: props.paidOrders,
        desc: 'Settled & commission earned',
        color: '#10b981', // emerald-500
        bgGradient: 'from-emerald-500/10 to-emerald-500/5 dark:from-emerald-950/40 dark:to-emerald-950/20',
        borderColor: 'border-emerald-200 dark:border-emerald-800/60',
        widthPct: props.scans > 0 ? Math.max(8, Math.min(100, (props.paidOrders / props.scans) * 100)) : (props.paidOrders > 0 ? 40 : 8),
        rateFromPrev: props.orderToPaidRate ?? (props.orders > 0 ? Math.round((props.paidOrders / props.orders) * 1000) / 10 : 0),
    },
]);

const overallConversion = computed(() => {
    if (props.overallRate !== undefined) return props.overallRate;
    if (props.scans === 0) return 0;
    return Math.round((props.paidOrders / props.scans) * 1000) / 10;
});
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>Referral Conversion Funnel</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    End-to-end customer journey from physical QR scan to paid order fulfillment
                </p>
            </div>
            <div class="flex items-center gap-2 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-800 dark:text-emerald-300">
                <span>Overall End-to-End Rate:</span>
                <span class="text-sm font-extrabold font-mono text-emerald-600 dark:text-emerald-400">
                    {{ overallConversion }}%
                </span>
            </div>
        </div>

        <!-- Funnel Stages Container -->
        <div class="space-y-3">
            <template v-for="(stage, idx) in stages" :key="stage.step">
                <!-- Drop-off indicator between steps -->
                <div v-if="idx > 0" class="flex items-center justify-between px-4 py-0.5 text-xs">
                    <div class="flex items-center gap-1.5 text-slate-400 dark:text-slate-500">
                        <svg class="h-3.5 w-3.5 text-slate-400 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                        <span class="text-[11px] font-medium">Stage {{ idx }} &rarr; Stage {{ idx + 1 }} Transition</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">Conversion Rate:</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold font-mono bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                            {{ stage.rateFromPrev }}%
                        </span>
                    </div>
                </div>

                <!-- Stage Card -->
                <div
                    class="relative overflow-hidden rounded-xl border p-4 transition-all hover:shadow-xs"
                    :class="[stage.borderColor, 'bg-linear-to-r ' + stage.bgGradient]"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 relative z-10">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-8 w-8 rounded-lg flex items-center justify-center font-bold text-sm text-white shrink-0 shadow-xs"
                                :style="{ backgroundColor: stage.color }"
                            >
                                {{ stage.step }}
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ stage.name }}
                                </h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ stage.desc }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Total Volume</span>
                                <span class="text-2xl font-black text-slate-900 dark:text-white font-mono">
                                    {{ stage.count.toLocaleString('en-US') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Progress Bar inside card -->
                    <div class="mt-3 w-full bg-slate-200/60 dark:bg-slate-800/80 rounded-full h-2 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :style="{
                                width: `${stage.widthPct}%`,
                                backgroundColor: stage.color,
                            }"
                        ></div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
