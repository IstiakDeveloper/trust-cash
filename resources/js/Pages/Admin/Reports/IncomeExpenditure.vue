<template>
    <AdminLayout :title="t('আয়-ব্যয় বিবরণী', 'Income & Expenditure Statement')">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">{{ t('আয়-ব্যয় বিবরণী', 'Income & Expenditure Statement') }}</h2>
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
            <div id="income-expenditure-content" class="mx-auto ">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Income Section -->
                    <div class="overflow-hidden bg-white border rounded-lg shadow-sm">
                        <div class="px-4 py-3 border-b bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-800">{{ t('আয়', 'Income') }}</h3>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="px-4 py-2 text-left border-r">{{ t('বিবরণ', 'Description') }}</th>
                                    <th class="px-4 py-2 text-right border-r">{{ t('মাস', 'Month') }}</th>
                                    <th class="px-4 py-2 text-right">{{ t('ক্রমবর্ধমান', 'Cumulative') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Sales Profit Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 font-semibold border-r">{{ t('বিক্রয় লাভ', 'Sales Profit') }}</td>
                                    <td class="px-4 py-2 text-right border-r" :class="{
                                        'text-green-600': income.sales_profit.period > 0,
                                        'text-red-600': income.sales_profit.period < 0
                                    }">
                                        {{ formatCurrency(income.sales_profit.period) }}
                                    </td>
                                    <td class="px-4 py-2 text-right" :class="{
                                        'text-green-600': income.sales_profit.cumulative > 0,
                                        'text-red-600': income.sales_profit.cumulative < 0
                                    }">
                                        {{ formatCurrency(income.sales_profit.cumulative) }}
                                    </td>
                                </tr>

                                <!-- Extra Income Categories -->
                                <template v-for="category in income.extra_income.categories" :key="category.name">
                                    <tr class="border-b">
                                        <td class="px-4 py-2 border-r">
                                            {{ category.name }} <span class="text-gray-500">({{ t('অন্যান্য আয়', 'Others Income') }})</span>
                                        </td>
                                        <td class="px-4 py-2 text-right text-green-600 border-r">
                                            {{ formatCurrency(category.period) }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-green-600">
                                            {{ formatCurrency(category.cumulative) }}
                                        </td>
                                    </tr>
                                </template>

                                <!-- Total Income Row -->
                                <tr class="font-bold border-b bg-gray-50">
                                    <td class="px-4 py-2 border-r">{{ t('মোট আয়', 'Total Income') }}</td>
                                    <td class="px-4 py-2 text-right text-green-600 border-r">
                                        {{ formatCurrency(income.total.period) }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-green-600">
                                        {{ formatCurrency(income.total.cumulative) }}
                                    </td>
                                </tr>

                                <!-- Surplus Row -->
                                <tr class="font-bold bg-gray-50">
                                    <td class="px-4 py-2 border-r">{{ t('অতিরিক্ত/সার্বিক ফল', 'Surplus') }}</td>
                                    <td class="px-4 py-2 text-right border-r">
                                        <span class="font-bold" :class="{
                                            'text-green-600': netResultPeriod > 0,
                                            'text-red-600': netResultPeriod < 0
                                        }">
                                            {{ formatCurrency(netResultPeriod) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <span class="font-bold" :class="{
                                            'text-green-600': netResultCumulative > 0,
                                            'text-red-600': netResultCumulative < 0
                                        }">
                                            {{ formatCurrency(netResultCumulative) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr class="font-bold border-b bg-gray-50">
                                    <td class="px-4 py-2 border-r">{{ t('সর্বমোট', 'Grand Total') }}</td>
                                    <td class="px-4 py-2 text-right text-red-600 border-r">
                                        {{ formatCurrency(expenditure.total.period) }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-red-600">
                                        {{ formatCurrency(expenditure.total.cumulative) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Expenditure Section -->
                    <div class="overflow-hidden bg-white border rounded-lg shadow-sm">
                        <div class="px-4 py-3 border-b bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-800">{{ t('ব্যয়', 'Expenditure') }}</h3>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="px-4 py-2 text-left border-r">{{ t('বিবরণ', 'Description') }}</th>
                                    <th class="px-4 py-2 text-right border-r">{{ t('মাস', 'Month') }}</th>
                                    <th class="px-4 py-2 text-right">{{ t('ক্রমবর্ধমান', 'Cumulative') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="category in expenditure.categories" :key="category.name" class="border-b">
                                    <td class="px-4 py-2 border-r">{{ category.name }}</td>
                                    <td class="px-4 py-2 text-right text-red-600 border-r">
                                        {{ formatCurrency(category.period) }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-red-600">
                                        {{ formatCurrency(category.cumulative) }}
                                    </td>
                                </tr>

                                <!-- Total Expenses Row -->
                                <tr class="font-bold border-b bg-gray-50">
                                    <td class="px-4 py-2 border-r">{{ t('মোট ব্যয়', 'Total Expenditure') }}</td>
                                    <td class="px-4 py-2 text-right text-red-600 border-r">
                                        {{ formatCurrency(expenditure.total.period) }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-red-600">
                                        {{ formatCurrency(expenditure.total.cumulative) }}
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
        income: { type: Object, required: true },
        expenditure: { type: Object, required: true },
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
            isDownloadDisabled: false,
        }
    },
    computed: {
        netResultPeriod() {
            return this.income.total.period - this.expenditure.total.period;
        },
        netResultCumulative() {
            return this.income.total.cumulative - this.expenditure.total.cumulative;
        }
    },
    methods: {
        formatCurrency,
        formatYear,
        extractYear(dateString) {
            if (!dateString) return null;
            // FIX: Parse the date properly to avoid timezone issues
            const date = new Date(dateString + 'T00:00:00');
            return date.getFullYear();
        },
        extractMonth(dateString) {
            if (!dateString) return null;
            // FIX: Parse the date properly to avoid timezone issues
            const date = new Date(dateString + 'T00:00:00');
            return date.getMonth() + 1;
        },
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
        handleDateChange() {
            // FIX: Create dates using local timezone to avoid date shifting
            const year = this.selectedYear;
            const month = this.selectedMonth;

            // Create start and end dates for the selected month
            const startDate = new Date(year, month - 1, 1);
            const endDate = new Date(year, month, 0); // Last day of the month

            // Format dates as YYYY-MM-DD
            const formatDate = (date) => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            };

            router.get(route('admin.reports.income-expenditure'), {
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
                // FIX: Create correct dates for the selected month
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

                // Create URL with properly formatted parameters
                const url = route('admin.reports.income-expenditure.pdf') +
                    `?start_date=${formatDate(startDate)}&end_date=${formatDate(endDate)}&selected_month=${month}&selected_year=${year}`;

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

    #income-expenditure-content,
    #income-expenditure-content * {
        visibility: visible;
    }

    #income-expenditure-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>
