<template>
    <Head :title="title">
        <link v-if="$page.props.platform?.favicon" rel="icon" :href="$page.props.platform.favicon" />
        <link v-if="$page.props.platform?.favicon" rel="shortcut icon" :href="$page.props.platform.favicon" />
    </Head>
    <div class="min-h-screen bg-slate-50 text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-100 flex flex-col font-sans">
        <!-- Sidebar Navigation -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-50 w-72 sm:w-80 lg:w-64 transition-transform duration-300 ease-in-out flex flex-col',
            'bg-white border-r border-slate-200/90 shadow-2xl lg:shadow-sm',
            'dark:bg-slate-900 dark:border-slate-800',
            isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]">
            <!-- Sidebar Header / Brand -->
            <div class="flex-shrink-0 h-16 flex items-center justify-between px-5 border-b border-slate-100 dark:border-slate-800">
                <Link href="/admin/dashboard" class="flex items-center gap-3 overflow-hidden group">
                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 flex items-center justify-center shadow-xs shrink-0 overflow-hidden p-1.5 transition-transform group-hover:scale-105">
                        <img
                            v-if="effectiveLogo && !logoError"
                            :src="effectiveLogo"
                            :alt="$page.props.business_name || $page.props.platform?.name || 'Logo'"
                            class="w-full h-full object-contain"
                            @error="logoError = true"
                        />
                        <div v-else class="w-full h-full rounded-lg bg-indigo-600 flex items-center justify-center text-white font-extrabold text-base">
                            {{ ($page.props.business_name || $page.props.platform?.name || 'T').substring(0, 1).toUpperCase() }}
                        </div>
                    </div>
                    <div class="truncate">
                        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate tracking-tight">
                            {{ $page.props.business_name || $page.props.platform?.name || 'TrustCash' }}
                        </h1>
                        <p class="text-[11px] font-medium text-slate-400 dark:text-slate-500">
                            {{ $page.props.platform?.tagline || t('রিটেইল ও পিওএস', 'Retail POS & ERP') }}
                        </p>
                    </div>
                </Link>
                <button
                    class="p-2 transition-colors rounded-lg lg:hidden hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400"
                    @click="toggleSidebar"
                >
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>

            <!-- Standout POS Terminal Action (Always High Priority) -->
            <div class="px-4 pt-4 pb-2">
                <Link
                    href="/admin/pos"
                    class="flex items-center justify-between w-full px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-sm shadow-emerald-600/20 transition-all active:scale-98"
                >
                    <div class="flex items-center gap-2.5">
                        <i class="fas fa-cash-register text-base"></i>
                        <span>{{ t('ক্যাশ কাউন্টার (POS)', 'POS Terminal') }}</span>
                    </div>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-white/20">F1</span>
                </Link>
            </div>

            <!-- Navigation Menu Items -->
            <div class="flex-1 overflow-y-auto px-3 py-2 space-y-1">
                <template v-for="item in navItems" :key="item.id">
                    <!-- Direct Link Item (No Dropdown) -->
                    <Link
                        v-if="!item.children"
                        :href="item.href"
                        :class="[
                            'flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-all',
                            isUrlMatch(item.href)
                                ? 'bg-indigo-50 text-indigo-700 font-bold dark:bg-indigo-950/70 dark:text-indigo-300 border-l-4 border-indigo-600 shadow-xs'
                                : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <i :class="['fas', item.icon, 'w-5 text-center text-sm', isUrlMatch(item.href) ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500']"></i>
                            <span>{{ t(item.nameBn, item.nameEn) }}</span>
                        </div>
                        <span
                            v-if="item.badgeKey && (counts[item.badgeKey] || 0) > 0"
                            class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500 text-white animate-pulse"
                        >
                            {{ counts[item.badgeKey] }}
                        </span>
                    </Link>

                    <!-- Accordion Dropdown Group -->
                    <div v-else class="space-y-0.5">
                        <!-- Parent Group Button -->
                        <button
                            @click="toggleDropdown(item.id)"
                            :class="[
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-left',
                                isChildActive(item)
                                    ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 font-bold border-l-4 border-indigo-600 shadow-xs'
                                    : isDropdownExpanded(item.id)
                                        ? 'bg-slate-100/70 text-slate-900 dark:bg-slate-800/70 dark:text-white'
                                        : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800'
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <i :class="['fas', item.icon, 'w-5 text-center text-sm', isChildActive(item) ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500']"></i>
                                <span>{{ t(item.nameBn, item.nameEn) }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span v-if="isChildActive(item)" class="w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                                <i :class="[
                                    'fas fa-chevron-right text-xs transition-transform duration-200',
                                    isDropdownExpanded(item.id) ? 'rotate-90 text-indigo-600 dark:text-indigo-400' : 'text-slate-400'
                                ]"></i>
                            </div>
                        </button>

                        <!-- Submenu Links -->
                        <div v-show="isDropdownExpanded(item.id)" class="pl-6 pr-1 py-1 space-y-1">
                            <Link
                                v-for="sub in item.children"
                                :key="sub.href"
                                :href="sub.href"
                                :class="[
                                    'flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition-all',
                                    isUrlMatch(sub.href)
                                        ? 'bg-indigo-600 text-white font-bold shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/60'
                                ]"
                            >
                                <div class="flex items-center gap-2">
                                    <span :class="['w-1.5 h-1.5 rounded-full', isUrlMatch(sub.href) ? 'bg-white' : 'bg-slate-300 dark:bg-slate-600']"></span>
                                    <span>{{ t(sub.nameBn, sub.nameEn) }}</span>
                                </div>
                                <i v-if="isUrlMatch(sub.href)" class="fas fa-check text-[10px]"></i>
                            </Link>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Sidebar User Profile Footer -->
            <div class="flex-shrink-0 p-3 border-t border-slate-100 dark:border-slate-800">
                <Menu as="div" class="relative">
                    <MenuButton class="flex items-center w-full p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-all text-left">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-xs shrink-0">
                            {{ user?.name?.charAt(0) || 'U' }}
                        </div>
                        <div class="flex-1 ml-2.5 truncate">
                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ user?.name }}</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 capitalize truncate">{{ user?.role?.name || 'Admin' }}</p>
                        </div>
                        <i class="fas fa-ellipsis-v text-slate-400 text-xs ml-1"></i>
                    </MenuButton>

                    <MenuItems class="absolute left-0 w-full mb-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-lg bottom-full rounded-xl p-1 z-50 focus:outline-none">
                        <MenuItem v-slot="{ active }">
                            <Link
                                href="/profile"
                                :class="[
                                    'flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-lg',
                                    active ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700' : 'text-slate-700 dark:text-slate-300'
                                ]"
                            >
                                <i class="fas fa-user-circle text-sm text-indigo-500"></i>
                                {{ t('প্রোফাইল', 'Profile') }}
                            </Link>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                class="w-full text-left flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-lg text-rose-600 dark:text-rose-400"
                                :class="active ? 'bg-rose-50 dark:bg-rose-950' : ''"
                            >
                                <i class="fas fa-sign-out-alt text-sm"></i>
                                {{ t('লগআউট', 'Sign out') }}
                            </Link>
                        </MenuItem>
                    </MenuItems>
                </Menu>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div :class="['lg:pl-64 min-h-screen flex flex-col flex-1', isOpen && 'overflow-hidden']">
            <!-- Sleek Top Bar (Touch-friendly on mobile, zero clutter) -->
            <header class="sticky top-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 h-16 px-3 sm:px-6">
                <div class="flex items-center justify-between h-full gap-2 sm:gap-3">
                    <!-- Left: Mobile Toggle & Page Title -->
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                        <button
                            class="p-2 text-slate-500 rounded-xl lg:hidden hover:bg-slate-100 dark:hover:bg-slate-800 shrink-0"
                            @click="toggleSidebar"
                            :aria-label="t('মেনু খুলুন', 'Open Menu')"
                        >
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                        <div class="truncate">
                            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate">
                                {{ pageTitle }}
                            </h2>
                        </div>
                    </div>

                    <!-- Right: Search, Language Toggle, Theme, Notification -->
                    <div class="flex items-center gap-1.5 sm:gap-2.5 shrink-0">
                        <!-- Quick Search Trigger -->
                        <button
                            @click="isCommandPaletteOpen = true"
                            class="flex items-center gap-2 p-2 sm:px-3 sm:py-1.5 text-xs text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all"
                            :title="t('খুঁজুন...', 'Search...')"
                        >
                            <i class="fas fa-search text-xs"></i>
                            <span class="hidden md:inline">{{ t('খুঁজুন...', 'Search...') }}</span>
                            <kbd class="hidden sm:inline text-[10px] font-mono px-1 py-0.5 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700">Ctrl K</kbd>
                        </button>

                        <!-- Instant Language Toggle Switch (বাংলা | EN) -->
                        <div class="flex items-center p-0.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                            <button
                                @click="setLanguage('bn')"
                                :class="[
                                    'px-2 py-1 text-xs font-bold rounded-lg transition-all',
                                    currentLang === 'bn'
                                        ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-800'
                                ]"
                            >
                                বাং
                            </button>
                            <button
                                @click="setLanguage('en')"
                                :class="[
                                    'px-2 py-1 text-xs font-bold rounded-lg transition-all',
                                    currentLang === 'en'
                                        ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-800'
                                ]"
                            >
                                EN
                            </button>
                        </div>

                        <!-- Theme Toggle Button -->
                        <button
                            @click="switchTheme"
                            class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            :title="t('থিম পরিবর্তন', 'Theme toggle')"
                        >
                            <i class="fa-solid fa-circle-half-stroke text-base"></i>
                        </button>

                        <!-- PWA Install Button -->
                        <button
                            @click="triggerPwaInstall"
                            class="p-2 text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            :title="t('অ্যাপ ইনস্টল করুন', 'Install App (PWA)')"
                        >
                            <i class="fas fa-download text-base"></i>
                        </button>

                        <!-- Notification Menu -->
                        <Link
                            href="/admin/pending-sales"
                            class="relative p-2 text-slate-500 hover:text-indigo-600 dark:text-slate-400 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800"
                            :title="t('অনলাইন অর্ডার', 'Online Orders')"
                        >
                            <i class="fas fa-bell text-base"></i>
                            <span v-if="(counts.pendingOrders || 0) > 0" class="absolute top-1.5 right-1.5 w-2 h-2 bg-amber-500 rounded-full animate-ping"></span>
                            <span v-if="(counts.pendingOrders || 0) > 0" class="absolute top-1.5 right-1.5 w-2 h-2 bg-amber-500 rounded-full"></span>
                        </Link>
                    </div>
                </div>
            </header>

            <!-- Main Page Content (Responsive padding with mobile bottom nav spacing) -->
            <main class="flex-1 px-3 sm:px-6 lg:px-8 py-4 sm:py-6 pb-24 lg:pb-8">
                <!-- Page Header Slot -->
                <div v-if="$slots.header" class="mb-4 sm:mb-6">
                    <slot name="header" />
                </div>

                <!-- Page Body Content -->
                <div class="space-y-4">
                    <slot />
                </div>
            </main>

            <!-- Footer (Hidden on small mobile to give room to bottom nav, visible on md+) -->
            <footer class="mt-auto hidden sm:flex bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 py-3 px-4 sm:px-6 text-xs text-slate-400 items-center justify-between">
                <p>© {{ new Date().getFullYear() }} {{ $page.props.business_name || $page.props.app_name || 'TrustCash' }}</p>
                <div class="flex items-center gap-2">
                    <span>POS: <b class="font-mono text-emerald-600">F1</b></span>
                    <span>•</span>
                    <span>{{ t('ভাষা:', 'Lang:') }} <b class="uppercase">{{ currentLang }}</b></span>
                </div>
            </footer>
        </div>

        <!-- Mobile Bottom Navigation Bar (Thumb-friendly quick navigation) -->
        <nav class="fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200/90 dark:border-slate-800 lg:hidden shadow-lg pb-[max(0.25rem,env(safe-area-inset-bottom))]">
            <div class="grid grid-cols-5 h-14 items-center px-1">
                <!-- Dashboard -->
                <Link
                    href="/admin/dashboard"
                    :class="[
                        'flex flex-col items-center justify-center h-full transition-colors',
                        isUrlMatch('/admin/dashboard')
                            ? 'text-indigo-600 dark:text-indigo-400 font-bold'
                            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'
                    ]"
                >
                    <i class="fas fa-tachometer-alt text-base mb-1"></i>
                    <span class="text-[10px] leading-tight">{{ t('ড্যাশবোর্ড', 'Dashboard') }}</span>
                </Link>

                <!-- Sales -->
                <Link
                    href="/admin/sales"
                    :class="[
                        'flex flex-col items-center justify-center h-full transition-colors',
                        isUrlMatch('/admin/sales')
                            ? 'text-indigo-600 dark:text-indigo-400 font-bold'
                            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'
                    ]"
                >
                    <i class="fas fa-file-invoice-dollar text-base mb-1"></i>
                    <span class="text-[10px] leading-tight">{{ t('বিক্রি', 'Sales') }}</span>
                </Link>

                <!-- Center POS Action Button -->
                <Link
                    href="/admin/pos"
                    class="flex flex-col items-center justify-center relative -top-3 group"
                >
                    <div class="w-12 h-12 rounded-full bg-emerald-600 group-hover:bg-emerald-700 text-white flex items-center justify-center shadow-lg shadow-emerald-600/30 transition-transform active:scale-95 border-2 border-white dark:border-slate-900">
                        <i class="fas fa-cash-register text-lg"></i>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ t('পিওএস', 'POS') }}</span>
                </Link>

                <!-- Products -->
                <Link
                    href="/admin/products"
                    :class="[
                        'flex flex-col items-center justify-center h-full transition-colors',
                        isUrlMatch('/admin/products')
                            ? 'text-indigo-600 dark:text-indigo-400 font-bold'
                            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'
                    ]"
                >
                    <i class="fas fa-boxes text-base mb-1"></i>
                    <span class="text-[10px] leading-tight">{{ t('পণ্য', 'Products') }}</span>
                </Link>

                <!-- Menu / Sidebar Drawer Toggle -->
                <button
                    @click="toggleSidebar"
                    :class="[
                        'flex flex-col items-center justify-center h-full transition-colors',
                        isOpen
                            ? 'text-indigo-600 dark:text-indigo-400 font-bold'
                            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'
                    ]"
                >
                    <i class="fas fa-bars text-base mb-1"></i>
                    <span class="text-[10px] leading-tight">{{ t('মেনু', 'Menu') }}</span>
                </button>
            </div>
        </nav>

        <!-- Mobile Drawer Overlay -->
        <div
            v-if="isOpen"
            class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs transition-opacity lg:hidden"
            @click="toggleSidebar"
        ></div>

        <!-- Global Search Dialog -->
        <CommandPalette
            v-model="isCommandPaletteOpen"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, usePage, Link } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import { switchTheme } from '@/theme'
