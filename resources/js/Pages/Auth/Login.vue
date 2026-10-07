<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    Lock,
    Mail,
    Eye,
    EyeOff,
    Sun,
    Moon,
    ArrowRight,
    Store,
    CheckCircle2,
    ShieldCheck
} from 'lucide-vue-next';
import { switchTheme } from '@/theme';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
});

const page = usePage();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const isDark = ref(false);

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});

const toggleTheme = () => {
    switchTheme();
    isDark.value = document.documentElement.classList.contains('dark');
};

const formatErrorMessage = (msg) => {
    if (!msg) return '';
    return msg.replace(
        /(https?:\/\/[^\s]+)/g,
        '<a href="$1" class="block mt-2 font-bold text-emerald-600 dark:text-emerald-400 hover:underline bg-white dark:bg-slate-900 px-3 py-2 rounded-xl border border-emerald-500/40 shadow-sm text-center">👉 এখানে ক্লিক করে আপনার শপে প্রবেশ করুন ($1)</a>'
    );
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head :title="($page.props.business_name || $page.props.platform?.name || 'TrustCash') + ' - লগইন'">
        <link v-if="$page.props.platform?.favicon" rel="icon" :href="$page.props.platform.favicon" />
        <link v-if="$page.props.platform?.favicon" rel="shortcut icon" :href="$page.props.platform.favicon" />
    </Head>

    <div class="min-h-screen w-full flex flex-col justify-between bg-slate-50 dark:bg-slate-950 font-bengali text-slate-800 dark:text-slate-100 transition-colors duration-200">
        <!-- Top Navigation -->
        <header class="w-full max-w-6xl mx-auto px-4 py-4 flex items-center justify-between z-10">
            <Link href="/" class="flex items-center gap-2.5 group">
                <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 flex items-center justify-center p-1.5 shadow-sm shrink-0 overflow-hidden">
                    <img
                        v-if="$page.props.business_logo || $page.props.platform?.logo"
                        :src="$page.props.business_logo || $page.props.platform?.logo"
                        class="w-full h-full object-contain"
                        alt="Logo"
                    />
                    <div v-else class="w-full h-full rounded-lg bg-emerald-600 flex items-center justify-center text-white font-extrabold text-base">
                        {{ ($page.props.business_name || $page.props.platform?.name || 'T')[0] }}
                    </div>
                </div>
                <div>
                    <span class="text-base font-extrabold tracking-tight text-slate-900 dark:text-white">
                        {{ $page.props.business_name || $page.props.platform?.name || 'TrustCash' }}
                    </span>
                    <span class="block text-[10px] text-emerald-600 dark:text-emerald-400 font-medium -mt-1">
                        {{ $page.props.platform?.tagline || 'SaaS POS Platform' }}
                    </span>
                </div>
            </Link>

            <div class="flex items-center gap-2">
                <!-- Theme Switcher Button -->
                <button
                    type="button"
                    @click="toggleTheme"
                    class="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-600 dark:text-slate-300 transition shadow-sm"
                    :title="isDark ? 'লাইট থিমে পরিবর্তন করুন' : 'ডার্ক থিমে পরিবর্তন করুন'"
                >
                    <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                    <Moon v-else class="w-4 h-4 text-slate-600" />
                </button>
            </div>
        </header>

        <!-- Main Card Section -->
        <main class="w-full max-w-md mx-auto px-4 my-auto py-6 z-10">
            <div class="bg-white dark:bg-slate-900/90 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none p-6 sm:p-8 space-y-6">
                <!-- Header Text -->
                <div class="text-center space-y-1">
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                        স্বাগতম! লগইন করুন
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $page.props.business_name || 'TrustCash' }} POS & ইনভেন্টরি ম্যানেজমেন্ট
                    </p>
                </div>

                <!-- Status Flash Message -->
                <div v-if="status" class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs flex items-center gap-2">
                    <CheckCircle2 class="w-4 h-4 shrink-0" />
                    <span>{{ status }}</span>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-4 text-xs">
                    <!-- Email or Username Field -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1.5" for="email">
                            ইমেইল বা ইউজারনেম (Email or Username) *
                        </label>
                        <div class="relative">
                            <Mail class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                id="email"
                                type="text"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="name@store.com বা username"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-slate-900 dark:text-white placeholder-slate-400 text-xs transition"
                            />
                        </div>
                        <div v-if="form.errors.email" class="text-rose-600 dark:text-rose-400 text-[11px] mt-1.5 font-medium leading-relaxed p-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20">
                            <div v-html="formatErrorMessage(form.errors.email)"></div>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="font-semibold text-slate-700 dark:text-slate-300" for="password">
                                পাসওয়ার্ড (Password - কমপক্ষে ৪ সংখ্যা) *
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-[11px] text-emerald-600 dark:text-emerald-400 hover:underline"
                            >
                                পাসওয়ার্ড ভুলে গেছেন?
                            </Link>
                        </div>
                        <div class="relative">
                            <Lock class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                minlength="4"
                                autocomplete="current-password"
                                placeholder="কমপক্ষে ৪ ডিজিটের পাসওয়ার্ড"
                                class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-slate-900 dark:text-white placeholder-slate-400 text-xs transition"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1"
                            >
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <div v-if="form.errors.password" class="text-rose-500 text-[11px] mt-1 font-medium">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="rounded bg-slate-100 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-emerald-500 focus:ring-0"
                            />
                            <span class="text-slate-600 dark:text-slate-400 text-xs">লগইন মনে রাখুন (Remember me)</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full mt-2 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold text-xs shadow-lg shadow-emerald-500/20 flex items-center justify-center gap-2 transition disabled:opacity-50"
                    >
                        <span>{{ form.processing ? 'লগইন হচ্ছে...' : 'লগইন করুন' }}</span>
                        <ArrowRight class="w-4 h-4" />
                    </button>
                </form>

                <!-- Help / Support -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800/80 text-center">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        নতুন শপ তৈরি করতে চান?
                        <Link href="/register-business" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline ml-1">
                            রেজিস্টার করুন
                        </Link>
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-6xl mx-auto py-3 text-center text-[11px] text-slate-400 dark:text-slate-500 z-10">
            © {{ new Date().getFullYear() }} {{ $page.props.business_name || $page.props.app_name || 'TrustCash' }}. সর্বস্বত্ব সংরক্ষিত।
        </footer>
    </div>
</template>
