<template>
    <AdminLayout :title="product.name + ' - ' + t('পণ্যের বিবরণ', 'Product Details')">
        <Head :title="product.name + ' - ' + t('পণ্যের বিবরণ', 'Product Details')" />

        <div class="mx-auto max-w-7xl space-y-5 pb-10">
            <!-- Breadcrumbs & Header Bar -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 mb-1">
                        <Link :href="route('admin.products.index')" class="hover:underline flex items-center gap-1">
                            <CubeIcon class="h-3.5 w-3.5" />
                            <span>{{ t('পণ্য তালিকা', 'Products') }}</span>
                        </Link>
                        <span>/</span>
                        <span class="text-slate-400 dark:text-slate-500">{{ t('বিস্তারিত বিবরণ', 'Details') }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ product.name }}
                        </h1>
                        <!-- Status Badge -->
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold"
                            :class="product.status
                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/60'
                                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="product.status ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                            <span>{{ product.status ? t('সক্রিয় পণ্য', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}</span>
                        </span>
                    </div>
                </div>

                <!-- Action Buttons Toolbar -->
                <div class="flex flex-wrap items-center gap-2">
                    <BarcodePrintSelection :products="[product]" />
                    <BarcodeGenerator :product-id="product.id" />

                    <Link :href="route('admin.products.edit', product.id)"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-all">
                        <PencilIcon class="h-3.5 w-3.5" />
                        <span>{{ t('সম্পাদনা', 'Edit') }}</span>
                    </Link>

                    <Link :href="route('admin.products.index')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-all">
                        <ArrowLeftIcon class="h-3.5 w-3.5" />
                        <span>{{ t('তালিকায় ফিরুন', 'Back') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Compact Main Grid -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 items-start">
                
                <!-- Left Column (4 cols): Product Gallery & Core Identifiers -->
                <div class="space-y-5 lg:col-span-4">
                    
                    <!-- Image Card with Thumbnail Switcher -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                        <!-- Main Featured Image -->
                        <div class="relative aspect-square w-full rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700 flex items-center justify-center">
                            <img v-if="activeImage" :src="getImageUrl(activeImage)" :alt="product.name"
                                class="h-full w-full object-cover transition-transform duration-300 hover:scale-105" />
                            <div v-else class="text-center p-6">
                                <CubeIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600 mb-1" />
                                <span class="text-xs font-medium text-slate-400 dark:text-slate-500">
                                    {{ t('কোনো ছবি সংযুক্ত নেই', 'No image available') }}
                                </span>
                            </div>
                        </div>

                        <!-- Thumbnails list (if multiple) -->
                        <div v-if="product.images && product.images.length > 1" class="mt-3 flex gap-2 overflow-x-auto pb-1">
                            <button v-for="(img, idx) in product.images" :key="img.id || idx"
                                type="button" @click="activeImage = img.image || img.url"
                                class="relative h-14 w-14 shrink-0 rounded-lg overflow-hidden border-2 transition-all"
                                :class="(activeImage === img.image || activeImage === img.url)
                                    ? 'border-indigo-600 ring-2 ring-indigo-600/30'
                                    : 'border-slate-200 dark:border-slate-700 opacity-70 hover:opacity-100'">
                                <img :src="getImageUrl(img.image || img.url)" class="h-full w-full object-cover" />
                            </button>
                        </div>
                    </div>

                    <!-- Core Identifiers & Meta Card -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            {{ t('শনাক্তকারী কোড ও তথ্য', 'Identifiers & Taxonomy') }}
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <!-- SKU -->
                            <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                                <span class="font-semibold text-slate-500 dark:text-slate-400">{{ t('SKU কোড', 'SKU') }}:</span>
                                <div class="flex items-center gap-1.5 font-mono font-bold text-slate-900 dark:text-white">
                                    <span>{{ product.sku }}</span>
                                    <button type="button" @click="copyToClipboard(product.sku)"
                                        :title="t('কপি করুন', 'Copy SKU')"
                                        class="p-1 rounded text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        <ClipboardDocumentCheckIcon v-if="copiedSku" class="h-4 w-4 text-emerald-600" />
                                        <ClipboardDocumentIcon v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Barcode -->
                            <div v-if="product.barcode" class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                                <span class="font-semibold text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                    <QrCodeIcon class="h-3.5 w-3.5" />
                                    <span>{{ t('বারকোড', 'Barcode') }}:</span>
                                </span>
                                <span class="font-mono font-bold text-slate-900 dark:text-white">{{ product.barcode }}</span>
                            </div>

                            <!-- Category -->
                            <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-slate-500 dark:text-slate-400">{{ t('ক্যাটাগরি', 'Category') }}</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ product.category?.name || '—' }}
                                </span>
                            </div>

                            <!-- Brand -->
                            <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-slate-500 dark:text-slate-400">{{ t('ব্র্যান্ড', 'Brand') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ product.brand?.name || t('কোনো ব্র্যান্ড নেই', 'None') }}
                                </span>
                            </div>

                            <!-- Unit -->
                            <div class="flex items-center justify-between py-1">
                                <span class="text-slate-500 dark:text-slate-400">{{ t('পরিমাপের একক', 'Unit') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ product.unit?.name }} <span class="text-slate-400">({{ product.unit?.short_name }})</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (8 cols): Financials, Stock, Specs & Description -->
                <div class="space-y-5 lg:col-span-8">
                    
                    <!-- Pricing & Margin KPI Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <!-- Selling Price -->
                        <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                    {{ t('বিক্রয় মূল্য', 'Selling Price') }}
                                </span>
                                <span class="h-6 w-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center dark:bg-emerald-950/60 dark:text-emerald-400 text-xs font-bold">
                                    ৳
                                </span>
                            </div>
                            <h3 class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400">
                                {{ formatCurrency(product.selling_price) }}
                            </h3>
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                                / {{ product.unit?.short_name || t('একক', 'Unit') }}
                            </p>
                        </div>

                        <!-- Cost Price -->
                        <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                    {{ t('ক্রয় / খরচ মূল্য', 'Cost Price') }}
                                </span>
                                <span class="h-6 w-6 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center dark:bg-slate-800 dark:text-slate-300 text-xs font-bold">
                                    ৳
                                </span>
                            </div>
                            <h3 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">
                                {{ formatCurrency(product.cost_price || 0) }}
                            </h3>
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                                {{ product.cost_price ? t('নির্ধারিত ক্রয়মূল্য', 'Default purchase cost') : t('ক্রয়মূল্য সেট করা হয়নি', 'Not set') }}
                            </p>
                        </div>

                        <!-- Profit Margin -->
                        <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                    {{ t('আনুমানিক মুনাফা', 'Profit Margin') }}
                                </span>
                                <span class="h-6 w-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center dark:bg-indigo-950/60 dark:text-indigo-400 text-xs font-bold">
                                    %
                                </span>
                            </div>
                            <h3 class="mt-2 text-2xl font-black" :class="marginColor">
                                {{ formatNumber(calculateMargin) }}%
                            </h3>
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                                {{ estimatedProfitText }}
                            </p>
                        </div>
                    </div>

                    <!-- Stock Alert Limit Card -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400 font-bold">
                                <ExclamationTriangleIcon class="h-5 w-5" />
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ t('সীমিত স্টক সতর্কতা সীমা (Low Stock Alert)', 'Low Stock Alert Limit') }}
                                </h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ t('দোকানে স্টক এই পরিমাণের নিচে নামলে নোটিফিকেশন দেবে', 'Notification triggers when inventory reaches this number') }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="text-xl font-black text-slate-900 dark:text-white">
                                {{ formatNumber(product.alert_quantity || 0) }}
                            </span>
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1">
                                {{ product.unit?.short_name || t('একক', 'Unit') }}
                            </span>
                        </div>
                    </div>

                    <!-- Specifications Card -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                <TagIcon class="h-4 w-4 text-indigo-500" />
                                <span>{{ t('পণ্যের বৈশিষ্ট্য ও স্পেসিফিকেশন', 'Product Specifications') }}</span>
                            </h3>
                            <span class="text-[10px] font-bold text-slate-400">
                                {{ hasSpecifications ? formatNumber(Object.keys(product.specifications).length) + ' ' + t('টি বৈশিষ্ট্য', 'specs') : '' }}
                            </span>
                        </div>

                        <div v-if="hasSpecifications" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div v-for="(val, key) in product.specifications" :key="key"
                                class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 text-xs">
                                <span class="font-bold text-slate-600 dark:text-slate-400">{{ key }}</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ val }}</span>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-xs text-slate-400 dark:text-slate-500">
                            {{ t('কোনো স্পেসিফিকেশন বা অতিরিক্ত বৈশিষ্ট্য উল্লেখ করা নেই।', 'No specifications added for this product.') }}
                        </div>
                    </div>

                    <!-- Description & Notes Card -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <DocumentTextIcon class="h-4 w-4 text-indigo-500" />
                            <span>{{ t('পণ্যের বিবরণ ও নোট', 'Description & Notes') }}</span>
                        </h3>
                        
                        <p v-if="product.description" class="text-xs leading-relaxed text-slate-700 dark:text-slate-300 whitespace-pre-line p-2">
                            {{ product.description }}
                        </p>
                        <div v-else class="text-center py-4 text-xs text-slate-400 dark:text-slate-500">
                            {{ t('কোনো বিস্তারিত বিবরণ বা নোট যোগ করা হয়নি।', 'No description or notes provided for this product.') }}
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    PencilIcon,
    ArrowLeftIcon,
    CubeIcon,
    TagIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    ClipboardDocumentIcon,
    ClipboardDocumentCheckIcon,
    QrCodeIcon
} from '@heroicons/vue/24/outline';
import BarcodeGenerator from '@/Components/BarcodeGenerator.vue';
import BarcodePrintSelection from '@/Components/BarcodePrintSelection.vue';
import { useLanguage } from '@/composables/useLanguage';
import { getImageUrl } from '@/utils/image';

