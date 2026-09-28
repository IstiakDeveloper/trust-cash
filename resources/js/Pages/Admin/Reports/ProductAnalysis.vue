<template>
    <AdminLayout :title="t('পণ্য বিশ্লেষণ রিপোর্ট', 'Product Analysis Report')">
        <template #header>
            <div class="flex items-center justify-between no-print">
                <h2 class="text-base font-semibold text-gray-800">{{ t('পণ্য বিশ্লেষণ রিপোর্ট', 'Product Analysis Report') }}</h2>
                <div class="flex items-center space-x-2">
                    <!-- Date Range Selector -->
                    <div class="flex items-center space-x-1">
                        <label class="text-xs text-gray-600">{{ t('শুরুর তারিখ', 'From Date') }}</label>
                        <div class="relative">
                            <input v-model="startDateText" type="text" inputmode="numeric"
                                :placeholder="t('দিন/মাস/বছর', 'DD/MM/YYYY')"
                                class="w-32 px-2 py-1 pr-7 text-xs border-gray-300 rounded-md shadow-sm"
                                @change="handleTypedDate('start', startDateText)" />
                            <button type="button" class="absolute right-1 top-1/2 -translate-y-1/2 text-gray-500"
                                :aria-label="t('তারিখ নির্বাচন করুন', 'Select date')" @click="openDatePicker('start')">
                                <i class="fas fa-calendar-alt text-xs"></i>
                            </button>
                            <input ref="startDatePicker" type="date" :value="filters.start_date"
                                :max="filters.end_date" :lang="currentLang === 'bn' ? 'bn-BD' : 'en-BD'"
                                class="sr-only" @change="handleNativeDate('start', $event)" />
                        </div>
                        <span class="text-xs text-gray-500">{{ t('থেকে', 'to') }}</span>
                        <label class="text-xs text-gray-600">{{ t('শেষের তারিখ', 'To Date') }}</label>
                        <div class="relative">
                            <input v-model="endDateText" type="text" inputmode="numeric"
                                :placeholder="t('দিন/মাস/বছর', 'DD/MM/YYYY')"
                                class="w-32 px-2 py-1 pr-7 text-xs border-gray-300 rounded-md shadow-sm"
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

                    <!-- Search Box -->
                    <input type="text" v-model="searchQuery" @input="handleSearch" :placeholder="t('পণ্যের নাম খুঁজুন...', 'Search products...')"
                        class="w-48 px-3 py-1 text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />

                    <!-- Export Buttons -->
                    <button @click="exportToExcel" :disabled="isExporting"
                        class="flex items-center px-3 py-1 space-x-1 text-xs text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>{{ isExporting ? t('এক্সপোর্ট হচ্ছে...', 'Exporting...') : t('এক্সেল', 'Excel') }}</span>
                    </button>

                    <button @click="downloadPDF" :disabled="isDownloading"
                        class="flex items-center px-3 py-1 space-x-1 text-xs text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>{{ isDownloading ? t('জেনারেট হচ্ছে...', 'Generating...') : t('পিডিএফ', 'PDF') }}</span>
                    </button>

                    <!-- Print Button -->
                    <button @click="printReport"
                        class="flex items-center px-3 py-1 space-x-1 text-xs text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>{{ t('প্রিন্ট', 'Print') }}</span>
                    </button>


                    <!-- Refresh Button -->
                    <button @click="refreshData" :disabled="isRefreshing"
                        class="flex items-center px-3 py-1 text-xs text-white bg-gray-600 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50">
                        <svg :class="['w-4 h-4', { 'animate-spin': isRefreshing }]" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>

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
                    <div v-else class="p-2">
                        <!-- Summary Cards -->
                        <div class="grid grid-cols-5 gap-4 mb-4">
                            <div class="p-3 rounded-lg bg-blue-50">
                                <h3 class="text-xs font-medium text-blue-700">{{ t('মোট ক্রয়', 'Total Buy') }}</h3>
                                <p class="text-lg font-bold text-blue-900">{{ formatCurrency(totalBuyPrice) }}</p>
                            </div>
                            <div class="p-3 rounded-lg bg-green-50">
                                <h3 class="text-xs font-medium text-green-700">{{ t('মোট বিক্রয়', 'Total Sale') }}</h3>
                                <p class="text-lg font-bold text-green-900">{{ formatCurrency(totalSaleAfterDiscount) }}
                                </p>
                            </div>
                            <div class="p-3 rounded-lg bg-orange-50">
                                <h3 class="text-xs font-medium text-orange-700">{{ t('মোট লাভ', 'Total Profit') }}</h3>
                                <p class="text-lg font-bold text-orange-900">{{ formatCurrency(totalProfit) }}</p>
                            </div>
                            <div class="p-3 rounded-lg bg-purple-50">
                                <h3 class="text-xs font-medium text-purple-700">{{ t('লাভ মার্জিন', 'Profit Margin') }}</h3>
                                <p class="text-lg font-bold text-purple-900">{{ profitMargin }}%</p>
                            </div>
                            <div class="p-3 rounded-lg bg-yellow-50">
                                <h3 class="text-xs font-medium text-yellow-700">{{ t('স্টক মূল্য', 'Stock Value') }}</h3>
                                <p class="text-lg font-bold text-yellow-900">{{ formatCurrency(totalAvailableValue) }}
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

        <!-- PRINT AREA -->
        <div class="print-area">
            <div class="print-header">
                <h1>{{ t('পণ্য বিশ্লেষণ রিপোর্ট', 'Product Analysis Report') }}</h1>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ t('পণ্য', 'Product') }}</th>
                        <th class="text-right">{{ t('শুরু স্টক', 'Opening') }}</th>
                        <th class="text-right">{{ t('ক্রয়', 'Purchased') }}</th>
                        <th class="text-right">{{ t('বিক্রয় পরিমাণ', 'Sold Qty') }}</th>
                        <th class="text-right">{{ t('বিক্রয় মোট', 'Sold Total') }}</th>
                        <th class="text-right">{{ t('লাভ', 'Profit') }}</th>
                        <th class="text-right">{{ t('বর্তমান স্টক', 'Current Stock') }}</th>
                        <th class="text-right">{{ t('স্টক মূল্য', 'Stock Value') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="product in filteredProducts" :key="product.serial">
                        <td>{{ product.serial }}</td>
                        <td>{{ product.product_name }}</td>
                        <td class="text-right">{{ formatNumber(product.before_stock_quantity) }}</td>
                        <td class="text-right">{{ formatNumber(product.purchased_quantity) }}</td>
                        <td class="text-right">{{ formatNumber(product.sold_quantity) }}</td>
                        <td class="text-right">{{ formatCurrency(product.sold_total) }}</td>
                        <td class="text-right">{{ formatCurrency(product.profit_total) }}</td>
                        <td class="text-right">{{ formatNumber(product.available_quantity) }}</td>
                        <td class="text-right">{{ formatCurrency(product.available_value) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right"><strong>{{ t('সর্বমোট', 'Grand Total') }}</strong></td>
                        <td class="text-right"><strong>{{ formatNumber(totalSoldQuantity) }}</strong></td>
                        <td class="text-right"><strong>{{ formatCurrency(totalSoldTotal) }}</strong></td>
                        <td class="text-right"><strong>{{ formatCurrency(totalProfitTotal) }}</strong></td>
                        <td class="text-right"><strong>{{ formatNumber(totalAvailableQuantity) }}</strong></td>
                        <td class="text-right"><strong>{{ formatCurrency(totalAvailableValue) }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            <div class="print-footer">
                <span>{{ t('মুদ্রণের তারিখ', 'Printed on') }}: {{ new Date().toLocaleDateString() }}</span>
                <span>{{ t('পণ্য বিশ্লেষণ রিপোর্ট', 'Product Analysis Report') }}</span>
            </div>
        </div>
    </AdminLayout>
</template>


<script>
import { defineComponent, computed, ref } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { format, parseISO } from 'date-fns'
import axios from 'axios'
import { useLanguage } from '@/composables/useLanguage'
import { getNumberLocale } from '@/utils'

export default defineComponent({
    components: {
        AdminLayout,
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
            isDownloading.value = true
            error.value = null

            try {
                const response = await axios.get(route('admin.reports.product-analysis.pdf'), {
                    params: {
                        start_date: props.filters.start_date,
                        end_date: props.filters.end_date,
                        locale: isBangla.value ? 'bn' : 'en',
                    },
                    responseType: 'blob'
                })

                const url = window.URL.createObjectURL(new Blob([response.data]))
                const link = document.createElement('a')
                link.href = url
                link.setAttribute('download', `product-analysis-${props.filters.start_date}-to-${props.filters.end_date}.pdf`)
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
                window.URL.revokeObjectURL(url)
            } catch (err) {
                console.error('Error downloading PDF:', err)
                error.value = 'Failed to download PDF. Please try again.'
            } finally {
                isDownloading.value = false
            }
        }

        const printReport = () => {
            window.print()
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

        const printReport = () => {
            window.print()
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
</style>
