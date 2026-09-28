<template>
    <div class="space-y-3">
        <!-- Floating/Sticky Multi-Select Bar -->
        <div v-if="selectedProductIds.length > 0"
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-indigo-50 px-4 py-3 border border-indigo-200 dark:bg-indigo-950/50 dark:border-indigo-800/60 shadow-xs">
            <div class="flex items-center gap-2.5 text-xs font-bold text-indigo-900 dark:text-indigo-200">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-white text-[11px] font-black">
                    {{ formatNumber(selectedProductIds.length) }}
                </span>
                <span>{{ t('টি পণ্য নির্বাচিত হয়েছে — একসাথে বারকোড প্রিন্ট করতে পারবেন', 'products selected for bulk actions') }}</span>
            </div>

            <div class="flex items-center gap-2">
                <BarcodePrintSelection
                    :products="selectedProducts"
                    :button-label="t('নির্বাচিত বারকোড প্রিন্ট', 'Print Selected Barcodes')"
                    button-class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-all cursor-pointer"
                />

                <button
                    type="button"
                    @click="clearSelection"
                    class="rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-50 dark:border-indigo-800 dark:bg-slate-800 dark:text-indigo-300 dark:hover:bg-slate-700 transition-all cursor-pointer"
                >
                    {{ t('নির্বাচন বাতিল', 'Clear Selection') }}
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-900 overflow-x-auto">
            <table class="min-w-[950px] w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-2 w-10 text-center">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                @change="toggleSelectAll"
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 cursor-pointer"
                            />
                        </th>
                        <th scope="col" class="py-3.5 pl-2 pr-3 text-left">
                            {{ t('পণ্য', 'Product') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left">
                            {{ t('ক্যাটাগরি', 'Category') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left">
                            {{ t('মূল্য (বিক্রয় / ক্রয়)', 'Price (Sell / Cost)') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left">
                            {{ t('মজুদ (স্টক)', 'Stock') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left">
                            {{ t('স্টক মূল্য', 'Stock Value') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left">
                            {{ t('অবস্থা', 'Status') }}
                        </th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-5 text-right">
                            <span>{{ t('অ্যাকশন', 'Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr v-for="product in products" :key="product.id"
                        :class="['hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors', isSelected(product.id) ? 'bg-indigo-50/30 dark:bg-indigo-950/20' : '']">
                        <td class="whitespace-nowrap py-3.5 pl-4 pr-2 text-center">
                            <input
                                type="checkbox"
                                :checked="isSelected(product.id)"
                                @change="toggleSelect(product)"
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 cursor-pointer"
                            />
                        </td>
                        <td class="whitespace-nowrap py-3.5 pl-2 pr-3">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 flex-shrink-0 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                                    <img v-if="getProductImage(product)" :src="getImageUrl(getProductImage(product))"
                                        :alt="product.name" class="h-10 w-10 object-cover">
                                    <PhotoIcon v-else class="h-5 w-5 text-slate-400" />
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ product.name }}</div>
                                    <div class="text-[11px] font-mono text-slate-400">SKU: {{ product.sku }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3.5 font-medium text-slate-600 dark:text-slate-300">
                            {{ product.category?.name || '—' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-3.5">
                            <div class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(product.selling_price) }}</div>
                            <div class="text-[11px] text-slate-400">{{ t('কেনা:', 'Cost:') }} {{ formatCurrency(product.cost_price) }}</div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3.5">
                            <div class="font-bold text-slate-900 dark:text-white">
                                {{ formatNumber(product.available_quantity) }}
                            </div>
                            <span :class="[
                                'inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold mt-0.5',
                                getStockStatusClass(product.stock_status)
                            ]">
                                {{ getStockStatusText(product.stock_status) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3.5">
                            <div class="font-bold text-slate-900 dark:text-white">
                                {{ formatCurrency(product.current_stock_value) }}
                            </div>
                            <div class="text-slate-400 text-[11px]">
                                {{ t('গড়:', 'Avg:') }} {{ formatCurrency(product.cost_price) }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3.5">
                            <span :class="[
                                'inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold',
                                product.status
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                    : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                            ]">
                                {{ product.status ? t('সক্রিয়', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap py-3.5 pl-3 pr-5 text-right font-medium">
                            <div class="flex justify-end items-center gap-1.5">
                                <!-- Print Barcode Quick Button -->
                                <button
                                    type="button"
                                    @click="openSinglePrint(product)"
                                    :title="t('বারকোড প্রিন্ট', 'Print Barcode')"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 dark:border-slate-700 dark:bg-slate-800 dark:text-emerald-400 dark:hover:bg-emerald-950/40 transition-all cursor-pointer"
                                >
                                    <QrCodeIcon class="h-4 w-4" />
                                </button>

                                <Link v-if="user?.role?.name?.toLowerCase() === 'admin'" :href="route('admin.products.show', product.id)"
                                    :title="t('বিবরণ', 'Details')"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 transition-all">
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                                <Link v-if="user?.role?.name?.toLowerCase() === 'admin'" :href="route('admin.products.edit', product.id)"
                                    :title="t('সম্পাদনা', 'Edit')"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-indigo-600 hover:text-indigo-800 dark:border-slate-700 dark:bg-slate-800 dark:text-indigo-400 transition-all">
                                    <PencilIcon class="h-4 w-4" />
                                </Link>
                                <button v-if="user?.role?.name?.toLowerCase() === 'admin'" @click="$emit('delete', product)"
                                    :title="t('মুছে ফেলুন', 'Delete')"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-rose-600 hover:text-rose-800 dark:border-slate-700 dark:bg-slate-800 dark:text-rose-400 transition-all cursor-pointer">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Hidden component for single product barcode printing modal -->
        <BarcodePrintSelection
            ref="singlePrintRef"
            :products="singlePrintProduct ? [singlePrintProduct] : []"
            :show-button="false"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { EyeIcon, PencilIcon, TrashIcon, PhotoIcon, QrCodeIcon } from '@heroicons/vue/24/outline';
import BarcodePrintSelection from '@/Components/BarcodePrintSelection.vue';
import { useLanguage } from '@/composables/useLanguage';
import { getImageUrl } from '@/utils/image';

const { t, formatCurrency, formatNumber } = useLanguage();

const props = defineProps({
    products: {
        type: Array,
        required: true
    }
});

const page = usePage();
const user = page.props.auth.user;

defineEmits(['delete']);

// Multi-select state
const selectedProductIds = ref([]);

const selectedProducts = computed(() => {
    return props.products.filter(p => selectedProductIds.value.includes(p.id));
});

const isSelected = (id) => selectedProductIds.value.includes(id);

const toggleSelect = (product) => {
    const idx = selectedProductIds.value.indexOf(product.id);
    if (idx > -1) {
        selectedProductIds.value.splice(idx, 1);
    } else {
        selectedProductIds.value.push(product.id);
    }
};

const isAllSelected = computed(() => {
    return props.products.length > 0 && props.products.every(p => selectedProductIds.value.includes(p.id));
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedProductIds.value = [];
    } else {
        selectedProductIds.value = props.products.map(p => p.id);
    }
};

const clearSelection = () => {
    selectedProductIds.value = [];
};

// Single product barcode modal
const singlePrintRef = ref(null);
const singlePrintProduct = ref(null);

const openSinglePrint = (product) => {
    singlePrintProduct.value = product;
    if (singlePrintRef.value) {
        singlePrintRef.value.openModal([product]);
    }
};

const getProductImage = (product) => {
    if (product.image_url) return product.image_url;
    if (!product.images?.length) return null;
    const primaryImage = product.images.find(img => img.is_primary);
    return primaryImage ? (primaryImage.url || primaryImage.image) : (product.images[0].url || product.images[0].image);
};

const getStockStatusText = (status) => {
    switch (status) {
        case 'out': return t('স্টক নেই', 'Out of Stock');
        case 'low': return t('সীমিত স্টক', 'Low Stock');
        default: return t('মজুদ আছে', 'In Stock');
    }
};

const getStockStatusClass = (status) => {
    switch (status) {
        case 'out':
            return 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300';
        case 'low':
            return 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300';
        default:
            return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300';
    }
};
</script>
