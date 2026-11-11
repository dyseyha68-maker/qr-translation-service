<script setup lang="ts">
import { ReferralCode } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { toPng } from 'html-to-image';
import { computed, ref } from 'vue';

interface CardItem {
    referralCode: ReferralCode;
    telegramDeepLink: string;
    qrSvg: string;
}

const props = withDefaults(
    defineProps<{
        cards: CardItem[];
        companyName?: string;
        supportPhone?: string;
        address?: string;
        companyLogo?: string | null;
    }>(),
    {
        companyName: 'DIN & CO Translation',
        supportPhone: '(+855) 12 762 772',
        address: 'Phnom Penh, Cambodia',
        companyLogo: null,
    }
);

// Layout: Cards per A4 Sheet (1, 2, 4, or 6 cards)
const cardsPerSheet = ref<number>(4);

// Orientation toggle: 'landscape' | 'portrait' (defaulting to landscape as requested for QR Card Studio)
const orientation = ref<'portrait' | 'landscape'>('landscape');

// Scale percentage for A4 paper (default 85% for multi-card balance)
const cardScale = ref<number>(85);

// Reactive editable fields applied to all cards in the batch (identical defaults to Print.vue)
const brandName = ref(props.companyName || 'DIN & CO Translation');
const headline = ref('ស្កេនដើម្បីបកប្រែឯកសារ');
const servicesSubtitle = ref('ឯកសារច្បាប់ · ទិដ្ឋាការ · ស្ថានទូត · អាជីវកម្ម');
const ctaBadge = ref('សេវាកម្មរហ័សតាម Telegram');
const instructions = ref(
    'សូមបើកកាមេរ៉ាទូរស័ព្ទ ឬកម្មវិធី Telegram ដើម្បីស្កេន QR កូដនេះ និងផ្ញើឯកសារមកកាន់យើងខ្ញុំភ្លាមៗ'
);
const footerText = ref(
    `${props.address} · ទូរស័ព្ទ៖ ${props.supportPhone}`
);

// Display options
const showLogo = ref(Boolean(props.companyLogo));
const showDiscreteCode = ref(true);
const showCutGuides = ref(true);

// Chunk cards into A4 sheets
const sheets = computed(() => {
    const perSheet = cardsPerSheet.value;
    const result: CardItem[][] = [];
    for (let i = 0; i < props.cards.length; i += perSheet) {
        result.push(props.cards.slice(i, i + perSheet));
    }
    return result;
});

// Quick presets for Khmer, Bilingual, and English (Identical to Print.vue QR Card Studio)
function applyKhmerPreset() {
    brandName.value = props.companyName || 'DIN & CO Translation';
    headline.value = 'ស្កេនដើម្បីបកប្រែឯកសារ';
    servicesSubtitle.value = 'ឯកសារច្បាប់ · ទិដ្ឋាការ · ស្ថានទូត · អាជីវកម្ម';
    ctaBadge.value = 'សេវាកម្មរហ័សតាម Telegram';
    instructions.value =
        'សូមបើកកាមេរ៉ាទូរស័ព្ទ ឬកម្មវិធី Telegram ដើម្បីស្កេន QR កូដនេះ និងផ្ញើឯកសារមកកាន់យើងខ្ញុំភ្លាមៗ';
    footerText.value = `${props.address} · ទូរស័ព្ទ៖ ${props.supportPhone}`;
}

function applyBilingualPreset() {
    brandName.value = `${props.companyName || 'DIN & CO'} · សេវាកម្មបកប្រែ`;
    headline.value = 'ស្កេនដើម្បីបកប្រែឯកសារ\nScan for Translation';
    servicesSubtitle.value =
        'Legal · Visa · Embassy · Business Documents\nឯកសារច្បាប់ · ទិដ្ឋាការ · ស្ថានទូត · អាជីវកម្ម';
    ctaBadge.value = 'Telegram Direct Service 24/7';
    instructions.value =
        'Open your camera or Telegram app and scan to submit documents.\nស្កេនតាមរយៈកាមេរ៉ា ឬ Telegram ដើម្បីទទួលបានការប្រឹក្សា និងតម្លៃភ្លាមៗ';
    footerText.value = `${props.address} · Hotline: ${props.supportPhone}`;
}

