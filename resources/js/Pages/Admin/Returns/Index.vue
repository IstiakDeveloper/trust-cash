<template>
    <AdminLayout :title="t('পণ্য ফেরত ও রিটার্ন', 'Sales Returns')">
        <Head :title="t('পণ্য ফেরত ও রিটার্ন', 'Sales Returns')" />

        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                        {{ t('পণ্য ফেরত ও রিটার্ন', 'Sales Returns') }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('কাস্টমার কর্তৃক ফেরতকৃত পণ্যের হিসাব ও রিফান্ড ব্যবস্থাপনা', 'Manage customer returns, restock inventory and handle refunds') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.returns.create')"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-all"
                    >
                        <PlusIcon class="h-4 w-4" />
                        <span>{{ t('নতুন পণ্য ফেরত', 'New Return') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ t('মোট রিটার্ন অর্ডার', 'Total Return Orders') }}
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ stats.total_count }}</h3>
                </div>
                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ t('মোট রিটার্ন মূল্য', 'Total Return Value') }}
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400">৳{{ formatNumber(stats.total_returns_amount) }}</h3>
                </div>
                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ t('মোট রিফান্ড প্রদান', 'Total Refunded') }}
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-rose-600 dark:text-rose-400">৳{{ formatNumber(stats.total_refunded) }}</h3>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="relative w-full sm:w-80">
                    <input
                        type="text"
                        v-model="search"
                        :placeholder="t('রিটার্ন নং বা কাস্টমার খুঁজুন...', 'Search Return # or Customer...')"
                        class="w-full rounded-xl border-slate-200 bg-white pl-9 pr-4 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    />
                    <SearchIcon class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                </div>
            </div>

            <!-- Table (Responsive Hybrid: Mobile Cards on < md, Data Table on md+) -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-900">
                <!-- Mobile Cards (< md) -->
                <div class="md:hidden space-y-3 p-3">
                    <div
                        v-for="r in returns.data"
                        :key="'m-ret-' + r.id"
                        class="p-4 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 shadow-xs space-y-2.5"
                    >
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60 dark:border-slate-700/60">
                            <div>
                                <Link :href="route('admin.returns.show', r.id)" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 font-mono">
                                    {{ r.return_number }}
                                </Link>
                                <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ r.return_date }}</p>
                            </div>
                            <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold"
                                :class="r.refund_status === 'credited_to_due'
                                    ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'
                                    : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'">
                                {{ r.refund_status === 'credited_to_due' ? t('বকেয়া সমন্বয়', 'Credited') : t('রিফান্ডকৃত', 'Refunded') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <div class="min-w-0 pr-2">
                                <p class="font-bold text-slate-900 dark:text-white truncate">
                                    {{ r.customer?.name || t('খুচরা কাস্টমার', 'Walk-in Customer') }}
                                </p>
                                <p class="text-[11px] text-slate-400 font-mono">
                                    {{ r.sale?.invoice_no ? ('Inv: ' + r.sale.invoice_no) : t('সরাসরি রিটার্ন', 'Manual Return') }}
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-[10px] text-slate-400">{{ t('ফেরত মূল্য', 'Total') }}</p>
                                <p class="text-xs font-extrabold text-slate-800 dark:text-slate-200">৳{{ formatNumber(r.total_amount) }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-200/60 dark:border-slate-700/60 text-xs">
                            <div>
                                <span class="text-[10px] text-slate-400 mr-1">{{ t('রিফান্ড:', 'Refund:') }}</span>
                                <span class="font-bold text-rose-600 dark:text-rose-400 font-mono">৳{{ formatNumber(r.refund_amount) }}</span>
                            </div>
                            <Link :href="route('admin.returns.show', r.id)" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 inline-flex items-center gap-1">
                                <span>{{ t('বিস্তারিত', 'View') }}</span>
                                <i class="fas fa-arrow-right text-[10px]"></i>
                            </Link>
                        </div>
                    </div>

                    <div v-if="returns.data.length === 0" class="text-center py-8 text-xs text-slate-400">
                        {{ t('কোনো পণ্য ফেরতের তথ্য পাওয়া যায়নি।', 'No sales returns found.') }}
                    </div>
                </div>

                <!-- Desktop Table (md+) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                            <tr>
                                <th class="px-5 py-3 text-left">{{ t('রিটার্ন নং', 'Return #') }}</th>
                                <th class="px-4 py-3 text-left">{{ t('মূল ইনভয়েস', 'Original Invoice') }}</th>
                                <th class="px-4 py-3 text-left">{{ t('কাস্টমার', 'Customer') }}</th>
                                <th class="px-4 py-3 text-left">{{ t('তারিখ', 'Date') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('মোট রিটার্ন', 'Total Returned') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('রিফান্ড টাকা', 'Refund Amount') }}</th>
                                <th class="px-4 py-3 text-center">{{ t('অবস্থা', 'Status') }}</th>
                                <th class="px-5 py-3 text-right">{{ t('অ্যাকশন', 'Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="r in returns.data" :key="r.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                <td class="px-5 py-3 font-bold text-indigo-600 dark:text-indigo-400">
                                    <Link :href="route('admin.returns.show', r.id)">{{ r.return_number }}</Link>
                                </td>
                                <td class="px-4 py-3 font-mono font-medium text-slate-700 dark:text-slate-300">
                                    {{ r.sale?.invoice_no || t('সরাসরি রিটার্ন', 'Manual Return') }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                    {{ r.customer?.name || t('খুচরা কাস্টমার', 'Walk-in Customer') }}
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ r.return_date }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-800 dark:text-slate-200">৳{{ formatNumber(r.total_amount) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-rose-600 dark:text-rose-400">৳{{ formatNumber(r.refund_amount) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold"
                                        :class="r.refund_status === 'credited_to_due'
                                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'
                                            : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'">
                                        {{ r.refund_status === 'credited_to_due' ? t('বকেয়া সমন্বয়', 'Credited') : t('রিফান্ডকৃত', 'Refunded') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <Link :href="route('admin.returns.show', r.id)" class="text-xs font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                        {{ t('বিস্তারিত', 'View') }}
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="returns.data.length === 0">
                                <td colspan="8" class="px-6 py-10 text-center text-xs font-medium text-slate-400">
                                    {{ t('কোনো পণ্য ফেরতের তথ্য পাওয়া যায়নি।', 'No sales returns found.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import debounce from 'lodash/debounce'
import { Search as SearchIcon, Plus as PlusIcon } from 'lucide-vue-next'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    returns: Object,
    filters: Object,
    stats: Object,
})

const search = ref(props.filters?.search || '')

const formatNumber = (val) => {
    return Number(val || 0).toLocaleString('bn-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}

watch(search, debounce((val) => {
    router.get(route('admin.returns.index'), { search: val }, {
        preserveState: true,
        preserveScroll: true,
    })
}, 300))
</script>
