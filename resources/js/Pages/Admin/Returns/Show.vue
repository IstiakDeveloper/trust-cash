<template>
    <AdminLayout :title="t('পণ্য ফেরত ভাউচার', 'Return Voucher') + ' #' + saleReturn.return_number">
        <Head :title="t('পণ্য ফেরত ভাউচার', 'Return Voucher') + ' #' + saleReturn.return_number" />

        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex items-center justify-between no-print">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.returns.index')"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >
                        <ArrowLeftIcon class="h-4 w-4" />
                    </Link>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                            {{ t('পণ্য ফেরত ভাউচার', 'Return Voucher') }} #{{ saleReturn.return_number }}
                        </h2>
                        <span class="text-xs text-slate-500">{{ t('তারিখ:', 'Date:') }} {{ saleReturn.return_date }}</span>
                    </div>
                </div>

                <button
                    @click="printVoucher"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-all"
                >
                    <PrinterIcon class="h-4 w-4" />
                    <span>{{ t('ভাউচার প্রিন্ট', 'Print Voucher') }}</span>
                </button>
            </div>

            <!-- Printable Voucher -->
            <div id="voucher-paper" class="rounded-2xl border border-slate-200/90 bg-white p-8 shadow-xs dark:border-slate-700 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200">
                <div class="flex justify-between items-start pb-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <h1 class="text-xl font-black text-rose-600 tracking-tight">
                            {{ t('বিক্রয় পণ্য ফেরত ভাউচার', 'SALE RETURN VOUCHER') }}
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ t('স্টকে পণ্য পুনর্বহাল ও রিফান্ড সম্পন্ন', 'Stock Restocked & Refund Issued') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-slate-900 dark:text-white">{{ saleReturn.return_number }}</div>
                        <div class="text-xs text-slate-400">{{ t('তারিখ:', 'Date:') }} {{ saleReturn.return_date }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 my-6 text-xs">
                    <div>
                        <span class="font-bold text-slate-400 block uppercase tracking-wider text-[11px]">{{ t('কাস্টমার', 'Customer') }}</span>
                        <div class="font-bold text-sm text-slate-900 dark:text-white mt-1">{{ saleReturn.customer?.name || t('খুচরা কাস্টমার', 'Walk-in Customer') }}</div>
                        <div v-if="saleReturn.customer?.phone" class="text-slate-500 mt-0.5">{{ saleReturn.customer?.phone }}</div>
                    </div>
                    <div class="text-right">
                        <span class="font-bold text-slate-400 block uppercase tracking-wider text-[11px]">{{ t('মূল ইনভয়েস', 'Original Sale') }}</span>
                        <div class="font-bold text-sm text-slate-900 dark:text-white mt-1">{{ saleReturn.sale?.invoice_no || t('সরাসরি ফেরত', 'N/A') }}</div>
                        <div class="text-slate-500 mt-0.5">{{ t('প্রক্রিয়াকারী:', 'Processed By:') }} {{ saleReturn.creator?.name || 'Admin' }}</div>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 my-4 text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                            <th class="py-2.5 px-3 text-left">{{ t('পণ্য', 'Item') }}</th>
                            <th class="py-2.5 px-3 text-center">{{ t('একক দর (৳)', 'Unit Price (৳)') }}</th>
                            <th class="py-2.5 px-3 text-center">{{ t('ফেরত পরিমাণ', 'Qty Returned') }}</th>
                            <th class="py-2.5 px-3 text-right">{{ t('মোট (৳)', 'Subtotal (৳)') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="item in saleReturn.items" :key="item.id">
                            <td class="py-2.5 px-3 font-semibold text-slate-900 dark:text-slate-100">{{ item.product?.name }}</td>
                            <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-300">৳{{ formatNumber(item.unit_price) }}</td>
                            <td class="py-2.5 px-3 text-center font-bold text-slate-900 dark:text-slate-100">{{ item.quantity }}</td>
                            <td class="py-2.5 px-3 text-right font-bold text-slate-900 dark:text-slate-100">৳{{ formatNumber(item.subtotal) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex justify-end mt-4">
                    <div class="w-64 space-y-1.5 text-xs border-t border-slate-200 dark:border-slate-700 pt-3">
                        <div class="flex justify-between font-bold text-slate-900 dark:text-white">
                            <span>{{ t('মোট ফেরত মূল্য:', 'Total Return:') }}</span>
                            <span>৳{{ formatNumber(saleReturn.total_amount) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-rose-600">
                            <span>{{ t('রিফান্ড প্রদান:', 'Refunded:') }}</span>
                            <span>৳{{ formatNumber(saleReturn.refund_amount) }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="saleReturn.reason" class="mt-6 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500">
                    <strong class="text-slate-700 dark:text-slate-300">{{ t('ফেরতের কারণ:', 'Reason:') }}</strong> {{ saleReturn.reason }}
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ArrowLeft as ArrowLeftIcon, Printer as PrinterIcon } from 'lucide-vue-next'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    saleReturn: Object,
})

const formatNumber = (val) => {
    return Number(val || 0).toLocaleString('bn-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}

const printVoucher = () => {
    window.print()
}
</script>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background: white !important;
    }
    #voucher-paper {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
}
</style>
