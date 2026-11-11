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

            <form @submit.prevent="submitPurchase" class="space-y-4">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ t('পণ্য যোগ করুন', 'Add a product') }}</h3>
                    <div class="mt-3 space-y-3">
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300">{{ t('পণ্য', 'Product') }}
                            <div class="relative mt-1.5">
                                <input
                                    v-model="productSearch"
                                    type="text"
                                    :placeholder="t('নাম, SKU বা বারকোড', 'Name, SKU, or barcode')"
                                    class="min-h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                                    @keydown.enter.prevent="selectFirstProduct"
                                    @focus="searchOpen = true"
                                />
                                <SearchIcon class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" />
                                <div v-if="searchOpen && filteredProducts.length" class="absolute left-0 right-0 top-full z-30 mt-1 max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
                                    <button
                                        v-for="p in filteredProducts"
                                        :key="p.id"
                                        type="button"
                                        class="flex min-h-11 w-full items-center justify-between gap-3 border-b border-slate-100 px-3 py-2 text-left last:border-0 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700"
                                        @click="chooseProduct(p)"
                                    >
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-bold text-slate-900 dark:text-white">{{ p.name }}</span>
                                            <span class="block truncate text-[11px] text-slate-400">{{ p.sku || '—' }}</span>
                                        </span>
                                        <span class="shrink-0 text-xs font-bold text-indigo-600">৳{{ formatNumber(p.selling_price) }}</span>
                                    </button>
                                </div>
                            </div>
                        </label>
                        <p v-if="productSearch.trim() && !filteredProducts.length" class="text-xs text-slate-500">{{ t('কোনো পণ্য পাওয়া যায়নি। এখানে তৈরি করুন।', 'No product found. Create it here.') }}</p>
                        <QuickCreateSelect trigger-only resource="products" :initial-name="productSearch" @created="productCreated" />
                        <p v-if="selectedProduct" class="rounded-xl bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-200">{{ selectedProduct.name }}</p>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label v-if="isFifo" class="block text-xs font-bold text-slate-600 dark:text-slate-300">{{ t('লট নম্বর', 'Lot number') }}
                                <input v-model="draft.lot_number" maxlength="100" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                                <span class="mt-1 block font-normal text-[11px] text-slate-400">{{ t('পণ্য বাছলে lot-1, lot-2 এভাবে বসে। চাইলে বদলান।', 'Choosing a product fills lot-1, lot-2, and so on. Change it if you want.') }}</span>
                            </label>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300">{{ isFifo ? t('এই লটের বিক্রয়মূল্য', 'Selling price for this lot') : t('বিক্রয়মূল্য', 'Selling price') }}
                                <input v-model="draft.selling_price" type="number" min="0" step="0.01" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                                <span class="mt-1 block font-normal text-[11px] text-slate-400">{{ isFifo ? t('আগের লটের দাম বসানো আছে। চাইলে বদলান।', 'The previous lot price is filled in. Change it if you want.') : t('পণ্যের আগের দাম বসানো আছে। চাইলে বদলান।', 'The product’s current price is filled in. Change it if you want.') }}</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300">{{ t('পরিমাণ', 'Quantity') }}
                                <input v-model="draft.quantity" type="number" min="0.01" step="0.01" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                            </label>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300">{{ t('ক্রয়মূল্য', 'Cost price') }}
                                <input v-model="draft.purchase_price" type="number" min="0" step="0.01" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                            </label>
                        </div>
                        <button type="button" class="min-h-11 w-full rounded-xl bg-indigo-600 text-sm font-bold text-white disabled:opacity-50" :disabled="!selectedProduct" @click="commitDraft">{{ t('তালিকায় যোগ করুন', 'Add to purchase') }}</button>
                    </div>
                </section>

                <!-- Products Table & Mobile Cards -->
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ t('ক্রয়কৃত পণ্যসমূহ', 'Purchase Items') }}</h3>
                        <span class="text-xs text-slate-500">{{ form.items.length }} {{ t('টি পণ্য', 'item(s)') }}</span>
                    </div>
                    <div v-if="form.items.length === 0" class="px-4 py-10 text-center text-xs text-slate-400">{{ t('উপরে পণ্য বেছে তালিকায় যোগ করুন।', 'Choose a product above and add it to the list.') }}</div>
                    <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                        <article v-for="(item, index) in form.items" :key="index" class="space-y-3 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ item.product_name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ item.sku || '—' }}<span v-if="isFifo && item.lot_number"> · {{ item.lot_number }}</span></p>
                                </div>
                                <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40" @click="removeItem(index)">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <label class="text-[11px] font-bold text-slate-500">{{ t('পরিমাণ', 'Qty') }}<input v-model.number="item.quantity" type="number" min="0.01" step="0.01" required class="mt-1 min-h-10 w-full rounded-lg border border-slate-200 bg-white px-2 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white" /></label>
                                <label class="text-[11px] font-bold text-slate-500">{{ t('ক্রয়মূল্য', 'Cost') }}<input v-model.number="item.purchase_price" type="number" min="0" step="0.01" required class="mt-1 min-h-10 w-full rounded-lg border border-slate-200 bg-white px-2 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white" /></label>
                                <label v-if="isFifo" class="text-[11px] font-bold text-slate-500">{{ t('লট', 'Lot') }}<input v-model="item.lot_number" maxlength="100" class="mt-1 min-h-10 w-full rounded-lg border border-slate-200 bg-white px-2 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white" /></label>
                                <label class="text-[11px] font-bold text-slate-500">{{ t('বিক্রয়মূল্য', 'Sell') }}<input v-model.number="item.selling_price" type="number" min="0" step="0.01" class="mt-1 min-h-10 w-full rounded-lg border border-slate-200 bg-white px-2 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white" /></label>
                            </div>
                            <p class="text-right text-sm font-black text-slate-900 dark:text-white">৳{{ formatNumber(item.purchase_price * item.quantity) }}</p>
                        </article>
                    </div>
                </section>

                <!-- Bottom Grid: Discounts, Taxes, Payment Summary -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <!-- Left: Notes & Additional Info -->
                    <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300">{{ t('সরবরাহকারী', 'Supplier') }}
                                <select v-model="form.supplier_id" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                    <option value="">{{ t('সাপ্লায়ার নেই — নগদে কিনবেন', 'No supplier — pay now') }}</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }} {{ s.company_name ? `(${s.company_name})` : '' }}</option>
                                </select>
                            </label>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300">{{ t('ক্রয় তারিখ', 'Purchase date') }}
                                <input v-model="form.purchase_date" type="date" required class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                            </label>
                        </div>
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
                                <p v-if="!form.supplier_id" class="text-xs text-amber-700 dark:text-amber-300">{{ t('সাপ্লায়ার না বাছলে বাকিতে কেনা যাবে না। ব্যাংক অ্যাকাউন্ট থেকে পরিশোধ করতে হবে।', 'Without a supplier this purchase cannot be on due. Choose a bank or cash account.') }}</p>
                                <label v-else class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3 cursor-pointer">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ t('Due Purchase — বাকিতে কিনবেন', 'Due Purchase') }}</span>
                                    <input
                                        type="checkbox"
                                        v-model="isDuePurchase"
                                        class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </label>
                                <div v-if="!isDuePurchase">
                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ t('যে অ্যাকাউন্ট থেকে পরিশোধ করবেন *', 'Pay From Bank / Cash Account *') }}</label>
                                    <select
                                        v-model="form.bank_account_id"
                                        class="w-full px-3.5 py-2.5 border rounded-xl text-sm dark:bg-gray-700 dark:border-gray-600"
                                    >
                                        <option value="">{{ t('-- অ্যাকাউন্ট বেছে নিন --', '-- Choose an account --') }}</option>
                                        <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
                                            {{ acc.bank_name }} - {{ acc.account_number || t('ক্যাশ', 'Cash') }} ({{ t('ব্যালেন্স:', 'Bal:') }} ৳{{ formatNumber(acc.current_balance) }})
                                        </option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ t('সম্পূর্ণ টাকা নির্বাচিত অ্যাকাউন্ট থেকে পরিশোধ হবে।', 'The full amount will be paid from the selected account.') }}</p>
                                </div>
                                <p v-else class="text-xs text-amber-700 dark:text-amber-300">{{ t('পুরো টাকা এই সাপ্লায়ারের বাকি থাকবে। ব্যাংক অ্যাকাউন্ট লাগবে না।', 'The full amount stays as this supplier’s due. No bank account is needed.') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Calculations & Submit -->
                    <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-3.5 text-sm">
                        <div class="flex justify-between py-1 text-gray-600 dark:text-gray-300">
                            <span class="font-medium">{{ t('উপমোট:', 'Subtotal:') }}</span>
                            <span class="font-black text-gray-900 dark:text-white">৳{{ formatNumber(subtotal) }}</span>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 py-1">
                            <span class="font-medium text-gray-600 dark:text-gray-300">{{ t('ছাড়:', 'Discount:') }}</span>
                            <div class="flex items-center gap-2">
                                <select v-model="form.discount_type" class="min-h-10 rounded-lg border border-slate-200 bg-white px-2 text-xs text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                    <option value="fixed">{{ t('স্থায়ী (৳)', 'Fixed (৳)') }}</option>
                                    <option value="percentage">{{ t('শতাংশ (%)', '%') }}</option>
                                </select>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model.number="form.discount_amount"
                                    min="0"
                                    class="min-h-10 w-24 rounded-lg border border-slate-200 bg-white px-2 text-right text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                                />
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 py-1">
                            <span class="font-medium text-gray-600 dark:text-gray-300">{{ t('ট্যাক্স / ভ্যাট (৳):', 'Tax / VAT (৳):') }}</span>
                            <input
                                type="number"
                                step="0.01"
                                v-model.number="form.tax_amount"
                                min="0"
                                class="min-h-10 w-28 rounded-lg border border-slate-200 bg-white px-2 text-right text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                            />
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 py-1">
                            <span class="font-medium text-gray-600 dark:text-gray-300">{{ t('পরিবহন খরচ (৳):', 'Shipping Cost (৳):') }}</span>
                            <input
                                type="number"
                                step="0.01"
                                v-model.number="form.shipping_cost"
                                min="0"
                                class="min-h-10 w-28 rounded-lg border border-slate-200 bg-white px-2 text-right text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
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
                                :disabled="submitting || form.items.length === 0 || (isDuePurchase && !form.supplier_id) || (!isDuePurchase && !form.bank_account_id)"
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
import QuickCreateSelect from '@/Components/QuickCreateSelect.vue';
import { ref, computed, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLanguage } from '@/composables/useLanguage'
import {
    ArrowLeft as ArrowLeftIcon,
    Search as SearchIcon,
    Trash as TrashIcon,
    Check as CheckIcon
} from 'lucide-vue-next'

const { t } = useLanguage()
const isFifo = computed(() => usePage().props.inventory_costing_method === 'fifo')

const props = defineProps({
    suppliers: Array,
    products: Array,
    bankAccounts: Array,
    units: Array,
})

const productSearch = ref('')
const searchOpen = ref(false)
const selectedProduct = ref(null)
const draft = ref({ lot_number: '', selling_price: '', quantity: 1, purchase_price: '' })
const localProducts = ref([...(props.products || [])])
const localUnits = ref([...(props.units || [])])
const money = (value) => {
    const number = Number(value)
    return Number.isFinite(number) ? number : ''
}
const submitting = ref(false)
const isDuePurchase = ref(false)

const form = ref({
    supplier_id: '',
    purchase_date: (usePage().props.business_date || new Date().toLocaleDateString('en-CA')),
    bank_account_id: '',
    discount_type: 'fixed',
    discount_amount: 0,
    tax_amount: 0,
    shipping_cost: 0,
    paid_amount: 0,
    note: '',
    items: [],
})

const filteredProducts = computed(() => {
    const q = productSearch.value.trim().toLowerCase()
    const list = q
        ? localProducts.value.filter(p =>
            p.name.toLowerCase().includes(q) ||
            (p.sku && p.sku.toLowerCase().includes(q)) ||
            (p.barcode && p.barcode.toLowerCase().includes(q))
        )
        : localProducts.value
    return list.slice(0, 8)
})

const nextLotNumber = (product) => {
    let max = 0
    const take = (value, suggested) => {
        const match = String(value || '').trim().match(/^lot-(\d+)$/i)
        if (!match) return
        max = Math.max(max, suggested ? Number(match[1]) - 1 : Number(match[1]))
    }
    take(product.next_lot_number, true)
    form.value.items.filter(item => item.product_id === product.id).forEach(item => take(item.lot_number, false))
    return `lot-${max + 1}`
}

const chooseProduct = (product) => {
    selectedProduct.value = product
    productSearch.value = product.name
    searchOpen.value = false
    const prior = [...form.value.items].reverse().find(item => item.product_id === product.id)
    draft.value = {
        lot_number: isFifo.value ? nextLotNumber(product) : '',
        selling_price: money(isFifo.value ? (prior?.selling_price ?? product.previous_selling_price ?? product.selling_price) : product.selling_price),
        quantity: 1,
        purchase_price: money(product.cost_price),
    }
}

const productCreated = (product) => {
    const ready = {
        ...product,
        cost_price: Number(product.cost_price || 0),
        selling_price: Number(product.selling_price || 0),
        next_lot_number: product.next_lot_number || 'lot-1',
        previous_selling_price: Number(product.previous_selling_price ?? product.selling_price ?? 0),
    }
    localProducts.value.push(ready)
    if (product.unit && !localUnits.value.some(unit => unit.id === product.unit.id)) localUnits.value.push(product.unit)
    chooseProduct(ready)
}

const commitDraft = () => {
    const product = selectedProduct.value
    if (!product) return
    const existing = form.value.items.find(item => item.product_id === product.id)
    if (existing && !isFifo.value) {
        existing.quantity = Number(existing.quantity || 0) + (Number(draft.value.quantity) || 1)
        existing.purchase_price = money(draft.value.purchase_price)
        existing.selling_price = money(draft.value.selling_price)
    } else {
        form.value.items.push({
            product_id: product.id,
            product_name: product.name,
            sku: product.sku,
            unit_id: product.unit_id || null,
            product_variant_id: null,
            base_unit_id: product.unit_id,
            lot_number: isFifo.value ? (String(draft.value.lot_number || '').trim() || nextLotNumber(product)) : '',
            purchase_price: money(draft.value.purchase_price),
            selling_price: money(draft.value.selling_price),
            quantity: Number(draft.value.quantity) || 1,
        })
    }
    if (isFifo.value) draft.value.lot_number = nextLotNumber(product)
    draft.value.quantity = 1
}

const selectFirstProduct = () => {
    if (filteredProducts.value.length > 0) chooseProduct(filteredProducts.value[0])
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

watch(() => form.value.supplier_id, (id) => {
    if (!id) isDuePurchase.value = false
})

const paidAmount = computed(() => isDuePurchase.value ? 0 : grandTotal.value)

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
        supplier_id: form.value.supplier_id || null,
        bank_account_id: isDuePurchase.value ? null : form.value.bank_account_id,
        paid_amount: paidAmount.value,
    }, {
        onError: () => {
            submitting.value = false
        }
    })
}
</script>
