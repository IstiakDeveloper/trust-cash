<template>
    <AdminLayout :title="t('ফান্ড ম্যানেজমেন্ট', 'Fund Management')">
      <template #header>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div>
            <h2 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-gray-200 leading-tight">
              {{ t('ফান্ড ম্যানেজমেন্ট', 'Fund Management') }}
            </h2>
            <p class="mt-0.5 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
              {{ t('ফান্ড লেনদেন পরিচালনা ও নগদ প্রবাহ নজরদারি করুন', 'Manage your fund transactions and track cash flow') }}
            </p>
          </div>
          <Link
            :href="route('admin.funds.create')"
            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            {{ t('নতুন লেনদেন যোগ করুন', 'Add New Transaction') }}
          </Link>
        </div>
      </template>

      <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4">
          <!-- Statistics Cards: 2 cols on mobile, 4 on desktop -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
            <!-- Total Funds In -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-3 sm:p-4">
              <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="flex-shrink-0 rounded-lg bg-green-500/10 text-green-600 dark:text-green-400 p-2 sm:p-2.5">
                  <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11l7-7 7 7M5 19l7-7 7 7" />
                  </svg>
                </div>
                <div class="min-w-0 flex-1">
                  <dt class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ t('মোট জমা ফান্ড', 'Total Funds In') }}</dt>
                  <dd class="text-sm sm:text-base font-bold text-gray-900 dark:text-gray-100 truncate">{{ formatCurrency(statistics.totalFundsIn) }}</dd>
                </div>
              </div>
            </div>

            <!-- Total Funds Out -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-3 sm:p-4">
              <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="flex-shrink-0 rounded-lg bg-red-500/10 text-red-600 dark:text-red-400 p-2 sm:p-2.5">
                  <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7M19 5l-7 7-7-7" />
                  </svg>
                </div>
                <div class="min-w-0 flex-1">
                  <dt class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ t('মোট উত্তোলন ফান্ড', 'Total Funds Out') }}</dt>
                  <dd class="text-sm sm:text-base font-bold text-gray-900 dark:text-gray-100 truncate">{{ formatCurrency(statistics.totalFundsOut) }}</dd>
                </div>
              </div>
            </div>

            <!-- Net Balance -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-3 sm:p-4">
              <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="flex-shrink-0 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 p-2 sm:p-2.5">
                  <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="min-w-0 flex-1">
                  <dt class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ t('নিট ব্যালেন্স', 'Net Balance') }}</dt>
                  <dd class="text-sm sm:text-base font-bold truncate" :class="getNetBalanceClass">
                    {{ formatCurrency(statistics.netFunds) }}
                  </dd>
                </div>
              </div>
            </div>

            <!-- Total Transactions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-3 sm:p-4">
              <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="flex-shrink-0 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 p-2 sm:p-2.5">
                  <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                </div>
                <div class="min-w-0 flex-1">
                  <dt class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ t('মোট লেনদেন', 'Total Transactions') }}</dt>
                  <dd class="text-sm sm:text-base font-bold text-gray-900 dark:text-gray-100 truncate">{{ statistics.totalTransactions }}</dd>
                </div>
              </div>
            </div>
          </div>

          <!-- Filters -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-3 sm:p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
              <!-- Search -->
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <input
                  v-model="filters.search"
                  type="text"
                  class="w-full pl-9 pr-3 py-2 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  :placeholder="t('নাম বা বিবরণ দিয়ে খুঁজুন...', 'Search name or description...')"
                  @input="debouncedFilter"
                />
              </div>

              <!-- Bank Account Filter -->
              <div>
                <select
                  v-model="filters.bank_account"
                  class="w-full py-2 px-3 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  @change="filterData"
                >
                  <option value="">{{ t('সকল ব্যাংক অ্যাকাউন্ট', 'All Bank Accounts') }}</option>
                  <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                    {{ account.bank_name }} - {{ account.account_number }}
                  </option>
                </select>
              </div>

              <!-- Transaction Type -->
              <div>
                <select
                  v-model="filters.type"
                  class="w-full py-2 px-3 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  @change="filterData"
                >
                  <option value="">{{ t('সকল ধরন', 'All Types') }}</option>
                  <option value="in">{{ t('ফান্ড জমা (Fund In)', 'Fund In') }}</option>
                  <option value="out">{{ t('ফান্ড উত্তোলন (Fund Out)', 'Fund Out') }}</option>
                </select>
              </div>

              <!-- Date Range -->
              <div class="grid grid-cols-2 gap-2">
                <input
                  v-model="filters.date_from"
                  type="date"
                  class="w-full py-2 px-2 text-xs sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  placeholder="From"
                  @input="filterData"
                />
                <input
                  v-model="filters.date_to"
                  type="date"
                  class="w-full py-2 px-2 text-xs sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  placeholder="To"
                  @input="filterData"
                />
              </div>
            </div>

            <!-- Active Filters -->
            <div v-if="hasActiveFilters" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center gap-1.5 sm:gap-2">
              <div
                v-for="(filter, index) in activeFilters"
                :key="index"
                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200"
              >
                <span>{{ filter.label }}: {{ filter.value }}</span>
                <button
                  @click="removeFilter(filter.key)"
                  class="ml-1.5 inline-flex items-center p-0.5 rounded-full text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800"
                >
                  <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" />
                  </svg>
                </button>
              </div>
              <button
                @click="clearFilters"
                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
              >
                {{ t('ফিল্টার মুছুন', 'Clear All') }}
              </button>
            </div>
          </div>

          <!-- Data Container -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <!-- Mobile Cards View (md:hidden) -->
            <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
              <div
                v-for="fund in funds.data"
                :key="fund.id"
                class="p-3.5 sm:p-4 space-y-2 hover:bg-gray-50/50 dark:hover:bg-gray-700/30"
              >
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                      :class="getTypeClass(fund.type)"
                    >
                      {{ fund.type === 'in' ? t('জমা (In)', 'Fund In') : t('উত্তোলন (Out)', 'Fund Out') }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatDate(fund.date) }}
                    </span>
                  </div>
                  <div class="text-sm font-bold" :class="getAmountClass(fund.type)">
                    {{ formatCurrency(fund.amount) }}
                  </div>
                </div>

                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ fund.from_who }}
                </div>

                <div v-if="fund.description" class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                  {{ fund.description }}
                </div>

                <div class="flex items-center justify-between pt-1 border-t border-gray-50 dark:border-gray-700/50 text-xs">
                  <div class="text-gray-600 dark:text-gray-400 truncate max-w-[180px]">
                    🏦 {{ fund.bank_account?.bank_name }} - {{ fund.bank_account?.account_number }}
                  </div>
                  <div class="flex items-center gap-2">
                    <Link
                      :href="route('admin.funds.edit', fund.id)"
                      class="px-2.5 py-1 text-xs font-medium rounded text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30"
                    >
                      {{ t('সম্পাদনা', 'Edit') }}
                    </Link>
                    <button
                      @click="confirmDelete(fund)"
                      class="px-2.5 py-1 text-xs font-medium rounded text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30"
                    >
                      {{ t('মুছুন', 'Delete') }}
                    </button>
                  </div>
                </div>
              </div>

              <div v-if="funds.data.length === 0" class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ t('কোনো লেনদেন পাওয়া যায়নি', 'No fund transactions found') }}
              </div>
            </div>

            <!-- Desktop Data Table (hidden md:block) -->
            <div class="hidden md:block overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                  <tr>
                    <th
                      v-for="header in tableHeaders"
                      :key="header.key"
                      class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer select-none"
                      @click="sortBy(header.key)"
                    >
                      <div class="flex items-center space-x-1">
                        <span>{{ header.label }}</span>
                        <span v-if="sort.key === header.key" class="text-gray-400">
                          <svg v-if="sort.direction === 'asc'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                          </svg>
                          <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                          </svg>
                        </span>
                      </div>
                    </th>
                    <th class="relative px-6 py-3">
                      <span class="sr-only">Actions</span>
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr
                    v-for="fund in funds.data"
                    :key="fund.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                  >
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900 dark:text-gray-100">{{ formatDate(fund.date) }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatTime(fund.created_at) }}</div>
                    </td>
                    <td class="px-6 py-4">
                      <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ fund.from_who }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                        {{ fund.description || '—' }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900 dark:text-gray-100">
                        {{ fund.bank_account?.bank_name }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ fund.bank_account?.account_number }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="getTypeClass(fund.type)"
                      >
                        {{ fund.type === 'in' ? 'Fund In' : 'Fund Out' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-semibold" :class="getAmountClass(fund.type)">
                        {{ formatCurrency(fund.amount) }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex items-center justify-end space-x-3">
                        <Link
                          :href="route('admin.funds.edit', fund.id)"
                          class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                        >
                          {{ t('সম্পাদনা', 'Edit') }}
                        </Link>
                        <button
                          @click="confirmDelete(fund)"
                          class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                        >
                          {{ t('মুছুন', 'Delete') }}
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="funds.data.length === 0">
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                      {{ t('কোনো লেনদেন পাওয়া যায়নি', 'No fund transactions found') }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-100 dark:border-gray-700 sm:px-6">
              <Pagination :links="funds.links" />
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <Modal :show="deleteModal.show" @close="deleteModal.show = false">
        <div class="p-5 sm:p-6 max-h-[90vh] overflow-y-auto">
          <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
            {{ t('লেনদেন মুছতে চান?', 'Confirm Delete') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ t('আপনি কি নিশ্চিত যে এই লেনদেনটি মুছতে চান? এটি পুনরায় ফিরিয়ে আনা যাবে না।', 'Are you sure you want to delete this transaction? This action cannot be undone.') }}
          </p>
          <div class="mt-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 sm:p-4 text-sm space-y-1">
            <div><span class="font-medium text-gray-700 dark:text-gray-300">{{ t('ধরন:', 'Type:') }}</span> {{ deleteModal.fund?.type === 'in' ? 'Fund In' : 'Fund Out' }}</div>
            <div><span class="font-medium text-gray-700 dark:text-gray-300">{{ t('পরিমাণ:', 'Amount:') }}</span> {{ formatCurrency(deleteModal.fund?.amount) }}</div>
            <div><span class="font-medium text-gray-700 dark:text-gray-300">{{ t('উৎস/প্রাপক:', 'From/To:') }}</span> {{ deleteModal.fund?.from_who }}</div>
            <div><span class="font-medium text-gray-700 dark:text-gray-300">{{ t('তারিখ:', 'Date:') }}</span> {{ formatDate(deleteModal.fund?.date) }}</div>
          </div>
          <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button
              type="button"
              class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-center"
              @click="deleteModal.show = false"
            >
              {{ t('বাতিল', 'Cancel') }}
            </button>
            <button
              type="button"
              class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800"
              :disabled="form.processing"
              @click="deleteFund"
            >
              {{ form.processing ? t('মুছছে...', 'Deleting...') : t('মুছুন', 'Delete') }}
            </button>
          </div>
        </div>
      </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import Pagination from '@/Components/Pagination.vue'
import Modal from '@/Components/Modal.vue'
import debounce from 'lodash/debounce'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
  funds: {
    type: Object,
    required: true
  },
  bankAccounts: {
    type: Array,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  statistics: {
    type: Object,
    required: true
  }
})

const filters = ref({
  search: props.filters.search || '',
  bank_account: props.filters.bank_account || '',
  type: props.filters.type || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || ''
})

const sort = ref({
  key: 'date',
  direction: 'desc'
})

const deleteModal = ref({
  show: false,
  fund: null
})

const form = useForm({})

const tableHeaders = computed(() => [
  { key: 'date', label: t('তারিখ', 'Date') },
  { key: 'from_who', label: t('যার কাছ থেকে/যার কাছে', 'From/To') },
  { key: 'bank_account', label: t('ব্যাংক অ্যাকাউন্ট', 'Bank Account') },
  { key: 'type', label: t('ধরন', 'Type') },
  { key: 'amount', label: t('পরিমাণ', 'Amount') }
])

const formatCurrency = (amount) => {
  const number = new Intl.NumberFormat('en-BD', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
  return `৳ ${number}`
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString()
}

const formatTime = (datetime) => {
  if (!datetime) return ''
  return new Date(datetime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const getTypeClass = (type) => {
  return {
    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': type === 'in',
    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': type === 'out'
  }
}

const getAmountClass = (type) => {
  return {
    'text-green-600 dark:text-green-400': type === 'in',
    'text-red-600 dark:text-red-400': type === 'out'
  }
}

const getNetBalanceClass = computed(() => {
  return {
    'text-green-600 dark:text-green-400': props.statistics.netFunds >= 0,
    'text-red-600 dark:text-red-400': props.statistics.netFunds < 0
  }
})

const hasActiveFilters = computed(() => {
  return Object.values(filters.value).some(value => value !== '')
})

const activeFilters = computed(() => {
  const active = []

  if (filters.value.search) {
    active.push({ key: 'search', label: 'Search', value: filters.value.search })
  }

  if (filters.value.bank_account) {
    const bank = props.bankAccounts.find(b => b.id === Number(filters.value.bank_account))
    active.push({
      key: 'bank_account',
      label: 'Bank',
      value: bank ? `${bank.bank_name} - ${bank.account_number}` : ''
    })
  }

  if (filters.value.type) {
    active.push({
      key: 'type',
      label: 'Type',
      value: filters.value.type === 'in' ? 'Fund In' : 'Fund Out'
    })
  }

  if (filters.value.date_from) {
    active.push({ key: 'date_from', label: 'From', value: formatDate(filters.value.date_from) })
  }

  if (filters.value.date_to) {
    active.push({ key: 'date_to', label: 'To', value: formatDate(filters.value.date_to) })
  }

  return active
})

const debouncedFilter = debounce(() => {
  filterData()
}, 300)

const filterData = () => {
  router.get(
    route('admin.funds.index'),
    { ...filters.value, sort: `${sort.value.key}-${sort.value.direction}` },
    { preserveState: true, preserveScroll: true }
  )
}

const sortBy = (key) => {
  sort.value.direction = sort.value.key === key && sort.value.direction === 'asc' ? 'desc' : 'asc'
  sort.value.key = key
  filterData()
}

const removeFilter = (key) => {
  filters.value[key] = ''
  filterData()
}

const clearFilters = () => {
  filters.value = {
    search: '',
    bank_account: '',
    type: '',
    date_from: '',
    date_to: ''
  }
  filterData()
}

const confirmDelete = (fund) => {
  deleteModal.value = {
    show: true,
    fund
  }
}

const deleteFund = () => {
  if (!deleteModal.value.fund) return

  form.delete(route('admin.funds.destroy', deleteModal.value.fund.id), {
    preserveScroll: true,
    onSuccess: () => {
      deleteModal.value.show = false
    }
  })
}
</script>
