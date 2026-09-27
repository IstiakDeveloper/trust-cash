<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import {
    Settings,
    CreditCard,
    Building2,
    Shield,
    CheckCircle2
} from 'lucide-vue-next';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    app_name: props.settings?.app_name || 'TrustCash',
    support_phone: props.settings?.support_phone || '+880 1700-000000',
    support_email: props.settings?.support_email || 'support@trustcash.com',
    company_address: props.settings?.company_address || 'Dhaka, Bangladesh',
    currency_symbol: props.settings?.currency_symbol || '৳',
    
    // SSLCommerz
    sslcommerz_store_id: props.settings?.sslcommerz_store_id || '',
    sslcommerz_store_passwd: props.settings?.sslcommerz_store_passwd || '',
    sslcommerz_sandbox: props.settings?.sslcommerz_sandbox === 'true' || props.settings?.sslcommerz_sandbox === true,

    // bKash Tokenized
    bkash_app_key: props.settings?.bkash_app_key || '',
    bkash_app_secret: props.settings?.bkash_app_secret || '',
    bkash_username: props.settings?.bkash_username || '',
    bkash_password: props.settings?.bkash_password || '',
    bkash_sandbox: props.settings?.bkash_sandbox === 'true' || props.settings?.bkash_sandbox === true,
});

const submitSettings = () => {
    form.post(route('super-admin.settings.update'));
};
</script>

<template>
    <Head title="Platform Settings | TrustCash Super Admin" />

    <SuperAdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white tracking-tight">Global SaaS Platform Settings</h1>
            <p class="text-xs text-slate-400">Configure landlord branding, credentials & payment gateway APIs</p>
        </template>

        <form @submit.prevent="submitSettings" class="max-w-4xl space-y-6">
            <!-- Brand & Company Details -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 shadow-sm">
                <div class="flex items-center gap-2 border-b border-slate-800 pb-3">
                    <Building2 class="w-5 h-5 text-emerald-400" />
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Platform Brand Identity</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block text-slate-400 mb-1">Company / Platform Name</label>
                        <input
                            v-model="form.app_name"
                            type="text"
                            required
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500 font-semibold"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">Currency Symbol</label>
                        <input
                            v-model="form.currency_symbol"
                            type="text"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-bold text-center focus:border-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">Customer Support Hotline</label>
                        <input
                            v-model="form.support_phone"
                            type="text"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">Official Support Email</label>
                        <input
                            v-model="form.support_email"
                            type="email"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-slate-400 mb-1">Registered Office Address</label>
                        <input
                            v-model="form.company_address"
                            type="text"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                        />
                    </div>
                </div>
            </div>

            <!-- SSLCommerz Gateway Configuration -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <CreditCard class="w-5 h-5 text-purple-400" />
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">SSLCommerz Payment Gateway</h2>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                        <input
                            type="checkbox"
                            v-model="form.sslcommerz_sandbox"
                            class="rounded bg-slate-950 border-slate-700 text-purple-500 focus:ring-0"
                        />
                        <span>Sandbox / Test Mode</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block text-slate-400 mb-1">Store ID</label>
                        <input
                            v-model="form.sslcommerz_store_id"
                            type="text"
                            placeholder="mousu676100c598d1a"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:border-purple-500"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">Store Password</label>
                        <input
                            v-model="form.sslcommerz_store_passwd"
                            type="password"
                            placeholder="••••••••••••"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:border-purple-500"
                        />
                    </div>
                </div>
            </div>

            <!-- bKash Gateway Configuration -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded-md bg-rose-500 flex items-center justify-center font-bold text-white text-[10px]">
                            b
                        </div>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">bKash Direct Tokenized Checkout</h2>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                        <input
                            type="checkbox"
                            v-model="form.bkash_sandbox"
                            class="rounded bg-slate-950 border-slate-700 text-rose-500 focus:ring-0"
                        />
                        <span>Sandbox / Test Mode</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block text-slate-400 mb-1">bKash App Key</label>
                        <input
                            v-model="form.bkash_app_key"
                            type="text"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:border-rose-500"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">bKash App Secret</label>
                        <input
                            v-model="form.bkash_app_secret"
                            type="password"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:border-rose-500"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">bKash Username</label>
                        <input
                            v-model="form.bkash_username"
                            type="text"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:border-rose-500"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">bKash Password</label>
                        <input
                            v-model="form.bkash_password"
                            type="password"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:border-rose-500"
                        />
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold text-xs shadow-lg shadow-emerald-500/20 transition disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Save Global Settings' }}
                </button>
            </div>
        </form>
    </SuperAdminLayout>
</template>