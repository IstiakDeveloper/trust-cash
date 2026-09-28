<script setup>
import { ref, watch, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLanguage } from '@/composables/useLanguage';
import { formatYear, getNumberLocale } from '@/utils';

const { t, isBangla } = useLanguage();

const props = defineProps({
    bankAccounts: Array,
    selectedAccount: Object,
    selectedBankAccount: [Number, String, null],
    selectedMonth: Number,
    selectedYear: Number,
    previousMonthBalance: Number,
    dailyTransactions: Array,
    monthTotals: Object,
    currentAccountBalance: Number,
    filters: Object
});

const currentMonth = ref(props.selectedMonth);
const currentYear = ref(props.selectedYear);
const selectedBankAccount = ref(props.selectedBankAccount ?? (props.selectedAccount?.id ? String(props.selectedAccount.id) : ''));

const months = [
    { id: 1, name: 'January' },
    { id: 2, name: 'February' },
    { id: 3, name: 'March' },
    { id: 4, name: 'April' },
    { id: 5, name: 'May' },
    { id: 6, name: 'June' },
    { id: 7, name: 'July' },
    { id: 8, name: 'August' },
    { id: 9, name: 'September' },
    { id: 10, name: 'October' },
    { id: 11, name: 'November' },
    { id: 12, name: 'December' }
];

const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString(getNumberLocale());
};

const downloadPdf = () => {
    const params = new URLSearchParams({
        month: currentMonth.value,
        year: currentYear.value,
        bank_account_id: selectedBankAccount.value || '',
        locale: isBangla.value ? 'bn' : 'en'
    });

    window.location.href = `${route('admin.reports.bank-transactions.pdf')}?${params}`;
};

const printReport = () => {
    window.print();
};

const formatAmount = (amount) => {
    const num = Number(amount) || 0;
    const hasDecimal = Math.abs(num - Math.round(num)) >= 0.0001;
    return num.toLocaleString(getNumberLocale(), {
        minimumFractionDigits: hasDecimal ? 2 : 0,
        maximumFractionDigits: 2
    });
};

const getMonthName = (month) => {
    const names = {
        1: ['জানুয়ারি', 'January'],
        2: ['ফেব্রুয়ারি', 'February'],
        3: ['মার্চ', 'March'],
        4: ['এপ্রিল', 'April'],
        5: ['মে', 'May'],
        6: ['জুন', 'June'],
        7: ['জুলাই', 'July'],
        8: ['আগস্ট', 'August'],
        9: ['সেপ্টেম্বর', 'September'],
        10: ['অক্টোবর', 'October'],
        11: ['নভেম্বর', 'November'],
        12: ['ডিসেম্বর', 'December']
    };
    const name = names[month];
    return name ? t(name[0], name[1]) : month;
};

const endingBalance = computed(() => {
    if (!props.dailyTransactions || props.dailyTransactions.length === 0) {
        return props.previousMonthBalance || 0;
    }
    return props.dailyTransactions[props.dailyTransactions.length - 1]?.balance ?? props.previousMonthBalance;
});

const isBalanceMatched = computed(() => {
    return Math.abs(Number(endingBalance.value) - Number(props.currentAccountBalance || 0)) < 0.01;
});

