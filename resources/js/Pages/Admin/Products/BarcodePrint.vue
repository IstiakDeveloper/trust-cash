<template>
    <div class="min-h-screen bg-slate-100 p-4 sm:p-6 dark:bg-slate-900 print:bg-white print:p-0">
        <!-- Top Toolbar & Settings (Hidden during print) -->
        <div class="mx-auto max-w-5xl mb-6 space-y-4 print:hidden">
            <!-- Main Action Bar -->
            <div class="rounded-2xl bg-white p-4 shadow-sm border border-slate-200 dark:bg-slate-800 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                        <QrCodeIcon class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-base font-bold text-slate-900 dark:text-white">
                                {{ t('বারকোড প্রিন্ট প্রিভিউ', 'Barcode Print Preview') }}
                            </h1>
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                {{ currentSizeLabel }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ t('মোট পণ্য:', 'Products:') }} <span class="font-bold text-slate-800 dark:text-slate-200">{{ formatNumber(products.length) }}</span> | 
                            {{ t('মোট স্টিকার লেবেল:', 'Total Stickers:') }} <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ formatNumber(totalLabels) }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Quick Paper Size Dropdown -->
                    <div class="flex items-center gap-1.5">
                        <label class="text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">
                            {{ t('সাইজ:', 'Size:') }}
                        </label>
                        <select
                            v-model="selectedPaperSize"
                            class="rounded-xl border border-slate-300 bg-white py-1.5 pl-3 pr-8 text-xs font-bold text-slate-800 shadow-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                        >
                            <option value="38x25mm">{{ t('৩৮ × ২৫ মিমি (১-কলাম থার্মাল স্টিকার)', '38mm x 25mm (Single Thermal)') }}</option>
                            <option value="38x25mm_2col">{{ t('৩৮ × ২৫ মিমি (২-কলাম রোল / ৮০ মিমি)', '38mm x 25mm (2-Col on 80mm)') }}</option>
                            <option value="40x30mm">{{ t('৪০ × ৩০ মিমি থার্মাল স্টিকার', '40mm x 30mm Thermal') }}</option>
                            <option value="50x25mm">{{ t('৫০ × ২৫ মিমি (২" × ১" থার্মাল)', '50mm x 25mm Thermal') }}</option>
                            <option value="50x30mm">{{ t('৫০ × ৩০ মিমি থার্মাল স্টিকার', '50mm x 30mm Thermal') }}</option>
                            <option value="80mm">{{ t('৮০ মিমি থার্মাল রোল (Continuous Roll)', '80mm Continuous Roll') }}</option>
                            <option value="A4">{{ t('A4 শিট (মাল্টি-কলাম গ্রিড)', 'A4 Multi-label Sheet') }}</option>
                            <option value="Letter">{{ t('Letter সাইজ শিট', 'Letter Sheet') }}</option>
                            <option value="custom">{{ t('✨ কাস্টম সাইজ (Custom mm - নিজের সাইজ দিন)', '✨ Custom Size (Enter Custom mm)') }}</option>
                        </select>
                    </div>

                    <!-- Customize Settings Toggle -->
                    <button
                        type="button"
                        @click="showSettings = !showSettings"
                        :class="['inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-bold transition-all cursor-pointer', showSettings ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700']"
                    >
                        <AdjustmentsHorizontalIcon class="h-4 w-4" />
                        <span>{{ t('কাস্টমাইজ', 'Customize') }}</span>
                    </button>

                    <!-- Printer Tips Toggle -->
                    <button
                        type="button"
                        @click="showGuide = !showGuide"
                        :class="['inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-bold transition-all cursor-pointer', showGuide ? 'border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700']"
                    >
                        <InformationCircleIcon class="h-4 w-4" />
                        <span>{{ t('প্রিন্টার গাইড', 'Print Tips') }}</span>
                    </button>

                    <!-- Print Button -->
                    <button
                        type="button"
                        @click="triggerPrint"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition-all cursor-pointer"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        <span>{{ t('এখনই প্রিন্ট করুন', 'Print Now') }}</span>
                    </button>

                    <!-- Back to Products -->
                    <Link
                        :href="route('admin.products.index')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-all"
                    >
                        <ArrowLeftIcon class="h-4 w-4" />
                        <span class="hidden sm:inline">{{ t('পণ্য তালিকা', 'Products') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Custom Size Inputs & Quick mm Presets (When selectedPaperSize === 'custom') -->
            <div v-if="selectedPaperSize === 'custom'" class="rounded-2xl bg-white p-4 shadow-sm border border-indigo-200 dark:bg-slate-800 dark:border-indigo-900/50 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-400 font-black text-xs">
                            mm
                        </span>
                        <div>
                            <span class="font-bold text-slate-900 dark:text-white">
                                {{ t('কাস্টম সাইজ নির্ধারণ (মিমি):', 'Custom Dimensions (mm):') }}
                            </span>
                            <span class="ml-2 rounded-md bg-indigo-50 px-2 py-0.5 text-indigo-700 font-bold dark:bg-indigo-950 dark:text-indigo-300">
                                {{ customWidth }}mm × {{ customHeight }}mm
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <label class="font-bold text-slate-700 dark:text-slate-300">{{ t('প্রস্থ (Width):', 'Width:') }}</label>
                            <input
                                type="number"
                                v-model.number="customWidth"
                                min="15"
                                max="250"
                                class="w-18 rounded-lg border border-slate-300 bg-white py-1 px-2 text-center font-bold text-slate-900 focus:ring-1 focus:ring-indigo-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            />
                            <span class="text-slate-400 font-bold">mm</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <label class="font-bold text-slate-700 dark:text-slate-300">{{ t('উচ্চতা (Height):', 'Height:') }}</label>
                            <input
                                type="number"
                                v-model.number="customHeight"
                                min="10"
                                max="250"
                                class="w-18 rounded-lg border border-slate-300 bg-white py-1 px-2 text-center font-bold text-slate-900 focus:ring-1 focus:ring-indigo-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            />
                            <span class="text-slate-400 font-bold">mm</span>
                        </div>
                    </div>
                </div>

                <!-- Quick mm Preset Buttons -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-700/60 flex flex-wrap items-center gap-1.5 text-[11px]">
                    <span class="text-slate-400 font-medium mr-1">{{ t('দ্রুত সাইজ বাছুন:', 'Quick mm Presets:') }}</span>
                    <button
                        v-for="p in commonMmPresets"
                        :key="`${p.w}x${p.h}`"
                        type="button"
                        @click="setCustomPreset(p.w, p.h)"
                        :class="['px-2.5 py-0.5 rounded-lg font-bold border transition-all cursor-pointer', (customWidth === p.w && customHeight === p.h) ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 dark:bg-slate-700/50 dark:text-slate-200 dark:border-slate-600']"
                    >
                        {{ p.w }}×{{ p.h }} mm
                    </button>
                </div>
            </div>

            <!-- Customization Settings Panel (Expandable) -->
            <div v-if="showSettings" class="rounded-2xl bg-white p-4 shadow-sm border border-slate-200 dark:bg-slate-800 dark:border-slate-700 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        {{ t('লেবেল ডিজাইন ও দৃশ্যমান সেটিংস', 'Label Content & Design Settings') }}
                    </h3>
                    <button type="button" @click="showSettings = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                    <!-- Store Name Toggle & Input -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" v-model="showStoreName" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <span>{{ t('শপের নাম দেখান', 'Show Shop Name') }}</span>
                        </label>
                        <input
                            v-if="showStoreName"
                            type="text"
                            v-model="storeName"
                            :placeholder="t('শপের নাম লিখুন...', 'Store Name...')"
                            class="w-full rounded-lg border-slate-300 bg-white py-1 px-2 text-xs font-medium dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                        />
                    </div>

                    <!-- Product Name Toggle -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" v-model="showProductName" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <span>{{ t('পণ্যের নাম দেখান', 'Show Product Name') }}</span>
                        </label>
                        <p class="text-[10px] text-slate-400">
                            {{ t('স্টিকারে ফিট করার জন্য স্বয়ংক্রিয়ভাবে সংক্ষেপিত হবে', 'Auto-truncated to fit sticker width') }}
                        </p>
                    </div>

                    <!-- Barcode Code Toggle -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" v-model="showBarcodeText" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <span>{{ t('বারকোড কোড (নম্বর) দেখান', 'Show Barcode Number') }}</span>
                        </label>
                        <p class="text-[10px] text-slate-400">
                            {{ t('বারকোড ইমেজের নিচে কোড নম্বর প্রিন্ট হবে', 'Prints numeric code below barcode') }}
                        </p>
                    </div>

                    <!-- Price Toggle & Prefix -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" v-model="showPrice" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <span>{{ t('বিক্রয় মূল্য দেখান', 'Show Selling Price') }}</span>
                        </label>
                        <div v-if="showPrice" class="flex items-center gap-1.5">
                            <span class="text-[11px] text-slate-500">{{ t('প্রিফিক্স:', 'Prefix:') }}</span>
                            <input
                                type="text"
                                v-model="pricePrefix"
                                placeholder="MRP: ৳ "
                                class="w-full rounded-lg border-slate-300 bg-white py-1 px-2 text-xs font-bold dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                            />
                        </div>
                    </div>

                    <!-- Label Border Toggle -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" v-model="showBorder" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <span>{{ t('প্রিন্টে বর্ডার দেখান', 'Print Label Border') }}</span>
                        </label>
                        <p class="text-[10px] text-slate-400">
                            {{ t('থার্মাল রোলের জন্য সাধারণত বর্ডার আনচেক রাখা ভালো', 'Recommended OFF for pre-cut thermal labels') }}
                        </p>
                    </div>

                    <!-- Screen Zoom Control -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="block font-bold text-slate-800 dark:text-slate-200">
                            {{ t('স্ক্রিন প্রিভিউ জুম', 'Screen Preview Zoom') }}
                        </label>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="previewScale = 1"
                                :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', previewScale === 1 ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200']"
                            >
                                100% (Real)
                            </button>
                            <button
                                type="button"
                                @click="previewScale = 1.3"
                                :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', previewScale === 1.3 ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200']"
                            >
                                130%
                            </button>
                            <button
                                type="button"
                                @click="previewScale = 1.6"
                                :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', previewScale === 1.6 ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200']"
                            >
                                160%
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thermal Printer Instruction Guide (Expandable) -->
            <div v-if="showGuide" class="rounded-2xl bg-amber-50/80 p-4 shadow-sm border border-amber-200 dark:bg-amber-950/30 dark:border-amber-900/50">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-2 text-xs text-amber-950 dark:text-amber-200">
                        <div class="flex items-center gap-2 font-bold text-sm text-amber-900 dark:text-amber-300">
                            <InformationCircleIcon class="h-5 w-5 text-amber-600 shrink-0" />
                            <span>{{ t('থার্মাল বারকোড প্রিন্টার নিখুঁতভাবে প্রিন্ট করার গাইড', 'Thermal Barcode Printer Perfect Print Guide') }}</span>
                        </div>
                        <p class="text-amber-800 dark:text-amber-300">
                            {{ t('ব্রাউজারের প্রিন্ট ডায়ালগ (Ctrl + P) ওপেন হলে নিচের সেটিংসগুলো ঠিক রাখুন:', 'When browser print dialog opens, set the following options:') }}
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 pt-1 font-medium">
                            <div class="bg-white/80 dark:bg-slate-900/60 p-2.5 rounded-xl border border-amber-200/60 dark:border-amber-800/40">
                                <span class="font-bold text-amber-900 dark:text-amber-200">১. Destination (প্রিন্টার):</span>
                                <p class="text-slate-600 dark:text-slate-300 mt-0.5">আপনার থার্মাল প্রিন্টার (যেমন: Xprinter, Rongta, TSC, Zebra) সিলেক্ট করুন।</p>
                            </div>
                            <div class="bg-white/80 dark:bg-slate-900/60 p-2.5 rounded-xl border border-amber-200/60 dark:border-amber-800/40">
                                <span class="font-bold text-amber-900 dark:text-amber-200">২. Paper Size (কাগজের সাইজ):</span>
                                <p class="text-slate-600 dark:text-slate-300 mt-0.5">প্রিন্টারে 38mm x 25mm অথবা আপনার কাস্টম সাইজ সিলেক্ট করুন।</p>
                            </div>
                            <div class="bg-white/80 dark:bg-slate-900/60 p-2.5 rounded-xl border border-amber-200/60 dark:border-amber-800/40">
                                <span class="font-bold text-amber-900 dark:text-amber-200">৩. Margins (মার্জিন):</span>
                                <p class="text-emerald-700 dark:text-emerald-400 font-bold mt-0.5">"None" (কোনো মার্জিন নয়) সিলেক্ট করুন। ডিফল্ট মার্জিন রাখবেন না।</p>
                            </div>
                            <div class="bg-white/80 dark:bg-slate-900/60 p-2.5 rounded-xl border border-amber-200/60 dark:border-amber-800/40">
                                <span class="font-bold text-amber-900 dark:text-amber-200">৪. Scale & Headers:</span>
                                <p class="text-slate-600 dark:text-slate-300 mt-0.5">Scale: 100% (বা Default), এবং "Headers and Footers" আনচেক রাখুন।</p>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="showGuide = false" class="text-amber-500 hover:text-amber-700">
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Barcode Sheet & Stickers Area -->
        <div class="mx-auto max-w-5xl print:max-w-none print:w-full print:m-0 flex justify-center">
            <div
                :class="['barcode-container', currentModeClass]"
                :style="previewContainerStyle"
            >
                <template v-for="product in products" :key="product.id">
                    <template v-for="copy in (product.copies || 1)" :key="`${product.id}-${copy}`">
                        <div
                            :class="[
                                'label-wrapper',
                                currentSizeClass,
                                showBorder ? 'has-print-border' : 'no-print-border'
                            ]"
                            :style="customLabelStyle"
                        >
                            <div class="label-content">
                                <!-- Store Name -->
                                <div
                                    v-if="showStoreName && storeName"
                                    class="lbl-store"
                                    :style="customStoreStyle"
                                    :title="storeName"
                                >
                                    {{ storeName }}
                                </div>

                                <!-- Product Name -->
                                <div
                                    v-if="showProductName"
                                    class="lbl-name"
                                    :style="customNameStyle"
                                    :title="product.name"
                                >
                                    {{ truncate(product.name, maxNameLength) }}
                                </div>

                                <!-- Barcode Image (Crisp SVG) -->
                                <div class="lbl-barcode-wrapper">
                                    <img
                                        :src="product.image_url"
                                        :alt="product.barcode"
                                        class="lbl-barcode-img"
                                        :style="customBarcodeStyle"
                                    />
                                </div>

                                <!-- Barcode Number / Code -->
                                <div
                                    v-if="showBarcodeText"
                                    class="lbl-code"
                                    :style="customCodeStyle"
                                >
                                    {{ product.barcode }}
                                </div>

                                <!-- Price -->
                                <div
                                    v-if="showPrice"
                                    class="lbl-price"
                                    :style="customPriceStyle"
                                >
                                    <span>{{ pricePrefix }}</span>{{ formatPrice(product.price) }}
                                </div>
                            </div>
                        </div>
                    </template>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
    PrinterIcon,
    ArrowLeftIcon,
    QrCodeIcon,
    AdjustmentsHorizontalIcon,
    InformationCircleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'
