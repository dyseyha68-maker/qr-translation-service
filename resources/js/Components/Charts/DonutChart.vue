<script setup lang="ts">
import { ref, computed } from 'vue';

export interface DonutItem {
    label: string;
    count: number;
    percentage?: number;
    color: string;
    amount?: number;
}

const props = defineProps<{
    title: string;
    subtitle?: string;
    items: DonutItem[];
    centerLabel?: string;
    centerValue?: string | number;
    isCurrency?: boolean;
}>();

const hoveredIndex = ref<number | null>(null);

const total = computed(() => {
    return props.items.reduce((sum, item) => sum + (props.isCurrency && item.amount !== undefined ? item.amount : item.count), 0);
});

// Radius and geometry
const size = 220;
const strokeWidth = 26;
const radius = (size - strokeWidth) / 2;
const circumference = 2 * Math.PI * radius;

// Calculate stroke dashes and offsets for each item
const slices = computed(() => {
    const tot = total.value;
    let accumulatedAngle = 0;

    return props.items.map((item, index) => {
        const val = props.isCurrency && item.amount !== undefined ? item.amount : item.count;
        const pct = tot > 0 ? (val / tot) * 100 : 0;
        const strokeLength = tot > 0 ? (val / tot) * circumference : 0;
        const strokeOffset = circumference - strokeLength;
        const rotation = (accumulatedAngle / 100) * 360 - 90;

        accumulatedAngle += pct;

        return {
            ...item,
            index,
            val,
            pct: roundPct(pct),
            strokeLength,
            strokeOffset,
            rotation,
        };
    });
});

function roundPct(val: number): string {
    return val.toFixed(1) + '%';
}

const activeCenterTitle = computed(() => {
    if (hoveredIndex.value !== null && slices.value[hoveredIndex.value]) {
        return slices.value[hoveredIndex.value].label;
    }
    return props.centerLabel ?? 'Total';
});

const activeCenterValue = computed(() => {
    if (hoveredIndex.value !== null && slices.value[hoveredIndex.value]) {
        const item = slices.value[hoveredIndex.value];
        if (props.isCurrency) {
            return '$' + (item.amount ?? item.count).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        return item.count.toLocaleString('en-US') + ` (${item.pct})`;
    }

    if (props.centerValue !== undefined) {
        return props.centerValue;
    }

    if (props.isCurrency) {
        return '$' + total.value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    return total.value.toLocaleString('en-US');
});
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
        <div>
            <!-- Header -->
            <div class="mb-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    {{ title }}
                </h3>
                <p v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ subtitle }}
                </p>
            </div>

            <!-- Donut Visual -->
            <div class="relative flex items-center justify-center my-3">
                <svg
                    :viewBox="`0 0 ${size} ${size}`"
                    class="w-48 h-48 transform -rotate-90 select-none overflow-visible"
                >
                    <!-- Background Circle Ring -->
                    <circle
                        :cx="size / 2"
                        :cy="size / 2"
                        :r="radius"
                        class="stroke-slate-100 dark:stroke-slate-800"
                        :stroke-width="strokeWidth - 4"
                        fill="none"
                    />

                    <!-- Donut Arc Slices -->
                    <g
                        v-for="(slice, i) in slices"
                        :key="slice.label"
                        class="transition-all duration-300 origin-center cursor-pointer"
                        @mouseenter="hoveredIndex = i"
                        @mouseleave="hoveredIndex = null"
                    >
                        <circle
                            v-if="slice.val > 0"
                            :cx="size / 2"
                            :cy="size / 2"
                            :r="radius"
                            :stroke="slice.color"
                            :stroke-width="hoveredIndex === i ? strokeWidth + 4 : strokeWidth"
                            fill="none"
                            :stroke-dasharray="`${slice.strokeLength} ${circumference}`"
                            :stroke-dashoffset="0"
                            :transform="`rotate(${(slices.slice(0, i).reduce((acc, s) => acc + (total > 0 ? (s.val / total) * 360 : 0), 0))}, ${size / 2}, ${size / 2})`"
                            stroke-linecap="round"
                            class="transition-all duration-200"
                        />
                    </g>
                </svg>

                <!-- Center Metric Text -->
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none px-6">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ activeCenterTitle }}
                    </span>
                    <span class="text-xl font-extrabold text-slate-900 dark:text-white font-mono mt-0.5">
                        {{ activeCenterValue }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Legend List -->
        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 space-y-2">
            <div
                v-for="(slice, idx) in slices"
                :key="'leg-' + slice.label"
                class="flex items-center justify-between text-xs py-1 px-1.5 rounded-md transition-colors cursor-pointer"
                :class="{
                    'bg-slate-100 dark:bg-slate-800': hoveredIndex === idx,
                }"
                @mouseenter="hoveredIndex = idx"
                @mouseleave="hoveredIndex = null"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 rounded-full shrink-0"
                        :style="{ backgroundColor: slice.color }"
                    ></span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">
                        {{ slice.label }}
                    </span>
                </div>

                <div class="flex items-center gap-2 font-mono">
                    <span v-if="isCurrency && slice.amount !== undefined" class="font-bold text-slate-900 dark:text-white">
                        ${{ slice.amount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </span>
                    <span v-else class="font-bold text-slate-900 dark:text-white">
                        {{ slice.count.toLocaleString('en-US') }}
                    </span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        {{ slice.pct }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
