<template>
    <AdminLayout :title="t('পণ্য বিশ্লেষণ রিপোর্ট', 'Product Analysis Report')">
        <template #header>
            <div class="flex flex-col gap-3 no-print">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <h2 class="text-base sm:text-lg font-bold text-gray-800 dark:text-white">{{ t('পণ্য বিশ্লেষণ রিপোর্ট', 'Product Analysis Report') }}</h2>
                    <!-- Search Box -->
                    <div class="w-full sm:w-64">
                        <input type="text" v-model="searchQuery" @input="handleSearch" :placeholder="t('পণ্যের নাম খুঁজুন...', 'Search products...')"
                            class="w-full px-3 py-1.5 text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                    <!-- Date Range Selector -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <div class="flex items-center gap-1">
                            <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ t('শুরুর তারিখ', 'From') }}</label>
                            <div class="relative">
                                <input v-model="startDateText" type="text" inputmode="numeric"
                                    :placeholder="t('দিন/মাস/বছর', 'DD/MM/YYYY')"
                                    class="w-28 sm:w-32 px-2 py-1 pr-7 text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg shadow-sm"
                                    @change="handleTypedDate('start', startDateText)" />
                                <button type="button" class="absolute right-1 top-1/2 -translate-y-1/2 text-gray-500"
                                    :aria-label="t('তারিখ নির্বাচন করুন', 'Select date')" @click="openDatePicker('start')">
                                    <i class="fas fa-calendar-alt text-xs"></i>
                                </button>
                                <input ref="startDatePicker" type="date" :value="filters.start_date"
                                    :max="filters.end_date" :lang="currentLang === 'bn' ? 'bn-BD' : 'en-BD'"
                                    class="sr-only" @change="handleNativeDate('start', $event)" />
                            </div>
                        </div>
                        <span class="text-xs text-gray-500">-</span>
                        <div class="flex items-center gap-1">
                            <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ t('শেষের তারিখ', 'To') }}</label>
                            <div class="relative">
                                <input v-model="endDateText" type="text" inputmode="numeric"
                                    :placeholder="t('দিন/মাস/বছর', 'DD/MM/YYYY')"
                                    class="w-28 sm:w-32 px-2 py-1 pr-7 text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg shadow-sm"
                                    @change="handleTypedDate('end', endDateText)" />
                                <button type="button" class="absolute right-1 top-1/2 -translate-y-1/2 text-gray-500"
                                    :aria-label="t('তারিখ নির্বাচন করুন', 'Select date')" @click="openDatePicker('end')">
                                    <i class="fas fa-calendar-alt text-xs"></i>
                                </button>
                                <input ref="endDatePicker" type="date" :value="filters.end_date"
                                    :min="filters.start_date" :max="today" :lang="currentLang === 'bn' ? 'bn-BD' : 'en-BD'"
                                    class="sr-only" @change="handleNativeDate('end', $event)" />
                            </div>
                        </div>
                    </div>

                    <!-- Actions Row Grid -->
                    <div class="grid grid-cols-5 sm:flex sm:items-center gap-1.5">
                        <!-- View Mode Toggle -->
                        <button @click="viewMode = viewMode === 'dashboard' ? 'document' : 'dashboard'"
                            type="button"
                            class="inline-flex justify-center items-center px-2 py-1.5 text-xs text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 shadow-sm min-h-[34px]">
                            <span v-if="viewMode === 'dashboard'">📄</span>
                            <span v-else>📊</span>
                        </button>

                        <!-- Export Buttons -->
                        <button @click="exportToExcel" :disabled="isExporting"
                            class="inline-flex justify-center items-center px-2 py-1.5 text-xs text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 min-h-[34px]"
                            :title="t('এক্সেল', 'Excel')">
                            <span class="truncate">{{ t('এক্সেল', 'XLS') }}</span>
                        </button>

                        <button @click="downloadPDF" :disabled="isDownloading"
                            class="inline-flex justify-center items-center px-2 py-1.5 text-xs text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 min-h-[34px]"
                            :title="t('পিডিএফ', 'PDF')">
                            <span class="truncate">{{ t('পিডিএফ', 'PDF') }}</span>
                        </button>

                        <!-- Print Button -->
                        <button @click="printReport"
                            class="inline-flex justify-center items-center px-2 py-1.5 text-xs text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 min-h-[34px]"
                            :title="t('প্রিন্ট', 'Print')">
                            <span class="truncate">{{ t('প্রিন্ট', 'Print') }}</span>
                        </button>

                        <!-- Refresh Button -->
                        <button @click="refreshData" :disabled="isRefreshing"
                            class="inline-flex justify-center items-center px-2 py-1.5 text-xs text-white bg-gray-600 rounded-lg hover:bg-gray-700 disabled:opacity-50 min-h-[34px]">
                            <svg :class="['w-4 h-4', { 'animate-spin': isRefreshing }]" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div v-show="viewMode === 'dashboard'" class="no-print">
            <div class="py-3">
                <div class="max-w-full mx-auto">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <!-- Loading State -->
                    <div v-if="isLoading" class="p-6 text-center">
                        <div class="inline-flex items-center">
                            <svg class="w-5 h-5 mr-3 text-gray-600 animate-spin" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                    fill="none" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            {{ t('পণ্য বিশ্লেষণ লোড হচ্ছে...', 'Loading product analysis...') }}
                        </div>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="p-6">
                        <div class="p-4 border border-red-200 rounded-md bg-red-50">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">{{ t('ডেটা লোড করতে সমস্যা হয়েছে', 'Error loading data') }}</h3>
                                    <div class="mt-2 text-sm text-red-700">{{ error }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div v-else class="p-2 sm:p-4">
                        <!-- Summary Cards (2 to 5 cols) -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-4 mb-4">
                            <div class="p-2.5 sm:p-3 rounded-xl bg-blue-50 border border-blue-200/60">
                                <h3 class="text-xs font-semibold text-blue-700">{{ t('মোট ক্রয়', 'Total Buy') }}</h3>
                                <p class="text-sm sm:text-lg font-bold font-mono text-blue-900 truncate">{{ formatCurrency(totalBuyPrice) }}</p>
                            </div>
                            <div class="p-2.5 sm:p-3 rounded-xl bg-green-50 border border-green-200/60">
                                <h3 class="text-xs font-semibold text-green-700">{{ t('মোট বিক্রয়', 'Total Sale') }}</h3>
                                <p class="text-sm sm:text-lg font-bold font-mono text-green-900 truncate">{{ formatCurrency(totalSaleAfterDiscount) }}
                                </p>
                            </div>
                            <div class="p-2.5 sm:p-3 rounded-xl bg-orange-50 border border-orange-200/60">
                                <h3 class="text-xs font-semibold text-orange-700">{{ t('মোট লাভ', 'Total Profit') }}</h3>
                                <p class="text-sm sm:text-lg font-bold font-mono text-orange-900 truncate">{{ formatCurrency(totalProfit) }}</p>
                            </div>
                            <div class="p-2.5 sm:p-3 rounded-xl bg-purple-50 border border-purple-200/60">
                                <h3 class="text-xs font-semibold text-purple-700">{{ t('লাভ মার্জিন', 'Profit Margin') }}</h3>
                                <p class="text-sm sm:text-lg font-bold font-mono text-purple-900 truncate">{{ profitMargin }}%</p>
                            </div>
                            <div class="p-2.5 sm:p-3 rounded-xl bg-yellow-50 border border-yellow-200/60 col-span-2 sm:col-span-1">
                                <h3 class="text-xs font-semibold text-yellow-700">{{ t('স্টক মূল্য', 'Stock Value') }}</h3>
                                <p class="text-sm sm:text-lg font-bold font-mono text-yellow-900 truncate">{{ formatCurrency(totalAvailableValue) }}
                                </p>
                            </div>
                        </div>


                        <!-- Table Container -->
                        <div class="max-h-[70vh] overflow-auto">
                            <table class="min-w-full text-xs divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <!-- Product Info Section -->
                                        <th colspan="2"
                                            class="sticky top-0 z-20 px-2 py-1 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-r bg-gray-50">
                                            {{ t('পণ্যের তথ্য', 'Product Information') }}
                                        </th>

                                        <!-- Before Stock Section -->
                                        <th colspan="3"
                                            class="sticky top-0 z-20 px-2 py-1 text-xs font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-yellow-50">
                                            {{ t('স্টক পূর্বের তথ্য', 'Before Stock Information') }}
                                        </th>

                                        <!-- Buy Info Section -->
                                        <th colspan="3"
                                            class="sticky top-0 z-20 px-2 py-1 text-xs font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-blue-50">
                                            {{ t('ক্রয় তথ্য', 'Buy Information') }}
                                        </th>

                                        <!-- Sale Info Section -->
                                        <th colspan="5"
                                            class="sticky top-0 z-20 px-2 py-1 text-xs font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-green-50">
                                            {{ t('বিক্রয় তথ্য', 'Sale Information') }}
                                        </th>

                                        <!-- Profit Info Section -->
                                        <th colspan="3"
                                            class="sticky top-0 z-20 px-2 py-1 text-xs font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-orange-50">
                                            {{ t('লাভ তথ্য', 'Profit Information') }}
                                        </th>

                                        <!-- Available Info Section -->
                                        <th colspan="2"
                                            class="sticky top-0 z-20 px-2 py-1 text-xs font-medium tracking-wider text-center text-gray-500 uppercase bg-purple-50">
                                            {{ t('উপলব্ধ তথ্য', 'Available Information') }}
                                        </th>
                                    </tr>
                                    <tr class="text-xs bg-gray-50">
                                        <!-- Product Info Headers -->
                                        <th
                                            class="sticky top-7 left-0 z-30 px-2 py-1 font-medium tracking-wider text-left text-gray-500 uppercase border-r bg-gray-50">
                                            {{ t('ক্রমিক', 'SL') }}</th>
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-left text-gray-500 uppercase border-r bg-gray-50">
                                            {{ t('নাম', 'Name') }}</th>
                                        <!-- Before Stock Headers -->
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-yellow-50">
                                            {{ t('পরিমাণ', 'Qty') }}</th>
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-yellow-50">
                                            {{ t('দর', 'Price') }}</th>
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-yellow-50">
                                            {{ t('মান', 'Value') }}</th>

                                        <!-- Buy Info Headers -->
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-blue-50">
                                            {{ t('পরিমাণ', 'Qty') }}</th>
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-blue-50">
                                            {{ t('দর', 'Price') }}</th>
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-blue-50">
                                            {{ t('মোট', 'Total') }}</th>

                                        <!-- Sale Info Headers -->
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-green-50">
                                            {{ t('পরিমাণ', 'Qty') }}</th>
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-green-50">
                                            {{ t('দর', 'Price') }}</th>
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-green-50">
                                            {{ t('সাবটোটাল', 'Subtotal') }}</th>
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-green-50">
                                            {{ t('ছাড়', 'Discount') }}</th>
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-green-50">
                                            {{ t('মোট', 'Total') }}</th>

                                        <!-- Profit Info Headers -->
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-orange-50">
                                            {{ t('প্রতি ইউনিট', 'Per Unit') }}</th>
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-orange-50">
                                            {{ t('মোট', 'Total') }}</th>
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-orange-50">
                                            %</th>

                                        <!-- Available Info Headers -->
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-purple-50">
                                            {{ t('স্টক', 'Stock') }}</th>
                                        <th
                                            class="sticky top-7 z-20 px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase bg-purple-50">
                                            {{ t('মান', 'Value') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="product in filteredProducts" :key="product.serial"
                                        class="text-xs hover:bg-gray-50">
                                        <!-- Product Info -->
                                        <td
                                            class="sticky left-0 px-2 py-1 text-gray-500 bg-white border-r whitespace-nowrap">
                                            {{
                                                product.serial }}</td>
                                        <td class="px-2 py-1 font-medium text-gray-900 border-r whitespace-nowrap">{{
                                            product.product_name }}</td>


                                        <!-- Before Stock Info -->
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-yellow-50/30">{{
                                            formatNumber(product.before_quantity) }}</td>
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-yellow-50/30">{{
                                            formatCurrency(product.before_price) }}</td>
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-yellow-50/30">{{
                                            formatCurrency(product.before_value) }}</td>

                                        <!-- Buy Info -->
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-blue-50/30">{{
                                            formatNumber(product.buy_quantity) }}</td>
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-blue-50/30">{{
                                            formatCurrency(product.buy_price) }}</td>
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-blue-50/30">{{
                                            formatCurrency(product.total_buy_price) }}</td>

                                        <!-- Sale Info -->
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-green-50/30">{{
                                            formatNumber(product.sale_quantity) }}</td>
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-green-50/30">{{
                                            formatCurrency(product.sale_price) }}</td>
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-green-50/30">{{
                                            formatCurrency(product.total_sale_price) }}</td>
                                        <td
                                            class="px-2 py-1 text-center text-red-600 border-r whitespace-nowrap bg-green-50/30">
                                            {{ formatCurrency(product.sale_discount) }}</td>
                                        <td
                                            class="px-2 py-1 font-semibold text-center border-r whitespace-nowrap bg-green-50/30">
                                            {{
                                                formatCurrency(product.sale_after_discount) }}</td>

                                        <!-- Profit Info -->
                                        <td :class="[
                                            'px-2 py-1 whitespace-nowrap text-center border-r bg-orange-50/30',
                                            product.profit_per_unit < 0 ? 'text-red-600' : 'text-green-600'
                                        ]">
                                            {{ formatCurrency(product.profit_per_unit) }}
                                        </td>
                                        <td :class="[
                                            'px-2 py-1 whitespace-nowrap text-center border-r bg-orange-50/30 font-semibold',
                                            product.total_profit < 0 ? 'text-red-600' : 'text-green-600'
                                        ]">
                                            {{ formatCurrency(product.total_profit) }}
                                        </td>
                                        <td :class="[
                                            'px-2 py-1 whitespace-nowrap text-center border-r bg-orange-50/30',
                                            product.profit_percentage < 0 ? 'text-red-600' : 'text-green-600'
                                        ]">
                                            {{ formatNumber(product.profit_percentage) }}%
                                        </td>

                                        <!-- Available Info -->
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-purple-50/30">{{
                                            formatNumber(product.available_quantity) }}</td>
                                        <td class="px-2 py-1 text-center whitespace-nowrap bg-purple-50/30">{{
                                            formatCurrency(product.available_stock_value) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="text-xs font-semibold bg-gray-50">
                                        <td colspan="2" class="px-2 py-1 text-right text-gray-900 border-r">{{ t('মোট', 'Totals') }}</td>
                                        <!-- Before Stock Totals -->
                                        <td class="px-2 py-1 text-center bg-yellow-100 border-r">{{
                                            formatNumber(totalBeforeQuantity) }}</td>
                                        <td class="px-2 py-1 text-center bg-yellow-100 border-r">-</td>
                                        <td class="px-2 py-1 text-center bg-yellow-100 border-r">{{
                                            formatCurrency(totalBeforeValue) }}</td>
                                        <!-- Buy Info Totals -->
                                        <td class="px-2 py-1 text-center bg-blue-100 border-r">{{
                                            formatNumber(totalBuyQuantity)
                                            }}</td>
                                        <td class="px-2 py-1 text-center bg-blue-100 border-r">-</td>
                                        <td class="px-2 py-1 text-center bg-blue-100 border-r">{{
                                            formatCurrency(totalBuyPrice)
                                            }}</td>
                                        <!-- Sale Info Totals -->
                                        <td class="px-2 py-1 text-center bg-green-100 border-r">{{
                                            formatNumber(totalSaleQuantity) }}</td>
                                        <td class="px-2 py-1 text-center bg-green-100 border-r">-</td>
                                        <td class="px-2 py-1 text-center bg-green-100 border-r">{{
                                            formatCurrency(totalSalePrice) }}</td>
                                        <td class="px-2 py-1 text-center text-red-600 bg-green-100 border-r">{{
                                            formatCurrency(totalSaleDiscount) }}</td>
                                        <td class="px-2 py-1 font-semibold text-center bg-green-100 border-r">{{
                                            formatCurrency(totalSaleAfterDiscount) }}</td>
                                        <!-- Profit Info Totals -->
                                        <td class="px-2 py-1 text-center bg-orange-100 border-r">-</td>
                                        <td :class="[
                                            'px-2 py-1 text-center border-r bg-orange-100',
                                            totalProfit < 0 ? 'text-red-600' : 'text-green-600'
                                        ]">
                                            {{ formatCurrency(totalProfit) }}
                                        </td>
                                        <td :class="[
                                            'px-2 py-1 text-center border-r bg-orange-100',
                                            profitMargin < 0 ? 'text-red-600' : 'text-green-600'
                                        ]">
                                            {{ profitMargin }}%
                                        </td>
                                        <!-- Available Info Totals -->
                                        <td class="px-2 py-1 text-center bg-purple-100 border-r">{{
                                            formatNumber(totalAvailableQuantity) }}</td>
                                        <td class="px-2 py-1 text-center bg-purple-100">{{
                                            formatCurrency(totalAvailableValue)
                                            }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredProducts.length === 0 && !isLoading" class="p-6 text-center">
                            <div class="text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="mt-2">{{ t('আপনার ফিল্টারের সাথে কোনো পণ্য পাওয়া যায়নি', 'No products found matching your criteria') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- ============================================================
             B&W WORD REPORT VIEW & PRINT / PDF TEMPLATE
             ============================================================ -->
        <div :class="[viewMode === 'document' ? 'block py-4 overflow-x-auto' : 'print-only']">
            <WordReportLayout
                ref="wordReportRef"
                :title="t('পণ্য বিশ্লেষণ রিপোর্ট', 'Product Analysis Report')"
                :date-range="`${startDateText || filters.start_date || '-'} ${t('হতে', 'to')} ${endDateText || filters.end_date || '-'}`"
                orientation="landscape"
                file-name="product-analysis-report.pdf"
            >
                <!-- Quick Summary Bar / Table -->
                <div class="mb-3 avoid-break">
                    <table class="word-table" style="margin-top: 0; margin-bottom: 6px;">
                        <thead>
                            <tr style="background-color: #f2f2f2 !important;">
                                <th class="text-right" style="width: 20%;">{{ t('মোট ক্রয়', 'Total Buy') }}</th>
                                <th class="text-right" style="width: 20%;">{{ t('মোট বিক্রয়', 'Total Sale') }}</th>
                                <th class="text-right" style="width: 20%;">{{ t('মোট লাভ', 'Total Profit') }}</th>
                                <th class="text-right" style="width: 20%;">{{ t('লাভ মার্জিন', 'Profit Margin') }}</th>
                                <th class="text-right" style="width: 20%;">{{ t('স্টক মূল্য', 'Stock Value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="font-bold">
                                <td class="text-right">{{ formatCurrency(totalBuyPrice) }}</td>
                                <td class="text-right">{{ formatCurrency(totalSaleAfterDiscount) }}</td>
                                <td class="text-right" :style="totalProfit >= 0 ? 'color: #166534;' : 'color: #dc2626;'">{{ formatCurrency(totalProfit) }}</td>
                                <td class="text-right" :style="profitMargin >= 0 ? 'color: #166534;' : 'color: #dc2626;'">{{ profitMargin }}%</td>
                                <td class="text-right">{{ formatCurrency(totalAvailableValue) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Exact 18-Column Landscape Products Analysis Table matching Show Page -->
                <table class="word-table pa-landscape-table">
                    <thead>
                        <tr>
                            <!-- Product Info Section -->
                            <th colspan="2" class="text-left font-bold" style="background-color: #f2f2f2 !important;">
                                {{ t('পণ্যের তথ্য', 'Product Information') }}
                            </th>

                            <!-- Before Stock Section -->
                            <th colspan="3" class="text-center font-bold" style="background-color: #fef9c3 !important;">
                                {{ t('স্টক পূর্বের তথ্য', 'Before Stock Information') }}
                            </th>

                            <!-- Buy Info Section -->
                            <th colspan="3" class="text-center font-bold" style="background-color: #dbeafe !important;">
                                {{ t('ক্রয় তথ্য', 'Buy Information') }}
                            </th>

                            <!-- Sale Info Section -->
                            <th colspan="5" class="text-center font-bold" style="background-color: #dcfce7 !important;">
                                {{ t('বিক্রয় তথ্য', 'Sale Information') }}
                            </th>

                            <!-- Profit Info Section -->
                            <th colspan="3" class="text-center font-bold" style="background-color: #ffedd5 !important;">
                                {{ t('লাভ তথ্য', 'Profit Information') }}
                            </th>

                            <!-- Available Info Section -->
                            <th colspan="2" class="text-center font-bold" style="background-color: #f3e8ff !important;">
                                {{ t('উপলব্ধ তথ্য', 'Available Information') }}
                            </th>
                        </tr>
                        <tr>
                            <!-- Product Info Headers -->
                            <th style="width: 2.5%;" class="text-center">{{ t('ক্রমিক', 'SL') }}</th>
                            <th style="width: 12%;" class="text-left">{{ t('নাম', 'Name') }}</th>

                            <!-- Before Stock Headers -->
                            <th style="width: 4.5%;" class="text-right">{{ t('পরিমাণ', 'Qty') }}</th>
                            <th style="width: 5%;" class="text-right">{{ t('দর', 'Price') }}</th>
                            <th style="width: 5.5%;" class="text-right">{{ t('মান', 'Value') }}</th>

                            <!-- Buy Info Headers -->
                            <th style="width: 4.5%;" class="text-right">{{ t('পরিমাণ', 'Qty') }}</th>
                            <th style="width: 5%;" class="text-right">{{ t('দর', 'Price') }}</th>
                            <th style="width: 6%;" class="text-right">{{ t('মোট', 'Total') }}</th>

                            <!-- Sale Info Headers -->
                            <th style="width: 4.5%;" class="text-right">{{ t('পরিমাণ', 'Qty') }}</th>
                            <th style="width: 5%;" class="text-right">{{ t('দর', 'Price') }}</th>
                            <th style="width: 6%;" class="text-right">{{ t('সাবটোটাল', 'Subtotal') }}</th>
                            <th style="width: 5%;" class="text-right">{{ t('ছাড়', 'Discount') }}</th>
                            <th style="width: 6%;" class="text-right">{{ t('মোট', 'Total') }}</th>

                            <!-- Profit Info Headers -->
                            <th style="width: 5%;" class="text-right">{{ t('প্রতি ইউনিট', 'Per Unit') }}</th>
                            <th style="width: 6%;" class="text-right">{{ t('মোট', 'Total') }}</th>
                            <th style="width: 4%;" class="text-center">%</th>

                            <!-- Available Info Headers -->
                            <th style="width: 5%;" class="text-right">{{ t('স্টক', 'Stock') }}</th>
                            <th style="width: 6.5%;" class="text-right">{{ t('মান', 'Value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="product in filteredProducts" :key="'wpa_' + product.serial">
                            <!-- Product Info -->
                            <td class="text-center">{{ product.serial }}</td>
                            <td class="text-left font-medium">
                                <div>{{ product.product_name }}</div>
                                <div v-if="product.product_model || product.category" class="text-[8px] text-gray-500">
                                    {{ [product.category, product.product_model].filter(Boolean).join(' | ') }}
                                </div>
                            </td>

                            <!-- Before Stock Info -->
                            <td class="text-right">{{ formatNumber(product.before_quantity) }}</td>
                            <td class="text-right">{{ formatCurrency(product.before_price) }}</td>
                            <td class="text-right">{{ formatCurrency(product.before_value) }}</td>

                            <!-- Buy Info -->
                            <td class="text-right">{{ formatNumber(product.buy_quantity) }}</td>
                            <td class="text-right">{{ formatCurrency(product.buy_price) }}</td>
                            <td class="text-right">{{ formatCurrency(product.total_buy_price) }}</td>

                            <!-- Sale Info -->
                            <td class="text-right">{{ formatNumber(product.sale_quantity) }}</td>
                            <td class="text-right">{{ formatCurrency(product.sale_price) }}</td>
                            <td class="text-right">{{ formatCurrency(product.total_sale_price) }}</td>
                            <td class="text-right" :style="Number(product.sale_discount) > 0 ? 'color: #dc2626;' : ''">
                                {{ formatCurrency(product.sale_discount) }}
                            </td>
                            <td class="text-right font-semibold">{{ formatCurrency(product.sale_after_discount) }}</td>

                            <!-- Profit Info -->
                            <td class="text-right" :style="Number(product.profit_per_unit) >= 0 ? 'color: #166534;' : 'color: #dc2626;'">
                                {{ formatCurrency(product.profit_per_unit) }}
                            </td>
                            <td class="text-right font-semibold" :style="Number(product.total_profit) >= 0 ? 'color: #166534;' : 'color: #dc2626;'">
                                {{ formatCurrency(product.total_profit) }}
                            </td>
                            <td class="text-center" :style="Number(product.profit_percentage) >= 0 ? 'color: #166534;' : 'color: #dc2626;'">
                                {{ formatNumber(product.profit_percentage) }}%
                            </td>

                            <!-- Available Info -->
                            <td class="text-right">{{ formatNumber(product.available_quantity) }}</td>
                            <td class="text-right">{{ formatCurrency(product.available_stock_value) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="total-row font-bold">
                            <td colspan="2" class="text-right uppercase">{{ t('মোট', 'Totals') }}</td>

                            <!-- Before Stock Totals -->
                            <td class="text-right">{{ formatNumber(totalBeforeQuantity) }}</td>
                            <td class="text-center">-</td>
                            <td class="text-right">{{ formatCurrency(totalBeforeValue) }}</td>

                            <!-- Buy Info Totals -->
                            <td class="text-right">{{ formatNumber(totalBuyQuantity) }}</td>
                            <td class="text-center">-</td>
                            <td class="text-right">{{ formatCurrency(totalBuyPrice) }}</td>

                            <!-- Sale Info Totals -->
                            <td class="text-right">{{ formatNumber(totalSaleQuantity) }}</td>
                            <td class="text-center">-</td>
                            <td class="text-right">{{ formatCurrency(totalSalePrice) }}</td>
                            <td class="text-right" :style="Number(totalSaleDiscount) > 0 ? 'color: #dc2626;' : ''">{{ formatCurrency(totalSaleDiscount) }}</td>
                            <td class="text-right font-bold">{{ formatCurrency(totalSaleAfterDiscount) }}</td>

                            <!-- Profit Info Totals -->
                            <td class="text-center">-</td>
                            <td class="text-right font-bold" :style="Number(totalProfit) >= 0 ? 'color: #166534;' : 'color: #dc2626;'">
                                {{ formatCurrency(totalProfit) }}
                            </td>
                            <td class="text-center font-bold" :style="Number(profitMargin) >= 0 ? 'color: #166534;' : 'color: #dc2626;'">
                                {{ profitMargin }}%
                            </td>

                            <!-- Available Info Totals -->
                            <td class="text-right">{{ formatNumber(totalAvailableQuantity) }}</td>
                            <td class="text-right">{{ formatCurrency(totalAvailableValue) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </WordReportLayout>
        </div>
    </AdminLayout>
</template>


<script>
import { defineComponent, computed, ref, nextTick } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import WordReportLayout from '@/Components/Reports/WordReportLayout.vue'
import { router } from '@inertiajs/vue3'
import { format, parseISO } from 'date-fns'
import axios from 'axios'
import { useLanguage } from '@/composables/useLanguage'
import { getNumberLocale } from '@/utils'

export default defineComponent({
    components: {
        AdminLayout,
        WordReportLayout
    },
    props: {
        products: {
            type: Array,
            required: true,
        },
        filters: {
            type: Object,
            required: true,
        },
        totals: {
            type: Object,
            required: true,
        },
    },

    setup(props) {
        const { currentLang, t, isBangla } = useLanguage()
        const viewMode = ref('dashboard')
        const wordReportRef = ref(null)
        const searchQuery = ref('')
        const isLoading = ref(false)
        const isDownloading = ref(false)
        const isExporting = ref(false)
        const isRefreshing = ref(false)
        const error = ref(null)
        const startDateText = ref('')
        const endDateText = ref('')
        const startDatePicker = ref(null)
        const endDatePicker = ref(null)

        // Computed properties
        const today = computed(() => format(new Date(), 'yyyy-MM-dd'))

        const filteredProducts = computed(() => {
            if (!searchQuery.value) return props.products

            const query = searchQuery.value.toLowerCase()
            return props.products.filter(product => {
                return product.product_name.toLowerCase().includes(query) ||
                    product.product_model.toLowerCase().includes(query) ||
                    product.category.toLowerCase().includes(query)
            })
        })

        const profitMargin = computed(() => {
            const afterDiscount = props.totals.sale_after_discount || props.totals.total_sale_price
            if (afterDiscount === 0) return 0
            return ((props.totals.total_profit / afterDiscount) * 100).toFixed(2)
        })

        // Totals from props
        const totalBeforeQuantity = computed(() => props.totals.before_quantity)
        const totalBeforeValue = computed(() => props.totals.before_value)
        const totalBuyQuantity = computed(() => props.totals.buy_quantity)
        const totalBuyPrice = computed(() => props.totals.total_buy_price)
        const totalSaleQuantity = computed(() => props.totals.sale_quantity)
        const totalSalePrice = computed(() => props.totals.total_sale_price)
        const totalSaleDiscount = computed(() => props.totals.sale_discount || 0)
        const totalSaleAfterDiscount = computed(() => props.totals.sale_after_discount || props.totals.total_sale_price)
        const totalProfit = computed(() => props.totals.total_profit)
        const totalAvailableQuantity = computed(() => props.totals.available_quantity)
        const totalAvailableValue = computed(() => props.totals.available_stock_value)

        const formatDisplayDate = (dateValue) => {
            if (!dateValue) return t('তারিখ নির্বাচন করুন', 'Select date')

            return new Intl.DateTimeFormat(currentLang.value === 'bn' ? 'bn-BD' : 'en-BD', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }).format(new Date(`${dateValue}T00:00:00`))
        }

        const syncDateText = () => {
            startDateText.value = formatDisplayDate(props.filters.start_date)
            endDateText.value = formatDisplayDate(props.filters.end_date)
        }

        syncDateText()

        const openDatePicker = (field) => {
            const picker = field === 'start' ? startDatePicker.value : endDatePicker.value
            if (picker?.showPicker) picker.showPicker()
            else picker?.click()
        }

        const normalizeDateDigits = (value) => value.replace(/[০-৯]/g, digit => '০১২৩৪৫৬৭৮৯'.indexOf(digit)).trim()

        const updateDateFilter = (field, dateValue) => {
            props.filters[field === 'start' ? 'start_date' : 'end_date'] = dateValue
            syncDateText()
            handleDateChange()
        }

        const handleNativeDate = (field, event) => updateDateFilter(field, event.target.value)

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

        // Methods
        const handleDateChange = () => {
            router.get(route('admin.reports.product-analysis'), {
                start_date: props.filters.start_date,
                end_date: props.filters.end_date,
            }, {
                preserveState: true,
                preserveScroll: true,
                onStart: () => isLoading.value = true,
                onFinish: () => isLoading.value = false,
                onError: (errors) => error.value = Object.values(errors).join('\n')
            })
        }

        const handleSearch = () => {
            // Debounce search functionality could be added here
        }

        const refreshData = () => {
            router.reload({
                onStart: () => isRefreshing.value = true,
                onFinish: () => isRefreshing.value = false,
                onError: (errors) => error.value = Object.values(errors).join('\n')
            })
        }

        const formatNumber = (number) => {
            const num = Number(number || 0)
            const hasDecimal = Math.abs(num - Math.round(num)) >= 0.0001
            return new Intl.NumberFormat(getNumberLocale(), {
                minimumFractionDigits: hasDecimal ? 2 : 0,
                maximumFractionDigits: 2
            }).format(num)
        }

        const formatCurrency = (amount) => {
            const num = Number(amount || 0)
            const hasDecimal = Math.abs(num - Math.round(num)) >= 0.0001
            return new Intl.NumberFormat(getNumberLocale(), {
                minimumFractionDigits: hasDecimal ? 2 : 0,
                maximumFractionDigits: 2,
            }).format(num)
        }

        const downloadPDF = async () => {
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

        const printReport = () => {
            const prev = viewMode.value
            viewMode.value = 'document'
            setTimeout(() => {
                window.print()
                viewMode.value = prev
            }, 150)
        }

        const exportToExcel = async () => {
            isExporting.value = true
            error.value = null

            try {
                // Check if we have an excel export route
                const excelRoute = route('admin.reports.product-analysis.excel')
                if (excelRoute) {
                    const response = await axios.get(excelRoute, {
                        params: {
                            start_date: props.filters.start_date,
                            end_date: props.filters.end_date,
                        },
                        responseType: 'blob'
                    })

                    const url = window.URL.createObjectURL(new Blob([response.data]))
                    const link = document.createElement('a')
                    link.href = url
                    link.setAttribute('download', `product-analysis-${props.filters.start_date}-to-${props.filters.end_date}.xlsx`)
                    document.body.appendChild(link)
                    link.click()
                    document.body.removeChild(link)
                    window.URL.revokeObjectURL(url)
                } else {
                    // Fallback to CSV if excel route not available
                    exportToCSV()
                }
            } catch (err) {
                // Fallback to CSV on error
                exportToCSV()
            } finally {
                isExporting.value = false
            }
        }

        const exportToCSV = () => {
            const data = props.products.map(product => ({
                'SL': product.serial,
                'Product Name': product.product_name,
                'Model': product.product_model,
                'Category': product.category,
                'Unit': product.unit,
                'Before Quantity': product.before_quantity,
                'Before Price': product.before_price,
                'Before Value': product.before_value,
                'Buy Quantity': product.buy_quantity,
                'Buy Price': product.buy_price,
                'Buy Total': product.total_buy_price,
                'Sale Quantity': product.sale_quantity,
                'Sale Price': product.sale_price,
                'Sale Subtotal': product.total_sale_price,
                'Sale Discount': product.sale_discount,
                'Sale Total': product.sale_after_discount,
                'Profit Per Unit': product.profit_per_unit,
                'Total Profit': product.total_profit,
                'Profit %': product.profit_percentage.toFixed(2),
                'Available Stock': product.available_quantity,
                'Stock Value': product.available_stock_value,
            }))

            // Add totals row
            data.push({
                'SL': '',
                'Product Name': 'TOTALS',
                'Model': '',
                'Category': '',
                'Unit': '',
                'Before Quantity': totalBeforeQuantity.value,
                'Before Price': '',
                'Before Value': totalBeforeValue.value,
                'Buy Quantity': totalBuyQuantity.value,
                'Buy Price': '',
                'Buy Total': totalBuyPrice.value,
                'Sale Quantity': totalSaleQuantity.value,
                'Sale Price': '',
                'Sale Subtotal': totalSalePrice.value,
                'Sale Discount': totalSaleDiscount.value,
                'Sale Total': totalSaleAfterDiscount.value,
                'Profit Per Unit': '',
                'Total Profit': totalProfit.value,
                'Profit %': profitMargin.value,
                'Available Stock': totalAvailableQuantity.value,
                'Stock Value': totalAvailableValue.value,
            })

            // Convert data to CSV format
            const headers = Object.keys(data[0]).join(',')
            const rows = data.map(item => Object.values(item).join(','))
            const csvContent = '\ufeff' + [headers, ...rows].join('\n') // Add BOM for Excel compatibility

            // Create blob and download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
            const link = document.createElement('a')

            // Create download URL
            const url = window.URL.createObjectURL(blob)
            link.setAttribute('href', url)
            link.setAttribute('download', `product-analysis-${props.filters.start_date}-to-${props.filters.end_date}.csv`)

            // Append link, trigger download, and cleanup
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            window.URL.revokeObjectURL(url)
        }


        return {
            currentLang,
            t,
            searchQuery,
            isLoading,
            isDownloading,
            isExporting,
            isRefreshing,
            error,
            today,
            filteredProducts,
            profitMargin,
            totalBeforeQuantity,
            totalBeforeValue,
            totalBuyQuantity,
            totalBuyPrice,
            totalSaleQuantity,
            totalSalePrice,
            totalSaleDiscount,
            totalSaleAfterDiscount,
            totalProfit,
            totalAvailableQuantity,
            totalAvailableValue,
            startDateText,
            endDateText,
            startDatePicker,
            endDatePicker,
            openDatePicker,
            handleNativeDate,
            handleTypedDate,
            handleDateChange,
            handleSearch,
            refreshData,
            formatNumber,
            formatCurrency,
            downloadPDF,
            exportToExcel,
            printReport,
            viewMode,
            wordReportRef,
        }
    },
})
</script>

<style scoped>
/* Add any custom styles here if needed */
.sticky {
    position: sticky;
    z-index: 10;
}

/* Ensure proper scrolling on mobile devices */
@media (max-width: 640px) {
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
    }
}

/* Animation for loading spinner */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

:deep(.pa-landscape-table th),
:deep(.pa-landscape-table td) {
    padding: 3px 2px !important;
    font-size: 6.8pt !important;
    line-height: 1.25 !important;
    word-break: break-word;
}

.print-only {
    display: none;
}

@media print {
    @page {
        size: landscape;
        margin: 6mm;
    }
    .no-print {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }
}
</style>
