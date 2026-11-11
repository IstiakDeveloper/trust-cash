<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLanguage } from '@/composables/useLanguage';
defineProps({ messages: { type: Array, default: () => [] } });
const { t } = useLanguage();
const goBack = () => window.history.back();
</script>

<template>
    <AdminLayout :title="t('রিপোর্ট খোলা যায়নি', 'Report could not be opened')">
        <div role="alert" class="mx-auto max-w-3xl space-y-4 rounded-xl border border-amber-300 bg-amber-50 p-6 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
            <h1 class="text-lg font-bold">{{ t('এই রিপোর্টের জন্য কিছু তথ্য যাচাই প্রয়োজন', 'This report needs some information checked') }}</h1>
            <p v-for="message in messages" :key="message" class="text-sm">{{ message }}</p>
            <p class="text-sm">{{ t('পুরোনো তথ্য সংরক্ষিত আছে। সমস্যাটি ঠিক করে আবার রিপোর্ট খুলুন।', 'Existing data is preserved. Resolve the issue and reopen the report.') }}</p>
            <button type="button" class="rounded-lg bg-indigo-600 px-4 py-2 text-white" @click="goBack">{{ t('ফিরে যান', 'Go back') }}</button>
        </div>
    </AdminLayout>
</template>
