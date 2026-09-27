<template>
    <AdminLayout :title="t('ব্যালেন্স শিট', 'Balance Sheet')">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">{{ t('ব্যালেন্স শিট', 'Balance Sheet') }}</h2>
                <div class="flex items-center space-x-4">
                    <!-- Year Selection -->
                    <label class="text-sm font-medium text-gray-700">{{ t('বছর', 'Year') }}</label>
                    <select v-model="selectedYear" @change="handleDateChange"
                        class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option v-for="year in years" :key="year" :value="year">
                            {{ formatYear(year) }}
                        </option>
                    </select>

                    <!-- Month Selection -->
                    <label class="text-sm font-medium text-gray-700">{{ t('মাস', 'Month') }}</label>
                    <select v-model="selectedMonth" @change="handleDateChange"
                        class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option v-for="month in months" :key="month.value" :value="month.value">
                            {{ getMonthName(month.value) }}
                        </option>
                    </select>

                    <button @click="downloadPDF" :disabled="isDownloadDisabled"
                        class="inline-flex items-center px-3 py-2 text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50">
                        <svg v-if="!isDownloading" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2 animate-spin" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ isDownloading ? t('ডাউনলোড হচ্ছে...', 'Downloading...') : t('পিডিএফ ডাউনলোড', 'Download PDF') }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div id="balance-sheet-content" class="mx-auto max-w-7xl">

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Fund & Liabilities -->
                    <div class="overflow-hidden bg-white border rounded-lg shadow-sm">
                        <div class="px-4 py-3 border-b bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-800">{{ t('ফান্ড ও দেনা', 'Fund & Liabilities') }}</h3>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="px-4 py-2 text-left border-r">{{ t('বিবরণ', 'Description') }}</th>
                                    <th class="px-4 py-2 text-right">{{ t('পরিমাণ', 'Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Fund Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 border-r">{{ t('ফান্ড', 'Fund') }}</td>
                                    <td class="px-4 py-2 text-right text-green-600">
                                        {{ formatCurrency(fund_and_liabilities.fund.period) }}
                                    </td>
                                </tr>

                                <!-- Net Profit Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 font-semibold border-r">{{ t('নিট লাভ', 'Net Profit') }}</td>
                                    <td class="px-4 py-2 text-right" :class="{
                                        'text-green-600': fund_and_liabilities.net_profit.period > 0,
                                        'text-red-600': fund_and_liabilities.net_profit.period < 0
                                    }">
                                        {{ formatCurrency(fund_and_liabilities.net_profit.period) }}
                                    </td>
                                </tr>

                                <!-- Empty Rows for Spacing -->
                                <tr class="border-b">
                                    <td class="px-4 py-5 border-r"></td>
                                    <td class="px-4 py-5 text-right"></td>
                                </tr>
                                <tr class="border-b">
                                    <td class="px-4 py-5 border-r"></td>
                                    <td class="px-4 py-5 text-right"></td>
                                </tr>

                                <!-- Total Row -->
                                <tr class="font-bold bg-gray-50">
                                    <td class="px-4 py-2 border-r">{{ t('মোট', 'Total') }}</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ formatCurrency(fund_and_liabilities.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Property & Assets -->
                    <div class="overflow-hidden bg-white border rounded-lg shadow-sm">
                        <div class="px-4 py-3 border-b bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-800">{{ t('সম্পদ ও সম্পত্তি', 'Property & Assets') }}</h3>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="px-4 py-2 text-left border-r">{{ t('বিবরণ', 'Description') }}</th>
                                    <th class="px-4 py-2 text-right">{{ t('পরিমাণ', 'Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Bank Balance Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 border-r">{{ t('ব্যাংক ব্যালেন্স', 'Bank Balance') }}</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ formatCurrency(property_and_assets.bank_balance.period) }}
                                    </td>
                                </tr>

                                <!-- Customer Due Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 border-r">{{ t('গ্রাহক বকেয়া', 'Customer Due') }}</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ formatCurrency(property_and_assets.customer_due.period) }}
                                    </td>
                                </tr>

                                <!-- Fixed Assets Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 border-r">{{ t('স্থায়ী সম্পদ', 'Fixed Assets') }}</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ formatCurrency(property_and_assets.fixed_assets) }}
                                    </td>
                                </tr>

                                <!-- Stock Value Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 border-r">{{ t('স্টক মূল্য', 'Stock Value') }}</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ formatCurrency(property_and_assets.stock_value.period) }}
                                    </td>
                                </tr>

                                <!-- Total Row -->
                                <tr class="font-bold bg-gray-50">
                                    <td class="px-4 py-2 border-r">{{ t('মোট', 'Total') }}</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ formatCurrency(property_and_assets.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { defineComponent } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import { formatCurrency, formatYear } from '@/Utils';
import { useLanguage } from '@/composables/useLanguage';

export default defineComponent({
    components: { AdminLayout },
    setup() {
        const { t } = useLanguage()
        return { t }
    },
    props: {
        filters: { type: Object, required: true },
        fund_and_liabilities: { type: Object, required: true },
        property_and_assets: { type: Object, required: true },
    },
    data() {
        const currentYear = new Date().getFullYear();
        const currentMonth = new Date().getMonth() + 1;

        return {
            months: [
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
            ],
            years: [currentYear - 1, currentYear, currentYear + 1],
            selectedYear: this.extractYear(this.filters.start_date) || currentYear,
            selectedMonth: this.extractMonth(this.filters.start_date) || currentMonth,
            isDownloading: false,
            isDownloadDisabled: false
        }
    },
    methods: {
        formatCurrency,
        formatYear,
        getMonthName(monthNumber) {
            const names = {
                1: ['জানুয়ারি', 'January'], 2: ['ফেব্রুয়ারি', 'February'], 3: ['মার্চ', 'March'],
                4: ['এপ্রিল', 'April'], 5: ['মে', 'May'], 6: ['জুন', 'June'],
                7: ['জুলাই', 'July'], 8: ['আগস্ট', 'August'], 9: ['সেপ্টেম্বর', 'September'],
                10: ['অক্টোবর', 'October'], 11: ['নভেম্বর', 'November'], 12: ['ডিসেম্বর', 'December']
            };
            const name = names[monthNumber];
            return name ? this.t(name[0], name[1]) : '';
        },
        extractYear(dateString) {
            if (!dateString) return null;
            // Add timezone handling to avoid date shifting
            const date = new Date(dateString + 'T00:00:00');
            return date.getFullYear();
        },
        extractMonth(dateString) {
            if (!dateString) return null;
            // Add timezone handling to avoid date shifting
            const date = new Date(dateString + 'T00:00:00');
            return date.getMonth() + 1;
        },
        handleDateChange() {
            // Create dates using local timezone to avoid date shifting
            const year = this.selectedYear;
            const month = this.selectedMonth;

            // Get first day of selected month
            const startDate = new Date(year, month - 1, 1);
            // Get last day of selected month
            const endDate = new Date(year, month, 0);

            // Format dates as YYYY-MM-DD
            const formatDate = (date) => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            };

            router.get(route('admin.reports.balance-sheet'), {
                start_date: formatDate(startDate),
                end_date: formatDate(endDate),
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        async downloadPDF() {
            this.isDownloading = true;
            this.isDownloadDisabled = true;

            try {
                // Get first and last day of selected month
                const year = this.selectedYear;
                const month = this.selectedMonth;

                // Create start and end dates without timezone issues
                const startDate = new Date(year, month - 1, 1);
                const endDate = new Date(year, month, 0);

                // Format dates as YYYY-MM-DD
                const formatDate = (date) => {
                    const y = date.getFullYear();
                    const m = String(date.getMonth() + 1).padStart(2, '0');
                    const d = String(date.getDate()).padStart(2, '0');
                    return `${y}-${m}-${d}`;
                };

                // Create URL with query parameters
                const url = route('admin.reports.balance-sheet.download') +
                    `?start_date=${formatDate(startDate)}&end_date=${formatDate(endDate)}`;

                // Open in new window or redirect current window
                window.location.href = url;

                // Reset states after a brief delay to show loading state
                setTimeout(() => {
                    this.isDownloading = false;
                    this.isDownloadDisabled = false;
                }, 1500);
            } catch (error) {
                console.error('Error downloading PDF:', error);
                this.isDownloading = false;
                this.isDownloadDisabled = false;
            }
        },
    }
})
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
