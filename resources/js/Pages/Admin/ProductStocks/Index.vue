<!-- Index.vue -->
<template>
    <AdminLayout :title="t('পণ্য স্টক ব্যবস্থাপনা', 'Product Stocks')">
        <Head :title="t('পণ্য স্টক ব্যবস্থাপনা', 'Product Stocks')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                        {{ t('পণ্য স্টক ও ইনভেন্টরি', 'Product Stocks') }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('দোকানের পণ্যের মজুদ, গড় ক্রয়মূল্য ও স্টক হিস্ট্রি নিরীক্ষা করুন', 'Manage your inventory and stock levels') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.product-stocks.create')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-all">
                        <PlusIcon class="h-4 w-4" />
                        <span>{{ t('নতুন স্টক যুক্ত করুন', 'Add Stock') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <!-- Total Products -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ t('মোট পণ্য', 'Total Products') }}
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ formatNumber(summary.total_products) }}</h3>
                </div>

                <!-- Total Stock Value -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ t('বর্তমান স্টক মূল্য', 'Stock Value') }}
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ formatCurrency(summary.total_value) }}</h3>
                </div>

                <!-- Total Quantity -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ t('মজুদ পরিমাণ', 'Available Qty') }}
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ formatNumber(summary.total_quantity) }}</h3>
                </div>

                <!-- Low Stock Items -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ t('সীমিত স্টক সতর্কতা', 'Low Stock Items') }}
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400">{{ formatNumber(summary.low_stock_items) }}</h3>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <input
                            v-model="localFilters.search"
                            @input="debouncedSearch"
                            type="text"
                            :placeholder="t('পণ্যের নাম বা SKU কোড দিয়ে খুঁজুন...', 'Search by product name or SKU...')"
                            class="w-full rounded-xl border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                    </div>
                    <div class="flex gap-2">
                        <select
                            v-model="localFilters.per_page"
                            @change="applyFilters"
                            class="rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            <option :value="10">10 {{ t('টি প্রতি পাতায়', 'per page') }}</option>
                            <option :value="15">15 {{ t('টি প্রতি পাতায়', 'per page') }}</option>
                            <option :value="25">25 {{ t('টি প্রতি পাতায়', 'per page') }}</option>
                            <option :value="50">50 {{ t('টি প্রতি পাতায়', 'per page') }}</option>
                            <option :value="100">100 {{ t('টি প্রতি পাতায়', 'per page') }}</option>
                        </select>
                        <button
                            v-if="localFilters.search"
                            @click="clearFilters"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ t('মুছুন', 'Clear') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stock Table & Mobile Cards -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <!-- Mobile Stock Cards (< md) -->
                <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800 p-3 space-y-3">
                    <div v-if="stocks.data.length === 0" class="py-10 text-center text-xs text-slate-400">
                        {{ t('কোনো পণ্য বা স্টক পাওয়া যায়নি।', 'No products found') }}
                    </div>

                    <div v-for="stock in stocks.data" :key="stock.id" class="pt-3 first:pt-0 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white leading-snug line-clamp-2">
                                    {{ stock.product.name }}
                                </h4>
                                <p class="text-[11px] font-mono text-slate-400 mt-0.5">
                                    SKU: {{ stock.product.sku }}
                                </p>
                            </div>
                            <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold shrink-0" :class="{
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300': stock.stock_status === 'in',
                                'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300': stock.stock_status === 'low',
                                'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300': stock.stock_status === 'out'
                            }">
                                {{ stock.stock_status === 'in' ? t('মজুদ আছে', 'In Stock') :
                                    stock.stock_status === 'low' ? t('সীমিত স্টক', 'Low Stock') : t('স্টক শেষ', 'Out of Stock') }}
                            </span>
                        </div>

                        <!-- 3-Column Stock Metric Grid -->
                        <div class="grid grid-cols-3 gap-2 bg-slate-50 dark:bg-slate-800/50 p-2.5 rounded-xl text-xs">
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold">{{ t('মজুদ', 'Available') }}</span>
                                <span class="font-extrabold text-slate-900 dark:text-white mt-0.5 block tabular-nums text-sm">
                                    {{ formatNumber(stock.quantity) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold">{{ t('গড় মূল্য', 'Avg Cost') }}</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5 block tabular-nums">
                                    ৳{{ formatNumber(stock.average_unit_cost) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold">{{ t('স্টক মূল্য', 'Value') }}</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 mt-0.5 block tabular-nums">
                                    ৳{{ formatNumber(stock.current_stock_value) }}
                                </span>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[11px] text-slate-400">
                                {{ t('মোট ক্রয়:', 'Purchased:') }} <strong class="text-slate-600 dark:text-slate-300">{{ formatNumber(stock.total_purchased) }}</strong>
                            </span>

                            <button v-if="userIsAdmin" @click="showHistory(stock.product)"
                                class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 active:scale-95 transition-all">
                                {{ t('হিস্ট্রি দেখুন', 'View History') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table (md+) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                            <tr>
                                <th scope="col" @click="sortBy('product_name')"
                                    class="px-5 py-3 text-left cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800">
                                    <div class="flex items-center gap-1">
                                        {{ t('পণ্যের বিবরণ', 'Product Info') }}
                                        <svg v-if="localFilters.sort_field === 'product_name'" class="w-3.5 h-3.5" :class="{'rotate-180': localFilters.sort_order === 'desc'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                    </div>
                                </th>
                                <th scope="col" class="px-4 py-3 text-right">
                                    {{ t('মজুদ পরিমাণ', 'Available Qty') }}
                                </th>
                                <th scope="col" class="px-4 py-3 text-right">
                                    {{ t('মোট ক্রয়কৃত', 'Total Purchased') }}
                                </th>
                                <th scope="col" class="px-4 py-3 text-right">
                                    {{ t('গড় ক্রয়মূল্য', 'Avg. Unit Cost') }}
                                </th>
                                <th scope="col" class="px-4 py-3 text-right">
                                    {{ t('স্টক মূল্য', 'Current Value') }}
                                </th>
                                <th scope="col" class="px-4 py-3 text-right">
                                    {{ t('মোট ক্রয় ব্যয়', 'Total Purchase Cost') }}
                                </th>
                                <th scope="col" class="px-4 py-3 text-center">
                                    {{ t('অবস্থা', 'Status') }}
                                </th>
                                <th v-if="userIsAdmin" scope="col" class="px-5 py-3 text-right">
                                    {{ t('অ্যাকশন', 'Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-if="stocks.data.length === 0">
                                <td colspan="8" class="px-6 py-10 text-center text-slate-400">
                                    {{ t('কোনো পণ্য বা স্টক পাওয়া যায়নি।', 'No products found') }}
                                </td>
                            </tr>
                            <tr v-for="stock in stocks.data" :key="stock.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ stock.product.name }}
                                    </div>
                                    <div class="text-[11px] font-mono text-slate-400">
                                        SKU: {{ stock.product.sku }}
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ formatNumber(stock.quantity) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right text-slate-600 dark:text-slate-300">
                                    {{ formatNumber(stock.total_purchased) }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right font-medium text-slate-700 dark:text-slate-300">
                                    ৳{{ formatNumber(stock.average_unit_cost) }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-indigo-600 dark:text-indigo-400">
                                    ৳{{ formatNumber(stock.current_stock_value) }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right">
                                    <div class="font-semibold text-slate-900 dark:text-slate-100">
                                        ৳{{ formatNumber(stock.total_purchase_cost) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold" :class="{
                                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300': stock.stock_status === 'in',
                                        'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300': stock.stock_status === 'low',
                                        'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300': stock.stock_status === 'out'
                                    }">
                                        {{ stock.stock_status === 'in' ? t('মজুদ আছে', 'In Stock') :
                                            stock.stock_status === 'low' ? t('সীমিত স্টক', 'Low Stock') : t('স্টক শেষ', 'Out of Stock') }}
                                    </span>
                                </td>
                                <td v-if="userIsAdmin" class="px-5 py-3.5 whitespace-nowrap text-right">
                                    <button @click="showHistory(stock.product)"
                                        class="rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300">
                                        {{ t('হিস্ট্রি', 'History') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <div>
                            {{ t('দেখাচ্ছে', 'Showing') }}
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ stocks.from || 0 }}</span>
                            {{ t('থেকে', 'to') }}
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ stocks.to || 0 }}</span>
                            ({{ t('মোট', 'of') }} <span class="font-bold text-slate-800 dark:text-slate-200">{{ stocks.total }}</span> {{ t('টি ফলাফল', 'results') }})
                        </div>
                        <nav class="inline-flex rounded-xl bg-white shadow-xs border border-slate-200 dark:bg-slate-800 dark:border-slate-700 overflow-hidden">
                            <a v-for="link in stocks.links" :key="link.label"
                                :href="link.url"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-1.5 text-xs font-semibold transition-all',
                                    link.active
                                        ? 'bg-indigo-600 text-white'
                                        : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700',
                                    !link.url ? 'cursor-not-allowed opacity-40' : 'cursor-pointer'
                                ]">
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Stock History Modal -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0">
                    <div v-if="showHistoryModal"
                        class="fixed inset-0 z-[100] overflow-y-auto"
                        @click.self="closeHistoryModal">

                        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

                        <div class="flex min-h-screen items-center justify-center p-4">
                            <div class="relative w-full max-w-5xl rounded-2xl bg-white dark:bg-slate-900 shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800"
                                @click.stop>
                                <!-- Modal Header -->
                                <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                                    <div>
                                        <h3 class="text-base font-bold">
                                            {{ t('স্টক মুভমেন্ট ও হিস্ট্রি', 'Stock History') }}
                                        </h3>
                                        <p class="text-xs text-slate-300 mt-0.5">
                                            {{ selectedProduct?.name }}
                                        </p>
                                    </div>
                                    <button
                                        @click="closeHistoryModal"
                                        class="rounded-lg p-1.5 text-slate-400 hover:text-white hover:bg-slate-800">
                                        <XMarkIcon class="w-5 h-5" />
                                    </button>
                                </div>

                                <!-- Table Content -->
                                <div class="p-6 overflow-x-auto max-h-[500px]">
                                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                        <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300 sticky top-0">
                                            <tr>
                                                <th class="px-4 py-3 text-left">{{ t('তারিখ ও সময়', 'Date & Time') }}</th>
                                                <th class="px-4 py-3 text-left">{{ t('লেনদেনের ধরন', 'Type') }}</th>
                                                <th class="px-4 py-3 text-right">{{ t('পরিমাণ', 'Quantity') }}</th>
                                                <th class="px-4 py-3 text-right">{{ t('একক দর', 'Unit Cost') }}</th>
                                                <th class="px-4 py-3 text-right">{{ t('মোট টাকা', 'Total Cost') }}</th>
                                                <th class="px-4 py-3 text-right">{{ t('বর্তমান স্থিতি', 'Balance') }}</th>
                                                <th class="px-4 py-3 text-left">{{ t('মন্তব্য', 'Note') }}</th>
                                                <th class="px-4 py-3 text-left">{{ t('প্রক্রিয়াকারী', 'Created By') }}</th>
                                                <th class="px-4 py-3 text-center">{{ t('অ্যাকশন', 'Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                            <tr v-if="stockHistory.length === 0">
                                                <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                                                    {{ t('কোনো হিস্ট্রি রেকর্ড পাওয়া যায়নি।', 'No history records found') }}
                                                </td>
                                            </tr>
                                            <tr v-for="entry in stockHistory" :key="entry.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ entry.created_at }}</td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold"
                                                        :class="{
                                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300': entry.type === 'purchase',
                                                            'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300': entry.type === 'adjustment',
                                                            'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300': entry.type === 'sale'
                                                        }">
                                                        {{ entry.type === 'purchase' ? t('ক্রয়', 'Purchase') : (entry.type === 'sale' ? t('বিক্রয়', 'Sale') : t('সমন্বয়', 'Adjustment')) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">{{ formatNumber(entry.quantity) }}</td>
                                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">৳{{ formatNumber(entry.unit_cost) }}</td>
                                                <td class="px-4 py-3 text-right font-bold text-indigo-600 dark:text-indigo-400">৳{{ formatNumber(entry.total_cost) }}</td>
                                                <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">{{ formatNumber(entry.available_quantity) }}</td>
                                                <td class="px-4 py-3 text-slate-500">{{ entry.note || '—' }}</td>
                                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ entry.created_by }}</td>
                                                <td class="px-4 py-3 text-center">
                                                    <button
                                                        v-if="entry.type === 'purchase' && userIsAdmin"
                                                        @click="openDeleteConfirmation(entry)"
                                                        class="text-rose-600 hover:text-rose-800 font-bold text-xs">
                                                        {{ t('মুছুন', 'Delete') }}
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Footer -->
                                <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center text-xs">
                                    <span class="text-slate-500">{{ t('মোট রেকর্ড:', 'Total Entries:') }} {{ stockHistory.length }}</span>
                                    <button
                                        @click="closeHistoryModal"
                                        class="rounded-xl border border-slate-200 bg-white px-4 py-1.5 font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        {{ t('বন্ধ করুন', 'Close') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </Teleport>

            <!-- Delete Confirmation Modal -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0">
                    <div v-if="showDeleteConfirmation"
                        class="fixed inset-0 z-[200] overflow-y-auto"
                        @click.self="closeDeleteConfirmation">

                        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>

                        <div class="fixed inset-0 flex items-center justify-center p-4">
                            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-center"
                                @click.stop>
                                <div class="mx-auto w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center mb-4 dark:bg-rose-950/50">
                                    <ExclamationTriangleIcon class="w-6 h-6" />
                                </div>

                                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">
                                    {{ t('স্টক এন্ট্রি মুছে ফেলতে চান?', 'Delete Stock Entry?') }}
                                </h3>

                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">
                                    {{ t('এটি স্থায়ীভাবে স্টক রেকর্ড থেকে মুছে যাবে এবং ব্যাংক ব্যালেন্স ও পণ্যের গড় দাম পুনঃহিসাব হবে।', 'This stock entry will be permanently removed and stock records/balance will be recalculated.') }}
                                </p>

                                <div class="flex gap-2">
                                    <button
                                        @click="closeDeleteConfirmation"
                                        class="flex-1 rounded-xl border border-slate-200 bg-white py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        {{ t('বাতিল', 'Cancel') }}
                                    </button>
                                    <button
                                        @click="deleteStock"
                                        class="flex-1 rounded-xl bg-rose-600 py-2 text-xs font-bold text-white hover:bg-rose-500 shadow-xs">
                                        {{ t('হ্যাঁ, মুছে ফেলুন', 'Yes, Delete Entry') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';
import { useLanguage } from '@/composables/useLanguage';

import {
    PlusIcon,
    ExclamationTriangleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const { t, formatCurrency, formatNumber } = useLanguage();

const page = usePage();
const user = computed(() => page.props.auth.user);

const userIsAdmin = computed(() => {
    return user.value?.role?.name?.toLowerCase() === 'admin';
});

const stockToDelete = ref(null);
const showHistoryModal = ref(false);
const selectedProduct = ref(null);
const stockHistory = ref([]);
const showDeleteConfirmation = ref(false);

const props = defineProps({
    stocks: Object,
    summary: Object,
    filters: Object,
});

const localFilters = ref({
    search: props.filters?.search || '',
    sort_field: props.filters?.sort_field || 'product_id',
    sort_order: props.filters?.sort_order || 'asc',
    per_page: props.filters?.per_page || 15,
});

const searchTimeout = ref(null);

const debouncedSearch = () => {
    clearTimeout(searchTimeout.value);
    searchTimeout.value = setTimeout(() => {
        applyFilters();
    }, 400);
};

const applyFilters = () => {
    router.get(route('admin.product-stocks.index'), localFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const sortBy = (field) => {
    if (localFilters.value.sort_field === field) {
        localFilters.value.sort_order = localFilters.value.sort_order === 'asc' ? 'desc' : 'asc';
    } else {
        localFilters.value.sort_field = field;
        localFilters.value.sort_order = 'asc';
    }
    applyFilters();
};

const clearFilters = () => {
    localFilters.value = {
        search: '',
        sort_field: 'product_id',
        sort_order: 'asc',
        per_page: 15,
    };
    applyFilters();
};

const openDeleteConfirmation = (stock) => {
    stockToDelete.value = stock;
    showDeleteConfirmation.value = true;
};

const closeDeleteConfirmation = () => {
    stockToDelete.value = null;
    showDeleteConfirmation.value = false;
};

const deleteStock = async () => {
    if (!stockToDelete.value) return;

    try {
        const response = await axios.delete(
            route('admin.product-stocks.destroy', stockToDelete.value.id)
        );

        if (response.data.success) {
            alert(response.data.message || t('স্টক এন্ট্রি সফলভাবে বাতিল করা হয়েছে', 'Stock entry deleted successfully'));
            await showHistory(selectedProduct.value);
            closeDeleteConfirmation();
            router.reload({ only: ['stocks', 'stats'] });
        }
    } catch (error) {
        const msg = error.response?.data?.message || error.message || t('স্টক ডিলিট করতে সমস্যা হয়েছে', 'Failed to delete stock');
        alert('❌ ' + msg);
        closeDeleteConfirmation();
    }
};

const closeHistoryModal = () => {
    showHistoryModal.value = false;
    selectedProduct.value = null;
    stockHistory.value = [];
    closeDeleteConfirmation();
};

const showHistory = async (product) => {
    selectedProduct.value = product;
    try {
        const response = await axios.get(route('admin.product-stocks.history', product.id));
        stockHistory.value = response.data.data;
        showHistoryModal.value = true;
    } catch (error) {
        console.error('Error fetching stock history:', error);
    }
};
</script>
