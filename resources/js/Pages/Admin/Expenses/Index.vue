<template>
    <Head :title="t('খরচ তালিকা', 'Expenses')" />
    <AdminLayout :title="t('খরচ ব্যবস্থাপনা', 'Expense Management')">
        <div class="container mx-auto px-4 py-6">
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('মোট খরচ', 'Total Operational Expenses') }}</p>
                        <h3 class="text-2xl font-black text-rose-600 mt-1">
                            {{ formatAmount(summary.totalExpenses) }}
                        </h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-600">
                        <TrendingDownIcon class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('স্থায়ী সম্পদ মডিউল', 'Fixed Assets Module') }}</p>
                        <div class="mt-2">
                            <a :href="route('admin.fixed-assets.index')" class="inline-flex items-center gap-1.5 text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                {{ t('স্থায়ী সম্পদ তালিকা দেখুন →', 'Go to Fixed Assets →') }}
                            </a>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                        <BuildingIcon class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Actions & Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-4 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                    <div class="text-base font-bold text-gray-900 dark:text-white">
                        {{ t('খরচের হিসাব ও ফিল্টার', 'Expenses & Filter') }}
                    </div>
                    <button
                        @click="openCreateModal"
                        class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold flex items-center gap-2 shadow-sm transition"
                    >
                        <PlusIcon class="w-4 h-4" />
                        {{ t('নতুন খরচ যোগ করুন', 'Add Expense') }}
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ t('খরচের খাত / ক্যাটাগরি', 'Category') }}</label>
                        <select v-model="filters.expense_category_id" @change="getExpenses" class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">{{ t('সকল ক্যাটাগরি', 'All Categories') }}</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ t('ব্যাংক / ক্যাশ অ্যাকাউন্ট', 'Bank / Cash Account') }}</label>
                        <select v-model="filters.bank_id" @change="getExpenses" class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">{{ t('সকল অ্যাকাউন্ট', 'All Accounts') }}</option>
                            <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">
                                {{ bank.bank_name }} - {{ bank.account_number || t('ক্যাশ', 'Cash') }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ t('শুরুর তারিখ', 'From Date') }}</label>
                        <input type="date" v-model="filters.from_date" @change="getExpenses" class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ t('শেষ তারিখ', 'To Date') }}</label>
                        <input type="date" v-model="filters.to_date" @change="getExpenses" class="w-full text-sm rounded-xl border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                    </div>
                </div>
            </div>

            <!-- Expenses Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('তারিখ', 'Date') }}</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('ক্যাটাগরি', 'Category') }}</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('অ্যাকাউন্ট', 'Account') }}</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('বিবরণ / রেফারেন্স', 'Description / Ref') }}</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 dark:text-gray-300">{{ t('পরিমাণ (৳)', 'Amount (৳)') }}</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 dark:text-gray-300">{{ t('অ্যাকশন', 'Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="expense in expenses.data" :key="expense.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-200">{{ formatDate(expense.date) }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ expense.category?.name || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ expense.bank_account?.bank_name }} - {{ expense.bank_account?.account_number || t('ক্যাশ', 'Cash') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-900 dark:text-white font-medium">{{ expense.description || '—' }}</div>
                                    <div v-if="expense.reference_no" class="text-xs text-gray-400">Ref: {{ expense.reference_no }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-black text-rose-600">{{ formatAmount(expense.amount) }}</td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="editExpense(expense)" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition" :title="t('সম্পাদনা', 'Edit')">
                                        <EditIcon class="w-4 h-4 inline" />
                                    </button>
                                    <button @click="deleteExpense(expense)" class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition" :title="t('মুছুন', 'Delete')">
                                        <TrashIcon class="w-4 h-4 inline" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="expenses.data.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400 font-medium">
                                    {{ t('কোনো খরচের রেকর্ড পাওয়া যায়নি।', 'No expense records found.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="expenses.links && expenses.links.length > 3" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-3 text-sm">
                    <div class="text-gray-500 font-medium">
                        {{ t(`মোট ${expenses.total} টির মধ্যে ${expenses.from || 0} থেকে ${expenses.to || 0} দেখাচ্ছে`, `Showing ${expenses.from || 0} to ${expenses.to || 0} of ${expenses.total} entries`) }}
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in expenses.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-lg border text-sm font-medium transition"
                            :class="link.active ? 'bg-blue-600 text-white border-blue-600 font-bold' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border-gray-200 dark:border-gray-700'"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    {{ isEditing ? t('খরচ সম্পাদনা করুন', 'Edit Expense') : t('নতুন খরচ যোগ করুন', 'Create Expense') }}
                </h3>
                <form @submit.prevent="submitForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ t('খরচের খাত *', 'Category *') }}</label>
                            <select v-model="form.expense_category_id" required class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="" disabled>{{ t('-- ক্যাটাগরি বেছে নিন --', '-- Select Category --') }}</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ t('যে অ্যাকাউন্ট থেকে পরিশোধিত *', 'Bank / Cash Account *') }}</label>
                            <select v-model="form.bank_account_id" required class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="" disabled>{{ t('-- অ্যাকাউন্ট বেছে নিন --', '-- Select Account --') }}</option>
                                <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">
                                    {{ bank.bank_name }} - {{ bank.account_number || t('ক্যাশ', 'Cash') }} ({{ t('ব্যালেন্স:', 'Bal:') }} ৳{{ bank.current_balance }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ t('টাকার পরিমাণ (৳) *', 'Amount (৳) *') }}</label>
                            <input type="number" v-model="form.amount" step="0.01" required min="0.01" class="w-full text-sm rounded-xl font-bold border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ t('খরচের তারিখ *', 'Date *') }}</label>
                            <input type="date" v-model="form.date" required class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ t('রেফারেন্স / ভাউচার নং', 'Reference No') }}</label>
                            <input type="text" v-model="form.reference_no" class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ t('বিবরণ / মন্তব্য', 'Description') }}</label>
                            <textarea v-model="form.description" rows="2" class="w-full text-sm rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 border rounded-xl text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700">
                            {{ t('বাতিল', 'Cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition">
                            {{ isEditing ? t('আপডেট করুন', 'Update') : t('সংরক্ষণ করুন', 'Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'
import { useLanguage } from '@/composables/useLanguage'
import {
    Plus as PlusIcon,
    Edit as EditIcon,
    Trash as TrashIcon,
    TrendingDown as TrendingDownIcon,
    Building as BuildingIcon
} from 'lucide-vue-next'

const { t } = useLanguage()

const props = defineProps({
    expenses: Object,
    categories: Array,
    bankAccounts: Array,
    filters: Object,
    summary: {
        type: Object,
        default: () => ({
            totalExpenses: 0
        })
    }
})

const showModal = ref(false)
const isEditing = ref(false)
const currentExpense = ref(null)

const filters = reactive({
    expense_category_id: props.filters?.expense_category_id || '',
    bank_id: props.filters?.bank_id || '',
    from_date: props.filters?.from_date || '',
    to_date: props.filters?.to_date || ''
})

const form = reactive({
    expense_category_id: '',
    bank_account_id: '',
    amount: '',
    description: '',
    reference_no: '',
    date: new Date().toISOString().split('T')[0],
    attachment: null
})

const getExpenses = () => {
    router.get(route('admin.expenses.index'), filters, {
        preserveState: true,
        preserveScroll: true
    })
}

const openCreateModal = () => {
    isEditing.value = false
    resetForm()
    showModal.value = true
}

const editExpense = (expense) => {
    isEditing.value = true
    currentExpense.value = expense
    Object.assign(form, {
        expense_category_id: expense.expense_category_id,
        bank_account_id: expense.bank_account_id,
        amount: expense.amount,
        description: expense.description,
        reference_no: expense.reference_no,
        date: expense.date
    })
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    resetForm()
}

const resetForm = () => {
    Object.assign(form, {
        expense_category_id: props.categories.length > 0 ? props.categories[0].id : '',
        bank_account_id: props.bankAccounts.length > 0 ? props.bankAccounts[0].id : '',
        amount: '',
        description: '',
        reference_no: '',
        date: new Date().toISOString().split('T')[0],
        attachment: null
    })
    currentExpense.value = null
}

const submitForm = () => {
    if (isEditing.value) {
        router.post(route('admin.expenses.update', currentExpense.value.id), {
            ...form,
            _method: 'PUT'
        }, {
            onSuccess: () => closeModal()
        })
    } else {
        router.post(route('admin.expenses.store'), form, {
            onSuccess: () => closeModal()
        })
    }
}

const deleteExpense = (expense) => {
    const confirmMsg = t('আপনি কি নিশ্চিত যে এই খরচের হিসাবটি মুছে ফেলতে চান?', 'Are you sure you want to delete this expense?')
    if (confirm(confirmMsg)) {
        router.delete(route('admin.expenses.destroy', expense.id))
    }
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString()
}

const formatAmount = (amount) => {
    const number = Number(amount || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
    return `৳ ${number}`
}
</script>
