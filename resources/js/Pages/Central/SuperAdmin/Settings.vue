<script setup>
import { ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import {
    Settings,
    CreditCard,
    Building2,
    Shield,
    CheckCircle2,
    AlertTriangle,
    Upload,
    Image as ImageIcon,
    Compass,
    PhoneCall,
    MessageSquare,
    Globe,
    Share2,
    Trash2,
    Sparkles,
    Link2,
    Clock,
    DollarSign,
    Loader2
} from 'lucide-vue-next';

const props = defineProps({
    settings: Object,
});

const isTruthy = (val, defaultVal = false) => {
    if (val === undefined || val === null || val === '') return defaultVal;
    return val === true || val === 'true' || val === '1' || val === 1;
};

const logoPreview = ref(props.settings?.site_logo || null);
const faviconPreview = ref(props.settings?.site_favicon || null);
const showSuccessToast = ref(false);
const errorMessage = ref('');

const form = useForm({
    app_name: props.settings?.app_name || 'TrustCash',
    app_tagline: props.settings?.app_tagline || 'Cloud POS & Accounting SaaS',
    site_logo: null,
    site_favicon: null,
    remove_site_logo: false,
    remove_site_favicon: false,
    currency_symbol: props.settings?.currency_symbol || '৳',
    currency_code: props.settings?.currency_code || 'BDT',
    default_trial_days: props.settings?.default_trial_days || '14',
    auto_approve_tenants: isTruthy(props.settings?.auto_approve_tenants, false),

    // Navigation & CTA
    nav_show_features: isTruthy(props.settings?.nav_show_features, true),
    nav_show_use_cases: isTruthy(props.settings?.nav_show_use_cases, true),
    nav_show_pricing: isTruthy(props.settings?.nav_show_pricing, true),
    nav_show_faq: isTruthy(props.settings?.nav_show_faq, true),
    nav_cta_text: props.settings?.nav_cta_text || '১৪ দিন ফ্রি ট্রায়াল শুরু করুন',
    nav_cta_url: props.settings?.nav_cta_url || '/register-business',

    // Support & Social
    whatsapp_number: props.settings?.whatsapp_number || '',
    support_phone: props.settings?.support_phone || '+880 1700-000000',
    support_email: props.settings?.support_email || 'support@trustcash.com',
    company_address: props.settings?.company_address || 'Dhaka, Bangladesh',
    social_facebook: props.settings?.social_facebook || '',
    social_youtube: props.settings?.social_youtube || '',
    social_linkedin: props.settings?.social_linkedin || '',

    // SSLCommerz
    sslcommerz_store_id: props.settings?.sslcommerz_store_id || '',
    sslcommerz_store_passwd: props.settings?.sslcommerz_store_passwd || '',
    sslcommerz_sandbox: isTruthy(props.settings?.sslcommerz_sandbox, false),

    // bKash Tokenized
    bkash_app_key: props.settings?.bkash_app_key || '',
    bkash_app_secret: props.settings?.bkash_app_secret || '',
    bkash_username: props.settings?.bkash_username || '',
    bkash_password: props.settings?.bkash_password || '',
    bkash_sandbox: isTruthy(props.settings?.bkash_sandbox, false),
});

// Sync previews when props update from server
watch(() => props.settings, (newSettings) => {
    if (newSettings) {
        if (!form.site_logo && !form.remove_site_logo) {
            logoPreview.value = newSettings.site_logo || null;
        }
        if (!form.site_favicon && !form.remove_site_favicon) {
            faviconPreview.value = newSettings.site_favicon || null;
        }
    }
}, { deep: true });

const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.site_logo = file;
        form.remove_site_logo = false;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const handleFaviconChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.site_favicon = file;
        form.remove_site_favicon = false;
        faviconPreview.value = URL.createObjectURL(file);
    }
};

const removeLogo = () => {
    form.site_logo = null;
    form.remove_site_logo = true;
    logoPreview.value = null;
};

const removeFavicon = () => {
    form.site_favicon = null;
    form.remove_site_favicon = true;
    faviconPreview.value = null;
};