import { useLanguage } from '@/composables/useLanguage'

const { t, formatNumber } = useLanguage()
const page = usePage()

const props = defineProps({
    products: {
        type: Array,
        required: true,
        default: () => []
    },
    paperSize: {
        type: String,
        default: '38x25mm'
    },
    initialCustomWidth: {
        type: Number,
        default: 38
    },
    initialCustomHeight: {
        type: Number,
        default: 25
    }
})

// UI & Customization State
const selectedPaperSize = ref(props.paperSize || '38x25mm')
const customWidth = ref(Number(localStorage.getItem('trustcash_barcode_custom_w')) || props.initialCustomWidth || 38)
const customHeight = ref(Number(localStorage.getItem('trustcash_barcode_custom_h')) || props.initialCustomHeight || 25)
const showSettings = ref(false)
const showGuide = ref(false)
const previewScale = ref(1)

// Quick Presets
const commonMmPresets = [
    { w: 35, h: 25 },
    { w: 38, h: 25 },
    { w: 40, h: 20 },
    { w: 40, h: 30 },
    { w: 50, h: 25 },
    { w: 50, h: 30 },
    { w: 50, h: 35 },
    { w: 60, h: 40 },
    { w: 70, h: 50 },
    { w: 100, h: 150 }
]

const setCustomPreset = (w, h) => {
    customWidth.value = w
    customHeight.value = h
    localStorage.setItem('trustcash_barcode_custom_w', w)
    localStorage.setItem('trustcash_barcode_custom_h', h)
    updatePrintStyleTag()
}

