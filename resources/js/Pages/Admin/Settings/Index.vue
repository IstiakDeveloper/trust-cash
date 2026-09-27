<template>
    <Head :title="t('সেটিংস', 'Settings')" />
    <AdminLayout :title="t('ব্যবসায়িক সেটিংস', 'Business Settings')">
        <div class="container mx-auto px-4 py-6 max-w-4xl">
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ t('ব্যবসায় ও পিওএস সেটিংস', 'Business & POS Settings') }}</h2>
                <p class="text-xs text-gray-500">{{ t('ব্যবসায়ের পরিচয়, রসিদ ফরম্যাট, মুদ্রা ও ভ্যাট সেট করুন', 'Configure your business identity, receipt format, currency and VAT') }}</p>
            </div>

            <form @submit.prevent="saveSettings" class="space-y-6">
                <!-- Business Identity Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider">Business Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">{{ t('ব্যবসা / দোকানের নাম *', 'Business / Store Name *') }}</label>
                            <input
                                type="text"
                                v-model="form.business_name"
                                required
                                class="w-full px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">{{ t('যোগাযোগ নম্বর', 'Contact Phone') }}</label>
                            <input
                                type="text"
                                v-model="form.business_phone"
                                class="w-full px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">{{ t('যোগাযোগ ইমেইল', 'Contact Email') }}</label>
                            <input
                                type="email"
                                v-model="form.business_email"
                                class="w-full px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">Store Address / City</label>
                            <input
                                type="text"
                                v-model="form.business_address"
                                class="w-full px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600"
                            />
                        </div>
                    </div>
                </div>

                <!-- Currency & VAT Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider">Currency & Tax</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">Currency Symbol</label>
                            <input
                                type="text"
                                v-model="form.currency_symbol"
                                placeholder="৳"
                                class="w-full px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">Currency Code</label>
                            <input
                                type="text"
                                v-model="form.currency_code"
                                placeholder="BDT"
                                class="w-full px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 uppercase"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">Default Tax / VAT (%)</label>
                            <input
                                type="number"
                                step="0.1"
                                v-model="form.tax_percentage"
                                placeholder="0"
                                class="w-full px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600"
                            />
                        </div>
                    </div>
                </div>

                <!-- Invoice & Receipt Settings -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider">Receipt & Printer Setup</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">Invoice Number Prefix</label>
                            <input
                                type="text"
                                v-model="form.invoice_prefix"
                                placeholder="INV-"
                                class="w-full px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">Receipt Paper Size</label>
                            <select
                                v-model="form.receipt_paper_size"
                                class="w-full px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600"
                            >
                                <option value="80mm">80mm Thermal POS Printer</option>
                                <option value="58mm">58mm Thermal Mini Printer</option>
                                <option value="A4">A4 / Normal Printer</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block font-medium text-xs text-gray-700 dark:text-gray-300 mb-1">Receipt Footer Note</label>
                            <textarea
                                v-model="form.invoice_footer_note"
                                rows="2"
                                placeholder="Thank you for shopping with us!"
                                class="w-full px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-md transition disabled:opacity-50 text-sm"
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
})

const saving = ref(false)
const form = ref({ ...props.settings })

const saveSettings = () => {
    saving.value = true
    router.post(route('admin.settings.update'), form.value, {
        onSuccess: () => {
            saving.value = false
        },
        onError: () => {
            saving.value = false
        }
    })
}
</script>
