<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Bank Transaction
            </h2>
        </template>

        <div class="py-4 sm:py-8">
            <div class="max-w-3xl mx-auto px-3 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700">
                    <div class="p-4 sm:p-6">
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label for="bank_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                    Bank Account
                                </label>
                                <select id="bank_account_id" v-model="form.bank_account_id"
                                        class="block w-full py-2.5 px-3 border border-gray-300 dark:border-gray-700
                                               bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" required>
                                    <option v-for="bankAccount in bankAccounts" :key="bankAccount.id" :value="bankAccount.id">
                                        {{ bankAccount.account_name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="transaction_type" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                    Transaction Type
                                </label>
                                <select id="transaction_type" v-model="form.transaction_type"
                                        class="block w-full py-2.5 px-3 border border-gray-300 dark:border-gray-700
                                               bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" required>
                                    <option value="in">In (Deposit)</option>
                                    <option value="out">Out (Withdrawal)</option>
                                </select>
                            </div>

                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                    Amount
                                </label>
                                <input type="number" id="amount" v-model="form.amount" step="0.01" min="0"
                                       class="block w-full py-2.5 px-3 border border-gray-300 dark:border-gray-700
                                              bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" required>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                    Description
                                </label>
                                <textarea id="description" v-model="form.description" rows="3"
                                          class="block w-full py-2 px-3 border border-gray-300 dark:border-gray-700
                                                 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                </textarea>
                            </div>

                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                    Transaction Date
                                </label>
                                <input type="date" id="date" v-model="form.date" :max="today"
                                       class="block w-full py-2.5 px-3 border border-gray-300 dark:border-gray-700
                                              bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" required>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Can be backdated for historical records</p>
                            </div>

                            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                                <button type="button" @click="$inertia.visit(route('admin.bank-transactions.index'))"
                                        class="w-full sm:w-auto py-2.5 px-4 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300
                                               hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-center">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="w-full sm:w-auto inline-flex justify-center py-2.5 px-4 border border-transparent shadow-sm text-sm
                                               font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700
                                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Update Transaction
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
        bankTransaction: Object,
        bankAccounts: Array,
    },
    data() {
        return {
            form: {
                bank_account_id: this.bankTransaction.bank_account_id,
                transaction_type: this.bankTransaction.transaction_type,
                amount: this.bankTransaction.amount,
                description: this.bankTransaction.description,
                date: this.bankTransaction.date,
            },
            today: new Date().toISOString().split('T')[0],
        }
    },
    methods: {
        submit() {
            this.$inertia.put(route('admin.bank-transactions.update', this.bankTransaction.id), this.form)
        },
    },
}
</script>