// Content Toggles
const showStoreName = ref(true)
const storeName = ref(page.props.business_name || page.props.app_name || '')
const showProductName = ref(true)
const showBarcodeText = ref(true)
const showPrice = ref(true)
const pricePrefix = ref('MRP: ৳ ')
const showBorder = ref(false)

// Size Configurations
const SIZES = {
    '38x25mm': {
        labelBn: '৩৮ × ২৫ মিমি (১-কলাম থার্মাল স্টিকার)',
        labelEn: '38mm x 25mm (Single Thermal Label)',
        mode: 'mode-thermal-single',
        sizeClass: 'size-38x25',
        pageSize: '38mm 25mm',
        pageMargin: '0mm',
        maxNameLength: 22
    },
    '38x25mm_2col': {
        labelBn: '৩৮ × ২৫ মিমি (২-কলাম রোল)',
        labelEn: '38mm x 25mm (2-Column Roll)',
        mode: 'mode-thermal-2col',
        sizeClass: 'size-38x25-2col',
        pageSize: '80mm 25mm',
        pageMargin: '0mm',
        maxNameLength: 22
    },
    '40x30mm': {
        labelBn: '৪০ × ৩০ মিমি থার্মাল স্টিকার',
        labelEn: '40mm x 30mm Thermal Label',
        mode: 'mode-thermal-single',
        sizeClass: 'size-40x30',
        pageSize: '40mm 30mm',
        pageMargin: '0mm',
        maxNameLength: 26
    },
    '50x25mm': {
        labelBn: '৫০ × ২৫ মিমি (২" × ১")',
        labelEn: '50mm x 25mm Thermal Label',
        mode: 'mode-thermal-single',
        sizeClass: 'size-50x25',
        pageSize: '50mm 25mm',
        pageMargin: '0mm',
        maxNameLength: 30
    },
    '50x30mm': {
        labelBn: '৫০ × ৩০ মিমি থার্মাল স্টিকার',
        labelEn: '50mm x 30mm Thermal Label',
        mode: 'mode-thermal-single',
        sizeClass: 'size-50x30',
        pageSize: '50mm 30mm',
        pageMargin: '0mm',
        maxNameLength: 32
    },
    '80mm': {
        labelBn: '৮০ মিমি থার্মাল রোল (Continuous Roll)',
        labelEn: '80mm Continuous Roll',
        mode: 'mode-thermal-roll',
        sizeClass: 'size-80mm',
        pageSize: '80mm auto',
        pageMargin: '0mm',
        maxNameLength: 40
    },
    'A4': {
        labelBn: 'A4 স্টিকার শিট',
        labelEn: 'A4 Multi-label Sheet',
        mode: 'mode-sheet',
        sizeClass: 'size-a4',
        pageSize: 'A4 portrait',
        pageMargin: '6mm',
        maxNameLength: 28
    },
    'Letter': {
        labelBn: 'Letter সাইজ শিট',
        labelEn: 'Letter Sheet',
        mode: 'mode-sheet',
        sizeClass: 'size-a4',
        pageSize: 'letter portrait',
        pageMargin: '6mm',
        maxNameLength: 28
    },
    'custom': {
        labelBn: 'কাস্টম সাইজ (Custom mm)',
        labelEn: 'Custom Dimensions (mm)',
        mode: 'mode-custom',
        sizeClass: 'size-custom',
        maxNameLength: 25
    }
}

