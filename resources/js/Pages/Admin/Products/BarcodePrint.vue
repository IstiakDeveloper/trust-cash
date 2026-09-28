<template>
    <div class="min-h-screen bg-slate-100 p-4 dark:bg-slate-900 print:bg-white print:p-0">
        <!-- Top Toolbar (Hidden during print) -->
        <div class="mx-auto max-w-4xl mb-6 rounded-2xl bg-white p-4 shadow-sm border border-slate-200 dark:bg-slate-800 dark:border-slate-700 print:hidden flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                    <QrCodeIcon class="h-6 w-6" />
                </div>
                <div>
                    <h1 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ t('বারকোড প্রিন্ট প্রিভিউ', 'Barcode Print Preview') }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ t('মোট লেবেল:', 'Total Labels:') }} <span class="font-bold text-slate-800 dark:text-slate-200">{{ formatNumber(totalLabels) }}</span> | 
                        {{ t('কাগজ:', 'Paper:') }} <span class="font-bold uppercase text-slate-800 dark:text-slate-200">{{ paperSize }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="print"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition-all cursor-pointer"
                >
                    <PrinterIcon class="h-4 w-4" />
                    <span>{{ t('এখনই প্রিন্ট করুন', 'Print Now') }}</span>
                </button>

                <Link
                    :href="route('admin.products.index')"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-all"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                    <span>{{ t('পণ্য তালিকা', 'Products') }}</span>
                </Link>
            </div>
        </div>

        <!-- Barcode Sheet Grid -->
        <div class="mx-auto max-w-4xl print:max-w-none print:w-full print:m-0">
            <div :class="['barcode-grid', paperSize === '80mm' ? 'thermal-mode' : 'sheet-mode']">
                <template v-for="product in products" :key="product.id">
                    <template v-for="copy in product.copies" :key="`${product.id}-${copy}`">
                        <div class="label-wrapper">
                            <div class="label-container">
                                <div class="label-content">
                                    <div class="product-name" :title="product.name">
                                        {{ truncate(product.name, 22) }}
                                    </div>
                                    <img
                                        :src="product.image_url"
                                        :alt="product.barcode"
                                        class="barcode-image"
                                    />
                                    <div class="barcode-number">
                                        {{ product.barcode }}
                                    </div>
                                    <div class="price">
                                        {{ formatCurrency(product.price) }}
                                    </div>
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
import { computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import { PrinterIcon, ArrowLeftIcon, QrCodeIcon } from '@heroicons/vue/24/outline'
import { useLanguage } from '@/composables/useLanguage'

const { t, formatCurrency, formatNumber } = useLanguage()

const props = defineProps({
    products: {
        type: Array,
        required: true,
        default: () => []
    },
    paperSize: {
        type: String,
        default: 'A4'
    }
})

const totalLabels = computed(() => {
    return props.products.reduce((acc, p) => acc + (Number(p.copies) || 1), 0)
})

onMounted(() => {
    const meta = document.createElement('meta')
    meta.name = 'viewport'
    meta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'
    document.head.appendChild(meta)
})

const truncate = (str, length) => {
    if (!str) return ''
    if (str.length <= length) return str
    return str.substring(0, length) + '...'
}

const print = () => {
    window.print()
}
</script>

<style scoped>
/* Screen & Common Styles */
.barcode-grid.sheet-mode {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start;
    gap: 8px;
    background: transparent;
}

.barcode-grid.thermal-mode {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.label-wrapper {
    width: 65mm;
    height: 40mm;
    background: white;
    border: 1px dashed #cbd5e1;
    border-radius: 6px;
    box-sizing: border-box;
    display: flex;
    justify-content: center;
    align-items: center;
    page-break-inside: avoid;
    break-inside: avoid;
}

.label-container {
    width: 100%;
    height: 100%;
    padding: 2.5mm;
    box-sizing: border-box;
    display: flex;
    justify-content: center;
    align-items: center;
}

.label-content {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    text-align: center;
}

.product-name {
    font-size: 3.2mm;
    font-weight: 700;
    line-height: 1.1;
    color: #0f172a;
    max-width: 58mm;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.barcode-image {
    width: 52mm;
    height: 18mm;
    object-fit: contain;
    image-rendering: pixelated;
}

.barcode-number {
    font-size: 3mm;
    font-family: monospace;
    font-weight: 600;
    letter-spacing: 1px;
    line-height: 1;
    color: #1e293b;
}

.price {
    font-size: 3.8mm;
    font-weight: 900;
    line-height: 1;
    color: #000;
}

/* Print Rules */
@media print {
    @page {
        margin: 5mm;
        size: auto;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        color: black !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print\:hidden {
        display: none !important;
    }

    .barcode-grid {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 3mm !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .thermal-mode .label-wrapper {
        page-break-after: always !important;
        break-after: always !important;
        margin-bottom: 0 !important;
    }

    .label-wrapper {
        border: 1px solid #e2e8f0 !important;
        box-shadow: none !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
