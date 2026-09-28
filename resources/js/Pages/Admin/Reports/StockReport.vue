<template>
    <AdminLayout :title="t('স্টক মুভমেন্ট রিপোর্ট', 'Stock Movement Report')">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ t('স্টক মুভমেন্ট রিপোর্ট', 'Stock Movement Report') }}
                </h2>
                <div class="flex items-center space-x-2 no-print">
                    <button @click="downloadReport"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        <DocumentArrowDownIcon class="h-5 w-5 mr-1" />
                        {{ t('পিডিএফ', 'PDF') }}
                    </button>
                    <button @click="printReport"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700">
                        <PrinterIcon class="h-5 w-5 mr-1" />
                        {{ t('প্রিন্ট', 'Print') }}
                    </button>
                </div>
            </div>
        </template>

        <!-- Filters -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4 no-print">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ t('পণ্য', 'Product') }}
                    </label>
                    <select v-model="filters.product_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                        <option value="">{{ t('সব পণ্য', 'All Products') }}</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.id }} - {{ product.name }} ({{ product.sku }})
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ t('ক্যাটাগরি', 'Category') }}
                    </label>
                    <select v-model="filters.category_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                        <option value="">{{ t('সব ক্যাটাগরি', 'All Categories') }}</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ t('শুরুর তারিখ', 'From Date') }}
                    </label>
                    <div class="relative">
                        <input v-model="fromDateText" type="text" inputmode="numeric"
                            :placeholder="t('দিন/মাস/বছর', 'DD/MM/YYYY')"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 pr-10 text-sm text-gray-700 dark:text-gray-200"
                            @change="handleTypedDate('from', fromDateText)">
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500"
                            :aria-label="t('তারিখ নির্বাচন করুন', 'Select date')" @click="openDatePicker('from')">
                            <i class="fas fa-calendar-alt"></i>
                        </button>
                        <input ref="fromDatePicker" type="date" :value="filters.from_date"
                            :lang="currentLang === 'bn' ? 'bn-BD' : 'en-BD'" class="sr-only"
                            @change="handleNativeDate('from', $event)">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ t('শেষের তারিখ', 'To Date') }}
                    </label>
                    <div class="relative">
                        <input v-model="toDateText" type="text" inputmode="numeric"
                            :placeholder="t('দিন/মাস/বছর', 'DD/MM/YYYY')"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 pr-10 text-sm text-gray-700 dark:text-gray-200"
                            @change="handleTypedDate('to', toDateText)">
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500"
                            :aria-label="t('তারিখ নির্বাচন করুন', 'Select date')" @click="openDatePicker('to')">
                            <i class="fas fa-calendar-alt"></i>
                        </button>
                        <input ref="toDatePicker" type="date" :value="filters.to_date"
                            :lang="currentLang === 'bn' ? 'bn-BD' : 'en-BD'" class="sr-only"
                            @change="handleNativeDate('to', $event)">
                    </div>
                </div>
            </div>
        </div>
        <div v-if="reports.length > 0" class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ t('সারাংশ', 'Overall Summary') }} ({{ filters.from_date }} {{ t('থেকে', 'to') }} {{ filters.to_date }})</h3>
            </div>

            <div class="p-4">
                <div class="grid grid-cols-3 gap-6">
                    <!-- Purchase Summary -->
                    <div class="p-4 bg-green-50 dark:bg-green-900/10 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">{{ t('মোট ক্রয়', 'Total Purchases') }}</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600 dark:text-gray-400">{{ t('পরিমাণ', 'Quantity') }}:</span>
                                <span class="font-medium text-green-600">
                                    {{ formatQty(summary.total_purchase_quantity) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">{{ t('মান', 'Value') }}:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ formatPrice(summary.total_purchase_value) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Summary -->
                    <div class="p-4 bg-red-50 dark:bg-red-900/10 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">{{ t('মোট বিক্রয়', 'Total Sales') }}</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600 dark:text-gray-400">{{ t('পরিমাণ', 'Quantity') }}:</span>
                                <span class="font-medium text-red-600">
                                    {{ formatQty(summary.total_sales_quantity) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">{{ t('মান', 'Value') }}:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ formatPrice(summary.total_sales_value) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Summary -->
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">{{ t('চলতি স্টক', 'Current Stock') }}</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600 dark:text-gray-400">{{ t('পরিমাণ', 'Quantity') }}:</span>
                                <span class="font-medium">
                                    {{ formatQty(summary.total_current_stock) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">{{ t('মান', 'Value') }}:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ formatPrice(summary.total_stock_value) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-for="report in reports" :key="report.product.id"
            class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <!-- Product Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ report.product.name }}
                            <span class="ml-2 text-sm text-gray-500">({{ report.product.sku }})</span>
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ report.product.category }} | {{ report.product.unit }}
                        </p>
                    </div>
                    <div class="text-sm">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-gray-500">{{ t('শুরুর স্টক', 'Opening Stock') }}:</p>
                                <p class="font-medium">{{ formatQty(report.summary.opening_stock) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">{{ t('চলতি স্টক', 'Current Stock') }}:</p>
                                <p class="font-medium" :class="getStockClass(report.summary.current_stock)">
                                    {{ formatQty(report.summary.current_stock) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-gray-700">
                <div>
                    <p class="text-sm text-gray-500">{{ t('মোট ক্রয়', 'Total Purchased') }}</p>
                    <p class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ formatQty(report.summary.total_purchased) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ t('মোট বিক্রয়', 'Total Sold') }}</p>
                    <p class="text-lg font-medium text-red-600 dark:text-red-400">
                        {{ formatQty(report.summary.total_sold) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ t('গড় খরচ', 'Average Cost') }}</p>
                    <p class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ formatPrice(report.summary.avg_cost) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ t('স্টক মূল্য', 'Stock Value') }}</p>
                    <p class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ formatPrice(report.summary.stock_value) }}
                    </p>
                </div>
            </div>

            <div class="p-4">
                <div class="grid grid-cols-2 gap-6">
                    <!-- Purchase History -->
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ t('ক্রয় ইতিহাস', 'Purchase History') }}</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <th class="px-4 py-2 text-left">{{ t('তারিখ', 'Date') }}</th>
                                        <th class="px-4 py-2 text-right">{{ t('পরিমাণ', 'Qty') }}</th>
                                        <th class="px-4 py-2 text-right">{{ t('ইউনিট খরচ', 'Unit Cost') }}</th>
                                        <th class="px-4 py-2 text-right">{{ t('মোট', 'Total') }}</th>
                                        <th class="px-4 py-2 text-right">{{ t('উপলব্ধ', 'Available') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="purchase in report.purchases" :key="purchase.date"
                                        class="text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-2">{{ formatDate(purchase.date) }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatQty(purchase.quantity) }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatPrice(purchase.unit_cost) }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatPrice(purchase.total_cost) }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatQty(purchase.available_quantity) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="font-medium bg-gray-50 dark:bg-gray-700">
                                        <td class="px-4 py-2">{{ t('মোট', 'Total') }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatQty(report.summary.total_purchased) }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatPrice(report.summary.avg_cost) }}</td>
                                        <td class="px-4 py-2 text-right" colspan="2">
                                            {{ formatPrice(report.purchases.reduce((sum, p) => sum + p.total_cost, 0))
                                            }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Sales History -->
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ t('বিক্রয় ইতিহাস', 'Sales History') }}</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <td class="px-4 py-2 text-left">{{ t('তারিখ', 'Date') }}</td>
                                        <td class="px-4 py-2 text-right">{{ t('পরিমাণ', 'Qty') }}</td>
                                        <td class="px-4 py-2 text-right">{{ t('খরচ', 'Cost') }}</td>
                                        <td class="px-4 py-2 text-right">{{ t('মোট', 'Total') }}</td>
                                        <td class="px-4 py-2 text-right">{{ t('উপলব্ধ', 'Available') }}</td>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="sale in report.sales" :key="sale.date"
                                        class="text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-2">{{ formatDate(sale.date) }}</td>
                                        <td class="px-4 py-2 text-right text-red-600">{{ formatQty(sale.quantity) }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatPrice(sale.unit_cost) }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatPrice(sale.total_cost) }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatQty(sale.available_quantity) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="font-medium bg-gray-50 dark:bg-gray-700">
                                        <td class="px-4 py-2">{{ t('মোট', 'Total') }}</td>
                                        <td class="px-4 py-2 text-right text-red-600">
                                            {{ formatQty(report.summary.total_sold) }}
                                        </td>
                                        <td class="px-4 py-2"></td>
                                        <td class="px-4 py-2 text-right" colspan="2">
                                            {{ formatPrice(report.sales.reduce((sum, s) => sum + s.total_cost, 0)) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRINT AREA -->
        <div class="print-area">
            <div class="print-header">
                <h1>{{ t('স্টক মুভমেন্ট রিপোর্ট', 'Stock Movement Report') }}</h1>
                <p>{{ t('সময়কাল', 'Period') }}: {{ filters.from_date }} — {{ filters.to_date }}</p>
            </div>

            <template v-for="report in reports" :key="'sp_' + report.product.id">
                <div class="section-title">
                    {{ report.product.name }}
                    ({{ t('SKU', 'SKU') }}: {{ report.product.sku }})
                    &nbsp;|&nbsp; {{ t('বর্তমান স্টক', 'Current Stock') }}: {{ formatQty(report.summary.current_stock) }}
                    &nbsp;|&nbsp; {{ t('স্টক মূল্য', 'Stock Value') }}: {{ formatPrice(report.summary.stock_value) }}
                </div>

                <!-- Summary row -->
                <table style="margin-bottom:6px">
                    <thead>
                        <tr>
                            <th>{{ t('শুরু স্টক', 'Opening Stock') }}</th>
                            <th>{{ t('মোট ক্রয়', 'Total Purchased') }}</th>
                            <th>{{ t('মোট বিক্রয়', 'Total Sold') }}</th>
                            <th>{{ t('বর্তমান স্টক', 'Current Stock') }}</th>
                            <th>{{ t('গড় খরচ', 'Avg Cost') }}</th>
                            <th>{{ t('স্টক মূল্য', 'Stock Value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-right">{{ formatQty(report.summary.opening_stock) }}</td>
                            <td class="text-right">{{ formatQty(report.summary.total_purchased) }}</td>
                            <td class="text-right">{{ formatQty(report.summary.total_sold) }}</td>
                            <td class="text-right">{{ formatQty(report.summary.current_stock) }}</td>
                            <td class="text-right">{{ formatPrice(report.summary.avg_cost) }}</td>
                            <td class="text-right">{{ formatPrice(report.summary.stock_value) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Purchases -->
                <div style="font-size:8.5pt;font-weight:600;margin-bottom:2px">{{ t('ক্রয় ইতিহাস', 'Purchase History') }}</div>
                <table>
                    <thead>
                        <tr>
                            <th>{{ t('তারিখ', 'Date') }}</th>
                            <th class="text-right">{{ t('পরিমাণ', 'Qty') }}</th>
                            <th class="text-right">{{ t('ইউনিট খরচ', 'Unit Cost') }}</th>
                            <th class="text-right">{{ t('মোট', 'Total') }}</th>
                            <th class="text-right">{{ t('উপলব্ধ', 'Available') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="purchase in report.purchases" :key="purchase.date + '_p'">
                            <td>{{ formatDate(purchase.date) }}</td>
                            <td class="text-right">{{ formatQty(purchase.quantity) }}</td>
                            <td class="text-right">{{ formatPrice(purchase.unit_cost) }}</td>
                            <td class="text-right">{{ formatPrice(purchase.total_cost) }}</td>
                            <td class="text-right">{{ formatQty(purchase.available_quantity) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Sales -->
                <div style="font-size:8.5pt;font-weight:600;margin-bottom:2px;margin-top:6px">{{ t('বিক্রয় ইতিহাস', 'Sales History') }}</div>
                <table style="margin-bottom:14px">
                    <thead>
                        <tr>
                            <th>{{ t('তারিখ', 'Date') }}</th>
                            <th class="text-right">{{ t('পরিমাণ', 'Qty') }}</th>
                            <th class="text-right">{{ t('ইনভয়েস', 'Invoice') }}</th>
                            <th class="text-right">{{ t('ইউনিট মূল্য', 'Unit Price') }}</th>
                            <th class="text-right">{{ t('মোট', 'Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sale in report.sales" :key="sale.date + '_s'">
                            <td>{{ formatDate(sale.date) }}</td>
                            <td class="text-right">{{ formatQty(sale.quantity) }}</td>
                            <td>{{ sale.invoice_no ?? '—' }}</td>
                            <td class="text-right">{{ formatPrice(sale.unit_price ?? 0) }}</td>
                            <td class="text-right">{{ formatPrice(sale.total ?? 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <div class="print-footer">
                <span>{{ t('মুদ্রণের তারিখ', 'Printed on') }}: {{ new Date().toLocaleDateString() }}</span>
                <span>{{ t('স্টক মুভমেন্ট রিপোর্ট', 'Stock Movement Report') }}</span>
            </div>
        </div>
    </AdminLayout>
</template>


<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { DocumentArrowDownIcon, PrinterIcon } from '@heroicons/vue/24/outline'
import { useLanguage } from '@/composables/useLanguage'
import { getNumberLocale } from '@/utils'

const { currentLang, t } = useLanguage()

const props = defineProps({
    products: {
        type: Array,
        required: true
    },
    categories: {
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
        required: true
    }
});

const filters = ref({
    product_id: props.filters.product_id || '',
    category_id: props.filters.category_id || '',
    from_date: props.filters.from_date || '',
    to_date: props.filters.to_date || ''
});

const fromDateText = ref('')
const toDateText = ref('')
const fromDatePicker = ref(null)
const toDatePicker = ref(null)

const formatDisplayDate = (dateValue) => {
    if (!dateValue) return t('তারিখ নির্বাচন করুন', 'Select date')

    return new Intl.DateTimeFormat(currentLang.value === 'bn' ? 'bn-BD' : 'en-BD', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).format(new Date(`${dateValue}T00:00:00`))
}

const syncDateText = () => {
    fromDateText.value = formatDisplayDate(filters.value.from_date)
    toDateText.value = formatDisplayDate(filters.value.to_date)
}

syncDateText()

const openDatePicker = (field) => {
    const picker = field === 'from' ? fromDatePicker.value : toDatePicker.value
    if (picker?.showPicker) {
        picker.showPicker()
    } else {
        picker?.click()
    }
}

const normalizeDateDigits = (value) => value.replace(/[০-৯]/g, digit => '০১২৩৪৫৬৭৮৯'.indexOf(digit)).trim()

const updateDateFilter = (field, dateValue) => {
    filters.value[field === 'from' ? 'from_date' : 'to_date'] = dateValue
    syncDateText()
    applyFilters()
}

const handleNativeDate = (field, event) => {
    updateDateFilter(field, event.target.value)
}

const handleTypedDate = (field, value) => {
    const parts = normalizeDateDigits(value).split(/[/.\-]/).filter(Boolean).map(Number)
    if (parts.length !== 3) return syncDateText()

    const [first, second, third] = parts
    const year = first > 999 ? first : third
    const month = second
    const day = first > 999 ? third : first
    const date = new Date(Date.UTC(year, month - 1, day))

    if (date.getUTCFullYear() !== year || date.getUTCMonth() !== month - 1 || date.getUTCDate() !== day) {
        return syncDateText()
    }

    updateDateFilter(field, `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`)
}


const formatPrice = (amount) => {
    const num = Number(amount || 0);
    const hasDecimal = Math.abs(num % 1) > 0.00001;
    return new Intl.NumberFormat(getNumberLocale(), {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: hasDecimal ? 2 : 0,
        maximumFractionDigits: hasDecimal ? 2 : 0
    }).format(num);
};

const formatQty = (amount) => {
    const num = Number(amount || 0);
    const hasDecimal = Math.abs(num % 1) > 0.00001;
    return new Intl.NumberFormat(getNumberLocale(), {
        minimumFractionDigits: hasDecimal ? 2 : 0,
        maximumFractionDigits: hasDecimal ? 2 : 0
    }).format(num);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getStockClass = (stock) => {
    if (stock <= 0) return 'text-red-600';
    if (stock < 10) return 'text-yellow-600';
    return 'text-green-600';
};


const getMovementClass = (movement) => {
    if (movement > 0) return 'text-green-600 dark:text-green-400'
    if (movement < 0) return 'text-red-600 dark:text-red-400'
    return 'text-gray-600 dark:text-gray-400'
}


const applyFilters = () => {
    router.get(route('admin.reports.stock'), {
        ...filters.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const downloadReport = () => {
    const params = new URLSearchParams({
        product_id: filters.value.product_id || '',
        category_id: filters.value.category_id || '',
        from_date: filters.value.from_date || '',
        to_date: filters.value.to_date || '',
        locale: currentLang.value === 'bn' ? 'bn' : 'en',
    }).toString()

    window.location.href = `${route('admin.reports.stock.download')}?${params}`
}

const printReport = () => {
    window.print();
}



// Initialize with first product selected
onMounted(() => {
    if (!filters.value.product_id && props.products.length > 0) {
        filters.value.product_id = props.products[0].id
        applyFilters()
    }
})
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

.table-hover tr:hover {
    @apply bg-gray-50 dark:bg-gray-700;
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>