import { useLanguage } from '@/composables/useLanguage'
import CommandPalette from '@/Components/CommandPalette.vue'

const page = usePage()
const props = defineProps({
    title: {
        type: String,
        default: ''
    }
})
const user = computed(() => page.props.auth?.user || {})
const counts = computed(() => page.props.counts || {})
const logoError = ref(false)
const effectiveLogo = computed(() => {
    return page.props.business_logo || page.props.platform?.logo || null
})

watch(() => [page.props.business_logo, page.props.platform?.logo], () => {
    logoError.value = false
})

// Reactive Language Composable
const { currentLang, setLanguage, t } = useLanguage()

// Layout State
const isOpen = ref(false)
const activeDropdowns = ref([])
const isCommandPaletteOpen = ref(false)

const triggerPwaInstall = () => {
    window.dispatchEvent(new CustomEvent('trustcash:prompt-install'))
}

// Clean Navigation Items
const navItems = [
    {
        id: 'dashboard',
        nameBn: 'ড্যাশবোর্ড',
        nameEn: 'Dashboard',
        href: '/admin/dashboard',
        icon: 'fa-tachometer-alt'
    },
    {
        id: 'sales',
        nameBn: 'বেচাকেনা ও অর্ডার',
        nameEn: 'Sales & Orders',
        icon: 'fa-shopping-cart',
        children: [
            { nameBn: 'বিক্রির তালিকা', nameEn: 'Sales List', href: '/admin/sales' },
            { nameBn: 'অনলাইন অর্ডার', nameEn: 'Online Orders', href: '/admin/pending-sales' },
            { nameBn: 'পণ্য ফেরত (রিটার্ন)', nameEn: 'Sales Returns', href: '/admin/returns' }
        ]
    },
    {
        id: 'inventory',
        nameBn: 'পণ্য ও স্টক',
        nameEn: 'Products & Stock',
        icon: 'fa-boxes',
        children: [
            { nameBn: 'সকল পণ্য', nameEn: 'All Products', href: '/admin/products' },
            { nameBn: 'স্টক ম্যানেজমেন্ট', nameEn: 'Stock Management', href: '/admin/product-stocks' },
            { nameBn: 'ক্যাটাগরি', nameEn: 'Categories', href: '/admin/categories' },
            { nameBn: 'ব্র্যান্ড', nameEn: 'Brands', href: '/admin/brands' },
            { nameBn: 'পরিমাপক / ইউনিট', nameEn: 'Units', href: '/admin/units' }
        ]
    },
    {
        id: 'purchases',
        nameBn: 'মাল ক্রয় (পারচেজ)',
        nameEn: 'Purchases',
        href: '/admin/purchases',
        icon: 'fa-truck-loading'
    },
    {
        id: 'customers',
        nameBn: 'কাস্টমার ও বাকি খাতা',
        nameEn: 'Customers & Due Khata',
        href: '/admin/customers',
        icon: 'fa-user-friends'
    },
    {
        id: 'suppliers',
        nameBn: 'সাপ্লায়ার / মহাজন',
        nameEn: 'Suppliers',
        href: '/admin/suppliers',
        icon: 'fa-truck'
    },
    {
        id: 'finance',
        nameBn: 'ক্যাশ ও খরচ হিসাব',
        nameEn: 'Finance & Expenses',
        icon: 'fa-wallet',
        children: [
            { nameBn: 'দোকানের খরচ', nameEn: 'Expenses', href: '/admin/expenses' },
            { nameBn: 'খরচের ক্যাটাগরি', nameEn: 'Expense Categories', href: '/admin/expense-categories' },
            { nameBn: 'স্থায়ী সম্পদ', nameEn: 'Fixed Assets', href: '/admin/fixed-assets' },
            { nameBn: 'ব্যাংক ও ক্যাশ অ্যাকাউন্ট', nameEn: 'Bank Accounts', href: '/admin/bank-accounts' },
            { nameBn: 'ফান্ড ট্রান্সফার', nameEn: 'Fund Transfer', href: '/admin/funds' },
            { nameBn: 'লেনদেনের হিস্ট্রি', nameEn: 'Transactions', href: '/admin/bank-transactions' },
            { nameBn: 'বাড়তি আয়', nameEn: 'Extra Income', href: '/admin/extra-incomes' }
        ]
    },
    {
        id: 'reports',
        nameBn: 'রিপোর্ট সেন্টার',
        nameEn: 'Reports Hub',
        icon: 'fa-chart-line',
        children: [
            { nameBn: 'বিক্রয় রিপোর্ট', nameEn: 'Sales Report', href: '/admin/reports/sales' },
            { nameBn: 'স্টক রিপোর্ট', nameEn: 'Stock Report', href: '/admin/reports/stock' },
            { nameBn: 'পণ্য অ্যানালাইসিস', nameEn: 'Product Analysis', href: '/admin/reports/product-analysis' },
            { nameBn: 'ব্যাংক রিপোর্ট', nameEn: 'Bank Report', href: '/admin/reports/bank-transaction-report' },
            { nameBn: 'লাভ-ক্ষতি বিবরণী', nameEn: 'Income & Expenditure', href: '/admin/reports/income-expenditure' },
            { nameBn: 'রিসিপ্ট ও পেমেন্ট', nameEn: 'Receipt & Payment', href: '/admin/reports/receipt-payment' },
            { nameBn: 'ব্যালেন্স শীট', nameEn: 'Balance Sheet', href: '/admin/reports/balance-sheet' }
        ]
    },
    {
        id: 'settings',
        nameBn: 'দোকান সেটিংস',
        nameEn: 'Settings & Setup',
        icon: 'fa-cog',
        children: [
            { nameBn: 'ব্যবসা সেটিংস', nameEn: 'Business Settings', href: '/admin/settings' },
            { nameBn: 'শাখা / ব্রাঞ্চ', nameEn: 'Branches', href: '/admin/branches' },
            { nameBn: 'স্টাফ ও ইউজার', nameEn: 'Staff & Roles', href: '/admin/users' }
        ]
    }
]

