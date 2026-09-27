<template>
    <div v-if="isOpen">
        <!-- Backdrop -->
        <div class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="close"></div>

        <!-- Palette Dialog -->
        <div class="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-24 px-4 pointer-events-none">
            <div class="pointer-events-auto w-full max-w-xl overflow-hidden rounded-2xl bg-white dark:bg-slate-900 shadow-2xl border border-slate-200 dark:border-slate-800 transition-all">
                <!-- Search Input Header -->
                <div class="relative flex items-center px-4 py-3.5 border-b border-slate-100 dark:border-slate-800">
                    <i class="fas fa-search text-slate-400 text-base mr-3"></i>
                    <input
                        ref="searchInput"
                        v-model="query"
                        type="text"
                        :placeholder="t('পণ্য, কাস্টমার, মেমো বা পেইজ খুঁজুন...', 'Search products, customers, invoices or pages...')"
                        class="w-full bg-transparent border-none text-slate-900 dark:text-slate-100 placeholder-slate-400 text-sm focus:outline-none focus:ring-0"
                        @keydown.down.prevent="navigateResults(1)"
                        @keydown.up.prevent="navigateResults(-1)"
                        @keydown.enter.prevent="selectActive"
                        @keydown.esc.prevent="close"
                    />
                    <kbd class="hidden sm:inline-flex items-center px-2 py-0.5 text-[11px] font-semibold text-slate-400 bg-slate-100 dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700">ESC</kbd>
                </div>

                <!-- Results & Options -->
                <div class="max-h-96 overflow-y-auto p-2 divide-y divide-slate-100 dark:divide-slate-800">
                    <!-- Quick Actions -->
                    <div v-if="filteredActions.length > 0" class="py-2">
                        <div class="px-3 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            {{ t('কুইক অ্যাকশন', 'Quick Actions') }}
                        </div>
                        <div class="space-y-1">
                            <button
                                v-for="(item, idx) in filteredActions"
                                :key="'act-' + idx"
                                @click="runItem(item)"
                                @mouseenter="selectedIndex = idx"
                                :class="[
                                    'w-full flex items-center justify-between px-3 py-2 rounded-xl text-left text-sm transition-colors',
                                    selectedIndex === idx
                                        ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold'
                                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <div :class="['w-8 h-8 rounded-lg flex items-center justify-center text-xs', item.bg || 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900 dark:text-indigo-400']">
                                        <i :class="['fas', item.icon]"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-xs sm:text-sm">{{ t(item.titleBn, item.titleEn) }}</div>
                                        <div class="text-[11px] text-slate-400 font-normal">{{ t(item.subBn, item.subEn) }}</div>
                                    </div>
                                </div>
                                <span v-if="item.shortcut" class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">{{ item.shortcut }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Navigation Pages -->
                    <div v-if="filteredPages.length > 0" class="py-2">
                        <div class="px-3 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            {{ t('পেইজ ও মডিউল', 'Navigation Pages') }}
                        </div>
                        <div class="space-y-1">
                            <button
                                v-for="(item, idx) in filteredPages"
                                :key="'page-' + idx"
                                @click="runItem(item)"
                                @mouseenter="selectedIndex = filteredActions.length + idx"
                                :class="[
                                    'w-full flex items-center justify-between px-3 py-2 rounded-xl text-left text-sm transition-colors',
                                    selectedIndex === filteredActions.length + idx
                                        ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold'
                                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 text-xs">
                                        <i :class="['fas', item.icon]"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-xs sm:text-sm">{{ t(item.titleBn, item.titleEn) }}</div>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-xs text-slate-300"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredActions.length === 0 && filteredPages.length === 0" class="py-8 text-center text-sm text-slate-400">
                        {{ t('কোন ফলাফল পাওয়া যায়নি', 'No results found for') }} "{{ query }}"
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
                    <div class="flex items-center gap-2">
                        <span><kbd class="px-1 py-0.5 bg-white dark:bg-slate-700 rounded border border-slate-200 dark:border-slate-600">↑↓</kbd> {{ t('নেভিগেট', 'Navigate') }}</span>
                        <span><kbd class="px-1 py-0.5 bg-white dark:bg-slate-700 rounded border border-slate-200 dark:border-slate-600">↵</kbd> {{ t('সিলেক্ট', 'Select') }}</span>
                    </div>
                    <span>TrustCash</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useLanguage } from '@/composables/useLanguage'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])

const { t } = useLanguage()

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
})

const searchInput = ref(null)
const query = ref('')
const selectedIndex = ref(0)

