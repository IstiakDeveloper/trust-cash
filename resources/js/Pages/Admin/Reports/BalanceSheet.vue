<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLanguage } from '@/composables/useLanguage'
const props = defineProps({ fund_and_liabilities: Object, property_and_assets: Object, filters: Object, reconciliation: Object, legacy_carry_forwards: { type: Array, default: () => [] } })
const { t, formatCurrency } = useLanguage()
const through = ref(props.filters.end_date)
const apply = () => router.get(route('admin.reports.balance-sheet'), { end_date: through.value })
const download = (format) => { window.location.href = route(`admin.reports.balance-sheet.${format}`) + '?' + new URLSearchParams({ end_date: props.filters.end_date }).toString() }
const sections = computed(() => [
    { title: t('সম্পদ', 'Assets'), data: props.property_and_assets },
    { title: t('দায় ও মূলধন', 'Liabilities and Equity'), data: props.fund_and_liabilities }
])
const differences = computed(() => Object.entries(props.reconciliation || {}).filter(([, value]) => Number(value) !== 0))
const printReport = () => window.print()
</script>
<template>
    <Head :title="t('স্থিতিপত্র', 'Balance Sheet')" />
    <AdminLayout :title="t('স্থিতিপত্র', 'Balance Sheet')">
        <div class="mx-auto max-w-7xl space-y-5">
            <form @submit.prevent="apply" class="print:hidden flex flex-wrap items-end gap-3 rounded-2xl bg-white dark:bg-slate-900 p-4 border dark:border-slate-800">
                <label class="text-xs dark:text-white">{{ t('এই তারিখে', 'As of') }}<input v-model="through" type="date" required class="block rounded-lg dark:bg-slate-800" /></label>
                <button class="rounded-xl bg-indigo-600 px-4 py-2 text-white text-sm">{{ t('দেখুন', 'Apply') }}</button>
                <button type="button" @click="download('pdf')" class="rounded-xl border px-4 py-2 text-sm dark:text-white">PDF</button>
                <button type="button" @click="download('excel')" class="rounded-xl border px-4 py-2 text-sm dark:text-white">Excel</button>
                <button type="button" @click="printReport" class="rounded-xl border px-4 py-2 text-sm dark:text-white">{{ t('প্রিন্ট', 'Print') }}</button>
            </form>
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ t('এই তারিখে আর্থিক অবস্থান', 'Financial position as of') }} {{ props.filters.end_date }}</p>
            <div v-if="differences.length" role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700"><p class="font-bold">{{ t('হিসাব মিলিয়ে দেখা প্রয়োজন', 'Reconciliation required') }}</p><p v-for="[name, value] in differences" :key="name">{{ name.replaceAll('_', ' ') }}: {{ formatCurrency(value) }}</p></div>
            <details v-if="legacy_carry_forwards.length" class="rounded-xl border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                <summary class="cursor-pointer font-semibold">{{ t('?????? ????? ???? ???? ???? opening balance', 'Opening balances preserved from previous records') }}</summary>
                <p class="mt-2 text-slate-500">{{ t('???????? ????????? ??? ????? ?????? ???????? ???????? ????? opening equity ?????? ???? ?????? ??? ?? ?? ??? ???', 'Differences between stored balances and available historical transactions are recorded separately in opening equity, outside income and expenses.') }}</p>
                <table class="mt-3 w-full text-left"><thead><tr><th>{{ t('?????', 'Account') }}</th><th>{{ t('???? ?????????', 'Preserved balance') }}</th><th>{{ t('Opening ????????', 'Opening difference') }}</th></tr></thead><tbody><tr v-for="row in legacy_carry_forwards" :key="row.account"><td class="py-2">{{ row.name }} ? {{ row.date }}</td><td>{{ formatCurrency(row.stored_balance) }}</td><td>{{ formatCurrency(row.difference) }}</td></tr></tbody></table>
            </details>
            <div class="grid gap-5 lg:grid-cols-2">
                <section v-for="section in sections" :key="section.title" class="rounded-2xl border dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
                    <h2 class="p-4 font-bold text-slate-900 dark:text-white border-b dark:border-slate-800">{{ section.title }}</h2>
                    <table class="w-full text-sm text-left dark:text-slate-200"><tbody><tr v-for="row in section.data.rows" :key="row.name" class="border-b dark:border-slate-800"><td class="p-3">{{ row.name }}</td><td class="p-3 text-right tabular-nums">{{ formatCurrency(row.amount) }}</td></tr></tbody><tfoot><tr class="font-bold bg-slate-50 dark:bg-slate-800"><td class="p-3">{{ t('মোট', 'Total') }}</td><td class="p-3 text-right">{{ formatCurrency(section.data.total) }}</td></tr></tfoot></table>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
