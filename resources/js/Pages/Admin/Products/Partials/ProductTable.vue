<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-900 overflow-x-auto">
        <table class="min-w-[900px] w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
            <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                <tr>
                    <th scope="col" class="py-3.5 pl-5 pr-3 text-left">
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
                <tr v-for="product in products" :key="product.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                    <td class="whitespace-nowrap py-3.5 pl-5 pr-3">
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
                        <div class="font-bold text-slate-900 dark:text-white">{{ formatPrice(product.selling_price) }}</div>
                        <div class="text-[11px] text-slate-400">{{ t('কেনা:', 'Cost:') }} {{ formatPrice(product.cost_price) }}</div>
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
                            {{ formatPrice(product.current_stock_value) }}
                        </div>
                        <div class="text-slate-400 text-[11px]">
                            {{ t('গড়:', 'Avg:') }} {{ formatPrice(product.cost_price) }}
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
                            <Link v-if="user?.role?.name?.toLowerCase() === 'admin'" :href="route('admin.products.show', product.id)"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                <EyeIcon class="h-4 w-4" />
                            </Link>
                            <Link v-if="user?.role?.name?.toLowerCase() === 'admin'" :href="route('admin.products.edit', product.id)"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-indigo-600 hover:text-indigo-800 dark:border-slate-700 dark:bg-slate-800 dark:text-indigo-400">
                                <PencilIcon class="h-4 w-4" />
                            </Link>
                            <button v-if="user?.role?.name?.toLowerCase() === 'admin'" @click="$emit('delete', product)"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-rose-600 hover:text-rose-800 dark:border-slate-700 dark:bg-slate-800 dark:text-rose-400">
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { EyeIcon, PencilIcon, TrashIcon, PhotoIcon } from '@heroicons/vue/24/outline';
import { useLanguage } from '@/composables/useLanguage';

const { t } = useLanguage();

const props = defineProps({
    products: {
        type: Array,
        required: true
    }
});

const page = usePage();
const user = page.props.auth.user;

defineEmits(['delete']);

const getProductImage = (product) => {
    if (!product.images?.length) return null;
    const primaryImage = product.images.find(img => img.is_primary);
    return primaryImage ? primaryImage.image : product.images[0].image;
};

const getImageUrl = (path) => {
    if (!path) return null;
    return `/storage/${path}`;
};

const formatPrice = (price) => {
    const number = Number(price || 0).toLocaleString('bn-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    return `৳ ${number}`;
};

const formatNumber = (number) => {
    if (Number.isInteger(Number(number))) {
        return Math.round(number);
    }
    return Number(number).toFixed(2);
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