const currentConfig = computed(() => {
    return SIZES[selectedPaperSize.value] || SIZES['38x25mm']
})

const currentSizeLabel = computed(() => {
    if (selectedPaperSize.value === 'custom') {
        return `${customWidth.value}×${customHeight.value} mm (${t('কাস্টম', 'Custom')})`
    }
    return currentConfig.value.labelBn || currentConfig.value.labelEn
})

const currentModeClass = computed(() => {
    return currentConfig.value.mode
})

const currentSizeClass = computed(() => {
    return currentConfig.value.sizeClass
})

const maxNameLength = computed(() => {
    if (selectedPaperSize.value === 'custom') {
        const w = customWidth.value || 38
        return Math.max(14, Math.floor(w * 0.65))
    }
    return currentConfig.value.maxNameLength || 25
})

const totalLabels = computed(() => {
    return props.products.reduce((acc, p) => acc + (Number(p.copies) || 1), 0)
})

// Dynamic Proportional Custom Styling (Scales accurately for ANY mm)
const customLabelStyle = computed(() => {
    if (selectedPaperSize.value === 'custom') {
        const w = customWidth.value || 38
        const h = customHeight.value || 25
        return {
            width: `${w}mm`,
            height: `${h}mm`,
            maxWidth: `${w}mm`,
            maxHeight: `${h}mm`,
            padding: `${Math.max(0.8, h * 0.04).toFixed(1)}mm ${Math.max(1, w * 0.04).toFixed(1)}mm`
        }
    }
    return {}
})

