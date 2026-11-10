<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLanguage } from '@/composables/useLanguage'
const props = defineProps({ receipt: Object, payment: Object, filters: Object, bankAccounts: Array, reconciliation_difference: String })
const { t, formatCurrency } = useLanguage()
const filters = ref({ ...props.filters, bank_account_id: props.filters.bank_account_id || 'all' })
const apply = () => router.get(route('admin.reports.receipt-payment'), filters.value, { preserveState: true })
const download = (format) => { window.location.href = route(`admin.reports.receipt-payment.${format}`) + '?' + new URLSearchParams(props.filters).toString() }
const printReport = () => window.print()
const sections = computed(() => [
    { title: t('প্রাপ্তি', 'Receipts'), rows: [{ name: t('শুরুর নগদ ও ব্যাংক', 'Opening cash and bank'), ...props.receipt.opening_cash_on_bank }, ...props.receipt.rows], total: props.receipt.total },
    { title: t('পরিশোধ', 'Payments'), rows: [...props.payment.rows, { name: t('শেষের নগদ ও ব্যাংক', 'Closing cash and bank'), ...props.payment.closing_cash_at_bank }], total: props.payment.total }
])
</script>
<template>
    <Head :title="t('প্রাপ্তি ও পরিশোধ', 'Receipts and Payments')" />
    <AdminLayout :title="t('প্রাপ্তি ও পরিশোধ', 'Receipts and Payments')">
        <div class="mx-auto max-w-7xl space-y-5">
            <form @submit.prevent="apply" class="print:hidden flex flex-wrap items-end gap-3 rounded-2xl bg-white dark:bg-slate-900 p-4 border dark:border-slate-800">
                <label class="text-xs dark:text-white">{{ t('শুরুর তারিখ', 'From') }}<input v-model="filters.start_date" type="date" required class="block rounded-lg dark:bg-slate-800" /></label>
                <label class="text-xs dark:text-white">{{ t('শেষের তারিখ', 'Through') }}<input v-model="filters.end_date" type="date" required class="block rounded-lg dark:bg-slate-800" /></label>
                <label class="text-xs dark:text-white">{{ t('হিসাব', 'Account') }}<select v-model="filters.bank_account_id" class="block rounded-lg dark:bg-slate-800"><option value="all">{{ t('সব হিসাব', 'All accounts') }}</option><option v-for="account in bankAccounts" :key="account.id" :value="account.id">{{ account.account_name }} · {{ account.bank_name }}</option></select></label>
                <button class="rounded-xl bg-indigo-600 px-4 py-2 text-white text-sm">{{ t('দেখুন', 'Apply') }}</button>
                <button type="button" @click="download('pdf')" class="rounded-xl border px-4 py-2 text-sm dark:text-white">PDF</button>
                <button type="button" @click="download('excel')" class="rounded-xl border px-4 py-2 text-sm dark:text-white">Excel</button>
                <button type="button" @click="printReport" class="rounded-xl border px-4 py-2 text-sm dark:text-white">{{ t('প্রিন্ট', 'Print') }}</button>
            </form>
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ props.filters.start_date }} — {{ props.filters.end_date }}</p>
            <p v-if="Number(reconciliation_difference) !== 0" role="alert" class="rounded-xl bg-red-50 p-3 text-red-700">{{ t('হিসাবের অমিল', 'Reconciliation difference') }}: {{ formatCurrency(reconciliation_difference) }}</p>
            <div class="grid gap-5 lg:grid-cols-2">
                <section v-for="section in sections" :key="section.title" class="rounded-2xl border dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
                    <h2 class="p-4 font-bold text-slate-900 dark:text-white border-b dark:border-slate-800">{{ section.title }}</h2>
                    <div class="overflow-x-auto"><table class="w-full text-sm text-left dark:text-slate-200">
                        <thead class="bg-slate-50 dark:bg-slate-800"><tr><th class="p-3">{{ t('বিবরণ', 'Particulars') }}</th><th class="p-3 text-right">{{ t('চলতি সময়', 'Period') }}</th><th class="p-3 text-right">{{ t('ক্রমপুঞ্জিত', 'Cumulative') }}</th></tr></thead>
                        <tbody><tr v-for="(row, i) in section.rows" :key="i" class="border-b dark:border-slate-800"><td class="p-3">{{ row.name }}</td><td class="p-3 text-right tabular-nums">{{ formatCurrency(row.period) }}</td><td class="p-3 text-right tabular-nums">{{ formatCurrency(row.cumulative) }}</td></tr></tbody>
                        <tfoot><tr class="font-bold bg-slate-50 dark:bg-slate-800"><td class="p-3">{{ t('মোট', 'Total') }}</td><td class="p-3 text-right">{{ formatCurrency(section.total.period) }}</td><td class="p-3 text-right">{{ formatCurrency(section.total.cumulative) }}</td></tr></tfoot>
                    </table></div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
