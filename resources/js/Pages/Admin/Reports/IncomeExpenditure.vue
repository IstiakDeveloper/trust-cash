<template>
    <AdminLayout :title="t('আয়-ব্যয় বিবরণী', 'Income & Expenditure Statement')">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        {{ t('আয়-ব্যয় বিবরণী', 'Income & Expenditure Statement') }}
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ t('নির্দিষ্ট সময়ের ব্যবসায়িক আয় ও ব্যয়ের হিসাব', 'Monthly & cumulative breakdown of business earnings and expenses') }}
                    </p>
                </div>

                <!-- Filter & Action Controls -->
                <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5 sm:gap-3 w-full sm:w-auto no-print">
                    <!-- Year & Month in grid on mobile -->
                    <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center sm:gap-3">
                        <!-- Year Selection -->
                        <div class="flex items-center space-x-1.5">
                            <label class="text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ t('বছর', 'Year') }}:</label>
                            <select v-model="selectedYear" @change="handleDateChange"
                                class="w-full sm:w-auto text-xs font-medium border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors py-1.5 pl-2.5 pr-7">
                                <option v-for="year in years" :key="year" :value="year">
                                    {{ formatNumber(year) }}
                                </option>
                            </select>
                        </div>

                        <!-- Month Selection -->
                        <div class="flex items-center space-x-1.5">
                            <label class="text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ t('মাস', 'Month') }}:</label>
                            <select v-model="selectedMonth" @change="handleDateChange"
                                class="w-full sm:w-auto text-xs font-medium border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors py-1.5 pl-2.5 pr-7">
                                <option v-for="month in months" :key="month.value" :value="month.value">
                                    {{ getMonthName(month.value) }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons in 3 columns on mobile -->
                    <div class="grid grid-cols-3 gap-2 w-full sm:flex sm:w-auto">
                        <!-- View Mode Toggle -->
                        <button @click="viewMode = viewMode === 'dashboard' ? 'document' : 'dashboard'"
                            type="button"
                            class="inline-flex items-center justify-center px-2.5 sm:px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-300 dark:border-slate-600 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 shadow-sm transition-all text-center">
                            <span v-if="viewMode === 'dashboard'">📄 {{ t('ওয়ার্ড', 'Word') }}</span>
                            <span v-else>📊 {{ t('ড্যাশবোর্ড', 'Dashboard') }}</span>
                        </button>

                        <!-- Download PDF Button -->
                        <button @click="downloadPDF" :disabled="isDownloading"
                            class="inline-flex items-center justify-center px-2.5 sm:px-3.5 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50 transition-all text-center">
                            <svg v-if="!isDownloading" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 mr-1" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 mr-1 animate-spin" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>{{ isDownloading ? '...' : 'PDF' }}</span>
                        </button>

                        <!-- Print Button -->
                        <button @click="printReport"
                            class="inline-flex items-center justify-center px-2.5 sm:px-3.5 py-1.5 text-xs font-medium text-white bg-black hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900 transition-all text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>{{ t('প্রিন্ট', 'Print') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- DASHBOARD VIEW -->
        <div v-show="viewMode === 'dashboard'" class="no-print py-4 sm:py-6">
            <div id="income-expenditure-content" class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
                <!-- Summary Net Result Card -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between px-4 sm:px-5 py-3 rounded-xl border transition-colors shadow-sm gap-2"
                     :class="[
                         netResultPeriod >= 0
                             ? 'bg-emerald-50/80 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200'
                             : 'bg-rose-50/80 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200'
                     ]">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-2.5 w-2.5 rounded-full flex-shrink-0"
                              :class="netResultPeriod >= 0 ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                        <span class="text-xs sm:text-sm font-semibold">
                            {{ netResultPeriod >= 0 ? t('চলতি মাসে নিট উদ্বৃত্ত (লাভ)', 'Net Surplus for Period') : t('চলতি মাসে নিট ঘাটতি (ক্ষতি)', 'Net Deficit for Period') }}:
                            <strong>{{ formatCurrency(Math.abs(netResultPeriod)) }}</strong>
                        </span>
                    </div>
                    <div class="text-xs font-mono font-bold pl-5 sm:pl-0">
                        {{ t('ক্রমপুঞ্জিত:', 'Cumulative:') }} {{ formatCurrency(netResultCumulative) }}
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-2">
                    <!-- Income Section -->
                    <div class="flex flex-col h-full overflow-hidden bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm transition-colors">
                        <div class="px-4 sm:px-5 py-3 sm:py-3.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/90 flex items-center justify-between">
                            <h3 class="text-sm sm:text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                {{ t('আয় বিবরণী', 'Income') }}
                            </h3>
                        </div>
                        <div class="flex-1 overflow-x-auto flex flex-col justify-between">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50/40 dark:bg-slate-900/30 text-xs font-semibold text-slate-600 dark:text-slate-400">
                                        <th class="px-3 sm:px-5 py-2.5 border-r border-slate-200 dark:border-slate-700">{{ t('বিবরণ', 'Description') }}</th>
                                        <th class="px-3 sm:px-5 py-2.5 text-right border-r border-slate-200 dark:border-slate-700">{{ t('মাসিক', 'Period') }}</th>
                                        <th class="px-3 sm:px-5 py-2.5 text-right">{{ t('ক্রমপুঞ্জিত', 'Cumulative') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-200">
                                            {{ t('বিক্রয় লাভ', 'Sales Profit') }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-bold font-mono text-emerald-600 border-r border-slate-200 dark:border-slate-700">
                                            {{ formatCurrency(income.sales_profit?.period ?? 0) }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-bold font-mono text-emerald-600">
                                            {{ formatCurrency(income.sales_profit?.cumulative ?? 0) }}
                                        </td>
                                    </tr>

                                    <tr v-for="category in (income.extra_income?.categories || [])" :key="category.name" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm text-slate-700 dark:text-slate-200">
                                            {{ category.name }} <span class="text-slate-400 text-xs">({{ t('অন্যান্য আয়', 'Other') }})</span>
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-mono text-emerald-600 border-r border-slate-200 dark:border-slate-700">
                                            {{ formatCurrency(category.period) }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-mono text-emerald-600">
                                            {{ formatCurrency(category.cumulative) }}
                                        </td>
                                    </tr>

                                    <!-- Spacer rows on desktop to align Grand Total with Total Expenditure on the same line -->
                                    <tr v-for="i in incomeSpacerCount" :key="'inc_spacer_' + i" class="hidden lg:table-row">
                                        <td class="px-3 sm:px-5 py-3 border-r border-slate-200 dark:border-slate-700">&nbsp;</td>
                                        <td class="px-3 sm:px-5 py-3 border-r border-slate-200 dark:border-slate-700">&nbsp;</td>
                                        <td class="px-3 sm:px-5 py-3">&nbsp;</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <!-- 1. Total Income -->
                                    <tr class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-100/70 dark:bg-slate-900/60 font-bold">
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-extrabold text-slate-900 dark:text-slate-100">
                                            {{ t('মোট আয়', 'Total Income') }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-extrabold font-mono text-emerald-600 border-r border-slate-200 dark:border-slate-700">
                                            {{ formatCurrency(income.total?.period ?? 0) }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-extrabold font-mono text-emerald-600">
                                            {{ formatCurrency(income.total?.cumulative ?? 0) }}
                                        </td>
                                    </tr>

                                    <!-- 2. Surplus / Deficit -->
                                    <tr class="border-t border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/40 font-bold">
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-extrabold text-slate-900 dark:text-slate-100">
                                            {{ surplusLabel }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-extrabold font-mono border-r border-slate-200 dark:border-slate-700"
                                            :class="netResultPeriod >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                            {{ formatCurrency(netResultPeriod) }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-extrabold font-mono"
                                            :class="netResultCumulative >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                            {{ formatCurrency(netResultCumulative) }}
                                        </td>
                                    </tr>

                                    <!-- 3. Grand Total (Balanced with Total Expenditure) -->
                                    <tr class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-100/90 dark:bg-slate-900/80 font-bold">
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-extrabold text-slate-900 dark:text-slate-100">
                                            {{ t('সর্বমোট', 'Grand Total') }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-extrabold font-mono text-rose-600 border-r border-slate-200 dark:border-slate-700">
                                            {{ formatCurrency(expenditure.total?.period ?? 0) }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-extrabold font-mono text-rose-600">
                                            {{ formatCurrency(expenditure.total?.cumulative ?? 0) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Expenditure Section -->
                    <div class="flex flex-col h-full overflow-hidden bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm transition-colors">
                        <div class="px-4 sm:px-5 py-3 sm:py-3.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/90 flex items-center justify-between">
                            <h3 class="text-sm sm:text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                {{ t('ব্যয় বিবরণী', 'Expenditure') }}
                            </h3>
                        </div>
                        <div class="flex-1 overflow-x-auto flex flex-col justify-between">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50/40 dark:bg-slate-900/30 text-xs font-semibold text-slate-600 dark:text-slate-400">
                                        <th class="px-3 sm:px-5 py-2.5 border-r border-slate-200 dark:border-slate-700">{{ t('বিবরণ', 'Description') }}</th>
                                        <th class="px-3 sm:px-5 py-2.5 text-right border-r border-slate-200 dark:border-slate-700">{{ t('মাসিক', 'Period') }}</th>
                                        <th class="px-3 sm:px-5 py-2.5 text-right">{{ t('ক্রমপুঞ্জিত', 'Cumulative') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                    <tr v-for="category in (expenditure.categories || [])" :key="category.name" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm text-slate-700 dark:text-slate-200">
                                            {{ category.name }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-mono text-rose-600 border-r border-slate-200 dark:border-slate-700">
                                            {{ formatCurrency(category.period) }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-mono text-rose-600">
                                            {{ formatCurrency(category.cumulative) }}
                                        </td>
                                    </tr>

                                    <!-- Spacer rows on desktop to align Total Expenditure with Grand Total on the same line -->
                                    <tr v-for="i in expenditureSpacerCount" :key="'exp_spacer_' + i" class="hidden lg:table-row">
                                        <td class="px-3 sm:px-5 py-3 border-r border-slate-200 dark:border-slate-700">&nbsp;</td>
                                        <td class="px-3 sm:px-5 py-3 border-r border-slate-200 dark:border-slate-700">&nbsp;</td>
                                        <td class="px-3 sm:px-5 py-3">&nbsp;</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-100/90 dark:bg-slate-900/80 font-bold">
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 border-r border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-extrabold text-slate-900 dark:text-slate-100">
                                            {{ t('মোট ব্যয়', 'Total Expenditure') }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-extrabold font-mono text-rose-600 border-r border-slate-200 dark:border-slate-700">
                                            {{ formatCurrency(expenditure.total?.period ?? 0) }}
                                        </td>
                                        <td class="px-3 sm:px-5 py-2.5 sm:py-3 text-right text-xs sm:text-sm font-extrabold font-mono text-rose-600">
                                            {{ formatCurrency(expenditure.total?.cumulative ?? 0) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- WORD DOCUMENT / PRINT VIEW (Black & White Word Line Art) -->
        <div :class="[viewMode === 'document' ? 'block py-4' : 'print-only']">
            <WordReportLayout
                ref="wordReportRef"
                :title="t('আয়-ব্যয় বিবরণী', 'Income & Expenditure Statement')"
                :date-range="`${formattedStartDate} ${t('হতে', 'to')} ${formattedEndDate}`"
                orientation="portrait"
                file-name="income-expenditure.pdf"
            >
                <!-- Unified 6-Column Word Line Table with Aligned Totals -->
                <table class="word-table" style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th colspan="3" style="width: 50%; text-align: center; font-size: 8.5pt;">{{ t('আয় বিবরণী (Income)', 'Income') }}</th>
                            <th colspan="3" style="width: 50%; text-align: center; font-size: 8.5pt;">{{ t('ব্যয় বিবরণী (Expenditure)', 'Expenditure') }}</th>
                        </tr>
                        <tr>
                            <th style="width: 24%;">{{ t('বিবরণ', 'Particulars') }}</th>
                            <th class="text-right" style="width: 13%;">{{ t('চলতি (৳)', 'Period') }}</th>
                            <th class="text-right" style="width: 13%;">{{ t('ক্রমপুঞ্জিত (৳)', 'Cumulative') }}</th>
                            <th style="width: 24%;">{{ t('বিবরণ', 'Particulars') }}</th>
                            <th class="text-right" style="width: 13%;">{{ t('চলতি (৳)', 'Period') }}</th>
                            <th class="text-right" style="width: 13%;">{{ t('ক্রমপুঞ্জিত (৳)', 'Cumulative') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, idx) in incomeExpenditureRows" :key="'ie_row_' + idx">
                            <!-- Left: Income -->
                            <td>{{ row.left ? row.left.name : '' }}</td>
                            <td class="text-right">{{ row.left && row.left.period != null ? formatCurrency(row.left.period) : '' }}</td>
                            <td class="text-right">{{ row.left && row.left.cumulative != null ? formatCurrency(row.left.cumulative) : '' }}</td>

                            <!-- Right: Expenditure -->
                            <td>{{ row.right ? row.right.name : '' }}</td>
                            <td class="text-right">{{ row.right && row.right.period != null ? formatCurrency(row.right.period) : '' }}</td>
                            <td class="text-right">{{ row.right && row.right.cumulative != null ? formatCurrency(row.right.cumulative) : '' }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <!-- Subtotal: Total Income (Left) & Total Expenditure (Right) on the SAME row -->
                        <tr class="subtotal-row">
                            <td><strong>{{ t('মোট আয়', 'Total Income') }}</strong></td>
                            <td class="text-right"><strong>{{ formatCurrency(income.total?.period ?? 0) }}</strong></td>
                            <td class="text-right"><strong>{{ formatCurrency(income.total?.cumulative ?? 0) }}</strong></td>
                            <td><strong>{{ t('মোট ব্যয়', 'Total Expenditure') }}</strong></td>
                            <td class="text-right"><strong>{{ formatCurrency(expenditure.total?.period ?? 0) }}</strong></td>
                            <td class="text-right"><strong>{{ formatCurrency(expenditure.total?.cumulative ?? 0) }}</strong></td>
                        </tr>
                        <!-- Row 2: Surplus / Deficit on Income (Left) side -->
                        <tr class="subtotal-row" style="background-color: #fafafa;">
                            <td><strong>{{ surplusLabel }}</strong></td>
                            <td class="text-right" :style="netResultPeriod >= 0 ? '' : 'color: #dc2626;'"><strong>{{ formatCurrency(netResultPeriod) }}</strong></td>
                            <td class="text-right" :style="netResultCumulative >= 0 ? '' : 'color: #dc2626;'"><strong>{{ formatCurrency(netResultCumulative) }}</strong></td>
                            <td></td>
                            <td class="text-right"></td>
                            <td class="text-right"></td>
                        </tr>
                        <!-- Grand Total: Both sides on the SAME row -->
                        <tr class="total-row grand-total">
                            <td><strong>{{ t('সর্বমোট', 'Grand Total') }}</strong></td>
                            <td class="text-right"><strong>{{ formatCurrency(expenditure.total?.period ?? 0) }}</strong></td>
                            <td class="text-right"><strong>{{ formatCurrency(expenditure.total?.cumulative ?? 0) }}</strong></td>
                            <td><strong>{{ t('সর্বমোট', 'Grand Total') }}</strong></td>
                            <td class="text-right"><strong>{{ formatCurrency(expenditure.total?.period ?? 0) }}</strong></td>
                            <td class="text-right"><strong>{{ formatCurrency(expenditure.total?.cumulative ?? 0) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </WordReportLayout>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import WordReportLayout from '@/Components/Reports/WordReportLayout.vue'
import { router } from '@inertiajs/vue3'
import { useLanguage } from '@/composables/useLanguage'

const { t, formatCurrency, formatNumber, isBangla } = useLanguage()

const props = defineProps({
    filters: { type: Object, required: true },
    income: { type: Object, required: true },
    expenditure: { type: Object, required: true },
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
const viewMode = ref('dashboard') // 'dashboard' | 'document'
const wordReportRef = ref(null)
const isDownloading = ref(false)

const incomeExpenditureRows = computed(() => {
    // Left: Income
    const left = []
    // 1. Sales profit
    left.push({
        name: t('বিক্রয় লাভ', 'Sales Profit'),
        period: props.income?.sales_profit?.period ?? 0,
        cumulative: props.income?.sales_profit?.cumulative ?? 0
    })
    // 2. Extra income categories
    for (const cat of (props.income?.extra_income?.categories || [])) {
        left.push({
            name: cat.name,
            period: cat.period,
            cumulative: cat.cumulative
        })
    }

    // Right: Expenditure
    const right = []
    for (const cat of (props.expenditure?.categories || [])) {
        right.push({
            name: cat.name,
            period: cat.period,
            cumulative: cat.cumulative
        })
    }

    const maxLen = Math.max(left.length, right.length)
    const rows = []
    for (let i = 0; i < maxLen; i++) {
        rows.push({
            left: left[i] || null,
            right: right[i] || null
        })
    }
    return rows
})

const netResultPeriod = computed(() => {
    return (props.income.total?.period ?? 0) - (props.expenditure.total?.period ?? 0)
})

const netResultCumulative = computed(() => {
    return (props.income.total?.cumulative ?? 0) - (props.expenditure.total?.cumulative ?? 0)
})

const surplusLabel = computed(() => {
    if (netResultPeriod.value >= 0 && netResultCumulative.value >= 0) {
        return t('উদ্বৃত্ত', 'Surplus')
    } else if (netResultPeriod.value < 0 && netResultCumulative.value < 0) {
        return t('ঘাটতি', 'Deficit')
    }
    return t('উদ্বৃত্ত / (ঘাটতি)', 'Surplus / (Deficit)')
})

const incomeItemsCount = computed(() => {
    return 1 + (props.income?.extra_income?.categories?.length || 0)
})

const expenditureItemsCount = computed(() => {
    return (props.expenditure?.categories?.length || 0)
})

const targetTotalRows = computed(() => {
    return Math.max(incomeItemsCount.value + 3, expenditureItemsCount.value + 1)
})

const incomeSpacerCount = computed(() => {
    return Math.max(0, targetTotalRows.value - (incomeItemsCount.value + 3))
})

const expenditureSpacerCount = computed(() => {
    return Math.max(0, targetTotalRows.value - (expenditureItemsCount.value + 1))
})

const formattedStartDate = computed(() => {
    if (!props.filters.start_date) return '-'
    const d = new Date(props.filters.start_date)
    return d.toLocaleDateString(isBangla.value ? 'bn-BD' : 'en-US', { day: 'numeric', month: 'short', year: 'numeric' })
})

const formattedEndDate = computed(() => {
    if (!props.filters.end_date) return '-'
    const d = new Date(props.filters.end_date)
    return d.toLocaleDateString(isBangla.value ? 'bn-BD' : 'en-US', { day: 'numeric', month: 'short', year: 'numeric' })
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

    router.get(route('admin.reports.income-expenditure'), {
        start_date: formatDate(startDate),
        end_date: formatDate(endDate),
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const downloadPDF = async () => {
    isDownloading.value = true
    const previousMode = viewMode.value
    if (viewMode.value !== 'document') {
        viewMode.value = 'document'
        await nextTick()
        await new Promise(r => setTimeout(r, 120))
    }

    try {
        await wordReportRef.value?.downloadPdf()
    } finally {
        if (previousMode !== 'document') {
            viewMode.value = previousMode
        }
        isDownloading.value = false
    }
}

const printReport = async () => {
    const previousMode = viewMode.value
    if (viewMode.value !== 'document') {
        viewMode.value = 'document'
        await nextTick()
        await new Promise(r => setTimeout(r, 120))
    }
    window.print()
    if (previousMode !== 'document') {
        setTimeout(() => {
            viewMode.value = previousMode
        }, 1000)
    }
}
</script>
