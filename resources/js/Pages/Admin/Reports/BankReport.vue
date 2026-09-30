<template>
    <AdminLayout :title="t('ব্যাংক ব্যালেন্স রিপোর্ট', 'Bank Balance Report')">
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ t('ব্যাংক ব্যালেন্স রিপোর্ট', 'Bank Balance Report') }}
            </h2>
        </template>

        <!-- SCREEN CONTENT (Hidden during Print) -->
        <div class="no-print space-y-6">
            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Account Select -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ t('ব্যাংক অ্যাকাউন্ট', 'Bank Account') }}
                        </label>
                        <select v-model="filters.account_id"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            @change="applyFilters">
                            <option value="">{{ t('সব অ্যাকাউন্ট', 'All Accounts') }}</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.bank_name }} - {{ account.account_name }}
                            </option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ t('শুরুর তারিখ', 'From Date') }}
                        </label>
                        <input type="date" v-model="filters.from_date"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            @change="applyFilters">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ t('শেষের তারিখ', 'To Date') }}
                        </label>
                        <input type="date" v-model="filters.to_date"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            @change="applyFilters">
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-end gap-2">
                        <button @click="resetFilters"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                            <ArrowPathIcon class="h-4 w-4 mr-1.5" />
                            {{ t('রিসেট', 'Reset') }}
                        </button>
                        <button @click="viewMode = viewMode === 'dashboard' ? 'document' : 'dashboard'"
                            type="button"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                            <span v-if="viewMode === 'dashboard'">📄 {{ t('ওয়ার্ড ভিউ', 'Word View') }}</span>
                            <span v-else>📊 {{ t('ড্যাশবোর্ড', 'Dashboard') }}</span>
                        </button>
                        <button @click="exportReport"
                            class="inline-flex items-center px-3 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700">
                            <DocumentArrowDownIcon class="h-4 w-4 mr-1.5" />
                            {{ t('পিডিএফ', 'PDF') }}
                        </button>
                        <button @click="printReport"
                            class="inline-flex items-center px-3 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800">
                            <PrinterIcon class="h-4 w-4 mr-1.5" />
                            {{ t('প্রিন্ট', 'Print') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- DASHBOARD VIEW -->
            <div v-show="viewMode === 'dashboard'" class="no-print space-y-6">
                <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('মোট অ্যাকাউন্ট', 'Total Accounts') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ summary.total_accounts }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('মোট ব্যালেন্স', 'Total Balance') }}</div>
                    <div class="mt-1 text-2xl font-semibold" :class="getBalanceColorClass(summary.total_balance)">
                        {{ formatPrice(summary.total_balance) }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('মোট ইনফ্লো', 'Total Inflows') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                        {{ formatPrice(summary.total_inflows) }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('মোট আউটফ্লো', 'Total Outflows') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">
                        {{ formatPrice(summary.total_outflows) }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('পণ্য ক্রয়', 'Product Purchases') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">
                        {{ formatPrice(summary.total_product_purchases) }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('পণ্য ফেরত', 'Product Refunds') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                        {{ formatPrice(summary.total_product_refunds) }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('নেট পণ্য পরিমাণ', 'Net Product Amount') }}</div>
                    <div class="mt-1 text-2xl font-semibold" :class="getBalanceColorClass(summary.net_product_amount)">
                        {{ formatPrice(summary.net_product_amount) }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('ইনভয়েস থেকে বিক্রয় পরিমাণ', 'Sale Amount from Invoices') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                        {{ formatPrice(summary.total_sales_from_invoices) }}
                    </div>
                </div>
            </div>

            <!-- Accounts and Transactions List -->
            <div class="space-y-6">
                <TransitionGroup name="fade">
                    <div v-for="report in reports" :key="report.account.id"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <!-- Account Header -->
                        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ report.account.bank }} - {{ report.account.name }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ t('একাউন্ট নং', 'Account No') }}: {{ report.account.number }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('বর্তমান ব্যালেন্স', 'Current Balance') }}</div>
                                    <div class="text-lg font-medium" :class="getBalanceColorClass(report.current_balance)">
                                        {{ formatPrice(report.current_balance) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Summary -->
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ t('মাসিক সারাংশ', 'Monthly Summary') }}</h4>
                            <div class="max-h-80 overflow-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="sticky top-0 z-10 px-4 py-2 text-left bg-gray-50 dark:bg-gray-700">{{ t('মাস', 'Month') }}</th>
                                            <th class="sticky top-0 z-10 px-4 py-2 text-right bg-gray-50 dark:bg-gray-700">{{ t('ইনফ্লো', 'Inflows') }}</th>
                                            <th class="sticky top-0 z-10 px-4 py-2 text-right bg-gray-50 dark:bg-gray-700">{{ t('আউটফ্লো', 'Outflows') }}</th>
                                            <th class="sticky top-0 z-10 px-4 py-2 text-right bg-gray-50 dark:bg-gray-700">{{ t('নেট', 'Net') }}</th>
                                            <th class="sticky top-0 z-10 px-4 py-2 text-right bg-gray-50 dark:bg-gray-700">{{ t('লেনদেন', 'Transactions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="summary in report.monthly_summary" :key="summary.month"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                            <td class="px-4 py-2">{{ summary.month }}</td>
                                            <td class="px-4 py-2 text-right text-green-600">
                                                {{ formatPrice(summary.inflows) }}
                                            </td>
                                            <td class="px-4 py-2 text-right text-red-600">
                                                {{ formatPrice(summary.outflows) }}
                                            </td>
                                            <td class="px-4 py-2 text-right" :class="getBalanceColorClass(summary.net)">
                                                {{ formatPrice(summary.net) }}
                                            </td>
                                            <td class="px-4 py-2 text-right">{{ summary.transaction_count }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Detailed Transactions -->
                        <div class="max-h-[32rem] overflow-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="sticky top-0 z-10 px-4 py-3 text-left bg-gray-50 dark:bg-gray-700">{{ t('তারিখ', 'Date') }}</th>
                                        <th class="sticky top-0 z-10 px-4 py-3 text-left bg-gray-50 dark:bg-gray-700">{{ t('বিবরণ', 'Description') }}</th>
                                        <th class="sticky top-0 z-10 px-4 py-3 text-left bg-gray-50 dark:bg-gray-700">{{ t('ধরন', 'Type') }}</th>
                                        <th class="sticky top-0 z-10 px-4 py-3 text-right bg-gray-50 dark:bg-gray-700">{{ t('পরিমাণ', 'Amount') }}</th>
                                        <th class="sticky top-0 z-10 px-4 py-3 text-right bg-gray-50 dark:bg-gray-700">{{ t('ব্যালেন্স', 'Balance') }}</th>
                                        <th class="sticky top-0 z-10 px-4 py-3 text-left bg-gray-50 dark:bg-gray-700">{{ t('তৈরি করেছেন', 'Created By') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="transaction in report.transactions" :key="transaction.id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="px-4 py-3">{{ formatDate(transaction.date) }}</td>
                                        <td class="px-4 py-3">{{ transaction.description }}</td>
                                        <td class="px-4 py-3">
                                            <span :class="getTransactionTypeClass(transaction.type)"
                                                class="px-2 py-1 rounded-full text-xs font-medium">
                                                {{ formatTransactionType(transaction.type) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right" :class="getAmountColorClass(transaction.type)">
                                            {{ formatPrice(transaction.amount) }}
                                        </td>
                                        <td class="px-4 py-3 text-right"
                                            :class="getBalanceColorClass(transaction.running_balance)">
                                            {{ formatPrice(transaction.running_balance) }}
                                        </td>
                                        <td class="px-4 py-3">{{ transaction.created_by }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </TransitionGroup>
                </div>
            </div>
        </div>

        <!-- ============================================================
             B&W WORD REPORT VIEW & PRINT / PDF TEMPLATE
             ============================================================ -->
        <div :class="[viewMode === 'document' ? 'block py-4' : 'print-only']">
            <WordReportLayout
                ref="wordReportRef"
                :title="t('ব্যাংক ব্যালেন্স রিপোর্ট', 'Bank Balance Report')"
                :date-range="`${filters.from_date || '-'} ${t('হতে', 'to')} ${filters.to_date || '-'}`"
                orientation="portrait"
                file-name="bank-balance-report.pdf"
            >
                <!-- Summary Table -->
                <div class="mb-5">
                    <div class="text-xs font-bold uppercase tracking-wider mb-1">{{ t('সামগ্রিক সারাংশ', 'Overall Summary') }}</div>
                    <table class="word-table">
                        <thead>
                            <tr>
                                <th class="text-center">{{ t('মোট অ্যাকাউন্ট', 'Total Accounts') }}</th>
                                <th class="text-right">{{ t('মোট ব্যালেন্স', 'Total Balance') }}</th>
                                <th class="text-right">{{ t('মোট ইনফ্লো (জমা)', 'Total Inflows') }}</th>
                                <th class="text-right">{{ t('মোট আউটফ্লো (খরচ)', 'Total Outflows') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center font-bold">{{ summary.total_accounts }}</td>
                                <td class="text-right font-bold">{{ formatPrice(summary.total_balance) }}</td>
                                <td class="text-right font-bold text-green-700">{{ formatPrice(summary.total_inflows) }}</td>
                                <td class="text-right font-bold text-red-700">{{ formatPrice(summary.total_outflows) }}</td>
                            </tr>
                            <tr v-if="summary.total_product_purchases || summary.total_product_refunds || summary.total_sales_from_invoices">
                                <td class="font-semibold">{{ t('পণ্য ক্রয় / ফেরত:', 'Purchases / Refunds:') }}</td>
                                <td class="text-right">{{ formatPrice(summary.total_product_purchases) }} / {{ formatPrice(summary.total_product_refunds) }}</td>
                                <td class="font-semibold">{{ t('ইনভয়েস বিক্রয়:', 'Invoice Sales:') }}</td>
                                <td class="text-right font-bold">{{ formatPrice(summary.total_sales_from_invoices) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Account Sections -->
                <div v-for="(report, rIndex) in reports" :key="'wr_' + report.account.id" class="mb-6">
                    <div v-if="rIndex > 0" class="page-break my-4"></div>

                    <!-- Account Subheader -->
                    <div class="border border-black bg-gray-100 px-3 py-1.5 font-bold text-sm flex justify-between items-center mb-1">
                        <span>{{ report.account.bank }} - {{ report.account.name }} (A/C: {{ report.account.number }})</span>
                        <span class="text-xs font-normal">
                            {{ t('প্রারম্ভিক:', 'Opening:') }} <strong>{{ formatPrice(report.opening_balance) }}</strong> |
                            {{ t('পূর্ববর্তী:', 'Previous:') }} <strong>{{ formatPrice(report.previous_balance) }}</strong>
                        </span>
                    </div>

                    <!-- Detailed Transactions Table -->
                    <table v-if="report.transactions && report.transactions.length > 0" class="word-table">
                        <thead>
                            <tr>
                                <th style="width: 12%;">{{ t('তারিখ', 'Date') }}</th>
                                <th style="width: 38%;">{{ t('বিবরণ', 'Description') }}</th>
                                <th class="text-center" style="width: 10%;">{{ t('ধরন', 'Type') }}</th>
                                <th class="text-right" style="width: 13%;">{{ t('ইনফ্লো (জমা)', 'Inflow') }}</th>
                                <th class="text-right" style="width: 13%;">{{ t('আউটফ্লো (খরচ)', 'Outflow') }}</th>
                                <th class="text-right" style="width: 14%;">{{ t('ব্যালেন্স', 'Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="tx in report.transactions" :key="'wtx_' + tx.id">
                                <td class="text-center">{{ formatDate(tx.date) }}</td>
                                <td>{{ tx.description }}</td>
                                <td class="text-center">{{ formatTransactionType(tx.type) }}</td>
                                <td class="text-right">{{ tx.type === 'in' ? formatPrice(tx.amount) : '-' }}</td>
                                <td class="text-right">{{ tx.type === 'out' ? formatPrice(tx.amount) : '-' }}</td>
                                <td class="text-right font-medium">{{ formatPrice(tx.running_balance) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row font-bold">
                                <td colspan="3" class="text-right">{{ t('মোট / বর্তমান ব্যালেন্স', 'Total / Current Balance') }}</td>
                                <td class="text-right">{{ formatPrice(getAccountTotalInflows(report)) }}</td>
                                <td class="text-right">{{ formatPrice(getAccountTotalOutflows(report)) }}</td>
                                <td class="text-right">{{ formatPrice(report.current_balance) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                    <div v-else class="text-center py-3 text-xs italic border border-black border-dashed">
                        {{ t('এই সময়কালে কোনো লেনদেন পাওয়া যায়নি', 'No transactions found for this period') }}
                    </div>
                </div>
            </WordReportLayout>
        </div>
    </AdminLayout>
</template>


<script setup>
import { ref, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import WordReportLayout from '@/Components/Reports/WordReportLayout.vue'
import { DocumentArrowDownIcon, ArrowPathIcon, PrinterIcon } from '@heroicons/vue/24/outline'
import { useLanguage } from '@/composables/useLanguage'
import { getNumberLocale } from '@/utils'

const { currentLang, t } = useLanguage()

const props = defineProps({
    accounts: {
        type: Array,
        required: true
    },
    filters: {
        type: Object,
        required: true
    },
    reports: {
        type: Array,
        required: true
    },
    summary: {
        type: Object,
        required: true,
        default: () => ({
            total_accounts: 0,
            total_balance: 0,
            total_inflows: 0,
            total_outflows: 0,
            total_product_purchases: 0,
            total_product_refunds: 0,
            net_product_amount: 0
        })
    }
})

const viewMode = ref('dashboard')
const wordReportRef = ref(null)

const filters = ref({
    account_id: props.filters.account_id,
    from_date: props.filters.from_date,
    to_date: props.filters.to_date
})

const formatPrice = (amount) => {
    const num = Number(amount || 0)
    const hasDecimal = Math.abs(num % 1) > 0.00001
    return new Intl.NumberFormat(getNumberLocale(), {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: hasDecimal ? 2 : 0,
        maximumFractionDigits: hasDecimal ? 2 : 0
    }).format(num)
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getBalanceColorClass = (amount) => {
    if (amount > 0) return 'text-green-600 dark:text-green-400'
    if (amount < 0) return 'text-red-600 dark:text-red-400'
    return 'text-gray-600 dark:text-gray-400'
}

const getAmountColorClass = (type) => {
    return type === 'in' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
}

const formatTransactionType = (type) => {
    return type === 'in' ? t('ইনফ্লো', 'Inflow') : t('আউটফ্লো', 'Outflow')
}

const getTransactionTypeClass = (type) => {
    return type === 'in'
        ? 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900'
        : 'bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900'
}

const applyFilters = () => {
    router.get(route('admin.reports.bank'), {
        account_id: filters.value.account_id,
        from_date: filters.value.from_date,
        to_date: filters.value.to_date
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['reports', 'summary']
    })
}

const exportReport = async () => {
    const prev = viewMode.value
    viewMode.value = 'document'
    await nextTick()
    setTimeout(async () => {
        if (wordReportRef.value) {
            await wordReportRef.value.downloadPdf()
        }
        viewMode.value = prev
    }, 120)
}

const getAccountTotalInflows = (report) => {
    if (!report.transactions || !Array.isArray(report.transactions)) return 0
    return report.transactions
        .filter(t => t.type === 'in')
        .reduce((sum, t) => sum + Number(t.amount || 0), 0)
}

const getAccountTotalOutflows = (report) => {
    if (!report.transactions || !Array.isArray(report.transactions)) return 0
    return report.transactions
        .filter(t => t.type === 'out')
        .reduce((sum, t) => sum + Number(t.amount || 0), 0)
}

const printReport = () => {
    const prev = viewMode.value
    viewMode.value = 'document'
    setTimeout(() => {
        window.print()
        viewMode.value = prev
    }, 150)
}

const resetFilters = () => {
    filters.value = {
        account_id: '',
        from_date: new Date().toISOString().split('T')[0],
        to_date: new Date().toISOString().split('T')[0]
    }
    applyFilters()
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.print-only {
    display: none;
}

@media print {
    .no-print {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }
}
</style>
