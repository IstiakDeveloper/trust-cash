<template>
    <AdminLayout :title="t('নতুন স্টক যোগ করুন', 'Add Product Stock')">
        <Head :title="t('নতুন স্টক যোগ করুন', 'Add Product Stock')" />

        <div class="mx-auto max-w-4xl space-y-5 pb-12">
            <!-- Header & Breadcrumbs -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 mb-1">
                        <Link :href="route('admin.product-stocks.index')" class="hover:underline flex items-center gap-1">
                            <CubeIcon class="h-3.5 w-3.5" />
                            <span>{{ t('পণ্য স্টক', 'Product Stocks') }}</span>
                        </Link>
                        <span>/</span>
                        <span class="text-slate-400 dark:text-slate-500">{{ t('নতুন স্টক', 'Create') }}</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ t('নতুন স্টক ও ক্রয় এন্ট্রি', 'Add New Product Stock') }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('পণ্যের মজুদ বাড়াতে নগদ ব্যাংক পেমেন্ট অথবা সরবরাহকারীর কাছ থেকে বাকিতে ক্রয় করুন', 'Add inventory to your product stock via bank payment or credit purchase') }}
                    </p>
                </div>

                <div>
                    <Link :href="route('admin.product-stocks.index')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-all">
                        <ArrowLeftIcon class="h-3.5 w-3.5" />
                        <span>{{ t('তালিকায় ফিরুন', 'Back to Stocks') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Main Form Card -->
            <div class="rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <form @submit.prevent="submit" class="p-6 sm:p-8 space-y-6">

                    <!-- Product Selection Field -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">
                                {{ t('পণ্য নির্বাচন করুন', 'Select Product') }} <span class="text-rose-500">*</span>
                            </label>
                            <span v-if="selectedProductData" class="text-[11px] font-mono text-slate-400">
                                SKU: {{ selectedProductData.sku }}
                            </span>
                        </div>

                        <SearchableSelect
                            v-model="form.product_id"
                            :options="productsForSelect"
                            label-key="displayName"
                            value-key="id"
                            description-key="stockInfo"
                            :placeholder="t('নাম বা SKU দিয়ে পণ্য খুঁজুন...', 'Search and select a product by name or SKU...')"
                            :show-badge="!!selectedProductData"
                            :badge-text="selectedProductData ? `${t('মজুদ:', 'Stock:')} ${formatNumber(selectedProductData.current_stock)}` : ''"
                            @change="onProductChange"
                        />
                        <p v-if="form.errors.product_id" class="mt-1.5 text-xs text-rose-600 dark:text-rose-400 font-medium">
                            {{ form.errors.product_id }}
                        </p>
                    </div>

                    <!-- Selected Product Info Banner -->
                    <div v-if="selectedProductData"
                        class="grid grid-cols-1 sm:grid-cols-3 gap-3 rounded-2xl bg-indigo-50/70 p-4 border border-indigo-100 dark:bg-indigo-950/30 dark:border-indigo-900/50">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900 dark:text-indigo-300">
                                <CubeIcon class="h-5 w-5" />
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-indigo-950/60 dark:text-indigo-300 uppercase">
                                    {{ t('বর্তমান মজুদ', 'Current Stock') }}
                                </span>
                                <p class="text-base font-black text-indigo-950 dark:text-indigo-100">
                                    {{ formatNumber(selectedProductData.current_stock) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900 dark:text-emerald-300">
                                <TagIcon class="h-5 w-5" />
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-emerald-950/60 dark:text-emerald-300 uppercase">
                                    {{ t('পূর্ববর্তী একক মূল্য', 'Last Cost Price') }}
                                </span>
                                <p class="text-base font-black text-emerald-950 dark:text-emerald-100">
                                    {{ formatCurrency(selectedProductData.last_unit_cost) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-200/80 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                <span class="text-xs font-mono font-bold">#</span>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                                    {{ t('বারকোড / SKU', 'SKU Code') }}
                                </span>
                                <p class="text-sm font-mono font-bold text-slate-800 dark:text-slate-200 truncate">
                                    {{ selectedProductData.sku || 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity, Total Cost and Stock Date (3 Columns) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Quantity -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                                {{ t('ক্রয়ের পরিমাণ', 'Quantity') }} <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                v-model.number="form.quantity"
                                min="0.01"
                                step="any"
                                required
                                :placeholder="t('যেমন: ১০', 'e.g. 10')"
                                class="block w-full rounded-xl border-slate-200 bg-white py-2.5 px-3.5 text-xs font-semibold text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                            <p v-if="form.errors.quantity" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                {{ form.errors.quantity }}
                            </p>
                        </div>

                        <!-- Total Cost -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                                {{ t('মোট ক্রয় মূল্য', 'Total Cost') }} <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative rounded-xl shadow-xs">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-xs font-bold text-slate-400">৳</span>
                                </div>
                                <input
                                    type="number"
                                    v-model.number="form.total_cost"
                                    min="0.01"
                                    step="any"
                                    required
                                    :placeholder="t('যেমন: ৫০০.০০', '0.00')"
                                    class="block w-full rounded-xl border-slate-200 bg-white py-2.5 pl-8 pr-3 text-xs font-semibold text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                />
                            </div>
                            <p v-if="form.errors.total_cost" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                {{ form.errors.total_cost }}
                            </p>
                        </div>

                        <!-- Stock Date -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                                {{ t('স্টক ও ক্রয়ের তারিখ', 'Stock Date') }} <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="date"
                                v-model="form.date"
                                :max="todayDate"
                                required
                                class="block w-full rounded-xl border-slate-200 bg-white py-2.5 px-3.5 text-xs font-semibold text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                            <p v-if="form.errors.date" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                {{ form.errors.date }}
                            </p>
                        </div>
                    </div>

                    <!-- Calculated Unit Cost Banner -->
                    <div v-if="unitCost > 0"
                        class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-4 dark:border-emerald-800/60 dark:bg-emerald-950/30 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                <span class="font-bold text-sm">৳</span>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                                    {{ t('গণনাকৃত একক ক্রয়মূল্য (Unit Cost)', 'Calculated Unit Cost') }}
                                </span>
                                <p class="text-xs text-emerald-700/80 dark:text-emerald-400/80">
                                    {{ t('মোট ক্রয়মূল্যকে পরিমাণ দিয়ে ভাগ করে স্বয়ংক্রিয়ভাবে হিসাব করা হয়েছে', 'Automatically calculated (Total Cost ÷ Quantity)') }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xl font-black text-emerald-700 dark:text-emerald-300">
                                {{ formatCurrency(unitCost) }}
                            </span>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 ml-1">
                                / {{ t('একক', 'unit') }}
                            </span>
                        </div>
                    </div>

                    <!-- BAKI / CREDIT SWITCH SECTION -->
                    <div class="rounded-2xl border p-4 sm:p-5 transition-all"
                        :class="form.is_credit 
                            ? 'bg-amber-50/60 border-amber-200 dark:bg-amber-950/20 dark:border-amber-900/60' 
                            : 'bg-slate-50 dark:bg-slate-800/40 border-slate-200/80 dark:border-slate-700/60'">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <BanknotesIcon class="h-4 w-4" :class="form.is_credit ? 'text-amber-600' : 'text-indigo-600'" />
                                        <span>{{ t('বাকিতে ক্রয় করবেন? (Credit Purchase)', 'Purchase on Credit / Baki?') }}</span>
                                    </h3>
                                    <!-- Badge -->
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                                        :class="form.is_credit 
                                            ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300' 
                                            : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300'">
                                        {{ form.is_credit ? t('বাকি / দেনা ক্রয়', 'Credit (Due)') : t('নগদ / ব্যাংক পরিশোধ', 'Paid (Bank)') }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ form.is_credit 
                                        ? t('সুইচ অন রয়েছে: কোনো ব্যাংক ব্যালেন্স কাটা হবে না, সরবরাহকারীর হিসাবে বাকি দেনা যুক্ত হবে।', 'Switch is ON: No bank deduction. Due will be recorded under selected supplier.') 
                                        : t('সুইচ অফ রয়েছে: আপনার ব্যাংক অ্যাকাউন্ট থেকে সরাসরি খরচ পরিশোধ হবে। বাকিতে কিনলে সুইচটি অন করুন।', 'Switch is OFF: Amount will be deducted from bank. Turn ON to buy on credit from a supplier.') }}
                                </p>
                            </div>

                            <!-- Modern Toggle Switch Button -->
                            <div class="flex items-center gap-2.5 shrink-0">
                                <span class="text-xs font-bold" :class="!form.is_credit ? 'text-slate-900 dark:text-white' : 'text-slate-400'">
                                    {{ t('নগদ/ব্যাংক', 'Cash/Bank') }}
                                </span>

                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="form.is_credit"
                                    @click="toggleCreditSwitch"
                                    class="relative inline-flex h-6 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                                    :class="form.is_credit ? 'bg-amber-500' : 'bg-slate-300 dark:bg-slate-600'"
                                >
                                    <span
                                        aria-hidden="true"
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out"
                                        :class="form.is_credit ? 'translate-x-6' : 'translate-x-0'"
                                    />
                                </button>

                                <span class="text-xs font-bold" :class="form.is_credit ? 'text-amber-600 dark:text-amber-400 font-extrabold' : 'text-slate-400'">
                                    {{ t('বাকি', 'Credit') }}
                                </span>
                            </div>
                        </div>

                        <!-- CONDITIONAL: SUPPLIER SELECTION (If Credit / Baki is ON) -->
                        <div v-if="form.is_credit" class="mt-4 pt-4 border-t border-amber-200/80 dark:border-amber-900/60 space-y-3">
                            <label class="block text-xs font-bold text-amber-900 dark:text-amber-200 uppercase tracking-wider">
                                {{ t('সরবরাহকারী নির্বাচন করুন (Supplier)', 'Select Supplier') }} <span class="text-rose-500">*</span>
                            </label>

                            <SearchableSelect
                                v-model="form.supplier_id"
                                :options="suppliersForSelect"
                                label-key="displayName"
                                value-key="id"
                                description-key="supplierInfo"
                                :placeholder="t('সরবরাহকারীর নাম, কোম্পানি বা ফোন নম্বর খুঁজুন...', 'Search supplier by name, company, or phone...')"
                                :show-badge="!!selectedSupplier"
                                :badge-text="selectedSupplier ? `${t('বকেয়া:', 'Due:')} ${formatCurrency(selectedSupplier.current_balance)}` : ''"
                                @change="onSupplierChange"
                            />
                            <p v-if="form.errors.supplier_id" class="mt-1 text-xs text-rose-600 dark:text-rose-400 font-medium">
                                {{ form.errors.supplier_id }}
                            </p>

                            <!-- Supplier Details & Due Preview Card -->
                            <div v-if="selectedSupplier"
                                class="rounded-xl bg-white dark:bg-slate-900 p-3.5 border border-amber-200 dark:border-amber-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <BuildingOfficeIcon class="h-4 w-4 text-amber-600" />
                                        <span class="font-bold text-slate-900 dark:text-white">{{ selectedSupplier.name }}</span>
                                        <span v-if="selectedSupplier.company_name" class="text-slate-500 dark:text-slate-400">
                                            ({{ selectedSupplier.company_name }})
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-slate-500 dark:text-slate-400 text-[11px]">
                                        {{ t('মোবাইল:', 'Phone:') }} {{ selectedSupplier.phone || 'N/A' }}
                                    </p>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-4 text-left sm:text-right pt-2 sm:pt-0 border-t sm:border-t-0 border-amber-100 dark:border-amber-900/60">
                                    <div>
                                        <span class="text-[10px] uppercase font-bold text-slate-400">{{ t('বর্তমান বকেয়া দেনা', 'Current Due') }}</span>
                                        <p class="font-bold text-slate-700 dark:text-slate-300">
                                            {{ formatCurrency(selectedSupplier.current_balance) }}
                                        </p>
                                    </div>
                                    <div class="border-l border-slate-200 dark:border-slate-700 pl-4">
                                        <span class="text-[10px] uppercase font-bold text-amber-600 dark:text-amber-400">{{ t('ক্রয় শেষে মোট দেনা হবে', 'New Due Balance') }}</span>
                                        <p class="font-black text-amber-600 dark:text-amber-400 text-sm">
                                            {{ formatCurrency(newSupplierTotalDue) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CONDITIONAL: BANK ACCOUNT SELECTION (If Credit is OFF) -->
                        <div v-else class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700 space-y-3">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">
                                {{ t('পরিশোধের ব্যাংক অ্যাকাউন্ট (Bank Account)', 'Payment Bank Account') }} <span class="text-rose-500">*</span>
                            </label>

                            <SearchableSelect
                                v-model="form.bank_account_id"
                                :options="bankAccountsForSelect"
                                label-key="account_name"
                                value-key="id"
                                description-key="bankInfo"
                                :placeholder="t('ব্যাংক অ্যাকাউন্ট নির্বাচন করুন...', 'Search and select a bank account...')"
                                :show-badge="!!selectedBank"
                                :badge-text="selectedBank ? formatCurrency(selectedBank.current_balance) : ''"
                                @change="onBankChange"
                            />
                            <p v-if="form.errors.bank_account_id" class="mt-1 text-xs text-rose-600 dark:text-rose-400 font-medium">
                                {{ form.errors.bank_account_id }}
                            </p>

                            <!-- Insufficient Balance Warning -->
                            <div v-if="selectedBank && Number(form.total_cost) > Number(selectedBank.current_balance)"
                                class="rounded-xl bg-rose-50 border border-rose-200 p-3.5 dark:bg-rose-950/30 dark:border-rose-900/60 flex items-start gap-3">
                                <ExclamationTriangleIcon class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" />
                                <div>
                                    <h4 class="text-xs font-bold text-rose-800 dark:text-rose-300">
                                        {{ t('পর্যাপ্ত ব্যালেন্স নেই (Insufficient Balance)', 'Insufficient Balance in Bank Account') }}
                                    </h4>
                                    <p class="text-[11px] text-rose-700 dark:text-rose-400 mt-0.5">
                                        {{ t('নির্বাচিত ব্যাংক অ্যাকাউন্টে ব্যালেন্স রয়েছে', 'Selected bank balance is') }}
                                        <strong class="font-bold">{{ formatCurrency(selectedBank.current_balance) }}</strong>,
                                        {{ t('কিন্তু মোট ক্রয়ের জন্য প্রয়োজন', 'but purchase total requires') }}
                                        <strong class="font-bold">{{ formatCurrency(form.total_cost) }}</strong>।
                                        {{ t('অন্য অ্যাকাউন্ট নির্বাচন করুন অথবা ওপরের সুইচটি অন করে বাকিতে ক্রয় করুন।', 'Please choose another account or toggle ON Credit Purchase above.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Note / Remarks -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                            {{ t('মন্তব্য / চালান নোট (ঐচ্ছিক)', 'Note / Remarks (Optional)') }}
                        </label>
                        <textarea
                            v-model="form.note"
                            rows="2"
                            :placeholder="t('স্টক বা চালান সংক্রান্ত কোনো অতিরিক্ত তথ্য থাকলে লিখুন...', 'Add any additional notes about this stock entry...')"
                            class="block w-full rounded-xl border-slate-200 bg-white py-2.5 px-3.5 text-xs font-semibold text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <!-- Actions Toolbar -->
                    <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center sm:justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <Link :href="route('admin.product-stocks.index')"
                            class="w-full sm:w-auto text-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 active:scale-95 transition-all">
                            {{ t('বাতিল', 'Cancel') }}
                        </Link>

                        <button
                            type="submit"
                            :disabled="form.processing || isSubmitDisabled"
                            class="w-full sm:w-auto justify-center inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed transition-all cursor-pointer"
                        >
                            <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <CheckIcon v-else class="h-4 w-4" />
                            <span>
                                {{ form.processing 
                                    ? t('সংরক্ষণ হচ্ছে...', 'Saving...') 
                                    : (form.is_credit ? t('বাকিতে স্টক সংরক্ষণ করুন', 'Save Credit Stock Entry') : t('স্টক ও পেমেন্ট সংরক্ষণ করুন', 'Save Stock Entry')) }}
                            </span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useLanguage } from '@/composables/useLanguage';
import {
    CubeIcon,
    ArrowLeftIcon,
    TagIcon,
    BanknotesIcon,
    BuildingOfficeIcon,
    ExclamationTriangleIcon,
    CheckIcon
} from '@heroicons/vue/24/outline';

const { t, formatCurrency, formatNumber } = useLanguage();

const props = defineProps({
    products: {
        type: Array,
        default: () => []
    },
    bankAccounts: {
        type: Array,
        default: () => []
    },
    suppliers: {
        type: Array,
        default: () => []
    }
});

const todayDate = new Date().toISOString().split('T')[0];

const form = useForm({
    product_id: '',
    quantity: '',
    total_cost: '',
    date: todayDate,
    note: '',
    is_credit: false,
    bank_account_id: '',
    supplier_id: ''
});

// Toggle credit switch
const toggleCreditSwitch = () => {
    form.is_credit = !form.is_credit;
    if (form.is_credit) {
        form.bank_account_id = '';
    } else {
        form.supplier_id = '';
    }
};

// Transform products for searchable select
const productsForSelect = computed(() => {
    return props.products.map(product => ({
        ...product,
        displayName: `${product.name} (${product.sku})`,
        stockInfo: `${t('মজুদ:', 'Stock:')} ${formatNumber(product.current_stock)} | ${t('শেষ ক্রয়:', 'Last Cost:')} ${formatCurrency(product.last_unit_cost)}`
    }));
});

// Transform bank accounts for searchable select
const bankAccountsForSelect = computed(() => {
    return props.bankAccounts.map(account => ({
        ...account,
        bankInfo: `${account.bank_name || ''} | ${t('হিসেব নং:', 'A/C:')} ${account.account_number || 'N/A'} | ${t('ব্যালেন্স:', 'Balance:')} ${formatCurrency(account.current_balance)}`
    }));
});

// Transform suppliers for searchable select
const suppliersForSelect = computed(() => {
    return props.suppliers.map(supplier => ({
        ...supplier,
        displayName: `${supplier.name}${supplier.company_name ? ' (' + supplier.company_name + ')' : ''}`,
        supplierInfo: `${supplier.phone ? t('ফোন:', 'Phone:') + ' ' + supplier.phone + ' | ' : ''}${t('বর্তমান দেনা:', 'Due:')} ${formatCurrency(supplier.current_balance)}`
    }));
});

const selectedProductData = computed(() => {
    return props.products.find(p => p.id === form.product_id);
});

const selectedBank = computed(() => {
    return props.bankAccounts.find(account => account.id === form.bank_account_id);
});

const selectedSupplier = computed(() => {
    return props.suppliers.find(supplier => supplier.id === form.supplier_id);
});

const newSupplierTotalDue = computed(() => {
    if (!selectedSupplier.value) return 0;
    const current = Number(selectedSupplier.value.current_balance || 0);
    const added = Number(form.total_cost || 0);
    return current + added;
});

const unitCost = computed(() => {
    if (!form.quantity || !form.total_cost || Number(form.quantity) <= 0) return 0;
    return Number(form.total_cost) / Number(form.quantity);
});

const isSubmitDisabled = computed(() => {
    if (!form.product_id || !form.quantity || !form.total_cost || !form.date) return true;
    if (form.is_credit) {
        return !form.supplier_id;
    } else {
        if (!form.bank_account_id) return true;
        if (selectedBank.value && Number(form.total_cost) > Number(selectedBank.value.current_balance)) {
            return true;
        }
    }
    return false;
});

const onProductChange = (product) => {
    if (product) {
        form.product_id = product.id;
    }
};

const onBankChange = (account) => {
    if (account) {
        form.bank_account_id = account.id;
    }
};

const onSupplierChange = (supplier) => {
    if (supplier) {
        form.supplier_id = supplier.id;
    }
};

const submit = () => {
    form.post(route('admin.product-stocks.store'));
};
</script>