const customStoreStyle = computed(() => {
    if (selectedPaperSize.value === 'custom') {
        const h = customHeight.value || 25
        const sizeMm = Math.max(1.8, Math.min(3.2, h * 0.09)).toFixed(1)
        return { fontSize: `${sizeMm}mm` }
    }
    return {}
})

const customNameStyle = computed(() => {
    if (selectedPaperSize.value === 'custom') {
        const h = customHeight.value || 25
        const sizeMm = Math.max(2.1, Math.min(3.6, h * 0.11)).toFixed(1)
        return { fontSize: `${sizeMm}mm` }
    }
    return {}
})

const customBarcodeStyle = computed(() => {
    if (selectedPaperSize.value === 'custom') {
        const h = customHeight.value || 25
        const w = customWidth.value || 38
        const maxH = Math.max(6, Math.min(40, h * 0.42)).toFixed(1)
        const maxW = Math.max(10, w - 4).toFixed(1)
        return {
            maxHeight: `${maxH}mm`,
            height: `${maxH}mm`,
            maxWidth: `${maxW}mm`
        }
    }
    return {}
})

const customCodeStyle = computed(() => {
    if (selectedPaperSize.value === 'custom') {
        const h = customHeight.value || 25
        const sizeMm = Math.max(1.8, Math.min(3.0, h * 0.09)).toFixed(1)
        return { fontSize: `${sizeMm}mm` }
    }
    return {}
})

