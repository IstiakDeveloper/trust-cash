<template>
    <AdminLayout :title="t('রিসিপ্ট ও পেমেন্ট', 'Receipt & Payment')">
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-gray-200 pb-4 no-print">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('রিসিপ্ট ও পেমেন্ট স্টেটমেন্ট', 'Receipt & Payment Statement') }}</h2>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ t('তারিখ অনুযায়ী পিরিয়ড ও ক্রমপুঞ্জিত আয়-ব্যয়ের বিবরণী', 'Date to date period & cumulative receipt and payment statement') }}
                    </p>
                </div>

                <!-- Filter Controls -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Bank Account Select (Default: All Accounts) -->
                    <div class="flex items-center space-x-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase">{{ t('অ্যাকাউন্ট', 'Account') }}:</label>
                        <select
                            v-model="selectedBankAccountId"
                            @change="handleFilterChange"
                            class="form-select text-sm rounded-md border-gray-300 shadow-sm py-1.5 pl-3 pr-8 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">{{ t('সকল ব্যাংক অ্যাকাউন্ট (All Banks)', 'All Bank Accounts') }}</option>
                            <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                {{ account.account_name }} - {{ account.bank_name }}
                            </option>
                        </select>
                    </div>

                    <!-- Date Range Inputs -->
                    <div class="flex items-center space-x-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase">{{ t('শুরু', 'From') }}:</label>
                        <input
                            type="date"
                            v-model="startDate"
                            @change="handleFilterChange"
                            class="form-input text-sm rounded-md border-gray-300 shadow-sm py-1.5 px-2.5 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>

                    <div class="flex items-center space-x-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase">{{ t('শেষ', 'To') }}:</label>
                        <input
                            type="date"
                            v-model="endDate"
                            @change="handleFilterChange"
                            class="form-input text-sm rounded-md border-gray-300 shadow-sm py-1.5 px-2.5 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>

                    <!-- Preset Buttons -->
                    <div class="flex items-center space-x-1 bg-gray-100 p-1 rounded-md border border-gray-200">
                        <button
                            type="button"
                            @click="applyPreset('this_month')"
                            class="px-2 py-1 text-xs font-medium rounded hover:bg-white hover:shadow-xs transition"
                            :class="activePreset === 'this_month' ? 'bg-white shadow-xs text-indigo-600 font-bold' : 'text-gray-600'"
                        >
                            {{ t('চলতি মাস', 'This Month') }}
                        </button>
                        <button
                            type="button"
                            @click="applyPreset('today')"
                            class="px-2 py-1 text-xs font-medium rounded hover:bg-white hover:shadow-xs transition"
                            :class="activePreset === 'today' ? 'bg-white shadow-xs text-indigo-600 font-bold' : 'text-gray-600'"
                        >
                            {{ t('আজ', 'Today') }}
                        </button>
                        <button
                            type="button"
                            @click="applyPreset('last_month')"
                            class="px-2 py-1 text-xs font-medium rounded hover:bg-white hover:shadow-xs transition"
                            :class="activePreset === 'last_month' ? 'bg-white shadow-xs text-indigo-600 font-bold' : 'text-gray-600'"
                        >
                            {{ t('গত মাস', 'Last Month') }}
                        </button>
                        <button
                            type="button"
                            @click="applyPreset('this_year')"
                            class="px-2 py-1 text-xs font-medium rounded hover:bg-white hover:shadow-xs transition"
                            :class="activePreset === 'this_year' ? 'bg-white shadow-xs text-indigo-600 font-bold' : 'text-gray-600'"
                        >
                            {{ t('চলতি বছর', 'This Year') }}
                        </button>
                    </div>

                    <!-- PDF Download Button -->
                    <button
                        @click="downloadPDF"
                        :disabled="isDownloading"
                        class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 text-white text-sm font-medium rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50 shadow-sm transition"
                    >
                        <svg v-if="!isDownloading" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ isDownloading ? t('ডাউনলোড হচ্ছে...', 'Downloading...') : t('পিডিএফ ডাউনলোড', 'Download PDF') }}
                    </button>

                    <!-- Print Button -->
                    <button
                        @click="printReport"
                        class="inline-flex items-center px-3.5 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 shadow-sm transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        {{ t('প্রিন্ট', 'Print') }}
                    </button>

                </div>
            </div>
        </template>

        <div class="py-6 px-4 max-w-[1600px] mx-auto space-y-4">
            <!-- Statement Header Badge -->
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-300 flex flex-col sm:flex-row justify-between items-center text-sm gap-2">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-gray-800 text-base uppercase tracking-wide">
                        {{ t('রিসিপ্ট ও পেমেন্ট বিবরণী', 'Statement of Receipts & Payments') }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded text-xs font-semibold"
                          :class="selectedBankAccountId ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-green-50 text-green-700 border border-green-200'">
                        {{ selectedAccountName || t('সকল ব্যাংক অ্যাকাউন্ট (Consolidated)', 'All Bank Accounts (Consolidated)') }}
                    </span>
                </div>
                <div class="text-xs sm:text-sm font-semibold text-gray-700 bg-gray-50 px-3 py-1.5 rounded-md border border-gray-300">
                    <span class="text-gray-500 mr-1.5">{{ t('তারিখ সীমা:', 'Date:') }}</span>
                    <span class="text-indigo-700 font-bold">{{ formattedDateRange }}</span>
                </div>
            </div>

            <!-- Two-Column Statement Grid with Complete Cell Borders -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-stretch">
                <!-- ================= RECEIPTS SECTION ================= -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden flex flex-col justify-between border-2 border-gray-400">
                    <div>
                        <div class="bg-emerald-700 text-white px-4 py-2.5 flex justify-between items-center border-b-2 border-emerald-800">
                            <h3 class="text-base font-bold tracking-wide uppercase">{{ t('রিসিপ্ট (Receipts)', 'Receipts') }}</h3>
                            <span class="text-xs font-medium text-emerald-100">{{ t('ইনফ্লো / প্রাপ্তি', 'Inflows') }}</span>
                        </div>
                        <table class="w-full text-sm border-collapse border border-gray-400">
                            <thead>
                                <tr class="bg-gray-200 text-xs font-bold text-gray-800 uppercase">
                                    <th class="text-center py-2.5 px-3 w-12 border border-gray-400">{{ t('ক্রমিক', 'SL') }}</th>
                                    <th class="text-left py-2.5 px-3 border border-gray-400">{{ t('বিবরণ', 'Particulars') }}</th>
                                    <th class="text-right py-2.5 px-3 w-36 border border-gray-400">{{ t('চলতি পিরিয়ড', 'Current Month') }}</th>
                                    <th class="text-right py-2.5 px-3 w-36 border border-gray-400">{{ t('ক্রমপুঞ্জিত', 'Cumulative') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- 1. Opening Cash in Hand / Bank -->
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="text-center py-2 px-3 font-semibold text-gray-600 border border-gray-300">1</td>
                                    <td class="py-2 px-3 font-semibold text-gray-800 border border-gray-300">
                                        {{ t('ব্যাংকে শুরুর নগদ', 'Opening Cash in Hand / Bank') }}
                                    </td>
                                    <td class="text-right py-2 px-3 font-bold text-emerald-700 border border-gray-300">
                                        {{ formatCurrency(receipt?.opening_cash_on_bank?.period || 0) }}
                                    </td>
                                    <td class="text-right py-2 px-3 font-bold text-emerald-800 border border-gray-300">
                                        {{ formatCurrency(receipt?.opening_cash_on_bank?.cumulative || 0) }}
                                    </td>
                                </tr>

                                <!-- 2. Sale Collection -->
                                <tr class="bg-gray-50/40 hover:bg-gray-50 transition">
                                    <td class="text-center py-2 px-3 font-semibold text-gray-600 border border-gray-300">2</td>
                                    <td class="py-2 px-3 font-semibold text-gray-800 border border-gray-300">
                                        {{ t('বিক্রয় সংগ্রহ', 'Sale Collection') }}
                                    </td>
                                    <td class="text-right py-2 px-3 font-bold text-emerald-700 border border-gray-300">
                                        {{ formatCurrency(receipt?.sale_collection?.period || 0) }}
                                    </td>
                                    <td class="text-right py-2 px-3 font-bold text-emerald-800 border border-gray-300">
                                        {{ formatCurrency(receipt?.sale_collection?.cumulative || 0) }}
                                    </td>
                                </tr>

                                <!-- 3. Others Income (Header) -->
                                <tr class="bg-gray-100 font-bold border border-gray-400">
                                    <td class="text-center py-2 px-3 font-bold text-gray-800 border border-gray-300">3</td>
                                    <td colspan="3" class="py-2 px-3 font-bold text-gray-800 border border-gray-300">
                                        {{ t('অন্যান্য আয়', 'Others Income') }}
                                    </td>
                                </tr>
                                <!-- Extra Income Sub-rows -->
                                <template v-if="receipt?.extra_income?.categories && receipt.extra_income.categories.length > 0">
                                    <tr v-for="(cat, idx) in receipt.extra_income.categories" :key="idx" class="hover:bg-emerald-50/30 transition text-xs">
                                        <td class="border border-gray-300"></td>
                                        <td class="py-1.5 px-3 pl-8 text-gray-700 border border-gray-300">
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span>
                                            {{ cat.category }}
                                        </td>
                                        <td class="text-right py-1.5 px-3 text-emerald-600 font-medium border border-gray-300">
                                            {{ formatCurrency(cat.period || 0) }}
                                        </td>
                                        <td class="text-right py-1.5 px-3 text-emerald-700 font-medium border border-gray-300">
                                            {{ formatCurrency(cat.cumulative || 0) }}
                                        </td>
                                    </tr>
                                </template>
                                <template v-else>
                                    <tr class="text-xs text-gray-400 italic">
                                        <td class="border border-gray-300"></td>
                                        <td class="py-1.5 px-3 pl-8 border border-gray-300">{{ t('এই পিরিয়ডে অন্য কোনো আয় নেই', 'No others income in this period') }}</td>
                                        <td class="text-right py-1.5 px-3 border border-gray-300">{{ formatCurrency(0) }}</td>
                                        <td class="text-right py-1.5 px-3 border border-gray-300">{{ formatCurrency(0) }}</td>
                                    </tr>
                                </template>
                                <!-- Total Others Income -->
                                <tr class="bg-emerald-50/60 font-semibold text-xs sm:text-sm">
                                    <td class="border border-gray-300"></td>
                                    <td class="py-2 px-3 pl-8 text-gray-800 font-bold border border-gray-300">
                                        {{ t('মোট অন্যান্য আয়', 'Total Others Income') }}
                                    </td>
                                    <td class="text-right py-2 px-3 text-emerald-700 font-bold border border-gray-300">
                                        {{ formatCurrency(receipt?.extra_income?.total?.period || 0) }}
                                    </td>
                                    <td class="text-right py-2 px-3 text-emerald-800 font-bold border border-gray-300">
                                        {{ formatCurrency(receipt?.extra_income?.total?.cumulative || 0) }}
                                    </td>
                                </tr>

                                <!-- 4. Fund Receive (ফান্ড গ্রহণ) -->
                                <tr class="bg-gray-100 font-bold border border-gray-400">
                                    <td class="text-center py-2 px-3 font-bold text-gray-800 border border-gray-300">4</td>
                                    <td colspan="3" class="py-2 px-3 font-bold text-gray-800 border border-gray-300">
                                        {{ t('ফান্ড গ্রহণ', 'Fund Receive') }}
                                    </td>
                                </tr>
                                <template v-if="receipt?.fund_receive?.items && receipt.fund_receive.items.length > 0">
                                    <tr v-for="(item, idx) in receipt.fund_receive.items" :key="idx" class="hover:bg-emerald-50/30 transition text-xs">
                                        <td class="border border-gray-300"></td>
                                        <td class="py-1.5 px-3 pl-8 text-gray-700 border border-gray-300">
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span>
                                            {{ item.name }}
                                        </td>
                                        <td class="text-right py-1.5 px-3 text-emerald-600 font-medium border border-gray-300">
                                            {{ formatCurrency(item.period || 0) }}
                                        </td>
                                        <td class="text-right py-1.5 px-3 text-emerald-700 font-medium border border-gray-300">
                                            {{ formatCurrency(item.cumulative || 0) }}
                                        </td>
                                    </tr>
                                </template>
                                <tr class="bg-emerald-50/60 font-semibold text-xs sm:text-sm">
                                    <td class="border border-gray-300"></td>
                                    <td class="py-2 px-3 pl-8 text-gray-800 font-bold border border-gray-300">
                                        {{ t('মোট ফান্ড গ্রহণ', 'Total Fund Receive') }}
                                    </td>
                                    <td class="text-right py-2 px-3 text-emerald-700 font-bold border border-gray-300">
                                        {{ formatCurrency(receipt?.fund_receive?.total?.period || 0) }}
                                    </td>
                                    <td class="text-right py-2 px-3 text-emerald-800 font-bold border border-gray-300">
                                        {{ formatCurrency(receipt?.fund_receive?.total?.cumulative || 0) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Total Receipt Row (Pinned to bottom for baseline match) -->
                    <div class="border-t-2 border-gray-400 bg-emerald-50 px-4 py-3 mt-auto">
                        <div class="grid grid-cols-12 items-center text-sm sm:text-base font-bold">
                            <span class="col-span-6 text-gray-900 uppercase tracking-wide">
                                {{ t('মোট রিসিপ্ট', 'Total Receipt') }}
                            </span>
                            <span class="col-span-3 text-right text-emerald-700 font-extrabold pr-3">
                                {{ formatCurrency(receipt?.total?.period || 0) }}
                            </span>
                            <span class="col-span-3 text-right text-emerald-800 font-black">
                                {{ formatCurrency(receipt?.total?.cumulative || 0) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ================= PAYMENTS SECTION ================= -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden flex flex-col justify-between border-2 border-gray-400">
                    <div>
                        <div class="bg-rose-700 text-white px-4 py-2.5 flex justify-between items-center border-b-2 border-rose-800">
                            <h3 class="text-base font-bold tracking-wide uppercase">{{ t('পেমেন্ট (Payments)', 'Payments') }}</h3>
                            <span class="text-xs font-medium text-rose-100">{{ t('আউটফ্লো / খরচ', 'Outflows') }}</span>
                        </div>
                        <table class="w-full text-sm border-collapse border border-gray-400">
                            <thead>
                                <tr class="bg-gray-200 text-xs font-bold text-gray-800 uppercase">
                                    <th class="text-center py-2.5 px-3 w-12 border border-gray-400">{{ t('ক্রমিক', 'SL') }}</th>
                                    <th class="text-left py-2.5 px-3 border border-gray-400">{{ t('বিবরণ', 'Particulars') }}</th>
                                    <th class="text-right py-2.5 px-3 w-36 border border-gray-400">{{ t('চলতি পিরিয়ড', 'Current Month') }}</th>
                                    <th class="text-right py-2.5 px-3 w-36 border border-gray-400">{{ t('ক্রমপুঞ্জিত', 'Cumulative') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- 1. Purchase (Net) -->
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="text-center py-2 px-3 font-semibold text-gray-600 border border-gray-300">1</td>
                                    <td class="py-2 px-3 font-semibold text-gray-800 border border-gray-300">
                                        {{ t('ক্রয়', 'Purchase') }}
                                    </td>
                                    <td class="text-right py-2 px-3 font-bold text-rose-700 border border-gray-300">
                                        {{ formatCurrency(payment?.purchase?.period || 0) }}
                                    </td>
                                    <td class="text-right py-2 px-3 font-bold text-rose-800 border border-gray-300">
                                        {{ formatCurrency(payment?.purchase?.cumulative || 0) }}
                                    </td>
                                </tr>

                                <!-- 2. Supplier Payment (Due Paid) -->
                                <tr class="bg-gray-50/40 hover:bg-gray-50 transition">
                                    <td class="text-center py-2 px-3 font-semibold text-gray-600 border border-gray-300">2</td>
                                    <td class="py-2 px-3 font-semibold text-gray-800 border border-gray-300">
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ t('সরবরাহকারী পরিশোধ (বকেয়া/দেনা)', 'Supplier Payment (Due Paid)') }}</span>
                                            <span v-if="(payment?.supplier_payment?.period || 0) > 0"
                                                  class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-700">
                                                {{ t('পরিশোধ', 'Paid') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-right py-2 px-3 font-bold text-rose-700 border border-gray-300">
                                        {{ formatCurrency(payment?.supplier_payment?.period || 0) }}
                                    </td>
                                    <td class="text-right py-2 px-3 font-bold text-rose-800 border border-gray-300">
                                        {{ formatCurrency(payment?.supplier_payment?.cumulative || 0) }}
                                    </td>
                                </tr>

                                <!-- 3. Fixed Asset Purchase (Header & Breakdown) -->
                                <tr class="bg-gray-100 font-bold border border-gray-400">
                                    <td class="text-center py-2 px-3 font-bold text-gray-800 border border-gray-300">3</td>
                                    <td colspan="3" class="py-2 px-3 font-bold text-gray-800 border border-gray-300">
                                        <div class="flex items-center gap-2">
                                            <span>{{ t('স্থায়ী সম্পদ ক্রয়', 'Fixed Asset Purchase') }}</span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-700">
                                                {{ t('সম্পদ', 'Asset') }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Fixed Asset Items Breakdown -->
                                <template v-if="payment?.fixed_assets?.items && payment.fixed_assets.items.length > 0">
                                    <tr v-for="(item, idx) in payment.fixed_assets.items" :key="idx" class="hover:bg-rose-50/30 transition text-xs">
                                        <td class="border border-gray-300"></td>
                                        <td class="py-1.5 px-3 pl-8 text-gray-700 border border-gray-300">
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-500 mr-2"></span>
                                            {{ item.name }}
                                        </td>
                                        <td class="text-right py-1.5 px-3 text-rose-600 font-medium border border-gray-300">
                                            {{ formatCurrency(item.period || 0) }}
                                        </td>
                                        <td class="text-right py-1.5 px-3 text-rose-700 font-medium border border-gray-300">
                                            {{ formatCurrency(item.cumulative || 0) }}
                                        </td>
                                    </tr>
                                </template>
                                <template v-else>
                                    <tr class="text-xs text-gray-400 italic">
                                        <td class="border border-gray-300"></td>
                                        <td class="py-1.5 px-3 pl-8 border border-gray-300">{{ t('এই পিরিয়ডে স্থায়ী সম্পদ কেনা হয়নি', 'No fixed asset purchase in this period') }}</td>
                                        <td class="text-right py-1.5 px-3 border border-gray-300">{{ formatCurrency(0) }}</td>
                                        <td class="text-right py-1.5 px-3 border border-gray-300">{{ formatCurrency(0) }}</td>
                                    </tr>
                                </template>
                                <!-- Total Fixed Assets -->
                                <tr class="bg-rose-50/50 font-semibold text-xs sm:text-sm">
                                    <td class="border border-gray-300"></td>
                                    <td class="py-2 px-3 pl-8 text-gray-800 font-bold border border-gray-300">
                                        {{ t('মোট স্থায়ী সম্পদ ক্রয়', 'Total Fixed Asset Purchase') }}
                                    </td>
                                    <td class="text-right py-2 px-3 text-rose-700 font-bold border border-gray-300">
                                        {{ formatCurrency(payment?.fixed_assets?.total?.period || 0) }}
                                    </td>
                                    <td class="text-right py-2 px-3 text-rose-800 font-bold border border-gray-300">
                                        {{ formatCurrency(payment?.fixed_assets?.total?.cumulative || 0) }}
                                    </td>
                                </tr>

                                <!-- 4. Fund Refund / Fund Out (Header & Items) -->
                                <tr class="bg-gray-100 font-bold border border-gray-400">
                                    <td class="text-center py-2 px-3 font-bold text-gray-800 border border-gray-300">4</td>
                                    <td colspan="3" class="py-2 px-3 font-bold text-gray-800 border border-gray-300">
                                        {{ t('ফান্ড রিফান্ড / ফান্ড আউট', 'Fund Refund / Fund Out') }}
                                    </td>
                                </tr>
                                <template v-if="payment?.fund_refund?.items && payment.fund_refund.items.length > 0">
                                    <tr v-for="(item, idx) in payment.fund_refund.items" :key="idx" class="hover:bg-rose-50/30 transition text-xs">
                                        <td class="border border-gray-300"></td>
                                        <td class="py-1.5 px-3 pl-8 text-gray-700 border border-gray-300">
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-500 mr-2"></span>
                                            {{ item.name }}
                                        </td>
                                        <td class="text-right py-1.5 px-3 text-rose-600 font-medium border border-gray-300">
                                            {{ formatCurrency(item.period || 0) }}
                                        </td>
                                        <td class="text-right py-1.5 px-3 text-rose-700 font-medium border border-gray-300">
                                            {{ formatCurrency(item.cumulative || 0) }}
                                        </td>
                                    </tr>
                                </template>
                                <tr class="bg-rose-50/50 font-semibold text-xs sm:text-sm">
                                    <td class="border border-gray-300"></td>
                                    <td class="py-2 px-3 pl-8 text-gray-800 font-bold border border-gray-300">
                                        {{ t('মোট ফান্ড রিফান্ড / আউট', 'Total Fund Refund / Out') }}
                                    </td>
                                    <td class="text-right py-2 px-3 text-rose-700 font-bold border border-gray-300">
                                        {{ formatCurrency(payment?.fund_refund?.total?.period || 0) }}
                                    </td>
                                    <td class="text-right py-2 px-3 text-rose-800 font-bold border border-gray-300">
                                        {{ formatCurrency(payment?.fund_refund?.total?.cumulative || 0) }}
                                    </td>
                                </tr>

                                <!-- 5. Expenses (Header & Categories) -->
                                <tr class="bg-gray-100 font-bold border border-gray-400">
                                    <td class="text-center py-2 px-3 font-bold text-gray-800 border border-gray-300">5</td>
                                    <td colspan="3" class="py-2 px-3 font-bold text-gray-800 border border-gray-300">
                                        {{ t('খরচসমূহ', 'Expenses') }}
                                    </td>
                                </tr>
                                <template v-if="payment?.expenses?.categories && payment.expenses.categories.length > 0">
                                    <tr v-for="(cat, idx) in payment.expenses.categories" :key="idx" class="hover:bg-rose-50/30 transition text-xs">
                                        <td class="border border-gray-300"></td>
                                        <td class="py-1.5 px-3 pl-8 text-gray-700 border border-gray-300">
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-500 mr-2"></span>
                                            {{ cat.category }}
                                        </td>
                                        <td class="text-right py-1.5 px-3 text-rose-600 font-medium border border-gray-300">
                                            {{ formatCurrency(cat.period || 0) }}
                                        </td>
                                        <td class="text-right py-1.5 px-3 text-rose-700 font-medium border border-gray-300">
                                            {{ formatCurrency(cat.cumulative || 0) }}
                                        </td>
                                    </tr>
                                </template>
                                <template v-else>
                                    <tr class="text-xs text-gray-400 italic">
                                        <td class="border border-gray-300"></td>
                                        <td class="py-1.5 px-3 pl-8 border border-gray-300">{{ t('এই পিরিয়ডে কোনো খরচ নেই', 'No expenses in this period') }}</td>
                                        <td class="text-right py-1.5 px-3 border border-gray-300">{{ formatCurrency(0) }}</td>
                                        <td class="text-right py-1.5 px-3 border border-gray-300">{{ formatCurrency(0) }}</td>
                                    </tr>
                                </template>
                                <!-- Total Expenses -->
                                <tr class="bg-rose-50/50 font-semibold text-xs sm:text-sm">
                                    <td class="border border-gray-300"></td>
                                    <td class="py-2 px-3 pl-8 text-gray-800 font-bold border border-gray-300">
                                        {{ t('মোট খরচ', 'Total Expenses') }}
                                    </td>
                                    <td class="text-right py-2 px-3 text-rose-700 font-bold border border-gray-300">
                                        {{ formatCurrency(payment?.expenses?.total?.period || 0) }}
                                    </td>
                                    <td class="text-right py-2 px-3 text-rose-800 font-bold border border-gray-300">
                                        {{ formatCurrency(payment?.expenses?.total?.cumulative || 0) }}
                                    </td>
                                </tr>

                                <!-- 6. Closing Cash at Bank -->
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="text-center py-2 px-3 font-semibold text-gray-600 border border-gray-300">6</td>
                                    <td class="py-2 px-3 font-semibold text-gray-800 border border-gray-300">
                                        {{ t('ব্যাংকে সমাপনী নগদ', 'Closing Cash at Bank') }}
                                    </td>
                                    <td class="text-right py-2 px-3 font-bold text-rose-700 border border-gray-300">
                                        {{ formatCurrency(payment?.closing_cash_at_bank?.period || 0) }}
                                    </td>
                                    <td class="text-right py-2 px-3 font-bold text-rose-800 border border-gray-300">
                                        {{ formatCurrency(payment?.closing_cash_at_bank?.cumulative || 0) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Total Payment Row (Pinned to bottom for baseline match) -->
                    <div class="border-t-2 border-gray-400 bg-rose-50 px-4 py-3 mt-auto">
                        <div class="grid grid-cols-12 items-center text-sm sm:text-base font-bold">
                            <span class="col-span-6 text-gray-900 uppercase tracking-wide">
                                {{ t('মোট পেমেন্ট', 'Total Payment') }}
                            </span>
                            <span class="col-span-3 text-right text-rose-700 font-extrabold pr-3">
                                {{ formatCurrency(payment?.total?.period || 0) }}
                            </span>
                            <span class="col-span-3 text-right text-rose-800 font-black">
                                {{ formatCurrency(payment?.total?.cumulative || 0) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatCurrency } from '@/Utils';
import { useLanguage } from '@/composables/useLanguage';

const { t, isBangla } = useLanguage();

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            start_date: null,
            end_date: null,
            bank_account_id: null,
        })
    },
    bankAccounts: {
        type: Array,
        default: () => [],
    },
    selectedBankAccountId: {
        type: [Number, String, null],
        default: '',
    },
    receipt: {
        type: Object,
        default: () => ({})
    },
    payment: {
        type: Object,
        default: () => ({})
    }
});

const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const selectedBankAccountId = ref(props.selectedBankAccountId ? String(props.selectedBankAccountId) : '');
const isDownloading = ref(false);
const activePreset = ref('');

const selectedAccountName = computed(() => {
    if (!selectedBankAccountId.value) return '';
    const acc = props.bankAccounts.find(a => String(a.id) === String(selectedBankAccountId.value));
    return acc ? `${acc.account_name} (${acc.bank_name})` : '';
});

const formatDisplayDate = (dStr) => {
    if (!dStr) return '';
    try {
        const d = new Date(dStr);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch {
        return dStr;
    }
};

const formattedDateRange = computed(() => {
    if (!startDate.value || !endDate.value) return '';
    return `${formatDisplayDate(startDate.value)} to ${formatDisplayDate(endDate.value)}`;
});

const handleFilterChange = () => {
    activePreset.value = '';
    applyFilter();
};

const applyFilter = () => {
    router.get(route('admin.reports.receipt-payment'), {
        start_date: startDate.value,
        end_date: endDate.value,
        bank_account_id: selectedBankAccountId.value || '',
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['receipt', 'payment', 'filters', 'selectedBankAccountId']
    });
};

const applyPreset = (preset) => {
    activePreset.value = preset;
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');

    if (preset === 'this_month') {
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        startDate.value = `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`;
        endDate.value = `${end.getFullYear()}-${pad(end.getMonth() + 1)}-${pad(end.getDate())}`;
    } else if (preset === 'today') {
        const todayStr = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
        startDate.value = todayStr;
        endDate.value = todayStr;
    } else if (preset === 'last_month') {
        const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const end = new Date(now.getFullYear(), now.getMonth(), 0);
        startDate.value = `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`;
        endDate.value = `${end.getFullYear()}-${pad(end.getMonth() + 1)}-${pad(end.getDate())}`;
    } else if (preset === 'this_year') {
        startDate.value = `${now.getFullYear()}-01-01`;
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        endDate.value = `${end.getFullYear()}-${pad(end.getMonth() + 1)}-${pad(end.getDate())}`;
    }

    applyFilter();
};

const downloadPDF = () => {
    isDownloading.value = true;
    const params = new URLSearchParams({
        start_date: startDate.value,
        end_date: endDate.value,
        bank_account_id: selectedBankAccountId.value || '',
        locale: isBangla.value ? 'bn' : 'en',
    }).toString();

    window.location.href = `${route('admin.reports.receipt-payment.pdf')}?${params}`;
    setTimeout(() => {
        isDownloading.value = false;
    }, 2500);
};

const printReport = () => {
    window.print();
};
</script>
