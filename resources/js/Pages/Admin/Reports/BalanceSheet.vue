<template>
    <AdminLayout :title="t('ব্যালেন্স শিট', 'Balance Sheet')">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        {{ t('ব্যালেন্স শিট', 'Balance Sheet') }}
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ t('নির্দিষ্ট সময়ের ফান্ড, দায়-দেনা ও সম্পত্তির হিসাব', 'Financial overview of Fund, Liabilities & Assets') }}
                    </p>
                </div>

                <!-- Filter Controls -->
                <div class="flex items-center space-x-3 flex-wrap gap-y-2 no-print">
                    <!-- Year Selection -->
                    <div class="flex items-center space-x-2">
                        <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ t('বছর', 'Year') }}</label>
                        <select v-model="selectedYear" @change="handleDateChange"
                            class="text-xs font-medium border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
                            <option v-for="year in years" :key="year" :value="year">
                                {{ formatNumber(year) }}
                            </option>
                        </select>
                    </div>

                    <!-- Month Selection -->
                    <div class="flex items-center space-x-2">
                        <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ t('মাস', 'Month') }}</label>
                        <select v-model="selectedMonth" @change="handleDateChange"
                            class="text-xs font-medium border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
                            <option v-for="month in months" :key="month.value" :value="month.value">
                                {{ getMonthName(month.value) }}
                            </option>
                        </select>
                    </div>

                    <!-- Download PDF Button -->
                    <button @click="downloadPDF" :disabled="isDownloadDisabled"
                        class="inline-flex items-center px-3.5 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:opacity-50 transition-all">
                        <svg v-if="!isDownloading" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5 animate-spin" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ isDownloading ? t('ডাউনলোড হচ্ছে...', 'Downloading...') : t('পিডিএফ ডাউনলোড', 'Download PDF') }}
                    </button>

                    <!-- Print Button -->
                    <button @click="printReport"
                        class="inline-flex items-center px-3.5 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-500 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        {{ t('প্রিন্ট', 'Print') }}
                    </button>
                </div>

            </div>
        </template>

        <div class="py-6">
            <div id="balance-sheet-content" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Balance Status Banner -->
                <div class="flex items-center justify-between px-5 py-3 rounded-xl border transition-colors shadow-sm"
                     :class="[
                         isBalanced
                             ? 'bg-emerald-50/80 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200'
                             : 'bg-amber-50/80 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200'
                     ]">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-2.5 w-2.5 rounded-full"
                              :class="isBalanced ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                        <span class="text-xs sm:text-sm font-semibold">
                            {{ isBalanced
                                ? t('ব্যালেন্স শিট সমতাপ্রাপ্ত (Balanced)', 'Balance Sheet is Balanced')
                                : t('ব্যালেন্স শিটে অমিল রয়েছে (Unbalanced)', 'Balance Sheet has a difference') }}
                        </span>
                    </div>
                    <div v-if="!isBalanced" class="text-xs font-mono font-bold">
                        {{ t('পার্থক্য:', 'Difference:') }} {{ formatCurrency(Math.abs(fund_and_liabilities.total - property_and_assets.total)) }}
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Fund & Liabilities -->
                    <div class="overflow-hidden bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm transition-colors">
                        <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/90 flex items-center justify-between">
                            <h3 class="text-sm sm:text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                {{ t('ফান্ড ও দেনা', 'Fund & Liabilities') }}
                            </h3>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                {{ t('দায় ও মূলধন', 'Capital & Debts') }}
                            </span>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50/40 dark:bg-slate-900/30 text-xs font-semibold text-slate-600 dark:text-slate-400">
                                    <th class="px-5 py-2.5 text-left border-r border-slate-200 dark:border-slate-700">{{ t('বিবরণ', 'Description') }}</th>
                                    <th class="px-5 py-2.5 text-right">{{ t('পরিমাণ', 'Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                <!-- Fund Row -->
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                    <td class="px-5 py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-200">
                                        {{ t('ফান্ড (মূলধন)', 'Fund (Capital)') }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs sm:text-sm font-bold font-mono text-emerald-600 dark:text-emerald-400">
                                        {{ formatCurrency(fund_and_liabilities.fund.period) }}
                                    </td>
                                </tr>

                                <!-- Net Profit Row -->
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                    <td class="px-5 py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-200">
                                        {{ t('নিট লাভ', 'Net Profit') }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs sm:text-sm font-bold font-mono"
                                        :class="fund_and_liabilities.net_profit.period >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                        {{ formatCurrency(fund_and_liabilities.net_profit.period) }}
                                    </td>
                                </tr>

                                <!-- Supplier Due Row (NEW) -->
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                    <td class="px-5 py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-200">
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ t('সরবরাহকারী বকেয়া (পাওনাদার)', 'Supplier Due (Payable)') }}</span>
                                            <span v-if="(fund_and_liabilities.supplier_due?.period ?? 0) > 0"
                                                  class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                                {{ t('দেনা', 'Due') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs sm:text-sm font-bold font-mono text-rose-600 dark:text-rose-400">
                                        {{ formatCurrency(fund_and_liabilities.supplier_due?.period ?? 0) }}
                                    </td>
                                </tr>

                                <!-- Balancing Row for visual alignment -->
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                    <td class="px-5 py-3.5 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm text-slate-400 dark:text-slate-500 italic">
                                        {{ t('অন্যান্য স্বল্পমেয়াদী দায়', 'Other Current Liabilities') }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right text-xs sm:text-sm font-mono text-slate-400 dark:text-slate-500">
                                        {{ formatCurrency(0) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <!-- Total Row -->
                                <tr class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-100/70 dark:bg-slate-900/60 font-bold">
                                    <td class="px-5 py-3.5 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-extrabold text-slate-900 dark:text-slate-100">
                                        {{ t('মোট ফান্ড ও দেনা', 'Total Fund & Liabilities') }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right text-sm sm:text-base font-extrabold font-mono text-indigo-600 dark:text-indigo-400">
                                        {{ formatCurrency(fund_and_liabilities.total) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Property & Assets -->
                    <div class="overflow-hidden bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm transition-colors">
                        <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/90 flex items-center justify-between">
                            <h3 class="text-sm sm:text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                {{ t('সম্পদ ও সম্পত্তি', 'Property & Assets') }}
                            </h3>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                {{ t('সম্পত্তির বর্তমান মূল্য', 'Assets & Resources') }}
                            </span>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50/40 dark:bg-slate-900/30 text-xs font-semibold text-slate-600 dark:text-slate-400">
                                    <th class="px-5 py-2.5 text-left border-r border-slate-200 dark:border-slate-700">{{ t('বিবরণ', 'Description') }}</th>
                                    <th class="px-5 py-2.5 text-right">{{ t('পরিমাণ', 'Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                <!-- Bank Balance Row -->
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                    <td class="px-5 py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-200">
                                        {{ t('ব্যাংক ব্যালেন্স', 'Bank Balance') }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs sm:text-sm font-bold font-mono text-slate-800 dark:text-slate-100">
                                        {{ formatCurrency(property_and_assets.bank_balance.period) }}
                                    </td>
                                </tr>

                                <!-- Customer Due Row -->
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                    <td class="px-5 py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-200">
                                        {{ t('গ্রাহক বকেয়া (পাওনা)', 'Customer Due (Receivable)') }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs sm:text-sm font-bold font-mono text-slate-800 dark:text-slate-100">
                                        {{ formatCurrency(property_and_assets.customer_due.period) }}
                                    </td>
                                </tr>

                                <!-- Fixed Assets Row -->
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                    <td class="px-5 py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-200">
                                        <div class="flex items-center justify-between">
                                            <span>{{ t('স্থায়ী সম্পদ', 'Fixed Assets') }}</span>
                                            <span v-if="property_and_assets.fixed_assets_breakdown && property_and_assets.fixed_assets_breakdown.length > 0"
                                                  class="text-[11px] font-normal text-indigo-600 dark:text-indigo-400">
                                                ({{ formatNumber(property_and_assets.fixed_assets_breakdown.length) }} {{ t('টি সম্পদ', 'assets') }})
                                            </span>
                                        </div>
                                        <!-- Asset breakdown tags -->
                                        <div v-if="property_and_assets.fixed_assets_breakdown && property_and_assets.fixed_assets_breakdown.length > 0"
                                             class="mt-1.5 flex flex-wrap gap-1.5">
                                            <span v-for="item in property_and_assets.fixed_assets_breakdown" :key="item.name"
                                                  class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-slate-700/70 text-slate-700 dark:text-slate-300">
                                                {{ item.name }}: <strong class="ml-1 font-mono text-indigo-600 dark:text-indigo-400">{{ formatCurrency(item.amount) }}</strong>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs sm:text-sm font-bold font-mono text-slate-800 dark:text-slate-100">
                                        {{ formatCurrency(property_and_assets.fixed_assets) }}
                                    </td>
                                </tr>

                                <!-- Stock Value Row -->
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                    <td class="px-5 py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-200">
                                        {{ t('স্টক মূল্য', 'Stock Value') }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs sm:text-sm font-bold font-mono text-emerald-600 dark:text-emerald-400">
                                        {{ formatCurrency(property_and_assets.stock_value.period) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <!-- Total Row -->
                                <tr class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-100/70 dark:bg-slate-900/60 font-bold">
                                    <td class="px-5 py-3.5 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-extrabold text-slate-900 dark:text-slate-100">
                                        {{ t('মোট সম্পদ ও সম্পত্তি', 'Total Property & Assets') }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right text-sm sm:text-base font-extrabold font-mono text-indigo-600 dark:text-indigo-400">
                                        {{ formatCurrency(property_and_assets.total) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRINT AREA -->
        <div class="print-area">
            <div class="print-header">
                <h1>{{ t('ব্যালেন্স শিট', 'Balance Sheet') }}</h1>
                <p>{{ t('সময়কাল', 'Period') }}: {{ filters.start_date }} — {{ filters.end_date }}</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width:50%">{{ t('ফান্ড ও দেনা', 'Fund & Liabilities') }}</th>
                        <th class="text-right" style="width:50%">{{ t('পরিমাণ', 'Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ t('ফান্ড (মূলধন)', 'Fund (Capital)') }}</td>
                        <td class="text-right">{{ formatCurrency(fund_and_liabilities.fund) }}</td>
                    </tr>
                    <tr>
                        <td>{{ t('মোট দেনা', 'Total Liabilities') }}</td>
                        <td class="text-right">{{ formatCurrency(fund_and_liabilities.total_liabilities) }}</td>
                    </tr>
                    <tr v-for="item in fund_and_liabilities.liabilities" :key="item.name">
                        <td style="padding-left:16px">{{ item.name }}</td>
                        <td class="text-right">{{ formatCurrency(item.amount) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>{{ t('মোট ফান্ড ও দেনা', 'Total Fund & Liabilities') }}</strong></td>
                        <td class="text-right"><strong>{{ formatCurrency(fund_and_liabilities.total) }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            <table style="margin-top:12px">
                <thead>
                    <tr>
                        <th style="width:50%">{{ t('সম্পদ ও সম্পত্তি', 'Property & Assets') }}</th>
                        <th class="text-right" style="width:50%">{{ t('পরিমাণ', 'Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ t('ব্যাংক ব্যালেন্স', 'Bank Balance') }}</td>
                        <td class="text-right">{{ formatCurrency(property_and_assets.bank_balance?.period ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>{{ t('নগদ', 'Cash') }}</td>
                        <td class="text-right">{{ formatCurrency(property_and_assets.cash?.period ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>{{ t('গ্রাহক বকেয়া', 'Customer Receivables') }}</td>
                        <td class="text-right">{{ formatCurrency(property_and_assets.receivable?.period ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>{{ t('স্টক মূল্য', 'Stock Value') }}</td>
                        <td class="text-right">{{ formatCurrency(property_and_assets.stock_value?.period ?? 0) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>{{ t('মোট সম্পদ', 'Total Assets') }}</strong></td>
                        <td class="text-right"><strong>{{ formatCurrency(property_and_assets.total) }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            <div class="print-footer">
                <span>{{ t('মুদ্রণের তারিখ', 'Printed on') }}: {{ new Date().toLocaleDateString() }}</span>
                <span>{{ t('ব্যালেন্স শিট', 'Balance Sheet') }}</span>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { useLanguage } from '@/composables/useLanguage'

const { t, formatCurrency, formatNumber, isBangla } = useLanguage()

const props = defineProps({
    filters: { type: Object, required: true },
    fund_and_liabilities: { type: Object, required: true },
    property_and_assets: { type: Object, required: true },
})

const currentYear = new Date().getFullYear()
const currentMonth = new Date().getMonth() + 1

const months = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' }
]

const years = [currentYear - 2, currentYear - 1, currentYear, currentYear + 1]

const extractYear = (dateString) => {
    if (!dateString) return null
    const date = new Date(dateString + 'T00:00:00')
    return date.getFullYear()
}

const extractMonth = (dateString) => {
    if (!dateString) return null
    const date = new Date(dateString + 'T00:00:00')
    return date.getMonth() + 1
}

const selectedYear = ref(extractYear(props.filters.start_date) || currentYear)
const selectedMonth = ref(extractMonth(props.filters.start_date) || currentMonth)
const isDownloading = ref(false)
const isDownloadDisabled = ref(false)

const isBalanced = computed(() => {
    const diff = Math.abs((props.fund_and_liabilities?.total || 0) - (props.property_and_assets?.total || 0))
    return diff < 0.01
})

const getMonthName = (monthNumber) => {
    const names = {
        1: ['জানুয়ারি', 'January'], 2: ['ফেব্রুয়ারি', 'February'], 3: ['মার্চ', 'March'],
        4: ['এপ্রিল', 'April'], 5: ['মে', 'May'], 6: ['জুন', 'June'],
        7: ['জুলাই', 'July'], 8: ['আগস্ট', 'August'], 9: ['সেপ্টেম্বর', 'September'],
        10: ['অক্টোবর', 'October'], 11: ['নভেম্বর', 'November'], 12: ['ডিসেম্বর', 'December']
    }
    const name = names[monthNumber]
    return name ? t(name[0], name[1]) : ''
}

const handleDateChange = () => {
    const year = selectedYear.value
    const month = selectedMonth.value

    const startDate = new Date(year, month - 1, 1)
    const endDate = new Date(year, month, 0)

    const formatDate = (date) => {
        const y = date.getFullYear()
        const m = String(date.getMonth() + 1).padStart(2, '0')
        const d = String(date.getDate()).padStart(2, '0')
        return `${y}-${m}-${d}`
    }

    router.get(route('admin.reports.balance-sheet'), {
        start_date: formatDate(startDate),
        end_date: formatDate(endDate),
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const downloadPDF = async () => {
    isDownloading.value = true
    isDownloadDisabled.value = true

    try {
        const year = selectedYear.value
        const month = selectedMonth.value

        const startDate = new Date(year, month - 1, 1)
        const endDate = new Date(year, month, 0)

        const formatDate = (date) => {
            const y = date.getFullYear()
            const m = String(date.getMonth() + 1).padStart(2, '0')
            const d = String(date.getDate()).padStart(2, '0')
            return `${y}-${m}-${d}`
        }

        const url = route('admin.reports.balance-sheet.download') +
            `?start_date=${formatDate(startDate)}&end_date=${formatDate(endDate)}&locale=${isBangla.value ? 'bn' : 'en'}`

        window.location.href = url

        setTimeout(() => {
            isDownloading.value = false
            isDownloadDisabled.value = false
        }, 1500)
    } catch (error) {
        console.error('Error downloading PDF:', error)
        isDownloading.value = false
        isDownloadDisabled.value = false
    }
}

const printReport = () => {
    window.print()
}
</script>


<style>
@media print {
    body * {
        visibility: hidden;
    }

    #balance-sheet-content,
    #balance-sheet-content * {
        visibility: visible;
    }

    #balance-sheet-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    .print\:hidden {
        display: none;
    }

    .print\:block {
        display: block !important;
    }
}
</style>