watch([currentMonth, currentYear, selectedBankAccount], () => {
    router.get(route('admin.bank-transaction-report'), {
        month: currentMonth.value,
        year: currentYear.value,
        bank_account_id: selectedBankAccount.value || ''
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
});
</script>

<template>
    <AdminLayout :title="t('ব্যাংক লেনদেন রিপোর্ট', 'Bank Transaction Report')">

        <Head :title="t('ব্যাংক লেনদেন রিপোর্ট', 'Bank Transaction Report')" />

        <div class="py-6">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow-xl sm:rounded-lg overflow-hidden border border-gray-200">
                    <!-- Filter Section -->
                    <div class="p-6 bg-white border-b border-gray-200 no-print">
                        <div class="flex flex-wrap gap-4 items-end">
                            <div class="flex-1 min-w-[240px]">
                                <label class="block mb-1 text-sm font-medium text-gray-700">
                                    {{ t('ব্যাংক অ্যাকাউন্ট', 'Bank Account') }}
                                </label>
                                <select v-model="selectedBankAccount"
                                    class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                    <option value="">{{ t('সকল ব্যাংক অ্যাকাউন্ট (একত্রে / Consolidated)', 'All Bank Accounts (Consolidated)') }}</option>
                                    <option v-for="account in bankAccounts" :key="account.id" :value="String(account.id)">
                                        {{ account.account_name }} - {{ account.bank_name }}
                                    </option>
                                </select>
                            </div>

                            <div class="w-[150px]">
                                <label class="block mb-1 text-sm font-medium text-gray-700">
                                    {{ t('মাস', 'Month') }}
                                </label>
                                <select v-model="currentMonth"
                                    class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                    <option v-for="month in months" :key="month.id" :value="month.id">
                                        {{ getMonthName(month.id) }}
                                    </option>
                                </select>
                            </div>

                            <div class="w-[120px]">
                                <label class="block mb-1 text-sm font-medium text-gray-700">
                                    {{ t('বছর', 'Year') }}
                                </label>
                                <select v-model="currentYear"
                                    class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                    <option v-for="year in years" :key="year" :value="year">
                                        {{ formatYear(year) }}
                                    </option>
                                </select>
                            </div>

                            <div class="flex items-center gap-2">
                                <button @click="downloadPdf"
                                    class="bg-red-600 text-white px-3.5 py-2 text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 flex items-center gap-1.5 shadow-sm transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    {{ t('পিডিএফ ডাউনলোড', 'Download PDF') }}
                                </button>
                                <button @click="printReport"
                                    class="bg-indigo-600 text-white px-3.5 py-2 text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center gap-1.5 shadow-sm transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    {{ t('প্রিন্ট', 'Print') }}
                                </button>
                            </div>
                        </div>

                        <!-- Quick Balance Summary Bar -->
                        <div class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-3 pt-4 border-t border-gray-100">
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <span class="text-xs text-gray-500 block">{{ t('পূর্বের ব্যালেন্স', 'Previous Balance') }}</span>
                                <span class="text-sm font-semibold text-gray-800">{{ formatAmount(previousMonthBalance) }}</span>
                            </div>
                            <div class="p-3 bg-emerald-50 rounded-lg border border-emerald-200">
                                <span class="text-xs text-emerald-700 block">{{ t('মোট ডিপোজিট (+)', 'Total Deposit (+)') }}</span>
                                <span class="text-sm font-semibold text-emerald-700">{{ formatAmount(monthTotals?.in?.total) }}</span>
                            </div>
                            <div class="p-3 bg-rose-50 rounded-lg border border-rose-200">
                                <span class="text-xs text-rose-700 block">{{ t('মোট উত্তোলন (-)', 'Total Withdrawal (-)') }}</span>
                                <span class="text-sm font-semibold text-rose-700">{{ formatAmount(monthTotals?.out?.total) }}</span>
                            </div>
                            <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <span class="text-xs text-blue-700 block">{{ t('মাসের শেষ ব্যালেন্স', 'Month End Balance') }}</span>
                                <span class="text-sm font-bold text-blue-700">{{ formatAmount(endingBalance) }}</span>
                            </div>
                            <div class="p-3 rounded-lg border" :class="isBalanceMatched ? 'bg-green-50 border-green-300' : 'bg-amber-50 border-amber-300'">
                                <span class="text-xs block" :class="isBalanceMatched ? 'text-green-700' : 'text-amber-700'">
                                    {{ t('বর্তমান ব্যাংক ব্যালেন্স', 'Current Bank Balance') }}
                                </span>
                                <div class="flex items-center gap-1">
                                    <span class="text-sm font-bold" :class="isBalanceMatched ? 'text-green-800' : 'text-amber-800'">
                                        {{ formatAmount(currentAccountBalance) }}
                                    </span>
                                    <span v-if="isBalanceMatched" class="text-xs font-bold text-green-700 bg-green-200 px-1.5 py-0.5 rounded-full" title="মাসের শেষ ব্যালেন্স ও বর্তমান ব্যালেন্স হুবহু মিলেছে">
                                        ✓ {{ t('মিলেছে', 'Matched') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Table -->
                    <div class="max-h-[70vh] overflow-auto p-4">
                        <table class="w-full border-collapse border border-gray-400 text-xs">
                            <thead>
                                <tr>
                                    <th rowspan="2"
                                        class="sticky top-0 z-30 border border-gray-400 bg-gray-100 px-3 py-2 text-center font-bold text-gray-900 min-w-[90px]">
                                        {{ t('তারিখ', 'Date') }}
                                    </th>
                                    <th colspan="5"
                                        class="sticky top-0 z-30 border border-gray-400 bg-emerald-100 px-3 py-2 text-center font-bold text-emerald-900">
                                        {{ t('ডিপোজিট (জমা)', 'Deposit (Inflow)') }}
                                    </th>
                                    <th colspan="7"
                                        class="sticky top-0 z-30 border border-gray-400 bg-rose-100 px-3 py-2 text-center font-bold text-rose-900">
                                        {{ t('উত্তোলন (খরচ / পরিশোধ)', 'Withdrawal (Outflow)') }}
                                    </th>
                                    <th rowspan="2"
                                        class="sticky top-0 z-30 border border-gray-400 bg-gray-100 px-3 py-2 text-right font-bold text-gray-900 min-w-[110px]">
                                        {{ t('ব্যাংক ব্যালেন্স', 'Bank Balance') }}
                                    </th>
                                </tr>
                                <tr>
                                    <!-- IN subcategories (5 columns) -->
                                    <th class="sticky top-8 z-30 border border-gray-400 bg-emerald-50 px-2 py-1.5 text-center font-semibold text-gray-800 min-w-[70px]">
                                        {{ t('ফান্ড', 'Fund') }}
                                    </th>
                                    <th class="sticky top-8 z-30 border border-gray-400 bg-emerald-50 px-2 py-1.5 text-center font-semibold text-gray-800 min-w-[85px]">
                                        {{ t('বিক্রয় গ্রহণ', 'Sale Receive') }}
                                    </th>
                                    <th class="sticky top-8 z-30 border border-gray-400 bg-emerald-50 px-2 py-1.5 text-center font-semibold text-gray-800 min-w-[75px]">
                                        {{ t('অন্যান্য আয়', 'Others') }}
                                    </th>
                                    <th class="sticky top-8 z-30 border border-gray-400 bg-emerald-50 px-2 py-1.5 text-center font-semibold text-gray-800 min-w-[70px]">
                                        {{ t('রিফান্ড', 'Refund') }}
                                    </th>
                                    <th class="sticky top-8 z-30 border border-gray-400 bg-emerald-100 px-2 py-1.5 text-center font-bold text-emerald-800 min-w-[80px]">
                                        {{ t('মোট জমা', 'Total In') }}
                                    </th>

                                    <!-- OUT subcategories (7 columns) -->
                                    <th class="sticky top-8 z-30 border border-gray-400 bg-rose-50 px-2 py-1.5 text-center font-semibold text-gray-800 min-w-[70px]">
                                        {{ t('ফান্ড', 'Fund') }}
                                    </th>
                                    <th class="sticky top-8 z-30 border border-gray-400 bg-rose-50 px-2 py-1.5 text-center font-semibold text-gray-800 min-w-[75px]">
                                        {{ t('ক্রয়', 'Purchase') }}
                                    </th>
                                    <th class="sticky top-8 z-30 border border-gray-400 bg-rose-50 px-2 py-1.5 text-center font-semibold text-gray-800 min-w-[85px]">
                                        {{ t('সরবরাহকারী', 'Supplier') }}
                                    </th>
                                    <th class="sticky top-8 z-30 border border-gray-400 bg-rose-50 px-2 py-1.5 text-center font-semibold text-gray-800 min-w-[85px]">
                                        {{ t('স্থায়ী সম্পদ', 'Fixed Asset') }}
                                    </th>
                                    <th class="sticky top-8 z-30 border border-gray-400 bg-rose-50 px-2 py-1.5 text-center font-semibold text-gray-800 min-w-[70px]">
                                        {{ t('খরচ', 'Expense') }}
                                    </th>
                                    <th class="sticky top-8 z-30 border border-gray-400 bg-rose-50 px-2 py-1.5 text-center font-semibold text-gray-800 min-w-[70px]">
                                        {{ t('অন্যান্য', 'Others') }}
                                    </th>
                                    <th class="sticky top-8 z-30 border border-gray-400 bg-rose-100 px-2 py-1.5 text-center font-bold text-rose-800 min-w-[80px]">
                                        {{ t('মোট উত্তোলন', 'Total Out') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Previous Month Balance Row -->
                                <tr class="bg-gray-100 font-semibold">
                                    <td class="border border-gray-400 px-3 py-1.5 text-left text-gray-900">
                                        {{ t('আগের মাসের ব্যালেন্স', 'Previous Balance') }}
                                    </td>
                                    <!-- IN (5 empty) -->
                                    <td class="border border-gray-400 px-2 py-1.5 text-center text-gray-400">0.00</td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-center text-gray-400">0.00</td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-center text-gray-400">0.00</td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-center text-gray-400">0.00</td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-center text-gray-400 bg-emerald-50/50">0.00</td>
                                    <!-- OUT (7 empty) -->
                                    <td class="border border-gray-400 px-2 py-1.5 text-center text-gray-400">0.00</td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-center text-gray-400">0.00</td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-center text-gray-400">0.00</td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-center text-gray-400">0.00</td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-center text-gray-400">0.00</td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-center text-gray-400">0.00</td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-center text-gray-400 bg-rose-50/50">0.00</td>
                                    <!-- Balance -->
                                    <td class="border border-gray-400 px-3 py-1.5 text-right font-bold text-gray-900 bg-gray-50">
                                        {{ formatAmount(previousMonthBalance) }}
                                    </td>
                                </tr>

                                <!-- Daily Transaction Rows -->
                                <tr v-for="transaction in dailyTransactions" :key="transaction.date" class="hover:bg-amber-50/30">
                                    <td class="border border-gray-400 px-3 py-1.5 text-center text-gray-800 whitespace-nowrap font-medium">
                                        {{ formatDate(transaction.date) }}
                                    </td>

                                    <!-- IN columns -->
                                    <td class="border border-gray-400 px-2 py-1.5 text-right" :class="Number(transaction.in.fund) > 0 ? 'text-emerald-700 font-medium' : 'text-gray-400'">
                                        {{ formatAmount(transaction.in.fund) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-right" :class="Number(transaction.in.payment) > 0 ? 'text-emerald-700 font-medium' : 'text-gray-400'">
                                        {{ formatAmount(transaction.in.payment) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-right" :class="(Number(transaction.in.extra || 0) + Number(transaction.in.other || 0)) > 0 ? 'text-emerald-700 font-medium' : 'text-gray-400'">
                                        {{ formatAmount(Number(transaction.in.extra || 0) + Number(transaction.in.other || 0)) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-right" :class="Number(transaction.in.refund) > 0 ? 'text-emerald-700 font-medium' : 'text-gray-400'">
                                        {{ formatAmount(transaction.in.refund) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-right font-bold bg-emerald-50/60" :class="Number(transaction.in.total) > 0 ? 'text-emerald-800' : 'text-gray-400'">
                                        {{ formatAmount(transaction.in.total) }}
                                    </td>

                                    <!-- OUT columns -->
                                    <td class="border border-gray-400 px-2 py-1.5 text-right" :class="Number(transaction.out.fund) > 0 ? 'text-rose-700 font-medium' : 'text-gray-400'">
                                        {{ formatAmount(transaction.out.fund) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-right" :class="Number(transaction.out.purchase) > 0 ? 'text-rose-700 font-medium' : 'text-gray-400'">
                                        {{ formatAmount(transaction.out.purchase) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-right" :class="Number(transaction.out.supplier_payment) > 0 ? 'text-rose-700 font-medium' : 'text-gray-400'">
                                        {{ formatAmount(transaction.out.supplier_payment) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-right" :class="Number(transaction.out.fixed_asset) > 0 ? 'text-rose-700 font-medium' : 'text-gray-400'">
                                        {{ formatAmount(transaction.out.fixed_asset) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-right" :class="Number(transaction.out.expense) > 0 ? 'text-rose-700 font-medium' : 'text-gray-400'">
                                        {{ formatAmount(transaction.out.expense) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-right" :class="Number(transaction.out.other) > 0 ? 'text-rose-700 font-medium' : 'text-gray-400'">
                                        {{ formatAmount(transaction.out.other) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-1.5 text-right font-bold bg-rose-50/60" :class="Number(transaction.out.total) > 0 ? 'text-rose-800' : 'text-gray-400'">
                                        {{ formatAmount(transaction.out.total) }}
                                    </td>

                                    <!-- Balance -->
                                    <td class="border border-gray-400 px-3 py-1.5 text-right font-bold text-gray-900 bg-gray-50/50">
                                        {{ formatAmount(transaction.balance) }}
                                    </td>
                                </tr>
                            </tbody>

                            <!-- Month Total Row -->
                            <tfoot>
                                <tr class="bg-gray-200 font-bold">
                                    <td class="border border-gray-400 px-3 py-2 text-left text-gray-900 uppercase">
                                        {{ t('মাসের মোট:', 'Month Total:') }}
                                    </td>
                                    <!-- IN totals -->
                                    <td class="border border-gray-400 px-2 py-2 text-right text-emerald-800">
                                        {{ formatAmount(monthTotals?.in?.fund) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-2 text-right text-emerald-800">
                                        {{ formatAmount(monthTotals?.in?.payment) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-2 text-right text-emerald-800">
                                        {{ formatAmount(Number(monthTotals?.in?.extra || 0) + Number(monthTotals?.in?.other || 0)) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-2 text-right text-emerald-800">
                                        {{ formatAmount(monthTotals?.in?.refund) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-2 text-right text-emerald-900 bg-emerald-100">
                                        {{ formatAmount(monthTotals?.in?.total) }}
                                    </td>

                                    <!-- OUT totals -->
                                    <td class="border border-gray-400 px-2 py-2 text-right text-rose-800">
                                        {{ formatAmount(monthTotals?.out?.fund) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-2 text-right text-rose-800">
                                        {{ formatAmount(monthTotals?.out?.purchase) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-2 text-right text-rose-800">
                                        {{ formatAmount(monthTotals?.out?.supplier_payment) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-2 text-right text-rose-800">
                                        {{ formatAmount(monthTotals?.out?.fixed_asset) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-2 text-right text-rose-800">
                                        {{ formatAmount(monthTotals?.out?.expense) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-2 text-right text-rose-800">
                                        {{ formatAmount(monthTotals?.out?.other) }}
                                    </td>
                                    <td class="border border-gray-400 px-2 py-2 text-right text-rose-900 bg-rose-100">
                                        {{ formatAmount(monthTotals?.out?.total) }}
                                    </td>

                                    <!-- Ending Balance matching current balance -->
                                    <td class="border border-gray-400 px-3 py-2 text-right text-sm font-extrabold text-blue-900 bg-blue-100">
                                        {{ formatAmount(endingBalance) }}
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
                <h1>{{ t('ব্যাংক লেনদেন রিপোর্ট', 'Bank Transaction Report') }}</h1>
                <p>{{ getMonthName(currentMonth) }} {{ currentYear }}</p>
                <p v-if="props.bankAccount">{{ props.bankAccount.bank_name }} — {{ props.bankAccount.account_name }} ({{ props.bankAccount.account_number }})</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>{{ t('তারিখ', 'Date') }}</th>
                        <th class="text-right">{{ t('শুরু ব্যালেন্স', 'Opening') }}</th>
                        <th class="text-right">{{ t('আয় (বিক্রয়)', 'IN: Sales') }}</th>
                        <th class="text-right">{{ t('আয় (ফান্ড)', 'IN: Fund') }}</th>
                        <th class="text-right">{{ t('আয় (অন্যান্য)', 'IN: Other') }}</th>
                        <th class="text-right">{{ t('মোট আয়', 'Total IN') }}</th>
                        <th class="text-right">{{ t('ব্যয় (ক্রয়)', 'OUT: Purchase') }}</th>
                        <th class="text-right">{{ t('ব্যয় (খরচ)', 'OUT: Expense') }}</th>
                        <th class="text-right">{{ t('ব্যয় (অন্যান্য)', 'OUT: Other') }}</th>
                        <th class="text-right">{{ t('মোট ব্যয়', 'Total OUT') }}</th>
                        <th class="text-right">{{ t('শেষ ব্যালেন্স', 'Closing') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="day in dailyTransactions" :key="day.date">
                        <td>{{ day.date }}</td>
                        <td class="text-right">{{ formatAmount(day.opening_balance) }}</td>
                        <td class="text-right">{{ formatAmount(day.in?.sales) }}</td>
                        <td class="text-right">{{ formatAmount(day.in?.fund) }}</td>
                        <td class="text-right">{{ formatAmount(day.in?.other) }}</td>
                        <td class="text-right">{{ formatAmount(day.in?.total) }}</td>
                        <td class="text-right">{{ formatAmount(day.out?.purchase) }}</td>
                        <td class="text-right">{{ formatAmount(day.out?.expense) }}</td>
                        <td class="text-right">{{ formatAmount(day.out?.other) }}</td>
                        <td class="text-right">{{ formatAmount(day.out?.total) }}</td>
                        <td class="text-right">{{ formatAmount(day.balance) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>{{ t('মোট', 'Total') }}</strong></td>
                        <td class="text-right"><strong>{{ formatAmount(previousMonthBalance) }}</strong></td>
                        <td class="text-right"><strong>{{ formatAmount(monthTotals?.in?.sales) }}</strong></td>
                        <td class="text-right"><strong>{{ formatAmount(monthTotals?.in?.fund) }}</strong></td>
                        <td class="text-right"><strong>{{ formatAmount(monthTotals?.in?.other) }}</strong></td>
                        <td class="text-right"><strong>{{ formatAmount(monthTotals?.in?.total) }}</strong></td>
                        <td class="text-right"><strong>{{ formatAmount(monthTotals?.out?.purchase) }}</strong></td>
                        <td class="text-right"><strong>{{ formatAmount(monthTotals?.out?.expense) }}</strong></td>
                        <td class="text-right"><strong>{{ formatAmount(monthTotals?.out?.other) }}</strong></td>
                        <td class="text-right"><strong>{{ formatAmount(monthTotals?.out?.total) }}</strong></td>
                        <td class="text-right"><strong>{{ formatAmount(endingBalance) }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            <div class="print-footer">
                <span>{{ t('মুদ্রণের তারিখ', 'Printed on') }}: {{ new Date().toLocaleDateString() }}</span>
                <span>{{ t('ব্যাংক লেনদেন রিপোর্ট', 'Bank Transaction Report') }}</span>
            </div>
        </div>
    </AdminLayout>
</template>