// Accurate URL Matching (No prefix collisions between /admin/sales and /admin/reports/sales)
const isUrlMatch = (targetPath) => {
    const current = (page.url || window.location.pathname).split('?')[0]
    if (targetPath === '/admin/dashboard') {
        return current === '/admin/dashboard' || current === '/admin'
    }
    return current === targetPath || current.startsWith(targetPath + '/')
}

// Check if any child of a parent item is currently active
const isChildActive = (parentItem) => {
    if (!parentItem.children) return false
    return parentItem.children.some(child => isUrlMatch(child.href))
}

// Check if dropdown is expanded
const isDropdownExpanded = (id) => {
    return activeDropdowns.value.includes(id)
}

// Toggle dropdown manually
const toggleDropdown = (id) => {
    const index = activeDropdowns.value.indexOf(id)
    if (index === -1) {
        // Open this one, and optionally close others so only 1 stays open
        activeDropdowns.value = [id]
    } else {
        activeDropdowns.value.splice(index, 1)
    }
}

// Automatically sync dropdowns with the active page route
const syncActiveDropdown = () => {
    // Find parent that owns the currently active child route
    const matchingParent = navItems.find(item => isChildActive(item))
    if (matchingParent) {
        activeDropdowns.value = [matchingParent.id]
    } else {
        // If on top-level direct route (like Dashboard, Customers, Purchases, POS), collapse all
        activeDropdowns.value = []
    }
}

// Watch Inertia page.url so route transitions dynamically update active/expanded states
watch(
    () => page.url,
    () => {
        syncActiveDropdown()
        isOpen.value = false
    },
    { immediate: true }
)

// Dynamic Page Title for Top Header
const pageTitle = computed(() => {
    if (props.title) return props.title

    for (const item of navItems) {
        if (isUrlMatch(item.href)) return t(item.nameBn, item.nameEn)
        if (item.children) {
            const sub = item.children.find(s => isUrlMatch(s.href))
            if (sub) return t(sub.nameBn, sub.nameEn)
        }
    }
    return t('অ্যাডমিন প্যানেল', 'Admin Panel')
})

const toggleSidebar = () => {
    isOpen.value = !isOpen.value
}
</script>

<style scoped>
/* Clean scrollbar for sidebar */
.overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.3) transparent;
}
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: rgba(148, 163, 184, 0.3);
    border-radius: 4px;
}
</style>
