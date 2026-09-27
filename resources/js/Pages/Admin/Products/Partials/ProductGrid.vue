<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <div v-for="product in products" :key="product.id"
            class="rounded-2xl border border-slate-200/90 bg-white shadow-xs overflow-hidden hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 transition-all flex flex-col justify-between">
            <div>
                <!-- Product Image -->
                <div class="relative bg-slate-100 dark:bg-slate-800 h-44 flex items-center justify-center overflow-hidden">
                    <img v-if="getProductImage(product)" :src="getImageUrl(getProductImage(product))" :alt="product.name"
                        class="object-cover w-full h-full">
                    <PhotoIcon v-else class="h-10 w-10 text-slate-400" />

                    <div class="absolute top-2.5 right-2.5">
                        <span :class="[
                            'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold shadow-xs',
                            getStockStatusClass(product.stock_status)
                        ]">
                            {{ getStockStatusText(product.stock_status) }}
                        </span>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="p-4">
                    <div class="min-h-[44px]">
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white line-clamp-2">
                            {{ product.name }}
                        </h3>
                        <p class="mt-0.5 text-[11px] font-mono text-slate-400">
                            SKU: {{ product.sku }}
                        </p>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-100 dark:border-slate-800 pt-3 text-xs">
                        <div>
                            <p class="text-[11px] font-medium text-slate-400">{{ t('বিক্রয় মূল্য', 'Selling Price') }}</p>
                            <p class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">
                                {{ formatPrice(product.selling_price) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-slate-400">{{ t('মজুদ স্টক', 'Stock') }}</p>
                            <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400 mt-0.5">
                                {{ formatNumber(product.available_quantity) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-2 text-xs">
                        <p class="text-[11px] text-slate-400">
                            {{ t('স্টক মূল্য:', 'Stock Value:') }} <span class="font-bold text-slate-700 dark:text-slate-300">{{ formatPrice(product.current_stock_value) }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="p-3 bg-slate-50/50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex justify-end items-center gap-1.5">
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
        </div>
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
