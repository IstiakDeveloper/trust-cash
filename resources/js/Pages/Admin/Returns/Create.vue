<template>
    <AdminLayout :title="t('নতুন পণ্য ফেরত ও রিফান্ড', 'New Sale Return')">
        <Head :title="t('নতুন পণ্য ফেরত ও রিফান্ড', 'New Sale Return')" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.returns.index')"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                </Link>
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                        {{ t('কাস্টমার পণ্য ফেরত (রিটার্ন)', 'Customer Sale Return') }}
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ t('পণ্য স্টকে পুনর্বহাল এবং কাস্টমারকে নগদ রিফান্ড অথবা বকেয়া সমন্বয় করুন', 'Return products back to inventory and issue customer refund or due adjustment') }}
                    </p>
                </div>
            </div>

            <!-- Search Sale by Invoice -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <label class="block text-xs font-bold text-slate-900 dark:text-slate-100 mb-2">
                    {{ t('বিক্রয় ইনভয়েস নং দিয়ে খুঁজুন', 'Search Sale by Invoice #') }}
                </label>
                <div class="flex flex-col sm:flex-row gap-2">
                    <input
                        type="text"
                        v-model="invoiceQuery"
                        :placeholder="t('যেমনঃ INV-2026...', 'e.g. INV-2026...')"
                        class="flex-1 rounded-xl border-slate-200 bg-white px-3.5 py-2.5 sm:py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        @keydown.enter.prevent="searchInvoice"
                    />
                    <button
                        type="button"
                        @click="searchInvoice"
                        :disabled="searching"
                        class="w-full sm:w-auto justify-center rounded-xl bg-indigo-600 px-4 py-2.5 sm:py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 active:scale-95 disabled:opacity-50 transition-all"
                    >
                        {{ searching ? t('খুঁজছে...', 'Searching...') : t('ইনভয়েস খুঁজুন', 'Find Sale') }}
                    </button>
                </div>

                <!-- Found Sale details -->
                <div v-if="loadedSale" class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50/50 p-3.5 sm:p-4 text-xs dark:border-indigo-900/50 dark:bg-indigo-950/20">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <span class="font-bold text-indigo-900 dark:text-indigo-300">{{ t('ইনভয়েস #', 'Invoice #') }}{{ loadedSale.invoice_no }}</span>
                            <span class="text-slate-500 ml-2">{{ t('তারিখ:', 'Date:') }} {{ loadedSale.created_at }}</span>
                            <div class="text-slate-600 dark:text-slate-400 mt-1">
                                {{ t('কাস্টমার:', 'Customer:') }} <strong>{{ loadedSale.customer?.name || t('খুচরা কাস্টমার', 'Walk-in') }}</strong> | {{ t('মোট টাকা:', 'Total:') }} ৳{{ formatNumber(loadedSale.total) }}
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="loadItemsFromSale"
                            class="w-full sm:w-auto text-center rounded-lg bg-indigo-600 px-3 py-2 sm:py-1.5 text-xs font-bold text-white hover:bg-indigo-500 active:scale-95 shadow-xs transition-all"
                        >
                            {{ t('পণ্যগুলো যোগ করুন', 'Load Sale Items') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Return Form -->
            <form @submit.prevent="submitReturn" class="space-y-4 sm:space-y-6">
                <!-- Meta Info -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900 grid grid-cols-1 md:grid-cols-3 gap-3.5 sm:gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ t('কাস্টমার', 'Customer') }}
                        </label>
                        <select
                            v-model="form.customer_id"
                            class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="">{{ t('খুচরা কাস্টমার (Walk-in)', 'Walk-in Customer') }}</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">
                                {{ c.name }} ({{ c.phone || t('ফোন নেই', 'No phone') }}) - {{ t('বকেয়া:', 'Due:') }} ৳{{ formatNumber(c.balance) }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ t('ফেরতের তারিখ *', 'Return Date *') }}
                        </label>
                        <input
                            type="date"
                            v-model="form.return_date"
                            required
                            class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ t('রিফান্ড মাধ্যম *', 'Refund Method *') }}
                        </label>
                        <select
                            v-model="form.refund_status"
                            class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="completed">{{ t('নগদ / ব্যাংক থেকে রিফান্ড প্রদান', 'Cash / Bank Refund to Customer') }}</option>
                            <option value="credited_to_due">{{ t('কাস্টমারের বকেয়া থেকে সমন্বয় (বকেয়া কমবে)', 'Credit to Customer Balance (Reduce Due)') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Return Items Container -->
                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="px-4 sm:px-5 py-3.5 sm:py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white">
                            {{ t('ফেরতকৃত পণ্যের তালিকা', 'Items to Return') }}
                        </h3>
                        <span class="text-xs font-semibold text-slate-500">{{ form.items.length }} {{ t('টি পণ্য', 'item(s)') }}</span>
                    </div>

                    <!-- Mobile Items Card View (< sm) -->
                    <div class="sm:hidden p-3 space-y-3">
                        <div v-for="(item, idx) in form.items" :key="idx" class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="font-bold text-xs text-slate-900 dark:text-white min-w-0">
                                    {{ item.product_name }}
                                </div>
                                <button type="button" @click="form.items.splice(idx, 1)" class="p-1 text-rose-500 hover:text-rose-700 active:scale-90 transition-all shrink-0">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-0.5">
                                        {{ t('দর (৳)', 'Price (৳)') }}
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        v-model.number="item.unit_price"
                                        min="0"
                                        class="w-full text-right text-xs rounded-lg border-slate-200 py-1.5 font-semibold dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-0.5">
                                        {{ t('ফেরত পরিমাণ', 'Qty') }}
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        v-model.number="item.quantity"
                                        min="0.01"
                                        class="w-full text-center text-xs rounded-lg border-slate-200 py-1.5 font-bold text-indigo-600 dark:bg-slate-800 dark:border-slate-700 dark:text-indigo-400"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1.5 border-t border-slate-200 dark:border-slate-700/60 text-xs">
                                <span class="text-slate-500 dark:text-slate-400">{{ t('সাবটোটাল:', 'Subtotal:') }}</span>
                                <span class="font-bold text-slate-900 dark:text-white">৳{{ formatNumber(item.unit_price * item.quantity) }}</span>
                            </div>
                        </div>

                        <div v-if="form.items.length === 0" class="py-8 text-center text-xs text-slate-400">
                            {{ t('এখনো কোনো পণ্য যোগ করা হয়নি। ইনভয়েস সার্চ করে পণ্য লোড করুন।', 'No items added yet. Search an invoice above to load items.') }}
                        </div>
                    </div>

                    <!-- Desktop Items Table (sm+) -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ t('পণ্য', 'Product') }}</th>
                                    <th class="px-4 py-3 text-center w-36">{{ t('একক দর (৳)', 'Unit Price (৳)') }}</th>
                                    <th class="px-4 py-3 text-center w-28">{{ t('ফেরত পরিমাণ', 'Return Qty') }}</th>
                                    <th class="px-4 py-3 text-right w-36">{{ t('মোট টাকা (৳)', 'Subtotal (৳)') }}</th>
                                    <th class="px-4 py-3 text-center w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">{{ item.product_name }}</td>
                                    <td class="px-4 py-3">
                                        <input
                                            type="number"
                                            step="0.01"
                                            v-model.number="item.unit_price"
                                            min="0"
                                            class="w-full text-right text-xs rounded-lg border-slate-200 py-1 font-semibold dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input
                                            type="number"
                                            step="0.01"
                                            v-model.number="item.quantity"
                                            min="0.01"
                                            class="w-full text-center text-xs rounded-lg border-slate-200 py-1 font-bold text-indigo-600 dark:bg-slate-800 dark:border-slate-700 dark:text-indigo-400"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">
                                        ৳{{ formatNumber(item.unit_price * item.quantity) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button" @click="form.items.splice(idx, 1)" class="text-rose-500 hover:text-rose-700">
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="form.items.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                        {{ t('এখনো কোনো পণ্য যোগ করা হয়নি। ইনভয়েস সার্চ করে পণ্য লোড করুন।', 'No items added yet. Search an invoice above to load items.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bottom Section: Refund Account & Reason -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-700 dark:bg-slate-900 space-y-4">
                        <div v-if="form.refund_status === 'completed'">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('যে হিসাব থেকে রিফান্ড প্রদান করা হবে *', 'Refund From Account *') }}
                            </label>
                            <select
                                v-model="form.bank_account_id"
                                required
                                class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            >
                                <option value="" disabled>{{ t('ক্যাশ বা ব্যাংক হিসাব নির্বাচন করুন', 'Select Cash/Bank Account') }}</option>
                                <option v-for="b in bankAccounts" :key="b.id" :value="b.id">
                                    {{ b.bank_name }} ({{ t('ব্যালেন্স:', 'Bal:') }} ৳{{ formatNumber(b.current_balance) }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('ফেরতের কারণ / মন্তব্য', 'Reason for Return') }}
                            </label>
                            <textarea
                                v-model="form.reason"
                                rows="3"
                                :placeholder="t('ত্রুটিপূর্ণ পণ্য, সাইজ সমস্যা ইত্যাদি...', 'Defective item, incorrect size, customer changed mind...')"
                                class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            ></textarea>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-700 dark:bg-slate-900 space-y-4 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between py-2 text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800">
                                <span>{{ t('মোট ফেরত পণ্যের মূল্য:', 'Total Return Value:') }}</span>
                                <span class="text-indigo-600 dark:text-indigo-400 text-lg">৳{{ formatNumber(totalReturnAmount) }}</span>
                            </div>

                            <div class="flex justify-between py-2 text-xs font-bold text-rose-600 dark:text-rose-400">
                                <span>{{ t('কাস্টমারকে রিফান্ড প্রদান:', 'Refund Amount:') }}</span>
                                <span class="text-base">৳{{ formatNumber(totalReturnAmount) }}</span>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="submitting || form.items.length === 0"
                            class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all disabled:opacity-50"
                        >
                            {{ t('ফেরত নিশ্চিত ও স্টক আপডেট করুন', 'Confirm Return & Restock') }}
                        </button>
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
import axios from 'axios'
import { ArrowLeft as ArrowLeftIcon, Trash as TrashIcon } from 'lucide-vue-next'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    initialSale: Object,
    customers: Array,
    bankAccounts: Array,
})

