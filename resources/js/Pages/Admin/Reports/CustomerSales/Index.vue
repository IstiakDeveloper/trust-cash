<script setup>
import { ref, onMounted } from 'vue';
import Modal from '@/Components/Modal.vue';
import { Printer } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLanguage } from '@/composables/useLanguage';
import { formatYear, getNumberLocale } from '@/utils';
import axios from 'axios'; // Add this import

const { t } = useLanguage();

const props = defineProps({
    customers: {
        type: Array,
        required: true
    },
    months: {
        type: Array,
        required: true
    },
    years: {
        type: Array,
        required: true
    }
});

const filters = ref({
    customer_id: '',
    year: new Date().getFullYear(),
    month: new Date().getMonth() + 1,
});

const reportData = ref(null);
const selectedSale = ref(null);
const loading = ref(false);

const formatCurrency = (value) => {
    const num = Number(value || 0);
    const hasDecimal = Math.abs(num % 1) > 0.00001;
    return new Intl.NumberFormat(getNumberLocale(), {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: hasDecimal ? 2 : 0,
        maximumFractionDigits: hasDecimal ? 2 : 0
    }).format(num);
};

const getMonthName = (monthValue) => {
    const names = {
        1: ['জানুয়ারি', 'January'], 2: ['ফেব্রুয়ারি', 'February'], 3: ['মার্চ', 'March'],
        4: ['এপ্রিল', 'April'], 5: ['মে', 'May'], 6: ['জুন', 'June'],
        7: ['জুলাই', 'July'], 8: ['আগস্ট', 'August'], 9: ['সেপ্টেম্বর', 'September'],
        10: ['অক্টোবর', 'October'], 11: ['নভেম্বর', 'November'], 12: ['ডিসেম্বর', 'December']
    };
    const name = names[monthValue];
    return name ? t(name[0], name[1]) : monthValue;
};

const loadReport = async () => {
    if (!filters.value.customer_id) {
        reportData.value = null;
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get(route('reports.customer-sales.data'), {
            params: filters.value
        });
        reportData.value = response.data;
    } catch (error) {
        console.error('Error loading report:', error);
    } finally {
        loading.value = false;
    }
};

const showSaleDetails = (sale) => {
    selectedSale.value = sale;
};

const printReport = () => {
    window.print();
};

// Load report if customer is pre-selected
onMounted(() => {
    if (filters.value.customer_id) {
        loadReport();
    }
});

const calculateTotal = (items) => {
    return items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
};
</script>