const submitSettings = () => {
    errorMessage.value = '';

    // Use relative path '/super-admin/settings' so it works seamlessly on any domain/port
    form.post('/super-admin/settings', {
        preserveScroll: true,
        onSuccess: (page) => {
            showSuccessToast.value = true;
            form.site_logo = null;
            form.site_favicon = null;
            form.remove_site_logo = false;
            form.remove_site_favicon = false;

            if (page.props.settings) {
                logoPreview.value = page.props.settings.site_logo || null;
                faviconPreview.value = page.props.settings.site_favicon || null;
            }

            setTimeout(() => {
                showSuccessToast.value = false;
            }, 4000);
        },
        onError: (errs) => {
            console.error('Settings save errors:', errs);
            const first = Object.values(errs)[0];
            errorMessage.value = first || 'Failed to save settings. Please verify input fields.';
        },
    });
};
</script>

<template>
    <Head title="Platform Settings | TrustCash Super Admin" />

    <SuperAdminLayout>
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <Settings class="w-6 h-6 text-emerald-400" />
                        Global SaaS Platform Settings
                    </h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Customize platform identity, official logos, landing navigation, contact channels & payment gateways
                    </p>
                </div>

                <button
                    type="button"
                    @click="submitSettings"
                    :disabled="form.processing"
                    class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold text-xs shadow-lg shadow-emerald-500/20 transition flex items-center gap-2 disabled:opacity-50"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <CheckCircle2 v-else class="w-4 h-4" />
                    {{ form.processing ? 'Saving Changes...' : 'Save All Settings' }}
                </button>
            </div>
        </template>

        <!-- Floating Success Notification -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showSuccessToast"
                class="fixed top-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5 bg-emerald-600 text-white rounded-2xl shadow-2xl border border-emerald-500 font-semibold text-xs animate-bounce"
            >
                <CheckCircle2 class="w-5 h-5 text-emerald-200" />
                <span>প্ল্যাটফর্ম সেটিংস সফলভাবে সংরক্ষিত হয়েছে! (Settings saved successfully)</span>
            </div>
        </transition>

        <!-- Error Alert Banner -->
        <div
            v-if="errorMessage || $page.props.flash?.error || form.hasErrors"
            class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-center gap-3"
        >
            <AlertTriangle class="w-5 h-5 shrink-0" />
            <span>{{ errorMessage || $page.props.flash?.error || 'সেটিংস সেভ হতে ব্যর্থ হয়েছে। অনুগ্রহ করে ইনপুটগুলো সঠিক আছে কিনা দেখুন।' }}</span>
        </div>

        <form @submit.prevent="submitSettings" class="max-w-5xl space-y-6">
            <!-- 1. Brand & Logo Identity -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-5 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                            <Building2 class="w-4 h-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Brand Identity & Visuals</h2>
                            <p class="text-[11px] text-slate-400">Manage main SaaS logo, favicon icon, and app metadata</p>
                        </div>
                    </div>
                </div>

                <!-- Logo & Favicon Upload Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 p-4 rounded-xl bg-slate-950/60 border border-slate-850">
                    <!-- Main Logo -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-2">Main Platform Logo</label>
                        <div class="flex items-center gap-4">
                            <div class="w-24 h-16 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center overflow-hidden p-2 relative group">
                                <img
                                    v-if="logoPreview"
                                    :src="logoPreview"
                                    alt="Logo preview"
                                    class="max-w-full max-h-full object-contain"
                                />
                                <div v-else class="text-center">
                                    <ImageIcon class="w-6 h-6 text-slate-600 mx-auto" />
                                    <span class="text-[9px] text-slate-500">No logo</span>
                                </div>
                            </div>
                            <div class="flex-1 space-y-2">
                                <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium cursor-pointer border border-slate-700 transition">
                                    <Upload class="w-3.5 h-3.5 text-emerald-400" />
                                    <span>Upload New Logo</span>
                                    <input type="file" accept="image/*" class="hidden" @change="handleLogoChange" />
                                </label>
                                <button
                                    v-if="logoPreview"
                                    type="button"
                                    @click="removeLogo"
                                    class="inline-flex items-center gap-1.5 ml-2 px-2.5 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 text-xs font-medium border border-rose-500/20 transition"
                                >
                                    <Trash2 class="w-3 h-3" />
                                    Remove
                                </button>
                                <p class="text-[11px] text-slate-500">PNG, SVG or WEBP (Recommended: 240x60px, transparent)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Favicon -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-2">Browser Favicon Icon</label>
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center overflow-hidden p-2">
                                <img
                                    v-if="faviconPreview"
                                    :src="faviconPreview"
                                    alt="Favicon preview"
                                    class="w-8 h-8 object-contain"
                                />
                                <div v-else class="text-center">
                                    <Globe class="w-6 h-6 text-slate-600 mx-auto" />
                                    <span class="text-[9px] text-slate-500">Default</span>
                                </div>
                            </div>
                            <div class="flex-1 space-y-2">
                                <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium cursor-pointer border border-slate-700 transition">
                                    <Upload class="w-3.5 h-3.5 text-emerald-400" />
                                    <span>Upload Favicon</span>
                                    <input type="file" accept="image/*,.ico" class="hidden" @change="handleFaviconChange" />
                                </label>
                                <button
                                    v-if="faviconPreview"
                                    type="button"
                                    @click="removeFavicon"
                                    class="inline-flex items-center gap-1.5 ml-2 px-2.5 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 text-xs font-medium border border-rose-500/20 transition"
                                >
                                    <Trash2 class="w-3 h-3" />
                                    Remove
                                </button>
                                <p class="text-[11px] text-slate-500">ICO, PNG or SVG (Recommended: 32x32px or 64x64px)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Text metadata -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
                    <div>
                        <label class="block text-slate-400 mb-1">Company / SaaS Name</label>
                        <input
                            v-model="form.app_name"
                            type="text"
                            required
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500 font-semibold"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">Tagline / Slogan</label>
                        <input
                            v-model="form.app_tagline"
                            type="text"
                            placeholder="Cloud POS & Accounting SaaS"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
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
                        <label class="block text-slate-400 mb-1">Currency Code</label>
                        <input
                            v-model="form.currency_code"
                            type="text"
                            placeholder="BDT"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-bold text-center focus:border-emerald-500 uppercase"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">Default Free Trial (Days)</label>
                        <input
                            v-model="form.default_trial_days"
                            type="number"
                            min="0"
                            max="365"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-bold text-center focus:border-emerald-500"
                        />
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="flex items-center gap-2.5 cursor-pointer text-xs text-slate-300">
                            <input
                                type="checkbox"
                                v-model="form.auto_approve_tenants"
                                class="rounded bg-slate-950 border-slate-700 text-emerald-500 focus:ring-0 w-4 h-4"
                            />
                            <div>
                                <span class="font-semibold text-white">Auto-Approve New Stores</span>
                                <p class="text-[10px] text-slate-500">Instantly activate shops upon registration</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 2. Landing Page Navigation & Call to Action (CTA) -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 shadow-sm">
                <div class="flex items-center gap-2.5 border-b border-slate-800 pb-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center">
                        <Compass class="w-4 h-4" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">Landing Navigation & Call-to-Action (CTA)</h2>
                        <p class="text-[11px] text-slate-400">Control header menu links and primary registration button</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Nav Items Toggles -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-2">Visible Header Navigation Links</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-950/70 border border-slate-850 cursor-pointer hover:border-slate-750 transition">
                                <input
                                    type="checkbox"
                                    v-model="form.nav_show_features"
                                    class="rounded bg-slate-900 border-slate-700 text-blue-500 focus:ring-0"
                                />
                                <span class="text-xs text-slate-200 font-medium">Features (ফিচারসমূহ)</span>
                            </label>

                            <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-950/70 border border-slate-850 cursor-pointer hover:border-slate-750 transition">
                                <input
                                    type="checkbox"
                                    v-model="form.nav_show_use_cases"
                                    class="rounded bg-slate-900 border-slate-700 text-blue-500 focus:ring-0"
                                />
                                <span class="text-xs text-slate-200 font-medium">Use Cases (কার জন্য)</span>
                            </label>

                            <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-950/70 border border-slate-850 cursor-pointer hover:border-slate-750 transition">
                                <input
                                    type="checkbox"
                                    v-model="form.nav_show_pricing"
                                    class="rounded bg-slate-900 border-slate-700 text-blue-500 focus:ring-0"
                                />
                                <span class="text-xs text-slate-200 font-medium">Pricing (মূল্য তালিকা)</span>
                            </label>

                            <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-950/70 border border-slate-850 cursor-pointer hover:border-slate-750 transition">
                                <input
                                    type="checkbox"
                                    v-model="form.nav_show_faq"
                                    class="rounded bg-slate-900 border-slate-700 text-blue-500 focus:ring-0"
                                />
                                <span class="text-xs text-slate-200 font-medium">FAQ (প্রশ্নোত্তর)</span>
                            </label>
                        </div>
                    </div>

                    <!-- CTA Settings -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pt-2">
                        <div>
                            <label class="block text-slate-400 mb-1">Header CTA Button Text</label>
                            <input
                                v-model="form.nav_cta_text"
                                type="text"
                                placeholder="১৪ দিন ফ্রি ট্রায়াল শুরু করুন"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-blue-500 font-medium"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Header CTA Target Link / URL</label>
                            <div class="relative">
                                <Link2 class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" />
                                <input
                                    v-model="form.nav_cta_url"
                                    type="text"
                                    placeholder="/register-business"
                                    class="w-full pl-9 pr-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-blue-500 font-mono"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Customer Support, WhatsApp & Social Channels -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 shadow-sm">
                <div class="flex items-center gap-2.5 border-b border-slate-800 pb-3">
                    <div class="w-8 h-8 rounded-lg bg-teal-500/10 text-teal-400 flex items-center justify-center">
                        <MessageSquare class="w-4 h-4" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">Contact & Social Channels</h2>
                        <p class="text-[11px] text-slate-400">Direct WhatsApp support button, official helpline & corporate info</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
                    <div class="sm:col-span-2 md:col-span-1">
                        <label class="block text-slate-400 mb-1">WhatsApp Business Number</label>
                        <div class="relative">
                            <input
                                v-model="form.whatsapp_number"
                                type="text"
                                placeholder="8801700000000"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-teal-500 font-mono"
                            />
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Country code without + (e.g. 8801712345678) for live floating chat</p>
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">Support Phone Hotline</label>
                        <input
                            v-model="form.support_phone"
                            type="text"
                            placeholder="+880 1700-000000"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-teal-500"
                        />
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">Official Support Email</label>
                        <input
                            v-model="form.support_email"
                            type="email"
                            placeholder="support@trustcash.com"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-teal-500"
                        />
                    </div>

                    <div class="sm:col-span-2 md:col-span-3">
                        <label class="block text-slate-400 mb-1">Registered Office Address</label>
                        <input
                            v-model="form.company_address"
                            type="text"
                            placeholder="House #00, Road #00, Dhanmondi, Dhaka-1205"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-teal-500"
                        />
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">Facebook Page / Group URL</label>
                        <input
                            v-model="form.social_facebook"
                            type="text"
                            placeholder="https://facebook.com/trustcash"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-teal-500"
                        />
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">YouTube Channel URL</label>
                        <input
                            v-model="form.social_youtube"
                            type="text"
                            placeholder="https://youtube.com/@trustcash"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-teal-500"
                        />
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">LinkedIn Page URL</label>
                        <input
                            v-model="form.social_linkedin"
                            type="text"
                            placeholder="https://linkedin.com/company/trustcash"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-teal-500"
                        />
                    </div>
                </div>
            </div>

            <!-- 4. SSLCommerz Payment Gateway -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center">
                            <CreditCard class="w-4 h-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">SSLCommerz Payment Gateway</h2>
                            <p class="text-[11px] text-slate-400">Accept cards, MFS (Nagad, Rocket, Upay) and internet banking</p>
                        </div>
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

            <!-- 5. bKash Gateway Configuration -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-rose-500 flex items-center justify-center font-black text-white text-xs">
                            b
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">bKash Direct Tokenized Checkout</h2>
                            <p class="text-[11px] text-slate-400">Direct 1-click seamless bKash API checkout credentials</p>
                        </div>
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

            <!-- Sticky Save Bar -->
            <div class="sticky bottom-6 z-20 flex items-center justify-between p-4 rounded-2xl bg-slate-900/90 backdrop-blur-md border border-slate-850 shadow-2xl">
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <Sparkles class="w-4 h-4 text-amber-400" />
                    <span>Changes take effect globally across the landing page and tenant registration flows.</span>
                </div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-7 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold text-xs shadow-lg shadow-emerald-500/20 transition flex items-center gap-2 disabled:opacity-50"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <CheckCircle2 v-else class="w-4 h-4" />
                    {{ form.processing ? 'Saving...' : 'Save All Settings' }}
                </button>
            </div>
        </form>
    </SuperAdminLayout>
</template>