const customPriceStyle = computed(() => {
    if (selectedPaperSize.value === 'custom') {
        const h = customHeight.value || 25
        const sizeMm = Math.max(2.5, Math.min(4.5, h * 0.14)).toFixed(1)
        return { fontSize: `${sizeMm}mm` }
    }
    return {}
})

const previewContainerStyle = computed(() => {
    if (previewScale.value !== 1) {
        return {
            transform: `scale(${previewScale.value})`,
            transformOrigin: 'top center'
        }
    }
    return {}
})

const truncate = (str, length) => {
    if (!str) return ''
    if (str.length <= length) return str
    return str.substring(0, length) + '..'
}

const formatPrice = (price) => {
    const num = Number(price || 0)
    return num % 1 === 0 ? num.toFixed(0) : num.toFixed(2)
}

// Injected dynamic @page print CSS
const updatePrintStyleTag = () => {
    let styleTag = document.getElementById('barcode-dynamic-page-rule')
    if (!styleTag) {
        styleTag = document.createElement('style')
        styleTag.id = 'barcode-dynamic-page-rule'
        document.head.appendChild(styleTag)
    }

    let pSize = '38mm 25mm'
    let pMargin = '0mm'

    if (selectedPaperSize.value === 'custom') {
        pSize = `${customWidth.value || 38}mm ${customHeight.value || 25}mm`
        pMargin = '0mm'
    } else if (SIZES[selectedPaperSize.value]) {
        pSize = SIZES[selectedPaperSize.value].pageSize
        pMargin = SIZES[selectedPaperSize.value].pageMargin
    }

    styleTag.textContent = `
        @media print {
            @page {
                size: ${pSize};
                margin: ${pMargin};
            }
        }
    `
}

watch([selectedPaperSize, customWidth, customHeight], () => {
    if (customWidth.value) localStorage.setItem('trustcash_barcode_custom_w', customWidth.value)
    if (customHeight.value) localStorage.setItem('trustcash_barcode_custom_h', customHeight.value)
    updatePrintStyleTag()
}, { immediate: true })

onMounted(() => {
    updatePrintStyleTag()
})

onUnmounted(() => {
    const styleTag = document.getElementById('barcode-dynamic-page-rule')
    if (styleTag) {
        styleTag.remove()
    }
})

const triggerPrint = () => {
    window.print()
}
</script>

<style scoped>
/* ============================================================
   SCREEN PREVIEW STYLES
   ============================================================ */
.barcode-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
    transition: transform 0.2s ease-in-out;
}

.label-wrapper {
    background: #ffffff;
    box-sizing: border-box;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px dashed #cbd5e1;
    overflow: hidden;
}

