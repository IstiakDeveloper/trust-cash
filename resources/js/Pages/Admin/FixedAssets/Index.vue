<template>
    <Head :title="t('স্থায়ী সম্পদ', 'Fixed Assets')" />
    <AdminLayout :title="t('স্থায়ী সম্পদ ব্যবস্থাপনা', 'Fixed Asset Management')">
        <div class="container mx-auto px-4 py-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2.5">
                        <span class="w-3 h-3 rounded-full bg-indigo-600"></span>
                        {{ t('স্থায়ী সম্পদ ও সামগ্রী', 'Fixed Assets & Components') }}
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ t('দোকানের প্রধান সম্পদ (যেমন: কম্পিউটার, ডেকোরেশন) এবং তার অধীনে ক্রয়কৃত আইটেম (মনিটর, মাউস ইত্যাদি) ব্যবস্থাপনা', 'Manage parent assets (e.g., Computer) and subsequent component purchases (Monitor, Mouse)') }}
                    </p>
                </div>
                <button
                    @click="openCreateAssetModal"
                    class="w-full sm:w-auto px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold flex items-center justify-center gap-2 shadow-sm transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('নতুন সম্পদ হেড যোগ করুন', 'Add New Asset') }}
                </button>
            </div>

            <!-- Summary KPI Cards: 2 cols on mobile -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4 mb-6">
                <!-- Total Assets (Heads) -->
                <div class="bg-white dark:bg-gray-800 p-3.5 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] sm:text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ t('মোট সম্পদ খাত', 'Asset Groups') }}
                        </p>
                        <h3 class="text-lg sm:text-2xl font-black text-gray-900 dark:text-white mt-0.5 sm:mt-1">
                            {{ formatNumber(summary.total_assets_count) }}
                        </h3>
                        <p class="text-[10px] sm:text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 truncate">
                            {{ t('যেমন: কম্পিউটার', 'e.g. Computer') }}
                        </p>
                    </div>
                    <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>

                <!-- Total Component Items -->
                <div class="bg-white dark:bg-gray-800 p-3.5 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] sm:text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ t('মোট আইটেম', 'Total Parts') }}
                        </p>
                        <h3 class="text-lg sm:text-2xl font-black text-blue-600 dark:text-blue-400 mt-0.5 sm:mt-1">
                            {{ formatNumber(summary.total_items_count) }}
                        </h3>
                        <p class="text-[10px] sm:text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 truncate">
                            {{ t('পার্টস ও সামগ্রী', 'All parts') }}
                        </p>
                    </div>
                    <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>

                <!-- Total Procurement Cost -->
                <div class="bg-white dark:bg-gray-800 p-3.5 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] sm:text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ t('ক্রয়মূল্য', 'Purchase Cost') }}
                        </p>
                        <h3 class="text-base sm:text-2xl font-black text-slate-800 dark:text-slate-100 mt-0.5 sm:mt-1 truncate">
                            {{ formatCurrency(summary.total_purchase_value) }}
                        </h3>
                        <p class="text-[10px] sm:text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 truncate">
                            {{ t('সকল ক্রয়ের যোগফল', 'Sum of purchases') }}
                        </p>
                    </div>
                    <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl bg-purple-50 dark:bg-purple-950/50 flex items-center justify-center text-purple-600 dark:text-purple-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Active Book Value in Balance Sheet -->
                <div class="bg-white dark:bg-gray-800 p-3.5 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] sm:text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ t('বর্তমান মান', 'Book Value') }}
                        </p>
                        <h3 class="text-base sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5 sm:mt-1 truncate">
                            {{ formatCurrency(summary.active_value) }}
                        </h3>
                        <p class="text-[10px] sm:text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 truncate">
                            {{ t('ব্যালেন্স শীটের মান', 'In Balance Sheet') }}
                        </p>
                    </div>
                    <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div class="lg:col-span-2">
                        <input
                            type="text"
                            v-model="filters.search"
                            @input="debouncedSearch"
                            :placeholder="t('সম্পদের নাম, কোড বা আইটেম (যেমন: মাউস) দিয়ে খুঁজুন...', 'Search asset, code, or item name (e.g. mouse)...')"
                            class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <select
                            v-model="filters.bank_account_id"
                            @change="applyFilter"
                            class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">{{ t('সকল ব্যাংক / ক্যাশ', 'All Accounts') }}</option>
                            <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
                                {{ acc.account_name }} - {{ acc.bank_name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex gap-2 lg:col-span-2">
                        <input
                            type="date"
                            v-model="filters.from_date"
                            @change="applyFilter"
                            title="From Date"
                            class="w-1/2 text-xs rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <input
                            type="date"
                            v-model="filters.to_date"
                            @change="applyFilter"
                            title="To Date"
                            class="w-1/2 text-xs rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                </div>
            </div>

            <!-- Assets List (with Expandable Items) -->
            <div class="space-y-4">
                <div
                    v-for="asset in assets.data"
                    :key="asset.id"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden transition"
                >
                    <!-- Asset Parent Row -->
                    <div class="p-4 sm:p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50 dark:bg-slate-800/40">
                        <div class="flex items-start sm:items-center gap-3">
                            <button
                                @click="toggleExpand(asset.id)"
                                class="mt-0.5 sm:mt-0 p-1 rounded-lg text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-white dark:hover:bg-gray-700 transition"
                                :title="isExpanded(asset.id) ? t('সংকুচিত করুন', 'Collapse') : t('আইটেম তালিকা দেখুন', 'Expand items')"
                            >
                                <svg
                                    class="w-5 h-5 transform transition-transform duration-200"
                                    :class="{ 'rotate-90': isExpanded(asset.id) }"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white">
                                        {{ asset.name }}
                                    </h3>
                                    <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
                                        {{ asset.asset_code }}
                                    </span>
                                    <span v-if="asset.branch" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                        {{ asset.branch.name }}
                                    </span>
                                </div>
                                <p v-if="asset.description" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ asset.description }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
                            <div class="text-left sm:text-right">
                                <span class="text-[11px] uppercase font-bold text-gray-400 tracking-wider block">
                                    {{ t('মোট মূল্য (আইটেম: ', 'Total Value (Items: ') }}{{ formatNumber(asset.items?.length || 0) }})
                                </span>
                                <span class="text-lg sm:text-xl font-black font-mono text-emerald-600 dark:text-emerald-400">
                                    {{ formatCurrency(getAssetTotalValue(asset)) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    @click="openAddItemModal(asset)"
                                    class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900 rounded-xl text-xs font-bold flex items-center gap-1.5 transition"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    {{ t('আইটেম যোগ করুন', 'Add Item') }}
                                </button>
                                <button
                                    @click="openEditAssetModal(asset)"
                                    class="p-1.5 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-white dark:hover:bg-gray-700 rounded-lg transition"
                                    :title="t('সম্পদ হেড সম্পাদনা', 'Edit Asset')"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    @click="confirmDeleteAsset(asset)"
                                    class="p-1.5 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-white dark:hover:bg-gray-700 rounded-lg transition"
                                    :title="t('সম্পদ হেড মুছুন', 'Delete Asset')"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Items Sub-Table (Expanded by default if has items) -->
                    <div v-show="isExpanded(asset.id)" class="border-t border-gray-100 dark:border-gray-700">
                        <div v-if="asset.items && asset.items.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-gray-50/80 dark:bg-gray-750 text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                                        <th class="px-5 py-2.5">{{ t('আইটেমের নাম', 'Item Name') }}</th>
                                        <th class="px-5 py-2.5">{{ t('ক্রয় তারিখ', 'Purchase Date') }}</th>
                                        <th class="px-5 py-2.5 text-right">{{ t('ক্রয়মূল্য', 'Purchase Price') }}</th>
                                        <th class="px-5 py-2.5 text-right">{{ t('বর্তমান বুক ভ্যালু', 'Current Value') }}</th>
                                        <th class="px-5 py-2.5">{{ t('পেমেন্ট ব্যাংক / উৎস', 'Payment Account') }}</th>
                                        <th class="px-5 py-2.5 text-center">{{ t('স্ট্যাটাস', 'Status') }}</th>
                                        <th class="px-5 py-2.5 text-right">{{ t('অ্যাকশন', 'Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                    <tr
                                        v-for="item in asset.items"
                                        :key="item.id"
                                        class="hover:bg-slate-50/60 dark:hover:bg-slate-750 transition"
                                    >
                                        <td class="px-5 py-3 font-bold text-gray-800 dark:text-gray-200">
                                            <div class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                                <span>{{ item.item_name }}</span>
                                            </div>
                                            <p v-if="item.description" class="text-[11px] text-gray-400 dark:text-gray-500 font-normal ml-3.5 mt-0.5">
                                                {{ item.description }}
                                            </p>
                                        </td>
                                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                            {{ formatDate(item.purchase_date) }}
                                        </td>
                                        <td class="px-5 py-3 text-right font-bold text-gray-800 dark:text-gray-200 font-mono">
                                            {{ formatCurrency(item.purchase_price) }}
                                        </td>
                                        <td class="px-5 py-3 text-right font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">
                                            {{ formatCurrency(item.current_value) }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <span v-if="item.bank_account" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 font-medium">
                                                🏦 {{ item.bank_account.account_name }} ({{ item.bank_account.bank_name }})
                                            </span>
                                            <span v-else class="text-gray-400 dark:text-gray-500 italic">
                                                {{ t('পরিশোধ ছাড়া / পূর্বের আইটেম', 'No Bank Deduction (Opening)') }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            <span
                                                v-if="item.status === 'active'"
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                            >
                                                {{ t('সক্রিয়', 'Active') }}
                                            </span>
                                            <span
                                                v-else-if="item.status === 'disposed'"
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                            >
                                                {{ t('অপসারিত', 'Disposed') }}
                                            </span>
                                            <span
                                                v-else
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                                            >
                                                {{ t('ক্ষতিগ্রস্ত', 'Damaged') }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1">
                                                <button
                                                    @click="openEditItemModal(asset, item)"
                                                    class="p-1 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition"
                                                    :title="t('আইটেম সম্পাদনা', 'Edit Item')"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    @click="confirmDeleteItem(item)"
                                                    class="p-1 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition"
                                                    :title="t('আইটেম মুছুন', 'Delete Item')"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="p-6 text-center text-gray-400 dark:text-gray-500">
                            <p class="text-xs">{{ t('এই সম্পদে এখনও কোনো ক্রয় আইটেম যুক্ত করা হয়নি।', 'No purchase items added under this asset yet.') }}</p>
                            <button
                                @click="openAddItemModal(asset)"
                                class="mt-2 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline"
                            >
                                {{ t('+ এখনই প্রথম আইটেম যোগ করুন', '+ Add first purchase item now') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="assets.data.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center text-gray-400 dark:text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <p class="font-medium text-base">{{ t('কোনো স্থায়ী সম্পদ পাওয়া যায়নি', 'No fixed assets found') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ t('যেমন: কম্পিউটার, দোকান ডেকোরেশন ইত্যাদি যুক্ত করতে উপরের বাটনে ক্লিক করুন', 'Click the button above to add assets like Computer, Furniture, etc.') }}</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="assets.links && assets.links.length > 3" class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-3 text-sm">
                <span class="text-gray-500 dark:text-gray-400 text-xs">
                    {{ t(`মোট ${assets.total} টির মধ্যে ${assets.from || 0} থেকে ${assets.to || 0} দেখাচ্ছে`, `Showing ${assets.from || 0} to ${assets.to || 0} of ${assets.total} entries`) }}
                </span>
                <div class="flex gap-1">
                    <Link
                        v-for="(link, i) in assets.links"
                        :key="i"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                        :class="[
                            link.active
                                ? 'bg-indigo-600 text-white'
                                : link.url
                                    ? 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                    : 'text-gray-400 dark:text-gray-600 pointer-events-none'
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- Create/Edit Asset Head Modal -->
        <Modal :show="showAssetModal" @close="showAssetModal = false">
            <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl">
                <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                        {{ isEditingAsset ? t('স্থায়ী সম্পদ হেড সম্পাদনা', 'Edit Asset Group') : t('নতুন স্থায়ী সম্পদ হেড যোগ', 'Add New Asset Group') }}
                    </h3>
                    <button @click="showAssetModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitAssetForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ t('সম্পদের মূল নাম (Asset Name) *', 'Asset Name *') }}
                            </label>
                            <input
                                type="text"
                                v-model="assetForm.name"
                                required
                                :placeholder="t('যেমন: কম্পিউটার, দোকান ডেকোরেশন, আসবাবপত্র, ফ্রিজ', 'e.g. Computer, Shop Decoration, Furniture, Refrigerator')"
                                class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="assetForm.errors.name" class="text-xs text-rose-500 mt-1">{{ assetForm.errors.name }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    {{ t('সম্পদ কোড (ঐচ্ছিক)', 'Asset Code (Optional)') }}
                                </label>
                                <input
                                    type="text"
                                    v-model="assetForm.asset_code"
                                    :placeholder="t('যেমন: FA-0001 (ফাঁকা রাখলে অটো হবে)', 'e.g. FA-0001 (leave empty for auto)')"
                                    class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                                />
                                <p v-if="assetForm.errors.asset_code" class="text-xs text-rose-500 mt-1">{{ assetForm.errors.asset_code }}</p>
                            </div>

                            <div v-if="branches && branches.length > 0">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    {{ t('শাখা / ব্রাঞ্চ (ঐচ্ছিক)', 'Branch (Optional)') }}
                                </label>
                                <select
                                    v-model="assetForm.branch_id"
                                    class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">{{ t('প্রধান দোকান / সকল শাখা', 'Main Store') }}</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">
                                        {{ b.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ t('বিবরণ / নোট (ঐচ্ছিক)', 'Description (Optional)') }}
                            </label>
                            <textarea
                                v-model="assetForm.description"
                                rows="2"
                                :placeholder="t('সম্পদের সাধারণ বিবরণ বা অবস্থান...', 'Notes or location...')"
                                class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                        </div>

                        <!-- Optional: Add initial item when creating new asset -->
                        <div v-if="!isEditingAsset" class="pt-3 border-t border-gray-100 dark:border-gray-700">
                            <label class="flex items-center gap-2 cursor-pointer mb-3">
                                <input
                                    type="checkbox"
                                    v-model="includeInitialItem"
                                    class="rounded text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="text-xs font-bold text-indigo-700 dark:text-indigo-400">
                                    {{ t('প্রথম আইটেমের ক্রয় তথ্য এখনই যোগ করুন (যেমন: মনিটর)', 'Add first item purchase details now (e.g. Monitor)') }}
                                </span>
                            </label>

                            <div v-if="includeInitialItem" class="bg-indigo-50/50 dark:bg-indigo-950/30 p-4 rounded-xl space-y-3 border border-indigo-100 dark:border-indigo-900/50">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        {{ t('আইটেমের নাম (Item Name) *', 'Item Name *') }}
                                    </label>
                                    <input
                                        type="text"
                                        v-model="assetForm.item_name"
                                        :placeholder="t('যেমন: মনিটর (Monitor), সিপিইউ ইত্যাদি', 'e.g. Monitor, CPU, Table')"
                                        class="w-full text-xs rounded-lg border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ t('ক্রয় তারিখ *', 'Purchase Date *') }}
                                        </label>
                                        <input
                                            type="date"
                                            v-model="assetForm.purchase_date"
                                            class="w-full text-xs rounded-lg border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ t('ক্রয়মূল্য (৳) *', 'Purchase Price (৳) *') }}
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            v-model="assetForm.purchase_price"
                                            placeholder="0.00"
                                            class="w-full text-xs rounded-lg border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        {{ t('পেমেন্ট ব্যাংক / ক্যাশ অ্যাকাউন্ট', 'Payment Account') }}
                                    </label>
                                    <select
                                        v-model="assetForm.bank_account_id"
                                        class="w-full text-xs rounded-lg border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    >
                                        <option value="">{{ t('-- কোনো ব্যাংক থেকে টাকা কাটা হবে না (পূর্বের সম্পদ) --', '-- No Bank Deduction (Opening) --') }}</option>
                                        <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
                                            {{ acc.account_name }} - {{ acc.bank_name }} (ব্যালেন্স: {{ formatCurrency(acc.current_balance) }})
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            @click="showAssetModal = false"
                            class="px-4 py-2 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            {{ t('বাতিল', 'Cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="assetForm.processing"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-sm transition disabled:opacity-50"
                        >
                            {{ isEditingAsset ? t('আপডেট করুন', 'Update Asset') : t('সংরক্ষণ করুন', 'Save Asset') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Add / Edit Item Modal -->
        <Modal :show="showItemModal" @close="showItemModal = false">
            <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl">
                <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block">
                            {{ targetAsset?.name }}
                        </span>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 mt-0.5">
                            {{ isEditingItem ? t('আইটেম সম্পাদনা', 'Edit Item') : t('নতুন আইটেম ক্রয় / সংযোজন', 'Add Purchase Item') }}
                        </h3>
                    </div>
                    <button @click="showItemModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitItemForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ t('আইটেমের নাম *', 'Item Name *') }}
                            </label>
                            <input
                                type="text"
                                v-model="itemForm.item_name"
                                required
                                :placeholder="t('যেমন: মাউস, কিবোর্ড, ইউপিএস, ডিসপ্লে র্যাক', 'e.g. Mouse, Keyboard, UPS, Rack')"
                                class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="itemForm.errors.item_name" class="text-xs text-rose-500 mt-1">{{ itemForm.errors.item_name }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    {{ t('ক্রয় তারিখ *', 'Purchase Date *') }}
                                </label>
                                <input
                                    type="date"
                                    v-model="itemForm.purchase_date"
                                    required
                                    class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="itemForm.errors.purchase_date" class="text-xs text-rose-500 mt-1">{{ itemForm.errors.purchase_date }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    {{ t('ক্রয়মূল্য (৳) *', 'Purchase Price (৳) *') }}
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model="itemForm.purchase_price"
                                    required
                                    min="0"
                                    @input="handleItemPriceInput"
                                    placeholder="0.00"
                                    class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                                />
                                <p v-if="itemForm.errors.purchase_price" class="text-xs text-rose-500 mt-1">{{ itemForm.errors.purchase_price }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    {{ t('বর্তমান বুক ভ্যালু (৳)', 'Current / Book Value (৳)') }}
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model="itemForm.current_value"
                                    min="0"
                                    placeholder="0.00"
                                    class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    {{ t('স্ট্যাটাস', 'Status') }}
                                </label>
                                <select
                                    v-model="itemForm.status"
                                    class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                >
                                    <option value="active">{{ t('সক্রিয় (Active)', 'Active') }}</option>
                                    <option value="disposed">{{ t('অপসারিত (Disposed)', 'Disposed') }}</option>
                                    <option value="damaged">{{ t('ক্ষতিগ্রস্ত (Damaged)', 'Damaged') }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ t('পেমেন্ট ব্যাংক / ক্যাশ অ্যাকাউন্ট', 'Payment Account') }}
                            </label>
                            <select
                                v-model="itemForm.bank_account_id"
                                class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">{{ t('-- কোনো ব্যাংক থেকে টাকা কাটা হবে না (পূর্বের সম্পদ) --', '-- No Bank Deduction (Opening) --') }}</option>
                                <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
                                    {{ acc.account_name }} - {{ acc.bank_name }} (ব্যালেন্স: {{ formatCurrency(acc.current_balance) }})
                                </option>
                            </select>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                {{ t('ব্যাংক সিলেক্ট করলে ক্রয়মূল্যের টাকা স্বয়ংক্রিয়ভাবে অ্যাকাউন্ট থেকে কমে যাবে এবং ট্রানজেকশনে যুক্ত হবে।', 'Selecting an account will automatically deduct the amount and record a bank transaction.') }}
                            </p>
                            <p v-if="itemForm.errors.bank_account_id" class="text-xs text-rose-500 mt-1">{{ itemForm.errors.bank_account_id }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                {{ t('বিবরণ / নোট (ঐচ্ছিক)', 'Description / Notes (Optional)') }}
                            </label>
                            <textarea
                                v-model="itemForm.description"
                                rows="2"
                                :placeholder="t('মডেল, ইনভয়েস নম্বর, ওয়ারেন্টি সংক্রান্ত তথ্য...', 'Model, invoice number, warranty...')"
                                class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            ></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            @click="showItemModal = false"
                            class="px-4 py-2 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            {{ t('বাতিল', 'Cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="itemForm.processing"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-sm transition disabled:opacity-50"
                        >
                            {{ isEditingItem ? t('আপডেট করুন', 'Update Item') : t('সংরক্ষণ করুন', 'Save Item') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl">
                <div class="flex items-center gap-3 text-rose-600 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ deleteTargetType === 'asset' ? t('স্থায়ী সম্পদ মুছে ফেলা নিশ্চিতকরণ', 'Confirm Asset Deletion') : t('আইটেম মুছে ফেলা নিশ্চিতকরণ', 'Confirm Item Deletion') }}
                    </h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                    <span v-if="deleteTargetType === 'asset'">
                        {{ t('আপনি কি নিশ্চিত যে আপনি এই সম্পদটি এবং এর অন্তর্ভুক্ত সকল আইটেম মুছে ফেলতে চান? সংশ্লিষ্ট ব্যাংক ট্রানজেকশনসমূহ স্বয়ংক্রিয়ভাবে রিস্টোর হবে।', 'Are you sure you want to delete this asset and all its items? Related bank deductions will be restored.') }}
                    </span>
                    <span v-else>
                        {{ t('আপনি কি নিশ্চিত যে আপনি এই আইটেমটি মুছে ফেলতে চান? সংশ্লিষ্ট ব্যাংক ট্রানজেকশন থাকলে তা স্বয়ংক্রিয়ভাবে রিস্টোর হবে।', 'Are you sure you want to delete this item? Related bank deduction will be restored.') }}
                    </span>
                </p>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showDeleteModal = false"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                    >
                        {{ t('না, রাখুন', 'No, Keep') }}
                    </button>
                    <button
                        @click="executeDelete"
                        class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-bold shadow-sm transition"
                    >
                        {{ t('হ্যাঁ, মুছে ফেলুন', 'Yes, Delete') }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'
import { useLanguage } from '@/composables/useLanguage'

const { t, formatCurrency, formatNumber } = useLanguage()

const props = defineProps({
    assets: Object,
    next_code: String,
    bankAccounts: Array,
    branches: Array,
    filters: Object,
    summary: {
        type: Object,
        default: () => ({
            total_assets_count: 0,
            total_items_count: 0,
            total_purchase_value: 0,
            active_value: 0,
        })
    }
})

const filters = reactive({
    search: props.filters?.search || '',
    bank_account_id: props.filters?.bank_account_id || '',
    branch_id: props.filters?.branch_id || '',
    from_date: props.filters?.from_date || '',
    to_date: props.filters?.to_date || '',
})

let searchTimeout = null
const debouncedSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        applyFilter()
    }, 400)
}

const applyFilter = () => {
    router.get(route('admin.fixed-assets.index'), filters, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Expand/Collapse state
const expandedAssets = ref(new Set())
// Expand all by default
if (props.assets?.data) {
    props.assets.data.forEach(a => expandedAssets.value.add(a.id))
}

const toggleExpand = (assetId) => {
    if (expandedAssets.value.has(assetId)) {
        expandedAssets.value.delete(assetId)
    } else {
        expandedAssets.value.add(assetId)
    }
}

const isExpanded = (assetId) => expandedAssets.value.has(assetId)

const getAssetTotalValue = (asset) => {
    if (!asset.items || asset.items.length === 0) return 0
    return asset.items.reduce((sum, item) => {
        return item.status === 'active' ? sum + Number(item.current_value || item.purchase_price || 0) : sum
    }, 0)
}

// Asset Modal State
const showAssetModal = ref(false)
const isEditingAsset = ref(false)
const currentAssetId = ref(null)
const includeInitialItem = ref(false)

const assetForm = useForm({
    name: '',
    asset_code: '',
    branch_id: '',
    status: 'active',
    description: '',
    // Initial item
    item_name: '',
    purchase_date: new Date().toISOString().substr(0, 10),
    purchase_price: '',
    current_value: '',
    bank_account_id: '',
})

const openCreateAssetModal = () => {
    isEditingAsset.value = false
    currentAssetId.value = null
    includeInitialItem.value = false
    assetForm.reset()
    assetForm.clearErrors()
    assetForm.asset_code = props.next_code || 'FA-0001'
    assetForm.purchase_date = new Date().toISOString().substr(0, 10)
    showAssetModal.value = true
}

const openEditAssetModal = (asset) => {
    isEditingAsset.value = true
    currentAssetId.value = asset.id
    includeInitialItem.value = false
    assetForm.clearErrors()
    assetForm.name = asset.name
    assetForm.asset_code = asset.asset_code || ''
    assetForm.branch_id = asset.branch_id || ''
    assetForm.status = asset.status || 'active'
    assetForm.description = asset.description || ''
    showAssetModal.value = true
}

const submitAssetForm = () => {
    if (isEditingAsset.value) {
        assetForm.put(route('admin.fixed-assets.update', currentAssetId.value), {
            onSuccess: () => { showAssetModal.value = false },
        })
    } else {
        if (!includeInitialItem.value) {
            assetForm.item_name = ''
            assetForm.purchase_price = ''
        }
        assetForm.post(route('admin.fixed-assets.store'), {
            onSuccess: () => { showAssetModal.value = false },
        })
    }
}

// Item Modal State
const showItemModal = ref(false)
const isEditingItem = ref(false)
const targetAsset = ref(null)
const currentItemId = ref(null)

const itemForm = useForm({
    item_name: '',
    purchase_date: new Date().toISOString().substr(0, 10),
    purchase_price: '',
    current_value: '',
    bank_account_id: '',
    status: 'active',
    description: '',
})

const handleItemPriceInput = () => {
    if (!isEditingItem.value || !itemForm.current_value) {
        itemForm.current_value = itemForm.purchase_price
    }
}

const openAddItemModal = (asset) => {
    targetAsset.value = asset
    isEditingItem.value = false
    currentItemId.value = null
    itemForm.reset()
    itemForm.clearErrors()
    itemForm.purchase_date = new Date().toISOString().substr(0, 10)
    itemForm.status = 'active'
    showItemModal.value = true
}

const openEditItemModal = (asset, item) => {
    targetAsset.value = asset
    isEditingItem.value = true
    currentItemId.value = item.id
    itemForm.clearErrors()
    itemForm.item_name = item.item_name
    itemForm.purchase_date = item.purchase_date ? item.purchase_date.substr(0, 10) : ''
    itemForm.purchase_price = item.purchase_price
    itemForm.current_value = item.current_value
    itemForm.bank_account_id = item.bank_account_id || ''
    itemForm.status = item.status || 'active'
    itemForm.description = item.description || ''
    showItemModal.value = true
}

const submitItemForm = () => {
    if (isEditingItem.value) {
        itemForm.put(route('admin.fixed-assets.items.update', currentItemId.value), {
            onSuccess: () => { showItemModal.value = false },
        })
    } else {
        itemForm.post(route('admin.fixed-assets.items.store', targetAsset.value.id), {
            onSuccess: () => {
                showItemModal.value = false
                expandedAssets.value.add(targetAsset.value.id)
            },
        })
    }
}

// Delete State
const showDeleteModal = ref(false)
const deleteTargetType = ref('asset') // 'asset' or 'item'
const targetItemToDelete = ref(null)
const targetAssetToDelete = ref(null)

const confirmDeleteAsset = (asset) => {
    deleteTargetType.value = 'asset'
    targetAssetToDelete.value = asset
    showDeleteModal.value = true
}

const confirmDeleteItem = (item) => {
    deleteTargetType.value = 'item'
    targetItemToDelete.value = item
    showDeleteModal.value = true
}

const executeDelete = () => {
    if (deleteTargetType.value === 'asset' && targetAssetToDelete.value) {
        router.delete(route('admin.fixed-assets.destroy', targetAssetToDelete.value.id), {
            onSuccess: () => { showDeleteModal.value = false },
        })
    } else if (deleteTargetType.value === 'item' && targetItemToDelete.value) {
        router.delete(route('admin.fixed-assets.items.destroy', targetItemToDelete.value.id), {
            onSuccess: () => { showDeleteModal.value = false },
        })
    }
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    const d = new Date(dateString)
    return d.toLocaleDateString('bn-BD', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}
</script>
