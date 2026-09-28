<template>
    <AdminLayout :title="t('নতুন পণ্য যোগ করুন', 'Create Product')">
        <Head :title="t('নতুন পণ্য যোগ করুন', 'Create Product')" />

        <div class="mx-auto max-w-7xl space-y-6 pb-12">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 mb-1">
                        <Link :href="route('admin.products.index')" class="hover:underline flex items-center gap-1">
                            <CubeIcon class="h-3.5 w-3.5" />
                            <span>{{ t('পণ্য তালিকা', 'Products') }}</span>
                        </Link>
                        <span>/</span>
                        <span class="text-slate-400 dark:text-slate-500">{{ t('নতুন পণ্য', 'Create') }}</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                        <span>{{ t('নতুন পণ্য যুক্ত করুন', 'Create New Product') }}</span>
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                            {{ t('ইনভেন্টরি', 'Inventory') }}
                        </span>
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('নিচের আবশ্যকীয় তথ্যগুলো পূরণ করে দ্রুততম সময়ে পণ্যটি ইনভেন্টরিতে যোগ করুন।', 'Fill in the required information to quickly add this product to your inventory.') }}
                    </p>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center gap-2.5">
                    <Link :href="route('admin.products.index')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700/80 transition-all">
                        <ArrowLeftIcon class="h-4 w-4" />
                        <span>{{ t('পণ্য তালিকায় ফিরুন', 'Back to Products') }}</span>
                    </Link>

                    <button type="button" @click="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 active:scale-98 disabled:opacity-60 transition-all">
                        <span v-if="form.processing" class="h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <SparklesIcon v-else class="h-4 w-4" />
                        <span>{{ form.processing ? t('সংরক্ষণ হচ্ছে...', 'Saving...') : t('পণ্য সংরক্ষণ করুন', 'Save Product') }}</span>
                    </button>
                </div>
            </div>

            <!-- Main Form & Preview Grid -->
            <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-start">
                
                <!-- Left Main Section: Required & Secondary Fields -->
                <div class="space-y-6 lg:col-span-8">

                    <!-- 1. REQUIRED / ESSENTIAL INFORMATION CARD -->
                    <div class="rounded-2xl border-2 border-indigo-200/90 bg-white p-5 shadow-xs dark:border-indigo-900/60 dark:bg-slate-900 relative overflow-hidden">
                        <!-- Top Accent Banner -->
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-sm shadow-xs shadow-indigo-600/30">
                                    ১
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        <span>{{ t('প্রয়োজনীয় তথ্য', 'Essential Information') }}</span>
                                        <span class="rounded-md bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200/60 dark:border-rose-900/50">
                                            * {{ t('আবশ্যকীয়', 'Required') }}
                                        </span>
                                    </h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ t('পণ্য বিক্রির জন্য এই তথ্যগুলো পূরণ করা বাধ্যতামূলক', 'These primary fields are required for creating and selling the product') }}
                                    </p>
                                </div>
                            </div>

                            <span class="hidden sm:inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-1 rounded-lg">
                                <CheckCircleIcon class="h-3.5 w-3.5" />
                                <span>{{ t('দ্রুত এন্ট্রি মোড', 'Quick Entry Ready') }}</span>
                            </span>
                        </div>

                        <div class="space-y-4">
                            <!-- Product Name (Full Width, Large & Prominent) -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="name" class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                                        {{ t('পণ্যের নাম *', 'Product Name *') }}
                                    </label>
                                    <span class="text-[11px] text-slate-400 dark:text-slate-500">
                                        {{ t('নাম লিখলে SKU ও বারকোড অটো জেনারেট হবে', 'SKU & Barcode auto-fill from name') }}
                                    </span>
                                </div>
                                <div class="relative">
                                    <input id="name" v-model="form.name" type="text" autofocus
                                        class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-bold text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500 transition-all"
                                        :placeholder="t('যেমনঃ মিনিকেট চাল ২৫ কেজি, লাক্স সাবান ১০০ গ্রাম, স্যামসাং এ১৫...', 'e.g. Miniket Rice 25kg, Lux Soap 100g...')"
                                        required />
                                </div>
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>

                            <!-- Category & Unit (Side by Side) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Category -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="category_id" class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                                            {{ t('ক্যাটাগরি *', 'Category *') }}
                                        </label>
                                        <Link :href="route('admin.categories.index')" target="_blank"
                                            class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                                            + {{ t('নতুন ক্যাটাগরি', 'New Category') }}
                                        </Link>
                                    </div>
                                    <div class="relative">
                                        <select id="category_id" v-model="form.category_id" required
                                            class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-900 shadow-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 transition-all">
                                            <option value="" disabled class="bg-white dark:bg-slate-800 text-slate-400">
                                                {{ t('ক্যাটাগরি নির্বাচন করুন...', 'Select Category...') }}
                                            </option>
                                            <option v-for="category in categories" :key="category.id" :value="category.id"
                                                class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 py-1">
                                                {{ category.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <InputError :message="form.errors.category_id" class="mt-1" />
                                </div>

                                <!-- Unit -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="unit_id" class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                                            {{ t('পরিমাপের একক *', 'Measurement Unit *') }}
                                        </label>
                                        <Link :href="route('admin.units.index')" target="_blank"
                                            class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                                            + {{ t('নতুন একক', 'New Unit') }}
                                        </Link>
                                    </div>
                                    <div class="relative">
                                        <select id="unit_id" v-model="form.unit_id" required
                                            class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-900 shadow-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 transition-all">
                                            <option value="" disabled class="bg-white dark:bg-slate-800 text-slate-400">
                                                {{ t('একক নির্বাচন করুন...', 'Select Unit...') }}
                                            </option>
                                            <option v-for="unit in units" :key="unit.id" :value="unit.id"
                                                class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 py-1">
                                                {{ unit.name }} ({{ unit.short_name }})
                                            </option>
                                        </select>
                                    </div>
                                    <InputError :message="form.errors.unit_id" class="mt-1" />
                                </div>
                            </div>

                            <!-- Pricing & Low Stock Alert -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                                <!-- Selling Price -->
                                <div>
                                    <label for="selling_price" class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                                        {{ t('বিক্রয় মূল্য (টাকা) *', 'Selling Price (BDT) *') }}
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                            <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">৳</span>
                                        </div>
                                        <input id="selling_price" v-model="form.selling_price" type="number"
                                            step="0.01" min="0" required placeholder="0.00"
                                            class="block w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-8 pr-3 text-sm font-black text-slate-900 shadow-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white transition-all" />
                                    </div>
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                                        {{ t('গ্রাহকের কাছে যে মূল্যে পণ্যটি বিক্রি হবে', 'Retail sale price per unit') }}
                                    </p>
                                    <InputError :message="form.errors.selling_price" class="mt-1" />
                                </div>

                                <!-- Alert Quantity -->
                                <div>
                                    <label for="alert_quantity" class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                                        {{ t('সীমিত স্টক সতর্কতা সীমা *', 'Low Stock Alert Limit *') }}
                                    </label>
                                    <div class="relative">
                                        <input id="alert_quantity" v-model="form.alert_quantity" type="number" min="0" required
                                            class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 shadow-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white transition-all" />
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">
                                                {{ selectedUnitShortName || t('একক', 'Units') }}
                                            </span>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                                        {{ t('স্টক এই পরিমাণের নিচে নামলে সতর্কতা দেখাবে', 'Alerts when stock drops below this number') }}
                                    </p>
                                    <InputError :message="form.errors.alert_quantity" class="mt-1" />
                                </div>
                            </div>

                            <!-- SKU (Auto / Manual) -->
                            <div class="pt-1">
                                <div class="flex items-center justify-between mb-1">
                                    <label for="sku" class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                                        {{ t('SKU কোড *', 'SKU Code *') }}
                                    </label>
                                    <span class="text-[10px] font-medium text-slate-400 dark:text-slate-500">
                                        {{ t('অনন্য ইনভেন্টরি কোড', 'Unique product identifier') }}
                                    </span>
                                </div>
                                <div class="flex rounded-xl shadow-xs overflow-hidden border border-slate-300 dark:border-slate-700 focus-within:border-indigo-600 focus-within:ring-2 focus-within:ring-indigo-600/20 transition-all">
                                    <input id="sku" v-model="form.sku" type="text"
                                        class="block w-full border-0 bg-white px-3.5 py-2.5 text-xs font-mono font-bold text-slate-900 focus:ring-0 dark:bg-slate-800 dark:text-slate-100 uppercase tracking-wider"
                                        :placeholder="skuPlaceholder" required />
                                    <button type="button" @click="generateSku"
                                        :title="t('নতুন SKU তৈরি করুন', 'Regenerate SKU')"
                                        class="inline-flex items-center gap-1 bg-slate-100 px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 border-l border-slate-200 dark:border-slate-600 transition-colors">
                                        <ArrowPathIcon class="h-3.5 w-3.5" />
                                        <span class="text-[11px] hidden sm:inline">{{ t('তৈরি করুন', 'Generate') }}</span>
                                    </button>
                                </div>
                                <InputError :message="form.errors.sku" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- 2. BARCODE & BRAND (SECONDARY / OPTIONAL IDENTIFIERS) -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-700 font-black text-sm dark:bg-slate-800 dark:text-slate-200">
                                ২
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>{{ t('বারকোড ও ব্র্যান্ড', 'Barcode & Brand') }}</span>
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                        {{ t('ঐচ্ছিক', 'Optional') }}
                                    </span>
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ t('বারকোড স্ক্যানার ও ব্র্যান্ড ব্যবস্থাপনার জন্য', 'For barcode scanner integration and brand categorization') }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Barcode -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="barcode" class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                                        {{ t('বারকোড নম্বর', 'Barcode Number') }}
                                    </label>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500">
                                        {{ t('স্ক্যান করুন বা অটো তৈরি করুন', 'Scan or auto-generate') }}
                                    </span>
                                </div>
                                <div class="flex rounded-xl shadow-xs overflow-hidden border border-slate-300 dark:border-slate-700 focus-within:border-indigo-600 focus-within:ring-2 focus-within:ring-indigo-600/20 transition-all">
                                    <div class="pointer-events-none flex items-center pl-3 bg-white dark:bg-slate-800 text-slate-400">
                                        <QrCodeIcon class="h-4 w-4" />
                                    </div>
                                    <input id="barcode" v-model="form.barcode" type="text"
                                        class="block w-full border-0 bg-white px-2.5 py-2.5 text-xs font-mono font-bold text-slate-900 focus:ring-0 dark:bg-slate-800 dark:text-slate-100 tracking-wider"
                                        :placeholder="barcodePlaceholder" />
                                    <button type="button" @click="generateBarcode"
                                        :title="t('নতুন বারকোড তৈরি করুন', 'Generate random Barcode')"
                                        class="inline-flex items-center gap-1 bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 border-l border-slate-200 dark:border-slate-600 transition-colors">
                                        <ArrowPathIcon class="h-3.5 w-3.5" />
                                        <span class="text-[11px] hidden sm:inline">{{ t('অটো কোড', 'Auto') }}</span>
                                    </button>
                                </div>
                                <InputError :message="form.errors.barcode" class="mt-1" />
                            </div>

                            <!-- Brand -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="brand_id" class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                                        {{ t('ব্র্যান্ড', 'Brand') }}
                                    </label>
                                    <Link :href="route('admin.brands.index')" target="_blank"
                                        class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                                        + {{ t('নতুন ব্র্যান্ড', 'New Brand') }}
                                    </Link>
                                </div>
                                <div class="relative">
                                    <select id="brand_id" v-model="form.brand_id"
                                        class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-900 shadow-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 transition-all">
                                        <option value="" class="bg-white dark:bg-slate-800 text-slate-400">
                                            {{ t('কোনো ব্র্যান্ড নেই / প্রযোজ্য নয়', 'No Brand / Not Applicable') }}
                                        </option>
                                        <option v-for="brand in brands" :key="brand.id" :value="brand.id"
                                            class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 py-1">
                                            {{ brand.name }}
                                        </option>
                                    </select>
                                </div>
                                <InputError :message="form.errors.brand_id" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- 3. PRODUCT IMAGES (OPTIONAL) -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-700 font-black text-sm dark:bg-slate-800 dark:text-slate-200">
                                    ৩
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        <span>{{ t('পণ্যের ছবিসমূহ', 'Product Images') }}</span>
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                            {{ t('ঐচ্ছিক', 'Optional') }}
                                        </span>
                                    </h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ t('ছবি নির্বাচন করুন অথবা সরাসরি পেস্ট (Ctrl+V) করুন। সর্বোচ্চ ১০ মেগাবাইট।', 'Upload or paste images (Ctrl+V). Maximum 10MB each.') }}
                                    </p>
                                </div>
                            </div>

                            <span v-if="imagesPreviews.length" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-2.5 py-1 rounded-lg">
                                {{ imagesPreviews.length }} {{ t('টি ছবি যুক্ত', 'images selected') }}
                            </span>
                        </div>

                        <!-- Upload Zone -->
                        <div class="rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 p-6 text-center hover:border-indigo-500 hover:bg-indigo-50/20 dark:hover:border-indigo-500 dark:hover:bg-slate-800/80 transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            tabindex="0"
                            @dragover.prevent
                            @drop.prevent="onFilesDrop"
                            @paste.prevent="onPasteImages">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-xs dark:bg-slate-700 text-slate-400 dark:text-slate-300 mb-3">
                                <PhotoIcon class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                <label for="file-upload" class="cursor-pointer text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 underline underline-offset-2">
                                    <span>{{ t('ডিভাইস থেকে ছবি বাছাই করুন', 'Browse image files') }}</span>
                                    <input id="file-upload" type="file" multiple class="sr-only" accept="image/jpeg,image/png,image/webp,image/jpg" @change="onImagesSelected">
                                </label>
                                <span class="font-normal text-slate-500 dark:text-slate-400 ml-1">
                                    {{ t('বা ড্রপ করুন / কপি করে পেস্ট করুন', 'or drag and drop here') }}
                                </span>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                                {{ t('JPG, PNG, WebP ফরম্যাট সমর্থিত (প্রথম ছবি মূল ছবি হিসেবে ব্যবহৃত হবে)', 'JPG, PNG, WebP supported. First image serves as primary cover.') }}
                            </p>
                        </div>

                        <!-- Image Previews -->
                        <div v-if="imagesPreviews.length" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5">
                            <div v-for="(preview, index) in imagesPreviews" :key="index"
                                class="relative aspect-square rounded-xl overflow-hidden border-2 bg-slate-100 dark:bg-slate-800 group transition-all"
                                :class="primaryImageIndex === index ? 'border-indigo-600 ring-2 ring-indigo-600/30 shadow-md' : 'border-slate-200 dark:border-slate-700'">
                                <img :src="preview" class="h-full w-full object-cover">
                                
                                <!-- Primary Badge -->
                                <div v-if="primaryImageIndex === index"
                                    class="absolute top-2 left-2 bg-indigo-600 text-white px-2 py-0.5 rounded-md text-[10px] font-black tracking-wide shadow-sm flex items-center gap-1">
                                    <StarIcon class="h-3 w-3 fill-current" />
                                    <span>{{ t('মূল ছবি', 'Primary') }}</span>
                                </div>

                                <!-- Action Buttons Overlay -->
                                <div class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <button type="button" @click="setPrimaryImage(index)"
                                        :title="t('মূল ছবি হিসেবে নির্বাচন করুন', 'Set as primary image')"
                                        class="p-2 rounded-lg bg-white/20 hover:bg-white/40 text-white backdrop-blur-xs transition-colors"
                                        :class="{ 'text-amber-400 font-bold': primaryImageIndex === index }">
                                        <StarIcon class="h-4 w-4" :class="{ 'fill-current': primaryImageIndex === index }" />
                                    </button>
                                    <button type="button" @click="removeImage(index)"
                                        :title="t('ছবি মুছে ফেলুন', 'Remove this image')"
                                        class="p-2 rounded-lg bg-rose-600/80 hover:bg-rose-600 text-white backdrop-blur-xs transition-colors">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. ADDITIONAL DETAILS & SPECIFICATIONS (COLLAPSIBLE / OPTIONAL) -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                        <!-- Toggle Button -->
                        <button type="button" @click="isAdvancedOpen = !isAdvancedOpen"
                            class="w-full flex items-center justify-between p-5 text-left hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-700 font-black text-sm dark:bg-slate-800 dark:text-slate-200">
                                    ৪
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        <span>{{ t('অতিরিক্ত বিবরণ ও স্পেসিফিকেশন', 'Additional Notes & Specs') }}</span>
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                            {{ t('ঐচ্ছিক', 'Optional') }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ t('পণ্যের নোট, বিস্তারিত বিবরণ অথবা সাইজ/কালার/ওয়ারেন্টি বৈশিষ্ট্য', 'Product description or key-value specifications') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                <span>{{ isAdvancedOpen ? t('সংকুচিত করুন', 'Collapse') : t('প্রদর্শন করুন', 'Expand') }}</span>
                                <ChevronUpIcon v-if="isAdvancedOpen" class="h-4 w-4" />
                                <ChevronDownIcon v-else class="h-4 w-4" />
                            </div>
                        </button>

                        <!-- Expandable Content -->
                        <div v-show="isAdvancedOpen" class="p-5 pt-0 border-t border-slate-100 dark:border-slate-800 space-y-5">
                            <!-- Description -->
                            <div class="pt-4">
                                <label for="description" class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                                    {{ t('পণ্যের বিস্তারিত নোট / বিবরণ', 'Description / Notes') }}
                                </label>
                                <textarea id="description" v-model="form.description" rows="3"
                                    class="block w-full rounded-xl border border-slate-300 bg-white p-3 text-xs font-medium text-slate-900 shadow-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                                    :placeholder="t('পণ্য সম্পর্কিত গুরুত্বপূর্ণ তথ্য বা নোট এখানে লিখুন...', 'Enter any notes or full product description here...')"></textarea>
                                <InputError :message="form.errors.description" class="mt-1" />
                            </div>

                            <!-- Specifications -->
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            {{ t('বৈশিষ্ট্যসমূহ (Specifications)', 'Product Specifications') }}
                                        </h4>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-500">
                                            {{ t('যেমনঃ সাইজ: XL, রঙ: নীল, ওয়ারেন্টি: ১ বছর ইত্যাদি', 'e.g. Size: XL, Color: Blue, Warranty: 1 Year') }}
                                        </p>
                                    </div>
                                    <button type="button" @click="addSpecification"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/70 dark:text-indigo-300 dark:hover:bg-indigo-900 transition-colors">
                                        <PlusIcon class="h-3.5 w-3.5" />
                                        <span>{{ t('বৈশিষ্ট্য যোগ', 'Add Spec') }}</span>
                                    </button>
                                </div>

                                <div class="space-y-2.5">
                                    <div v-for="(spec, index) in specifications" :key="index"
                                        class="flex items-center gap-2.5">
                                        <input v-model="spec.key" type="text"
                                            class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-900 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                            :placeholder="t('বৈশিষ্ট্যের নাম (যেমন: সাইজ, রঙ)', 'Spec Name (e.g. Size)')" />
                                        
                                        <input v-model="spec.value" type="text"
                                            class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-900 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                            :placeholder="t('মান (যেমন: XL, লাল, ২ বছর)', 'Value (e.g. Red, XL)')" />

                                        <button type="button" @click="removeSpecification(index)"
                                            class="p-2 text-slate-400 hover:text-rose-500 dark:hover:text-rose-400 transition-colors"
                                            :title="t('মুছে ফেলুন', 'Delete')">
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Buttons (Mobile & Desktop) -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <Link :href="route('admin.products.index')"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors">
                            {{ t('বাতিল করুন', 'Cancel') }}
                        </Link>
                        <button type="submit" :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-bold text-white shadow-md hover:bg-indigo-500 active:scale-98 disabled:opacity-60 transition-all">
                            <span v-if="form.processing" class="h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span>{{ form.processing ? t('সংরক্ষণ হচ্ছে...', 'Saving...') : t('পণ্য সংরক্ষণ করুন', 'Save Product') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Right Sidebar: Live Preview & Status Settings -->
                <div class="space-y-6 lg:col-span-4 lg:sticky lg:top-6">
                    
                    <!-- LIVE PRODUCT CARD PREVIEW -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <SparklesIcon class="h-4 w-4 text-amber-500" />
                                <span>{{ t('লাইভ প্রিভিউ (POS ও শপ)', 'Live Preview (POS & Shop)') }}</span>
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                {{ t('কার্ড ভিউ', 'Card View') }}
                            </span>
                        </div>

                        <!-- Mock Product Card -->
                        <div class="rounded-xl border border-slate-200/90 bg-slate-50/70 p-4 dark:border-slate-700/80 dark:bg-slate-800/60 relative overflow-hidden transition-all">
                            <!-- Image / Thumbnail -->
                            <div class="relative aspect-video w-full rounded-lg overflow-hidden bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700 flex items-center justify-center">
                                <img v-if="imagesPreviews.length" :src="imagesPreviews[primaryImageIndex] || imagesPreviews[0]"
                                    class="h-full w-full object-cover">
                                <div v-else class="text-center p-4">
                                    <CubeIcon class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-600" />
                                    <span class="mt-1 block text-[10px] font-medium text-slate-400 dark:text-slate-500">
                                        {{ t('ছবি নেই', 'No image') }}
                                    </span>
                                </div>

                                <!-- Status Badge inside preview -->
                                <span class="absolute top-2 right-2 rounded-md px-2 py-0.5 text-[10px] font-bold shadow-xs"
                                    :class="form.status ? 'bg-emerald-600 text-white' : 'bg-slate-500 text-white'">
                                    {{ form.status ? t('সক্রিয়', 'Active') : t('নিষ্ক্রিয়', 'Inactive') }}
                                </span>
                            </div>

                            <!-- Meta details -->
                            <div class="mt-3 space-y-1.5">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400 truncate max-w-[65%]">
                                        {{ selectedCategoryName || t('সাধারণ ক্যাটাগরি', 'Category') }}
                                    </span>
                                    <span v-if="selectedBrandName" class="text-slate-500 dark:text-slate-400 text-[10px] font-medium">
                                        {{ selectedBrandName }}
                                    </span>
                                </div>

                                <h4 class="text-sm font-bold text-slate-900 dark:text-white line-clamp-2 leading-tight">
                                    {{ form.name || t('পণ্যের নাম এখানে দেখাবে...', 'Product name will appear here...') }}
                                </h4>

                                <div class="flex items-center gap-2 text-[10px] font-mono text-slate-500 dark:text-slate-400">
                                    <span v-if="form.sku" class="bg-white dark:bg-slate-700 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-600">
                                        SKU: {{ form.sku }}
                                    </span>
                                    <span v-if="form.barcode" class="bg-white dark:bg-slate-700 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-600">
                                        BAR: {{ form.barcode }}
                                    </span>
                                </div>

                                <div class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-700/80 flex items-baseline justify-between">
                                    <div>
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ t('মূল্য', 'Price') }}:</span>
                                        <span class="text-base font-black text-slate-900 dark:text-white ml-1">
                                            ৳ {{ formattedSellingPrice }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">
                                        / {{ selectedUnitShortName || t('একক', 'Unit') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress / Readiness Checklist -->
                        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 space-y-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                                {{ t('প্রয়োজনীয় তথ্য পূরণ অবস্থা', 'Required Fields Checklist') }}
                            </span>
                            
                            <div class="space-y-1 text-xs">
                                <div class="flex items-center justify-between" :class="form.name ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-400'">
                                    <span>{{ t('পণ্যের নাম', 'Product Name') }}</span>
                                    <span>{{ form.name ? '✓' : '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between" :class="form.category_id ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-400'">
                                    <span>{{ t('ক্যাটাগরি', 'Category') }}</span>
                                    <span>{{ form.category_id ? '✓' : '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between" :class="form.unit_id ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-400'">
                                    <span>{{ t('একক', 'Unit') }}</span>
                                    <span>{{ form.unit_id ? '✓' : '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between" :class="(form.selling_price !== '' && form.selling_price >= 0) ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-400'">
                                    <span>{{ t('বিক্রয় মূল্য', 'Selling Price') }}</span>
                                    <span>{{ (form.selling_price !== '' && form.selling_price >= 0) ? '✓' : '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between" :class="form.sku ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-400'">
                                    <span>{{ t('SKU কোড', 'SKU Code') }}</span>
                                    <span>{{ form.sku ? '✓' : '—' }}</span>
                                </div>
                            </div>

                            <div v-if="isRequiredComplete" class="mt-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 p-2.5 text-center text-xs font-bold text-emerald-700 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-900/50 flex items-center justify-center gap-1.5">
                                <CheckCircleIcon class="h-4 w-4 shrink-0" />
                                <span>{{ t('সব আবশ্যক তথ্য প্রস্তুত! সংরক্ষণ করুন।', 'All required fields ready to save!') }}</span>
                            </div>
                            <div v-else class="mt-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 p-2.5 text-center text-xs font-medium text-amber-700 dark:text-amber-400 border border-amber-200/60 dark:border-amber-900/50 flex items-center justify-center gap-1.5">
                                <InformationCircleIcon class="h-4 w-4 shrink-0" />
                                <span>{{ t('উপরের আবশ্যকীয় তথ্যগুলো পূরণ করুন', 'Please fill the required fields') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- STATUS & AVAILABILITY CARD -->
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ t('পণ্য অবস্থা (Status)', 'Product Status') }}
                                </h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ form.status ? t('POS ও বিক্রয় তালিকায় দৃশ্যমান থাকবে', 'Visible in POS and Sales') : t('খসড়া হিসেবে সংরক্ষিত থাকবে', 'Saved as hidden draft') }}
                                </p>
                            </div>

                            <!-- Modern Switch Toggle -->
                            <button type="button" @click="form.status = !form.status"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                                :class="form.status ? 'bg-indigo-600' : 'bg-slate-300 dark:bg-slate-700'"
                                role="switch" :aria-checked="form.status">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="form.status ? 'translate-x-5' : 'translate-x-0'" />
                            </button>
                        </div>

                        <!-- Big Save Button in Card -->
                        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="submit" :disabled="form.processing"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-xs font-black text-white shadow-sm hover:bg-indigo-500 active:scale-98 disabled:opacity-60 transition-all">
                                <span v-if="form.processing" class="h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                <SparklesIcon v-else class="h-4 w-4" />
                                <span>{{ form.processing ? t('সংরক্ষণ হচ্ছে...', 'Saving Product...') : t('পণ্য সংরক্ষণ করুন', 'Save Product') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- HELPFUL TIPS CARD -->
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/60 text-xs space-y-2 text-slate-600 dark:text-slate-400">
                        <div class="font-bold text-slate-900 dark:text-slate-200 flex items-center gap-1.5">
                            <InformationCircleIcon class="h-4 w-4 text-indigo-500" />
                            <span>{{ t('সহজ টিপস', 'Quick Tips') }}</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-[11px] text-slate-500 dark:text-slate-400">
                            <li>{{ t('শুধুমাত্র ১ম কার্ডের তথ্য পূরণ করেই দ্রুত পণ্য সেভ করতে পারবেন।', 'You can quickly save by completing only Card 1.') }}</li>
                            <li>{{ t('ছবি দিতে সরাসরি ব্রাউজারে যেকোনো জায়গা থেকে কপি করে Ctrl+V চাপুন।', 'Paste images instantly using Ctrl+V.') }}</li>
                            <li>{{ t('পরে পণ্য সম্পাদনা থেকে যেকোনো সময় তথ্য আপডেট করা যাবে।', 'All details can be edited anytime later.') }}</li>
                        </ul>
                    </div>

                </div>

            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ArrowLeftIcon,
    PhotoIcon,
    TrashIcon,
    ArrowPathIcon,
    StarIcon,
    PlusIcon,
    CheckCircleIcon,
    InformationCircleIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    SparklesIcon,
    CubeIcon,
    QrCodeIcon
} from '@heroicons/vue/24/outline';
import InputError from '@/Components/InputError.vue';
import { useLanguage } from '@/composables/useLanguage';

const { t } = useLanguage();

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    },
    brands: {
        type: Array,
        default: () => []
    },
    units: {
        type: Array,
        default: () => []
    }
});

// Form State with sensible defaults
const form = useForm({
    name: '',
    sku: '',
    barcode: '',
    category_id: '',
    brand_id: '',
    unit_id: '',
    selling_price: '',
    alert_quantity: 5,
    description: '',
    specifications: {},
    images: [],
    primary_image_index: 0,
    status: true
});

const isAdvancedOpen = ref(false);
const specifications = ref([{ key: '', value: '' }]);
const primaryImageIndex = ref(0);

const addSpecification = () => {
    specifications.value.push({ key: '', value: '' });
};

const removeSpecification = (index) => {
    specifications.value.splice(index, 1);
};

const imagesPreviews = ref([]);
const selectedImages = ref([]);

function addImageFiles(files) {
    const validFiles = files.filter(file => {
        if (file.size > 10 * 1024 * 1024) {
            alert(t('ফাইলের আকার সর্বোচ্চ ১০ মেগাবাইট হতে পারবে', 'Maximum file size is 10MB'));
            return false;
        }
        if (!file.type.startsWith('image/')) {
            alert(t('অনুগ্রহ করে ছবি নির্বাচন করুন', 'Please select an image file'));
            return false;
        }
        return true;
    });

    validFiles.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            imagesPreviews.value.push(e.target.result);
        };
        reader.readAsDataURL(file);
        selectedImages.value.push(file);
    });

    form.images = [...form.images, ...validFiles];

    if ((primaryImageIndex.value === null || primaryImageIndex.value === undefined) && imagesPreviews.value.length > 0) {
        primaryImageIndex.value = 0;
        form.primary_image_index = 0;
    }
}

const onImagesSelected = (event) => {
    const files = Array.from(event.target.files);
    addImageFiles(files);
};

const onFilesDrop = (event) => {
    const files = Array.from(event.dataTransfer?.files || []);
    if (files.length) {
        addImageFiles(files);
    }
};

const onPasteImages = (event) => {
    const items = Array.from(event.clipboardData?.items || []);
    const files = items
        .filter(i => i.kind === 'file' && i.type?.startsWith('image/'))
        .map(i => i.getAsFile())
        .filter(Boolean);

    if (files.length) {
        addImageFiles(files);
    }
};

const removeImage = (index) => {
    imagesPreviews.value.splice(index, 1);
    selectedImages.value.splice(index, 1);
    form.images.splice(index, 1);

    if (primaryImageIndex.value === index) {
        primaryImageIndex.value = imagesPreviews.value.length > 0 ? 0 : null;
        form.primary_image_index = primaryImageIndex.value ?? 0;
    } else if (primaryImageIndex.value > index) {
        primaryImageIndex.value--;
        form.primary_image_index = primaryImageIndex.value;
    }
};

const setPrimaryImage = (index) => {
    primaryImageIndex.value = index;
    form.primary_image_index = index;
};

// Generates numeric barcode
const generateBarcode = () => {
    const randomDigits = Array.from({ length: 8 }, () => Math.floor(Math.random() * 10));
    form.barcode = randomDigits.join('');
};

// Generates clean SKU with fallback to PRD prefix
const generateSku = () => {
    const cleanLetters = (form.name || '')
        .replace(/[^a-zA-Z0-9]/g, '')
        .slice(0, 4)
        .toUpperCase();

    const prefix = cleanLetters.length >= 2 ? cleanLetters : 'PRD';
    const randomNum = Math.floor(1000 + Math.random() * 9000);
    form.sku = `${prefix}-${randomNum}`;
};

// Auto generate barcode when name is typed if empty
watch(() => form.name, (newValue) => {
    if (newValue && !form.barcode) {
        generateBarcode();
    }
    if (newValue && !form.sku) {
        generateSku();
    }
}, { immediate: false });

// Helper computed values for Live Preview
const selectedCategoryName = computed(() => {
    const cat = props.categories.find(c => c.id == form.category_id);
    return cat ? cat.name : '';
});

const selectedBrandName = computed(() => {
    const brand = props.brands.find(b => b.id == form.brand_id);
    return brand ? brand.name : '';
});

const selectedUnitShortName = computed(() => {
    const unit = props.units.find(u => u.id == form.unit_id);
    return unit ? (unit.short_name || unit.name) : '';
});

const formattedSellingPrice = computed(() => {
    if (form.selling_price === '' || form.selling_price === null || form.selling_price === undefined) {
        return '0.00';
    }
    const num = parseFloat(form.selling_price);
    return isNaN(num) ? '0.00' : num.toFixed(2);
});

// Checklist completion check
const isRequiredComplete = computed(() => {
    return Boolean(
        form.name &&
        form.category_id &&
        form.unit_id &&
        form.selling_price !== '' &&
        form.selling_price !== null &&
        Number(form.selling_price) >= 0 &&
        form.sku
    );
});

const skuPlaceholder = computed(() =>
    form.name ? t('যেমনঃ PRD-4921', 'e.g. PRD-4921') : t('পণ্যের নাম লিখুন অথবা তৈরি বাটনে চাপুন', 'Enter product name or click generate')
);

const barcodePlaceholder = computed(() =>
    form.name ? t('যেমনঃ 84920194', 'e.g. 84920194') : t('বারকোড লিখুন বা অটো কোড বাটনে চাপুন', 'Enter barcode or click Auto')
);

const submit = () => {
    const specsObject = specifications.value.reduce((acc, spec) => {
        if (spec.key && spec.value) {
            acc[spec.key] = spec.value;
        }
        return acc;
    }, {});

    form.specifications = specsObject;

    form.post(route('admin.products.store'), {
        onSuccess: () => {
            specifications.value = [{ key: '', value: '' }];
            imagesPreviews.value = [];
            selectedImages.value = [];
        },
        preserveScroll: true,
        forceFormData: true
    });
};
</script>
