<template>
    <div class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-2xl shadow-2xl w-full max-w-md max-h-[92dvh] sm:max-h-[90vh] flex flex-col border border-slate-200 dark:border-slate-800 overflow-hidden text-left">
            <!-- Mobile Drag Handle -->
            <div class="sm:hidden flex justify-center pt-2.5 pb-1 shrink-0">
                <span class="w-10 h-1.5 rounded-full bg-slate-300 dark:bg-slate-700"></span>
            </div>

            <form @submit.prevent="submitPayment" class="flex flex-col h-full min-h-0">
                <!-- Sticky Header -->
                <div class="sticky top-0 z-10 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md px-4 sm:px-6 py-3.5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center shrink-0">
                    <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
                        Add Payment
                    </h2>
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                    >
                        <XIcon class="w-5 h-5" />
                    </button>
                </div>

                <!-- Scrollable Body -->
                <div class="p-4 sm:p-6 space-y-4 flex-1 overflow-y-auto overscroll-contain min-h-0">
                    <!-- Sale Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Select Invoice
                        </label>
                        <select
                            v-model="form.sale_id"
                            class="w-full text-xs min-h-[42px] px-3 py-2 border rounded-xl border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            required
                        >
                            <option value="">Select an invoice</option>
                            <option
                                v-for="sale in dueInvoices"
                                :key="sale.id"
                                :value="sale.id"
                            >
                                {{ sale.invoice_no }} - ৳{{ formatNumber(sale.due) }} Due
                            </option>
                        </select>
                    </div>

                    <!-- Payment Amount -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Payment Amount
                        </label>
                        <input
                            type="number"
                            inputmode="decimal"
                            v-model.number="form.amount"
                            min="0.01"
                            step="0.01"
                            :max="selectedInvoiceDue"
                            class="w-full text-xs min-h-[42px] px-3 py-2 border rounded-xl border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white font-bold focus:ring-1 focus:ring-indigo-600"
                            required
                        />
                        <p
                            v-if="selectedInvoiceDue"
                            class="text-[11px] text-slate-500 mt-1"
                        >
                            Maximum payable amount: ৳{{ formatNumber(selectedInvoiceDue) }}
                        </p>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Payment Method
                        </label>
                        <select
                            v-model="form.payment_method"
                            class="w-full text-xs min-h-[42px] px-3 py-2 border rounded-xl border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            required
                        >
                            <option value="">Select payment method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="mobile_banking">Mobile Banking</option>
                        </select>
                    </div>

                    <!-- Bank Account (Conditional) -->
                    <div
                        v-if="['bank', 'card'].includes(form.payment_method)"
                        class="mt-3"
                    >
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Bank Account
                        </label>
                        <select
                            v-model="form.bank_account_id"
                            class="w-full text-xs min-h-[42px] px-3 py-2 border rounded-xl border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            required
                        >
                            <option value="">Select bank account</option>
                            <option
                                v-for="account in bankAccounts"
                                :key="account.id"
                                :value="account.id"
                            >
                                {{ account.bank_name }} - {{ account.account_number }}
                            </option>
                        </select>
                    </div>

                    <!-- Transaction ID (Optional) -->
                    <div v-if="['bank', 'card', 'mobile_banking'].includes(form.payment_method)">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Transaction ID (Optional)
                        </label>
                        <input
                            type="text"
                            v-model="form.transaction_id"
                            class="w-full text-xs min-h-[42px] px-3 py-2 border rounded-xl border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                        />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Notes (Optional)
                        </label>
                        <textarea
                            v-model="form.note"
                            class="w-full text-xs px-3 py-2 border rounded-xl border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            rows="2"
                        ></textarea>
                    </div>
                </div>

                <!-- Sticky Footer (Submit Button always visible) -->
                <div class="sticky bottom-0 z-10 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md px-4 sm:px-6 py-3 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row justify-end gap-2 shrink-0 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="w-full sm:w-auto px-4 py-2.5 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold hover:bg-slate-50 dark:hover:bg-slate-800 min-h-[42px] transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-xs min-h-[42px] transition disabled:opacity-50"
                        :disabled="isSubmitting"
                    >
                        {{ isSubmitting ? 'Processing...' : 'Add Payment' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { XIcon } from 'lucide-vue-next'

const props = defineProps({
    customerId: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['close'])

// Form state
const form = ref({
    sale_id: '',
    amount: null,
    payment_method: '',
    bank_account_id: null,
    transaction_id: '',
    note: ''
})

// Other reactive states
const isSubmitting = ref(false)
const dueInvoices = ref([])
const bankAccounts = ref([])

// Computed properties
const selectedInvoiceDue = computed(() => {
    const selectedSale = dueInvoices.value.find(sale => sale.id === form.value.sale_id)
    return selectedSale ? selectedSale.due : 0
})

// Fetch data on component mount
onMounted(async () => {
    try {
        // Fetch due invoices for this customer
        const invoicesResponse = await axios.get(route('admin.customers.due-invoices', props.customerId))
        dueInvoices.value = invoicesResponse.data

        // Fetch bank accounts
        const bankResponse = await axios.get(route('admin.bank-accounts.index'))
        bankAccounts.value = bankResponse.data
    } catch (error) {
        console.error('Error fetching data:', error)
    }
})

// Submit payment method
const submitPayment = () => {
    isSubmitting.value = true

    router.post(route('admin.customers.add-payment', props.customerId), form.value, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false
            emit('close')
        },
        onError: () => {
            isSubmitting.value = false
        }
    })
}

// Number formatting utility
const formatNumber = (value) => {
    return new Intl.NumberFormat('en-BD').format(value || 0)
}
</script>
