<template>
    <AdminLayout :title="t('কাস্টমার খাতা ও তালিকা', 'Customers')">
        <Head :title="t('কাস্টমার খাতা ও তালিকা', 'Customers')" />

        <div class="space-y-6">
            <!-- Header Actions -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                        {{ t('কাস্টমার ও বাকির খাতা', 'Customers Management') }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('সকল কাস্টমারদের তালিকা, মোট বিক্রয় ও বকেয়া হিসাব নিরীক্ষা করুন', 'Track customer records, total purchases, and outstanding dues') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.customers.create')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-all">
                        <PlusIcon class="h-4 w-4" />
                        <span>{{ t('নতুন কাস্টমার যোগ', 'Add Customer') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-lg">
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <SearchIcon class="h-4 w-4 text-slate-400" />
                        </div>
                        <input type="text" v-model="search"
                            :placeholder="t('কাস্টমারের নাম বা ফোন নম্বর...', 'Search customers...')"
                            class="block w-full rounded-xl border-slate-200 bg-white py-2 pl-9 pr-3 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                    </div>
                    <select v-model="filters.status"
                        class="block w-full rounded-xl border-slate-200 bg-white py-2 pl-3 pr-8 text-xs font-medium text-slate-900 shadow-xs focus:ring-1 focus:ring-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option :value="null">{{ t('সকল অবস্থা', 'All Status') }}</option>
                        <option :value="true">{{ t('সক্রিয়', 'Active') }}</option>
                        <option :value="false">{{ t('নিষ্ক্রিয়', 'Inactive') }}</option>
                    </select>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-900">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold text-slate-600 dark:text-slate-300">
                        <tr>
                            <th class="px-5 py-3 text-left">
                                {{ t('কাস্টমার তথ্য', 'Customer Info') }}
                            </th>
                            <th class="px-4 py-3 text-right">
                                {{ t('মোট বিক্রয়', 'Total Sales') }}
                            </th>
                            <th class="px-4 py-3 text-right">
                                {{ t('বকেয়া টাকা', 'Due Amount') }}
                            </th>
                            <th class="px-4 py-3 text-center">
                                {{ t('অবস্থা', 'Status') }}
                            </th>
                            <th class="px-5 py-3 text-right">
                                {{ t('অ্যাকশন', 'Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="customer in customers.data" :key="customer.id"
                            class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900 dark:text-slate-100">
                                    {{ customer.name }}
                                </div>
                                <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">
                                    {{ customer.phone }}
                                </div>
                                <div v-if="customer.email" class="text-[11px] text-slate-400">
                                    {{ customer.email }}
                                </div>
                                <div v-if="customer.branch_code || customer.branch_name" class="text-[11px] text-slate-400">
                                    {{ t('শাখা:', 'Branch:') }} {{ customer.branch_name || '-' }} ({{ customer.branch_code || '-' }})
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-slate-900 dark:text-slate-100">
                                ৳{{ formatNumber(customer.total_sales) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold">
                                <span v-if="customer.total_due > 0" class="text-rose-600 dark:text-rose-400">
                                    ৳{{ formatNumber(customer.total_due) }}
                                </span>
                                <span v-else class="text-emerald-600 dark:text-emerald-400">
                                    ৳0.00
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold"
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300': customer.status,
                                        'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300': !customer.status
                                    }">
                                    {{ customer.status ? t('সক্রিয়', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="route('admin.customers.show', customer.id)"
                                        class="rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300">
                                        {{ t('লেজার ও আদায়', 'View') }}
                                    </Link>
                                    <Link :href="route('admin.customers.edit', customer.id)"
                                        class="p-1 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">
                                        {{ t('সম্পাদন', 'Edit') }}
                                    </Link>
                                    <button @click="confirmToggleStatus(customer)"
                                        class="text-xs font-medium text-amber-600 hover:text-amber-800">
                                        {{ customer.status ? t('নিষ্ক্রিয়', 'Deactivate') : t('সক্রিয়', 'Activate') }}
                                    </button>
                                    <button v-if="!customer.total_sales" @click="confirmDelete(customer)"
                                        class="text-xs font-medium text-rose-600 hover:text-rose-800">
                                        {{ t('মুছুন', 'Delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                {{ t('কোনো কাস্টমার পাওয়া যায়নি।', 'No customers found') }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="customers.links && customers.links.length > 3" class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">
                    <Pagination :links="customers.links" />
                </div>
            </div>
        </div>

        <!-- Status Toggle Confirmation Modal -->
        <ConfirmationModal :show="!!customerToToggle" @close="customerToToggle = null"
            :title="customerToToggle?.status ? t('কাস্টমার নিষ্ক্রিয় করুন', 'Deactivate Customer') : t('কাস্টমার সক্রিয় করুন', 'Activate Customer')"
            :message="customerToToggle?.status ?
                t('আপনি কি নিশ্চিত যে এই কাস্টমারকে নিষ্ক্রিয় করতে চান? নিষ্ক্রিয় থাকা অবস্থায় কেনাকাটা করা যাবে না।', 'Are you sure you want to deactivate this customer?') :
                t('আপনি কি নিশ্চিত যে এই কাস্টমারকে সক্রিয় করতে চান?', 'Are you sure you want to activate this customer?')"
            :confirm-text="customerToToggle?.status ? t('নিষ্ক্রিয় করুন', 'Deactivate') : t('সক্রিয় করুন', 'Activate')"
            @confirm="toggleStatus"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="!!customerToDelete" @close="customerToDelete = null"
            :title="t('কাস্টমার মুছে ফেলুন', 'Delete Customer')"
            :message="t('আপনি কি নিশ্চিত যে এই কাস্টমার মুছে ফেলতে চান? এটি আর ফেরত আনা যাবে না।', 'Are you sure you want to delete this customer? This action cannot be undone.')"
            :confirm-text="t('মুছে ফেলুন', 'Delete')"
            @confirm="deleteCustomer"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import _ from 'lodash'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import { Search as SearchIcon, Plus as PlusIcon } from 'lucide-vue-next'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    customers: Object,
    filters: Object
});

const search = ref(props.filters?.search || '');
const filters = ref({
    status: props.filters?.status || null,
    search: props.filters?.search || ''
});

const customerToToggle = ref(null);
const customerToDelete = ref(null);

watch(search, _.debounce((value) => {
    filters.value.search = value;
    applyFilters();
}, 350));

const formatNumber = (value) => {
    return Number(value || 0).toLocaleString('bn-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const applyFilters = () => {
    router.get(route('admin.customers.index'), filters.value, {
        preserveState: true,
        preserveScroll: true
    });
};

const confirmToggleStatus = (customer) => {
    customerToToggle.value = customer;
};

const toggleStatus = () => {
    if (!customerToToggle.value) return;

    router.post(route('admin.customers.toggle-status', customerToToggle.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            customerToToggle.value = null;
        }
    });
};

const confirmDelete = (customer) => {
    customerToDelete.value = customer;
};

const deleteCustomer = () => {
    if (!customerToDelete.value) return;

    router.delete(route('admin.customers.destroy', customerToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            customerToDelete.value = null;
        }
    });
};

watch(() => filters.value.status, applyFilters);
</script>
