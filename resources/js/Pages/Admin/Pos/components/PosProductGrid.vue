<template>
    <div class="flex-1 bg-gray-50 dark:bg-gray-800">
        <!-- Loading State -->
        <div v-if="loading"
             class="flex items-center justify-center h-full">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>

        <!-- Product Grid -->
        <div v-else
             class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4 p-4">
            <div v-for="product in products"
                :key="product.id"
                @click="product.stock > 0 ? $emit('add-to-cart', product) : showOutOfStockAlert(product)"
                :class="[
                    'group transform transition-all duration-200',
                    product.stock <= 0 ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer hover:scale-102'
                ]"
            >
                <div class="bg-white dark:bg-gray-700 rounded-xl shadow-sm overflow-hidden
                           group-hover:shadow-md transition-shadow duration-200">
                    <!-- Product Image -->
                    <div class="aspect-square bg-gray-100 dark:bg-gray-600 relative">
                        <img v-if="product.image"
                            :src="getImageUrl(product.image)"
                            :alt="product.name"
                            class="w-full h-full object-contain p-4"
                        />
                        <div v-else
                             class="w-full h-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>

                        <!-- Stock Badge -->
                        <div class="absolute top-2 right-2 px-2 py-1 rounded-full text-xs font-bold"
                             :class="[
                                 product.stock > 0
                                     ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                     : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                             ]">
                            {{ product.stock > 0 ? (t('স্টক: ', 'Stock: ') + product.stock) : t('স্টক শেষ', 'Out of stock') }}
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="p-3">
                        <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 line-clamp-2 mb-1"
                            :title="product.name">
                            {{ product.name }}
                        </h3>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-extrabold text-indigo-600 dark:text-indigo-400">
                                ৳{{ formatNumber(product.selling_price) }}
                            </span>
                            <button v-if="product.stock > 0"
                                    class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white dark:bg-slate-600 dark:text-slate-200 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && products.length === 0"
             class="flex flex-col items-center justify-center h-full text-slate-400 dark:text-slate-500 p-8">
            <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <p class="text-sm font-bold mb-1">{{ t('কোন পণ্য পাওয়া যায়নি', 'No Products Found') }}</p>
            <p class="text-xs text-center">{{ t('অনুসন্ধান বা ক্যাটাগরি ফিল্টার পরিবর্তন করে দেখুন', 'Try adjusting your search or category filter') }}</p>
        </div>
    </div>
</template>

<script setup>
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    products: {
        type: Array,
        required: true
    },
    loading: {
        type: Boolean,
        default: false
    }
})

defineEmits(['add-to-cart'])

const showOutOfStockAlert = (product) => {
    alert(`❌ ${product.name} ${t('স্টকে নেই!', 'is out of stock!')}`)
}

const getImageUrl = (path) => {
    return path ? `/storage/${path}` : null
}

const formatNumber = (value) => {
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}
</script>

<style scoped>
/* Optional: Add smooth hover effect */
.grid > div {
    @apply transition-all duration-200;
}
.grid > div:active {
    @apply transform scale-95;
}
</style>
