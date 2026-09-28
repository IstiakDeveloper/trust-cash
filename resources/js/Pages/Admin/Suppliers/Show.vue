<template>
    <AdminLayout :title="t('সাপ্লায়ার লেজার ও খাতা', 'Supplier Ledger') + ' - ' + supplier.name">
        <Head :title="t('সাপ্লায়ার লেজার ও খাতা', 'Supplier Ledger') + ' - ' + supplier.name" />

        <div class="space-y-6">
            <!-- Header & Back Button -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.suppliers.index')"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ supplier.name }}</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ supplier.company_name || t('ব্যক্তিগত সাপ্লায়ার', 'Individual Supplier') }}</p>
                    </div>
                </div>

                <button
                    v-if="supplier.current_balance > 0"
                    @click="showPaymentModal = true"
                    class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-rose-500 transition-all"
                >
                    <DollarSignIcon class="w-4 h-4" />
                    <span>{{ t('দেনা পরিশোধ করুন', 'Pay Due') }} (৳{{ formatNumber(supplier.current_balance) }})</span>
                </button>
            </div>

            <!-- Profile & Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-3">
                        {{ t('যোগাযোগ সংক্রান্ত তথ্য', 'Contact Information') }}
                    </h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-400">{{ t('ফোন নম্বর:', 'Phone:') }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ supplier.phone || '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">{{ t('ইমেইল:', 'Email:') }}</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ supplier.email || '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">{{ t('ঠিকানা:', 'Address:') }}</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300 text-right">{{ supplier.address || '—' }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('প্রারম্ভিক ব্যালেন্স', 'Opening Balance') }}</span>
                    <h4 class="text-xl font-black text-slate-900 dark:text-white mt-1">৳{{ formatNumber(supplier.opening_balance) }}</h4>
                    <p class="text-[11px] text-slate-400 mt-1">{{ t('পূর্বের হিসাব থেকে আনীত', 'Carried forward') }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('বর্তমান মোট দেনা (পাওনা)', 'Current Payable Due') }}</span>
                    <h4 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">৳{{ formatNumber(supplier.current_balance) }}</h4>
                    <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold mt-1" :class="supplier.current_balance > 0 ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'">
                        {{ supplier.current_balance > 0 ? t('পরিশোধযোগ্য বাকি আছে', 'Amount Outstanding') : t('সকল হিসাব পরিশোধিত', 'All Cleared') }}
                    </span>
                </div>
            </div>

            <!-- Tabs: Purchases vs Payments -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-900">
                <div class="border-b border-slate-100 dark:border-slate-800 px-6 flex gap-4 text-xs font-bold">
                    <button
                        @click="activeTab = 'purchases'"
                        class="py-3 border-b-2 transition-all flex items-center gap-1.5"
                        :class="activeTab === 'purchases' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    >
                        <ShoppingCartIcon class="w-4 h-4" />
                        <span>{{ t('ক্রয়ের তালিকা', 'Purchase History') }} ({{ supplier.purchases?.length || 0 }})</span>
                    </button>
                    <button
                        @click="activeTab = 'payments'"
                        class="py-3 border-b-2 transition-all flex items-center gap-1.5"
                        :class="activeTab === 'payments' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    >
                        <CreditCardIcon class="w-4 h-4" />
                        <span>{{ t('পরিশোধ লেজার', 'Payment Ledger') }} ({{ supplier.payments?.length || 0 }})</span>
                    </button>
                </div>

                <!-- Purchases Tab Content -->
                <div v-if="activeTab === 'purchases'" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                            <tr>
                                <th class="px-5 py-3 text-left">{{ t('ক্রয় ইনভয়েস #', 'Purchase #') }}</th>
                                <th class="px-4 py-3 text-left">{{ t('তারিখ', 'Date') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('মোট টাকা (৳)', 'Total (৳)') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('পরিশোধ (৳)', 'Paid (৳)') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('বকেয়া (৳)', 'Due (৳)') }}</th>
                                <th class="px-4 py-3 text-center">{{ t('অবস্থা', 'Status') }}</th>
                                <th class="px-5 py-3 text-right">{{ t('অ্যাকশন', 'Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="p in supplier.purchases" :key="p.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                <td class="px-5 py-3.5 font-bold text-indigo-600 dark:text-indigo-400">{{ p.purchase_number }}</td>
                                <td class="px-4 py-3.5 text-slate-500">{{ p.purchase_date }}</td>
                                <td class="px-4 py-3.5 text-right font-bold text-slate-900 dark:text-slate-100">৳{{ formatNumber(p.total_amount) }}</td>
                                <td class="px-4 py-3.5 text-right font-bold text-emerald-600 dark:text-emerald-400">৳{{ formatNumber(p.paid_amount) }}</td>
                                <td class="px-4 py-3.5 text-right font-bold text-rose-600 dark:text-rose-400">৳{{ formatNumber(p.due_amount) }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <span
                                        class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300': p.payment_status === 'paid',
                                            'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300': p.payment_status === 'partial',
                                            'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300': p.payment_status === 'due',
                                        }"
                                    >
                                        {{ p.payment_status === 'paid' ? t('পরিশোধিত', 'Paid') : (p.payment_status === 'partial' ? t('আংশিক', 'Partial') : t('বকেয়া', 'Due')) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <Link :href="route('admin.purchases.show', p.id)" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">{{ t('বিস্তারিত', 'View Invoice') }}</Link>
                                </td>
                            </tr>
                            <tr v-if="!supplier.purchases || supplier.purchases.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-slate-400">{{ t('এই সাপ্লায়ারের কোনো ক্রয় রেকর্ড পাওয়া যায়নি।', 'No purchases found for this supplier.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Payments Tab Content -->
                <div v-if="activeTab === 'payments'" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                            <tr>
                                <th class="px-5 py-3 text-left">{{ t('তারিখ', 'Date') }}</th>
                                <th class="px-4 py-3 text-left">{{ t('পরিশোধ মাধ্যম ও ব্যাংক হিসাব', 'Payment Method / Account') }}</th>
                                <th class="px-4 py-3 text-left">{{ t('রেফারেন্স ও মন্তব্য', 'Ref / Note') }}</th>
                                <th class="px-5 py-3 text-right">{{ t('পরিশোধিত টাকা (৳)', 'Amount Paid (৳)') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="pay in supplier.payments" :key="pay.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                <td class="px-5 py-3.5 text-slate-500">{{ pay.payment_date }}</td>
                                <td class="px-4 py-3.5 font-medium text-slate-800 dark:text-slate-200">
                                    <span class="font-bold">{{ pay.payment_method }}</span>
                                    <span v-if="pay.bank_account" class="text-[11px] text-slate-400 block">({{ pay.bank_account.bank_name }})</span>
                                </td>
                                <td class="px-4 py-3.5 text-slate-500 text-xs">{{ pay.reference_no || pay.note || '—' }}</td>
                                <td class="px-5 py-3.5 text-right font-bold text-emerald-600 dark:text-emerald-400">৳{{ formatNumber(pay.amount) }}</td>
                            </tr>
                            <tr v-if="!supplier.payments || supplier.payments.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-slate-400">{{ t('কোনো দেনা পরিশোধ রেকর্ড নেই।', 'No payment records found.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pay Due Modal -->
            <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800">
                    <div class="flex justify-between items-center pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ t('সাপ্লায়ার দেনা পরিশোধ', 'Record Payment') }}</h3>
                        <button @click="showPaymentModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                    </div>

                    <form @submit.prevent="submitPayment" class="mt-4 space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('পরিশোধের পরিমাণ (টাকা) *', 'Amount (BDT) *') }}</label>
                            <input
                                type="number"
                                step="0.01"
                                v-model="paymentForm.amount"
                                required
                                class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-base font-bold text-rose-600 dark:bg-slate-800 dark:border-slate-700 dark:text-rose-400"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('যে ব্যাংক / ক্যাশ হিসাব থেকে পরিশোধ হবে *', 'Paid From Bank / Cash Account *') }}</label>
                            <select
                                v-model="paymentForm.bank_account_id"
                                required
                                class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                                :class="isInsufficientBalance ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500' : ''"
                            >
                                <option value="" disabled>{{ t('-- ব্যাংক / ক্যাশ হিসাব নির্বাচন করুন * --', '-- Select Bank / Cash Account * --') }}</option>
                                <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
                                    {{ acc.bank_name }} - {{ acc.account_number || t('ক্যাশ', 'Cash') }} ({{ t('ব্যালেন্স:', 'Bal:') }} ৳{{ formatNumber(acc.current_balance) }})
                                </option>
                            </select>

                            <!-- Instant Balance Preview -->
                            <div v-if="selectedBankAccount" class="mt-2 p-2 rounded-lg text-xs flex items-center justify-between"
                                 :class="isInsufficientBalance ? 'bg-rose-50 border border-rose-200 text-rose-700 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300' : 'bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300'">
                                <span class="font-medium">{{ t('নির্বাচিত হিসাবে উপলব্ধ ব্যালেন্স:', 'Available in Account:') }}</span>
                                <span class="font-bold font-mono text-sm">৳{{ formatNumber(selectedBankAccount.current_balance) }}</span>
                            </div>

                            <!-- Instant Insufficient Balance Alert -->
                            <div v-if="isInsufficientBalance" class="mt-2 p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <p class="font-bold">{{ t('অপর্যাপ্ত ব্যাংক ব্যালেন্স!', 'Insufficient Balance!') }}</p>
                                    <p>{{ t('নির্বাচিত ব্যাংক হিসাবে পর্যাপ্ত টাকা নেই। অনুগ্রহ করে অন্য হিসাব নির্বাচন করুন।', 'Selected account does not have sufficient balance for this payment.') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('মাধ্যম', 'Method') }}</label>
                                <select v-model="paymentForm.payment_method" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                                    <option value="cash">{{ t('নগদ (Cash)', 'Cash') }}</option>
                                    <option value="bank">{{ t('ব্যাংক ট্রান্সফার', 'Bank Transfer') }}</option>
                                    <option value="mobile">{{ t('মোবাইল ব্যাংকিং', 'Mobile') }}</option>
                                    <option value="check">{{ t('চেক (Cheque)', 'Cheque') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('তারিখ *', 'Date *') }}</label>
                                <input type="date" v-model="paymentForm.payment_date" required class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('রেফারেন্স / নোট', 'Reference / Note') }}</label>
                            <input type="text" v-model="paymentForm.reference_no" :placeholder="t('চেক বা রসিদ নম্বর...', 'Check #, receipt #...')" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showPaymentModal = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ t('বাতিল', 'Cancel') }}</button>
                            <button
                                type="submit"
                                :disabled="submittingPayment || isInsufficientBalance || !paymentForm.bank_account_id || paymentForm.amount <= 0"
                                class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-500 disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                            >
                                {{ t('পরিশোধ নিশ্চিত করুন', 'Confirm Payment') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    ArrowLeft as ArrowLeftIcon,
    DollarSign as DollarSignIcon,
    ShoppingCart as ShoppingCartIcon,
    CreditCard as CreditCardIcon
} from 'lucide-vue-next'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    supplier: Object,
    bankAccounts: Array,
})

const activeTab = ref('purchases')
const showPaymentModal = ref(false)
const submittingPayment = ref(false)

const paymentForm = ref({
    amount: props.supplier.current_balance,
    bank_account_id: '',
    payment_method: 'cash',
    payment_date: new Date().toISOString().split('T')[0],
    reference_no: '',
    note: '',
})

const selectedBankAccount = computed(() => {
    return props.bankAccounts?.find(acc => acc.id == paymentForm.value.bank_account_id) || null
})

const isInsufficientBalance = computed(() => {
    if (!selectedBankAccount.value) return false
    const amt = parseFloat(paymentForm.value.amount || 0)
    const bal = parseFloat(selectedBankAccount.value.current_balance || 0)
    return amt > bal
})

const formatNumber = (val) => {
    return Number(val || 0).toLocaleString('bn-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}

const submitPayment = () => {
    if (!paymentForm.value.bank_account_id) {
        alert(t('অনুগ্রহ করে পরিশোধের জন্য ব্যাংক বা ক্যাশ হিসাব নির্বাচন করুন', 'Please select a bank or cash account for payment'))
        return
    }
    if (isInsufficientBalance.value) {
        alert(t('নির্বাচিত ব্যাংক হিসাবে পর্যাপ্ত ব্যালেন্স নেই!', 'Insufficient balance in selected account!'))
        return
    }
    submittingPayment.value = true
    router.post(route('admin.suppliers.add-payment', props.supplier.id), paymentForm.value, {
        onSuccess: () => {
            showPaymentModal.value = false
            submittingPayment.value = false
        },
        onError: () => {
            submittingPayment.value = false
        }
    })
}
</script>
