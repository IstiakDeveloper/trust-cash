<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Create Transaction
            </h2>
        </template>

        <div class="py-4 sm:py-8">
            <div class="max-w-3xl mx-auto px-3 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl">
                    <div class="p-4 sm:p-6">
                        <form @submit.prevent="submit"><div class="mb-4 space-y-2">
        <label class="block text-sm font-medium dark:text-white">Accounting category
            <select v-model="form.counter_account" required class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:text-white">
                <option value="" disabled>Choose the purpose of this cash movement</option>
                <option value="capital">Owner capital / withdrawal</option><option value="loan_payable">Loan principal / repayment</option>
                <option value="other_income:0">Other income / reversal</option><option value="expense:0">Operating expense / reimbursement</option>
            </select>
        </label>
        <label class="block text-sm font-medium dark:text-white">Reason / source reference<input v-model="form.classification_reason" required maxlength="1000" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:text-white" /></label>
        <p v-if="$page.props.errors?.counter_account || $page.props.errors?.classification_reason || $page.props.errors?.transaction" class="text-sm text-red-600">{{ $page.props.errors.counter_account || $page.props.errors.classification_reason || $page.props.errors.transaction }}</p>
    </div>
                            <div class="mb-4">
                                <label for="bank_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Bank Account
                                </label>
                                <select id="bank_account_id" v-model="form.bank_account_id"
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-700
                                               bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md" required>
                                    <option value="">Select Bank Account</option>
                                    <option v-for="bankAccount in bankAccounts" :key="bankAccount.id" :value="bankAccount.id">
                                        {{ bankAccount.account_name }}
                                    </option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="transaction_type" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Transaction Type
                                </label>
                                <select id="transaction_type" v-model="form.transaction_type"
                class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-700
                       bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md" required>
            <option value="in">In</option>
            <option value="out">Out</option>
        </select>
                            </div>

                            <div class="mb-4">
                                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Amount
                                </label>
                                <input type="number" id="amount" v-model="form.amount" step="0.01" min="0"
                                       class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-700
                                              bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md" required>
                            </div>

                            <div class="mb-4">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Description
                                </label>
                                <textarea id="description" v-model="form.description"
                                          class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-700
                                                 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md">
                                </textarea>
                            </div>

                            <div class="mb-4">
                                <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Transaction Date
                                </label>
                                <input type="date" id="date" v-model="form.date" :max="today"
                                       class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-700
                                              bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md" required>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Can be backdated for historical records</p>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button type="button" @click="$inertia.visit(route('admin.bank-transactions.index'))"
                                        class="py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700
                                               hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2
                                               focus:ring-indigo-500">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm
                                               font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700
                                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Create Transaction
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'

export default {
    components: {
        AdminLayout,
    },
    props: {
        bankAccounts: Array,
    },
    data() {
        return {
            form: {
                bank_account_id: '',
                transaction_type: 'in',
                amount: '',
                counter_account: '', classification_reason: '',
                description: '',
                date: (this.$page.props.business_date || new Date().toLocaleDateString('en-CA')),
            },
            today: (this.$page.props.business_date || new Date().toLocaleDateString('en-CA')),
        }
    },
    methods: {
        submit() {
            this.$inertia.post(route('admin.bank-transactions.store'), this.form)
        }
    }
}
</script>
