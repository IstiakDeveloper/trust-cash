<template>
    <Head :title="t('সেটিংস', 'Settings')" />
    <AdminLayout :title="t('ব্যবসায়িক সেটিংস', 'Business Settings')">
        <div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">{{ t('ব্যবসায় ও পিওএস সেটিংস', 'Business & POS Settings') }}</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('ব্যবসায়ের পরিচয়, রসিদ ফরম্যাট, মুদ্রা ও ভ্যাট সেট করুন', 'Configure your business identity, receipt format, currency and VAT') }}</p>
                </div>
            </div>

            <form @submit.prevent="saveSettings" class="space-y-4 sm:space-y-6">
                <div class="bg-white dark:bg-slate-900 p-4 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
                    <label for="inventory-method" class="block text-sm font-bold dark:text-white">{{ t('স্টকের মূল্য নির্ধারণ পদ্ধতি', 'Inventory Costing Method') }}</label>
                    <select id="inventory-method" v-model="form.inventory_costing_method" :disabled="inventoryMethodLocked" class="w-full rounded-xl border-slate-300 dark:bg-slate-800 dark:text-white disabled:opacity-60">
                        <option value="fifo">{{ t('FIFO — আগে আসা স্টকের খরচ আগে', 'FIFO — First In, First Out') }}</option>
                        <option value="moving_weighted_average">{{ t('চলমান গড় ক্রয়মূল্য', 'Moving Weighted Average') }}</option>
                    </select>
                    <p class="text-xs text-slate-500">{{ t('???? ?????? ?????? ? ?? ????? ???? ?????? Lot/FIFO ???? ???? ??????? ???? ???? opening lot ??? ??????? ??????? ???? ???? lot ???? Average ???? ??? ?????? ??? ?? ????? ?????', 'Existing quantity and average value stay intact. FIFO starts with one opening lot per product/variant; new purchases create new lots. Average continues from the existing stock value.') }}</p>
                    <p class="text-xs text-slate-500">{{ t('উভয় পদ্ধতিতে লটের ক্রয়মূল্য ও বিক্রয়মূল্য আলাদা থাকবে। নতুন স্টক এলে পুরোনো বিক্রির লাভ বদলাবে না।', 'Both methods retain lot purchase and selling prices. New stock never changes the cost of earlier sales.') }}</p>
                    <p v-if="inventoryMethodLocked" class="text-xs text-amber-600">{{ t('স্টক লেনদেন হয়েছে। পদ্ধতি বদলাতে হিসাব মিলিয়ে নিয়ন্ত্রিত পরিবর্তন প্রয়োজন।', 'Inventory has been posted. Changing methods requires a reconciled accounting cutover.') }}</p>
                    <p v-if="errors.inventory_costing_method" class="text-xs text-red-600">{{ errors.inventory_costing_method }}</p>
                    <label class="block text-xs font-bold dark:text-white">{{ t('ব্যবসার সময় অঞ্চল', 'Business timezone') }}
                        <input v-model="form.business_timezone" list="business-timezones" class="mt-2 block w-full rounded-xl border-slate-300 dark:bg-slate-800" />
                        <datalist id="business-timezones"><option value="Asia/Dhaka" /><option value="UTC" /><option value="Asia/Kolkata" /></datalist>
                    </label>
                    <p v-if="errors.business_timezone" class="text-xs text-red-600">{{ errors.business_timezone }}</p>
                </div>
                <!-- Business Identity Card -->
                <div class="bg-white dark:bg-slate-900 p-4 sm:p-6 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            {{ t('ব্যবসায়ের তথ্য', 'Business Information') }}
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                        <div>
                            <label class="block font-bold text-xs text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('ব্যবসা / দোকানের নাম *', 'Business / Store Name *') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.business_name"
                                required
                                class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl text-xs font-medium border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-xs text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('যোগাযোগ নম্বর', 'Contact Phone') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.business_phone"
                                placeholder="017XXXXXXXX"
                                class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl text-xs font-medium border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-xs text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('যোগাযোগ ইমেইল', 'Contact Email') }}
                            </label>
                            <input
                                type="email"
                                v-model="form.business_email"
                                placeholder="info@store.com"
                                class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl text-xs font-medium border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-xs text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('ঠিকানা / শহর', 'Store Address / City') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.business_address"
                                placeholder="Dhanmondi, Dhaka"
                                class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl text-xs font-medium border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            />
                        </div>
                    </div>
                </div>

                <!-- Currency & VAT Card -->
                <div class="bg-white dark:bg-slate-900 p-4 sm:p-6 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            {{ t('মুদ্রা ও কর (Tax)', 'Currency & Tax') }}
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
                        <div>
                            <label class="block font-bold text-xs text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('মুদ্রার প্রতীক', 'Currency Symbol') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.currency_symbol"
                                placeholder="৳"
                                class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl text-xs font-medium border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-xs text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('কারেন্সি কোড', 'Currency Code') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.currency_code"
                                placeholder="BDT"
                                class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl text-xs font-medium border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600 uppercase"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-xs text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('ডিফল্ট ভ্যাট / ট্যাক্স (%)', 'Default Tax / VAT (%)') }}
                            </label>
                            <input
                                type="number"
                                step="0.1"
                                v-model="form.tax_percentage"
                                placeholder="0"
                                class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl text-xs font-medium border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            />
                        </div>
                    </div>
                </div>

                <!-- Invoice & Receipt Settings -->
                <div class="bg-white dark:bg-slate-900 p-4 sm:p-6 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            {{ t('রসিদ ও প্রিন্টার সেটিংস', 'Receipt & Printer Setup') }}
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                        <div>
                            <label class="block font-bold text-xs text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('ইনভয়েস প্রিফিক্স', 'Invoice Prefix') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.invoice_prefix"
                                placeholder="INV-"
                                class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl text-xs font-medium border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-xs text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('রসিদের কাগজের সাইজ', 'Receipt Paper Size') }}
                            </label>
                            <select
                                v-model="form.receipt_paper_size"
                                class="w-full min-h-[42px] px-3.5 py-2 border rounded-xl text-xs font-medium border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            >
                                <option value="80mm">80mm Thermal POS Printer</option>
                                <option value="58mm">58mm Thermal Mini Printer</option>
                                <option value="A4">A4 / Normal Printer</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-xs text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('রসিদের নিচের মন্তব্য (Footer Note)', 'Receipt Footer Note') }}
                            </label>
                            <textarea
                                v-model="form.invoice_footer_note"
                                rows="2"
                                :placeholder="t('আমাদের সাথে কেনাকাটা করার জন্য ধন্যবাদ!', 'Thank you for shopping with us!')"
                                class="w-full px-3.5 py-2 border rounded-xl text-xs font-medium border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-600"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-md transition disabled:opacity-50 text-xs"
                    >
                        {{ saving ? t('সেভ হচ্ছে...', 'Saving...') : t('সেটিংস সেভ করুন', 'Save Settings') }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    settings: Object,
    inventoryMethodLocked: Boolean,
})

const saving = ref(false)
const errors = ref({})
const form = ref({ ...props.settings })

const saveSettings = () => {
    errors.value = {}
    saving.value = true
    router.post(route('admin.settings.update'), form.value, {
        onSuccess: () => {
            saving.value = false
        },
        onError: (validationErrors) => {
            errors.value = validationErrors
            saving.value = false
        }
    })
}
</script>
