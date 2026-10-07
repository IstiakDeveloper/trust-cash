<template>
    <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-slate-200/90 shadow-xs dark:bg-slate-900 dark:border-slate-800">
        <h3 class="mb-3 text-sm sm:text-base font-bold text-slate-800 dark:text-slate-200">Recent Expenses</h3>

        <!-- Mobile Card View (< sm) -->
        <div class="sm:hidden space-y-2.5">
            <div
                v-for="expense in expenses"
                :key="'m-exp-' + expense.id"
                class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 flex items-center justify-between"
            >
                <div class="min-w-0 flex-1 pr-2">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">
                        {{ expense.expense_category?.name || 'Expense' }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5 truncate">
                        {{ formatDate(expense.date) }} {{ expense.reference_no ? '• Ref: ' + expense.reference_no : '' }}
                    </p>
                    <p v-if="expense.description" class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                        {{ expense.description }}
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <span class="text-xs font-bold text-rose-600 dark:text-rose-400 tabular-nums">
                        -{{ formatCurrency(expense.amount) }}
                    </span>
                </div>
            </div>
            <div v-if="expenses.length === 0" class="text-center py-4 text-xs text-slate-400">
                No recent expenses
            </div>
        </div>

        <!-- Desktop Table View (sm+) -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 font-semibold text-slate-600 dark:text-slate-300">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Date</th>
                        <th class="px-4 py-2.5 text-left">Category</th>
                        <th class="px-4 py-2.5 text-right">Amount</th>
                        <th class="px-4 py-2.5 text-left">Reference</th>
                        <th class="px-4 py-2.5 text-left">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    <tr v-for="expense in expenses" :key="expense.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-700 dark:text-slate-300">
                            {{ formatDate(expense.date) }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-900 dark:text-white font-semibold">
                            {{ expense.expense_category?.name }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-rose-600 dark:text-rose-400 font-bold tabular-nums">
                            {{ formatCurrency(expense.amount) }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                            {{ expense.reference_no || '-' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400 truncate max-w-xs">
                            {{ expense.description || '-' }}
                        </td>
                    </tr>
                    <tr v-if="expenses.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-slate-400">
                            No recent expenses
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
    expenses: {
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
