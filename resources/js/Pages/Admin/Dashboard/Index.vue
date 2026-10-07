<template>
    <AdminLayout>
        <Head :title="t('ড্যাশবোর্ড', 'Dashboard')" />

        <div class="min-h-[calc(100vh-4rem)] bg-slate-50 dark:bg-slate-950 transition-colors">
            <div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8 py-3 sm:py-6 space-y-6 sm:space-y-8">
                <!-- Page Header & Date Filter Bar -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pb-3 sm:pb-4 border-b border-slate-200/80 dark:border-slate-800">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ t('ব্যবসায়িক ড্যাশবোর্ড', 'Business Dashboard') }}
                        </h1>
                        <p class="mt-0.5 sm:mt-1 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                            <i class="far fa-calendar-alt text-indigo-500"></i>
                            <span>{{ filters.periodLabel }}</span>
                        </p>
                    </div>

                    <!-- Quick Period Presets (Touch-scrollable chips on mobile) -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 -mx-2 px-2 sm:mx-0 sm:px-0">
                        <button
                            type="button"
                            class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-bold transition-all"
                            :class="preset === 'today'
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800'"
                            @click="applyPreset('today')"
                        >
                            {{ t('আজ', 'Today') }}
                        </button>
                        <button
                            type="button"
                            class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-bold transition-all"
                            :class="preset === 'month'
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800'"
                            @click="applyPreset('month')"
                        >
                            {{ t('এই মাস', 'This Month') }}
                        </button>
                        <button
                            type="button"
                            class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-bold transition-all"
                            :class="preset === '30d'
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800'"
                            @click="applyPreset('30d')"
                        >
                            {{ t('গত ৩০ দিন', 'Last 30 Days') }}
                        </button>
                        <button
                            type="button"
                            class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-1.5 text-xs font-bold text-slate-700 border border-slate-200 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800"
                            @click="router.reload()"
                            :title="t('রিফ্রেশ করুন', 'Refresh')"
                        >
                            <ArrowPathIcon class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline">{{ t('রিফ্রেশ', 'Refresh') }}</span>
                        </button>
                    </div>
                </div>

                <!-- KPI Metric Cards Grid (2 columns on mobile, 4 on desktop) -->
                <div class="grid grid-cols-2 gap-2.5 sm:gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="(card, idx) in metricCards"
                        :key="card.key"
                        class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-3.5 sm:p-5 shadow-xs transition-all hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-center justify-between">
                            <div
                                class="flex h-9 w-9 sm:h-11 sm:w-11 items-center justify-center rounded-xl text-white shadow-xs"
                                :class="card.iconBg"
                            >
                                <component :is="card.icon" class="h-4.5 w-4.5 sm:h-5 sm:w-5" aria-hidden="true" />
                            </div>
                            <span class="text-[10px] sm:text-xs font-mono font-bold px-1.5 py-0.5 sm:px-2 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                #{{ idx + 1 }}
                            </span>
                        </div>

                        <div class="mt-3 sm:mt-4">
                            <p class="text-[11px] sm:text-xs font-semibold text-slate-500 dark:text-slate-400 line-clamp-1">
                                {{ t(card.labelBn, card.labelEn) }}
                            </p>
                            <p class="mt-1 text-base sm:text-2xl font-extrabold tabular-nums tracking-tight text-slate-900 dark:text-white truncate">
                                <template v-if="card.format === 'currency'">
                                    {{ formatCurrency(stats[card.key]) }}
                                </template>
                                <template v-else>
                                    {{ Number(stats[card.key] ?? 0).toLocaleString() }}
                                </template>
                            </p>
                            <p v-if="card.hintBn" class="mt-1 text-[10px] sm:text-[11px] text-slate-400 dark:text-slate-500 font-medium line-clamp-1">
                                {{ t(card.hintBn, card.hintEn) }}
                            </p>
                        </div>

                        <div
                            class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full opacity-5 transition-transform group-hover:scale-125"
                            :class="card.blob"
                        />
                    </article>
                </div>

                <!-- Quick Action Hub -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
                            {{ t('কুইক এক্সেস ও শর্টকাট', 'Quick Access & Shortcuts') }}
                        </h2>
                        <span class="text-[11px] sm:text-xs text-slate-400">{{ t('জরুরি কাজগুলো সহজে করুন', 'Frequent actions') }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
                        <Link
                            v-for="link in quickLinks"
                            :key="link.href"
                            :href="link.href"
                            class="flex items-center gap-3 sm:gap-3.5 rounded-2xl border border-slate-200/90 bg-white p-3 sm:p-4 shadow-xs transition-all hover:border-indigo-400 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 group"
                        >
                            <div
                                class="flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl transition-colors"
                                :class="link.iconBg || 'bg-slate-100 text-slate-600 group-hover:bg-indigo-600 group-hover:text-white dark:bg-slate-800 dark:text-slate-300'"
                            >
                                <component :is="link.icon" class="h-5 w-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ t(link.titleBn, link.titleEn) }}
                                </p>
                                <p class="truncate text-[11px] sm:text-xs text-slate-400 mt-0.5">
                                    {{ t(link.subBn, link.subEn) }}
                                </p>
                            </div>
                            <ChevronRightIcon class="h-4 w-4 shrink-0 text-slate-300 group-hover:text-indigo-600 group-hover:translate-x-0.5 transition-all" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
    ArrowPathIcon,
    BanknotesIcon,
    ChartBarIcon,
    ChevronRightIcon,
    CurrencyDollarIcon,
    CubeIcon,
    ShoppingCartIcon,
    BuildingStorefrontIcon,
    DocumentChartBarIcon,
} from '@heroicons/vue/24/outline'
import { format, startOfMonth, subDays } from 'date-fns'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { formatCurrency } from '@/utils'
import { useLanguage } from '@/composables/useLanguage'

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
})