function applyEnglishPreset() {
    brandName.value = props.companyName || 'DIN & CO Translation';
    headline.value = 'Scan for Translation';
    servicesSubtitle.value = 'Legal · Visa · Embassy · Business · Documents';
    ctaBadge.value = 'Telegram Direct Service';
    instructions.value =
        'Open your phone camera or Telegram app and scan this QR code to begin your translation inquiry.';
    footerText.value = `${props.address} · Tel: ${props.supportPhone}`;
}

function triggerPrint() {
    window.print();
}

// Export single card or all cards as 300 DPI PNG images for Photoshop / Word
const exportingCardId = ref<number | null>(null);
const isExportingAll = ref(false);
const exportProgress = ref('');

async function downloadSingleCard(item: CardItem) {
    const el = document.getElementById(`card-export-${item.referralCode.id}`);
    if (!el) return;

    exportingCardId.value = item.referralCode.id;
    try {
        const dataUrl = await toPng(el, {
            pixelRatio: 3, // Ultra-sharp 300 DPI for Word & Photoshop
            backgroundColor: '#ffffff',
            cacheBust: true,
            filter: (node) => !(node instanceof HTMLElement && node.classList.contains('export-exclude')),
        });

        const link = document.createElement('a');
        link.download = `DIN-CO-QR-Card-${item.referralCode.code}.png`;
        link.href = dataUrl;
        link.click();
    } catch (err) {
        console.error('Failed to export card image:', err);
    } finally {
        exportingCardId.value = null;
    }
}

async function downloadAllCards() {
    if (props.cards.length === 0 || isExportingAll.value) return;

    isExportingAll.value = true;
    try {
        for (let i = 0; i < props.cards.length; i++) {
            const item = props.cards[i];
            exportProgress.value = `${i + 1}/${props.cards.length}`;
            const el = document.getElementById(`card-export-${item.referralCode.id}`);
            if (el) {
                const dataUrl = await toPng(el, {
                    pixelRatio: 3,
                    backgroundColor: '#ffffff',
                    cacheBust: true,
                    filter: (node) => !(node instanceof HTMLElement && node.classList.contains('export-exclude')),
                });
                const link = document.createElement('a');
                link.download = `DIN-CO-QR-Card-${item.referralCode.code}.png`;
                link.href = dataUrl;
                link.click();
                // Short wait to ensure browser handles downloads smoothly
                await new Promise((resolve) => setTimeout(resolve, 300));
            }
        }
    } catch (err) {
        console.error('Failed to export all cards:', err);
    } finally {
        isExportingAll.value = false;
        exportProgress.value = '';
    }
}
</script>