const invoiceQuery = ref('')
const searching = ref(false)
const loadedSale = ref(props.initialSale || null)
const submitting = ref(false)

const form = ref({
    sale_id: props.initialSale?.id || null,
    customer_id: props.initialSale?.customer_id || '',
    bank_account_id: props.bankAccounts?.[0]?.id || '',
    return_date: new Date().toISOString().split('T')[0],
    refund_status: 'completed',
    reason: '',
    items: [],
})

const searchInvoice = async () => {
    if (!invoiceQuery.value.trim()) return
    searching.value = true
    try {
        const res = await axios.get(route('admin.returns.search-sale'), { params: { q: invoiceQuery.value } })
        if (res.data && res.data.length > 0) {
            loadedSale.value = res.data[0]
            form.value.sale_id = loadedSale.value.id
            form.value.customer_id = loadedSale.value.customer_id || ''
        } else {
            alert(t('এই ইনভয়েস নম্বরে কোনো বিক্রয় পাওয়া যায়নি।', 'No sales found matching this invoice number.'))
        }
    } catch (e) {
        console.error(e)
    } finally {
        searching.value = false
    }
}

const loadItemsFromSale = () => {
    if (!loadedSale.value?.sale_items) return
    form.value.items = loadedSale.value.sale_items.map(item => ({
        product_id: item.product_id,
        product_name: item.product?.name || 'Product',
        product_variant_id: item.product_variant_id || null,
        unit_price: Number(item.price || item.unit_price || 0),
        quantity: 1,
    }))
}

const totalReturnAmount = computed(() => {
    return form.value.items.reduce((sum, i) => sum + (Number(i.unit_price || 0) * Number(i.quantity || 0)), 0)
})

const formatNumber = (val) => {
    return Number(val || 0).toLocaleString('bn-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}

const submitReturn = () => {
    submitting.value = true
    form.value.refund_amount = totalReturnAmount.value
    router.post(route('admin.returns.store'), form.value, {
        onError: () => {
            submitting.value = false
        }
    })
}
</script>