const { t } = useLanguage()

const preset = computed(() => {
    const s = props.filters?.startDate
    const e = props.filters?.endDate
    if (!s || !e) return 'month'
    const today = format(new Date(), 'yyyy-MM-dd')
    if (s === e && s === today) return 'today'
    const monthStart = format(startOfMonth(new Date()), 'yyyy-MM-dd')
    if (s === monthStart && e === today) return 'month'
    const thirty = format(subDays(new Date(), 30), 'yyyy-MM-dd')
    if (s === thirty && e === today) return '30d'
    return 'custom'
})

const metricCards = computed(() => [
    {
        key: 'sales_total',
        labelBn: 'মোট বিক্রি (Total Sales)',
        labelEn: 'Total Sales',
        format: 'currency',
        icon: CurrencyDollarIcon,
        iconBg: 'bg-emerald-600',
        blob: 'bg-emerald-500',
        hintBn: `${props.stats.sales_count ?? 0} টি ইনভয়েস কাটা হয়েছে`,
        hintEn: `${props.stats.sales_count ?? 0} invoices created`,
    },
    {
        key: 'buy_total',
        labelBn: 'মোট ক্রয় / মাল কেনা',
        labelEn: 'Total Buy / Purchase',
        format: 'currency',
        icon: ShoppingCartIcon,
        iconBg: 'bg-indigo-600',
        blob: 'bg-indigo-500',
        hintBn: 'নির্দিষ্ট সময়ে মোট ক্রয়কৃত স্টক',
        hintEn: 'Purchases in selected period',
    },
    {
        key: 'net_profit',
        labelBn: 'আনুমানিক নিট লাভ',
        labelEn: 'Estimated Net Profit',
        format: 'currency',
        icon: BanknotesIcon,
        iconBg: 'bg-violet-600',
        blob: 'bg-violet-500',
        hintBn: 'বিক্রি - ক্রয়মূল্য - খরচ',
        hintEn: 'Revenue - COGS - Expenses',
    },
    {
        key: 'bank_balance',
        labelBn: 'ক্যাশ ও ব্যাংক ব্যালেন্স',
        labelEn: 'Cash & Bank Balance',
        format: 'currency',
        icon: BuildingStorefrontIcon,
        iconBg: 'bg-sky-600',
        blob: 'bg-sky-500',
        hintBn: 'সকল সক্রিয় অ্যাকাউন্টের ব্যালেন্স',
        hintEn: 'Current total liquidity',
    },
    {
        key: 'sales_due',
        labelBn: 'কাস্টমার মোট বাকি (Due)',
        labelEn: 'Customer Outstanding Due',
        format: 'currency',
        icon: DocumentChartBarIcon,
        iconBg: 'bg-amber-500',
        blob: 'bg-amber-500',
        hintBn: 'অনাদায়ী বিক্রির টাকা',
        hintEn: 'Uncollected sales amount',
    },
    {
        key: 'expenses_total',
        labelBn: 'দোকানের মোট খরচ',
        labelEn: 'Total Store Expenses',
        format: 'currency',
        icon: ChartBarIcon,
        iconBg: 'bg-rose-600',
        blob: 'bg-rose-500',
        hintBn: 'দৈনন্দিন পরিচালন খরচ',
        hintEn: 'Operating store expenses',
    },
    {
        key: 'extra_income',
        labelBn: 'বাড়তি অন্যান্য আয়',
        labelEn: 'Extra Other Income',
        format: 'currency',
        icon: CurrencyDollarIcon,
        iconBg: 'bg-teal-600',
        blob: 'bg-teal-500',
        hintBn: 'বিক্রি বহির্ভূত আয়',
        hintEn: 'Non-sales revenue',
    },
    {
        key: 'products_count',
        labelBn: 'মোট সক্রিয় পণ্য',
        labelEn: 'Active Catalog Products',
        format: 'number',
        icon: CubeIcon,
        iconBg: 'bg-slate-700',
        blob: 'bg-slate-600',
        hintBn: 'দোকানের মোট আইটেম সংখ্যা',
        hintEn: 'Total products in inventory',
    },
])

