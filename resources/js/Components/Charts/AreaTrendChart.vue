<script setup lang="ts">
import { ref, computed } from 'vue';

interface SeriesConfig {
    key: 'series1' | 'series2';
    name: string;
    color: string;
    colorLight: string;
    unit?: string;
    isCurrency?: boolean;
}

const props = defineProps<{
    title: string;
    subtitle?: string;
    labels: string[];
    dates: string[];
    series1Data: number[];
    series2Data: number[];
    series1Config: {
        name: string;
        color?: string;
        colorLight?: string;
        isCurrency?: boolean;
    };
    series2Config: {
        name: string;
        color?: string;
        colorLight?: string;
        isCurrency?: boolean;
    };
}>();

const emit = defineEmits<{
    (e: 'changeRange', days: number): void;
}>();

const hoveredIndex = ref<number | null>(null);

const s1Color = computed(() => props.series1Config.color ?? '#3b82f6');
const s1ColorLight = computed(() => props.series1Config.colorLight ?? '#93c5fd');
const s2Color = computed(() => props.series2Config.color ?? '#10b981');
const s2ColorLight = computed(() => props.series2Config.colorLight ?? '#6ee7b7');

// SVG viewport coordinates
const width = 800;
const height = 300;
const padding = { top: 25, right: 30, bottom: 40, left: 55 };

const chartWidth = width - padding.left - padding.right;
const chartHeight = height - padding.top - padding.bottom;

const maxValue = computed(() => {
    const s1Max = Math.max(...props.series1Data, 0);
    const s2Max = Math.max(...props.series2Data, 0);
    const overallMax = Math.max(s1Max, s2Max);
    if (overallMax === 0) return 10;
    // Add 15% breathing room at top
    return Math.ceil(overallMax * 1.15);
});

// Calculate Y grid ticks (5 intervals)
const yTicks = computed(() => {
    const max = maxValue.value;
    const ticks = [];
    for (let i = 0; i <= 4; i++) {
        const val = Math.round((max / 4) * i);
        const y = padding.top + chartHeight - (val / max) * chartHeight;
        ticks.push({ val, y });
    }
    return ticks;
});

// Points for series 1 and series 2
const points1 = computed(() => {
    const n = props.labels.length;
    if (n === 0) return [];
    const stepX = n > 1 ? chartWidth / (n - 1) : 0;
    return props.series1Data.map((val, i) => {
        const x = padding.left + i * stepX;
        const y = padding.top + chartHeight - (val / maxValue.value) * chartHeight;
        return { x, y, val };
    });
});

const points2 = computed(() => {
    const n = props.labels.length;
    if (n === 0) return [];
    const stepX = n > 1 ? chartWidth / (n - 1) : 0;
    return props.series2Data.map((val, i) => {
        const x = padding.left + i * stepX;
        const y = padding.top + chartHeight - (val / maxValue.value) * chartHeight;
        return { x, y, val };
    });
});

// Generate smooth cubic bezier SVG path
function generateSmoothPath(pts: { x: number; y: number }[]): string {
    if (pts.length === 0) return '';
    if (pts.length === 1) return `M ${pts[0].x} ${pts[0].y}`;

    let path = `M ${pts[0].x} ${pts[0].y}`;
    for (let i = 0; i < pts.length - 1; i++) {
        const current = pts[i];
        const next = pts[i + 1];
        const controlX1 = current.x + (next.x - current.x) / 2;
        const controlY1 = current.y;
        const controlX2 = current.x + (next.x - current.x) / 2;
        const controlY2 = next.y;
        path += ` C ${controlX1} ${controlY1}, ${controlX2} ${controlY2}, ${next.x} ${next.y}`;
    }
    return path;
}

const linePath1 = computed(() => generateSmoothPath(points1.value));
const linePath2 = computed(() => generateSmoothPath(points2.value));

