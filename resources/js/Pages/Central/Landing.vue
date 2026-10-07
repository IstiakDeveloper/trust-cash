<template>
    <Head :title="t(($page.props.platform?.name || 'TrustCash') + ' - রিটেইল ও পিওএস সফটওয়্যার', ($page.props.platform?.name || 'TrustCash') + ' - Modern Retail POS & ERP')">
        <link v-if="$page.props.platform?.favicon" rel="icon" :href="$page.props.platform.favicon" />
    </Head>

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans transition-colors duration-200 selection:bg-indigo-600 selection:text-white">
        <!-- 1. Top Navigation Bar -->
        <header class="sticky top-0 z-50 backdrop-blur-xl bg-white/85 dark:bg-slate-950/85 border-b border-slate-200/80 dark:border-slate-800/80 transition-colors">
            <div class="container mx-auto px-4 sm:px-6 h-20 flex items-center justify-between gap-4">
                <!-- Brand / Logo -->
                <Link href="/" class="flex items-center gap-3 shrink-0">
                    <img
                        v-if="$page.props.platform?.logo"
                        :src="$page.props.platform.logo"
                        :alt="$page.props.platform?.name || 'Logo'"
                        class="h-10 w-auto max-w-[160px] object-contain"
                    />
                    <template v-else>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center font-black text-white text-xl shadow-lg shadow-emerald-500/25">
                            {{ ($page.props.platform?.name || 'T').substring(0, 1) }}
                        </div>
                        <div>
                            <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-slate-900 to-indigo-900 dark:from-white dark:to-slate-300 bg-clip-text text-transparent">
                                {{ $page.props.platform?.name || 'TrustCash' }}
                            </span>
                            <span class="hidden sm:inline-block ml-2 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                ERP Cloud
                            </span>
                        </div>
                    </template>
                </Link>

                <!-- Center Nav Links -->
                <nav class="hidden lg:flex items-center gap-7 text-sm font-semibold text-slate-600 dark:text-slate-300">
                    <a href="#features" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        {{ t('ফিচারসমূহ', 'Features') }}
                    </a>
                    <a href="#solutions" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        {{ t('ব্যবহার ক্ষেত্র', 'Solutions') }}
                    </a>
                    <a href="#pricing" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        {{ t('প্যাকেজ ও মূল্য', 'Pricing Plans') }}
                    </a>
                    <a href="#faq" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        {{ t('প্রশ্নোত্তর', 'FAQ') }}
                    </a>
                </nav>

                <!-- Controls & Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Language Switcher Pill -->
                    <div class="flex items-center p-0.5 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold">
                        <button
                            type="button"
                            @click="setLanguage('bn')"
                            :class="[
                                'px-2 py-1 rounded-lg transition-all',
                                isBangla ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'
                            ]"
                            title="বাংলা ভাষা"
                        >
                            বাং
                        </button>
                        <button
                            type="button"
                            @click="setLanguage('en')"
                            :class="[
                                'px-2 py-1 rounded-lg transition-all',
                                !isBangla ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'
                            ]"
                            title="English Language"
                        >
                            EN
                        </button>
                    </div>

                    <!-- Theme Switcher (Dark / Light) -->
                    <button
                        type="button"
                        @click="handleThemeSwitch"
                        class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 transition-colors"
                        :title="t('থিম পরিবর্তন করুন', 'Toggle Light/Dark Theme')"
                    >
                        <i v-if="isDark" class="fas fa-sun text-amber-400 text-sm"></i>
                        <i v-else class="fas fa-moon text-slate-700 text-sm"></i>
                    </button>

                    <!-- Login Link -->
                    <Link
                        :href="route('login')"
                        class="text-xs sm:text-sm font-bold text-slate-700 hover:text-indigo-600 dark:text-slate-300 dark:hover:text-white px-2.5 py-1.5 transition-colors"
                    >
                        {{ t('লগইন', 'Log In') }}
                    </Link>

                    <!-- Main Register CTA -->
                    <Link
                        :href="$page.props.platform?.nav_cta_url || route('tenant.register')"
                        class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-emerald-600/20 transition transform hover:-translate-y-0.5 active:translate-y-0 flex items-center gap-1.5"
                    >
                        <span>{{ t('ফ্রি ট্রায়াল', 'Start Free') }}</span>
                        <i class="fas fa-arrow-right text-[10px] hidden sm:inline-block"></i>
                    </Link>
                </div>
            </div>
        </header>

        <!-- 2. Hero Section -->
        <section class="relative pt-12 sm:pt-20 pb-20 sm:pb-28 overflow-hidden">
            <!-- Decorative Glow background -->
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] sm:w-[750px] h-[550px] bg-indigo-500/10 dark:bg-indigo-600/15 rounded-full blur-[140px] pointer-events-none"></div>
            <div class="absolute top-1/3 left-1/4 w-[350px] h-[350px] bg-emerald-500/10 dark:bg-emerald-600/10 rounded-full blur-[120px] pointer-events-none"></div>

            <div class="container mx-auto px-4 sm:px-6 relative z-10 text-center max-w-4xl">
                <!-- Highlight Pill -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-950/70 border border-indigo-200/80 dark:border-indigo-800/80 text-indigo-700 dark:text-indigo-300 text-xs font-bold mb-6 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>{{ t('সকল ব্যবসার বিশ্বস্ত পিওএস ও স্মার্ট হিসাব খাতা', 'Smart Retail POS & Financial Accounting Platform') }}</span>
                </div>

                <!-- Main Punchy Heading -->
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight sm:leading-none text-slate-900 dark:text-white mb-6">
                    {{ t('আপনার ব্যবসার হিসাব ও বেচাকেনা', 'Run Your Entire Retail Business') }} <br class="hidden sm:inline" />
                    <span class="bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 dark:from-emerald-400 dark:via-teal-300 dark:to-indigo-400 bg-clip-text text-transparent">
                        {{ t('এখন হবে ১০০% নির্ভুল ও সহজ', 'With 100% Speed & Accuracy') }}
                    </span>
                </h1>

                <!-- Subheading -->
                <p class="text-sm sm:text-lg text-slate-600 dark:text-slate-300 mb-9 max-w-2xl mx-auto leading-relaxed font-normal">
                    {{ t(
                        'সুপারশপ, পাইকারি আড়ত, ফার্মেসি, ইলেকট্রনিক্স বা ফ্যাশন শপ — দ্রুত বারকোড বিলিং, রিয়েলটাইম স্টক ইনভেন্টরি, কাস্টমার বাকি খাতা ও ব্যালেন্স শীট এক ছাতার নিচে।',
                        'Ultra-fast barcode POS, multi-branch stock inventory, customer dues & automated financial statements — all in one unified, cloud-ready platform.'
                    ) }}
                </p>

                <!-- Hero Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
                    <Link
                        :href="route('tenant.register')"
                        class="w-full sm:w-auto px-7 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm sm:text-base shadow-xl shadow-emerald-600/25 transition transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-rocket text-xs"></i>
                        <span>{{ t('১৪ দিন ফ্রি ট্রায়াল শুরু করুন', 'Start 14-Day Free Trial') }}</span>
                    </Link>
                    <a
                        href="#pricing"
                        class="w-full sm:w-auto px-7 py-3.5 rounded-xl bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-850 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 font-bold text-sm sm:text-base transition flex items-center justify-center gap-2 shadow-xs"
                    >
                        <i class="fas fa-tag text-xs text-indigo-500"></i>
                        <span>{{ t('প্যাকেজ ও মূল্য তালিকা', 'View Pricing Plans') }}</span>
                    </a>
                    <button
                        type="button"
                        @click="triggerPwaPrompt"
                        class="w-full sm:w-auto px-5 py-3.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-download text-xs"></i>
                        <span>{{ t('অ্যাপ ইনস্টল (PWA)', 'Install App') }}</span>
                    </button>
                </div>

                <!-- Trust Micro-Badges -->
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4 text-xs font-medium text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1.5">
                        <i class="fas fa-check-circle text-emerald-500 text-xs"></i>
                        {{ t('কোনো ক্রেডিট কার্ডের প্রয়োজন নেই', 'No credit card required') }}
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1.5">
                        <i class="fas fa-shield-alt text-indigo-500 text-xs"></i>
                        {{ t('শতভাগ ডাটা সুরক্ষা ও ব্যাকআপ', '100% secure automated backups') }}
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1.5">
                        <i class="fas fa-wifi-slash text-amber-500 text-xs"></i>
                        {{ t('অফলাইন ক্যাশ কাউন্টার রেডি', 'Offline POS mode included') }}
                    </span>
                </div>

                <!-- Interactive High-Tech Live Dashboard Preview Mockup -->
                <div class="mt-12 sm:mt-16 rounded-2xl sm:rounded-3xl p-2 sm:p-3 bg-gradient-to-b from-slate-200 via-slate-100 to-slate-200/40 dark:from-slate-800 dark:via-slate-900 dark:to-slate-950 border border-slate-200/80 dark:border-slate-800 shadow-2xl">
                    <div class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-slate-100 dark:border-slate-800/80 text-left">
                        <!-- Top mockup bar -->
                        <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-rose-400"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                                <span class="ml-2 text-xs font-mono font-bold text-slate-400">TrustCash ERP Terminal</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold text-[10px] flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                    {{ t('সরাসরি সংযুক্ত', 'Live Connected') }}
                                </span>
                            </div>
                        </div>

                        <!-- Mockup Stats Cards -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-100 dark:border-slate-750">
                                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block mb-1">{{ t('আজকের বিক্রি (Sales)', 'Today\'s Sales') }}</span>
                                <span class="text-base sm:text-xl font-black text-slate-900 dark:text-white font-mono">৳৪৮,৫২০.০০</span>
                            </div>
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-100 dark:border-slate-750">
                                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block mb-1">{{ t('আজকের ক্যাশ আদায় (Cash)', 'Cash Collected') }}</span>
                                <span class="text-base sm:text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono">৳৪১,৩০০.০০</span>
                            </div>
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-100 dark:border-slate-750">
                                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block mb-1">{{ t('মোট চালান / মেমো', 'Invoices Completed') }}</span>
                                <span class="text-base sm:text-xl font-black text-indigo-600 dark:text-indigo-400 font-mono">৭৪ টি</span>
                            </div>
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-100 dark:border-slate-750">
                                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block mb-1">{{ t('স্টক অ্যালার্ট (Low Stock)', 'Low Stock Items') }}</span>
                                <span class="text-base sm:text-xl font-black text-amber-500 font-mono">৩ টি</span>
                            </div>
                        </div>

                        <!-- POS Simulation Row -->
                        <div class="p-3.5 rounded-xl bg-slate-100/70 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-700/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold">
                                    <i class="fas fa-barcode"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 dark:text-slate-200">{{ t('১ সেকেন্ডে বারকোড স্ক্যান ও ইনস্ট্যান্ট প্রিন্ট', '1-Second Barcode Scan & Instant Thermal Receipt') }}</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('ক্যাশ ড্রয়ার অটো ওপেন এবং অফলাইনেও অবিরাম বিক্রি', 'Auto cash drawer trigger & seamless offline sync') }}</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-mono font-bold text-[11px] shrink-0">
                                {{ t('সুপারফাস্ট POS', 'Ultra-fast POS') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Core Value Pillars (Minimal & Punchy) -->
        <section id="features" class="py-16 sm:py-24 bg-white dark:bg-slate-900 border-y border-slate-200 dark:border-slate-800 transition-colors">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                        {{ t('মূল বৈশিষ্ট্যসমূহ', 'Core Capabilities') }}
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 dark:text-white mt-2 tracking-tight">
                        {{ t('ব্যবসা পরিচালনার সব টুলস এক জায়গায়', 'Everything Needed to Scale Your Retail Business') }}
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-2.5">
                        {{ t('কোনো অপ্রয়োজনীয় জটিলতা ছাড়াই সহজ ইন্টারফেসে কাজ করুন।', 'High-performance architecture built for zero downtime and effortless operations.') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Feature 1: POS -->
                    <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 hover:shadow-lg transition-all">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl mb-4">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">
                            {{ t('সুপারফাস্ট POS কাউন্টার', 'Lightning Fast POS') }}
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                            {{ t('ক্যাশিয়ারদের জন্য কিবোর্ড শর্টকাট (F1-F8), টাচস্ক্রিন সাপোর্ট ও ইনস্ট্যান্ট থার্মাল রসিদ প্রিন্টিং।', 'Instant thermal receipts, barcode scanner integration, cash drawer support, and full offline caching.') }}
                        </p>
                    </div>

                    <!-- Feature 2: Inventory -->
                    <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 hover:shadow-lg transition-all">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl mb-4">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">
                            {{ t('রিয়েলটাইম স্টক ইনভেন্টরি', 'Real-time Stock Control') }}
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                            {{ t('পণ্য শেষ হওয়ার আগেই অটোমেটিক স্টক অ্যালার্ট, বারকোড স্টিকার জেনারেশন ও মাল্টি-শাখা ট্রান্সফার।', 'Auto low-stock alerts, multi-branch transfers, SKU/barcode generation, and exact purchase valuation.') }}
                        </p>
                    </div>

                    <!-- Feature 3: Due & Ledger -->
                    <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 hover:shadow-lg transition-all">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-950/80 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl mb-4">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">
                            {{ t('স্বচ্ছ বাকি খাতা ও লেজার', 'Customer & Supplier Ledger') }}
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                            {{ t('কাস্টমার ও সরবরাহকারীদের বাকি-বকেয়া, ক্রেডিট লিমিট সতর্কতা এবং এক ক্লিকে সম্পূর্ণ স্টেটমেন্ট।', 'Track customer receivables, credit limits, supplier payables, and historical statement exports.') }}
                        </p>
                    </div>

                    <!-- Feature 4: Balance Sheet -->
                    <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 hover:shadow-lg transition-all">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl mb-4">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">
                            {{ t('ব্যালেন্স শীট ও লাভ-ক্ষতি', 'Balance Sheet & Reports') }}
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                            {{ t('ডাবল-এন্ট্রি নির্ভুল ব্যালেন্স শীট, আয়-ব্যয় হিসাব, ব্যাংক রিকনসিলিয়েশন ও পিডিএফ রিপোর্ট।', 'Automated double-entry accounting balance sheet, daily profit/loss, bank accounts, and PDF downloads.') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. Dynamic Packages & Pricing Section -->
        <section id="pricing" class="py-16 sm:py-24 bg-slate-50 dark:bg-slate-950 transition-colors">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                        {{ t('সহজ ও সাশ্রয়ী প্যাকেজ', 'Dynamic Pricing Plans') }}
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 dark:text-white mt-2 tracking-tight">
                        {{ t('আপনার ব্যবসার সাইজ অনুযায়ী সেরা প্ল্যান', 'Pick the Perfect Plan for Your Business') }}
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-2.5">
                        {{ t('কোনো লুকানো চার্জ নেই। ১৪ দিন সম্পূর্ণ ফ্রি ট্রায়াল। যেকোনো সময় আপগ্রেডযোগ্য।', 'No hidden charges. 14-day free trial on all plans. Switch or cancel at any time.') }}
                    </p>

                    <!-- Billing Cycle Toggle (Monthly / Yearly) -->
                    <div class="inline-flex items-center gap-2 p-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 mt-7 text-xs font-bold shadow-xs">
                        <button
                            type="button"
                            @click="yearly = false"
                            :class="[
                                'px-4 py-2 rounded-full transition-all',
                                !yearly ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                            ]"
                        >
                            {{ t('মাসিক বিলিং', 'Monthly Billing') }}
                        </button>
                        <button
                            type="button"
                            @click="yearly = true"
                            :class="[
                                'px-4 py-2 rounded-full transition-all flex items-center gap-1.5',
                                yearly ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                            ]"
                        >
                            <span>{{ t('বার্ষিক বিলিং', 'Yearly Billing') }}</span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-400 text-slate-950 font-black text-[10px]">
                                {{ t('২ মাস ফ্রি!', '2 Mos Free!') }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Dynamic Plan Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
                    <div
                        v-for="plan in displayPlans"
                        :key="plan.id"
                        :class="[
                            'rounded-3xl p-6 sm:p-8 flex flex-col justify-between transition-all duration-200 relative',
                            isPopular(plan)
                                ? 'bg-white dark:bg-slate-900 border-2 border-emerald-500 shadow-2xl shadow-emerald-500/15 dark:shadow-emerald-500/20'
                                : 'bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 shadow-sm'
                        ]"
                    >
                        <!-- Most popular badge -->
                        <div
                            v-if="isPopular(plan)"
                            class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-emerald-600 text-white text-[11px] font-black uppercase tracking-wider shadow-md"
                        >
                            {{ t('সবচেয়ে জনপ্রিয় (Popular)', 'Most Popular') }}
                        </div>

                        <div>
                            <!-- Plan Title & Description -->
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ plan.name }}</h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                    {{ plan.slug }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 min-h-[34px] leading-relaxed">
                                {{ plan.description }}
                            </p>

                            <!-- Price Display -->
                            <div class="mt-6 mb-7 pt-5 border-t border-slate-100 dark:border-slate-800">
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white font-mono">
                                        ৳{{ formatNumber(yearly ? plan.price_yearly : plan.price_monthly) }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                        / {{ yearly ? t('বছর', 'year') : t('মাস', 'month') }}
                                    </span>
                                </div>
                                <p v-if="yearly && plan.price_yearly > 0" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1">
                                    {{ t(`প্রতি মাসে মাত্র ৳${formatNumber(plan.price_yearly / 12)} সমমূল্য`, `Equivalent to only ৳${formatNumber(plan.price_yearly / 12)}/mo`) }}
                                </p>
                            </div>

                            <!-- Plan Limits -->
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 mb-6 space-y-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                        <i class="fas fa-users text-xs text-indigo-500"></i>
                                        {{ t('ব্যবহারকারী (Users):', 'Max Users:') }}
                                    </span>
                                    <span class="font-bold text-slate-900 dark:text-white">
                                        {{ plan.max_users >= 999 ? t('আনলিমিটেড', 'Unlimited') : t(plan.max_users + ' জন', plan.max_users + ' Users') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                        <i class="fas fa-boxes text-xs text-emerald-500"></i>
                                        {{ t('সর্বোচ্চ পণ্য (Products):', 'Max Products:') }}
                                    </span>
                                    <span class="font-bold text-slate-900 dark:text-white">
                                        {{ plan.max_products >= 9999 ? t('আনলিমিটেড', 'Unlimited') : t(formatNumber(plan.max_products) + ' টি', formatNumber(plan.max_products) + ' Items') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                        <i class="fas fa-store text-xs text-amber-500"></i>
                                        {{ t('শাখা / আউটলেট (Branches):', 'Branches:') }}
                                    </span>
                                    <span class="font-bold text-slate-900 dark:text-white">
                                        {{ t(plan.max_branches + ' টি', plan.max_branches + ' Outlet(s)') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Plan Features Checklist -->
                            <div class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300">
                                <div
                                    v-for="(feature, idx) in getPlanFeatureList(plan)"
                                    :key="idx"
                                    class="flex items-start gap-2.5"
                                >
                                    <i class="fas fa-check-circle text-emerald-500 text-xs mt-0.5 shrink-0"></i>
                                    <span class="leading-tight">{{ feature }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- CTA Register Button -->
                        <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
                            <Link
                                :href="route('tenant.register', { plan: plan.slug })"
                                :class="[
                                    'w-full py-3 px-4 rounded-xl font-bold text-center block text-xs sm:text-sm transition shadow-sm',
                                    isPopular(plan)
                                        ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/20'
                                        : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200'
                                ]"
                            >
                                {{ t('১৪ দিন ফ্রি ট্রায়াল শুরু করুন', 'Start 14-Day Free Trial') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. Business Types / Solutions Section -->
        <section id="solutions" class="py-16 sm:py-20 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 transition-colors">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                        {{ t('ব্যবহার ক্ষেত্র', 'Business Solutions') }}
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-1.5">
                        {{ t('যেসব ব্যবসার জন্য TrustCash উপযুক্ত', 'Tailored for Every Retail & Wholesale Business') }}
                    </h2>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 max-w-5xl mx-auto">
                    <div
                        v-for="(biz, i) in businessTypes"
                        :key="i"
                        class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 text-center hover:border-emerald-500 transition-colors group"
                    >
                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center text-lg mb-2 shadow-xs group-hover:scale-110 transition-transform">
                            <i :class="biz.icon"></i>
                        </div>
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ t(biz.nameBn, biz.nameEn) }}</h4>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. FAQ Accordion Section -->
        <section id="faq" class="py-16 sm:py-24 bg-slate-50 dark:bg-slate-950 transition-colors">
            <div class="container mx-auto px-4 sm:px-6 max-w-3xl">
                <div class="text-center mb-12">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                        {{ t('সাধারণ জিজ্ঞাসা', 'Frequently Asked Questions') }}
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-1.5">
                        {{ t('সচরাচর জানতে চাওয়া প্রশ্নসমূহ', 'Got Questions? We Have Answers') }}
                    </h2>
                </div>

                <div class="space-y-3.5">
                    <div
                        v-for="(faq, idx) in faqList"
                        :key="idx"
                        class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs"
                    >
                        <button
                            type="button"
                            @click="toggleFaq(idx)"
                            class="w-full p-4 sm:p-5 text-left flex items-center justify-between gap-4 font-bold text-xs sm:text-sm text-slate-900 dark:text-white"
                        >
                            <span>{{ t(faq.qBn, faq.qEn) }}</span>
                            <i
                                :class="[
                                    'fas fa-chevron-down text-xs transition-transform duration-200 text-slate-400',
                                    activeFaq === idx ? 'rotate-180 text-emerald-500' : ''
                                ]"
                            ></i>
                        </button>
                        <div
                            v-show="activeFaq === idx"
                            class="px-4 sm:px-5 pb-5 text-xs text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-100 dark:border-slate-800/80 pt-3"
                        >
                            {{ t(faq.aBn, faq.aEn) }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. Minimal Modern Footer -->
        <footer class="py-12 bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 transition-colors">
            <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6 pb-8 border-b border-slate-100 dark:border-slate-900 text-xs">
                    <div class="flex items-center gap-3">
                        <img
                            v-if="$page.props.platform?.logo"
                            :src="$page.props.platform.logo"
                            :alt="$page.props.platform?.name || 'Logo'"
                            class="h-8 w-auto object-contain"
                        />
                        <span class="font-bold text-sm text-slate-900 dark:text-white">
                            {{ $page.props.platform?.name || 'TrustCash' }}
                        </span>
                        <span class="text-slate-400">| {{ t('আধুনিক ক্লাউড পিওএস ও একাউন্টিং', 'Modern Cloud POS & ERP') }}</span>
                    </div>

                    <div class="flex items-center gap-6 font-semibold text-slate-500 dark:text-slate-400">
                        <Link :href="route('privacy')" class="hover:text-indigo-600 dark:hover:text-white transition">
                            {{ t('প্রাইভেসি পলিসি', 'Privacy Policy') }}
                        </Link>
                        <Link :href="route('terms')" class="hover:text-indigo-600 dark:hover:text-white transition">
                            {{ t('ব্যবহারের শর্তাবলি', 'Terms') }}
                        </Link>
                        <Link :href="route('contact')" class="hover:text-indigo-600 dark:hover:text-white transition">
                            {{ t('যোগাযোগ', 'Contact') }}
                        </Link>
                    </div>
                </div>

                <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-slate-500 dark:text-slate-400">
                    <p>© 2026 {{ $page.props.platform?.name || 'TrustCash' }}. {{ t('সর্বস্বত্ব সংরক্ষিত।', 'All rights reserved.') }}</p>
                    <p class="flex items-center gap-1.5">
                        <i class="fas fa-shield-halved text-emerald-500"></i>
                        <span>{{ t('সুরক্ষিত ক্লাউড মাল্টি-টেন্যান্সি ও ব্যাকআপ', 'Enterprise Cloud Multi-tenancy & Encrypted Storage') }}</span>
                    </p>
                </div>
            </div>
        </footer>

        <!-- Floating WhatsApp Widget if configured -->
        <a
            v-if="$page.props.platform?.whatsapp"
            :href="`https://wa.me/${$page.props.platform.whatsapp.replace(/[^0-9]/g, '')}?text=${encodeURIComponent('Hello ' + ($page.props.platform?.name || 'TrustCash') + ', I would like to get a live demo of your software.')}`"
            target="_blank"
            rel="noopener noreferrer"
            class="fixed bottom-6 right-6 z-40 flex items-center gap-2 px-3.5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full font-bold text-xs shadow-xl shadow-emerald-500/30 transition transform hover:scale-105 active:scale-95 group"
            title="Chat with us on WhatsApp"
        >
            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
            <span class="font-medium hidden sm:inline">{{ t('হোয়াটসঅ্যাপে চ্যাট করুন', 'Chat on WhatsApp') }}</span>
            <i class="fab fa-whatsapp text-base"></i>
        </a>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { useLanguage } from '@/composables/useLanguage'
import { switchTheme } from '@/theme'

const props = defineProps({
    plans: Array,
})

// Bilingual composable
const { currentLang, isBangla, t, setLanguage, formatNumber } = useLanguage()

// Theme State
const isDark = ref(true)

const updateThemeState = () => {
    if (typeof document !== 'undefined') {
        isDark.value = document.documentElement.classList.contains('dark')
    }
}

const handleThemeSwitch = () => {
    switchTheme()
    updateThemeState()
}

// Billing Cycle state
const yearly = ref(false)

// Active FAQ toggle
const activeFaq = ref(0)
const toggleFaq = (idx) => {
    activeFaq.value = activeFaq.value === idx ? null : idx
}

// Fallback plans if DB has no plans yet
const fallbackPlans = [
    {
        id: 1,
        name: 'Starter',
        slug: 'starter',
        description: 'ছোট দোকান, একক কাউন্টার রিটেইল শপ ও স্টার্টআপদের জন্য সেরা।',
        price_monthly: 799,
        price_yearly: 7990,
        max_users: 2,
        max_products: 500,
        max_branches: 1,
        features: ['pos', 'inventory', 'sales', 'customers', 'basic_accounting', 'barcode_scanner', 'receipt_printer'],
        sort_order: 1
    },
    {
        id: 2,
        name: 'Business',
        slug: 'business',
        description: 'মাঝারি ও দ্রুত বর্ধনশীল ব্যবসা, পাইকারি আড়ত ও মাল্টি-স্টোরের জন্য উপযুক্ত।',
        price_monthly: 1999,
        price_yearly: 19990,
        max_users: 6,
        max_products: 5000,
        max_branches: 3,
        features: ['pos', 'inventory', 'sales', 'customers', 'suppliers', 'purchases', 'sale_returns', 'banking', 'advanced_accounting', 'financial_reports', 'multi_branch', 'barcode_scanner', 'receipt_printer'],
        sort_order: 2
    },
    {
        id: 3,
        name: 'Enterprise',
        slug: 'enterprise',
        description: 'বড় রিটেইল চেইন, সুপারশপ এবং একাধিক শাখা পরিচালনাকারীদের জন্য পূর্ণাঙ্গ সমাধান।',
        price_monthly: 3999,
        price_yearly: 39990,
        max_users: 999,
        max_products: 99999,
        max_branches: 10,
        features: ['pos', 'inventory', 'sales', 'customers', 'suppliers', 'purchases', 'banking', 'advanced_accounting', 'financial_reports', 'multi_branch', 'stock_alerts', 'staff_roles', 'priority_support', 'api_access', 'barcode_scanner', 'receipt_printer'],
        sort_order: 3
    }
]

const displayPlans = computed(() => {
    if (props.plans && props.plans.length > 0) {
        return props.plans
    }
    return fallbackPlans
})

const isPopular = (plan) => {
    return plan.slug === 'business' || plan.sort_order === 2
}

// Feature dictionary for bilingual labels
const featureDict = {
    pos: { bn: 'ক্যাশ কাউন্টার (POS) ও বারকোড বিলিং', en: 'Cash Register (POS) & Barcode Billing' },
    inventory: { bn: 'রিয়েলটাইম স্টক ইনভেন্টরি ট্র্যাকিং', en: 'Real-time Stock Inventory Tracking' },
    sales: { bn: 'বিক্রয় ও কাস্টমার বাকি খাতা (Ledger)', en: 'Sales & Customer Due Ledger' },
    customers: { bn: 'কাস্টমার প্রোফাইল ও ব্যালেন্স ট্র্যাকিং', en: 'Customer Profile & Balance Tracking' },
    suppliers: { bn: 'সাপ্লায়ার ও ক্রয় চালান (Purchase) খাতা', en: 'Supplier & Purchase Payables Ledger' },
    purchases: { bn: 'পণ্য ক্রয় চালান ও সরবরাহকারী ট্র্যাকিং', en: 'Purchases & Supplier Invoices' },
    banking: { bn: 'ব্যাংক ও ক্যাশ অ্যাকাউন্ট ব্যালেন্স', en: 'Bank & Cash Account Tracking' },
    basic_accounting: { bn: 'দৈনিক আয়-ব্যয় ও প্রফিট রিপোর্ট', en: 'Daily Income-Expense & Profit Reports' },
    advanced_accounting: { bn: 'ব্যালেন্স শীট ও ডাবল-এন্ট্রি লেজার', en: 'Balance Sheet & Double-entry Ledger' },
    financial_reports: { bn: 'উন্নত ফিন্যান্সিয়াল অ্যানালিটিক্স রিপোর্ট', en: 'Advanced Financial Analytics Reports' },
    multi_branch: { bn: 'মাল্টি-ব্রাঞ্চ / শাখা ট্রান্সফার সাপোর্ট', en: 'Multi-Branch & Warehouse Transfers' },
    sale_returns: { bn: 'বিক্রয় ফেরত (Sale Return) ও রিফান্ড', en: 'Sale Returns & Refund Management' },
    purchase_returns: { bn: 'ক্রয় ফেরত (Purchase Return)', en: 'Purchase Return Management' },
    barcode_scanner: { bn: 'বারকোড জেনারেশন ও লেবেল প্রিন্ট', en: 'Barcode Generator & Label Printing' },
    receipt_printer: { bn: 'থার্মাল স্লিপ ও কাস্টম ইনভয়েস প্রিন্ট', en: 'POS Thermal Slip & A4 Invoices' },
    stock_alerts: { bn: 'স্বল্প স্টক সতর্কতা (Low Stock Alert)', en: 'Automatic Low Stock Alerts' },
    staff_roles: { bn: 'কর্মচারী রোল ও পারমিশন কন্ট্রোল', en: 'Staff Role & Permission Controls' },
    priority_support: { bn: '২৪/৭ ভিআইপি প্রায়োরিটি সাপোর্ট', en: '24/7 VIP Priority Support' },
    api_access: { bn: 'ডেভেলপার API অ্যাক্সেস', en: 'REST API & Webhook Access' },
    sms_alerts: { bn: 'এসএমএস নোটিফিকেশন সুবিধা', en: 'Automated SMS Notifications' },
}

const getPlanFeatureList = (plan) => {
    if (!plan.features || !Array.isArray(plan.features)) {
        return [
            t('ক্যাশ কাউন্টার (POS) ও বারকোড স্ক্যানার', 'Cash Register (POS) & Barcode Scanner'),
            t('রিয়েলটাইম স্টক ইনভেন্টরি ট্র্যাকিং', 'Real-time Stock Inventory Tracking'),
            t('কাস্টমার ও সাপ্লায়ার বাকি খাতা', 'Customer & Supplier Due Ledger'),
            t('ব্যালেন্স শীট ও আয়-ব্যয় হিসাব', 'Balance Sheet & Income Statement'),
        ]
    }

    return plan.features.slice(0, 7).map(key => {
        if (featureDict[key]) {
            return t(featureDict[key].bn, featureDict[key].en)
        }
        return key.replace(/_/g, ' ')
    })
}

// Business types
const businessTypes = [
    { icon: 'fas fa-cart-shopping', nameBn: 'মুদি ও সুপারশপ', nameEn: 'Super Shop & Grocery' },
    { icon: 'fas fa-prescription-bottle-medical', nameBn: 'ফার্মেসি ও ড্রাগস', nameEn: 'Pharmacy & Medical' },
    { icon: 'fas fa-shirt', nameBn: 'পোশাক ও ফ্যাশন', nameEn: 'Apparel & Fashion' },
    { icon: 'fas fa-mobile-screen', nameBn: 'ইলেকট্রনিক্স ও গ্যাজেট', nameEn: 'Electronics & Mobile' },
    { icon: 'fas fa-warehouse', nameBn: 'পাইকারি আড়ত', nameEn: 'Wholesale & Traders' },
    { icon: 'fas fa-wrench', nameBn: 'হার্ডওয়্যার ও পার্টস', nameEn: 'Hardware & Sanitary' },
]

// FAQ list
const faqList = [
    {
        qBn: 'আমি কি কোনো টাকা দেওয়া ছাড়াই ফ্রিতে ব্যবহার করে দেখতে পারব?',
        qEn: 'Can I test TrustCash completely free of charge?',
        aBn: 'হ্যাঁ! যেকোনো প্ল্যানে আপনি সম্পূর্ণ ১৪ দিন বিনামূল্যে সকল ফিচার ব্যবহার করে দেখতে পারবেন। কোনো ক্রেডিট কার্ডের প্রয়োজন নেই।',
        aEn: 'Yes! You get a full 14-day free trial on all plans with no credit card required. Test all features with zero risk.'
    },
    {
        qBn: 'ইন্টারনেট সংযোগ না থাকলে কি ক্যাশ কাউন্টার চলবে?',
        qEn: 'Will the POS cash counter work if the internet drops?',
        aBn: 'হ্যাঁ! TrustCash PWA অফলাইন মোড সাপোর্ট করে। প্রোডাক্ট ক্যাটালগ ক্যাশ থাকে, তাই নেট না থাকলেও বিক্রি করে মেমো প্রিন্ট করা যাবে এবং নেট ফিরলে অটো-সিঙ্ক হয়ে যাবে।',
        aEn: 'Yes! TrustCash PWA includes offline resilience. Your product catalog stays cached so you can take sales offline, and it auto-syncs when reconnected.'
    },
    {
        qBn: 'আমার বিদ্যমান পণ্যের তালিকা কি এক্সেল দিয়ে ইমপোর্ট করতে পারব?',
        qEn: 'Can I import my existing product stock list from Excel?',
        aBn: 'অবশ্যই। এক ক্লিকেই আপনার এক্সেল বা CSV ফাইল থেকে সব পণ্যের নাম, দাম, বারকোড ও স্টক ডাটাবেসে যুক্ত করে নিতে পারবেন।',
        aEn: 'Absolutely. You can import all your products, SKUs, barcode numbers, and starting stock with a single CSV/Excel upload.'
    },
    {
        qBn: 'আমার ডাটা কি নিরাপদ থাকবে?',
        qEn: 'Is my business data secure and confidential?',
        aBn: 'TrustCash এন্টারপ্রাইজ গ্রেড মাল্টি-টেন্যান্ট সিকিউরিটি এবং স্বয়ংক্রিয় ক্লাউড ব্যাকআপ প্রযুক্তি ব্যবহার করে। আপনার ব্যবসায়িক ডাটা সম্পূর্ণ এনক্রিপ্টেড ও শুধু আপনারই নিয়ন্ত্রণাধীন।',
        aEn: 'TrustCash utilizes enterprise multi-tenant database isolation, HTTPS encryption, and automated daily cloud backups.'
    }
]

// Trigger PWA installation prompt
const triggerPwaPrompt = () => {
    window.dispatchEvent(new CustomEvent('trustcash:prompt-install'))
}

onMounted(() => {
    updateThemeState()
})
</script>
