<template>
    <Head :title="t('কাস্টমার তথ্য সংশোধন', 'Edit Customer') + ' - ' + customer.name" />
    <AdminLayout :title="t('কাস্টমার ব্যবস্থাপনা', 'Customer Management')">
        <div class="max-w-3xl mx-auto space-y-4 sm:space-y-6">
            <!-- Header with Back Button -->
            <div class="flex items-center gap-3">
                <Link :href="route('admin.customers.index')"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    <ArrowLeftIcon class="h-4 w-4" />
                </Link>
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">
                        {{ t('কাস্টমার তথ্য সংশোধন', 'Edit Customer') }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ customer.name }} ({{ customer.phone }})
                    </p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Name -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('কাস্টমারের নাম *', 'Customer Name *') }}
                            </label>
                            <input type="text" v-model="form.name" required
                                class="w-full min-h-[42px] px-3.5 py-2 text-xs font-medium rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600" />
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('মোবাইল নম্বর *', 'Phone *') }}
                            </label>
                            <input type="text" v-model="form.phone" required
                                class="w-full min-h-[42px] px-3.5 py-2 text-xs font-medium rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600" />
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('ইমেইল', 'Email') }}
                            </label>
                            <input type="email" v-model="form.email"
                                class="w-full min-h-[42px] px-3.5 py-2 text-xs font-medium rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600" />
                        </div>

                        <!-- Address -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('ঠিকানা', 'Address') }}
                            </label>
                            <textarea v-model="form.address" rows="2"
                                class="w-full px-3.5 py-2 text-xs font-medium rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"></textarea>
                        </div>

                        <!-- Branch Code -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('শাখা কোড', 'Branch Code') }}
                            </label>
                            <input type="text" v-model="form.branch_code"
                                class="w-full min-h-[42px] px-3.5 py-2 text-xs font-medium rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600" />
                        </div>

                        <!-- Branch Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('শাখার নাম', 'Branch Name') }}
                            </label>
                            <input type="text" v-model="form.branch_name"
                                class="w-full min-h-[42px] px-3.5 py-2 text-xs font-medium rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600" />
                        </div>

                        <!-- Credit Limit -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('বাকি বা ক্রেডিট সীমা (টাকা)', 'Credit Limit') }}
                            </label>
                            <input type="number" v-model="form.credit_limit" min="0" step="0.01"
                                class="w-full min-h-[42px] px-3.5 py-2 text-xs font-medium rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600" />
                        </div>

                        <!-- Initial Balance -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('ব্যালেন্স', 'Balance') }}
                            </label>
                            <input type="number" v-model="form.balance" step="0.01"
                                class="w-full min-h-[42px] px-3.5 py-2 text-xs font-medium rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600" />
                        </div>

                        <!-- Points -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('রিওয়ার্ড পয়েন্টস', 'Points') }}
                            </label>
                            <input type="number" v-model="form.points" min="0"
                                class="w-full min-h-[42px] px-3.5 py-2 text-xs font-medium rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600" />
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('অবস্থা', 'Status') }}
                            </label>
                            <select v-model="form.status"
                                class="w-full min-h-[42px] px-3.5 py-2 text-xs font-medium rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600">
                                <option :value="true">{{ t('সক্রিয় (Active)', 'Active') }}</option>
                                <option :value="false">{{ t('নিষ্ক্রিয় (Inactive)', 'Inactive') }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <Link :href="route('admin.customers.index')"
                            class="w-full sm:w-auto text-center px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 transition">
                            {{ t('বাতিল', 'Cancel') }}
                        </Link>
                        <button type="submit" :disabled="form.processing"
                            class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-indigo-600 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 transition">
                            {{ t('পরিবর্তন সংরক্ষণ করুন', 'Update Customer') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ArrowLeft as ArrowLeftIcon } from 'lucide-vue-next'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    customer: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    name: props.customer.name,
    email: props.customer.email,
    phone: props.customer.phone,
    address: props.customer.address,
    branch_code: props.customer.branch_code,
    branch_name: props.customer.branch_name,
    credit_limit: props.customer.credit_limit,
    balance: props.customer.balance,
    points: props.customer.points,
    status: Boolean(props.customer.status),
})

const submit = () => {
    form.put(route('admin.customers.update', props.customer.id))
}
</script>
