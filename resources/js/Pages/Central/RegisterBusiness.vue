<template>
    <Head :title="($page.props.platform?.name || 'TrustCash') + ' - ' + t('ব্যবসা রেজিস্ট্রেশন', 'Business Registration')">
        <link v-if="$page.props.platform?.favicon" rel="icon" :href="$page.props.platform.favicon" />
        <link v-if="$page.props.platform?.favicon" rel="shortcut icon" :href="$page.props.platform.favicon" />
    </Head>

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col justify-between transition-colors duration-200">
        <!-- Top Navigation Bar -->
        <header class="w-full max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
            <Link href="/" class="flex items-center gap-2.5 group">
                <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center p-1.5 shadow-sm overflow-hidden transition-transform group-hover:scale-105">
                    <img
                        v-if="$page.props.platform?.logo"
                        :src="$page.props.platform.logo"
                        :alt="$page.props.platform?.name || 'Logo'"
                        class="w-full h-full object-contain"
                    />
                    <div v-else class="w-full h-full rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center font-black text-white text-base shadow-sm">
                        {{ ($page.props.platform?.name || 'T')[0] }}
                    </div>
                </div>
                <div>
                    <span class="text-base font-extrabold tracking-tight text-slate-900 dark:text-white">
                        {{ $page.props.platform?.name || 'TrustCash' }}
                    </span>
                    <span class="block text-[10px] text-blue-600 dark:text-blue-400 font-medium -mt-1">
                        {{ $page.props.platform?.tagline || 'Cloud POS & ERP' }}
                    </span>
                </div>
            </Link>

            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Language Switcher Pill -->
                <div class="flex items-center p-0.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold shadow-sm">
                    <button
                        type="button"
                        @click="setLanguage('bn')"
                        :class="[
                            'px-2.5 py-1 rounded-lg transition-all',
                            isBangla ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                        title="বাংলা ভাষা"
                    >
                        বাং
                    </button>
                    <button
                        type="button"
                        @click="setLanguage('en')"
                        :class="[
                            'px-2.5 py-1 rounded-lg transition-all',
                            !isBangla ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                        title="English Language"
                    >
                        EN
                    </button>
                </div>

                <!-- Theme Switcher (Dark / Light) -->
                <button
                    type="button"
                    @click="toggleTheme"
                    class="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-600 dark:text-slate-300 transition shadow-sm"
                    :title="isDark ? t('লাইট থিমে পরিবর্তন করুন', 'Switch to Light Theme') : t('ডার্ক থিমে পরিবর্তন করুন', 'Switch to Dark Theme')"
                >
                    <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                    <Moon v-else class="w-4 h-4 text-slate-600" />
                </button>

                <!-- Login Shortcut Link -->
                <Link
                    :href="route('login')"
                    class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 transition shadow-sm"
                >
                    <span>{{ t('লগইন', 'Login') }}</span>
                </Link>
            </div>
        </header>

        <!-- Main Body -->
        <main class="max-w-xl w-full mx-auto px-4 my-auto pt-2 pb-12">
            <!-- Hero Title -->
            <div class="text-center mb-7">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    {{ t('আপনার ব্যবসার একাউন্ট তৈরি করুন', 'Create Your Business Account') }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2">
                    {{ t('১৪ দিনের ফ্রি ট্রায়াল শুরু করুন। কোনো ক্রেডিট কার্ডের প্রয়োজন নেই।', 'Start your 14-day free trial. No credit card required.') }}
                </p>
            </div>

            <!-- Success Card (Pending Approval) -->
            <div v-if="isRegistered" class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl shadow-slate-200/50 dark:shadow-2xl dark:shadow-black/50 text-center space-y-6 animate-in fade-in zoom-in duration-300">
                <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 dark:text-amber-400 mx-auto flex items-center justify-center shadow-lg shadow-amber-500/10">
                    <Clock class="w-8 h-8" />
                </div>

                <div class="space-y-2">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                        {{ t('অনুমোদনের অপেক্ষায় (Pending Approval)', 'Pending Admin Approval') }}
                    </span>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white pt-2">
                        {{ t('অভিনন্দন! রেজিস্ট্রেশন সম্পন্ন হয়েছে', 'Congratulations! Registration Complete') }}
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed max-w-md mx-auto">
                        {{ registeredData?.message || t('আপনার শপের ডাটাবেজ প্রস্তুত হয়েছে। সিকিউরিটি ভেরিফিকেশনের জন্য অ্যাকাউন্টটি বর্তমানে অ্যাডমিন অনুমোদনের অপেক্ষায় রয়েছে।', 'Your shop database has been initialized. For security verification, your account is currently awaiting administrator approval.') }}
                    </p>
                </div>

                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-left text-xs space-y-2.5 max-w-md mx-auto">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 dark:text-slate-400">{{ t('প্রতিষ্ঠানের নাম:', 'Business Name:') }}</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ form.business_name }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 dark:text-slate-400">{{ t('আপনার ডোমেইন:', 'Your Domain:') }}</span>
                        <span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ registeredData?.subdomain || (form.subdomain + '.' + resolvedBaseDomain) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 dark:text-slate-400">{{ t('বর্তমান স্ট্যাটাস:', 'Current Status:') }}</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400">{{ t('অ্যাডমিন রিভিউ চলছে', 'Admin review in progress') }}</span>
                    </div>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Link
                        href="/"
                        class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-xs transition"
                    >
                        {{ t('হোম পেজে ফিরে যান', 'Back to Home') }}
                    </Link>
                    <a
                        :href="registeredData?.tenant_url"
                        target="_blank"
                        class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold text-xs transition shadow-md shadow-emerald-500/20 flex items-center justify-center gap-1.5"
                    >
                        <span>{{ t('শপ স্ট্যাটাস চেক করুন', 'Check Shop Status') }}</span>
                        <ExternalLink class="w-3.5 h-3.5" />
                    </a>
                </div>
            </div>

            <!-- Form Card -->
            <div v-else class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl shadow-slate-200/50 dark:shadow-2xl dark:shadow-black/50 transition-colors">
                <form @submit.prevent="submitRegistration" class="space-y-4 sm:space-y-5 text-sm">
                    <!-- Business Name -->
                    <div>
                        <label class="block font-semibold text-xs text-slate-700 dark:text-slate-300 mb-1.5">
                            {{ t('প্রতিষ্ঠানের নাম (Business / Shop Name) *', 'Business / Shop Name *') }}
                        </label>
                        <input
                            type="text"
                            v-model="form.business_name"
                            required
                            :placeholder="t('যেমন: আল-মদিনা জেনারেল স্টোর', 'e.g. Al-Madina General Store')"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 text-sm transition"
                            @input="generateSlug"
                        />
                    </div>

                    <!-- Subdomain Slug -->
                    <div>
                        <label class="block font-semibold text-xs text-slate-700 dark:text-slate-300 mb-1.5">
                            {{ t('আপনার সফটওয়্যার লিংক (Subdomain URL) *', 'Software Link (Subdomain URL) *') }}
                        </label>
                        <div class="relative flex items-center">
                            <input
                                type="text"
                                v-model="form.subdomain"
                                required
                                placeholder="al-madina"
                                class="w-full pl-4 pr-36 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border focus:ring-1 text-slate-900 dark:text-white font-mono text-xs placeholder-slate-400 dark:placeholder-slate-600 lowercase transition"
                                :class="subdomainStatus.available === true
                                    ? 'border-emerald-500 focus:border-emerald-500 focus:ring-emerald-500'
                                    : subdomainStatus.available === false
                                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                                    : 'border-slate-200 dark:border-slate-800 focus:border-blue-500 focus:ring-blue-500'"
                                @input="checkSubdomainDebounced"
                            />
                            <span class="absolute right-3 text-xs font-mono text-slate-400 dark:text-slate-500 select-none pointer-events-none truncate max-w-[130px]">
                                .{{ resolvedBaseDomain }}
                            </span>
                        </div>
                        <div v-if="subdomainStatus.message" class="text-[11px] mt-1" :class="subdomainStatus.available ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                            {{ subdomainStatus.message }}
                        </div>
                    </div>

                    <!-- Owner Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-xs text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ t('আপনার নাম (Owner Name) *', 'Owner Name *') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.name"
                                required
                                :placeholder="t('মোঃ রফিকুল ইসলাম', 'John Doe')"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 text-sm transition"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-xs text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ t('মোবাইল নম্বর (Phone)', 'Phone Number') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.phone"
                                placeholder="017xxxxxxxx"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 text-sm transition"
                            />
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block font-semibold text-xs text-slate-700 dark:text-slate-300 mb-1.5">
                            {{ t('ইমেইল এড্রেস (Login Email) *', 'Login Email *') }}
                        </label>
                        <input
                            type="email"
                            v-model="form.email"
                            required
                            placeholder="owner@business.com"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 text-sm transition"
                        />
                    </div>

                    <!-- Passwords -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-xs text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ t('পাসওয়ার্ড (Password) *', 'Password *') }}
                            </label>
                            <input
                                type="password"
                                v-model="form.password"
                                required
                                minlength="4"
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 text-sm transition"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-xs text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ t('পাসওয়ার্ড নিশ্চিত করুন *', 'Confirm Password *') }}
                            </label>
                            <input
                                type="password"
                                v-model="form.password_confirmation"
                                required
                                minlength="4"
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 text-sm transition"
                            />
                        </div>
                    </div>

                    <!-- Plan Selection -->
                    <div v-if="plans && plans.length > 0">
                        <label class="block font-semibold text-xs text-slate-700 dark:text-slate-300 mb-2">
                            {{ t('প্ল্যান নির্বাচন করুন', 'Select Plan') }}
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                            <div
                                v-for="p in plans"
                                :key="p.id"
                                @click="form.plan_id = p.id"
                                class="p-3 rounded-xl border text-center cursor-pointer transition select-none"
                                :class="form.plan_id === p.id
                                    ? 'bg-blue-50 dark:bg-blue-600/20 border-blue-500 text-blue-900 dark:text-white ring-1 ring-blue-500'
                                    : 'bg-slate-50 dark:bg-slate-950/60 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700'"
                            >
                                <div class="font-bold text-xs">{{ p.name }}</div>
                                <div class="text-[11px] text-blue-600 dark:text-blue-400 font-semibold mt-0.5">
                                    ৳{{ formatNumber(p.price_monthly) }}/{{ t('মাস', 'mo') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="submitting || subdomainStatus.available === false"
                            class="w-full py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-sm shadow-xl shadow-blue-500/25 transition disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed"
                        >
                            <span v-if="submitting" class="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                            <span>{{ submitting ? t('আপনার স্টোর ডাটাবেস তৈরি হচ্ছে...', 'Creating your store database...') : t('স্টোর তৈরি করুন ও ফ্রি শুরু করুন', 'Create Store & Start Free') }}</span>
                        </button>
                    </div>

                    <div v-if="errorMessage" class="p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 text-xs text-center font-medium">
                        {{ errorMessage }}
                    </div>
                </form>
            </div>

            <!-- Login Link -->
            <div class="text-center mt-6 text-xs text-slate-500 dark:text-slate-400">
                {{ t('ইতোমধ্যে একাউন্ট আছে?', 'Already have an account?') }}
                <Link :href="route('login')" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold ml-1">
                    {{ t('লগইন করুন', 'Log In') }}
                </Link>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import debounce from 'lodash/debounce'
import { Sun, Moon, Clock, ExternalLink } from 'lucide-vue-next'
import { switchTheme } from '@/theme'
import { useLanguage } from '@/composables/useLanguage'

const props = defineProps({
    plans: Array,
    selectedPlan: Object,
    baseDomain: {
        type: String,
        default: 'localhost',
    },
})

// Language Support
const { isBangla, t, setLanguage, formatNumber } = useLanguage()

// Theme Support
const isDark = ref(false)

const updateThemeState = () => {
    if (typeof document !== 'undefined') {
        isDark.value = document.documentElement.classList.contains('dark')
    }
}

const toggleTheme = () => {
    switchTheme()
    updateThemeState()
}

onMounted(() => {
    updateThemeState()
})

const resolvedBaseDomain = computed(() => {
    return props.baseDomain || (typeof window !== 'undefined' ? window.location.hostname : 'localhost')
})

const form = ref({
    business_name: '',
    subdomain: '',
    name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
    plan_id: props.selectedPlan?.id || props.plans?.[0]?.id || '',
})

const submitting = ref(false)
const isRegistered = ref(false)
const registeredData = ref(null)
const errorMessage = ref('')
const subdomainStatus = ref({ available: null, message: '' })

const generateSlug = () => {
    if (!form.value.subdomain) {
        form.value.subdomain = form.value.business_name
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .slice(0, 30)
        checkSubdomainDebounced()
    }
}

const checkSubdomain = async () => {
    if (!form.value.subdomain || form.value.subdomain.length < 3) {
        subdomainStatus.value = { available: null, message: '' }
        return
    }
    try {
        const res = await axios.get(route('tenant.check-subdomain'), { params: { subdomain: form.value.subdomain } })
        subdomainStatus.value = {
            available: res.data.available,
            message: isBangla.value
                ? (res.data.available ? 'সাবডোমেনটি খালি আছে!' : 'এই সাবডোমেনটি ইতোমধ্যে ব্যবহৃত হয়েছে।')
                : res.data.message
        }
    } catch (e) {
        console.error(e)
    }
}

const checkSubdomainDebounced = debounce(checkSubdomain, 400)

const submitRegistration = async () => {
    submitting.value = true
    errorMessage.value = ''
    try {
        const res = await axios.post(route('tenant.register.submit'), form.value)
        if (res.data?.success) {
            registeredData.value = res.data
            isRegistered.value = true
        }
    } catch (e) {
        const msg = e.response?.data?.message
        errorMessage.value = msg || (isBangla.value
            ? 'রেজিস্ট্রেশনে সমস্যা হয়েছে। দয়া করে তথ্যগুলো আবার যাচাই করুন।'
            : 'Registration failed. Please check your information and try again.')
    } finally {
        submitting.value = false
    }
}
</script>