const areaPath1 = computed(() => {
    const pts = points1.value;
    if (pts.length === 0) return '';
    const bottomY = padding.top + chartHeight;
    const linePart = generateSmoothPath(pts);
    return `${linePart} L ${pts[pts.length - 1].x} ${bottomY} L ${pts[0].x} ${bottomY} Z`;
});

const areaPath2 = computed(() => {
    const pts = points2.value;
    if (pts.length === 0) return '';
    const bottomY = padding.top + chartHeight;
    const linePart = generateSmoothPath(pts);
    return `${linePart} L ${pts[pts.length - 1].x} ${bottomY} L ${pts[0].x} ${bottomY} Z`;
});

// Format numeric values
function formatVal(val: number, isCurrency?: boolean): string {
    if (isCurrency) {
        return '$' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    return val.toLocaleString('en-US');
}

// Hover event handler based on mouse X
function handleMouseMove(event: MouseEvent) {
    const svgEl = event.currentTarget as SVGSVGElement;
    const rect = svgEl.getBoundingClientRect();
    const clientX = event.clientX - rect.left;
    const scaleX = width / rect.width;
    const svgX = clientX * scaleX;

    const n = props.labels.length;
    if (n === 0) return;

    let closestIdx = 0;
    let minDiff = Infinity;
    const stepX = n > 1 ? chartWidth / (n - 1) : 0;

    for (let i = 0; i < n; i++) {
        const ptX = padding.left + i * stepX;
        const diff = Math.abs(svgX - ptX);
        if (diff < minDiff) {
            minDiff = diff;
            closestIdx = i;
        }
    }
    hoveredIndex.value = closestIdx;
}

function handleMouseLeave() {
    hoveredIndex.value = null;
}
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <!-- Header & Series Legend -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>{{ title }}</span>
                </h3>
                <p v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ subtitle }}
                </p>
            </div>

            <!-- Legend Pills -->
            <div class="flex items-center gap-4 text-xs">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: s1Color }"></span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">
                        {{ series1Config.name }}
                    </span>
                    <span class="font-bold text-slate-900 dark:text-white font-mono">
                        {{ formatVal(series1Data.reduce((a, b) => a + b, 0), series1Config.isCurrency) }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: s2Color }"></span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">
                        {{ series2Config.name }}
                    </span>
                    <span class="font-bold text-slate-900 dark:text-white font-mono">
                        {{ formatVal(series2Data.reduce((a, b) => a + b, 0), series2Config.isCurrency) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="relative w-full">
            <svg
                :viewBox="`0 0 ${width} ${height}`"
                class="w-full h-auto overflow-visible cursor-crosshair select-none"
                @mousemove="handleMouseMove"
                @mouseleave="handleMouseLeave"
            >
                <defs>
                    <!-- Gradient Series 1 -->
                    <linearGradient id="gradientS1" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" :stop-color="s1Color" stop-opacity="0.35" />
                        <stop offset="100%" :stop-color="s1Color" stop-opacity="0.00" />
                    </linearGradient>

                    <!-- Gradient Series 2 -->
                    <linearGradient id="gradientS2" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" :stop-color="s2Color" stop-opacity="0.3" />
                        <stop offset="100%" :stop-color="s2Color" stop-opacity="0.00" />
                    </linearGradient>
                </defs>

                <!-- Horizontal Y Grid Lines -->
                <g class="grid-lines" stroke="currentColor">
                    <line
                        v-for="tick in yTicks"
                        :key="tick.val"
                        :x1="padding.left"
                        :y1="tick.y"
                        :x2="width - padding.right"
                        :y2="tick.y"
                        class="text-slate-100 dark:text-slate-800/80 stroke-1"
                        stroke-dasharray="3 3"
                    />
                </g>

                <!-- Y Axis Labels -->
                <g class="y-labels text-[11px] fill-slate-400 dark:fill-slate-500 font-mono">
                    <text
                        v-for="tick in yTicks"
                        :key="'label-' + tick.val"
                        :x="padding.left - 10"
                        :y="tick.y + 4"
                        text-anchor="end"
                    >
                        {{ series1Config.isCurrency ? '$' + tick.val : tick.val }}
                    </text>
                </g>

                <!-- Area Fill 1 -->
                <path
                    :d="areaPath1"
                    fill="url(#gradientS1)"
                    class="transition-opacity duration-300"
                />

                <!-- Area Fill 2 -->
                <path
                    :d="areaPath2"
                    fill="url(#gradientS2)"
                    class="transition-opacity duration-300"
                />

                <!-- Curve Line 2 (Secondary) -->
                <path
                    :d="linePath2"
                    fill="none"
                    :stroke="s2Color"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="transition-all duration-300"
                />

                <!-- Curve Line 1 (Primary) -->
                <path
                    :d="linePath1"
                    fill="none"
                    :stroke="s1Color"
                    stroke-width="3"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="transition-all duration-300"
                />

                <!-- X Axis Labels -->
                <g class="x-labels text-[11px] fill-slate-400 dark:fill-slate-500 font-sans">
                    <template v-for="(label, idx) in labels" :key="'xlabel-' + idx">
                        <text
                            v-if="labels.length <= 14 || idx % 2 === 0 || idx === labels.length - 1"
                            :x="points1[idx]?.x ?? 0"
                            :y="height - 10"
                            text-anchor="middle"
                        >
                            {{ label }}
                        </text>
                    </template>
                </g>

                <!-- Hover Crosshair & Dots -->
                <g v-if="hoveredIndex !== null && points1[hoveredIndex]" class="pointer-events-none">
                    <!-- Vertical Line -->
                    <line
                        :x1="points1[hoveredIndex].x"
                        :y1="padding.top"
                        :x2="points1[hoveredIndex].x"
                        :y2="padding.top + chartHeight"
                        class="stroke-slate-400 dark:stroke-slate-500 stroke-1"
                        stroke-dasharray="3 3"
                    />

                    <!-- Series 2 Point Dot -->
                    <circle
                        :cx="points2[hoveredIndex]?.x"
                        :cy="points2[hoveredIndex]?.y"
                        r="6"
                        :fill="s2Color"
                        class="stroke-white dark:stroke-slate-900 stroke-2 drop-shadow-md"
                    />

                    <!-- Series 1 Point Dot -->
                    <circle
                        :cx="points1[hoveredIndex].x"
                        :cy="points1[hoveredIndex].y"
                        r="6.5"
                        :fill="s1Color"
                        class="stroke-white dark:stroke-slate-900 stroke-2 drop-shadow-md"
                    />
                </g>
            </svg>

            <!-- Interactive Floating Tooltip -->
            <div
                v-if="hoveredIndex !== null && points1[hoveredIndex]"
                class="absolute pointer-events-none z-20 rounded-lg border border-slate-200 bg-white/95 px-3 py-2 shadow-lg backdrop-blur-xs text-xs dark:border-slate-700 dark:bg-slate-800/95 transition-transform duration-75"
                :style="{
                    left: `${(points1[hoveredIndex].x / width) * 100}%`,
                    top: '15px',
                    transform: points1[hoveredIndex].x > width * 0.7 ? 'translateX(-100%)' : 'translateX(10px)',
                }"
            >
                <div class="font-bold text-slate-800 dark:text-white border-b border-slate-100 dark:border-slate-700 pb-1 mb-1">
                    {{ labels[hoveredIndex] }} ({{ dates[hoveredIndex] }})
                </div>
                <div class="space-y-1">
                    <div class="flex items-center justify-between gap-4">
                        <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                            <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: s1Color }"></span>
                            {{ series1Config.name }}:
                        </span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono">
                            {{ formatVal(series1Data[hoveredIndex], series1Config.isCurrency) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                            <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: s2Color }"></span>
                            {{ series2Config.name }}:
                        </span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono">
                            {{ formatVal(series2Data[hoveredIndex], series2Config.isCurrency) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
