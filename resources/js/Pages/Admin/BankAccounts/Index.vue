<template>
    <Head :title="t('ব্যাংক / ক্যাশ অ্যাকাউন্ট', 'Bank Accounts')" />
    <AdminLayout :title="t('ব্যাংক অ্যাকাউন্ট ব্যবস্থাপনা', 'Bank Accounts')">
        <div class="container mx-auto px-3 sm:px-4 py-4 sm:py-6">
            <!-- Header & Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 mb-4 sm:mb-6">
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">{{ t('ব্যাংক ও ক্যাশ অ্যাকাউন্ট', 'Bank & Cash Accounts') }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ t('সকল ব্যাংক ও ক্যাশ অ্যাকাউন্টের তালিকা', 'Manage all bank and cash accounts') }}</p>
                </div>
                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-64">
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="t('অনুসন্ধান করুন...', 'Search...')"
                            class="w-full pl-9 pr-4 py-2 text-xs sm:text-sm border rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                        />
                        <SearchIcon class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
                    </div>
                    <Link
                        :href="route('admin.bank-accounts.create')"
                        class="px-4 py-2 sm:py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs sm:text-sm font-semibold flex items-center justify-center gap-2 shadow-sm transition whitespace-nowrap shrink-0"
                    >
                        <PlusIcon class="w-4 h-4" />
                        {{ t('নতুন অ্যাকাউন্ট', 'New Account') }}
                    </Link>
                </div>
            </div>

            <!-- List & Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden">
                <!-- Mobile Cards (< md) -->
                <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
                    <div
                        v-for="account in filteredBankAccounts"
                        :key="account.id"
                        class="p-4 space-y-2.5"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-bold text-sm text-gray-900 dark:text-white">
                                    {{ account.account_name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ account.bank_name }}
                                </div>
                            </div>
                            <span
                                class="px-2 py-0.5 text-[10px] font-bold rounded-full shrink-0"
                                :class="account.status
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                    : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'"
                            >
                                {{ account.status ? t('সক্রিয়', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-400 font-mono">{{ account.account_number || '—' }}</span>
                            <div class="text-right">
                                <span class="text-[10px] text-gray-400 block">{{ t('বর্তমান ব্যালেন্স', 'Current Balance') }}</span>
                                <span class="font-black text-emerald-600 text-sm">৳{{ formatNumber(account.current_balance) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-1.5 pt-1.5 border-t border-gray-100 dark:border-gray-700/50">
                            <Link
                                :href="route('admin.bank-accounts.edit', account.id)"
                                class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg inline-block transition"
                                :title="t('সম্পাদনা', 'Edit')"
                            >
                                <EditIcon class="w-4 h-4" />
                            </Link>
                            <button
                                @click="destroy(account.id)"
                                class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg inline-block transition"
                                :title="t('মুছুন', 'Delete')"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="filteredBankAccounts.length === 0" class="p-8 text-center text-gray-400 text-xs font-medium">
                        {{ t('কোনো অ্যাকাউন্ট পাওয়া যায়নি।', 'No bank accounts found.') }}
                    </div>
                </div>

                <!-- Desktop Table (>= md) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('অ্যাকাউন্টের নাম', 'Account Name') }}</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('অ্যাকাউন্ট নম্বর', 'Account Number') }}</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 dark:text-gray-300">{{ t('ব্যাংকের নাম', 'Bank Name') }}</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 dark:text-gray-300">{{ t('বর্তমান ব্যালেন্স (৳)', 'Current Balance (৳)') }}</th>
                                <th class="px-6 py-3.5 text-center font-bold text-gray-600 dark:text-gray-300">{{ t('অবস্থা', 'Status') }}</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 dark:text-gray-300">{{ t('অ্যাকশন', 'Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="account in filteredBankAccounts" :key="account.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition">
                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ account.account_name }}</td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300 font-medium">{{ account.account_number || '—' }}</td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ account.bank_name }}</td>
                                <td class="px-6 py-4 text-right font-black text-emerald-600">৳{{ formatNumber(account.current_balance) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-2.5 py-1 text-xs font-bold rounded-full"
                                        :class="account.status
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'"
                                    >
                                        {{ account.status ? t('সক্রিয়', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <Link
                                        :href="route('admin.bank-accounts.edit', account.id)"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg inline-block transition"
                                        :title="t('সম্পাদনা', 'Edit')"
                                    >
                                        <EditIcon class="w-4 h-4 inline" />
                                    </Link>
                                    <button
                                        @click="destroy(account.id)"
                                        class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg inline-block transition"
                                        :title="t('মুছুন', 'Delete')"
                                    >
                                        <TrashIcon class="w-4 h-4 inline" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredBankAccounts.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400 font-medium">
                                    {{ t('কোনো অ্যাকাউন্ট পাওয়া যায়নি।', 'No bank accounts found.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLanguage } from '@/composables/useLanguage'
import {
    Search as SearchIcon,
    Plus as PlusIcon,
    Edit as EditIcon,
    Trash as TrashIcon
} from 'lucide-vue-next'

const { t } = useLanguage()

const props = defineProps({
    bankAccounts: Array,
})

const search = ref('')

const filteredBankAccounts = computed(() => {
    if (!search.value.trim()) return props.bankAccounts || []
    const q = search.value.toLowerCase()
    return (props.bankAccounts || []).filter(acc =>
        (acc.account_name || '').toLowerCase().includes(q) ||
        (acc.account_number || '').toLowerCase().includes(q) ||
        (acc.bank_name || '').toLowerCase().includes(q)
    )
})

const formatNumber = (val) => {
    return Number(val || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}

const destroy = (id) => {
    const confirmMsg = t('আপনি কি নিশ্চিত যে এই ব্যাংক অ্যাকাউন্টটি মুছে ফেলতে চান?', 'Are you sure you want to delete this bank account?')
    if (confirm(confirmMsg)) {
        router.delete(route('admin.bank-accounts.destroy', id))
    }
}
</script>
