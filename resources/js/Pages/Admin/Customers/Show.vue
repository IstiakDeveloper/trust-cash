<template>
    <AdminLayout :title="t('কাস্টমার লেজার ও বাকি আদায়', 'Customer Details') + ' - ' + customer.name">
        <Head :title="t('কাস্টমার লেজার ও বাকি আদায়', 'Customer Details')" />

        <div class="space-y-4 sm:space-y-6">
            <!-- Back Button & Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.customers.index')"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        <ArrowLeftIcon class="h-4 w-4" />
                    </Link>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">{{ customer.name }}</h1>
                        <a v-if="customer.phone" :href="'tel:' + customer.phone" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                            {{ customer.phone }}
                        </a>
                        <p v-else class="text-xs text-slate-500 dark:text-slate-400">{{ t('ফোন নম্বর নেই', 'No phone') }}</p>
                    </div>
                </div>

                <div v-if="sales_summary.total_due > 0" class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-xs text-rose-600 font-bold bg-rose-50 dark:bg-rose-950/60 px-3 py-1.5 rounded-xl border border-rose-200 dark:border-rose-900">
                        {{ t('মোট বকেয়া:', 'Total Due:') }} ৳{{ formatNumber(sales_summary.total_due) }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Summary Cards -->
                <div class="space-y-4 sm:space-y-6">
                    <!-- Customer Info Card -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3 sm:mb-4">
                            {{ t('কাস্টমার তথ্য', 'Customer Information') }}
                        </h3>
                        <div class="space-y-3 text-xs">
                            <div>
                                <span class="text-slate-400 block">{{ t('নাম', 'Name') }}</span>
                                <span class="font-bold text-slate-900 dark:text-white mt-0.5 block">{{ customer.name }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block">{{ t('মোবাইল নম্বর', 'Phone') }}</span>
                                <a v-if="customer.phone" :href="'tel:' + customer.phone" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline mt-0.5 block">
                                    {{ customer.phone }}
                                </a>
                                <span v-else class="text-slate-400 mt-0.5 block">-</span>
                            </div>
                            <div v-if="customer.email">
                                <span class="text-slate-400 block">{{ t('ইমেইল', 'Email') }}</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block break-all">{{ customer.email }}</span>
                            </div>
                            <div v-if="customer.address">
                                <span class="text-slate-400 block">{{ t('ঠিকানা', 'Address') }}</span>
                                <span class="text-slate-700 dark:text-slate-300 mt-0.5 block">{{ customer.address }}</span>
                            </div>
                            <div v-if="customer.branch_code || customer.branch_name">
                                <span class="text-slate-400 block">{{ t('শাখা', 'Branch') }}</span>
                                <span class="text-slate-700 dark:text-slate-300 mt-0.5 block">
                                    {{ customer.branch_name || '-' }} ({{ customer.branch_code || '-' }})
                                </span>
                            </div>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                                <span class="text-slate-500">{{ t('অবস্থা', 'Status') }}</span>
                                <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold" :class="{
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300': customer.status,
                                    'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300': !customer.status
                                }">
                                    {{ customer.status ? t('সক্রিয়', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Summary -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3 sm:mb-4">
                            {{ t('হিসাব বিবরণী', 'Sales Summary') }}
                        </h3>
                        <div class="grid grid-cols-2 gap-3 sm:gap-4 text-xs">
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                                <span class="text-slate-500 dark:text-slate-400 block text-[11px]">{{ t('মোট চালান', 'Total Sales') }}</span>
                                <span class="mt-1 text-lg sm:text-xl font-bold text-slate-900 dark:text-white block">
                                    {{ sales_summary.total_invoices }}
                                </span>
                            </div>
                            <div class="p-3 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/30">
                                <span class="text-slate-500 dark:text-slate-400 block text-[11px]">{{ t('মোট কেনাকাটা', 'Total Amount') }}</span>
                                <span class="mt-1 text-lg sm:text-xl font-bold text-indigo-600 dark:text-indigo-400 block">
                                    ৳{{ formatNumber(sales_summary.total_amount) }}
                                </span>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/30">
                                <span class="text-slate-500 dark:text-slate-400 block text-[11px]">{{ t('মোট পরিশোধ', 'Total Paid') }}</span>
                                <span class="mt-1 text-lg sm:text-xl font-bold text-emerald-600 dark:text-emerald-400 block">
                                    ৳{{ formatNumber(sales_summary.total_paid) }}
                                </span>
                            </div>
                            <div class="p-3 rounded-xl bg-rose-50/50 dark:bg-rose-950/30">
                                <span class="text-slate-500 dark:text-slate-400 block text-[11px]">{{ t('মোট বকেয়া', 'Total Due') }}</span>
                                <span class="mt-1 text-lg sm:text-xl font-bold text-rose-600 dark:text-rose-400 block">
                                    ৳{{ formatNumber(sales_summary.total_due) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Payments -->
                    <div v-if="recent_payments.length > 0" class="rounded-2xl border border-slate-200/90 bg-white shadow-xs overflow-hidden dark:border-slate-700 dark:bg-slate-900">
                        <div class="px-4 sm:px-6 py-3.5 border-b border-slate-100 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ t('সাম্প্রতিক আদায়', 'Recent Payments') }}</h3>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <div v-for="payment in recent_payments" :key="payment.id" class="p-3.5 sm:p-4 flex justify-between items-center">
                                <div>
                                    <div class="font-bold text-emerald-600 dark:text-emerald-400">
                                        ৳{{ formatNumber(payment.amount) }}
                                    </div>
                                    <div class="text-[11px] font-mono text-slate-400">{{ payment.invoice_no }}</div>
                                </div>
                                <div class="text-slate-500">{{ payment.date }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales List & Payment Form -->
                <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                    <!-- Add Payment Form -->
                    <div v-if="salesWithDue.length > 0" class="rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3 sm:mb-4">
                            {{ t('বকেয়া টাকা আদায় (কালেকশন)', 'Add Payment') }}
                        </h3>
                        <form @submit.prevent="submitPayment" class="space-y-3 sm:space-y-4 text-xs">
                            <!-- Sale Selection -->
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    {{ t('বকেয়া ইনভয়েস নির্বাচন করুন *', 'Select Invoice *') }}
                                </label>
                                <select v-model="form.sale_id"
                                    class="block w-full min-h-[42px] rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                    <option value="">{{ t('ইনভয়েস নির্বাচন করুন', 'Select Invoice') }}</option>
                                    <option v-for="sale in salesWithDue" :key="sale.id" :value="sale.id">
                                        {{ sale.invoice_no }} - {{ t('বকেয়া:', 'Due:') }} ৳{{ formatNumber(sale.due) }}
                                    </option>
                                </select>
                                <div v-if="form.errors.sale_id" class="mt-1 text-rose-600">
                                    {{ form.errors.sale_id }}
                                </div>
                            </div>

                            <!-- Amount & Bank Account in 2 Cols on sm -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                <!-- Amount -->
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        {{ t('আদায়ের পরিমাণ (টাকা) *', 'Amount (BDT) *') }}
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="font-bold text-slate-400">৳</span>
                                        </div>
                                        <input type="number" v-model="form.amount"
                                            class="block w-full min-h-[42px] rounded-xl border-slate-200 py-2 pl-7 pr-3 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                            step="0.01" :max="selectedSaleDue"
                                            :placeholder="selectedSale ? `${t('সর্বোচ্চ:', 'Max:')} ${formatNumber(selectedSaleDue)}` : '0.00'" />
                                    </div>
                                    <div v-if="form.errors.amount" class="mt-1 text-rose-600">
                                        {{ form.errors.amount }}
                                    </div>
                                </div>

                                <!-- Bank Account -->
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        {{ t('যে ব্যাংক / ক্যাশ হিসাবে জমা হবে *', 'Bank Account *') }}
                                    </label>
                                    <select v-model="form.bank_account_id" @change="handleBankAccountSelect"
                                        class="block w-full min-h-[42px] rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="">{{ t('হিসাব নির্বাচন করুন', 'Select Bank Account') }}</option>
                                        <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                            {{ account.bank_name }} - {{ account.account_name }} ({{ account.account_number }})
                                        </option>
                                    </select>
                                    <div v-if="form.errors.bank_account_id" class="mt-1 text-rose-600">
                                        {{ form.errors.bank_account_id }}
                                    </div>
                                </div>
                            </div>

                            <!-- Note -->
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    {{ t('নোট / মন্তব্য', 'Note') }}
                                </label>
                                <textarea v-model="form.note"
                                    class="block w-full rounded-xl border-slate-200 bg-white p-2.5 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    rows="2" :placeholder="t('পেমেন্ট সংক্রান্ত কোনো বিবরণ...', 'Payment notes...')"></textarea>
                                <div v-if="form.errors.note" class="mt-1 text-rose-600">
                                    {{ form.errors.note }}
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end pt-2">
                                <button type="submit"
                                    :disabled="form.processing || !form.amount || !form.bank_account_id || !form.sale_id"
                                    class="w-full sm:w-auto rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 transition-all">
                                    {{ t('বকেয়া আদায় সংরক্ষণ করুন', 'Add Payment') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Sales List -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white shadow-xs overflow-hidden dark:border-slate-700 dark:bg-slate-900">
                        <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ t('বিক্রয় ও চালানের ইতিহাস', 'Sales History') }}</h3>
                            <span class="text-xs text-slate-500">{{ sales.length }} {{ t('টি চালান', 'invoices') }}</span>
                        </div>

                        <!-- Mobile Sales Cards (md:hidden) -->
                        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
                            <div v-for="sale in sales" :key="'m-' + sale.id" class="p-3.5 space-y-2">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">{{ sale.invoice_no }}</span>
                                        <span class="text-[11px] text-slate-400 block">{{ sale.date }}</span>
                                    </div>
                                    <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300': sale.payment_status === 'paid',
                                            'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300': sale.payment_status === 'partial',
                                            'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300': sale.payment_status === 'due'
                                        }">
                                        {{ sale.payment_status === 'paid' ? t('পরিশোধিত', 'Paid') : (sale.payment_status === 'partial' ? t('আংশিক', 'Partial') : t('বকেয়া', 'Due')) }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-3 gap-2 pt-1 text-[11px] border-t border-slate-50 dark:border-slate-800">
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">{{ t('মোট', 'Total') }}</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200">৳{{ formatNumber(sale.total) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">{{ t('পরিশোধ', 'Paid') }}</span>
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">৳{{ formatNumber(sale.paid) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">{{ t('বকেয়া', 'Due') }}</span>
                                        <span class="font-bold text-rose-600 dark:text-rose-400">৳{{ formatNumber(sale.due) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="sales.length === 0" class="p-6 text-center text-xs text-slate-400">
                                {{ t('কোনো বিক্রয়ের রেকর্ড পাওয়া যায়নি', 'No sales records found') }}
                            </div>
                        </div>

                        <!-- Desktop Table (hidden md:block) -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                                    <tr>
                                        <th class="px-5 py-3 text-left">{{ t('ইনভয়েস', 'Invoice') }}</th>
                                        <th class="px-4 py-3 text-right">{{ t('মোট', 'Total') }}</th>
                                        <th class="px-4 py-3 text-right">{{ t('পরিশোধ', 'Paid') }}</th>
                                        <th class="px-4 py-3 text-right">{{ t('বকেয়া', 'Due') }}</th>
                                        <th class="px-4 py-3 text-center">{{ t('অবস্থা', 'Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="sale in sales" :key="sale.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            <div class="font-bold text-slate-900 dark:text-slate-100">{{ sale.invoice_no }}</div>
                                            <div class="text-[11px] text-slate-400">{{ sale.date }}</div>
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-slate-900 dark:text-slate-100">
                                            ৳{{ formatNumber(sale.total) }}
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-emerald-600 dark:text-emerald-400">
                                            ৳{{ formatNumber(sale.paid) }}
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-rose-600 dark:text-rose-400">
                                            ৳{{ formatNumber(sale.due) }}
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                            <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold"
                                                :class="{
                                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300': sale.payment_status === 'paid',
                                                    'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300': sale.payment_status === 'partial',
                                                    'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300': sale.payment_status === 'due'
                                                }">
                                                {{ sale.payment_status === 'paid' ? t('পরিশোধিত', 'Paid') : (sale.payment_status === 'partial' ? t('আংশিক', 'Partial') : t('বকেয়া', 'Due')) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ArrowLeft as ArrowLeftIcon } from 'lucide-vue-next'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    customer: {
        type: Object,
        required: true
    },
    sales: {
        type: Array,
        required: true,
        default: () => []
    },
    sales_summary: {
        type: Object,
        required: true,
        default: () => ({
            total_invoices: 0,
            total_amount: '0.00',
            total_paid: '0.00',
            total_due: '0.00'
        })
    },
    recent_payments: {
        type: Array,
        default: () => []
    },
    bankAccounts: {
        type: Array,
        required: true,
        default: () => []
    }
})

const form = useForm({
    sale_id: '',
    amount: '',
    bank_account_id: '',
    payment_method: 'bank',
    note: ''
});

const handleBankAccountSelect = () => {
    if (form.bank_account_id) {
        form.payment_method = 'bank';
    }
};

const canSubmit = computed(() => {
    return !!form.amount && !!form.bank_account_id && !!form.sale_id && !form.processing;
});

const salesWithDue = computed(() => {
    return props.sales.filter(sale => {
        const due = parseFloat(sale.due || 0)
        return due > 0
    })
})

const selectedSale = computed(() => {
    if (!form.sale_id) return null
    return props.sales.find(sale => sale.id === form.sale_id) || null
})

const selectedSaleDue = computed(() => {
    if (!selectedSale.value) return 0
    return parseFloat(selectedSale.value.due || 0)
})

const formatNumber = (value) => {
    if (!value) return '0.00'
    if (typeof value === 'string') {
        value = parseFloat(value)
    }
    return value.toLocaleString('bn-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const submitPayment = () => {
    if (!canSubmit.value) return;

    if (selectedSale.value && parseFloat(form.amount) > selectedSaleDue.value) {
        form.amount = selectedSaleDue.value;
    }

    form.post(route('admin.customers.add-payment', props.customer.id), {
        preserveScroll: true,
        onSuccess: () => {
            window.location.reload();
        }
    });
};

watch(() => form.sale_id, (newValue) => {
    if (newValue && selectedSale.value) {
        form.amount = selectedSale.value.due
    } else {
        form.amount = ''
    }
})
</script>
