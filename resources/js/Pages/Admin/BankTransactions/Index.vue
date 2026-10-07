<template>
    <Head :title="t('ব্যাংক লেনদেন', 'Bank Transactions')" />
    <AdminLayout :title="t('ব্যাংক লেনদেন', 'Bank Transactions')">


        <div class="py-4 sm:py-8">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
                <!-- Summary Cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2.5 sm:gap-4">
                    <div class="col-span-2 md:col-span-1 bg-gradient-to-br from-green-500 to-green-600 overflow-hidden shadow-sm rounded-2xl p-4 sm:p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-xs sm:text-sm font-medium">{{ t('পাওয়া যোগ্য ব্যালেন্স', 'Available Balance') }}</p>
                                <p class="text-2xl sm:text-3xl font-bold mt-1 sm:mt-2 truncate">{{ formatCurrency(summary.available_balance) }}</p>
                            </div>
                            <div class="bg-white bg-opacity-20 rounded-xl p-2.5 sm:p-3 shrink-0">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-1 bg-gradient-to-br from-blue-500 to-blue-600 overflow-hidden shadow-sm rounded-2xl p-3.5 sm:p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-[11px] sm:text-sm font-medium truncate">{{ t('মোট জমা', 'Total In') }}</p>
                                <p class="text-lg sm:text-3xl font-bold mt-1 sm:mt-2 truncate">{{ formatCurrency(summary.total_in) }}</p>
                            </div>
                            <div class="bg-white bg-opacity-20 rounded-xl p-2 sm:p-3 shrink-0">
                                <svg class="w-5 h-5 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-1 bg-gradient-to-br from-red-500 to-red-600 overflow-hidden shadow-sm rounded-2xl p-3.5 sm:p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-100 text-[11px] sm:text-sm font-medium truncate">{{ t('মোট উত্তোলন', 'Total Out') }}</p>
                                <p class="text-lg sm:text-3xl font-bold mt-1 sm:mt-2 truncate">{{ formatCurrency(summary.total_out) }}</p>
                            </div>
                            <div class="bg-white bg-opacity-20 rounded-xl p-2 sm:p-3 shrink-0">
                                <svg class="w-5 h-5 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters & Actions -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl p-3.5 sm:p-6 border border-gray-100 dark:border-gray-700/60">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-4 mb-3 sm:mb-4">
                        <!-- Search -->
                        <div class="sm:col-span-2">
                            <input
                                v-model="localFilters.search"
                                @input="debouncedSearch"
                                type="text"
                                :placeholder="t('লেনদেন অনুসন্ধান করুন...', 'Search transactions...')"
                                class="w-full px-3.5 py-2 text-xs sm:text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Transaction Type Filter -->
                        <select
                            v-model="localFilters.transaction_type"
                            @change="applyFilters"
                            class="px-3 py-2 text-xs sm:text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">{{ t('সকল ধরন', 'All Types') }}</option>
                            <option value="in">{{ t('জমা (In)', 'In') }}</option>
                            <option value="out">{{ t('উত্তোলন (Out)', 'Out') }}</option>
                        </select>

                        <!-- Bank Account Filter -->
                        <select
                            v-model="localFilters.bank_account_id"
                            @change="applyFilters"
                            class="px-3 py-2 text-xs sm:text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">{{ t('সকল অ্যাকাউন্ট', 'All Accounts') }}</option>
                            <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                {{ account.account_name }}
                            </option>
                        </select>

                        <!-- Action Button -->
                        <a :href="route('admin.bank-transactions.create')"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl text-center flex items-center justify-center text-xs sm:text-sm shadow-sm transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ t('নতুন লেনদেন', 'New Transaction') }}
                        </a>
                    </div>

                    <!-- Date Range Filters -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 sm:gap-4">
                        <div>
                            <label class="block text-[11px] sm:text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('শুরুর তারিখ', 'From Date') }}</label>
                            <input
                                v-model="localFilters.date_from"
                                @change="applyFilters"
                                type="date"
                                class="w-full px-2.5 py-1.5 sm:py-2 text-xs sm:text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-[11px] sm:text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('শেষ তারিখ', 'To Date') }}</label>
                            <input
                                v-model="localFilters.date_to"
                                @change="applyFilters"
                                type="date"
                                class="w-full px-2.5 py-1.5 sm:py-2 text-xs sm:text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="col-span-2 sm:col-span-1 flex items-end">
                            <button
                                @click="clearFilters"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 text-xs sm:text-sm">
                                {{ t('ফিল্টার মুছুন', 'Clear Filters') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Transactions List & Table -->
                <!-- Transactions List & Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700/60">
                    <!-- Mobile Cards (< md) -->
                    <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
                        <div
                            v-for="transaction in bankTransactions.data"
                            :key="transaction.id"
                            class="p-3.5 space-y-2.5"
                        >
                            <div class="flex items-center justify-between">
                                <div class="font-bold text-sm text-gray-900 dark:text-white">
                                    {{ transaction.bank_account?.account_name }}
                                </div>
                                <span class="px-2.5 py-0.5 inline-flex text-[10px] font-bold rounded-full"
                                    :class="transaction.transaction_type === 'in' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'">
                                    {{ formatTransactionType(transaction.transaction_type) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-400">{{ formatDate(transaction.date) }}</span>
                                <div class="text-sm font-black" :class="getAmountClass(transaction.transaction_type)">
                                    {{ formatCurrency(transaction.amount) }}
                                </div>
                            </div>

                            <div v-if="transaction.description" class="text-xs text-gray-500 dark:text-gray-400">
                                {{ transaction.description }}
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-100 dark:border-gray-700/50">
                                <a :href="route('admin.bank-transactions.edit', transaction.id)"
                                    class="p-1.5 text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <button @click="destroy(transaction.id)"
                                    class="p-1.5 text-red-600 dark:text-red-400 hover:text-red-900 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-if="bankTransactions.data.length === 0" class="p-8 text-center text-gray-400 text-xs font-medium">
                            {{ t('কোনো লেনদেন পাওয়া যায়নি', 'No transactions found') }}
                        </div>
                    </div>

                    <!-- Desktop Table (>= md) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th scope="col" @click="sortBy('date')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800">
                                        <div class="flex items-center gap-1">
                                            {{ t('তারিখ', 'Date') }}
                                            <svg v-if="localFilters.sort_field === 'date'" class="w-4 h-4" :class="{'rotate-180': localFilters.sort_order === 'desc'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" @click="sortBy('bank_account_id')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800">
                                        <div class="flex items-center gap-1">
                                            {{ t('ব্যাংক অ্যাকাউন্ট', 'Bank Account') }}
                                            <svg v-if="localFilters.sort_field === 'bank_account_id'" class="w-4 h-4" :class="{'rotate-180': localFilters.sort_order === 'desc'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" @click="sortBy('transaction_type')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800">
                                        <div class="flex items-center gap-1">
                                            {{ t('ধরন', 'Type') }}
                                            <svg v-if="localFilters.sort_field === 'transaction_type'" class="w-4 h-4" :class="{'rotate-180': localFilters.sort_order === 'desc'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" @click="sortBy('amount')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800">
                                        <div class="flex items-center gap-1">
                                            {{ t('পরিমাণ', 'Amount') }}
                                            <svg v-if="localFilters.sort_field === 'amount'" class="w-4 h-4" :class="{'rotate-180': localFilters.sort_order === 'desc'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                        {{ t('বিবরণ', 'Description') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                        {{ t('অ্যাকশন', 'Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="bankTransactions.data.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ t('কোনো লেনদেন পাওয়া যায়নি', 'No transactions found') }}
                                    </td>
                                </tr>
                                <tr v-for="transaction in bankTransactions.data" :key="transaction.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-200">
                                            {{ formatDate(transaction.date) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-200">
                                            {{ transaction.bank_account.account_name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            :class="transaction.transaction_type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                            {{ formatTransactionType(transaction.transaction_type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold" :class="getAmountClass(transaction.transaction_type)">
                                            {{ formatCurrency(transaction.amount) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500 dark:text-gray-300 max-w-xs truncate">
                                            {{ transaction.description || '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a :href="route('admin.bank-transactions.edit', transaction.id)"
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <button @click="destroy(transaction.id)"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="bankTransactions.links && bankTransactions.links.length > 3" class="px-4 sm:px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                        <Pagination :links="bankTransactions.links" />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Head, router } from '@inertiajs/vue3';
import { format } from 'date-fns'
import { useLanguage } from '@/composables/useLanguage'

export default {
    components: {
        AdminLayout,
        Head,
        Pagination,
    },
    setup() {
        const { t } = useLanguage()
        return { t }
    },
    props: {
        bankTransactions: Object,
        summary: Object,
        bankAccounts: Array,
        filters: Object,
    },
    data() {
        return {
            localFilters: {
                search: this.filters.search || '',
                transaction_type: this.filters.transaction_type || '',
                bank_account_id: this.filters.bank_account_id || '',
                date_from: this.filters.date_from || '',
                date_to: this.filters.date_to || '',
                sort_field: this.filters.sort_field || 'date',
                sort_order: this.filters.sort_order || 'desc',
                per_page: this.filters.per_page || 15,
            },
            searchTimeout: null,
        }
    },
    methods: {
        formatCurrency(amount) {
            const number = new Intl.NumberFormat('en-BD', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount)
            return `৳ ${number}`
        },
        formatDate(date) {
            return format(new Date(date), 'MMM dd, yyyy')
        },
        formatTransactionType(type) {
            return type === 'in' ? this.t('জমা (Money In)', 'Money In') : this.t('উত্তোলন (Money Out)', 'Money Out')
        },
        getAmountClass(type) {
            return type === 'in' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
        },
        debouncedSearch() {
            clearTimeout(this.searchTimeout)
            this.searchTimeout = setTimeout(() => {
                this.applyFilters()
            }, 500)
        },
        applyFilters() {
            router.get(route('admin.bank-transactions.index'), this.localFilters, {
                preserveState: true,
                preserveScroll: true,
            })
        },
        sortBy(field) {
            if (this.localFilters.sort_field === field) {
                this.localFilters.sort_order = this.localFilters.sort_order === 'asc' ? 'desc' : 'asc'
            } else {
                this.localFilters.sort_field = field
                this.localFilters.sort_order = 'asc'
            }
            this.applyFilters()
        },
        clearFilters() {
            this.localFilters = {
                search: '',
                transaction_type: '',
                bank_account_id: '',
                date_from: '',
                date_to: '',
                sort_field: 'date',
                sort_order: 'desc',
                per_page: 15,
            }
            this.applyFilters()
        },
        destroy(id) {
            const confirmMsg = this.t('আপনি কি নিশ্চিত? এই লেনদেন মুছলে ব্যাংক ব্যালেন্সে প্রভাব পড়বে।', 'Are you sure you want to delete this transaction? This will affect bank balances.')
            if (confirm(confirmMsg)) {
                router.delete(route('admin.bank-transactions.destroy', id), {
                    preserveScroll: true,
                })
            }
        },
    },
}
</script>