const quickLinks = [
    {
        titleBn: 'ক্যাশ কাউন্টার (POS)',
        titleEn: 'POS Terminal',
        subBn: 'নতুন বিক্রির রসিদ কাটুন',
        subEn: 'Create sale invoice',
        href: '/admin/pos',
        icon: ShoppingCartIcon,
        iconBg: 'bg-emerald-600 text-white'
    },
    {
        titleBn: 'বিক্রির তালিকা ও মেমো',
        titleEn: 'Sales List',
        subBn: 'সকল ইনভয়েস ও রসিদ হিস্ট্রি',
        subEn: 'View invoice history',
        href: '/admin/sales',
        icon: DocumentChartBarIcon,
        iconBg: 'bg-indigo-600 text-white'
    },
    {
        titleBn: 'পণ্য ও স্টক ম্যানেজমেন্ট',
        titleEn: 'Products & Inventory',
        subBn: 'পণ্য যোগ, মূল্য ও স্টক',
        subEn: 'Stock and pricing',
        href: '/admin/products',
        icon: CubeIcon,
        iconBg: 'bg-amber-600 text-white'
    },
    {
        titleBn: 'দোকানের খরচ এন্ট্রি',
        titleEn: 'Store Expenses',
        subBn: 'দৈনন্দিন খরচের ভাউচার',
        subEn: 'Record expense voucher',
        href: '/admin/expenses',
        icon: BanknotesIcon,
        iconBg: 'bg-rose-600 text-white'
    },
]

function applyPreset(key) {
    const end = new Date()
    let start = new Date()
    if (key === 'today') {
        start = end
    } else if (key === 'month') {
        start = startOfMonth(end)
    } else if (key === '30d') {
        start = subDays(end, 30)
    } else {
        return
    }
    router.get(
        '/admin/dashboard',
        {
            start_date: format(start, 'yyyy-MM-dd'),
            end_date: format(end, 'yyyy-MM-dd'),
        },
        { preserveScroll: true, preserveState: true },
    )
}
</script>