.label-content {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    text-align: center;
    box-sizing: border-box;
}

/* ============================================================
   LABEL SIZE DIMENSIONS (Screen & Base)
   ============================================================ */

/* 1. Standard 38mm x 25mm Thermal Label */
.label-wrapper.size-38x25,
.label-wrapper.size-38x25-2col {
    width: 38mm;
    height: 25mm;
    max-width: 38mm;
    max-height: 25mm;
    padding: 1mm 1.5mm;
}

.size-38x25 .lbl-store,
.size-38x25-2col .lbl-store {
    font-size: 2mm;
    font-weight: 800;
    line-height: 1;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #111827;
    max-width: 35mm;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.size-38x25 .lbl-name,
.size-38x25-2col .lbl-name {
    font-size: 2.5mm;
    font-weight: 700;
    line-height: 1.1;
    color: #000000;
    max-width: 35mm;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.size-38x25 .lbl-barcode-wrapper,
.size-38x25-2col .lbl-barcode-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.size-38x25 .lbl-barcode-img,
.size-38x25-2col .lbl-barcode-img {
    max-width: 34mm;
    height: 9.5mm;
    object-fit: contain;
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
    image-rendering: pixelated;
    display: block;
    margin: 0.2mm auto;
}

.size-38x25 .lbl-code,
.size-38x25-2col .lbl-code {
    font-size: 2.2mm;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-weight: 700;
    letter-spacing: 0.6px;
    line-height: 1;
    color: #1f2937;
}

.size-38x25 .lbl-price,
.size-38x25-2col .lbl-price {
    font-size: 3.2mm;
    font-weight: 900;
    line-height: 1;
    color: #000000;
}

/* 2. 40mm x 30mm Thermal Label */
.label-wrapper.size-40x30 {
    width: 40mm;
    height: 30mm;
    max-width: 40mm;
    max-height: 30mm;
    padding: 1.5mm 1.5mm;
}

.size-40x30 .lbl-store {
    font-size: 2.3mm;
    font-weight: 800;
    line-height: 1;
    max-width: 37mm;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.size-40x30 .lbl-name {
    font-size: 2.8mm;
    font-weight: 700;
    line-height: 1.1;
    max-width: 37mm;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.size-40x30 .lbl-barcode-img {
    max-width: 36mm;
    height: 11.5mm;
    object-fit: contain;
    image-rendering: pixelated;
}

.size-40x30 .lbl-code {
    font-size: 2.4mm;
    font-family: monospace;
    font-weight: 700;
    line-height: 1;
}

.size-40x30 .lbl-price {
    font-size: 3.5mm;
    font-weight: 900;
    line-height: 1;
}

/* 3. 50mm x 25mm Thermal Label */
.label-wrapper.size-50x25 {
    width: 50mm;
    height: 25mm;
    max-width: 50mm;
    max-height: 25mm;
    padding: 1mm 2mm;
}

.size-50x25 .lbl-store {
    font-size: 2.2mm;
    font-weight: 800;
    line-height: 1;
    max-width: 46mm;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.size-50x25 .lbl-name {
    font-size: 2.8mm;
    font-weight: 700;
    line-height: 1.1;
    max-width: 46mm;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.size-50x25 .lbl-barcode-img {
    max-width: 44mm;
    height: 10mm;
    object-fit: contain;
    image-rendering: pixelated;
}

.size-50x25 .lbl-code {
    font-size: 2.3mm;
    font-family: monospace;
    font-weight: 700;
    line-height: 1;
}

.size-50x25 .lbl-price {
    font-size: 3.4mm;
    font-weight: 900;
    line-height: 1;
}

/* 4. 50mm x 30mm Thermal Label */
.label-wrapper.size-50x30 {
    width: 50mm;
    height: 30mm;
    max-width: 50mm;
    max-height: 30mm;
    padding: 1.5mm 2mm;
}

.size-50x30 .lbl-store {
    font-size: 2.4mm;
    font-weight: 800;
    max-width: 46mm;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.size-50x30 .lbl-name {
    font-size: 3mm;
    font-weight: 700;
    max-width: 46mm;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.size-50x30 .lbl-barcode-img {
    max-width: 44mm;
    height: 12mm;
    object-fit: contain;
    image-rendering: pixelated;
}

.size-50x30 .lbl-code {
    font-size: 2.4mm;
    font-family: monospace;
    font-weight: 700;
}

.size-50x30 .lbl-price {
    font-size: 3.6mm;
    font-weight: 900;
}

/* 5. 80mm Continuous POS Roll */
.label-wrapper.size-80mm {
    width: 72mm;
    height: 40mm;
    max-width: 72mm;
    padding: 2mm 3mm;
}

.size-80mm .lbl-store {
    font-size: 3.2mm;
    font-weight: 800;
}

.size-80mm .lbl-name {
    font-size: 3.5mm;
    font-weight: 700;
}

.size-80mm .lbl-barcode-img {
    max-width: 60mm;
    height: 16mm;
    object-fit: contain;
    image-rendering: pixelated;
}

.size-80mm .lbl-code {
    font-size: 3mm;
    font-family: monospace;
    font-weight: 700;
}

.size-80mm .lbl-price {
    font-size: 4.2mm;
    font-weight: 900;
}

/* 6. A4 / Letter Multi-label Sheet */
.label-wrapper.size-a4 {
    width: 48mm;
    height: 32mm;
    max-width: 48mm;
    max-height: 32mm;
    padding: 1.5mm 2mm;
}

.size-a4 .lbl-store {
    font-size: 2.3mm;
    font-weight: 800;
}

.size-a4 .lbl-name {
    font-size: 2.8mm;
    font-weight: 700;
}

.size-a4 .lbl-barcode-img {
    max-width: 42mm;
    height: 12mm;
    object-fit: contain;
    image-rendering: pixelated;
}

.size-a4 .lbl-code {
    font-size: 2.4mm;
    font-family: monospace;
    font-weight: 700;
}

.size-a4 .lbl-price {
    font-size: 3.5mm;
    font-weight: 900;
}

/* 7. Custom Dimensions (Base fallbacks) */
.label-wrapper.size-custom {
    padding: 1mm 1.5mm;
}

.size-custom .lbl-store {
    font-weight: 800;
    line-height: 1;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.size-custom .lbl-name {
    font-weight: 700;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.size-custom .lbl-barcode-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.size-custom .lbl-barcode-img {
    object-fit: contain;
    image-rendering: crisp-edges;
    image-rendering: pixelated;
    display: block;
    margin: 0.2mm auto;
}

.size-custom .lbl-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 700;
    line-height: 1;
}

.size-custom .lbl-price {
    font-weight: 900;
    line-height: 1;
    color: #000000;
}

/* ============================================================
   PRINT RULES (Clean, Zero-Margin, Page-Break Controlled)
   ============================================================ */
@media print {
    html, body {
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        color: #000000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print\:hidden {
        display: none !important;
    }

    .barcode-container {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        transform: none !important;
    }

    /* Borders in print */
    .has-print-border {
        border: 1px solid #000000 !important;
    }

    .no-print-border {
        border: none !important;
        box-shadow: none !important;
    }

    /* 1-Column Thermal Mode (Every label breaks to new page) */
    .mode-thermal-single {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .mode-thermal-single .label-wrapper {
        margin: 0 auto !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border-radius: 0 !important;
    }

    .mode-thermal-single .label-wrapper:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
    }

    /* 2-Column Thermal Mode (2 labels per row on 80mm roll) */
    .mode-thermal-2col {
        display: flex !important;
        flex-wrap: wrap !important;
        width: 80mm !important;
        margin: 0 auto !important;
        padding: 0 !important;
        justify-content: space-between !important;
    }

    .mode-thermal-2col .label-wrapper {
        page-break-after: avoid !important;
        break-after: avoid !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border-radius: 0 !important;
    }

    .mode-thermal-2col .label-wrapper:nth-child(2n) {
        page-break-after: always !important;
        break-after: page !important;
    }

    .mode-thermal-2col .label-wrapper:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
    }

    /* Sheet Mode (A4, Letter) */
    .mode-sheet {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 3mm !important;
        justify-content: flex-start !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .mode-sheet .label-wrapper {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border: 1px solid #e2e8f0 !important;
    }

    /* 80mm Continuous Roll Mode */
    .mode-thermal-roll {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        gap: 3mm !important;
        width: 80mm !important;
        margin: 0 auto !important;
    }

    .mode-thermal-roll .label-wrapper {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    /* Custom Mode */
    .mode-custom .label-wrapper {
        margin: 0 auto !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border-radius: 0 !important;
    }

    .mode-custom .label-wrapper:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
    }
}
</style>