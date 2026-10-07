<template>
    <AdminLayout :title="t('সাপ্লায়ার ও মহাজন খাতা', 'Suppliers')">
        <Head :title="t('সাপ্লায়ার ও মহাজন খাতা', 'Suppliers')" />

        <div class="space-y-6">
            <!-- Stats Header -->
            <div class="grid grid-cols-2 gap-2.5 sm:gap-4">
                <div class="rounded-2xl border border-slate-200/90 bg-white p-3.5 sm:p-5 shadow-xs dark:border-slate-700 dark:bg-slate-900 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 truncate">
                            {{ t('মোট সাপ্লায়ার', 'Total Suppliers') }}
                        </p>
                        <h3 class="mt-1 text-xl sm:text-2xl font-black text-slate-900 dark:text-white">{{ stats.total_suppliers }}</h3>
                    </div>
                    <div class="w-9 h-9 sm:w-11 sm:h-11 bg-indigo-50 dark:bg-indigo-950/50 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                        <UsersIcon class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/90 bg-white p-3.5 sm:p-5 shadow-xs dark:border-slate-700 dark:bg-slate-900 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 truncate">
                            {{ t('মোট দেনা / পাওনা', 'Total Payable') }}
                        </p>
                        <h3 class="mt-1 text-base sm:text-2xl font-black text-rose-600 dark:text-rose-400 truncate">৳{{ formatNumber(stats.total_payable) }}</h3>
                    </div>
                    <div class="w-9 h-9 sm:w-11 sm:h-11 bg-rose-50 dark:bg-rose-950/50 rounded-xl flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0">
                        <DollarSignIcon class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-80">
                        <input
                            type="text"
                            v-model="search"
                            :placeholder="t('নাম, কোম্পানি বা মোবাইল...', 'Search by name, company, phone...')"
                            class="w-full rounded-xl border-slate-200 bg-white pl-9 pr-4 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        />
                        <SearchIcon class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
                    </div>
                    <select
                        v-model="filters.status"
                        class="rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 shrink-0"
                    >
                        <option value="">{{ t('সকল অবস্থা', 'All') }}</option>
                        <option value="active">{{ t('সক্রিয়', 'Active') }}</option>
                        <option value="inactive">{{ t('নিষ্ক্রিয়', 'Inactive') }}</option>
                    </select>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-all shrink-0 w-full sm:w-auto"
                >
                    <PlusIcon class="w-4 h-4" />
                    <span>{{ t('নতুন সাপ্লায়ার যোগ', 'Add Supplier') }}</span>
                </button>
            </div>

            <!-- Suppliers List & Table -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-900">
                <!-- Mobile Cards (< md) -->
                <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
                    <div
                        v-for="supplier in suppliers.data"
                        :key="supplier.id"
                        class="p-4 space-y-3"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-sm">
                                    {{ supplier.name }}
                                </div>
                                <div v-if="supplier.company_name" class="text-xs text-slate-400">
                                    {{ supplier.company_name }}
                                </div>
                            </div>
                            <span
                                class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold shrink-0"
                                :class="supplier.status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
                            >
                                {{ supplier.status === 'active' ? t('সক্রিয়', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span>{{ t('ফোন:', 'Phone:') }} <strong class="font-mono text-slate-700 dark:text-slate-300">{{ supplier.phone || '—' }}</strong></span>
                            <span>{{ t('মোট ক্রয়:', 'Purchases:') }} <strong class="text-slate-900 dark:text-white">৳{{ formatNumber(supplier.total_purchases || 0) }}</strong></span>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                            <div>
                                <span class="text-[11px] text-slate-400 block">{{ t('বর্তমান পাওনা (দেনা)', 'Payable Due') }}</span>
                                <span v-if="supplier.current_balance > 0" class="font-black text-rose-600 dark:text-rose-400 text-sm">
                                    ৳{{ formatNumber(supplier.current_balance) }}
                                </span>
                                <span v-else class="text-emerald-600 dark:text-emerald-400 font-bold text-xs">৳0.00</span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <button
                                    v-if="supplier.current_balance > 0"
                                    @click="openPaymentModal(supplier)"
                                    class="rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 transition"
                                >
                                    {{ t('পরিশোধ', 'Pay') }}
                                </button>
                                <Link
                                    :href="route('admin.suppliers.show', supplier.id)"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-indigo-600 hover:text-indigo-800 dark:border-slate-700 dark:bg-slate-800 dark:text-indigo-400"
                                    :title="t('লেজার দেখুন', 'View Ledger')"
                                >
                                    <EyeIcon class="w-4 h-4" />
                                </Link>
                                <button
                                    @click="openEditModal(supplier)"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    :title="t('সম্পাদন', 'Edit')"
                                >
                                    <PencilIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="suppliers.data.length === 0" class="p-8 text-center text-slate-400 text-xs font-medium">
                        {{ t('কোনো সাপ্লায়ার পাওয়া যায়নি।', 'No suppliers found.') }}
                    </div>
                </div>

                <!-- Desktop Table (>= md) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                            <tr>
                                <th class="px-5 py-3 text-left">{{ t('সাপ্লায়ার / কোম্পানি', 'Supplier / Company') }}</th>
                                <th class="px-4 py-3 text-left">{{ t('যোগাযোগ', 'Contact') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('মোট ক্রয়', 'Total Purchases') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('বর্তমান পাওনা (দেনা)', 'Payable Due') }}</th>
                                <th class="px-4 py-3 text-center">{{ t('অবস্থা', 'Status') }}</th>
                                <th class="px-5 py-3 text-right">{{ t('অ্যাকশন', 'Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="supplier in suppliers.data" :key="supplier.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">{{ supplier.name }}</div>
                                    <div v-if="supplier.company_name" class="text-[11px] text-slate-400 mt-0.5">{{ supplier.company_name }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">
                                    <div class="font-mono">{{ supplier.phone || '—' }}</div>
                                    <div v-if="supplier.email" class="text-[11px] text-slate-400">{{ supplier.email }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-slate-900 dark:text-slate-100">
                                    ৳{{ formatNumber(supplier.total_purchases || 0) }}
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <span v-if="supplier.current_balance > 0" class="font-bold text-rose-600 dark:text-rose-400">
                                        ৳{{ formatNumber(supplier.current_balance) }}
                                    </span>
                                    <span v-else class="text-emerald-600 dark:text-emerald-400 font-bold">৳0.00</span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span
                                        class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold"
                                        :class="supplier.status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
                                    >
                                        {{ supplier.status === 'active' ? t('সক্রিয়', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right font-medium">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="supplier.current_balance > 0"
                                            @click="openPaymentModal(supplier)"
                                            class="rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-600 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 transition"
                                        >
                                            {{ t('দেনা পরিশোধ', 'Pay Due') }}
                                        </button>
                                        <Link
                                            :href="route('admin.suppliers.show', supplier.id)"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-indigo-600 hover:text-indigo-800 dark:border-slate-700 dark:bg-slate-800 dark:text-indigo-400"
                                        >
                                            <EyeIcon class="w-4 h-4" />
                                        </Link>
                                        <button
                                            @click="openEditModal(supplier)"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                        >
                                            <PencilIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="suppliers.data.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                    {{ t('কোনো সাপ্লায়ার পাওয়া যায়নি।', 'No suppliers found.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="suppliers.links && suppliers.links.length > 3" class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">
                    <Pagination :links="suppliers.links" />
                </div>
            </div>

            <!-- Create / Edit Modal -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-3 sm:p-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl max-w-lg w-full p-4 sm:p-6 border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                            {{ isEditing ? t('সাপ্লায়ার তথ্য সম্পাদন', 'Edit Supplier') : t('নতুন সাপ্লায়ার যোগ করুন', 'Add New Supplier') }}
                        </h3>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                    </div>

                    <form @submit.prevent="submitSupplier" class="mt-4 space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('সাপ্লায়ার / প্রতিনিধির নাম *', 'Contact Name *') }}</label>
                            <input
                                type="text"
                                v-model="form.name"
                                required
                                :placeholder="t('যেমনঃ মোঃ রহিম উল্লাহ', 'e.g. Rahim Ullah')"
                                class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('কোম্পানি / ব্র্যান্ডের নাম', 'Company / Brand Name') }}</label>
                            <input
                                type="text"
                                v-model="form.company_name"
                                :placeholder="t('যেমনঃ মেঘনা গ্রুপ, আকিজ ফুডস...', 'e.g. Meghna Group')"
                                class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('মোবাইল নম্বর', 'Phone Number') }}</label>
                                <input
                                    type="text"
                                    v-model="form.phone"
                                    placeholder="017xxxxxxxx"
                                    class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('ইমেইল', 'Email') }}</label>
                                <input
                                    type="email"
                                    v-model="form.email"
                                    placeholder="supplier@company.com"
                                    class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('পূর্বের পাওনা / প্রারম্ভিক দেনা (৳)', 'Opening Payable Balance (BDT)') }}
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                v-model="form.opening_balance"
                                placeholder="0.00"
                                class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                            <p v-if="isEditing" class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                                {{ t('⚠️ প্রারম্ভিক দেনা পরিবর্তন করলে মোট দেনাতেও সমন্বয় করা হবে।', '⚠️ Updating opening balance will adjust the current payable balance.') }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('ঠিকানা / নোট', 'Address / Notes') }}</label>
                            <textarea
                                v-model="form.address"
                                rows="2"
                                :placeholder="t('অফিস বা গুদামের ঠিকানা...', 'Shop/Office location...')"
                                class="w-full rounded-xl border-slate-200 bg-white p-2.5 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('অবস্থা', 'Status') }}</label>
                            <select
                                v-model="form.status"
                                class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            >
                                <option value="active">{{ t('সক্রিয়', 'Active') }}</option>
                                <option value="inactive">{{ t('নিষ্ক্রিয়', 'Inactive') }}</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button
                                type="button"
                                @click="showModal = false"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            >
                                {{ t('বাতিল', 'Cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-500 disabled:opacity-50"
                            >
                                {{ isEditing ? t('পরিবর্তন সংরক্ষণ', 'Update Supplier') : t('সংরক্ষণ করুন', 'Save Supplier') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Pay Supplier Due Modal -->
            <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-3 sm:p-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl max-w-md w-full p-4 sm:p-6 border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ t('সাপ্লায়ার দেনা পরিশোধ', 'Pay Supplier Due') }}</h3>
                            <p class="text-xs text-slate-500">{{ activeSupplier?.name }} ({{ t('পাওনা:', 'Due:') }} ৳{{ formatNumber(activeSupplier?.current_balance) }})</p>
                        </div>
                        <button @click="showPaymentModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                    </div>

                    <form @submit.prevent="submitPayment" class="mt-4 space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('পরিশোধের পরিমাণ (টাকা) *', 'Payment Amount *') }}</label>
                            <input
                                type="number"
                                step="0.01"
                                v-model="paymentForm.amount"
                                :max="activeSupplier?.current_balance"
                                required
                                class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-base font-bold text-rose-600 dark:bg-slate-800 dark:border-slate-700 dark:text-rose-400"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('যে ব্যাংক / ক্যাশ হিসাব থেকে পরিশোধ হবে *', 'Pay From Bank / Cash Account *') }}</label>
                            <select
                                v-model="paymentForm.bank_account_id"
                                required
                                class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                                :class="isInsufficientBalance ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500' : ''"
                            >
                                <option value="" disabled>{{ t('-- ব্যাংক / ক্যাশ হিসাব নির্বাচন করুন * --', '-- Select Bank / Cash Account * --') }}</option>
                                <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
                                    {{ acc.bank_name }} - {{ acc.account_number || t('ক্যাশ', 'Cash') }} ({{ t('ব্যালেন্স:', 'Bal:') }} ৳{{ formatNumber(acc.current_balance) }})
                                </option>
                            </select>

                            <!-- Instant Balance Preview -->
                            <div v-if="selectedBankAccount" class="mt-2 p-2 rounded-lg text-xs flex items-center justify-between"
                                 :class="isInsufficientBalance ? 'bg-rose-50 border border-rose-200 text-rose-700 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300' : 'bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300'">
                                <span class="font-medium">{{ t('নির্বাচিত হিসাবে উপলব্ধ ব্যালেন্স:', 'Available in Account:') }}</span>
                                <span class="font-bold font-mono text-sm">৳{{ formatNumber(selectedBankAccount.current_balance) }}</span>
                            </div>

                            <!-- Instant Insufficient Balance Alert -->
                            <div v-if="isInsufficientBalance" class="mt-2 p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <p class="font-bold">{{ t('অপর্যাপ্ত ব্যাংক ব্যালেন্স!', 'Insufficient Balance!') }}</p>
                                    <p>{{ t('নির্বাচিত ব্যাংক হিসাবে পর্যাপ্ত টাকা নেই। অনুগ্রহ করে অন্য হিসাব নির্বাচন করুন।', 'Selected account does not have sufficient balance for this payment.') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('মাধ্যম', 'Payment Method') }}</label>
                                <select
                                    v-model="paymentForm.payment_method"
                                    class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                                >
                                    <option value="cash">{{ t('নগদ (Cash)', 'Cash') }}</option>
                                    <option value="bank">{{ t('ব্যাংক ট্রান্সফার', 'Bank Transfer') }}</option>
                                    <option value="mobile">{{ t('মোবাইল ব্যাংকিং', 'Mobile') }}</option>
                                    <option value="check">{{ t('চেক (Cheque)', 'Cheque') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('তারিখ *', 'Date *') }}</label>
                                <input
                                    type="date"
                                    v-model="paymentForm.payment_date"
                                    required
                                    class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ t('রেফারেন্স নং / নোট', 'Reference / Note') }}</label>
                            <input
                                type="text"
                                v-model="paymentForm.reference_no"
                                :placeholder="t('যেমনঃ চেক নম্বর বা রসিদ নং', 'e.g. Check # or Receipt #')"
                                class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button
                                type="button"
                                @click="showPaymentModal = false"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            >
                                {{ t('বাতিল', 'Cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="submittingPayment || isInsufficientBalance || !paymentForm.bank_account_id || paymentForm.amount <= 0"
                                class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ t('পরিশোধ নিশ্চিত করুন', 'Confirm Payment') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import debounce from 'lodash/debounce'
import {
    Users as UsersIcon,
    DollarSign as DollarSignIcon,
    Search as SearchIcon,
    Plus as PlusIcon,
    Eye as EyeIcon,
    Pencil as PencilIcon
} from 'lucide-vue-next'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    suppliers: Object,
    bankAccounts: Array,
    filters: Object,
    stats: Object,
})

const search = ref(props.filters?.search || '')
const filters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
})

const showModal = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const editingId = ref(null)

const form = ref({
    name: '',
    company_name: '',
    phone: '',
    email: '',
    address: '',
    opening_balance: 0,
    status: 'active',
})

const showPaymentModal = ref(false)
const submittingPayment = ref(false)
const activeSupplier = ref(null)

const paymentForm = ref({
    amount: 0,
    bank_account_id: '',
    payment_method: 'cash',
    payment_date: new Date().toISOString().split('T')[0],
    reference_no: '',
})

const formatNumber = (val) => {
    return Number(val || 0).toLocaleString('bn-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}

watch(search, debounce((val) => {
    filters.value.search = val
    router.get(route('admin.suppliers.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    })
}, 350))

watch(() => filters.value.status, () => {
    router.get(route('admin.suppliers.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    })
})

const openCreateModal = () => {
    isEditing.value = false
    editingId.value = null
    form.value = {
        name: '',
        company_name: '',
        phone: '',
        email: '',
        address: '',
        opening_balance: 0,
        status: 'active',
    }
    showModal.value = true
}

const openEditModal = (supplier) => {
    isEditing.value = true
    editingId.value = supplier.id
    form.value = {
        name: supplier.name,
        company_name: supplier.company_name || '',
        phone: supplier.phone || '',
        email: supplier.email || '',
        address: supplier.address || '',
        opening_balance: supplier.opening_balance || 0,
        status: supplier.status || 'active',
    }
    showModal.value = true
}

const submitSupplier = () => {
    submitting.value = true
    if (isEditing.value) {
        router.put(route('admin.suppliers.update', editingId.value), form.value, {
            onSuccess: () => {
                showModal.value = false
                submitting.value = false
            },
            onError: () => {
                submitting.value = false
            }
        })
    } else {
        router.post(route('admin.suppliers.store'), form.value, {
            onSuccess: () => {
                showModal.value = false
                submitting.value = false
            },
            onError: () => {
                submitting.value = false
            }
        })
    }
}

const selectedBankAccount = computed(() => {
    return props.bankAccounts?.find(acc => acc.id == paymentForm.value.bank_account_id) || null
})

const isInsufficientBalance = computed(() => {
    if (!selectedBankAccount.value) return false
    const amt = parseFloat(paymentForm.value.amount || 0)
    const bal = parseFloat(selectedBankAccount.value.current_balance || 0)
    return amt > bal
})

const openPaymentModal = (supplier) => {
    activeSupplier.value = supplier
    paymentForm.value = {
        amount: supplier.current_balance,
        bank_account_id: '',
        payment_method: 'cash',
        payment_date: new Date().toISOString().split('T')[0],
        reference_no: '',
    }
    showPaymentModal.value = true
}

const submitPayment = () => {
    if (!activeSupplier.value) return
    if (!paymentForm.value.bank_account_id) {
        alert(t('অনুগ্রহ করে পরিশোধের জন্য ব্যাংক বা ক্যাশ হিসাব নির্বাচন করুন', 'Please select a bank or cash account for payment'))
        return
    }
    if (isInsufficientBalance.value) {
        alert(t('নির্বাচিত ব্যাংক হিসাবে পর্যাপ্ত ব্যালেন্স নেই!', 'Insufficient balance in selected account!'))
        return
    }
    submittingPayment.value = true
    router.post(route('admin.suppliers.add-payment', activeSupplier.value.id), paymentForm.value, {
        onSuccess: () => {
            showPaymentModal.value = false
            submittingPayment.value = false
        },
        onError: () => {
            submittingPayment.value = false
        }
    })
}
</script>
