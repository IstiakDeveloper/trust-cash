<script setup>
import { useLanguage } from '@/composables/useLanguage'
defineProps({ lots: { type: Array, default: () => [] } })
const { t, formatCurrency, formatNumber } = useLanguage()
</script>
<template>
    <details v-if="lots.length" class="my-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
        <summary class="cursor-pointer px-4 py-3 text-sm font-bold dark:text-white">{{ t('লট অনুযায়ী স্টকের বিবরণ', 'Stock by lot') }} ({{ lots.length }})</summary>
        <div class="overflow-x-auto"><table class="w-full text-xs dark:text-slate-200">
            <thead class="bg-slate-50 dark:bg-slate-800"><tr><th class="p-3 text-left">{{ t('পণ্য / লট', 'Product / Lot') }}</th><th class="p-3">{{ t('তারিখ', 'Date') }}</th><th class="p-3 text-right">{{ t('প্রাপ্ত পরিমাণ', 'Received') }}</th><th class="p-3 text-right">{{ t('অবশিষ্ট', 'Remaining') }}</th><th class="p-3 text-right">{{ t('লটের একক খরচ', 'Original lot unit cost') }}</th><th class="p-3 text-right">{{ t('বিক্রয়মূল্য', 'Selling price') }}</th></tr></thead>
            <tbody><tr v-for="lot in lots" :key="lot.id" class="border-t dark:border-slate-800"><td class="p-3"><span v-if="lot.product_name" class="block font-bold">{{ lot.product_name }}</span>{{ lot.number || lot.lot_number }}<span v-if="lot.variant_id" class="block">{{ t('ভ্যারিয়েন্ট', 'Variant') }} #{{ lot.variant_id }}</span></td><td class="p-3">{{ lot.date }}</td><td class="p-3 text-right">{{ formatNumber(lot.received_quantity) }}</td><td class="p-3 text-right">{{ formatNumber(lot.remaining_quantity) }}</td><td class="p-3 text-right">{{ formatCurrency(lot.purchase_cost) }}</td><td class="p-3 text-right">{{ formatCurrency(lot.selling_price) }}</td></tr></tbody>
        </table></div>
    </details>
</template>
