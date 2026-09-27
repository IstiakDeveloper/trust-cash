<template>
    <AdminLayout :title="t('রিসিপ্ট ও পেমেন্ট', 'Receipt & Payment')">
        <template #header>
            <div class="flex justify-between items-center border-b border-gray-200 pb-4">
                <h2 class="text-2xl font-bold text-gray-900">{{ t('রিসিপ্ট ও পেমেন্ট স্টেটমেন্ট', 'Receipt & Payment Statement') }}</h2>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">{{ t('অ্যাকাউন্ট', 'Account') }}</label>
                        <select
                            v-model="selectedBankAccountId"
                            @change="handleDateChange"
                            :disabled="bankAccounts.length === 0"
                            class="form-select rounded-md border-gray-300 shadow-sm w-52"
                        >
                            <option v-if="bankAccounts.length === 0" value="">{{ t('কোনো সক্রিয় অ্যাকাউন্ট নেই', 'No active account available') }}</option>
                            <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                {{ account.account_name }} - {{ account.bank_name }}
                            </option>
                        </select>
                        <label class="text-sm font-medium text-gray-700">{{ t('বছর', 'Year') }}</label>
                        <select v-model="selectedYear" @change="handleDateChange"
                            class="form-select rounded-md border-gray-300 shadow-sm w-32">
                            <option v-for="year in years" :key="year" :value="year">
                                {{ formatYear(year) }}
                            </option>
                        </select>

                        <label class="text-sm font-medium text-gray-700">{{ t('মাস', 'Month') }}</label>
                        <select v-model="selectedMonth" @change="handleDateChange"
                            class="form-select rounded-md border-gray-300 shadow-sm w-40">
                            <option v-for="month in months" :key="month.value" :value="month.value">
                                {{ getMonthLabel(month.value) }}
                            </option>
                        </select>

                        <button @click="downloadPDF" :disabled="isDownloadDisabled || !selectedBankAccountId"
                            class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50">
                            <svg v-if="!isDownloading" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 animate-spin" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ isDownloading ? t('ডাউনলোড হচ্ছে...', 'Downloading...') : t('পিডিএফ ডাউনলোড', 'Download PDF') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6 px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-4 py-3 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">{{ t('রিসিপ্ট', 'Receipt') }}</h3>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="text-left px-4 py-2">{{ t('বিবরণ', 'Description') }}</th>
                                <th class="text-right px-4 py-2">{{ t('পরিমাণ', 'Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-2 font-medium">{{ t('ব্যাংকে শুরুর নগদ', 'Opening Cash On the Bank') }}</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(receipt?.opening_cash_on_bank || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium">{{ t('বিক্রয় সংগ্রহ', 'Sale Collection') }}</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(receipt?.sale_collection || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-4 py-2 font-medium text-gray-900">{{ t('অন্যান্য আয়', 'Others Income') }}</td>
                            </tr>
                            <template v-if="receipt?.extra_income?.categories && receipt.extra_income.categories.length > 0">
                                <tr v-for="category in receipt.extra_income.categories" :key="category.category">
                                    <td class="px-4 py-2 pl-8 text-sm text-gray-700">{{ category.category }}</td>
                                    <td class="px-4 py-2 text-right text-green-600">
                                        {{ formatCurrency(category.amount) }}
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr>
                                    <td class="px-4 py-2 pl-8 text-sm text-gray-500 italic">{{ t('এই পিরিয়ডে অন্য আয় নেই', 'No others income this period') }}</td>
                                    <td class="px-4 py-2 text-right text-gray-500">
                                        {{ formatCurrency(0) }}
                                    </td>
                                </tr>
                            </template>
                            <tr class="bg-gray-100 font-semibold">
                                <td class="px-4 py-2 text-gray-800">{{ t('মোট অন্যান্য আয়', 'Total Others Income') }}</td>
                                <td class="px-4 py-2 text-right text-green-600 font-bold">
                                    {{ formatCurrency(receipt?.extra_income?.total || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium">{{ t('ফান্ড গ্রহণ', 'Fund Receive') }}</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(receipt?.fund_receive || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-4 py-2">{{ t('মোট রিসিপ্ট', 'Total Receipt') }}</td>
                                <td class="px-4 py-2 text-right text-green-600">
                                    {{ formatCurrency(receipt?.total || 0) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-4 py-3 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">{{ t('পেমেন্ট', 'Payment') }}</h3>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="text-left px-4 py-2">{{ t('বিবরণ', 'Description') }}</th>
                                <th class="text-right px-4 py-2">{{ t('পরিমাণ', 'Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-2 font-medium">{{ t('ক্রয়', 'Purchase') }}</td>
                                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                                    {{ formatCurrency(payment?.purchase || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium">{{ t('ফান্ড রিফান্ড', 'Fund Refund') }}</td>
                                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                                    {{ formatCurrency(payment?.fund_refund || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-4 py-2 font-medium text-gray-900">{{ t('খরচ', 'Expenses') }}</td>
                            </tr>
                            <template v-if="payment?.expenses?.categories && payment.expenses.categories.length > 0">
                                <tr v-for="category in payment.expenses.categories" :key="category.category">
                                    <td class="px-4 py-2 pl-8 text-sm text-gray-700">{{ category.category }}</td>
                                    <td class="px-4 py-2 text-right text-red-600">
                                        {{ formatCurrency(category.amount) }}
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr>
                                    <td class="px-4 py-2 pl-8 text-sm text-gray-500 italic">{{ t('এই পিরিয়ডে খরচ নেই', 'No expenses this period') }}</td>
                                    <td class="px-4 py-2 text-right text-gray-500">
                                        {{ formatCurrency(0) }}
                                    </td>
                                </tr>
                            </template>
                            <tr class="bg-gray-100 font-semibold">
                                <td class="px-4 py-2 text-gray-800">{{ t('মোট খরচ', 'Total Expenses') }}</td>
                                <td class="px-4 py-2 text-right text-red-600 font-bold">
                                    {{ formatCurrency(payment?.expenses?.total || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium">{{ t('ব্যাংকে সমাপনী নগদ', 'Closing Cash at Bank') }}</td>
                                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                                    {{ formatCurrency(payment?.closing_cash_at_bank || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-4 py-2">{{ t('মোট পেমেন্ট', 'Total Payment') }}</td>
                                <td class="px-4 py-2 text-right text-red-600">
                                    {{ formatCurrency(payment?.total || 0) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatCurrency, formatYear } from '@/Utils';
import { useLanguage } from '@/composables/useLanguage';

const { t } = useLanguage();

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            year: null,
            month: null,
            start_date: null,
            end_date: null,
        })
    },
    bankAccounts: {
        type: Array,
        default: () => [],
    },
    selectedBankAccountId: {
        type: [Number, String],
        default: '',
    },
    receipt: {
        type: Object,
        default: () => ({
            opening_cash_on_bank: 0,
            sale_collection: 0,
            extra_income: 0,
            fund_receive: 0,
            total: 0,
        })
    },
    payment: {
        type: Object,
        default: () => ({
            purchase: 0,
            fund_refund: 0,
            expenses: 0,
            closing_cash_at_bank: 0,
            total: 0,
        })
    }
});

const currentYear = new Date().getFullYear();
const years = [currentYear - 1, currentYear, currentYear + 1];
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
];

const selectedYear = ref(props.filters.year || currentYear);
const selectedMonth = ref(props.filters.month || (new Date().getMonth() + 1));
const selectedBankAccountId = ref(props.selectedBankAccountId || '');
const isDownloading = ref(false);
const isDownloadDisabled = ref(false);

const getMonthLabel = (monthValue) => {
    const labels = {
        1: t('জানুয়ারি', 'January'),
        2: t('ফেব্রুয়ারি', 'February'),
        3: t('মার্চ', 'March'),
        4: t('এপ্রিল', 'April'),
        5: t('মে', 'May'),
        6: t('জুন', 'June'),
        7: t('জুলাই', 'July'),
        8: t('আগস্ট', 'August'),
        9: t('সেপ্টেম্বর', 'September'),
        10: t('অক্টোবর', 'October'),
        11: t('নভেম্বর', 'November'),
        12: t('ডিসেম্বর', 'December')
    };
    return labels[monthValue] || monthValue;
};

const handleDateChange = () => {
    router.get(route('admin.reports.receipt-payment'), {
        year: selectedYear.value,
        month: selectedMonth.value,
        bank_account_id: selectedBankAccountId.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['receipt', 'payment']
    });
};

const downloadPDF = () => {
    const params = new URLSearchParams({
        year: selectedYear.value,
        month: selectedMonth.value,
        bank_account_id: selectedBankAccountId.value,
    }).toString();
    window.location.href = `${route('admin.reports.receipt-payment.pdf')}?${params}`;
};
</script>
