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
                    <div class="aspect-square bg-slate-100 dark:bg-slate-700/60 relative overflow-hidden flex items-center justify-center">
                        <img v-if="product.image || product.image_url"
                            :src="getImageUrl(product.image_url || product.image)"
                            :alt="product.name"
                            class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105"
                            loading="lazy"
                            @error="(e) => { e.target.style.display = 'none'; if (e.target.nextElementSibling) e.target.nextElementSibling.style.display = 'flex'; }"
                        />
                        <div :class="['w-full h-full flex items-center justify-center text-slate-300 dark:text-slate-500', (product.image || product.image_url) ? 'hidden' : '']">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <!-- Stock Badge -->
                        <div class="absolute top-2 right-2 px-2 py-1 rounded-full text-xs font-bold"
                             :class="[
                                 product.stock > 0
                                     ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                     : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                             ]">
                            {{ product.stock > 0 ? (t('স্টক: ', 'Stock: ') + formatNumber(product.stock)) : t('স্টক শেষ', 'Out of stock') }}
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
                                {{ formatCurrency(product.selling_price) }}
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
import { getImageUrl } from '@/utils/image'

const { t, formatNumber, formatCurrency } = useLanguage()

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
