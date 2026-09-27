<template>
    <Head title="TrustCash - সকল ব্যবসার জন্য আধুনিক ক্লাউড পিওএস ও একাউন্টিং প্ল্যাটফর্ম" />
    <div class="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-blue-500 selection:text-white">
        <!-- Navigation -->
        <header class="sticky top-0 z-50 backdrop-blur-lg bg-slate-950/80 border-b border-slate-800/80">
            <div class="container mx-auto px-6 h-20 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-black text-white text-xl shadow-lg shadow-blue-500/30">
                        T
                    </div>
                    <div>
                        <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-200 to-blue-300 bg-clip-text text-transparent">TrustCash</span>
                        <span class="hidden sm:inline-block ml-2 text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">SaaS Cloud</span>
                    </div>
                </div>

                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                    <a href="#features" class="hover:text-blue-400 transition-colors">ফিচারসমূহ (Features)</a>
                    <a href="#business-types" class="hover:text-blue-400 transition-colors">কার জন্য (Use Cases)</a>
                    <a href="#pricing" class="hover:text-blue-400 transition-colors">মূল্য তালিকা (Pricing)</a>
                    <a href="#faq" class="hover:text-blue-400 transition-colors">প্রশ্নোত্তর (FAQ)</a>
                </nav>

                <div class="flex items-center gap-4">
                    <Link
                        :href="route('login')"
                        class="text-sm font-medium text-slate-300 hover:text-white transition"
                    >
                        লগইন
                    </Link>
                    <Link
                        :href="route('tenant.register')"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold text-sm shadow-lg shadow-blue-600/30 transition transform hover:-translate-y-0.5 active:translate-y-0"
                    >
                        ১৪ দিন ফ্রি ট্রায়াল শুরু করুন
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative pt-20 pb-28 overflow-hidden">
            <!-- Background Glow -->
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[650px] h-[650px] bg-blue-600/20 rounded-full blur-[140px] pointer-events-none"></div>
            <div class="absolute top-1/3 left-1/4 w-[400px] h-[400px] bg-indigo-600/15 rounded-full blur-[120px] pointer-events-none"></div>

            <div class="container mx-auto px-6 relative z-10 text-center max-w-4xl">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold mb-6">
                    <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                    সব ধরনের খুচরা ও পাইকারি ব্যবসার বিশ্বস্ত সমাধান
                </div>

                <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight leading-tight sm:leading-none text-white mb-6">
                    আপনার ব্যবসার কেনা-বেচা ও হিসাব নিকাশ <br class="hidden sm:inline" />
                    <span class="bg-gradient-to-r from-blue-400 via-indigo-300 to-teal-300 bg-clip-text text-transparent">
                        এখন হবে ১০০% নির্ভুল ও সহজ
                    </span>
                </h1>

                <p class="text-lg sm:text-xl text-slate-400 mb-10 max-w-2xl mx-auto leading-relaxed">
                    সুপারশপ, পাইকারি আড়ত, ফার্মেসি, ইলেকট্রনিক্স বা গার্মেন্টস — যেকোনো ব্যবসার স্টক ইনভেন্টরি, দ্রুত বারকোড সেলস, কাস্টমার-সাপ্লায়ার বাকি এবং ব্যালেন্স শীট এক ছাতার নিচে।
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        :href="route('tenant.register')"
                        class="w-full sm:w-auto px-8 py-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-base shadow-xl shadow-blue-500/25 transition transform hover:-translate-y-0.5"
                    >
                        ফ্রি একাউন্ট খুলুন (১৪ দিন ফ্রি)
                    </Link>
                    <a
                        href="#pricing"
                        class="w-full sm:w-auto px-8 py-4 rounded-xl bg-slate-900 hover:bg-slate-850 border border-slate-800 text-slate-200 font-semibold text-base transition"
                    >
                        প্ল্যান ও মূল্য দেখুন
                    </a>
                </div>

                <!-- Stats highlight -->
                <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-6 pt-10 border-t border-slate-850">
                    <div>
                        <div class="text-3xl font-black text-white">১ সেকেন্ডে</div>
                        <div class="text-xs text-slate-400 mt-1">দ্রুত বারকোড বিলিং</div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-blue-400">১০০%</div>
                        <div class="text-xs text-slate-400 mt-1">সঠিক বাকি-বকেয়া খাতা</div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-indigo-400">একাধিক</div>
                        <div class="text-xs text-slate-400 mt-1">শাখা / ব্রাঞ্চ কন্ট্রোল</div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-emerald-400">২৪/৭</div>
                        <div class="text-xs text-slate-400 mt-1">ক্লাউড ডাটা ব্যাকআপ</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Business Types Grid -->
        <section id="business-types" class="py-20 bg-slate-900/60 border-y border-slate-800/80">
            <div class="container mx-auto px-6">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold text-white mb-4">যেকোনো ধরনের ব্যবসা পরিচালনা করুন স্বচ্ছন্দে</h2>
                    <p class="text-slate-400 text-sm">এক সফটওয়্যারেই রয়েছে আপনার বিশেষ ব্যবসার প্রয়োজনীয় সব মডিউল</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800 hover:border-blue-500/50 transition group">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center font-bold text-xl mb-4 group-hover:scale-110 transition">
                            🛒
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">সুপারশপ ও গ্রোসারি</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            হাজার হাজার আইটেমের বারকোড স্ক্যানিং, ক্যাশ ড্রয়ার ও থার্মাল প্রিন্টার ইন্টিগ্রেশন এবং দ্রুত বিলিং।
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800 hover:border-blue-500/50 transition group">
                        <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold text-xl mb-4 group-hover:scale-110 transition">
                            📦
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">পাইকারি আড়ত ও ডিলারশিপ</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            সাপ্লায়ার ক্রয় চালান, পাইকারি রেট, কাস্টমার বাকির লেজার এবং ডেলিভারি চালান প্রিন্ট।
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800 hover:border-blue-500/50 transition group">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold text-xl mb-4 group-hover:scale-110 transition">
                            💊
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">ফার্মেসি ও মেডিসিন শপ</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            বক্স/পাতা/পিস ইউনিট কনভার্সন, মেয়াদ উত্তীর্ণ পণ্য ট্র্যাকিং ও দ্রুত সার্চিং।
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800 hover:border-blue-500/50 transition group">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center font-bold text-xl mb-4 group-hover:scale-110 transition">
                            👕
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">পোশাক ও ফ্যাশন শপ</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            রং, সাইজ ও ব্র্যান্ড ভ্যারিয়েন্ট অনুযায়ী স্টক ম্যানেজমেন্ট এবং কাস্টম বারকোড প্রিন্টিং।
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800 hover:border-blue-500/50 transition group">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center font-bold text-xl mb-4 group-hover:scale-110 transition">
                            ⚡
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">ইলেকট্রনিক্স ও হার্ডওয়্যার</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            আইটেমের সিরিয়াল নম্বর ট্র্যাকিং, ওয়ারেন্টি মেমো এবং পণ্য রিপ্লেসমেন্ট/রিটার্ন ব্যবস্থাপনা।
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800 hover:border-blue-500/50 transition group">
                        <div class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center font-bold text-xl mb-4 group-hover:scale-110 transition">
                            🏢
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">মাল্টি-ব্রাঞ্চ চেইন শপ</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            কেন্দ্রীয় এডমিন থেকে সব শাখার স্টক, বিক্রি ও ক্যাশ কালেকশন রিয়েল-টাইমে মনিটরিং।
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Showcase -->
        <section id="features" class="py-24">
            <div class="container mx-auto px-6">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest text-blue-400">ফিচার হাইলাইটস</span>
                    <h2 class="text-3xl font-bold text-white mt-2">আপনার ব্যবসার প্রতিটি ধাপকে করে তুলবে গতিময়</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-slate-900/40 p-8 rounded-2xl border border-slate-800">
                        <div class="text-2xl mb-4">⚡</div>
                        <h4 class="text-lg font-bold text-white mb-2">দ্রুত পিওএস (Point of Sale)</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            কীবোর্ড শর্টকাট, বারকোড স্ক্যানার সাপোর্ট, একাধিক পেমেন্ট মেথড (ক্যাশ, ব্যাংক, বিকাশ, কার্ড) এবং সরাসরি থার্মাল রিসিট প্রিন্ট।
                        </p>
                    </div>

                    <div class="bg-slate-900/40 p-8 rounded-2xl border border-slate-800">
                        <div class="text-2xl mb-4">🚚</div>
                        <h4 class="text-lg font-bold text-white mb-2">সাপ্লায়ার ও ক্রয় ব্যবস্থাপনা</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            মহাজন বা সরবরাহকারীর ক্রয় চালান এন্ট্রি, অটোমেটিক স্টক বৃদ্ধি, আংশিক পরিশোধ ও বাকি হিসাবের অটো লেজার।
                        </p>
                    </div>

                    <div class="bg-slate-900/40 p-8 rounded-2xl border border-slate-800">
                        <div class="text-2xl mb-4">🔄</div>
                        <h4 class="text-lg font-bold text-white mb-2">বিক্রয় ও ক্রয় ফেরত (Returns)</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            গ্রাহক বা সাপ্লায়ার পণ্য ফেরত দিলে ইনভেন্টরি অটোমেটিক আপডেট ও ব্যালেন্স বা রিফান্ড এডজাস্টমেন্ট।
                        </p>
                    </div>

                    <div class="bg-slate-900/40 p-8 rounded-2xl border border-slate-800">
                        <div class="text-2xl mb-4">📊</div>
                        <h4 class="text-lg font-bold text-white mb-2">পরিপূর্ণ একাউন্টিং ও ব্যালেন্স শীট</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            ইনকাম-এক্সপেন্ডিচার, রিসিট-পেমেন্ট, ডেইলি লাভ-ক্ষতি এবং স্বয়ংক্রিয় ব্যালেন্স শীট রিপোর্ট (পিডিএফ ডাউনলোড সহ)।
                        </p>
                    </div>

                    <div class="bg-slate-900/40 p-8 rounded-2xl border border-slate-800">
                        <div class="text-2xl mb-4">🏦</div>
                        <h4 class="text-lg font-bold text-white mb-2">ব্যাংক ও ক্যাশ একাউন্ট কন্ট্রোল</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            দোকানের ক্যাশ ড্রয়ার ও একাধিক ব্যাংক একাউন্টের রানিং ব্যালেন্স ট্র্যাকিং ও ফান্ড ট্রান্সফার।
                        </p>
                    </div>

                    <div class="bg-slate-900/40 p-8 rounded-2xl border border-slate-800">
                        <div class="text-2xl mb-4">🔒</div>
                        <h4 class="text-lg font-bold text-white mb-2">পৃথক ডাটাবেস নিরাপত্তা</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            প্রতিটি ব্যবসার জন্য সম্পূর্ণ পৃথক ডাটাবেস। এক ব্যবসার ডাটা অন্য ব্যবসা কখনই দেখতে পাবে না।
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="py-24 bg-slate-900/50 border-t border-slate-800">
            <div class="container mx-auto px-6">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest text-blue-400">সুলভ মূল্য তালিকা</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white mt-2">আপনার ব্যবসার জন্য সঠিক প্ল্যান বেছে নিন</h2>
                    <p class="text-slate-400 text-sm mt-3">কোন হিডেন চার্জ নেই। ১৪ দিন ফ্রি ট্রায়াল। যেকোনো সময় আপগ্রেড বা বাতিল করুন।</p>

                    <!-- Billing Cycle Toggle -->
                    <div class="inline-flex items-center gap-3 p-1.5 rounded-full bg-slate-950 border border-slate-800 mt-8 text-xs font-semibold">
                        <button
                            @click="yearly = false"
                            class="px-4 py-1.5 rounded-full transition"
                            :class="!yearly ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white'"
                        >
                            মাসিক বিলিং
                        </button>
                        <button
                            @click="yearly = true"
                            class="px-4 py-1.5 rounded-full transition flex items-center gap-1.5"
                            :class="yearly ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white'"
                        >
                            বার্ষিক বিলিং
                            <span class="px-1.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px]">২ মাস ফ্রি!</span>
                        </button>
                    </div>
                </div>

                <!-- Plans Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        class="rounded-3xl p-8 transition-all flex flex-col justify-between"
                        :class="plan.slug === 'business'
                            ? 'bg-gradient-to-b from-blue-950/80 to-slate-900 border-2 border-blue-500 shadow-2xl shadow-blue-500/20 relative'
                            : 'bg-slate-950/80 border border-slate-800 hover:border-slate-700'"
                    >
                        <div v-if="plan.slug === 'business'" class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-[11px] font-bold uppercase tracking-wider shadow">
                            সবচেয়ে জনপ্রিয় (Most Popular)
                        </div>

                        <div>
                            <h3 class="text-xl font-bold text-white">{{ plan.name }}</h3>
                            <p class="text-xs text-slate-400 mt-1 min-h-[32px]">{{ plan.description }}</p>

                            <div class="mt-6 mb-8 flex items-baseline gap-1">
                                <span class="text-4xl font-extrabold text-white">৳{{ formatNumber(yearly ? plan.price_yearly : plan.price_monthly) }}</span>
                                <span class="text-xs text-slate-400">/ {{ yearly ? 'বছর' : 'মাস' }}</span>
                            </div>

                            <div class="space-y-3 text-xs text-slate-300 border-t border-slate-800/80 pt-6">
                                <div class="flex items-center gap-2">
                                    <span class="text-blue-400 font-bold">✓</span>
                                    <span>সর্বোচ্চ ব্যবহারকারী: <strong>{{ plan.max_users >= 999 ? 'আনলিমিটেড' : plan.max_users + ' জন' }}</strong></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-blue-400 font-bold">✓</span>
                                    <span>সর্বোচ্চ পণ্য সংখ্যা: <strong>{{ plan.max_products >= 9999 ? 'আনলিমিটেড' : plan.max_products + ' টি' }}</strong></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-blue-400 font-bold">✓</span>
                                    <span>শাখা / আউটলেট: <strong>{{ plan.max_branches }} টি</strong></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-blue-400 font-bold">✓</span>
                                    <span>দ্রুত পিওএস ও বারকোড জেনারেটর</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-blue-400 font-bold">✓</span>
                                    <span>কাস্টমার বাকি ও লেজার খাতা</span>
                                </div>
                                <div v-if="plan.slug !== 'starter'" class="flex items-center gap-2">
                                    <span class="text-blue-400 font-bold">✓</span>
                                    <span>সাপ্লায়ার ও ক্রয় চালান ম্যানেজমেন্ট</span>
                                </div>
                                <div v-if="plan.slug !== 'starter'" class="flex items-center gap-2">
                                    <span class="text-blue-400 font-bold">✓</span>
                                    <span>বিক্রয় ফেরত ও মাল্টি-ব্রাঞ্চ সাপোর্ট</span>
                                </div>
                                <div v-if="plan.slug === 'professional'" class="flex items-center gap-2">
                                    <span class="text-blue-400 font-bold">✓</span>
                                    <span>প্রায়োরিটি সাপোর্ট ও কাস্টম ব্র্যান্ডিং</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-850">
                            <Link
                                :href="route('tenant.register', { plan: plan.slug })"
                                class="w-full py-3 px-4 rounded-xl font-bold text-center block text-sm transition"
                                :class="plan.slug === 'business'
                                    ? 'bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30'
                                    : 'bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700'"
                            >
                                ১৪ দিন ফ্রি শুরু করুন
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="faq" class="py-24">
            <div class="container mx-auto px-6 max-w-3xl">
                <div class="text-center mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest text-blue-400">সাধারণ জিজ্ঞাসা</span>
                    <h2 class="text-3xl font-bold text-white mt-2">সচরাচর জানতে চাওয়া প্রশ্নসমূহ</h2>
                </div>

                <div class="space-y-4 text-sm">
                    <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
                        <h4 class="font-bold text-white mb-2">আমি কি বিনামূল্যে ব্যবহার করে দেখতে পারব?</h4>
                        <p class="text-slate-400 text-xs leading-relaxed">
                            হ্যাঁ! যেকোনো প্ল্যানে আপনি সম্পূর্ণ ১৪ দিন বিনামূল্যে সম্পূর্ণ ফিচার টেস্ট করতে পারবেন। কোনো ক্রেডিট কার্ডের প্রয়োজন নেই।
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
                        <h4 class="font-bold text-white mb-2">আমার ডাটা কি নিরাপদ থাকবে?</h4>
                        <p class="text-slate-400 text-xs leading-relaxed">
                            আমাদের আর্কিটেকচারে প্রতিটা ব্যবসার ডাটা আলাদা এনক্রিপ্টেড ডাটাবেসে সংরক্ষিত হয়। ক্লাউড ব্যাকআপ স্বয়ংক্রিয়ভাবে রক্ষিত হয়।
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
                        <h4 class="font-bold text-white mb-2">বিল পরিশোধ কিভাবে করব?</h4>
                        <p class="text-slate-400 text-xs leading-relaxed">
                            আপনি বিকাশ, নগদ, রকেট, ডেবিট/ক্রেডিট কার্ড বা সরাসরি ব্যাংক ট্রান্সফারের মাধ্যমে সহজেই সাবস্ক্রিপশন ফি পরিশোধ করতে পারবেন।
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-12 border-t border-slate-900 bg-slate-950 text-slate-500 text-xs">
            <div class="container mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <span class="text-white font-bold text-base">TrustCash</span>
                    <p class="mt-1">© 2026 TrustCash POS SaaS. All rights reserved.</p>
                </div>

                <div class="flex gap-6 text-slate-400">
                    <Link :href="route('privacy')" class="hover:text-white transition">Privacy Policy</Link>
                    <Link :href="route('terms')" class="hover:text-white transition">Terms of Service</Link>
                    <Link :href="route('contact')" class="hover:text-white transition">Contact Us</Link>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    plans: Array,
})

const yearly = ref(false)

const formatNumber = (val) => {
    return Number(val || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    })
}
</script>
