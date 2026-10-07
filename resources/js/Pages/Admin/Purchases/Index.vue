<template>
    <Head :title="t('পণ্য ক্রয় তালিকা', 'Purchases')" />
    <AdminLayout :title="t('ক্রয় ব্যবস্থাপনা', 'Purchase Management')">
        <div class="space-y-4 sm:space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4 mb-4 sm:mb-6">
                <div class="bg-white dark:bg-gray-800 p-3.5 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm">
                    <p class="text-[11px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider truncate">{{ t('মোট ক্রয় সংখ্যা', 'Total Purchases') }}</p>
                    <h3 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white mt-1">{{ stats.total_count }}</h3>
                </div>
                <div class="bg-white dark:bg-gray-800 p-3.5 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm">
                    <p class="text-[11px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider truncate">{{ t('মোট ক্রয় মূল্য', 'Total Amount') }}</p>
                    <h3 class="text-lg sm:text-2xl font-black text-blue-600 mt-1 truncate">৳{{ formatNumber(stats.total_purchases_amount) }}</h3>
                </div>
                <div class="bg-white dark:bg-gray-800 p-3.5 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm">
                    <p class="text-[11px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider truncate">{{ t('মোট পরিশোধ', 'Total Paid') }}</p>
                    <h3 class="text-lg sm:text-2xl font-black text-emerald-600 mt-1 truncate">৳{{ formatNumber(stats.total_paid) }}</h3>
                </div>
                <div class="bg-white dark:bg-gray-800 p-3.5 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm">
                    <p class="text-[11px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider truncate">{{ t('মোট বকেয়া', 'Total Due') }}</p>
                    <h3 class="text-lg sm:text-2xl font-black text-rose-600 mt-1 truncate">৳{{ formatNumber(stats.total_due) }}</h3>
                </div>
            </div>

            <!-- Actions & Filters -->
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 mb-4 sm:mb-6">
                <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5 w-full sm:w-auto">
                    <div class="relative w-full sm:w-64">
                        <input
                            type="text"
                            v-model="search"
                            :placeholder="t('ক্রয় নং বা সরবরাহকারী...', 'Purchase # or Supplier...')"
                            class="w-full pl-10 pr-4 py-2 text-xs font-medium border rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                        />
                        <SearchIcon class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
                    </div>

                    <div class="grid grid-cols-2 sm:flex items-center gap-2">
                        <select
                            v-model="filters.payment_status"
                            class="border text-xs font-medium rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                        >
                            <option value="">{{ t('সকল পরিশোধ অবস্থা', 'All Status') }}</option>
                            <option value="paid">{{ t('পরিশোধিত', 'Paid') }}</option>
                            <option value="partial">{{ t('আংশিক', 'Partial') }}</option>
                            <option value="due">{{ t('বকেয়া', 'Due') }}</option>
                        </select>

                        <div class="flex items-center gap-1 sm:hidden">
                            <input
                                type="date"
                                v-model="filters.from_date"
                                class="w-full border text-[11px] rounded-xl px-2 py-2 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                :title="t('শুরুর তারিখ', 'From Date')"
                            />
                        </div>
                    </div>

                    <div class="hidden sm:flex items-center gap-2">
                        <input
                            type="date"
                            v-model="filters.from_date"
                            class="border text-xs rounded-xl px-3 py-2 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                            :title="t('শুরুর তারিখ', 'From Date')"
                        />
                        <input
                            type="date"
                            v-model="filters.to_date"
                            class="border text-xs rounded-xl px-3 py-2 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                            :title="t('শেষ তারিখ', 'To Date')"
                        />
                    </div>
                </div>

                <Link
                    :href="route('admin.product-stocks.create')"
                    class="w-full sm:w-auto justify-center px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 active:scale-95 flex items-center gap-2 text-xs font-bold shadow-sm transition"
                >
                    <PlusIcon class="w-4 h-4" />
                    <span>{{ t('নতুন ক্রয় যোগ করুন', 'New Purchase') }}</span>
                </Link>
            </div>

            <!-- Table & Mobile Cards -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden">
                <!-- Mobile Purchase Cards (< md) -->
                <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700 p-3 space-y-3">
                    <div v-if="purchases.data.length === 0" class="py-10 text-center text-xs text-gray-400">
                        {{ t('কোনো ক্রয় রেকর্ড পাওয়া যায়নি।', 'No purchase orders found.') }}
                    </div>

                    <div v-for="p in purchases.data" :key="p.id" class="pt-3 first:pt-0 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <Link :href="route('admin.purchases.show', p.id)" class="text-xs font-bold text-blue-600 hover:underline">
                                    {{ p.purchase_number }}
                                </Link>
                                <p class="text-xs font-semibold text-gray-900 dark:text-white mt-0.5">
                                    {{ p.supplier?.name || t('সরবরাহকারী নেই', 'N/A') }}
                                </p>
                                <p v-if="p.supplier?.phone" class="text-[11px] text-gray-400">
                                    {{ p.supplier.phone }}
                                </p>
                            </div>
                            <span
                                class="px-2 py-0.5 text-[10px] font-bold rounded-full shrink-0"
                                :class="{
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': p.payment_status === 'paid',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': p.payment_status === 'partial',
                                    'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400': p.payment_status === 'due',
                                }"
                            >
                                {{ p.payment_status === 'paid' ? t('পরিশোধিত', 'Paid') : p.payment_status === 'partial' ? t('আংশিক', 'Partial') : t('বকেয়া', 'Due') }}
                            </span>
                        </div>

                        <!-- 3-Column Amount Grid -->
                        <div class="grid grid-cols-3 gap-2 bg-gray-50 dark:bg-gray-750 p-2.5 rounded-xl text-xs">
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase tracking-wider block font-semibold">{{ t('মোট', 'Total') }}</span>
                                <span class="font-extrabold text-gray-900 dark:text-white mt-0.5 block tabular-nums">
                                    ৳{{ formatNumber(p.total_amount) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase tracking-wider block font-semibold">{{ t('পরিশোধ', 'Paid') }}</span>
                                <span class="font-bold text-emerald-600 mt-0.5 block tabular-nums">
                                    ৳{{ formatNumber(p.paid_amount) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase tracking-wider block font-semibold">{{ t('বকেয়া', 'Due') }}</span>
                                <span class="font-bold text-rose-600 mt-0.5 block tabular-nums">
                                    ৳{{ formatNumber(p.due_amount) }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="flex items-center justify-between pt-1 text-[11px] text-gray-400">
                            <span>{{ p.purchase_date }}</span>
                            <div class="flex items-center gap-1.5">
                                <Link
                                    :href="route('admin.purchases.show', p.id)"
                                    class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg active:scale-90 transition"
                                >
                                    <EyeIcon class="w-4 h-4" />
                                </Link>
                                <button
                                    @click="deletePurchase(p)"
                                    class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg active:scale-90 transition"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table (md+) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('ক্রয় নং', 'Purchase #') }}</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('সরবরাহকারী', 'Supplier') }}</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('তারিখ', 'Date') }}</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 dark:text-gray-300">{{ t('মোট (৳)', 'Total (৳)') }}</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 dark:text-gray-300">{{ t('পরিশোধিত (৳)', 'Paid (৳)') }}</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 dark:text-gray-300">{{ t('বকেয়া (৳)', 'Due (৳)') }}</th>
                                <th class="px-6 py-3.5 text-center font-bold text-gray-600 dark:text-gray-300">{{ t('অবস্থা', 'Status') }}</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 dark:text-gray-300">{{ t('অ্যাকশন', 'Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="p in purchases.data" :key="p.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition">
                                <td class="px-6 py-3.5 font-bold text-blue-600">
                                    <Link :href="route('admin.purchases.show', p.id)" class="hover:underline">{{ p.purchase_number }}</Link>
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ p.supplier?.name }}</div>
                                    <div class="text-xs text-gray-500">{{ p.supplier?.phone }}</div>
                                </td>
                                <td class="px-6 py-3.5 text-gray-600 dark:text-gray-300 font-medium">{{ p.purchase_date }}</td>
                                <td class="px-6 py-3.5 text-right font-black text-gray-900 dark:text-white">৳{{ formatNumber(p.total_amount) }}</td>
                                <td class="px-6 py-3.5 text-right text-emerald-600 font-bold">৳{{ formatNumber(p.paid_amount) }}</td>
                                <td class="px-6 py-3.5 text-right">
                                    <span v-if="p.due_amount > 0" class="text-rose-600 font-black">৳{{ formatNumber(p.due_amount) }}</span>
                                    <span v-else class="text-emerald-600 font-bold">৳0.00</span>
                                </td>
                                <td class="px-6 py-3.5 text-center">
                                    <span
                                        class="px-2.5 py-1 text-xs font-bold rounded-full"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': p.payment_status === 'paid',
                                            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': p.payment_status === 'partial',
                                            'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400': p.payment_status === 'due',
                                        }"
                                    >
                                        {{ p.payment_status === 'paid' ? t('পরিশোধিত', 'Paid') : p.payment_status === 'partial' ? t('আংশিক', 'Partial') : t('বকেয়া', 'Due') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right space-x-2">
                                    <Link
                                        :href="route('admin.purchases.show', p.id)"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg inline-block transition"
                                        :title="t('চালান দেখুন', 'View Invoice')"
                                    >
                                        <EyeIcon class="w-4 h-4 inline" />
                                    </Link>
                                    <button
                                        @click="deletePurchase(p)"
                                        class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg inline-block transition"
                                        :title="t('বাতিল / স্টক রিভার্ট', 'Cancel / Revert')"
                                    >
                                        <TrashIcon class="w-4 h-4 inline" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="purchases.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-400 font-medium">
                                    {{ t('কোনো ক্রয় রেকর্ড পাওয়া যায়নি। স্টক ইন করতে "নতুন ক্রয় যোগ করুন" বোতামে চাপুন।', 'No purchase orders found. Click "New Purchase" to add stock.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="purchases.links && purchases.links.length > 3" class="px-4 sm:px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="purchases.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { useLanguage } from '@/composables/useLanguage'
import debounce from 'lodash/debounce'
import {
    Search as SearchIcon,
    Plus as PlusIcon,
    Eye as EyeIcon,
    Trash as TrashIcon
} from 'lucide-vue-next'

const { t } = useLanguage()

const props = defineProps({
    purchases: Object,
    filters: Object,
    stats: Object,
})

const search = ref(props.filters?.search || '')
const filters = ref({
    search: props.filters?.search || '',
    payment_status: props.filters?.payment_status || '',
    from_date: props.filters?.from_date || '',
    to_date: props.filters?.to_date || '',
})

const formatNumber = (val) => {
    return Number(val || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}

const applyFilters = debounce(() => {
    router.get(route('admin.purchases.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    })
}, 300)

watch(search, (val) => {
    filters.value.search = val
    applyFilters()
})

watch(() => [filters.value.payment_status, filters.value.from_date, filters.value.to_date], () => {
    applyFilters()
})

const deletePurchase = (p) => {
    const confirmMsg = t(
        `আপনি কি নিশ্চিত যে ক্রয় চালান #${p.purchase_number} বাতিল করতে চান? এতে যুক্ত স্টক বাদ যাবে এবং সরবরাহকারীর বকেয়া বা ব্যাংক ব্যালেন্স সমন্বয় হবে (যদি পণ্য বিক্রি না হয়ে থাকে)।`,
        `Are you sure you want to cancel Purchase #${p.purchase_number}? This will revert stock and adjust supplier due/bank balance if not yet sold.`
    )
    if (confirm(confirmMsg)) {
        router.delete(route('admin.purchases.destroy', p.id), {
            onError: (errors) => {
                const err = Object.values(errors)[0] || t('ক্রয় বাতিল করা সম্ভব হয়নি', 'Failed to cancel purchase')
                alert('❌ ' + err)
            }
        })
    }
}
</script>
