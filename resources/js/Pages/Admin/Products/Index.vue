<template>
    <AdminLayout :title="t('পণ্য তালিকা ও স্টক', 'Products')">
        <Head :title="t('পণ্য তালিকা ও স্টক', 'Products')" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                        {{ t('পণ্য তালিকা ও স্টক', 'Products Management') }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('দোকানের সকল পণ্য তালিকা, বিক্রয় মূল্য, এবং স্টক স্থিতি', 'Manage your product catalogue, pricing, and stock levels') }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- View Toggle -->
                    <div class="inline-flex rounded-xl bg-white p-1 shadow-xs border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <button type="button" @click="viewMode = 'table'"
                            :class="['px-3 py-1 text-xs font-bold rounded-lg transition-all', viewMode === 'table' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700']">
                            <ListBulletIcon class="h-4 w-4 inline mr-1" />
                            <span>{{ t('তালিকা', 'Table') }}</span>
                        </button>
                        <button type="button" @click="viewMode = 'grid'"
                            :class="['px-3 py-1 text-xs font-bold rounded-lg transition-all', viewMode === 'grid' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700']">
                            <Squares2X2Icon class="h-4 w-4 inline mr-1" />
                            <span>{{ t('গ্রিড', 'Grid') }}</span>
                        </button>
                    </div>

                    <Link :href="route('admin.products.download-pdf')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <ArrowDownTrayIcon class="h-4 w-4 text-emerald-600" />
                        <span>PDF</span>
                    </Link>

                    <BarcodePrintSelection
                        v-if="products.data && products.data.length > 0"
                        :products="products.data"
                        :button-label="t('বারকোড প্রিন্ট', 'Print Barcodes')"
                        button-class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 cursor-pointer"
                    />

                    <button type="button" @click="optimizeAllImages"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <ArrowPathIcon class="h-4 w-4 text-slate-500" />
                        <span>{{ t('ছবি অপ্টিমাইজ', 'Optimize') }}</span>
                    </button>

                    <Link :href="route('admin.products.create')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-all">
                        <PlusIcon class="h-4 w-4" />
                        <span>{{ t('নতুন পণ্য যোগ', 'Add Product') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Stats KPI Cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ t('মোট পণ্য সংখ্যা', 'Total Products') }}
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ formatNumber(products.total) }}</h3>
                </div>

                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ t('সক্রিয় পণ্য', 'Active Products') }}
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ formatNumber(activeProducts) }}</h3>
                </div>

                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ t('সীমিত স্টক সতর্কতা', 'Low Stock Alert') }}
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400">{{ formatNumber(lowStockProducts) }}</h3>
                </div>

                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ t('মোট মজুদ মূল্য', 'Total Stock Value') }}
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ formatCurrency(totalStockRawValue) }}</h3>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Search -->
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <MagnifyingGlassIcon class="h-4 w-4 text-slate-400" />
                        </div>
                        <input v-model="search" type="text"
                            :placeholder="t('পণ্য বা কোড খুঁজুন...', 'Search products...')"
                            class="block w-full rounded-xl border-slate-200 bg-white py-2 pl-9 pr-3 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            @input="handleSearchInput">
                    </div>

                    <!-- Category Filter -->
                    <select v-model="filters.category_id"
                        class="block w-full rounded-xl border-slate-200 bg-white py-2 pl-3 pr-8 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        @change="filterChanged">
                        <option value="">{{ t('সকল ক্যাটাগরি', 'All Categories') }}</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>

                    <!-- Brand Filter -->
                    <select v-model="filters.brand_id"
                        class="block w-full rounded-xl border-slate-200 bg-white py-2 pl-3 pr-8 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        @change="filterChanged">
                        <option value="">{{ t('সকল ব্র্যান্ড', 'All Brands') }}</option>
                        <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                            {{ brand.name }}
                        </option>
                    </select>

                    <!-- Status Filter -->
                    <select v-model="filters.status"
                        class="block w-full rounded-xl border-slate-200 bg-white py-2 pl-3 pr-8 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        @change="filterChanged">
                        <option value="">{{ t('সকল অবস্থা', 'All Status') }}</option>
                        <option :value="1">{{ t('সক্রিয়', 'Active') }}</option>
                        <option :value="0">{{ t('নিষ্ক্রিয়', 'Inactive') }}</option>
                    </select>
                </div>
            </div>

            <!-- Product Grid/Table Views -->
            <div v-if="products.data.length > 0">
                <ProductGrid v-if="viewMode === 'grid'" :products="products.data" @delete="deleteProduct" />
                <ProductTable v-else :products="products.data" @delete="deleteProduct" />
            </div>

            <!-- Empty State -->
            <div v-else class="text-center rounded-2xl border border-slate-200 bg-white p-12 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                <ShoppingBagIcon class="mx-auto h-12 w-12 text-slate-400" />
                <h3 class="mt-2 text-sm font-bold text-slate-900 dark:text-white">{{ t('কোনো পণ্য পাওয়া যায়নি', 'No products found') }}</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ t('নতুন পণ্য যুক্ত করতে নিচের বোতামে ক্লিক করুন।', 'Get started by creating a new product.') }}
                </p>
                <div class="mt-4">
                    <Link :href="route('admin.products.create')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500">
                        <PlusIcon class="h-4 w-4" />
                        <span>{{ t('নতুন পণ্য যোগ করুন', 'Add Product') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                <Pagination :links="products.links" />
            </div>

            <!-- Confirm Delete Dialog -->
            <ConfirmDialog v-model:show="showConfirmDialog" :title="t('পণ্য মুছে ফেলুন', 'Delete Product')" :message="confirmMessage"
                @confirm="confirmDelete" />
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import ProductGrid from './Partials/ProductGrid.vue';
import ProductTable from './Partials/ProductTable.vue';
import { useLanguage } from '@/composables/useLanguage';

import {
    PlusIcon,
    MagnifyingGlassIcon,
    ShoppingBagIcon,
    ArrowPathIcon,
    ArrowDownTrayIcon,
    Squares2X2Icon,
    ListBulletIcon
} from '@heroicons/vue/24/outline';
import BarcodePrintSelection from '@/Components/BarcodePrintSelection.vue';

const { t, formatCurrency, formatNumber } = useLanguage();

const props = defineProps({
    products: Object,
    filters: Object,
    categories: Array,
    brands: Array,
});

// State Management
const viewMode = ref(localStorage.getItem('productViewMode') || 'table');
const search = ref(props.filters?.search || '');
const filters = ref({
    category_id: props.filters?.category_id || '',
    brand_id: props.filters?.brand_id || '',
    status: props.filters?.status || ''
});

const showConfirmDialog = ref(false);
const confirmMessage = ref('');
const productToDelete = ref(null);
const isSearching = ref(false);

// Computed Properties
const activeProducts = computed(() =>
    props.products.data.filter(p => p.status).length
);

const lowStockProducts = computed(() =>
    props.products.data.filter(p => p.available_quantity <= p.alert_quantity && p.available_quantity > 0).length
);

const totalStockRawValue = computed(() => {
    return props.products.data.reduce((sum, product) => {
        return sum + (product.current_stock_value || 0);
    }, 0);
});

function optimizeAllImages() {
    router.post(route('admin.products.optimize-images'), {}, { preserveScroll: true });
}

// Search and Filters
const debouncedSearch = debounce(() => {
    isSearching.value = false;
    applyFilters({ search: search.value });
}, 400);

const handleSearchInput = () => {
    isSearching.value = true;
    debouncedSearch();
};

const filterChanged = () => {
    applyFilters(filters.value);
};

const applyFilters = (newFilters) => {
    router.get(route('admin.products.index'), {
        ...filters.value,
        ...newFilters
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['products']
    });
};

// Product Actions
const deleteProduct = (product) => {
    productToDelete.value = product;
    confirmMessage.value = t(
        `আপনি কি নিশ্চিত যে "${product.name}" মুছে ফেলতে চান? এটি পুনরুদ্ধার করা যাবে না।`,
        `Are you sure you want to delete "${product.name}"? This action cannot be undone.`
    );
    showConfirmDialog.value = true;
};

const confirmDelete = () => {
    if (productToDelete.value) {
        router.delete(route('admin.products.destroy', productToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showConfirmDialog.value = false;
                productToDelete.value = null;
            }
        });
    }
};

// Watchers
watch(() => props.filters, (newFilters) => {
    if (!isSearching.value) {
        search.value = newFilters.search ?? '';
    }
    filters.value = {
        category_id: newFilters.category_id ?? '',
        brand_id: newFilters.brand_id ?? '',
        status: newFilters.status ?? ''
    };
}, { deep: true });

watch(viewMode, (newMode) => {
    localStorage.setItem('productViewMode', newMode);
});
</script>
