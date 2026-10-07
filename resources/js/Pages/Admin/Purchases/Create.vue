<template>
    <Head :title="t('নতুন পণ্য ক্রয়', 'New Purchase')" />
    <AdminLayout :title="t('নতুন ক্রয় অর্ডার', 'New Purchase Order')">
        <div class="container mx-auto px-3 sm:px-4 py-4 sm:py-6 max-w-6xl pb-20 sm:pb-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.purchases.index')"
                        class="p-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl text-gray-700 dark:text-gray-200 transition shrink-0"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div class="min-w-0">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white truncate">{{ t('নতুন ক্রয় অর্ডার তৈরি করুন', 'Create Purchase Order') }}</h2>
                        <p class="text-xs text-gray-500 truncate">{{ t('সরবরাহকারী থেকে স্টক ইন করুন এবং ইনভেন্টরি আপডেট করুন', 'Record stock received from supplier and update inventory') }}</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submitPurchase" class="space-y-4 sm:space-y-6">
                <!-- Top Details: Supplier & Date -->
                <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                    <div>
                        <label class="block font-semibold text-sm text-gray-700 dark:text-gray-300 mb-1.5">{{ t('সরবরাহকারী নির্বাচন করুন *', 'Select Supplier *') }}</label>
                        <select
                            v-model="form.supplier_id"
                            required
                            class="w-full px-3.5 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                        >
                            <option value="" disabled>{{ t('-- সরবরাহকারী বেছে নিন --', '-- Choose Supplier --') }}</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">
                                {{ s.name }} {{ s.company_name ? `(${s.company_name})` : '' }} - {{ t('পূর্ব বকেয়া:', 'Prev Due:') }} ৳{{ formatNumber(s.current_balance) }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-sm text-gray-700 dark:text-gray-300 mb-1.5">{{ t('ক্রয় তারিখ *', 'Purchase Date *') }}</label>
                        <input
                            type="date"
                            v-model="form.purchase_date"
                            required
                            class="w-full px-3.5 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-sm text-gray-700 dark:text-gray-300 mb-1.5">{{ t('পণ্য দ্রুত অনুসন্ধান / বারকোড', 'Product Quick Search / Barcode') }}</label>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="productSearch"
                                :placeholder="t('পণ্যের নাম লিখুন বা স্ক্যান করুন...', 'Type product name or scan...')"
                                class="w-full pl-9 pr-3.5 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                @keydown.enter.prevent="selectFirstProduct"
                            />
                            <SearchIcon class="w-4 h-4 text-gray-400 absolute left-3 top-3" />

                            <!-- Search Results Dropdown -->
                            <div
                                v-if="filteredProducts.length > 0 && productSearch.trim().length > 1"
                                class="absolute left-0 right-0 top-full mt-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl z-30 max-h-60 overflow-y-auto"
                            >
                                <div
                                    v-for="p in filteredProducts"
                                    :key="p.id"
                                    @click="addProductToItems(p)"
                                    class="p-3 hover:bg-blue-50 dark:hover:bg-gray-750 cursor-pointer border-b border-gray-100 dark:border-gray-700 flex justify-between items-center text-xs transition"
                                >
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">{{ p.name }}</div>
                                        <div class="text-gray-400">SKU: {{ p.sku || 'N/A' }} | {{ t('বারকোড', 'Barcode') }}: {{ p.barcode || 'N/A' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-blue-600 font-bold">৳{{ formatNumber(p.cost_price || p.selling_price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Table & Mobile Cards -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden">
                    <div class="p-3.5 sm:p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/80">
                        <h3 class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm">{{ t('ক্রয়কৃত পণ্যসমূহ', 'Purchase Items') }}</h3>
                        <span class="text-xs text-gray-500 font-medium">{{ form.items.length }} {{ t('টি পণ্য নির্বাচিত', 'item(s) selected') }}</span>
                    </div>

                    <!-- Mobile Card List (< md) -->
                    <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="(item, index) in form.items" :key="index" class="p-3.5 space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="font-bold text-gray-900 dark:text-white text-sm">
                                        <span class="text-gray-400 font-normal mr-1">#{{ index + 1 }}</span>
                                        {{ item.product_name }}
                                    </div>
                                    <div v-if="item.sku" class="text-xs text-gray-400">SKU: {{ item.sku }}</div>
                                </div>
                                <button
                                    type="button"
                                    @click="removeItem(index)"
                                    class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition shrink-0"
                                    :title="t('মুছে ফেলুন', 'Remove')"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">{{ t('একক', 'Unit') }}</label>
                                    <select
                                        v-model="item.unit_id"
                                        class="w-full text-xs px-2.5 py-2 border rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    >
                                        <option :value="null">{{ t('ডিফল্ট', 'Default') }}</option>
                                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">{{ t('পরিমাণ', 'Quantity') }} *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        v-model.number="item.quantity"
                                        min="0.01"
                                        required
                                        class="w-full text-center text-xs px-2.5 py-2 border rounded-xl dark:bg-gray-700 dark:border-gray-600 font-black text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">{{ t('ক্রয় মূল্য (৳)', 'Cost Price (৳)') }} *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        v-model.number="item.purchase_price"
                                        min="0"
                                        required
                                        class="w-full text-right text-xs px-2.5 py-2 border rounded-xl dark:bg-gray-700 dark:border-gray-600 font-bold dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">{{ t('বিক্রয় মূল্য (৳)', 'Selling Price (৳)') }}</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        v-model.number="item.selling_price"
                                        min="0"
                                        :placeholder="t('ঐচ্ছিক', 'Optional')"
                                        class="w-full text-right text-xs px-2.5 py-2 border rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700/60">
                                <span class="text-xs text-gray-500 font-medium">{{ t('উপমোট:', 'Subtotal:') }}</span>
                                <span class="font-black text-sm text-gray-900 dark:text-white">৳{{ formatNumber(item.purchase_price * item.quantity) }}</span>
                            </div>
                        </div>

                        <div v-if="form.items.length === 0" class="px-4 py-10 text-center text-gray-400 font-medium text-xs">
                            {{ t('কোনো পণ্য যোগ করা হয়নি। উপরে সার্চ করে ক্রয় অর্ডারে পণ্য যোগ করুন।', 'No items added yet. Search above to add products to this purchase order.') }}
                        </div>
                    </div>

                    <!-- Desktop Table (>= md) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">#</th>
                                    <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('পণ্য', 'Product') }}</th>
                                    <th class="px-4 py-3 text-center font-bold text-gray-600 dark:text-gray-300 w-32">{{ t('একক', 'Unit') }}</th>
                                    <th class="px-4 py-3 text-center font-bold text-gray-600 dark:text-gray-300 w-32">{{ t('ক্রয় মূল্য (৳)', 'Cost Price (৳)') }}</th>
                                    <th class="px-4 py-3 text-center font-bold text-gray-600 dark:text-gray-300 w-28">{{ t('পরিমাণ', 'Quantity') }}</th>
                                    <th class="px-4 py-3 text-center font-bold text-gray-600 dark:text-gray-300 w-32">{{ t('বিক্রয় মূল্য (৳)', 'Selling Price (৳)') }}</th>
                                    <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300 w-32">{{ t('উপমোট (৳)', 'Subtotal (৳)') }}</th>
                                    <th class="px-4 py-3 text-center font-bold text-gray-600 dark:text-gray-300 w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3 text-gray-400 text-xs">{{ index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ item.product_name }}</div>
                                        <div v-if="item.sku" class="text-xs text-gray-400">SKU: {{ item.sku }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <select
                                            v-model="item.unit_id"
                                            class="w-full text-xs px-2 py-1.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                        >
                                            <option :value="null">{{ t('ডিফল্ট', 'Default') }}</option>
                                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input
                                            type="number"
                                            step="0.01"
                                            v-model.number="item.purchase_price"
                                            min="0"
                                            required
                                            class="w-full text-right text-xs px-2 py-1.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 font-bold"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input
                                            type="number"
                                            step="0.01"
                                            v-model.number="item.quantity"
                                            min="0.01"
                                            required
                                            class="w-full text-center text-xs px-2 py-1.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 font-black text-blue-600"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input
                                            type="number"
                                            step="0.01"
                                            v-model.number="item.selling_price"
                                            min="0"
                                            :placeholder="t('ঐচ্ছিক', 'Optional')"
                                            class="w-full text-right text-xs px-2 py-1.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-gray-900 dark:text-white">
                                        ৳{{ formatNumber(item.purchase_price * item.quantity) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            @click="removeItem(index)"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="form.items.length === 0">
                                    <td colspan="8" class="px-4 py-10 text-center text-gray-400 font-medium">
                                        {{ t('কোনো পণ্য যোগ করা হয়নি। উপরে সার্চ করে ক্রয় অর্ডারে পণ্য যোগ করুন।', 'No items added yet. Search above to add products to this purchase order.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bottom Grid: Discounts, Taxes, Payment Summary -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <!-- Left: Notes & Additional Info -->
                    <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-4">
                        <div>
                            <label class="block font-semibold text-sm text-gray-700 dark:text-gray-300 mb-1.5">{{ t('ক্রয় সংক্রান্ত নোট / মন্তব্য', 'Purchase Notes') }}</label>
                            <textarea
                                v-model="form.note"
                                rows="3"
                                :placeholder="t('চালান নং, ডেলিভারি স্লিপ নোট ইত্যাদি...', 'e.g. Consignment #, supplier delivery slip notes...')"
                                class="w-full px-3.5 py-2.5 border rounded-xl dark:bg-gray-700 dark:border-gray-600 text-sm focus:ring-2 focus:ring-blue-500"
                            ></textarea>
                        </div>

                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="font-bold text-sm text-gray-800 dark:text-white mb-3">{{ t('পরিশোধের বিবরণ', 'Payment Details') }}</h4>
                            <div class="space-y-4">
                                <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3 cursor-pointer">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ t('বাকিতে কিনবেন', 'Buy on credit') }}</span>
                                    <input
                                        type="checkbox"
                                        v-model="isDuePurchase"
                                        class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </label>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ t('যে অ্যাকাউন্ট থেকে পরিশোধ করবেন', 'Pay From Bank / Cash Account') }}</label>
                                    <select
                                        v-model="form.bank_account_id"
                                        class="w-full px-3.5 py-2.5 border rounded-xl text-sm dark:bg-gray-700 dark:border-gray-600"
                                    >
                                        <option v-if="!bankAccounts?.length" value="" disabled>{{ t('কোনো অ্যাকাউন্ট নেই', 'No accounts available') }}</option>
                                        <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
                                            {{ acc.bank_name }} - {{ acc.account_number || t('ক্যাশ', 'Cash') }} ({{ t('ব্যালেন্স:', 'Bal:') }} ৳{{ formatNumber(acc.current_balance) }})
                                        </option>
                                    </select>
                                </div>
                                <div v-if="isDuePurchase">
                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ t('প্রাথমিক পরিশোধ (৳)', 'Initial Payment (৳)') }}</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        v-model.number="form.paid_amount"
                                        min="0"
                                        :max="grandTotal"
                                        class="w-full px-3.5 py-2.5 border rounded-xl font-black text-emerald-600 text-base dark:bg-gray-700 dark:border-gray-600"
                                    />
                                </div>
                                <p v-else class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ t('সম্পূর্ণ টাকা নির্বাচিত অ্যাকাউন্ট থেকে পরিশোধ হবে।', 'The full amount will be paid from the selected account.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Calculations & Submit -->
                    <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-3.5 text-sm">
                        <div class="flex justify-between py-1 text-gray-600 dark:text-gray-300">
                            <span class="font-medium">{{ t('উপমোট:', 'Subtotal:') }}</span>
                            <span class="font-black text-gray-900 dark:text-white">৳{{ formatNumber(subtotal) }}</span>
                        </div>

                        <div class="flex justify-between items-center py-1">
                            <span class="font-medium text-gray-600 dark:text-gray-300">{{ t('ছাড়:', 'Discount:') }}</span>
                            <div class="flex items-center gap-2">
                                <select v-model="form.discount_type" class="text-xs py-1.5 px-2.5 border rounded-lg dark:bg-gray-700">
                                    <option value="fixed">{{ t('স্থায়ী (৳)', 'Fixed (৳)') }}</option>
                                    <option value="percentage">{{ t('শতাংশ (%)', '%') }}</option>
                                </select>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model.number="form.discount_amount"
                                    min="0"
                                    class="w-24 text-right text-xs py-1.5 px-2 border rounded-lg dark:bg-gray-700 font-bold"
                                />
                            </div>
                        </div>

                        <div class="flex justify-between items-center py-1">
                            <span class="font-medium text-gray-600 dark:text-gray-300">{{ t('ট্যাক্স / ভ্যাট (৳):', 'Tax / VAT (৳):') }}</span>
                            <input
                                type="number"
                                step="0.01"
                                v-model.number="form.tax_amount"
                                min="0"
                                class="w-24 text-right text-xs py-1.5 px-2 border rounded-lg dark:bg-gray-700 font-bold"
                            />
                        </div>

                        <div class="flex justify-between items-center py-1">
                            <span class="font-medium text-gray-600 dark:text-gray-300">{{ t('পরিবহন খরচ (৳):', 'Shipping Cost (৳):') }}</span>
                            <input
                                type="number"
                                step="0.01"
                                v-model.number="form.shipping_cost"
                                min="0"
                                class="w-24 text-right text-xs py-1.5 px-2 border rounded-lg dark:bg-gray-700 font-bold"
                            />
                        </div>

                        <hr class="border-gray-200 dark:border-gray-700" />

                        <div class="flex justify-between py-2 text-base font-bold">
                            <span class="text-gray-800 dark:text-white">{{ t('সর্বমোট:', 'Grand Total:') }}</span>
                            <span class="text-blue-600 text-xl font-black">৳{{ formatNumber(grandTotal) }}</span>
                        </div>

                        <div class="flex justify-between py-1 font-bold text-emerald-600">
                            <span>{{ t('পরিশোধিত টাকা:', 'Paid Amount:') }}</span>
                            <span>৳{{ formatNumber(paidAmount) }}</span>
                        </div>

                        <div class="flex justify-between py-1 font-black text-rose-600">
                            <span>{{ t('অবশিষ্ট বকেয়া:', 'Remaining Due:') }}</span>
                            <span>৳{{ formatNumber(dueAmount) }}</span>
                        </div>

                        <div class="pt-4">
                            <button
                                type="submit"
                                :disabled="submitting || form.items.length === 0 || !form.supplier_id || (paidAmount > 0 && !form.bank_account_id)"
                                class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-md transition-all flex items-center justify-center gap-2 disabled:opacity-50 text-base"
                            >
                                <CheckIcon class="w-5 h-5" />
                                {{ t('সংরক্ষণ করুন এবং স্টক ইন করুন', 'Save & Receive Stock') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLanguage } from '@/composables/useLanguage'
import {
    ArrowLeft as ArrowLeftIcon,
    Search as SearchIcon,
    Trash as TrashIcon,
    Check as CheckIcon
} from 'lucide-vue-next'

const { t } = useLanguage()

const props = defineProps({
    suppliers: Array,
    products: Array,
    bankAccounts: Array,
    units: Array,
})

const productSearch = ref('')
const submitting = ref(false)
const isDuePurchase = ref(false)

const form = ref({
    supplier_id: '',
    purchase_date: new Date().toISOString().split('T')[0],
    bank_account_id: props.bankAccounts?.[0]?.id || '',
    discount_type: 'fixed',
    discount_amount: 0,
    tax_amount: 0,
    shipping_cost: 0,
    paid_amount: 0,
    note: '',
    items: [],
})

const filteredProducts = computed(() => {
    if (!productSearch.value.trim()) return []
    const q = productSearch.value.toLowerCase()
    return (props.products || []).filter(p =>
        p.name.toLowerCase().includes(q) ||
        (p.sku && p.sku.toLowerCase().includes(q)) ||
        (p.barcode && p.barcode.toLowerCase().includes(q))
    ).slice(0, 8)
})

const addProductToItems = (p) => {
    const existing = form.value.items.find(item => item.product_id === p.id)
    if (existing) {
        existing.quantity += 1
    } else {
        form.value.items.push({
            product_id: p.id,
            product_name: p.name,
            sku: p.sku,
            unit_id: p.unit_id || null,
            product_variant_id: null,
            purchase_price: Number(p.cost_price || p.selling_price || 0),
            selling_price: Number(p.selling_price || 0),
            quantity: 1,
        })
    }
    productSearch.value = ''
}

const selectFirstProduct = () => {
    if (filteredProducts.value.length > 0) {
        addProductToItems(filteredProducts.value[0])
    }
}

const removeItem = (index) => {
    form.value.items.splice(index, 1)
}

const subtotal = computed(() => {
    return form.value.items.reduce((sum, item) => sum + (Number(item.purchase_price || 0) * Number(item.quantity || 0)), 0)
})

const computedDiscount = computed(() => {
    if (form.value.discount_type === 'percentage') {
        return (subtotal.value * Number(form.value.discount_amount || 0)) / 100
    }
    return Number(form.value.discount_amount || 0)
})

const grandTotal = computed(() => {
    const total = subtotal.value - computedDiscount.value + Number(form.value.tax_amount || 0) + Number(form.value.shipping_cost || 0)
    return Math.max(0, total)
})

const paidAmount = computed(() => isDuePurchase.value ? Math.min(grandTotal.value, Number(form.value.paid_amount || 0)) : grandTotal.value)

const dueAmount = computed(() => {
    return Math.max(0, grandTotal.value - paidAmount.value)
})

const formatNumber = (val) => {
    return Number(val || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}

const submitPurchase = () => {
    submitting.value = true
    router.post(route('admin.purchases.store'), {
        ...form.value,
        paid_amount: paidAmount.value,
    }, {
        onError: () => {
            submitting.value = false
        }
    })
}
</script>
