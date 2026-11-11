<script setup lang="ts">
import { ReferralCode } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { toPng } from 'html-to-image';
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        referralCode: ReferralCode;
        telegramDeepLink: string;
        qrSvg: string;
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

// Orientation toggle: 'landscape' | 'portrait' (defaulting to landscape as requested)
const orientation = ref<'portrait' | 'landscape'>('landscape');

// Scale percentage for A4 paper (default 80% as requested)
const cardScale = ref<number>(80);

// Reactive editable fields for live card customization before printing
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

// Quick presets for Khmer, Bilingual, and English
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

const isExportingImage = ref(false);

async function downloadAsImage() {
    const cardEl = document.getElementById('printable-card');
    if (!cardEl) return;

    isExportingImage.value = true;
    try {
        const dataUrl = await toPng(cardEl, {
            pixelRatio: 3, // Ultra-crisp 300 DPI export
            backgroundColor: '#ffffff',
            cacheBust: true,
        });

        const link = document.createElement('a');
        link.download = `DIN-CO-QR-Card-${props.referralCode.code}.png`;
        link.href = dataUrl;
        link.click();
    } catch (err) {
        console.error('Failed to export card image:', err);
    } finally {
        isExportingImage.value = false;
    }
}
</script>

<template>
    <Head :title="`Print QR (${orientation} - Scale ${cardScale}%) - ${referralCode.code}`" />

    <!-- Dynamic Print Page Size & Scale Styles for A4 -->
    <component :is="'style'">
        @media print {
            @page {
                size: A4 {{ orientation }};
                margin: 0.8cm;
            }
            body {
                background-color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            #printable-card {
                zoom: {{ cardScale / 100 }} !important;
                margin: 0 auto !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </component>

    <div class="min-h-screen bg-slate-100 font-sans print:bg-white print:p-0">
        <!-- Main Wrapper -->
        <div class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- LEFT COLUMN: Live Editor & Controls (Hidden during print) -->
                <div class="lg:col-span-5 space-y-5 print:hidden">
                    <!-- Action Bar Header -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">QR Card Studio</h2>
                                <p class="text-xs text-slate-500">A4 Scale: {{ cardScale }}% · {{ orientation === 'landscape' ? 'Landscape (ផ្ដេក)' : 'Portrait (បញ្ឈរ)' }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-xs font-mono font-bold text-blue-700 border border-blue-200">
                                {{ referralCode.code }}
                            </span>
                        </div>

                        <!-- Print & Navigation Actions -->
                        <div class="flex flex-wrap gap-2.5 pt-1">
                            <button
                                @click="triggerPrint"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition cursor-pointer active:scale-95"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H9v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>Print / Save PDF</span>
                            </button>

                            <button
                                @click="downloadAsImage"
                                :disabled="isExportingImage"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition cursor-pointer active:scale-95 disabled:opacity-50"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ isExportingImage ? 'Exporting Image...' : 'Save as Image (PNG)' }}</span>
                            </button>

                            <Link
                                :href="route('referral-codes.index')"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                            >
                                &larr; Back to Codes
                            </Link>

                            <button
                                onclick="window.close()"
                                type="button"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-medium text-slate-600 hover:bg-slate-100 transition"
                            >
                                Close
                            </button>
                        </div>

                        <!-- Photoshop & Word Info Note -->
                        <div class="rounded-xl bg-emerald-50/80 border border-emerald-200 p-3 text-xs text-emerald-900 flex items-start gap-2.5">
                            <span class="text-base select-none">🎨</span>
                            <div class="space-y-0.5">
                                <p class="font-bold">Photoshop & Word Ready</p>
                                <p class="text-[11px] text-emerald-700 leading-snug">
                                    Click <strong>Save as Image (PNG)</strong> to export this complete card in high-resolution (300 DPI). You can open or drag it directly into Photoshop, Microsoft Word, or Canva to arrange and scale freely!
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Layout Orientation & A4 Scale Controls -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 space-y-4">
                        <!-- Orientation -->
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between mb-2">
                                <span>Card Orientation</span>
                                <span class="text-[11px] font-normal text-slate-400">Layout style</span>
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
                                    class="flex items-center justify-center gap-2 rounded-xl py-2.5 px-3 text-xs transition cursor-pointer"
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
                                    class="flex items-center justify-center gap-2 rounded-xl py-2.5 px-3 text-xs transition cursor-pointer"
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
                                    v-for="scaleOption in [70, 80, 90, 100]"
                                    :key="scaleOption"
                                    @click="cardScale = scaleOption"
                                    type="button"
                                    :class="[
                                        cardScale === scaleOption
                                            ? 'bg-blue-600 text-white font-bold shadow-xs ring-2 ring-blue-600/30'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-medium'
                                    ]"
                                    class="rounded-xl py-2 text-center text-xs transition cursor-pointer"
                                >
                                    {{ scaleOption }}%
                                </button>
                            </div>
                            <div class="pt-1">
                                <input
                                    v-model.number="cardScale"
                                    type="range"
                                    min="50"
                                    max="100"
                                    step="5"
                                    class="w-full accent-blue-600 cursor-pointer h-1.5 bg-slate-200 rounded-lg appearance-none"
                                />
                            </div>
                            <p class="text-[11px] text-slate-500 leading-tight">
                                ✨ <strong>Scale 80%</strong> is set by default. It prints with optimal margins on standard A4 paper without overflowing.
                            </p>
                        </div>
                    </div>

                    <!-- Quick Language Templates -->
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

                    <!-- Editable Fields Form -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Edit Content (Support Khmer & English)
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

                        <!-- Display Checkboxes -->
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
                                <span>Show subtle code tag ({{ referralCode.code }}) at bottom</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Live Printable Card Preview -->
                <div class="lg:col-span-7 flex flex-col items-center justify-center print:col-span-12 print:block">
                    <div class="text-xs text-slate-400 mb-2 font-medium print:hidden flex items-center gap-2">
                        <span>Live Preview ({{ orientation === 'landscape' ? 'Landscape ផ្ដេក' : 'Portrait បញ្ឈរ' }} · Scale: {{ cardScale }}%):</span>
                    </div>

                    <!-- Outer Wrapper applying the live preview zoom -->
                    <div
                        class="w-full flex justify-center transition-all duration-200"
                        :style="{ zoom: cardScale / 100 }"
                    >
                        <!-- ============================================== -->
                        <!-- OPTION 1: LANDSCAPE CARD LAYOUT                   -->
                        <!-- ============================================== -->
                        <div
                            v-if="orientation === 'landscape'"
                            id="printable-card"
                            class="w-full max-w-[680px] bg-white rounded-3xl border-2 border-slate-900 shadow-2xl p-6 text-slate-900 transition-all font-['Kantumruy_Pro','Figtree',sans-serif] print:shadow-none print:border-2 print:border-black print:max-w-none print:w-[640px] print:mx-auto print:rounded-2xl print:p-5 flex flex-col justify-between"
                        >
                            <div class="flex items-center justify-between gap-6">
                                <!-- LEFT COLUMN: Brand, Services, Instructions & Address -->
                                <div class="flex-1 min-w-0 flex flex-col justify-between text-left space-y-3">
                                    <!-- Top Section: Brand, Headline & Services with divider below -->
                                    <div class="border-b-2 border-slate-900 pb-2.5 print:border-black space-y-1">
                                        <div v-if="showLogo && companyLogo" class="mb-1 flex justify-start">
                                            <img :src="companyLogo" alt="Logo" class="h-8 w-auto object-contain max-w-[140px]" />
                                        </div>
                                        <h1 class="text-xs uppercase font-extrabold tracking-widest text-slate-600 print:text-black">
                                            {{ brandName }}
                                        </h1>
                                        <h2 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 mt-0.5 uppercase whitespace-pre-line leading-tight print:text-black">
                                            {{ headline }}
                                        </h2>
                                        <p class="text-xs text-slate-700 mt-1 font-medium whitespace-pre-line leading-relaxed print:text-black">
                                            {{ servicesSubtitle }}
                                        </p>
                                    </div>

                                    <!-- Middle Section: CTA Badge & Scan Instructions -->
                                    <div class="space-y-1.5">
                                        <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-900 text-white uppercase tracking-wider print:bg-black print:text-white">
                                            {{ ctaBadge }}
                                        </div>
                                        <p class="text-xs text-slate-700 font-medium whitespace-pre-line leading-relaxed print:text-black">
                                            {{ instructions }}
                                        </p>
                                    </div>

                                    <!-- Address & Contact Info: Placed directly below Instructions -->
                                    <div class="pt-2 border-t border-slate-200 print:border-black flex flex-col gap-0.5">
                                        <p class="text-xs text-slate-700 font-medium leading-snug break-words print:text-black">
                                            {{ footerText }}
                                        </p>
                                        <div v-if="showDiscreteCode" class="text-[9px] font-mono font-semibold text-slate-400 tracking-wider print:text-slate-500">
                                            {{ referralCode.code }}
                                        </div>
                                    </div>
                                </div>

                                <!-- RIGHT COLUMN: QR Code only (No text underneath) -->
                                <div class="shrink-0 flex items-center justify-center p-3 bg-slate-50 rounded-2xl border-2 border-slate-200 shadow-xs print:bg-white print:border-2 print:border-black print:shadow-none print:p-2">
                                    <div class="w-52 h-52 sm:w-56 sm:h-56 [&>svg]:w-full [&>svg]:h-full flex items-center justify-center" v-html="qrSvg"></div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================== -->
                        <!-- OPTION 2: PORTRAIT CARD LAYOUT                    -->
                        <!-- ============================================== -->
                        <div
                            v-else
                            id="printable-card"
                            class="w-full max-w-[380px] bg-white rounded-3xl border-2 border-slate-900 shadow-2xl p-7 text-center text-slate-900 transition-all font-['Kantumruy_Pro','Figtree',sans-serif] print:shadow-none print:border-2 print:border-black print:max-w-none print:w-[350px] print:mx-auto print:rounded-2xl print:p-6"
                        >
                            <!-- Top Header / Brand with divider below Services -->
                            <div class="border-b-2 border-slate-900 pb-3 print:border-black space-y-1">
                                <div v-if="showLogo && companyLogo" class="mb-2 flex justify-center">
                                    <img :src="companyLogo" alt="Logo" class="h-10 w-auto object-contain max-w-[160px]" />
                                </div>
                                <h1 class="text-xs uppercase font-extrabold tracking-widest text-slate-600 print:text-black">
                                    {{ brandName }}
                                </h1>
                                <h2 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 mt-1 uppercase whitespace-pre-line leading-tight print:text-black">
                                    {{ headline }}
                                </h2>
                                <p class="text-xs text-slate-700 mt-1 font-medium whitespace-pre-line leading-relaxed print:text-black">
                                    {{ servicesSubtitle }}
                                </p>
                            </div>

                            <!-- Big Vector QR Code Container (No text underneath) -->
                            <div class="my-5 p-3.5 bg-slate-50 rounded-2xl border-2 border-slate-200 flex items-center justify-center shadow-xs print:bg-white print:border-2 print:border-black print:shadow-none print:p-2">
                                <div class="w-60 h-60 mx-auto [&>svg]:w-full [&>svg]:h-full flex items-center justify-center" v-html="qrSvg"></div>
                            </div>

                            <!-- Call to Action & Telegram Instruction -->
                            <div class="space-y-1.5">
                                <div class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-slate-900 text-white uppercase tracking-wider print:bg-black print:text-white">
                                    {{ ctaBadge }}
                                </div>
                                <p class="text-xs text-slate-700 font-medium px-2 whitespace-pre-line leading-relaxed print:text-black">
                                    {{ instructions }}
                                </p>
                            </div>

                            <!-- Clean Footer: Address directly below Instructions -->
                            <div class="mt-4 pt-3 border-t border-slate-200 print:border-black flex flex-col items-center gap-0.5">
                                <p class="text-xs text-slate-700 font-medium leading-snug break-words print:text-black">
                                    {{ footerText }}
                                </p>
                                <div v-if="showDiscreteCode" class="text-[9px] font-mono font-semibold text-slate-400 tracking-wider print:text-slate-500">
                                    {{ referralCode.code }}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</template>
