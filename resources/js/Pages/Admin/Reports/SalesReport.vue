<template>
    <AdminLayout :title="t('বিক্রয় রিপোর্ট', 'Sales Report')">
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ t('বিক্রয় রিপোর্ট', 'Sales Report') }}
            </h2>
        </template>

        <!-- Filters -->
        <div class="mb-4 sm:mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4 no-print">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 sm:gap-4">
                <!-- Customer Select -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ t('গ্রাহক', 'Customer') }}
                    </label>
                    <select v-model="filters.customer_id"
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                        <option value="">{{ t('সকল গ্রাহক', 'All Customers') }}</option>
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                            {{ customer.name || customer.customer_name || t('নামহীন গ্রাহক', 'Unnamed Customer') }}
                        </option>
                    </select>
                </div>

                <!-- Bank Account Select -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ t('ব্যাংক অ্যাকাউন্ট', 'Bank Account') }}
                    </label>
                    <select v-model="filters.bank_account_id"
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                        <option value="">{{ t('সকল ব্যাংক', 'All Bank Accounts') }}</option>
                        <option v-for="bank in bank_accounts" :key="bank.id" :value="bank.id">
                            {{ bank.bank_name || t('নামহীন ব্যাংক', 'Unnamed Bank') }} - {{ bank.account_number || t('অ্যাকাউন্ট নম্বর নেই', 'No account number') }}
                        </option>
                    </select>
                </div>

                <!-- Payment Status -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ t('পেমেন্ট অবস্থা', 'Payment Status') }}
                    </label>
                    <select v-model="filters.payment_status"
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                        <option value="">{{ t('সব স্ট্যাটাস', 'All Statuses') }}</option>
                        <option value="paid">{{ t('পরিশোধিত', 'Paid') }}</option>
                        <option value="partial">{{ t('আংশিক', 'Partial') }}</option>
                        <option value="due">{{ t('বকেয়া', 'Due') }}</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ t('শুরুর তারিখ', 'From Date') }}
                    </label>
                    <div class="relative">
                        <input v-model="fromDateText" type="text" inputmode="numeric"
                            :placeholder="t('দিন/মাস/বছর', 'DD/MM/YYYY')"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-1.5 pr-10 text-xs text-gray-700 dark:text-gray-200"
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
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ t('শেষের তারিখ', 'To Date') }}
                    </label>
                    <div class="relative">
                        <input v-model="toDateText" type="text" inputmode="numeric"
                            :placeholder="t('দিন/মাস/বছর', 'DD/MM/YYYY')"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-1.5 pr-10 text-xs text-gray-700 dark:text-gray-200"
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

                <!-- Actions -->
                <div class="sm:col-span-2 lg:col-span-1 flex items-end">
                    <div class="grid grid-cols-3 gap-1.5 w-full">
                        <button @click="viewMode = viewMode === 'dashboard' ? 'document' : 'dashboard'"
                            type="button"
                            class="inline-flex justify-center items-center px-2 py-2 border border-gray-300 rounded-lg shadow-sm text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 min-h-[36px]">
                            <span v-if="viewMode === 'dashboard'" class="truncate">📄 {{ t('ওয়ার্ড', 'Word') }}</span>
                            <span v-else class="truncate">📊 {{ t('ড্যাশবোর্ড', 'Dash') }}</span>
                        </button>
                        <button @click="downloadReport"
                            class="inline-flex justify-center items-center px-2 py-2 border border-transparent rounded-lg shadow-sm text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 min-h-[36px]">
                            <DocumentArrowDownIcon class="h-4 w-4 mr-0.5 shrink-0" />
                            <span class="truncate">{{ t('পিডিএফ', 'PDF') }}</span>
                        </button>
                        <button @click="printReport"
                            class="inline-flex justify-center items-center px-2 py-2 border border-transparent rounded-lg shadow-sm text-xs font-medium text-white bg-gray-600 hover:bg-gray-700 min-h-[36px]">
                            <PrinterIcon class="h-4 w-4 mr-0.5 shrink-0" />
                            <span class="truncate">{{ t('প্রিন্ট', 'Print') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- DASHBOARD VIEW -->
        <div v-show="viewMode === 'dashboard'" class="no-print space-y-4 sm:space-y-6">
            <!-- Summary Cards (2 cols on mobile) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 mb-4 sm:mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4">
                    <div class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('মোট বিক্রয়', 'Total Sales') }}</div>
                    <div class="mt-1 text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                        {{ summary.total_sales }}
                    </div>
                    <div class="text-[11px] sm:text-sm text-gray-500 truncate">{{ t('পরিমাণ', 'Amount') }}: {{ formatPrice(summary.total_amount) }}</div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4">
                    <div class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('মোট প্রাপ্ত', 'Total Received') }}</div>
                    <div class="mt-1 text-lg sm:text-2xl font-bold text-green-600">
                        {{ formatPrice(summary.received) }}
                    </div>
                    <div class="text-[11px] sm:text-sm text-gray-500">
                        {{ getPercentage(summary.received, summary.total_amount) }}% {{ t('সংগ্রহ', 'Collected') }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4">
                    <div class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('মোট বকেয়া', 'Total Due') }}</div>
                    <div class="mt-1 text-lg sm:text-2xl font-bold text-red-600">
                        {{ formatPrice(summary.due) }}
                    </div>
                    <div class="text-[11px] sm:text-sm text-gray-500">
                        {{ getPercentage(summary.due, summary.total_amount) }}% {{ t('অবশিষ্ট', 'Outstanding') }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4">
                    <div class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('গড় বিক্রয়', 'Average Sale') }}</div>
                    <div class="mt-1 text-lg sm:text-2xl font-bold text-gray-900 dark:text-white truncate">
                        {{ formatPrice(summary.total_sales ? summary.total_amount / summary.total_sales : 0) }}
                    </div>
                </div>
            </div>

        <!-- Monthly Reports -->
        <div class="space-y-6">
            <div v-for="report in reports" :key="report.month"
                class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <!-- Month Header -->
                <div class="px-3.5 py-4 sm:px-6 sm:py-5 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row justify-between items-start gap-2">
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">
                                {{ report.month }}
                            </h3>
                            <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{ t('বিক্রয়', 'Sales') }}: {{ report.summary.total_sales }} |
                                {{ t('পরিমাণ', 'Amount') }}: {{ formatPrice(report.summary.total_amount) }}
                            </p>
                        </div>
                        <div class="text-left sm:text-right">
                            <div class="text-xs sm:text-sm text-gray-500">{{ t('প্রাপ্ত/বকেয়া', 'Received/Due') }}</div>
                            <div class="font-medium text-xs sm:text-base">
                                <span class="text-green-600 font-bold">{{ formatPrice(report.summary.received) }}</span>
                                /
                                <span class="text-red-600 font-bold">{{ formatPrice(report.summary.due) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods Summary -->
                    <div class="mt-3 sm:mt-4 grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4">
                        <template v-for="(method, key) in report.summary.payment_methods" :key="key">
                            <div class="bg-gray-50 dark:bg-gray-700/60 rounded-lg p-2.5 sm:p-3 border border-slate-200/60 dark:border-slate-700">
                                <div class="text-xs sm:text-sm font-medium mb-1 sm:mb-2 text-slate-700 dark:text-slate-300">
                                    {{ formatPaymentMethod(key) }}
                                </div>
                                <div class="font-bold text-xs sm:text-base font-mono text-slate-900 dark:text-white">{{ formatPrice(method.amount) }}</div>
                                <!-- Bank Details if present -->
                                <template v-if="method.bank_details && Object.keys(method.bank_details).length">
                                    <div class="mt-1.5 space-y-0.5">
                                        <div v-for="(bank, bankId) in method.bank_details" :key="bankId"
                                            class="text-[11px] text-gray-500 dark:text-gray-400">
                                            {{ bank.bank_name }}: {{ formatPrice(bank.amount) }}
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Daily Sales -->
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="day in report.daily_data" :key="day.date" class="p-3 sm:p-4">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white">
                                {{ day.date }}
                            </h4>
                            <div class="text-xs sm:text-sm text-gray-500 font-medium">
                                {{ day.summary.total_sales }} {{ t('বিক্রয়', 'sales') }} |
                                {{ formatPrice(day.summary.total_amount) }}
                            </div>
                        </div>

                        <!-- Sales Table -->
                        <div class="overflow-x-auto -mx-3 sm:mx-0">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr class="text-[11px] sm:text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-700/40">
                                        <th class="px-2.5 sm:px-3 py-2 text-left">{{ t('সময়', 'Time') }}</th>
                                        <th class="px-2.5 sm:px-3 py-2 text-left">{{ t('ইনভয়েস', 'Invoice') }}</th>
                                        <th class="px-2.5 sm:px-3 py-2 text-left">{{ t('গ্রাহক', 'Customer') }}</th>
                                        <th class="px-2.5 sm:px-3 py-2 text-right">{{ t('মোট', 'Total') }}</th>
                                        <th class="px-2.5 sm:px-3 py-2 text-right">{{ t('পরিশোধ', 'Paid') }}</th>
                                        <th class="px-2.5 sm:px-3 py-2">{{ t('পেমেন্ট বিবরণ', 'Payment Details') }}</th>
                                        <th class="px-2.5 sm:px-3 py-2 text-center">{{ t('স্ট্যাটাস', 'Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="sale in day.sales" :key="sale.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20">
                                        <td class="px-2.5 sm:px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                                            {{ sale.created_at }}
                                        </td>
                                        <td class="px-2.5 sm:px-3 py-2 whitespace-nowrap text-xs sm:text-sm font-medium">
                                            {{ sale.invoice_no }}
                                        </td>
                                        <td class="px-2.5 sm:px-3 py-2 whitespace-nowrap text-xs sm:text-sm">
                                            {{ sale.customer }}
                                        </td>
                                        <td class="px-2.5 sm:px-3 py-2 whitespace-nowrap text-xs sm:text-sm text-right font-mono font-semibold">
                                            {{ formatPrice(sale.total) }}
                                        </td>
                                        <td class="px-2.5 sm:px-3 py-2 whitespace-nowrap text-xs sm:text-sm text-right text-green-600 font-mono font-semibold">
                                            {{ formatPrice(sale.paid) }}
                                        </td>
                                        <td class="px-2.5 sm:px-3 py-2 text-xs">
                                            <div v-for="payment in sale.payments" :key="payment.transaction_id"
                                                class="text-xs">
                                                {{ formatPaymentMethod(payment.method) }}:
                                                {{ formatPrice(payment.amount) }}
                                                <template v-if="payment.bank_name">
                                                    <br>
                                                    <span class="text-gray-500">
                                                        {{ payment.bank_name }} - {{ payment.account_number }}
                                                        <template v-if="payment.transaction_id">
                                                            ({{ payment.transaction_id }})
                                                        </template>
                                                    </span>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-2.5 sm:px-3 py-2 whitespace-nowrap text-center">
                                            <span :class="getStatusClass(sale.payment_status)">
                                                {{ getStatusLabel(sale.payment_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-50 dark:bg-gray-700 font-medium text-xs sm:text-sm">
                                        <td colspan="3" class="px-2.5 sm:px-3 py-2 font-bold">{{ t('দৈনিক মোট', 'Daily Total') }}</td>
                                        <td class="px-2.5 sm:px-3 py-2 text-right font-bold font-mono">{{ formatPrice(day.summary.total_amount) }}</td>
                                        <td class="px-2.5 sm:px-3 py-2 text-right text-green-600 font-bold font-mono">
                                            {{ formatPrice(day.summary.received) }}
                                        </td>
                                        <td colspan="2" class="px-2.5 sm:px-3 py-2 text-right text-red-600 font-bold font-mono">
                                            {{ t('বকেয়া', 'Due') }}: {{ formatPrice(day.summary.due) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Daily Payment Methods -->
                        <div class="mt-3 flex flex-wrap gap-2.5 sm:gap-4">
                            <template v-for="(method, key) in day.summary.payment_methods" :key="key">
                                <div class="text-xs sm:text-sm bg-slate-50 dark:bg-slate-700/40 px-2 py-1 rounded">
                                    <span class="text-gray-500">{{ formatPaymentMethod(key) }}:</span>
                                    <span class="font-bold font-mono ml-1">{{ formatPrice(method.amount) }}</span>
                                    <template v-if="method.bank_details && Object.keys(method.bank_details).length">
                                        <div class="text-[11px] text-gray-500">
                                            <div v-for="(bank, bankId) in method.bank_details" :key="bankId">
                                                {{ bank.bank_name }}: {{ formatPrice(bank.amount) }}
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
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
                :title="t('বিক্রয় রিপোর্ট', 'Sales Report')"
                :date-range="`${filters.from_date || '-'} ${t('হতে', 'to')} ${filters.to_date || '-'}`"
                orientation="portrait"
                file-name="sales-report.pdf"
            >
                <!-- Summary Table -->
                <div class="mb-5">
                    <div class="text-xs font-bold uppercase tracking-wider mb-1">{{ t('বিক্রয় সারাংশ', 'Sales Summary') }}</div>
                    <table class="word-table">
                        <thead>
                            <tr>
                                <th class="text-center">{{ t('মোট বিক্রয় ইনভয়েস', 'Total Sales Invoices') }}</th>
                                <th class="text-right">{{ t('মোট বিক্রয় পরিমাণ', 'Total Sales Amount') }}</th>
                                <th class="text-right">{{ t('মোট প্রাপ্ত (আদায়)', 'Total Received') }}</th>
                                <th class="text-right">{{ t('মোট বকেয়া', 'Total Due') }}</th>
                                <th class="text-right">{{ t('আদায় শতকরা', 'Collection %') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center font-bold">{{ summary.total_sales }}</td>
                                <td class="text-right font-bold">{{ formatPrice(summary.total_amount) }}</td>
                                <td class="text-right font-bold text-green-700">{{ formatPrice(summary.received) }}</td>
                                <td class="text-right font-bold text-red-700">{{ formatPrice(summary.due) }}</td>
                                <td class="text-right font-medium">{{ getPercentage(summary.received, summary.total_amount) }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Monthly Sales Breakdowns -->
                <div v-for="(report, rIdx) in reports" :key="'ws_' + report.month" class="mb-6">
                    <div v-if="rIdx > 0" class="page-break my-4"></div>

                    <!-- Month Subheader -->
                    <div class="border border-black bg-gray-100 px-3 py-1.5 font-bold text-sm flex justify-between items-center mb-1">
                        <span>{{ report.month }}</span>
                        <span class="text-xs font-normal">
                            {{ t('বিক্রয়:', 'Sales:') }} <strong>{{ report.summary.total_sales }}</strong> |
                            {{ t('মোট:', 'Total:') }} <strong>{{ formatPrice(report.summary.total_amount) }}</strong> |
                            {{ t('আদায়:', 'Received:') }} <strong class="text-green-700">{{ formatPrice(report.summary.received) }}</strong> |
                            {{ t('বকেয়া:', 'Due:') }} <strong class="text-red-700">{{ formatPrice(report.summary.due) }}</strong>
                        </span>
                    </div>

                    <!-- Invoices Table -->
                    <table class="word-table">
                        <thead>
                            <tr>
                                <th style="width: 14%;">{{ t('তারিখ', 'Date') }}</th>
                                <th style="width: 16%;">{{ t('ইনভয়েস', 'Invoice') }}</th>
                                <th style="width: 24%;">{{ t('গ্রাহক', 'Customer') }}</th>
                                <th class="text-right" style="width: 13%;">{{ t('মোট পরিমাণ', 'Total Amount') }}</th>
                                <th class="text-right" style="width: 13%;">{{ t('পরিশোধ', 'Paid') }}</th>
                                <th class="text-right" style="width: 10%;">{{ t('বকেয়া', 'Due') }}</th>
                                <th class="text-center" style="width: 10%;">{{ t('স্ট্যাটাস', 'Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="day in report.daily_data" :key="'wd_' + day.date">
                                <tr v-for="sale in day.sales" :key="'ws_' + sale.id">
                                    <td class="text-center">{{ day.date }}</td>
                                    <td class="font-medium">{{ sale.invoice_no }}</td>
                                    <td>{{ sale.customer }}</td>
                                    <td class="text-right font-medium">{{ formatPrice(sale.total) }}</td>
                                    <td class="text-right text-green-700">{{ formatPrice(sale.paid) }}</td>
                                    <td class="text-right text-red-700">{{ formatPrice(sale.due) }}</td>
                                    <td class="text-center">{{ getStatusLabel(sale.payment_status) }}</td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="font-bold bg-gray-50 border-t border-black">
                                <td colspan="3" class="text-right">{{ report.month }} {{ t('মোট', 'Total') }}</td>
                                <td class="text-right">{{ formatPrice(report.summary.total_amount) }}</td>
                                <td class="text-right text-green-700">{{ formatPrice(report.summary.received) }}</td>
                                <td class="text-right text-red-700">{{ formatPrice(report.summary.due) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Grand Total Table -->
                <div class="mt-4">
                    <table class="word-table">
                        <tfoot>
                            <tr class="total-row font-bold text-sm">
                                <td style="width: 54%;" class="text-right uppercase">{{ t('সর্বমোট বিক্রয় হিসাব (Grand Total)', 'Grand Total') }}</td>
                                <td style="width: 13%;" class="text-right">{{ formatPrice(summary.total_amount) }}</td>
                                <td style="width: 13%;" class="text-right text-green-700">{{ formatPrice(summary.received) }}</td>
                                <td style="width: 10%;" class="text-right text-red-700">{{ formatPrice(summary.due) }}</td>
                                <td style="width: 10%;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </WordReportLayout>
        </div>
    </AdminLayout>
</template>


<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import WordReportLayout from '@/Components/Reports/WordReportLayout.vue'
import { DocumentArrowDownIcon, PrinterIcon } from '@heroicons/vue/24/outline'
import { useLanguage } from '@/composables/useLanguage'
import { getNumberLocale } from '@/utils'

const { currentLang, t } = useLanguage()

const viewMode = ref('dashboard')
const wordReportRef = ref(null)

const props = defineProps({
    customers: {
        type: Array,
        default: () => []
    },
    bank_accounts: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    reports: {
        type: Array,
        default: () => []
    },
    summary: {
        type: Object,
        default: () => ({
            total_sales: 0,
            total_amount: 0,
            received: 0,
            due: 0
        })
    }
})


const filters = ref({
    customer_id: props.filters.customer_id || '',
    bank_account_id: props.filters.bank_account_id || '',
    payment_status: props.filters.payment_status || '',
    from_date: props.filters.from_date || '',
    to_date: props.filters.to_date || ''
})

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
    const month = first > 999 ? second : second
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
    const value = new Intl.NumberFormat(getNumberLocale(), {
        minimumFractionDigits: hasDecimal ? 2 : 0,
        maximumFractionDigits: hasDecimal ? 2 : 0
    }).format(num);

    return `৳ ${value}`;
}

const getPercentage = (value, total) => {
    if (!Number(total)) return '0.0'
    return ((Number(value || 0) / Number(total)) * 100).toFixed(1)
}

const formatPaymentMethod = (method) => {
    const methods = {
        'cash': t('নগদ', 'Cash'),
        'card': t('কার্ড', 'Card'),
        'bank': t('ব্যাংক স্থানান্তর', 'Bank Transfer'),
        'mobile_banking': t('মোবাইল ব্যাংকিং', 'Mobile Banking')
    };
    return methods[method] || method;
}

const getStatusLabel = (status) => {
    const labels = {
        'paid': t('পরিশোধিত', 'Paid'),
        'partial': t('আংশিক', 'Partial'),
        'due': t('বকেয়া', 'Due')
    };
    return labels[status] || status;
}

const getStatusClass = (status) => {
    const classes = {
        'paid': 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
        'partial': 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
        'due': 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
    };
    return classes[status] || '';
}

const applyFilters = () => {
    router.get(route('admin.reports.sales'), {
        customer_id: filters.value.customer_id,
        bank_account_id: filters.value.bank_account_id,
        payment_status: filters.value.payment_status,
        from_date: filters.value.from_date,
        to_date: filters.value.to_date
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['reports', 'summary']
    });
}

const downloadReport = async () => {
    const prev = viewMode.value;
    viewMode.value = 'document';
    await nextTick();
    setTimeout(async () => {
        if (wordReportRef.value) {
            await wordReportRef.value.downloadPdf();
        }
        viewMode.value = prev;
    }, 120);
}

const printReport = () => {
    const prev = viewMode.value;
    viewMode.value = 'document';
    setTimeout(() => {
        window.print();
        viewMode.value = prev;
    }, 150);
}

const getPaymentMethodIcon = (method) => {
    switch (method) {
        case 'cash':
            return 'fa-money-bill';
        case 'card':
            return 'fa-credit-card';
        case 'bank':
            return 'fa-university';
        case 'mobile_banking':
            return 'fa-mobile-alt';
        default:
            return 'fa-money-bill';
    }
}

const resetFilters = () => {
    filters.value = {
        customer_id: '',
        bank_account_id: '',
        payment_status: '',
        from_date: new Date().toISOString().split('T')[0],
        to_date: new Date().toISOString().split('T')[0]
    };
    applyFilters();
}

const calculateDayTotal = (payments) => {
    return Object.values(payments).reduce((total, method) => total + method.amount, 0);
}

// Computed for summary percentages
const collectionPercentage = computed(() => {
    if (!props.summary.total_amount) return 0;
    return ((props.summary.received / props.summary.total_amount) * 100).toFixed(1);
});

const duePercentage = computed(() => {
    if (!props.summary.total_amount) return 0;
    return ((props.summary.due / props.summary.total_amount) * 100).toFixed(1);
});

// Watch for changes in bank_account_id to update related data
watch(() => filters.value.bank_account_id, (newVal) => {
    if (newVal) {
        // You might want to fetch additional bank-specific data here
        applyFilters();
    }
});
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

.payment-method {
    @apply flex items-center space-x-2 text-sm;
}

.bank-details {
    @apply text-xs text-gray-500 ml-6 mt-1;
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