const { t, formatCurrency, formatNumber } = useLanguage();

const props = defineProps({
    product: {
        type: Object,
        required: true
    }
});

// Active image state
const activeImage = ref(
    props.product.image_url ||
    props.product.images?.find(i => i.is_primary)?.image ||
    props.product.images?.[0]?.image ||
    null
);

// Copy SKU feedback
const copiedSku = ref(false);
const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        copiedSku.value = true;
        setTimeout(() => {
            copiedSku.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy', err);
    }
};

const hasSpecifications = computed(() => {
    return Boolean(props.product.specifications && Object.keys(props.product.specifications).length > 0);
});

const calculateMargin = computed(() => {
    if (!props.product.cost_price || !props.product.selling_price) return 0;
    const margin = ((props.product.selling_price - props.product.cost_price) / props.product.selling_price) * 100;
    return margin.toFixed(2);
});

const marginColor = computed(() => {
    const margin = Number(calculateMargin.value);
    if (margin >= 25) return 'text-emerald-600 dark:text-emerald-400';
    if (margin > 0) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
});

const estimatedProfitText = computed(() => {
    if (!props.product.cost_price || !props.product.selling_price) {
        return t('লাভের হার হিসাব করা সম্ভব নয়', 'Cost price needed');
    }
    const diff = Number(props.product.selling_price) - Number(props.product.cost_price);
    return t(`লাভ: ${formatCurrency(diff)} / একক`, `Profit: ${formatCurrency(diff)}/unit`);
});
</script>
