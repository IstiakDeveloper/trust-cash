<template>
    <AdminLayout>
        <Head :title="t('অনলাইন ও কাস্টমার অর্ডার', 'Orders')" />

        <div class="space-y-6">
            <Alert
                :show="activeAlert.show"
                :type="activeAlert.type"
                :title="activeAlert.title"
                :message="activeAlert.message"
                @close="clearFlash"
            />

            <!-- Page Header -->
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                        {{ t('অনলাইন ও পেন্ডিং অর্ডার', 'Customer Orders') }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('অপেক্ষমাণ অর্ডারসমূহ যাচাই করে অনুমোদন (Approve) বা বাতিল (Reject) করুন', 'Pending orders can be approved or rejected.') }}
                    </p>
                </div>
                <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                    <div class="flex overflow-x-auto no-scrollbar rounded-xl bg-white p-1 shadow-xs border border-slate-200 dark:bg-slate-800 dark:border-slate-700 max-w-full">
                        <button type="button" @click="setStatus('pending')" :class="tabClass('pending')" class="whitespace-nowrap px-3 py-1.5 text-xs font-semibold rounded-lg transition">
                            {{ t('অপেক্ষমাণ (Pending)', 'Pending') }}
                        </button>
                        <button type="button" @click="setStatus('approved')" :class="tabClass('approved')" class="whitespace-nowrap px-3 py-1.5 text-xs font-semibold rounded-lg transition">
                            {{ t('অনুমোদিত (Approved)', 'Approved') }}
                        </button>
                        <button type="button" @click="setStatus('rejected')" :class="tabClass('rejected')" class="whitespace-nowrap px-3 py-1.5 text-xs font-semibold rounded-lg transition">
                            {{ t('বাতিলকৃত (Rejected)', 'Rejected') }}
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 sm:w-64">
                            <input v-model="q" @keydown.enter.prevent="applyFilters" type="text"
                                :placeholder="t('অর্ডার বা কাস্টমার খুঁজুন...', 'Search order/customer...')"
                                class="block w-full min-h-[40px] rounded-xl border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700" />
                        </div>
                        <button type="button" @click="applyFilters" :disabled="isLoading"
                            class="min-h-[40px] rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-500 shadow-xs shrink-0">
                            <span v-if="isLoading && loadingAction === 'filter'" class="inline-flex items-center gap-1.5">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>{{ t('খুঁজছে...', 'Applying...') }}</span>
                            </span>
                            <span v-else>{{ t('ফিল্টার', 'Apply') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Compact list for Approved / Rejected -->
            <div v-if="pendingSales.data.length && filters.status !== 'pending'" class="mt-4 sm:mt-6">
                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <!-- Mobile List (md:hidden) -->
                    <div class="md:hidden divide-y divide-gray-100 dark:divide-slate-800">
                        <div v-for="o in pendingSales.data" :key="'m-' + o.id" class="p-3.5 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white">{{ o.public_order_no }}</span>
                                    <span class="text-[11px] text-slate-400 block">{{ o.date }}</span>
                                </div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ formatCurrency(o.total) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-300 pt-1 border-t border-slate-50 dark:border-slate-800">
                                <div>
                                    <span v-if="o.customer" class="font-medium text-slate-800 dark:text-slate-200">
                                        {{ o.customer.name }} ({{ o.customer.phone }})
                                    </span>
                                    <span v-else class="text-slate-400">—</span>
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ filters.status === 'approved' ? (o.approved_at || '—') : (o.rejected_at || '—') }}
                                </div>
                            </div>
                            <div v-if="filters.status === 'approved' && o.sale_id" class="pt-1 flex justify-end">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                    @click="printReceipt(o.sale_id)"
                                >
                                    <i class="fas fa-print text-[11px]"></i>
                                    <span>{{ t('রসিদ প্রিন্ট', 'Print Receipt') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop Table (hidden md:block) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                                <tr>
                                    <th class="px-5 py-3 text-left">{{ t('অর্ডার নং', 'Order No') }}</th>
                                    <th class="px-4 py-3 text-left">{{ t('কাস্টমার', 'Customer') }}</th>
                                    <th class="px-4 py-3 text-left">
                                        {{ filters.status === 'approved' ? t('অনুমোদনের তারিখ', 'Approved at') : t('বাতিলের তারিখ', 'Rejected at') }}
                                    </th>
                                    <th class="px-4 py-3 text-right">{{ t('মোট টাকা', 'Total') }}</th>
                                    <th class="px-5 py-3 text-right">{{ t('অ্যাকশন', 'Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="o in pendingSales.data" :key="o.id" class="hover:bg-gray-50/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3">
                                        <div class="text-[13px] font-semibold text-gray-900 dark:text-white">{{ o.public_order_no }}</div>
                                        <div class="text-[12px] text-gray-500 dark:text-gray-400">{{ o.date }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="o.customer" class="text-[13px] text-gray-900 dark:text-white">
                                            {{ o.customer.name }}
                                        </div>
                                        <div v-if="o.customer" class="text-[12px] text-gray-500 dark:text-gray-400">{{ o.customer.phone }}</div>
                                        <div v-else class="text-[13px] text-gray-500 dark:text-gray-400">—</div>
                                    </td>
                                    <td class="px-4 py-3 text-[13px] text-gray-700 dark:text-gray-300">
                                        {{ filters.status === 'approved' ? (o.approved_at || '—') : (o.rejected_at || '—') }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-[13px] font-semibold text-gray-900 dark:text-white">
                                        {{ formatCurrency(o.total) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <button
                                                v-if="filters.status === 'approved' && o.sale_id"
                                                type="button"
                                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                                @click="printReceipt(o.sale_id)"
                                            >
                                                <i class="fas fa-print text-[11px]"></i>
                                                <span class="hidden sm:inline">{{ t('রসিদ', 'Receipt') }}</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Detailed cards for Pending -->
            <div v-else-if="pendingSales.data.length" class="mt-6 sm:mt-8 space-y-4">
                <div v-for="o in pendingSales.data" :key="o.id"
                    class="rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-700 dark:bg-slate-900 space-y-3">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ o.public_order_no }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ o.date }}</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-700 dark:text-slate-300">
                                <span class="font-bold text-slate-900 dark:text-slate-100">{{ t('কাস্টমার:', 'Customer:') }}</span>
                                <span v-if="o.customer"> {{ o.customer.name }} ({{ o.customer.phone }})</span>
                                <span v-else> —</span>
                            </p>
                            <p v-if="o.note" class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                                <span class="font-bold text-slate-900 dark:text-slate-100">{{ t('নোট:', 'Note:') }}</span> {{ o.note }}
                            </p>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 lg:flex-col lg:items-end">
                            <div class="text-sm font-bold text-slate-900 dark:text-white lg:text-right">
                                {{ t('মোট:', 'Total:') }} {{ formatCurrency(o.total) }}
                            </div>
                            <template v-if="filters.status === 'pending'">
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="approve(o.id)" :disabled="isLoading"
                                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-emerald-500 disabled:opacity-60 shadow-xs">
                                        <span v-if="isLoading && loadingAction === 'approve' && loadingId === o.id" class="inline-flex items-center gap-2">
                                            <i class="fas fa-spinner fa-spin text-xs"></i>
                                            <span>{{ t('অনুমোদন হচ্ছে...', 'Approving...') }}</span>
                                        </span>
                                        <span v-else class="inline-flex items-center gap-2">
                                            <i class="fas fa-check text-xs"></i>
                                            <span>{{ t('অনুমোদন', 'Approve') }}</span>
                                        </span>
                                    </button>

                                    <button type="button" @click="reject(o.id)" :disabled="isLoading"
                                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-rose-500 disabled:opacity-60 shadow-xs">
                                        <span v-if="isLoading && loadingAction === 'reject' && loadingId === o.id" class="inline-flex items-center gap-2">
                                            <i class="fas fa-spinner fa-spin text-xs"></i>
                                            <span>{{ t('বাতিল হচ্ছে...', 'Rejecting...') }}</span>
                                        </span>
                                        <span v-else class="inline-flex items-center gap-2">
                                            <i class="fas fa-ban text-xs"></i>
                                            <span>{{ t('বাতিল', 'Reject') }}</span>
                                        </span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-[700px] w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                                    <tr>
                                        <th class="px-3 py-2.5 text-left">{{ t('পণ্য', 'Product') }}</th>
                                        <th class="px-3 py-2.5 text-right">{{ t('পরিমাণ', 'Qty') }}</th>
                                        <th class="px-3 py-2.5 text-right">{{ t('দর', 'Unit Price') }}</th>
                                        <th class="px-3 py-2.5 text-right">{{ t('মোট', 'Subtotal') }}</th>
                                        <th class="px-3 py-2.5 text-right">{{ t('অ্যাকশন', 'Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="(it, idx) in o.items" :key="idx" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                        <td class="px-3 py-2 text-slate-900 dark:text-slate-100 font-medium">
                                            {{ it.product.name }} <span class="text-slate-400 font-mono text-[11px]">({{ it.product.sku }})</span>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <input
                                                v-if="filters.status === 'pending'"
                                                type="number"
                                                min="1"
                                                max="999"
                                                :value="it.quantity"
                                                class="w-20 rounded-lg border-slate-200 py-1 text-right text-xs font-semibold text-slate-900 focus:ring-1 focus:ring-indigo-600 dark:bg-slate-800 dark:text-slate-100 dark:border-slate-700"
                                                @change="updateItem(o.id, it.id, $event.target.value)"
                                            />
                                            <span v-else class="font-bold text-slate-800 dark:text-slate-200">{{ it.quantity }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-right text-slate-600 dark:text-slate-300">{{ formatCurrency(it.unit_price) }}</td>
                                        <td class="px-3 py-2 text-right font-bold text-slate-900 dark:text-slate-100">{{ formatCurrency(it.subtotal) }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <div v-if="filters.status === 'pending'" class="inline-flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    class="text-xs font-bold text-rose-600 hover:text-rose-500"
                                                    @click="removeItem(o.id, it.id)"
                                                >
                                                    {{ t('মুছুন', 'Remove') }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="filters.status === 'pending'" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                                <input v-model="addSearch[o.id]" type="text" :placeholder="t('যোগ করতে পণ্য খুঁজুন...', 'Search product to add...')"
                                    class="w-full sm:w-80 rounded-xl border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:bg-slate-800 dark:text-slate-100 dark:border-slate-700"
                                    @input="searchProducts(o.id)"
                                />
                                <div v-if="searchResults[o.id]?.length" class="relative w-full sm:w-80">
                                    <div class="absolute z-10 mt-2 w-full rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900 overflow-hidden">
                                        <button v-for="p in searchResults[o.id]" :key="p.id" type="button"
                                            class="w-full px-4 py-2 text-left text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-800 border-b border-slate-100 dark:border-slate-800 last:border-0"
                                            @click="addProductToOrder(o.id, p)"
                                        >
                                            <span class="font-bold text-slate-900 dark:text-white">{{ p.name }}</span> ({{ p.sku }}) — <span class="font-bold text-emerald-600">{{ formatCurrency(p.selling_price) }}</span> — {{ t('স্টক:', 'Stock:') }} {{ p.stock }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-10 rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('কোনো অর্ডার পাওয়া যায়নি।', 'No orders found.') }}</p>
                </div>

                <div class="mt-8">
                    <Pagination :links="pendingSales.links" />
                </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Alert from '@/Components/Alert.vue'
import { formatCurrency } from '@/utils'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    pendingSales: Object,
    filters: Object,
})

const page = usePage()
const addSearch = ref({})
const searchResults = ref({})
let searchTimers = {}
const flashSuccess = computed(() => page.props.flash?.success || '')
const flashError = computed(() => page.props.flash?.error || '')
const pageError = computed(() => page.props.errors?.error || '')

const localSuccess = ref('')
const localError = ref('')
let localTimer = null
let dismissTimer = null
const dismissed = ref(false)
const isLoading = ref(false)
const loadingAction = ref('')
const loadingId = ref(null)

const q = ref(props.filters?.q || '')

const activeAlert = computed(() => {
    // Show only ONE message at a time (highest priority first)
    const errorMessage = localError.value || pageError.value || flashError.value
    if (errorMessage) {
        return { show: !dismissed.value, type: 'error', title: t('ত্রুটি', 'Error'), message: errorMessage }
    }
    const successMessage = localSuccess.value || flashSuccess.value
    if (successMessage) {
        return { show: !dismissed.value, type: 'success', title: t('সফল', 'Success'), message: successMessage }
    }
    return { show: false, type: 'info', title: '', message: '' }
})

function clearFlash() {
    dismissed.value = true
    localSuccess.value = ''
    localError.value = ''
    if (localTimer) {
        clearTimeout(localTimer)
        localTimer = null
    }
    if (dismissTimer) {
        clearTimeout(dismissTimer)
        dismissTimer = null
    }
}

// Auto-dismiss after 2 seconds for any message (including flash/page errors)
watch(
    () => [localError.value, pageError.value, flashError.value, localSuccess.value, flashSuccess.value],
    () => {
        dismissed.value = false
        if (dismissTimer) clearTimeout(dismissTimer)
        const hasMessage =
            !!(localError.value || pageError.value || flashError.value || localSuccess.value || flashSuccess.value)
        if (hasMessage) {
            dismissTimer = setTimeout(() => {
                dismissed.value = true
            }, 2000)
        }
    },
    { immediate: true }
)

function tabClass(status) {
    const active = (props.filters?.status || 'pending') === status
    return [
        'px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all',
        active
            ? 'bg-indigo-600 text-white shadow-xs'
            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700',
    ]
}

function setStatus(status) {
    router.get(route('admin.pending-sales.index'), { status, q: q.value }, { preserveScroll: true, preserveState: true })
}

function applyFilters() {
    isLoading.value = true
    loadingAction.value = 'filter'
    loadingId.value = null
    router.get(route('admin.pending-sales.index'), { status: props.filters?.status || 'pending', q: q.value }, { preserveScroll: true, preserveState: true })
    setTimeout(() => { isLoading.value = false; loadingAction.value = ''; }, 500)
}

function approve(id) {
    isLoading.value = true
    loadingAction.value = 'approve'
    loadingId.value = id
    router.post(
        route('admin.pending-sales.approve', id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                localSuccess.value = t('অর্ডার সফলভাবে অনুমোদন করা হয়েছে।', 'Approved successfully.')
                if (localTimer) clearTimeout(localTimer)
                localTimer = setTimeout(() => (localSuccess.value = ''), 8000)
                isLoading.value = false
                loadingAction.value = ''
                loadingId.value = null
                router.reload({ preserveScroll: true, preserveState: true })
            },
            onError: (errors) => {
                localError.value = errors?.error || t('অনুমোদন ব্যর্থ হয়েছে।', 'Approve failed.')
                if (localTimer) clearTimeout(localTimer)
                localTimer = setTimeout(() => (localError.value = ''), 12000)
                isLoading.value = false
                loadingAction.value = ''
                loadingId.value = null
            },
        }
    )
}

function reject(id) {
    if (!confirm(t('এই অর্ডারটি কি বাতিল করতে চান?', 'Reject this order?'))) return
    isLoading.value = true
    loadingAction.value = 'reject'
    loadingId.value = id
    router.post(
        route('admin.pending-sales.reject', id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                localSuccess.value = t('অর্ডার বাতিল করা হয়েছে।', 'Rejected.')
                if (localTimer) clearTimeout(localTimer)
                localTimer = setTimeout(() => (localSuccess.value = ''), 8000)
                isLoading.value = false
                loadingAction.value = ''
                loadingId.value = null
                router.reload({ preserveScroll: true, preserveState: true })
            },
            onError: (errors) => {
                localError.value = errors?.error || t('বাতিল ব্যর্থ হয়েছে।', 'Reject failed.')
                if (localTimer) clearTimeout(localTimer)
                localTimer = setTimeout(() => (localError.value = ''), 12000)
                isLoading.value = false
                loadingAction.value = ''
                loadingId.value = null
            },
        }
    )
}

function printReceipt(saleId) {
    window.open(route('admin.sales.print-receipt', saleId), '_blank')
}

function updateItem(orderId, itemId, qty) {
    router.post(route('admin.pending-sales.items.update', { pendingSale: orderId, item: itemId }), { quantity: Number(qty) }, { preserveScroll: true })
}

function removeItem(orderId, itemId) {
    if (!confirm(t('অর্ডার থেকে পণ্যটি মুছে ফেলতে চান?', 'Remove this item from the order?'))) return
    router.post(route('admin.pending-sales.items.remove', { pendingSale: orderId, item: itemId }), {}, { preserveScroll: true })
}

function searchProducts(orderId) {
    const term = (addSearch.value[orderId] || '').trim()
    if (!term) {
        searchResults.value[orderId] = []
        return
    }
    if (searchTimers[orderId]) clearTimeout(searchTimers[orderId])
    searchTimers[orderId] = setTimeout(async () => {
        const res = await fetch(`/admin/pos/search-products?search=${encodeURIComponent(term)}`)
        const data = await res.json()
        searchResults.value[orderId] = Array.isArray(data) ? data : []
    }, 250)
}

function addProductToOrder(orderId, product) {
    // default add 1 qty
    router.post(route('admin.pending-sales.items.add', { pendingSale: orderId }), { product_id: product.id, quantity: 1 }, { preserveScroll: true })
    addSearch.value[orderId] = ''
    searchResults.value[orderId] = []
}
</script>

