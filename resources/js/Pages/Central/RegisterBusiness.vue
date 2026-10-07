<template>
    <Head :title="($page.props.platform?.name || 'TrustCash') + ' - ব্যবসা রেজিস্ট্রেশন'">
        <link v-if="$page.props.platform?.favicon" rel="icon" :href="$page.props.platform.favicon" />
        <link v-if="$page.props.platform?.favicon" rel="shortcut icon" :href="$page.props.platform.favicon" />
    </Head>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between p-6">
        <div class="max-w-xl w-full mx-auto my-auto pt-6 pb-12">
            <!-- Logo Header -->
            <div class="text-center mb-8">
                <Link href="/" class="inline-flex items-center gap-3 mb-4 group">
                    <div class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-900 border border-slate-800 flex items-center justify-center p-2 shadow-lg overflow-hidden transition-transform group-hover:scale-105">
                        <img
                            v-if="$page.props.platform?.logo"
                            :src="$page.props.platform.logo"
                            :alt="$page.props.platform?.name || 'Logo'"
                            class="w-full h-full object-contain"
                        />
                        <div v-else class="w-full h-full rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-black text-white text-xl shadow-lg shadow-blue-500/30">
                            {{ ($page.props.platform?.name || 'T')[0] }}
                        </div>
                    </div>
                    <span class="text-2xl font-bold text-white tracking-tight">{{ $page.props.platform?.name || 'TrustCash' }}</span>
                </Link>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white">আপনার ব্যবসার একাউন্ট তৈরি করুন</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-2">১৪ দিনের ফ্রি ট্রায়াল শুরু করুন। কোনো ক্রেডিট কার্ডের প্রয়োজন নেই।</p>
            </div>

            <!-- Pending Verification Success Card -->
            <div v-if="isRegistered" class="bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-2xl shadow-black/50 text-center space-y-6 animate-in fade-in zoom-in duration-300">
                <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 mx-auto flex items-center justify-center shadow-lg shadow-amber-500/10">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div class="space-y-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        অনুমোদনের অপেক্ষায় (Pending Approval)
                    </span>
                    <h2 class="text-2xl font-bold text-white pt-2">অভিনন্দন! রেজিস্ট্রেশন সম্পন্ন হয়েছে</h2>
                    <p class="text-xs text-slate-400 leading-relaxed max-w-md mx-auto">
                        {{ registeredData?.message || 'আপনার শপের ডাটাবেজ প্রস্তুত হয়েছে। সিকিউরিটি ভেরিফিকেশনের জন্য অ্যাকাউন্টটি বর্তমানে অ্যাডমিন অনুমোদনের অপেক্ষায় রয়েছে।' }}
                    </p>
                </div>

                <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 text-left text-xs space-y-2.5 max-w-md mx-auto">
                    <div class="flex justify-between">
                        <span class="text-slate-500">প্রতিষ্ঠানের নাম:</span>
                        <span class="font-bold text-white">{{ form.business_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">আপনার ডোমেইন:</span>
                        <span class="font-mono text-emerald-400">{{ registeredData?.subdomain || form.subdomain + '.localhost' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">বর্তমান স্ট্যাটাস:</span>
                        <span class="font-bold text-amber-400">অ্যাডমিন রিভিউ চলছে</span>
                    </div>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Link
                        href="/"
                        class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs transition"
                    >
                        হোম পেজে ফিরে যান
                    </Link>
                    <a
                        :href="registeredData?.tenant_url"
                        target="_blank"
                        class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold text-xs transition shadow-md shadow-emerald-500/20 flex items-center justify-center gap-1.5"
                    >
                        <span>শপ স্ট্যাটাস চেক করুন</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a>
                </div>
            </div>

            <!-- Form Card -->
            <div v-else class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-2xl shadow-black/50">
                <form @submit.prevent="submitRegistration" class="space-y-5 text-sm">
                    <!-- Business Name -->
                    <div>
                        <label class="block font-semibold text-xs text-slate-300 mb-1.5">প্রতিষ্ঠানের নাম (Business / Shop Name) *</label>
                        <input
                            type="text"
                            v-model="form.business_name"
                            required
                            placeholder="যেমন: আল-মদিনা জেনারেল স্টোর"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-white placeholder-slate-600 text-sm"
                            @input="generateSlug"
                        />
                    </div>

                    <!-- Subdomain Slug -->
                    <div>
                        <label class="block font-semibold text-xs text-slate-300 mb-1.5">আপনার সফটওয়্যার লিংক (Subdomain URL) *</label>
                        <div class="relative flex items-center">
                            <input
                                type="text"
                                v-model="form.subdomain"
                                required
                                placeholder="al-madina"
                                class="w-full pl-4 pr-32 py-2.5 rounded-xl bg-slate-950 border focus:ring-1 text-white font-mono text-xs placeholder-slate-600 lowercase"
                                :class="subdomainStatus.available === true
                                    ? 'border-emerald-500 focus:border-emerald-500 focus:ring-emerald-500'
                                    : subdomainStatus.available === false
                                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                                    : 'border-slate-800 focus:border-blue-500 focus:ring-blue-500'"
                                @input="checkSubdomainDebounced"
                            />
                            <span class="absolute right-3 text-xs font-mono text-slate-500 select-none pointer-events-none">.localhost</span>
                        </div>
                        <div v-if="subdomainStatus.message" class="text-[11px] mt-1" :class="subdomainStatus.available ? 'text-emerald-400' : 'text-red-400'">
                            {{ subdomainStatus.message }}
                        </div>
                    </div>

                    <!-- Owner Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-xs text-slate-300 mb-1.5">আপনার নাম (Owner Name) *</label>
                            <input
                                type="text"
                                v-model="form.name"
                                required
                                placeholder="মোঃ রফিকুল ইসলাম"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-white placeholder-slate-600 text-sm"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-xs text-slate-300 mb-1.5">মোবাইল নম্বর (Phone)</label>
                            <input
                                type="text"
                                v-model="form.phone"
                                placeholder="017xxxxxxxx"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-white placeholder-slate-600 text-sm"
                            />
                        </div>
                    </div>

                    <!-- Email & Password -->
                    <div>
                        <label class="block font-semibold text-xs text-slate-300 mb-1.5">ইমেইল এড্রেস (Login Email) *</label>
                        <input
                            type="email"
                            v-model="form.email"
                            required
                            placeholder="owner@business.com"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-white placeholder-slate-600 text-sm"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-xs text-slate-300 mb-1.5">পাসওয়ার্ড (Password) *</label>
                            <input
                                type="password"
                                v-model="form.password"
                                required
                                minlength="4"
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-white placeholder-slate-600 text-sm"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-xs text-slate-300 mb-1.5">পাসওয়ার্ড নিশ্চিত করুন *</label>
                            <input
                                type="password"
                                v-model="form.password_confirmation"
                                required
                                minlength="4"
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-white placeholder-slate-600 text-sm"
                            />
                        </div>
                    </div>

                    <!-- Plan Selection -->
                    <div>
                        <label class="block font-semibold text-xs text-slate-300 mb-2">প্ল্যান নির্বাচন করুন</label>
                        <div class="grid grid-cols-3 gap-2.5">
                            <div
                                v-for="p in plans"
                                :key="p.id"
                                @click="form.plan_id = p.id"
                                class="p-3 rounded-xl border text-center cursor-pointer transition select-none"
                                :class="form.plan_id === p.id
                                    ? 'bg-blue-600/20 border-blue-500 text-white'
                                    : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'"
                            >
                                <div class="font-bold text-xs">{{ p.name }}</div>
                                <div class="text-[11px] text-blue-400 font-semibold mt-0.5">৳{{ p.price_monthly }}/মাস</div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="submitting || subdomainStatus.available === false"
                            class="w-full py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-sm shadow-xl shadow-blue-500/25 transition disabled:opacity-50 flex items-center justify-center gap-2"
                        >
                            <span v-if="submitting" class="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                            <span>{{ submitting ? 'আপনার স্টোর ডাটাবেস তৈরি হচ্ছে...' : 'স্টোর তৈরি করুন ও ফ্রি শুরু করুন' }}</span>
                        </button>
                    </div>

                    <div v-if="errorMessage" class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-xs text-center">
                        {{ errorMessage }}
                    </div>
                </form>
            </div>

            <!-- Login Link -->
            <div class="text-center mt-6 text-xs text-slate-500">
                ইতোমধ্যে একাউন্ট আছে?
                <Link :href="route('login')" class="text-blue-400 hover:underline font-semibold ml-1">লগইন করুন</Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import debounce from 'lodash/debounce'

const props = defineProps({
    plans: Array,
    selectedPlan: Object,
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
        subdomainStatus.value = res.data
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
        errorMessage.value = e.response?.data?.message || 'রেজিস্ট্রেশনে সমস্যা হয়েছে। দয়া করে তথ্যগুলো আবার যাচাই করুন।'
    } finally {
        submitting.value = false
    }
}
</script>