<template>
    <Head :title="`Batch Print (${cards.length} Cards · ${cardsPerSheet} per A4 Sheet) - DIN & CO`" />

    <!-- Dynamic Print Page Size, Page Breaks & Scale for A4 -->
    <component :is="'style'">
        @media print {
            @page {
                size: A4 {{ orientation }};
                margin: 0.6cm;
            }
            body {
                background-color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .a4-print-sheet {
                page-break-after: always !important;
                break-after: page !important;
                zoom: {{ cardScale / 100 }} !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                padding: 0 !important;
                margin: 0 auto !important;
                border: none !important;
            }
            .a4-print-sheet:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
        }
    </component>

    <div class="min-h-screen bg-slate-100 font-sans print:bg-white print:p-0">
        <!-- Floating Top Action Bar (Hidden during print) -->
        <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-xs border-b border-slate-200 px-4 py-3 shadow-xs print:hidden">
            <div class="mx-auto max-w-7xl flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('referral-codes.index')"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                    >
                        &larr; Back to Codes
                    </Link>
                    <div>
                        <h1 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>A4 Multi-Card Batch Studio</span>
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">
                                {{ cards.length }} Cards &middot; {{ sheets.length }} Sheet{{ sheets.length > 1 ? 's' : '' }}
                            </span>
                        </h1>
                        <p class="text-xs text-slate-500">
                            Arranged as <strong>{{ cardsPerSheet }} cards per A4 sheet</strong> &middot; Scale: {{ cardScale }}% &middot; {{ orientation === 'landscape' ? 'Landscape (ផ្ដេក)' : 'Portrait (បញ្ឈរ)' }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Cards per Sheet Selector -->
                    <div class="flex items-center gap-1 bg-slate-50 border border-slate-200 rounded-lg p-1 text-xs">
                        <span class="text-slate-400 font-semibold px-1 text-[11px]">Cards/A4:</span>
                        <button
                            v-for="count in [1, 2, 4, 6]"
                            :key="count"
                            @click="cardsPerSheet = count"
                            type="button"
                            :class="[
                                cardsPerSheet === count
                                    ? 'bg-blue-600 text-white font-bold shadow-xs'
                                    : 'text-slate-600 hover:bg-slate-200 font-medium'
                            ]"
                            class="px-2.5 py-1 rounded-md transition cursor-pointer"
                        >
                            {{ count === 1 ? '1 (Large)' : count === 2 ? '2 (Half)' : count === 4 ? '4 (TukTuk A6)' : '6 (Mini)' }}
                        </button>
                    </div>

                    <!-- Save All as Images (PNG) for Photoshop & Word -->
                    <button
                        @click="downloadAllCards"
                        :disabled="isExportingAll || cards.length === 0"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition cursor-pointer active:scale-95 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ isExportingAll ? `Exporting (${exportProgress})...` : 'Save All as Images (PNG)' }}</span>
                    </button>

                    <!-- Print Button -->
                    <button
                        @click="triggerPrint"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition cursor-pointer active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H9v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Print All {{ cards.length }} Cards ({{ sheets.length }} Sheet{{ sheets.length > 1 ? 's' : '' }})</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Workspace -->
        <div class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- LEFT COLUMN: Live Editor & Controls (Hidden during print) -->
                <div class="lg:col-span-5 space-y-4 print:hidden">

                    <!-- Layout & Print Scale Controls -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700">Layout & Sheet Options</h2>
                            <span class="text-xs font-mono font-bold text-blue-600">{{ cardsPerSheet }} Cards / Page</span>
                        </div>

                        <!-- Orientation -->
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between mb-2">
                                <span>Sheet & Card Orientation</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2.5">
                                <button
                                    @click="orientation = 'landscape'"
                                    type="button"
                                    :class="[
                                        orientation === 'landscape'
                                            ? 'bg-blue-600 text-white font-bold shadow-xs'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-medium'
                                    ]"
                                    class="flex items-center justify-center gap-2 rounded-xl py-2 px-3 text-xs transition cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <rect x="2" y="5" width="20" height="14" rx="2" stroke-width="2" />
                                    </svg>
                                    <span>Landscape (ផ្ដេក)</span>
                                </button>

                                <button
                                    @click="orientation = 'portrait'"
                                    type="button"
                                    :class="[
                                        orientation === 'portrait'
                                            ? 'bg-blue-600 text-white font-bold shadow-xs'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-medium'
                                    ]"
                                    class="flex items-center justify-center gap-2 rounded-xl py-2 px-3 text-xs transition cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <rect x="5" y="2" width="14" height="20" rx="2" stroke-width="2" />
                                    </svg>
                                    <span>Portrait (បញ្ឈរ)</span>
                                </button>
                            </div>
                        </div>

                        <!-- A4 Print Scale -->
                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    A4 Page Scale (ទំហំលើក្រដាស A4)
                                </label>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ cardScale }}% Scale
                                </span>
                            </div>
                            <div class="grid grid-cols-4 gap-2">
                                <button
                                    v-for="scaleOption in [75, 80, 85, 95]"
                                    :key="scaleOption"
                                    @click="cardScale = scaleOption"
                                    type="button"
                                    :class="[
                                        cardScale === scaleOption
                                            ? 'bg-blue-600 text-white font-bold shadow-xs ring-2 ring-blue-600/30'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-medium'
                                    ]"
                                    class="rounded-xl py-1.5 text-center text-xs transition cursor-pointer"
                                >
                                    {{ scaleOption }}%
                                </button>
                            </div>
                            <div class="pt-1">
                                <input
                                    v-model.number="cardScale"
                                    type="range"
                                    min="60"
                                    max="100"
                                    step="5"
                                    class="w-full accent-blue-600 cursor-pointer h-1.5 bg-slate-200 rounded-lg appearance-none"
                                />
                            </div>
                            <p class="text-[11px] text-slate-500 leading-tight">
                                ✨ Scale <strong>85%</strong> fits 4 Tuk-Tuk cards on standard A4 paper with neat margins.
                            </p>
                        </div>
                    </div>

                    <!-- Photoshop & Microsoft Word Export Note -->
                    <div class="rounded-2xl bg-emerald-50/80 border border-emerald-200 p-4 text-xs text-emerald-950 space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-base select-none">🎨</span>
                            <h4 class="font-bold text-slate-900">Photoshop & Microsoft Word Export</h4>
                        </div>
                        <p class="text-[11px] text-emerald-800 leading-relaxed">
                            Want to arrange your cards freely in <strong>Photoshop</strong>, <strong>Word</strong>, or <strong>Canva</strong>?
                        </p>
                        <ul class="text-[11px] text-emerald-800 list-disc list-inside space-y-1">
                            <li>Click <strong>Save All as Images</strong> at top to download all cards as high-resolution 300 DPI PNGs.</li>
                            <li>Or hover any card and click <strong>PNG</strong> to download just that card.</li>
                            <li>Insert the images into Word/Photoshop to arrange 5, 8, or any custom quantity per page!</li>
                        </ul>
                    </div>

                    <!-- Quick Language Templates (Identical to Print.vue) -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 space-y-3">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Quick Language Templates
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                @click="applyKhmerPreset"
                                type="button"
                                class="rounded-xl border border-blue-200 bg-blue-50/70 p-2 text-center text-xs font-semibold text-blue-800 hover:bg-blue-100 transition cursor-pointer"
                            >
                                🇰🇭 ភាសាខ្មែរ
                            </button>
                            <button
                                @click="applyBilingualPreset"
                                type="button"
                                class="rounded-xl border border-purple-200 bg-purple-50/70 p-2 text-center text-xs font-semibold text-purple-800 hover:bg-purple-100 transition cursor-pointer"
                            >
                                🇰🇭🇬🇧 ខ្មែរ & English
                            </button>
                            <button
                                @click="applyEnglishPreset"
                                type="button"
                                class="rounded-xl border border-slate-200 bg-slate-50 p-2 text-center text-xs font-semibold text-slate-800 hover:bg-slate-100 transition cursor-pointer"
                            >
                                🇬🇧 English
                            </button>
                        </div>
                    </div>

                    <!-- Content Editor Form -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Edit Content (All Batch Cards)
                        </h3>

                        <!-- Company / Brand Name -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Company / Brand Name</label>
                            <input
                                v-model="brandName"
                                type="text"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-['Kantumruy_Pro',sans-serif]"
                                placeholder="e.g. DIN & CO Translation"
                            />
                        </div>

                        <!-- Headline / Title -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Headline (Title)</label>
                            <textarea
                                v-model="headline"
                                rows="2"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-['Kantumruy_Pro',sans-serif]"
                                placeholder="e.g. ស្កេនដើម្បីបកប្រែឯកសារ"
                            ></textarea>
                        </div>

                        <!-- Services Subtitle -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Services / Tagline</label>
                            <textarea
                                v-model="servicesSubtitle"
                                rows="2"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-['Kantumruy_Pro',sans-serif]"
                                placeholder="e.g. ឯកសារច្បាប់ · ទិដ្ឋាការ · ស្ថានទូត · អាជីវកម្ម"
                            ></textarea>
                        </div>

                        <!-- Call To Action Badge -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Badge Text</label>
                            <input
                                v-model="ctaBadge"
                                type="text"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-['Kantumruy_Pro',sans-serif]"
                                placeholder="e.g. សេវាកម្មរហ័សតាម Telegram"
                            />
                        </div>

                        <!-- Instructions -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Scan Instructions</label>
                            <textarea
                                v-model="instructions"
                                rows="2"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-['Kantumruy_Pro',sans-serif]"
                                placeholder="Instructions for customer..."
                            ></textarea>
                        </div>

                        <!-- Footer -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Address & Contact Info (Footer)
                            </label>
                            <textarea
                                v-model="footerText"
                                rows="2"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-['Kantumruy_Pro',sans-serif]"
                                placeholder="Phnom Penh, Cambodia · Tel: (+855) 12 762 772"
                            ></textarea>
                        </div>

                        <!-- Display Options Checkboxes -->
                        <div class="pt-2 border-t border-slate-100 flex flex-col gap-2">
                            <label v-if="companyLogo" class="inline-flex items-center gap-2 cursor-pointer text-xs text-slate-700">
                                <input
                                    v-model="showLogo"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                />
                                <span>Show Company Logo at top</span>
                            </label>

                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-slate-700">
                                <input
                                    v-model="showDiscreteCode"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                />
                                <span>Show subtle code tag (e.g. REF-TK-001) on sticker</span>
                            </label>

                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-slate-700">
                                <input
                                    v-model="showCutGuides"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                />
                                <span>Show Scissor Cut Guides (បន្ទាត់កាត់ ✂)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: A4 Sheets Preview with Exact QR Card Studio Style -->
                <div class="lg:col-span-7 print:col-span-12 space-y-8 print:space-y-0">
                    <div v-if="cards.length === 0" class="bg-white rounded-2xl p-12 text-center border border-slate-200">
                        <p class="text-sm text-slate-500">No referral codes found for printing.</p>
                        <Link
                            :href="route('referral-codes.index')"
                            class="mt-3 inline-flex items-center text-xs font-semibold text-blue-600 hover:underline"
                        >
                            &larr; Return to Referral Codes list
                        </Link>
                    </div>

                    <!-- Outer Wrapper applying the live preview zoom -->
                    <div
                        v-for="(sheetCards, sIdx) in sheets"
                        :key="sIdx"
                        class="w-full flex flex-col items-center"
                    >
                        <!-- On-screen Sheet Header -->
                        <div class="w-full max-w-[720px] text-xs text-slate-400 mb-2 pb-1.5 border-b border-slate-200 flex items-center justify-between print:hidden">
                            <span class="font-bold text-slate-700">📄 A4 Sheet #{{ sIdx + 1 }} of {{ sheets.length }} ({{ sheetCards.length }} Cards)</span>
                            <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-mono text-[11px] border border-blue-200">
                                Scale: {{ cardScale }}% &middot; {{ orientation }}
                            </span>
                        </div>

                        <!-- The A4 Sheet Container -->
                        <div
                            class="a4-print-sheet w-full bg-white rounded-3xl border-2 border-slate-300 shadow-xl p-5 print:shadow-none print:border-none print:p-0 transition-all font-['Kantumruy_Pro','Figtree',sans-serif]"
                            :style="{ zoom: cardScale / 100 }"
                        >
                            <!-- ============================================================== -->
                            <!-- GRID LAYOUT (1, 2, 4, or 6 cards per A4 page)                  -->
                            <!-- ============================================================== -->
                            <div
                                :class="[
                                    cardsPerSheet === 1 ? 'grid grid-cols-1 gap-6 max-w-[680px] mx-auto py-4' : '',
                                    cardsPerSheet === 2 ? 'grid grid-cols-1 gap-5 max-w-[640px] mx-auto py-2' : '',
                                    cardsPerSheet === 4 ? 'grid grid-cols-2 gap-4' : '',
                                    cardsPerSheet === 6 ? 'grid grid-cols-2 sm:grid-cols-3 gap-3' : '',
                                ]"
                                class="w-full"
                            >
                                <div
                                    v-for="item in sheetCards"
                                    :key="item.referralCode.id"
                                    class="relative"
                                >
                                    <!-- Optional Scissor Cut Badge -->
                                    <span
                                        v-if="showCutGuides && cardsPerSheet > 1"
                                        class="absolute -top-3 -left-1 text-[11px] text-slate-400 print:text-slate-500 font-mono select-none z-10"
                                    >
                                        ✂ - - - - - -
                                    </span>

                                    <!-- ========================================================== -->
                                    <!-- STYLE A: LANDSCAPE CARD                                    -->
                                    <!-- ========================================================== -->
                                    <div
                                        v-if="orientation === 'landscape'"
                                        :id="`card-export-${item.referralCode.id}`"
                                        :class="[
                                            showCutGuides ? 'border-dashed' : 'border-solid',
                                            cardsPerSheet === 1 ? 'p-6 rounded-3xl' : '',
                                            cardsPerSheet === 2 ? 'p-5 rounded-3xl' : '',
                                            cardsPerSheet === 4 ? 'p-3.5 rounded-2xl' : '',
                                            cardsPerSheet === 6 ? 'p-2.5 rounded-xl' : '',
                                        ]"
                                        class="relative w-full bg-white border-2 border-slate-900 shadow-sm text-slate-900 transition-all font-['Kantumruy_Pro','Figtree',sans-serif] print:shadow-none print:border-2 print:border-black flex flex-col justify-between"
                                    >
                                        <!-- Quick Export Single Card as PNG Button -->
                                        <div class="export-exclude absolute top-2 right-2 print:hidden z-20">
                                            <button
                                                @click.stop="downloadSingleCard(item)"
                                                :disabled="exportingCardId === item.referralCode.id"
                                                type="button"
                                                title="Save this card as high-res PNG image (300 DPI) for Photoshop or Word"
                                                class="inline-flex items-center gap-1 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 px-2 py-0.5 text-[10px] font-bold shadow-2xs transition cursor-pointer"
                                            >
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                                <span>{{ exportingCardId === item.referralCode.id ? '...' : 'PNG' }}</span>
                                            </button>
                                        </div>

                                        <!-- Content & QR Row -->
                                        <div class="flex items-center justify-between gap-3 sm:gap-4">
                                            <!-- Left: Brand, Services, Instructions & Address -->
                                            <div
                                                class="flex-1 min-w-0 flex flex-col justify-between text-left"
                                                :class="cardsPerSheet >= 4 ? 'space-y-1.5' : 'space-y-2.5'"
                                            >
                                                <!-- Header with divider line below services -->
                                                <div class="border-b-2 border-slate-900 pb-1.5 sm:pb-2 print:border-black space-y-0.5">
                                                    <div v-if="showLogo && companyLogo" class="mb-1 flex justify-start">
                                                        <img
                                                            :src="companyLogo"
                                                            alt="Logo"
                                                            :class="cardsPerSheet >= 4 ? 'h-4' : 'h-7'"
                                                            class="w-auto object-contain max-w-[120px]"
                                                        />
                                                    </div>
                                                    <div
                                                        :class="[cardsPerSheet >= 4 ? 'text-[8.5px]' : 'text-[10px]']"
                                                        class="uppercase font-extrabold tracking-widest text-slate-600 print:text-black"
                                                    >
                                                        {{ brandName }}
                                                    </div>
                                                    <h2
                                                        :class="[
                                                            cardsPerSheet === 1 ? 'text-lg sm:text-xl' : '',
                                                            cardsPerSheet === 2 ? 'text-base sm:text-lg' : '',
                                                            cardsPerSheet === 4 ? 'text-xs sm:text-sm' : '',
                                                            cardsPerSheet === 6 ? 'text-[11px]' : '',
                                                        ]"
                                                        class="font-black tracking-tight text-slate-900 uppercase whitespace-pre-line leading-tight print:text-black"
                                                    >
                                                        {{ headline }}
                                                    </h2>
                                                    <p
                                                        :class="[cardsPerSheet >= 4 ? 'text-[8.5px]' : 'text-[11px]']"
                                                        class="text-slate-700 mt-0.5 font-medium whitespace-pre-line leading-snug print:text-black"
                                                    >
                                                        {{ servicesSubtitle }}
                                                    </p>
                                                </div>

                                                <!-- CTA Badge & Instructions -->
                                                <div :class="cardsPerSheet >= 4 ? 'space-y-1' : 'space-y-1.5'">
                                                    <div
                                                        :class="[
                                                            cardsPerSheet >= 4 ? 'text-[8px] px-2 py-0.5' : 'text-[10px] px-2.5 py-0.5',
                                                        ]"
                                                        class="inline-flex items-center rounded-full font-bold bg-slate-900 text-white uppercase tracking-wider print:bg-black print:text-white"
                                                    >
                                                        {{ ctaBadge }}
                                                    </div>
                                                    <p
                                                        :class="[
                                                            cardsPerSheet === 1 ? 'text-xs' : '',
                                                            cardsPerSheet === 2 ? 'text-[11px]' : '',
                                                            cardsPerSheet === 4 ? 'text-[9px] line-clamp-3' : '',
                                                            cardsPerSheet === 6 ? 'text-[8px] line-clamp-2' : '',
                                                        ]"
                                                        class="text-slate-700 font-medium whitespace-pre-line leading-tight print:text-black"
                                                    >
                                                        {{ instructions }}
                                                    </p>
                                                </div>

                                                <!-- Address directly underneath instructions -->
                                                <div class="pt-1.5 border-t border-slate-200 print:border-black flex flex-col gap-0.5">
                                                    <p
                                                        :class="[
                                                            cardsPerSheet === 1 ? 'text-xs' : '',
                                                            cardsPerSheet === 2 ? 'text-[11px]' : '',
                                                            cardsPerSheet === 4 ? 'text-[8.5px]' : '',
                                                            cardsPerSheet === 6 ? 'text-[7.5px]' : '',
                                                        ]"
                                                        class="font-medium text-slate-700 print:text-black leading-snug break-words"
                                                    >
                                                        {{ footerText }}
                                                    </p>
                                                    <div
                                                        v-if="showDiscreteCode"
                                                        :class="[cardsPerSheet >= 4 ? 'text-[7.5px]' : 'text-[9px]']"
                                                        class="font-mono font-semibold text-slate-400 tracking-wider print:text-slate-500"
                                                    >
                                                        {{ item.referralCode.code }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Right: QR Code Only (No text underneath) -->
                                            <div
                                                :class="[
                                                    cardsPerSheet === 1 ? 'p-3 rounded-2xl' : '',
                                                    cardsPerSheet === 2 ? 'p-2.5 rounded-2xl' : '',
                                                    cardsPerSheet === 4 ? 'p-2 rounded-xl' : '',
                                                    cardsPerSheet === 6 ? 'p-1.5 rounded-lg' : '',
                                                ]"
                                                class="shrink-0 flex items-center justify-center bg-slate-50 border-2 border-slate-200 shadow-xs print:bg-white print:border-2 print:border-black print:shadow-none"
                                            >
                                                <div
                                                    :class="[
                                                        cardsPerSheet === 1 ? 'w-48 h-48 sm:w-52 sm:h-52' : '',
                                                        cardsPerSheet === 2 ? 'w-36 h-36 sm:w-40 sm:h-40' : '',
                                                        cardsPerSheet === 4 ? 'w-24 h-24 sm:w-28 sm:h-28' : '',
                                                        cardsPerSheet === 6 ? 'w-18 h-18' : '',
                                                    ]"
                                                    class="[&>svg]:w-full [&>svg]:h-full flex items-center justify-center"
                                                    v-html="item.qrSvg"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ========================================================== -->
                                    <!-- STYLE B: PORTRAIT CARD                                     -->
                                    <!-- ========================================================== -->
                                    <div
                                        v-else
                                        :id="`card-export-${item.referralCode.id}`"
                                        :class="[
                                            showCutGuides ? 'border-dashed' : 'border-solid',
                                            cardsPerSheet === 1 ? 'p-7 rounded-3xl' : '',
                                            cardsPerSheet === 2 ? 'p-5 rounded-3xl' : '',
                                            cardsPerSheet === 4 ? 'p-4 rounded-2xl' : '',
                                            cardsPerSheet === 6 ? 'p-2.5 rounded-xl' : '',
                                        ]"
                                        class="relative w-full bg-white border-2 border-slate-900 shadow-sm text-center text-slate-900 transition-all font-['Kantumruy_Pro','Figtree',sans-serif] print:shadow-none print:border-2 print:border-black flex flex-col justify-between"
                                    >
                                        <!-- Quick Export Single Card as PNG Button -->
                                        <div class="export-exclude absolute top-2 right-2 print:hidden z-20">
                                            <button
                                                @click.stop="downloadSingleCard(item)"
                                                :disabled="exportingCardId === item.referralCode.id"
                                                type="button"
                                                title="Save this card as high-res PNG image (300 DPI) for Photoshop or Word"
                                                class="inline-flex items-center gap-1 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 px-2 py-0.5 text-[10px] font-bold shadow-2xs transition cursor-pointer"
                                            >
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                                <span>{{ exportingCardId === item.referralCode.id ? '...' : 'PNG' }}</span>
                                            </button>
                                        </div>

                                        <!-- Top Header / Brand with divider below services -->
                                        <div class="pb-2 border-b-2 border-slate-900 print:border-black">
                                            <div v-if="showLogo && companyLogo" class="mb-1.5 flex justify-center">
                                                <img
                                                    :src="companyLogo"
                                                    alt="Logo"
                                                    :class="cardsPerSheet >= 4 ? 'h-6' : 'h-9'"
                                                    class="w-auto object-contain max-w-[140px]"
                                                />
                                            </div>
                                            <div
                                                :class="[cardsPerSheet >= 4 ? 'text-[8.5px]' : 'text-xs']"
                                                class="uppercase font-extrabold tracking-widest text-slate-600 print:text-black"
                                            >
                                                {{ brandName }}
                                            </div>
                                            <h2
                                                :class="[
                                                    cardsPerSheet === 1 ? 'text-xl sm:text-2xl' : '',
                                                    cardsPerSheet === 2 ? 'text-lg sm:text-xl' : '',
                                                    cardsPerSheet === 4 ? 'text-xs sm:text-sm' : '',
                                                    cardsPerSheet === 6 ? 'text-[11px]' : '',
                                                ]"
                                                class="font-black tracking-tight text-slate-900 mt-1 uppercase whitespace-pre-line leading-tight print:text-black"
                                            >
                                                {{ headline }}
                                            </h2>
                                            <p
                                                :class="[cardsPerSheet >= 4 ? 'text-[8.5px]' : 'text-xs']"
                                                class="text-slate-700 mt-0.5 font-medium whitespace-pre-line leading-relaxed print:text-black"
                                            >
                                                {{ servicesSubtitle }}
                                            </p>
                                        </div>

                                        <!-- Big Vector QR Code Container (No text underneath) -->
                                        <div
                                            :class="[
                                                cardsPerSheet === 1 ? 'my-4 p-3.5' : '',
                                                cardsPerSheet === 2 ? 'my-3 p-3' : '',
                                                cardsPerSheet >= 4 ? 'my-2 p-2' : '',
                                            ]"
                                            class="bg-slate-50 rounded-2xl border-2 border-slate-200 flex items-center justify-center shadow-xs print:bg-white print:border-2 print:border-black print:shadow-none"
                                        >
                                            <div
                                                :class="[
                                                    cardsPerSheet === 1 ? 'w-56 h-56' : '',
                                                    cardsPerSheet === 2 ? 'w-40 h-40' : '',
                                                    cardsPerSheet === 4 ? 'w-24 h-24 sm:w-28 sm:h-28' : '',
                                                    cardsPerSheet === 6 ? 'w-18 h-18' : '',
                                                ]"
                                                class="mx-auto [&>svg]:w-full [&>svg]:h-full flex items-center justify-center"
                                                v-html="item.qrSvg"
                                            ></div>
                                        </div>

                                        <!-- Call to Action & Telegram Instruction -->
                                        <div :class="cardsPerSheet >= 4 ? 'space-y-1' : 'space-y-1.5'">
                                            <div
                                                :class="[cardsPerSheet >= 4 ? 'text-[8px] px-2.5 py-0.5' : 'text-xs px-3.5 py-1']"
                                                class="inline-flex items-center rounded-full font-bold bg-slate-900 text-white uppercase tracking-wider print:bg-black print:text-white"
                                            >
                                                {{ ctaBadge }}
                                            </div>
                                            <p
                                                :class="[cardsPerSheet >= 4 ? 'text-[8.5px] px-1 line-clamp-2' : 'text-xs px-2']"
                                                class="text-slate-700 font-medium whitespace-pre-line leading-relaxed print:text-black"
                                            >
                                                {{ instructions }}
                                            </p>
                                        </div>

                                        <!-- Clean Footer: Address directly underneath instructions -->
                                        <div class="mt-3 pt-2 border-t border-slate-200 print:border-black flex flex-col items-center gap-0.5">
                                            <p
                                                :class="[cardsPerSheet >= 4 ? 'text-[8.5px]' : 'text-[11px]']"
                                                class="font-medium text-slate-700 print:text-black leading-snug break-words text-center"
                                            >
                                                {{ footerText }}
                                            </p>
                                            <div
                                                v-if="showDiscreteCode"
                                                :class="[cardsPerSheet >= 4 ? 'text-[7.5px]' : 'text-[9px]']"
                                                class="font-mono font-semibold text-slate-400 tracking-wider print:text-slate-500"
                                            >
                                                {{ item.referralCode.code }}
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
