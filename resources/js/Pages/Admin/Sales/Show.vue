<template>
    <AdminLayout :title="t('বিক্রয়ের বিবরণ', 'Sale Details') + ' - ' + sale.invoice_no">
        <Head :title="t('বিক্রয়ের বিবরণ', 'Sale Details')" />

        <div class="space-y-6">
            <!-- Header Actions -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.sales.index')"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        <ArrowLeftIcon class="h-4 w-4" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                            {{ t('বিক্রয়ের বিবরণ', 'Sale Details') }}
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ t('ইনভয়েস:', 'Invoice:') }} <span class="font-bold text-slate-800 dark:text-slate-200">#{{ sale.invoice_no }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="printReceipt"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-all">
                        <PrinterIcon class="h-4 w-4" />
                        <span>{{ t('রসিদ প্রিন্ট', 'Print Receipt') }}</span>
                    </button>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Sale Info & Items -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Info Card -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">
                            {{ t('বিক্রয় সংক্রান্ত তথ্য', 'Sale Information') }}
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div>
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider block">
                                    {{ t('ইনভয়েস নং', 'Invoice No') }}
                                </span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white mt-1 block">
                                    {{ sale.invoice_no }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider block">
                                    {{ t('তারিখ ও সময়', 'Date & Time') }}
                                </span>
                                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 mt-1 block">
                                    {{ sale.date }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider block">
                                    {{ t('পরিশোধের অবস্থা', 'Payment Status') }}
                                </span>
                                <span class="mt-1 inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold"
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300': sale.payment_status === 'paid',
                                        'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300': sale.payment_status === 'partial',
                                        'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300': sale.payment_status === 'due'
                                    }">
                                    {{ sale.payment_status === 'paid' ? t('পরিশোধিত', 'Paid') : (sale.payment_status === 'partial' ? t('আংশিক', 'Partial') : t('বকেয়া', 'Due')) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider block">
                                    {{ t('বিক্রেতা (ক্যাশিয়ার)', 'Created By') }}
                                </span>
                                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 mt-1 block">
                                    {{ sale.created_by || '—' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table Card -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white shadow-xs overflow-hidden dark:border-slate-700 dark:bg-slate-900">
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                {{ t('পণ্যসমূহের তালিকা', 'Sale Items') }}
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                                    <tr>
                                        <th class="px-5 py-3 text-left">{{ t('পণ্য', 'Product') }}</th>
                                        <th class="px-4 py-3 text-right">{{ t('দর', 'Unit Price') }}</th>
                                        <th class="px-4 py-3 text-right">{{ t('পরিমাণ', 'Quantity') }}</th>
                                        <th class="px-5 py-3 text-right">{{ t('মোট টাকা', 'Subtotal') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="item in sale.items" :key="item.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                        <td class="px-5 py-3 whitespace-nowrap">
                                            <div class="text-xs font-bold text-slate-900 dark:text-slate-100">
                                                {{ item.product.name }}
                                            </div>
                                            <div class="text-[11px] font-mono text-slate-400">
                                                {{ item.product.sku }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right font-medium text-slate-600 dark:text-slate-300">
                                            ৳{{ formatNumber(item.unit_price) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right font-bold text-slate-900 dark:text-slate-100">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="px-5 py-3 whitespace-nowrap text-right font-bold text-slate-900 dark:text-slate-100">
                                            ৳{{ formatNumber(item.subtotal) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Customer & Summary -->
                <div class="space-y-6">
                    <!-- Customer Info Card -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">
                            {{ t('কাস্টমার তথ্য', 'Customer Information') }}
                        </h3>
                        <div v-if="sale.customer" class="space-y-3">
                            <div>
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider block">
                                    {{ t('নাম', 'Name') }}
                                </span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white mt-0.5 block">
                                    {{ sale.customer.name }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider block">
                                    {{ t('ফোন নম্বর', 'Phone') }}
                                </span>
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 mt-0.5 block">
                                    {{ sale.customer.phone || '—' }}
                                </span>
                            </div>
                            <div v-if="sale.customer.address">
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider block">
                                    {{ t('ঠিকানা', 'Address') }}
                                </span>
                                <span class="text-xs text-slate-600 dark:text-slate-400 mt-0.5 block">
                                    {{ sale.customer.address }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-xs font-medium text-slate-500 dark:text-slate-400">
                            {{ t('খুচরা কাস্টমার (Walk-in)', 'Walk-in Customer') }}
                        </div>
                    </div>

                    <!-- Payment Summary Card -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">
                            {{ t('হিসাব বিবরণী', 'Payment Summary') }}
                        </h3>
                        <div class="space-y-3 text-xs">
                            <div class="flex justify-between">
                                <span class="font-medium text-slate-500 dark:text-slate-400">{{ t('উপমোট (Subtotal)', 'Subtotal') }}</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">
                                    ৳{{ formatNumber(sale.subtotal) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-slate-500 dark:text-slate-400">{{ t('ডিসকাউন্ট', 'Discount') }}</span>
                                <span class="font-semibold text-rose-600 dark:text-rose-400">
                                    -৳{{ formatNumber(sale.discount) }}
                                </span>
                            </div>
                            <div class="flex justify-between pt-3 border-t border-slate-100 dark:border-slate-800 text-sm font-bold">
                                <span class="text-slate-900 dark:text-white">{{ t('সর্বমোট', 'Total') }}</span>
                                <span class="text-indigo-600 dark:text-indigo-400">
                                    ৳{{ formatNumber(sale.total) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-slate-500 dark:text-slate-400">{{ t('পরিশোধ', 'Paid Amount') }}</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                    ৳{{ formatNumber(sale.paid) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-slate-500 dark:text-slate-400">{{ t('বকেয়া', 'Due Amount') }}</span>
                                <span class="font-bold text-rose-600 dark:text-rose-400">
                                    ৳{{ formatNumber(sale.due) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Note Card -->
                    <div v-if="sale.note" class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2">
                            {{ t('নোট / মন্তব্য', 'Note') }}
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            {{ sale.note }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    ArrowLeft as ArrowLeftIcon,
    Printer as PrinterIcon
} from 'lucide-vue-next'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    sale: {
        type: Object,
        required: true
    }
})

// Format number for currency display
const formatNumber = (value) => {
    return Number(value || 0).toLocaleString('bn-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

// Print receipt in new window
const printReceipt = () => {
    window.open(route('admin.sales.print-receipt', props.sale.id), '_blank')
}
</script>
