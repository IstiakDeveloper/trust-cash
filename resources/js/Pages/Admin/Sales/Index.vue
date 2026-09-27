<template>
    <Head :title="t('বিক্রির তালিকা', 'Sales List')" />
    <AdminLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                        {{ t('বিক্রির তালিকা ও ইনভয়েস', 'Sales & Invoices') }}
                    </h1>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ t('দোকানের সকল বিক্রয় মেমো ও পেমেন্ট হিস্ট্রি', 'All customer invoices and payment records') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        href="/admin/pos"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm shadow-emerald-600/20 transition-all active:scale-95"
                    >
                        <i class="fas fa-cash-register"></i>
                        <span>{{ t('নতুন বিক্রি (POS)', 'New Sale (POS)') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Sales Count -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('মোট বিক্রয় মেমো', 'Total Invoices') }}</p>
                            <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">
                                {{ summary.total_sales }}
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Amount -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('মোট বিক্রয় মূল্য', 'Total Amount') }}</p>
                            <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">
                                ৳{{ formatNumber(summary.total_amount) }}
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Paid -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('নগদ কালেকশন', 'Total Collected') }}</p>
                            <p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                                ৳{{ formatNumber(summary.total_paid) }}
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-950 text-teal-600 dark:text-teal-400 flex items-center justify-center text-xl">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Due -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('মোট বাকি পাওনা', 'Total Due') }}</p>
                            <p class="text-2xl font-extrabold text-rose-600 dark:text-rose-400 mt-1">
                                ৳{{ formatNumber(summary.total_due) }}
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-950 text-rose-600 dark:text-rose-400 flex items-center justify-center text-xl">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                <form @submit.prevent="applyFilters">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                        <!-- Start Date -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('শুরুর তারিখ', 'Start Date') }}
                            </label>
                            <input type="date" v-model="filters.start_date"
                                class="w-full text-xs font-medium border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 rounded-xl shadow-xs focus:ring-1 focus:ring-indigo-500" />
                        </div>

                        <!-- End Date -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('শেষের তারিখ', 'End Date') }}
                            </label>
                            <input type="date" v-model="filters.end_date"
                                class="w-full text-xs font-medium border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 rounded-xl shadow-xs focus:ring-1 focus:ring-indigo-500" />
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('পেমেন্ট স্ট্যাটাস', 'Payment Status') }}
                            </label>
                            <select v-model="filters.payment_status"
                                class="w-full text-xs font-medium border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 rounded-xl shadow-xs focus:ring-1 focus:ring-indigo-500">
                                <option :value="null">{{ t('সকল স্ট্যাটাস', 'All Statuses') }}</option>
                                <option value="paid">{{ t('পরিশোধিত (Paid)', 'Paid') }}</option>
                                <option value="partial">{{ t('আংশিক (Partial)', 'Partial') }}</option>
                                <option value="due">{{ t('বাকি (Due)', 'Due') }}</option>
                            </select>
                        </div>

                        <!-- Customer Filter -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('কাস্টমার', 'Customer') }}
                            </label>
                            <select v-model="filters.customer_id"
                                class="w-full text-xs font-medium border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 rounded-xl shadow-xs focus:ring-1 focus:ring-indigo-500">
                                <option :value="null">{{ t('সকল কাস্টমার', 'All Customers') }}</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.name }} ({{ customer.phone || 'N/A' }})
                                </option>
                            </select>
                        </div>

                        <!-- Invoice Search -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('ইনভয়েস নং', 'Invoice No') }}
                            </label>
                            <input type="text" v-model="filters.invoice_no" :placeholder="t('মেমো নং দিয়ে খুঁজুন...', 'Search by invoice no...')"
                                class="w-full text-xs font-medium border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 rounded-xl shadow-xs focus:ring-1 focus:ring-indigo-500" />
                        </div>
                    </div>

                    <!-- Filter Action Buttons -->
                    <div class="mt-4 flex items-center justify-end gap-2.5">
                        <button type="button" @click="resetFilters"
                            class="px-3.5 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            {{ t('রিসেট', 'Reset') }}
                        </button>
                        <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-all">
                            {{ t('ফিল্টার প্রয়োগ', 'Apply Filter') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sales Data Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                            <tr>
                                <th class="px-5 py-3 text-left">{{ t('মেমো নং', 'Invoice No') }}</th>
                                <th class="px-4 py-3 text-left">{{ t('তারিখ', 'Date') }}</th>
                                <th class="px-4 py-3 text-left">{{ t('কাস্টমার', 'Customer') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('মোট টাকা', 'Total') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('পরিশোধ', 'Paid') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('বাকি', 'Due') }}</th>
                                <th class="px-4 py-3 text-center">{{ t('স্ট্যাটাস', 'Status') }}</th>
                                <th class="px-5 py-3 text-right">{{ t('অ্যাকশন', 'Actions') }}</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                            <tr v-for="sale in sales.data" :key="sale.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <!-- Invoice -->
                                <td class="px-5 py-3.5 whitespace-nowrap font-bold text-indigo-600 dark:text-indigo-400">
                                    <Link :href="route('admin.sales.show', sale.id)" class="hover:underline">
                                        {{ sale.invoice_no }}
                                    </Link>
                                </td>

                                <!-- Date -->
                                <td class="px-4 py-3.5 whitespace-nowrap text-slate-600 dark:text-slate-300 font-mono">
                                    {{ sale.date }}
                                </td>

                                <!-- Customer -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div v-if="sale.customer">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ sale.customer.name }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ sale.customer.phone || '' }}</div>
                                    </div>
                                    <div v-else class="text-slate-400 italic">
                                        {{ t('সাধারণ কাস্টমার', 'Walk-in Customer') }}
                                    </div>
                                </td>

                                <!-- Amounts -->
                                <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-slate-900 dark:text-white">
                                    ৳{{ formatNumber(sale.total) }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-emerald-600 dark:text-emerald-400">
                                    ৳{{ formatNumber(sale.paid) }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold" :class="sale.due > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400'">
                                    ৳{{ formatNumber(sale.due) }}
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <span class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full" :class="{
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300': sale.payment_status === 'paid',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300': sale.payment_status === 'partial',
                                        'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300': sale.payment_status === 'due'
                                    }">
                                        {{ sale.payment_status === 'paid' ? t('পরিশোধিত', 'Paid') : (sale.payment_status === 'partial' ? t('আংশিক', 'Partial') : t('বাকি', 'Due')) }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-right space-x-2">
                                    <Link :href="route('admin.sales.show', sale.id)"
                                        class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-slate-800"
                                        :title="t('বিস্তারিত', 'View')">
                                        <i class="fas fa-eye"></i>
                                    </Link>
                                    <button @click="printReceipt(sale.id)"
                                        class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-slate-800"
                                        :title="t('প্রিন্ট রসিদ', 'Print')">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <button @click.prevent="confirmDelete(sale)"
                                        class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-slate-800"
                                        :title="t('মুছুন', 'Delete')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="sales.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                    <i class="fas fa-inbox text-3xl mb-2 text-slate-300"></i>
                                    <p class="font-medium text-sm">{{ t('কোন বিক্রয় তথ্য পাওয়া যায়নি', 'No sales found') }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800">
                    <Pagination :links="sales.links" />
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="!!saleToDelete"
            :title="t('মেমো মুছে ফেলা নিশ্চিত করুন', 'Delete Sale')"
            :message="t('আপনি কি নিশ্চিত যে আপনি এই মেমোটি মুছে ফেলতে চান? এতে স্টক এবং একাউন্টের ব্যালেন্স পূর্বের অবস্থায় ফিরে যাবে।', 'Are you sure you want to delete this sale? This will reverse all stock and financial transactions.')"
            :confirm-text="t('হ্যাঁ, মুছে ফেলুন', 'Delete Sale')"
            :cancel-text="t('বাতিল', 'Cancel')"
            @close="saleToDelete = null"
            @confirm="deleteSale"
        />
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import Pagination from '@/Components/Pagination.vue'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    sales: Object,
    filters: Object,
    customers: Array,
    paymentStatuses: Array,
    summary: Object
})

const saleToDelete = ref(null)
const filters = ref({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    payment_status: props.filters.payment_status || null,
    customer_id: props.filters.customer_id || null,
    invoice_no: props.filters.invoice_no || ''
})

const formatNumber = (value) => {
    return Number(value || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const applyFilters = () => {
    router.get(route('admin.sales.index'), filters.value, {
        preserveState: true,
        preserveScroll: true
    })
}

const resetFilters = () => {
    filters.value = {
        start_date: '',
        end_date: '',
        payment_status: null,
        customer_id: null,
        invoice_no: ''
    }
    applyFilters()
}

const printReceipt = (id) => {
    window.open(route('admin.sales.print-receipt', id), '_blank')
}

const confirmDelete = (sale) => {
    saleToDelete.value = sale
}

const deleteSale = () => {
    if (!saleToDelete.value) return

    router.delete(route('admin.sales.destroy', { sale: saleToDelete.value.id }), {
        onSuccess: () => {
            saleToDelete.value = null
        },
        preserveScroll: true,
        preserveState: true
    })
}
</script>