const actions = [
    {
        titleBn: 'ক্যাশ কাউন্টার (POS)',
        titleEn: 'POS Terminal',
        subBn: 'নতুন বিক্রির রসিদ কাটুন',
        subEn: 'Create sale invoice',
        icon: 'fa-cash-register',
        shortcut: 'F1',
        bg: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
        action: () => router.visit('/admin/pos')
    },
    {
        titleBn: 'নতুন খরচ এন্ট্রি',
        titleEn: 'Record Expense',
        subBn: 'দোকানের খরচের ভাউচার',
        subEn: 'Add daily store expense',
        icon: 'fa-receipt',
        shortcut: 'Expense',
        bg: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400',
        action: () => router.visit('/admin/expenses')
    },
    {
        titleBn: 'বাকি কালেকশন',
        titleEn: 'Customer Due Collection',
        subBn: 'কাস্টমার থেকে টাকা জমা',
        subEn: 'Collect payment from customer',
        icon: 'fa-hand-holding-usd',
        shortcut: 'Due',
        bg: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
        action: () => router.visit('/admin/customers')
    },
    {
        titleBn: 'নতুন পণ্য যোগ',
        titleEn: 'Add New Product',
        subBn: 'বারকোড ও স্টক প্রাইস',
        subEn: 'Create product item',
        icon: 'fa-box-open',
        shortcut: 'Product',
        bg: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-400',
        action: () => router.visit('/admin/products')
    }
]

const pages = [
    { titleBn: 'ড্যাশবোর্ড', titleEn: 'Dashboard', icon: 'fa-tachometer-alt', href: '/admin/dashboard' },
    { titleBn: 'বিক্রির তালিকা', titleEn: 'Sales List', icon: 'fa-receipt', href: '/admin/sales' },
    { titleBn: 'অনলাইন অর্ডার', titleEn: 'Online Orders', icon: 'fa-clipboard-list', href: '/admin/pending-sales' },
    { titleBn: 'সকল পণ্য', titleEn: 'All Products', icon: 'fa-boxes', href: '/admin/products' },
    { titleBn: 'স্টক ম্যানেজমেন্ট', titleEn: 'Stock Management', icon: 'fa-warehouse', href: '/admin/product-stocks' },
    { titleBn: 'কাস্টমার ও বাকি খাতা', titleEn: 'Customers & Due Khata', icon: 'fa-user-friends', href: '/admin/customers' },
    { titleBn: 'সাপ্লায়ার / মহাজন', titleEn: 'Suppliers', icon: 'fa-truck', href: '/admin/suppliers' },
    { titleBn: 'মাল ক্রয় (পারচেজ)', titleEn: 'Purchases', icon: 'fa-truck-loading', href: '/admin/purchases' },
    { titleBn: 'দোকানের খরচ', titleEn: 'Expenses', icon: 'fa-file-invoice-dollar', href: '/admin/expenses' },
    { titleBn: 'রিপোর্ট সেন্টার', titleEn: 'Reports Hub', icon: 'fa-chart-line', href: '/admin/reports/sales' },
    { titleBn: 'দোকান সেটিংস', titleEn: 'Settings', icon: 'fa-cog', href: '/admin/settings' }
]

const filteredActions = computed(() => {
    if (!query.value.trim()) return actions
    const q = query.value.toLowerCase()
    return actions.filter(a => a.titleBn.toLowerCase().includes(q) || a.titleEn.toLowerCase().includes(q) || a.subBn.toLowerCase().includes(q) || a.subEn.toLowerCase().includes(q))
})

const filteredPages = computed(() => {
    if (!query.value.trim()) return pages
    const q = query.value.toLowerCase()
    return pages.filter(p => p.titleBn.toLowerCase().includes(q) || p.titleEn.toLowerCase().includes(q))
})

const allItems = computed(() => [...filteredActions.value, ...filteredPages.value])

watch(isOpen, (newVal) => {
    if (newVal) {
        query.value = ''
        selectedIndex.value = 0
        nextTick(() => {
            searchInput.value?.focus()
        })
    }
})

const navigateResults = (step) => {
    const total = allItems.value.length
    if (total === 0) return
    selectedIndex.value = (selectedIndex.value + step + total) % total
}

const selectActive = () => {
    const item = allItems.value[selectedIndex.value]
    if (item) runItem(item)
}

const runItem = (item) => {
    close()
    if (item.action) {
        item.action()
    } else if (item.href) {
        router.visit(item.href)
    }
}

const close = () => {
    isOpen.value = false
}

// Global Keyboard Shortcut listener
const handleGlobalKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault()
        isOpen.value = !isOpen.value
    } else if (e.key === 'F1') {
        e.preventDefault()
        router.visit('/admin/pos')
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown)
})

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown)
})
</script>
