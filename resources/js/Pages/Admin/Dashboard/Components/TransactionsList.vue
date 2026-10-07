<template>
    <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-slate-200/90 shadow-xs dark:bg-slate-900 dark:border-slate-800">
        <h3 class="mb-3 text-sm sm:text-base font-bold text-slate-800 dark:text-slate-200">Recent Transactions</h3>

        <!-- Mobile Card View (< sm) -->
        <div class="sm:hidden space-y-2.5">
            <div
                v-for="transaction in transactions"
                :key="'m-trx-' + transaction.id"
                class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 flex items-center justify-between"
            >
                <div class="min-w-0 flex-1 pr-2">
                    <div class="flex items-center gap-1.5">
                        <span :class="[
                            'px-1.5 py-0.5 text-[10px] font-bold rounded uppercase',
                            transaction.transaction_type === 'in'
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                                : 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400'
                        ]">
                            {{ transaction.transaction_type }}
                        </span>
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">
                            {{ transaction.bank_account?.account_name || 'Account' }}
                        </p>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5 truncate">
                        {{ formatDate(transaction.date) }}
                    </p>
                    <p v-if="transaction.description" class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                        {{ transaction.description }}
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <span :class="[
                        'text-xs font-bold tabular-nums',
                        transaction.transaction_type === 'in'
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-rose-600 dark:text-rose-400'
                    ]">
                        {{ transaction.transaction_type === 'in' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                    </span>
                </div>
            </div>
            <div v-if="transactions.length === 0" class="text-center py-4 text-xs text-slate-400">
                No recent transactions
            </div>
        </div>

        <!-- Desktop Table View (sm+) -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 font-semibold text-slate-600 dark:text-slate-300">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Date</th>
                        <th class="px-4 py-2.5 text-left">Account</th>
                        <th class="px-4 py-2.5 text-center">Type</th>
                        <th class="px-4 py-2.5 text-right">Amount</th>
                        <th class="px-4 py-2.5 text-left">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-700 dark:text-slate-300">
                            {{ formatDate(transaction.date) }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-900 dark:text-white font-semibold">
                            {{ transaction.bank_account?.account_name }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <span :class="[
                                'px-2 py-0.5 text-[11px] font-bold rounded-full uppercase',
                                transaction.transaction_type === 'in'
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                            ]">
                                {{ transaction.transaction_type }}
                            </span>
                        </td>
                        <td :class="[
                            'px-4 py-3 whitespace-nowrap text-right font-bold tabular-nums',
                            transaction.transaction_type === 'in'
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-rose-600 dark:text-rose-400'
                        ]">
                            {{ transaction.transaction_type === 'in' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400 truncate max-w-xs">
                            {{ transaction.description || '-' }}
                        </td>
                    </tr>
                    <tr v-if="transactions.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-slate-400">
                            No recent transactions
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>


<script setup>
import { defineProps } from 'vue';

const props = defineProps({
    transactions: {
        type: Array,
        required: true
    }
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatCurrency = (amount) => {
    const number = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
    return `৳ ${number}`;
};

</script>