<template>
    <AdminLayout :title="t('গ্রাহক বিক্রয় রিপোর্ট', 'Customer Sales Report')">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h2 class="font-bold text-xl text-gray-800 dark:text-white leading-tight">
                    {{ t('গ্রাহক বিক্রয় রিপোর্ট', 'Customer Sales Report') }}
                </h2>
                <button v-if="reportData" @click="printReport"
                    class="inline-flex justify-center items-center px-4 py-2 text-xs sm:text-sm font-medium text-white bg-gray-700 hover:bg-gray-800 rounded-lg shadow-sm min-h-[38px]">
                    <Printer class="w-4 h-4 mr-2" />
                    {{ t('প্রিন্ট রিপোর্ট', 'Print Report') }}
                </button>
            </div>
        </template>

        <div class="py-4 sm:py-8">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
                <!-- Filters Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-slate-200/80 dark:border-slate-700 mb-4 sm:mb-6 no-print">
                    <div class="p-3.5 sm:p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                            <!-- Customer Select -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ t('গ্রাহক', 'Customer') }}</label>
                                <select v-model="filters.customer_id" @change="loadReport"
                                    class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                                    <option value="">{{ t('গ্রাহক নির্বাচন করুন', 'Select Customer') }}</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.name }} - {{ customer.phone }}
                                    </option>
                                </select>
                            </div>

                            <!-- Year Select -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ t('বছর', 'Year') }}</label>
                                <select v-model="filters.year" @change="loadReport"
                                    class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                                    <option v-for="year in years" :key="year.value" :value="year.value">
                                        {{ formatYear(year.value) }}
                                    </option>
                                </select>
                            </div>

                            <!-- Month Select -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ t('মাস', 'Month') }}</label>
                                <select v-model="filters.month" @change="loadReport"
                                    class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                                    <option v-for="month in months" :key="month.value" :value="month.value">
                                        {{ getMonthName(month.value) }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="text-center py-12">
                    <div class="spinner"></div>
                    <span class="text-xs text-slate-500 mt-2 block">{{ t('লোড হচ্ছে...', 'Loading...') }}</span>
                </div>

                <!-- Report Content -->
                <div v-else-if="reportData" class="space-y-4 sm:space-y-6">
                    <!-- Summary Cards (2-col on mobile) -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-3 sm:p-5">
                            <h3 class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-gray-400">{{ t('পূর্বের ব্যালেন্স', 'Previous Balance') }}</h3>
                            <p class="mt-1 text-base sm:text-2xl font-bold font-mono text-gray-900 dark:text-white truncate">
                                {{ formatCurrency(reportData.previousBalance) }}
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-3 sm:p-5">
                            <h3 class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-gray-400">{{ t('মাসিক বিক্রয়', 'Monthly Sales') }}</h3>
                            <p class="mt-1 text-base sm:text-2xl font-bold font-mono text-gray-900 dark:text-white truncate">
                                {{ formatCurrency(reportData.monthlyTotals.total_sales) }}
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-3 sm:p-5">
                            <h3 class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-gray-400">{{ t('মাসিক আদায়', 'Monthly Paid') }}</h3>
                            <p class="mt-1 text-base sm:text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400 truncate">
                                {{ formatCurrency(reportData.monthlyTotals.total_paid) }}
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-3 sm:p-5">
                            <h3 class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-gray-400">{{ t('বর্তমান বকেয়া', 'Current Due') }}</h3>
                            <p class="mt-1 text-base sm:text-2xl font-bold font-mono text-rose-600 dark:text-rose-400 truncate">
                                {{ formatCurrency(reportData.monthlyTotals.total_due) }}
                            </p>
                        </div>
                    </div>

                    <!-- Sales Table -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200/80 dark:border-slate-700 overflow-hidden">
                        <div class="p-3.5 sm:p-5">
                            <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white mb-3">{{ t('বিক্রয় বিবরণী', 'Sales Details') }}</h3>
                            <div class="overflow-x-auto -mx-3.5 sm:mx-0">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-700/40 text-[11px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            <th class="px-3 sm:px-6 py-2.5 sm:py-3 text-left">
                                                {{ t('তারিখ', 'Date') }}
                                            </th>
                                            <th class="px-3 sm:px-6 py-2.5 sm:py-3 text-left">
                                                {{ t('ইনভয়েস', 'Invoice') }}
                                            </th>
                                            <th class="px-3 sm:px-6 py-2.5 sm:py-3 text-right">
                                                {{ t('মোট', 'Total') }}
                                            </th>
                                            <th class="px-3 sm:px-6 py-2.5 sm:py-3 text-right">
                                                {{ t('পরিশোধ', 'Paid') }}
                                            </th>
                                            <th class="px-3 sm:px-6 py-2.5 sm:py-3 text-right">
                                                {{ t('বকেয়া', 'Due') }}
                                            </th>
                                            <th class="px-3 sm:px-6 py-2.5 sm:py-3 text-center">
                                                {{ t('অ্যাকশন', 'Actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="sale in reportData.monthlySales" :key="sale.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20">
                                            <td class="px-3 sm:px-6 py-2.5 sm:py-3 whitespace-nowrap text-xs text-gray-500">
                                                {{ sale.date }}
                                            </td>
                                            <td class="px-3 sm:px-6 py-2.5 sm:py-3 whitespace-nowrap text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                                {{ sale.invoice_no }}
                                            </td>
                                            <td class="px-3 sm:px-6 py-2.5 sm:py-3 whitespace-nowrap text-xs sm:text-sm font-mono text-gray-700 dark:text-gray-300 text-right">
                                                {{ formatCurrency(sale.total) }}
                                            </td>
                                            <td class="px-3 sm:px-6 py-2.5 sm:py-3 whitespace-nowrap text-xs sm:text-sm font-mono text-emerald-600 dark:text-emerald-400 text-right">
                                                {{ formatCurrency(sale.paid) }}
                                            </td>
                                            <td class="px-3 sm:px-6 py-2.5 sm:py-3 whitespace-nowrap text-xs sm:text-sm font-mono text-rose-600 dark:text-rose-400 text-right">
                                                {{ formatCurrency(sale.due) }}
                                            </td>
                                            <td class="px-3 sm:px-6 py-2.5 sm:py-3 whitespace-nowrap text-xs sm:text-sm text-center">
                                                <button @click="showSaleDetails(sale)"
                                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 font-medium">
                                                    {{ t('বিস্তারিত', 'View Details') }}
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Data Selected Message -->
                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-gray-500 text-center">
                            Please select a customer to view their sales report.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sale Details Modal -->
        <Modal :show="!!selectedSale" @close="selectedSale = null">
            <!-- ... rest of the modal code remains the same ... -->
        </Modal>
    </AdminLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none;
    }

    .print-full-width {
        width: 100% !important;
        max-width: none !important;
    }
}

.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>
