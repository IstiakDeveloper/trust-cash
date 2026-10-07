<template>
    <Head :title="`${t('ক্রয় চালান', 'Purchase Invoice')}: ${purchase.purchase_number}`" />
    <AdminLayout :title="t('ক্রয় চালান বিবরণ', 'Purchase Details')">
        <div class="container mx-auto px-4 py-6 max-w-4xl">
            <!-- Action Bar -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 mb-4 sm:mb-6 no-print">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.purchases.index')"
                        class="p-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 rounded-xl text-gray-700 dark:text-gray-200 transition shrink-0"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div class="min-w-0">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white truncate">
                            {{ t('ক্রয় চালান #', 'Purchase Invoice #') }}{{ purchase.purchase_number }}
                        </h2>
                        <span
                            class="px-2.5 py-0.5 text-xs font-bold rounded-full inline-block mt-1"
                            :class="{
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': purchase.payment_status === 'paid',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': purchase.payment_status === 'partial',
                                'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400': purchase.payment_status === 'due',
                            }"
                        >
                            {{ purchase.payment_status === 'paid' ? t('পরিশোধিত', 'Paid') : purchase.payment_status === 'partial' ? t('আংশিক পরিশোধ', 'Partial') : t('বকেয়া', 'Due') }}
                        </span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button
                        @click="printInvoice"
                        class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-semibold flex items-center justify-center gap-2 shadow-sm transition"
                    >
                        <PrinterIcon class="w-4 h-4" />
                        {{ t('চালান প্রিন্ট করুন', 'Print Invoice') }}
                    </button>
                </div>
            </div>

            <!-- Printable Invoice Paper -->
            <div id="invoice-paper" class="bg-white dark:bg-gray-800 p-4 sm:p-8 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-md text-sm text-gray-800 dark:text-gray-200">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start gap-4 pb-4 sm:pb-6 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-wide uppercase">{{ t('ক্রয় চালান / ইনভয়েস', 'PURCHASE ORDER') }}</h1>
                        <p class="text-xs text-gray-500 mt-1">{{ $page.props.business_name || 'TrustCash' }} {{ t('পিওএস ও ইনভেন্টরি ম্যানেজমেন্ট সিস্টেম', 'POS & Inventory System') }}</p>
                    </div>
                    <div class="sm:text-right">
                        <div class="text-base sm:text-lg font-black text-blue-600">{{ purchase.purchase_number }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ t('তারিখ:', 'Date:') }} {{ purchase.purchase_date }}</div>
                    </div>
                </div>

                <!-- Supplier and Meta info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 my-4 sm:my-6">
                    <div class="bg-gray-50 dark:bg-gray-700/30 sm:bg-transparent sm:dark:bg-transparent p-3 sm:p-0 rounded-xl">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1.5">{{ t('সরবরাহকারী তথ্য', 'Supplier Info') }}</h4>
                        <div class="font-bold text-gray-900 dark:text-white text-base">{{ purchase.supplier?.name }}</div>
                        <div v-if="purchase.supplier?.company_name" class="text-gray-600 dark:text-gray-300 font-medium text-xs sm:text-sm">{{ purchase.supplier?.company_name }}</div>
                        <div class="text-xs text-gray-500 mt-1">{{ t('মোবাইল:', 'Phone:') }} {{ purchase.supplier?.phone || 'N/A' }}</div>
                        <div class="text-xs text-gray-500">{{ t('ঠিকানা:', 'Address:') }} {{ purchase.supplier?.address || 'N/A' }}</div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/30 sm:bg-transparent sm:dark:bg-transparent p-3 sm:p-0 rounded-xl sm:text-right">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1.5">{{ t('অর্ডার বিবরণী', 'Order Details') }}</h4>
                        <div class="text-xs"><span class="text-gray-500">{{ t('এন্ট্রি করেছেন:', 'Recorded By:') }}</span> {{ purchase.creator?.name || 'Admin' }}</div>
                        <div class="text-xs mt-1"><span class="text-gray-500">{{ t('স্ট্যাটাস:', 'Status:') }}</span> {{ t('স্টকে গৃহীত হয়েছে', 'Received in Stock') }}</div>
                        <div v-if="purchase.bank_account" class="text-xs mt-1">
                            <span class="text-gray-500">{{ t('পেমেন্ট মাধ্যম:', 'Paid Through:') }}</span> {{ purchase.bank_account?.bank_name }}
                        </div>
                    </div>
                </div>

                <!-- Mobile Items Cards (< md) -->
                <div class="md:hidden space-y-2.5 my-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">{{ t('ক্রয়কৃত পণ্যসমূহ', 'Purchased Items') }}</h4>
                    <div
                        v-for="(item, idx) in purchase.items"
                        :key="item.id"
                        class="p-3 rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-700/50 space-y-2"
                    >
                        <div class="flex justify-between items-start gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-gray-400">#{{ idx + 1 }}</span>
                                <span class="font-bold text-gray-900 dark:text-white text-sm">
                                    {{ item.product?.name }}
                                </span>
                            </div>
                            <span class="font-black text-sm text-gray-900 dark:text-white">৳{{ formatNumber(item.subtotal) }}</span>
                        </div>
                        <div v-if="item.variant" class="text-xs text-gray-500">
                            {{ item.variant.name }}
                        </div>
                        <div class="flex justify-between items-center text-xs text-gray-500 pt-1 border-t border-gray-200/60 dark:border-gray-600/40">
                            <span>{{ t('দর', 'Rate') }}: ৳{{ formatNumber(item.purchase_price) }}</span>
                            <span>{{ t('পরিমাণ', 'Qty') }}: <strong class="text-gray-800 dark:text-gray-200">{{ item.quantity }} {{ item.unit?.name || 'Pcs' }}</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Items Desktop Table (>= md) -->
                <div class="hidden md:block overflow-x-auto my-6">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 text-xs font-bold uppercase text-gray-600 dark:text-gray-300">
                                <th class="py-3 px-4 text-left">#</th>
                                <th class="py-3 px-4 text-left">{{ t('পণ্যের বিবরণ', 'Item Description') }}</th>
                                <th class="py-3 px-4 text-center">{{ t('একক', 'Unit') }}</th>
                                <th class="py-3 px-4 text-right">{{ t('ক্রয় মূল্য (৳)', 'Cost Price (৳)') }}</th>
                                <th class="py-3 px-4 text-center">{{ t('পরিমাণ', 'Qty') }}</th>
                                <th class="py-3 px-4 text-right">{{ t('উপমোট (৳)', 'Subtotal (৳)') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="(item, idx) in purchase.items" :key="item.id">
                                <td class="py-3 px-4 text-xs text-gray-400">{{ idx + 1 }}</td>
                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">
                                    {{ item.product?.name }}
                                    <span v-if="item.variant" class="text-xs text-gray-500 block">({{ item.variant.name }})</span>
                                </td>
                                <td class="py-3 px-4 text-center text-xs text-gray-500">{{ item.unit?.name || 'Pcs' }}</td>
                                <td class="py-3 px-4 text-right font-medium">৳{{ formatNumber(item.purchase_price) }}</td>
                                <td class="py-3 px-4 text-center font-bold">{{ item.quantity }}</td>
                                <td class="py-3 px-4 text-right font-bold text-gray-900 dark:text-white">৳{{ formatNumber(item.subtotal) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Breakdown -->
                <div class="flex justify-end mt-4">
                    <div class="w-full sm:w-80 space-y-2 text-sm border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>{{ t('উপমোট:', 'Subtotal:') }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">৳{{ formatNumber(purchase.subtotal) }}</span>
                        </div>
                        <div v-if="purchase.discount_amount > 0" class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>{{ t('ছাড়:', 'Discount:') }}</span>
                            <span class="text-rose-500 font-bold">-৳{{ formatNumber(purchase.discount_amount) }}</span>
                        </div>
                        <div v-if="purchase.tax_amount > 0" class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>{{ t('ট্যাক্স / ভ্যাট:', 'Tax / VAT:') }}</span>
                            <span>৳{{ formatNumber(purchase.tax_amount) }}</span>
                        </div>
                        <div v-if="purchase.shipping_cost > 0" class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>{{ t('পরিবহন খরচ:', 'Shipping:') }}</span>
                            <span>৳{{ formatNumber(purchase.shipping_cost) }}</span>
                        </div>
                        <hr class="border-gray-200 dark:border-gray-700" />
                        <div class="flex justify-between text-base font-bold text-gray-900 dark:text-white">
                            <span>{{ t('সর্বমোট মূল্য:', 'Total Amount:') }}</span>
                            <span class="text-blue-600 font-black">৳{{ formatNumber(purchase.total_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-emerald-600 font-bold">
                            <span>{{ t('পরিশোধিত:', 'Paid Amount:') }}</span>
                            <span>৳{{ formatNumber(purchase.paid_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-rose-600 font-bold">
                            <span>{{ t('বকেয়া:', 'Due Amount:') }}</span>
                            <span>৳{{ formatNumber(purchase.due_amount) }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="purchase.note" class="mt-6 sm:mt-8 pt-4 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500">
                    <span class="font-bold">{{ t('নোট / মন্তব্য:', 'Note:') }}</span> {{ purchase.note }}
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLanguage } from '@/composables/useLanguage'
import {
    ArrowLeft as ArrowLeftIcon,
    Printer as PrinterIcon
} from 'lucide-vue-next'

const { t } = useLanguage()

const props = defineProps({
    purchase: Object,
})

const formatNumber = (val) => {
    return Number(val || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}

const printInvoice = () => {
    window.print()
}
</script>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    nav, aside, header {
        display: none !important;
    }
    body {
        background: white !important;
        color: black !important;
    }
    #invoice-paper {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
}
</style>
