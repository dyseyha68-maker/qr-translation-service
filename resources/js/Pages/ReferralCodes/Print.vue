<script setup lang="ts">
import { ReferralCode } from '@/types';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    referralCode: ReferralCode;
    telegramDeepLink: string;
    qrSvg: string;
}>();

function triggerPrint() {
    window.print();
}
</script>

<template>
    <Head :title="`Print QR - ${referralCode.code}`" />

    <div class="min-h-screen bg-slate-100 p-4 sm:p-8 flex flex-col items-center justify-center font-sans print:bg-white print:p-0">
        <!-- Floating Print Toolbar (Hidden during print) -->
        <div class="mb-6 flex items-center gap-4 print:hidden">
            <button
                @click="triggerPrint"
                type="button"
                class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-blue-500 transition"
            >
                &#128438; Print / Save PDF
            </button>
            <button
                onclick="window.close()"
                type="button"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
            >
                Close Window
            </button>
        </div>

        <!-- Printable Card Container -->
        <div class="w-full max-w-md bg-white rounded-2xl border-2 border-slate-900 shadow-xl p-8 text-center text-slate-900 print:shadow-none print:border-2 print:border-black print:max-w-none print:w-[360px] print:mx-auto">
            <!-- Header / Brand -->
            <div class="border-b-2 border-slate-900 pb-4">
                <h1 class="text-xs uppercase font-extrabold tracking-widest text-slate-500">
                    Cambodia Translation Services
                </h1>
                <h2 class="text-2xl font-black tracking-tight text-slate-900 mt-1 uppercase">
                    Scan for Translation
                </h2>
                <p class="text-xs text-slate-600 mt-1 font-medium">
                    Legal &middot; Visa &middot; Business &middot; Documents
                </p>
            </div>

            <!-- Big Vector QR Code Container -->
            <div class="my-6 p-4 bg-white rounded-xl border border-slate-300 flex items-center justify-center print:border-none print:p-2">
                <div class="w-64 h-64 mx-auto [&>svg]:w-full [&>svg]:h-full" v-html="qrSvg"></div>
            </div>

            <!-- Call to action & Telegram instruction -->
            <div class="space-y-1">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-900 text-white uppercase tracking-wider print:bg-black">
                    Telegram Direct Service
                </div>
                <p class="text-xs text-slate-600 pt-2 font-medium">
                    Open your phone camera or Telegram app and scan this code to begin your translation inquiry.
                </p>
            </div>

            <!-- Referral Identification Block -->
            <div class="mt-6 pt-4 border-t-2 border-slate-900 grid grid-cols-2 gap-2 text-left text-xs bg-slate-50 p-3 rounded-lg border border-slate-200 print:bg-white print:border-black">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Driver</span>
                    <span class="font-bold text-slate-900 truncate block">
                        {{ referralCode.driver?.name || 'Driver' }}
                    </span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Vehicle Plate</span>
                    <span class="font-mono font-bold text-slate-900 truncate block">
                        {{ referralCode.vehicle?.plate_number || '—' }}
                    </span>
                </div>
                <div class="col-span-2 pt-2 border-t border-slate-200 print:border-black flex justify-between items-center">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Referral ID</span>
                    <span class="font-mono font-black text-sm text-slate-900 tracking-wider">
                        {{ referralCode.code }}
                    </span>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-4 text-[10px] text-slate-400 font-medium">
                Phnom Penh, Cambodia &middot; Fast, Certified Translation
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    body {
        background: white !important;
    }
}
</style>
