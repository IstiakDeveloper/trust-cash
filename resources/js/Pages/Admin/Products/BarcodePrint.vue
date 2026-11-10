<template>
    <div class="min-h-screen bg-slate-100 p-4 sm:p-6 dark:bg-slate-900 print:bg-white print:p-0">
        <!-- Top Toolbar & Settings (Hidden during print) -->
        <div class="mx-auto max-w-5xl mb-6 space-y-3 print:hidden">
            <!-- Main Action Bar -->
            <div class="rounded-2xl bg-white p-4 shadow-sm border border-slate-200 dark:bg-slate-800 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                        <QrCodeIcon class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-base font-bold text-slate-900 dark:text-white">
                                {{ t('বারকোড প্রিন্ট প্রিভিউ', 'Barcode Print Preview') }}
                            </h1>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60">
                                {{ currentSizeLabel }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ t('মোট পণ্য:', 'Products:') }} <span class="font-bold text-slate-800 dark:text-slate-200">{{ formatNumber(products.length) }}</span> | 
                            {{ t('মোট স্টিকার লেবেল:', 'Total Stickers:') }} <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ formatNumber(totalLabels) }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Quick Paper Size Dropdown -->
                    <div class="flex items-center gap-1.5">
                        <label class="text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">
                            {{ t('সাইজ:', 'Size:') }}
                        </label>
                        <select
                            v-model="selectedPaperSize"
                            class="rounded-xl border border-slate-300 bg-white py-1.5 pl-3 pr-8 text-xs font-bold text-slate-800 shadow-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                        >
                            <option value="38x25mm">{{ t('৩৮ × ২৫ মিমি (১-কলাম থার্মাল স্টিকার)', '38mm x 25mm (Single Thermal)') }}</option>
                            <option value="38x25mm_2col">{{ t('৩৮ × ২৫ মিমি (২-কলাম রোল / ৮০ মিমি)', '38mm x 25mm (2-Col on 80mm)') }}</option>
                            <option value="40x30mm">{{ t('৪০ × ৩০ মিমি থার্মাল স্টিকার', '40mm x 30mm Thermal') }}</option>
                            <option value="50x25mm">{{ t('৫০ × ২৫ মিমি (২" × ১" থার্মাল)', '50mm x 25mm Thermal') }}</option>
                            <option value="50x30mm">{{ t('৫০ × ৩০ মিমি থার্মাল স্টিকার', '50mm x 30mm Thermal') }}</option>
                            <option value="80mm">{{ t('৮০ মিমি থার্মাল রোল (Continuous Roll)', '80mm Continuous Roll') }}</option>
                            <option value="A4">{{ t('A4 শিট (মাল্টি-কলাম গ্রিড)', 'A4 Multi-label Sheet') }}</option>
                            <option value="Letter">{{ t('Letter সাইজ শিট', 'Letter Sheet') }}</option>
                            <option value="custom">{{ t('✨ কাস্টম সাইজ (Custom mm - নিজের সাইজ দিন)', '✨ Custom Size (Enter Custom mm)') }}</option>
                        </select>
                    </div>

                    <!-- Save as Default Button -->
                    <button
                        type="button"
                        @click="saveSettingsAsDefault"
                        :class="['inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-bold transition-all cursor-pointer', savedToast ? 'border-emerald-600 bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'border-indigo-200 bg-indigo-50/70 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900']"
                        :title="t('বর্তমান সকল সাইজ ও রোটেশন সেটিংস ডিফল্ট হিসেবে সংরক্ষণ করুন', 'Save current size and rotation as your permanent default')"
                    >
                        <BookmarkIcon class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                        <span>{{ savedToast ? t('✓ ডিফল্ট সেভ হয়েছে!', '✓ Saved!') : t('ডিফল্ট সেভ করুন', 'Save as Default') }}</span>
                    </button>

                    <!-- Customize Settings Toggle -->
                    <button
                        type="button"
                        @click="showSettings = !showSettings"
                        :class="['inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-bold transition-all cursor-pointer', showSettings ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700']"
                    >
                        <AdjustmentsHorizontalIcon class="h-4 w-4" />
                        <span>{{ t('কাস্টমাইজ', 'Customize') }}</span>
                    </button>

                    <!-- Printer Tips Toggle -->
                    <button
                        type="button"
                        @click="showGuide = !showGuide"
                        :class="['inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-bold transition-all cursor-pointer', showGuide ? 'border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700']"
                    >
                        <InformationCircleIcon class="h-4 w-4" />
                        <span>{{ t('প্রিন্টার গাইড', 'Print Tips') }}</span>
                    </button>

                    <!-- Print Button -->
                    <button
                        type="button"
                        @click="triggerPrint"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition-all cursor-pointer"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        <span>{{ t('এখনই প্রিন্ট করুন', 'Print Now') }}</span>
                    </button>

                    <!-- Back to Products -->
                    <Link
                        :href="route('admin.products.index')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-all"
                    >
                        <ArrowLeftIcon class="h-4 w-4" />
                        <span class="hidden sm:inline">{{ t('পণ্য তালিকা', 'Products') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Direction, Rotation & Page Size Rotation Toolbar -->
            <div class="rounded-2xl bg-white p-3.5 shadow-sm border border-slate-200 dark:bg-slate-800 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3 text-xs">
                <!-- Page Size Rotation Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                        <ArrowPathIcon class="h-4 w-4 text-indigo-600" />
                        {{ t('পেজ সাইজ রোটেশন:', 'Page Size Rotation:') }}
                    </span>
                    <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600">
                        <!-- 0 Degree (Normal Base Dimensions) -->
                        <button
                            type="button"
                            @click="setRotation(0)"
                            :class="['px-3 py-1 rounded-lg font-bold transition-all cursor-pointer', Number(rotation) === 0 ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-600']"
                            :title="t(`স্বাভাবিক সোজা প্রিন্ট: ${baseW}×${baseH} মিমি`, `Normal: ${baseW}x${baseH}mm`)"
                        >
                            0° সোজা ({{ baseW }}×{{ baseH }} mm)
                        </button>

                        <!-- 90 Degree (Page Size & Content Both Rotate 90°) -->
                        <button
                            type="button"
                            @click="setRotation(90)"
                            :class="['px-3 py-1 rounded-lg font-bold transition-all cursor-pointer flex items-center gap-1.5', Number(rotation) === 90 ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-600']"
                            :title="t(`বারকোড ও পেজ সহ ৯০° রোটেট: ${baseH}×${baseW} মিমি`, `Rotate content & page 90°: ${baseH}x${baseW}mm`)"
                        >
                            <span>90° রোটেট ({{ baseH }}×{{ baseW }} mm)</span>
                            <span v-if="Number(rotation) === 90" class="rounded bg-indigo-700 px-1 text-[9px] text-white">Active</span>
                        </button>

                        <!-- 180 Degree (Inverted 180) -->
                        <button
                            type="button"
                            @click="setRotation(180)"
                            :class="['px-3 py-1 rounded-lg font-bold transition-all cursor-pointer', Number(rotation) === 180 ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-600']"
                            :title="t('১৮০° উল্টো প্রিন্ট', 'Inverted 180 degree rotation')"
                        >
                            180° উল্টো
                        </button>

                        <!-- 270 Degree (Page Size & Content Rotate 270°) -->
                        <button
                            type="button"
                            @click="setRotation(270)"
                            :class="['px-3 py-1 rounded-lg font-bold transition-all cursor-pointer', Number(rotation) === 270 ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-600']"
                            :title="t(`বারকোড ও পেজ সহ ২৭০° রোটেট: ${baseH}×${baseW} মিমি`, `Rotate content & page 270°: ${baseH}x${baseW}mm`)"
                        >
                            270° ({{ baseH }}×{{ baseW }} mm)
                        </button>
                    </div>
                </div>

                <!-- Dimension Swap & Layout Controls -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Swap W <-> H Button -->
                    <button
                        type="button"
                        @click="toggleSwapDimensions"
                        :class="['inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 font-bold transition-all cursor-pointer', isSwapped ? 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-200 dark:border-amber-700' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 dark:bg-slate-700/40 dark:text-slate-200 dark:border-slate-600']"
                        :title="t('প্রস্থ ও উচ্চতা অদলবদল করুন', 'Swap width and height dimensions')"
                    >
                        <ArrowsRightLeftIcon class="h-4 w-4 text-indigo-600" />
                        <span>{{ t('সাইজ অদলবদল', 'Swap W ⇄ H') }} ({{ effectiveWidth }}×{{ effectiveHeight }} mm)</span>
                    </button>

                    <!-- Page Orientation Dropdown -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ t('লেআউট:', 'Layout:') }}</span>
                        <select
                            v-model="pageOrientation"
                            class="rounded-xl border border-slate-300 bg-white py-1 px-2.5 text-xs font-bold text-slate-800 shadow-xs focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                        >
                            <option value="auto">Auto ({{ t('স্বয়ংক্রিয়', 'Auto') }})</option>
                            <option value="portrait">Portrait ({{ t('লম্বালম্বি', 'Portrait') }})</option>
                            <option value="landscape">Landscape ({{ t('আড়াআড়ি', 'Landscape') }})</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Saved Toast Notification -->
            <div v-if="savedToast" class="rounded-xl bg-emerald-50 p-2.5 border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900/50 flex items-center justify-between gap-2 text-xs text-emerald-900 dark:text-emerald-200 transition-all">
                <div class="flex items-center gap-2">
                    <CheckCircleIcon class="h-5 w-5 text-emerald-600 shrink-0" />
                    <span class="font-bold">
                        {{ t(`ডিফল্ট পেজ সাইজ সফলভাবে সংরক্ষিত হয়েছে (${effectiveWidth}×${effectiveHeight} মিমি, রোটেশন: ${rotation}°)! পরবর্তী সময়ে এটিই কার্যকর থাকবে।`, `Default settings saved successfully (${effectiveWidth}x${effectiveHeight}mm, ${rotation}°)!`) }}
                    </span>
                </div>
                <button type="button" @click="savedToast = false" class="text-emerald-600 hover:text-emerald-800">
                    <XMarkIcon class="h-4 w-4" />
                </button>
            </div>

            <!-- Reset Toast Notification -->
            <div v-if="resetToast" class="rounded-xl bg-amber-50 p-2.5 border border-amber-200 dark:bg-amber-950/40 dark:border-amber-900/50 flex items-center justify-between gap-2 text-xs text-amber-900 dark:text-amber-200 transition-all">
                <div class="flex items-center gap-2">
                    <CheckCircleIcon class="h-5 w-5 text-amber-600 shrink-0" />
                    <span class="font-bold">
                        {{ t('ডিফল্ট সেটিংস সফলভাবে রিসেট করা হয়েছে!', 'Default settings reset successfully!') }}
                    </span>
                </div>
                <button type="button" @click="resetToast = false" class="text-amber-600 hover:text-amber-800">
                    <XMarkIcon class="h-4 w-4" />
                </button>
            </div>

            <!-- Helpful Rotation Banner when 90° is active -->
            <div v-if="Number(rotation) === 90" class="rounded-xl bg-indigo-50/90 p-2.5 border border-indigo-200/80 dark:bg-indigo-950/30 dark:border-indigo-900/40 flex items-center gap-2 text-xs text-indigo-900 dark:text-indigo-200">
                <InformationCircleIcon class="h-4 w-4 text-indigo-600 shrink-0" />
                <span>{{ t(`💡 ৯০° রোটেশন সক্রিয়: বারকোড ও পেজ সাইজ সহ ঘুরে ${effectiveWidth}×${effectiveHeight} মিমি হিসেবে রোটেট হয়েছে।`, `💡 90° rotation active: content and paper size are rotated to ${effectiveWidth}x${effectiveHeight}mm!`) }}</span>
            </div>

            <!-- Custom Size Inputs & Quick mm Presets (When selectedPaperSize === 'custom') -->
            <div v-if="selectedPaperSize === 'custom'" class="rounded-2xl bg-white p-4 shadow-sm border border-indigo-200 dark:bg-slate-800 dark:border-indigo-900/50 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-400 font-black text-xs">
                            mm
                        </span>
                        <div>
                            <span class="font-bold text-slate-900 dark:text-white">
                                {{ t('কাস্টম সাইজ নির্ধারণ (মিমি):', 'Custom Dimensions (mm):') }}
                            </span>
                            <span class="ml-2 rounded-md bg-indigo-50 px-2 py-0.5 text-indigo-700 font-bold dark:bg-indigo-950 dark:text-indigo-300">
                                {{ effectiveWidth }}mm × {{ effectiveHeight }}mm
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <label class="font-bold text-slate-700 dark:text-slate-300">{{ t('প্রস্থ (Width):', 'Width:') }}</label>
                            <input
                                type="number"
                                v-model.number="customWidth"
                                min="15"
                                max="250"
                                class="w-18 rounded-lg border border-slate-300 bg-white py-1 px-2 text-center font-bold text-slate-900 focus:ring-1 focus:ring-indigo-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            />
                            <span class="text-slate-400 font-bold">mm</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <label class="font-bold text-slate-700 dark:text-slate-300">{{ t('উচ্চতা (Height):', 'Height:') }}</label>
                            <input
                                type="number"
                                v-model.number="customHeight"
                                min="10"
                                max="250"
                                class="w-18 rounded-lg border border-slate-300 bg-white py-1 px-2 text-center font-bold text-slate-900 focus:ring-1 focus:ring-indigo-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            />
                            <span class="text-slate-400 font-bold">mm</span>
                        </div>
                    </div>
                </div>

                <!-- Quick mm Preset Buttons -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-700/60 flex flex-wrap items-center gap-1.5 text-[11px]">
                    <span class="text-slate-400 font-medium mr-1">{{ t('দ্রুত সাইজ বাছুন:', 'Quick mm Presets:') }}</span>
                    <button
                        v-for="p in commonMmPresets"
                        :key="`${p.w}x${p.h}`"
                        type="button"
                        @click="setCustomPreset(p.w, p.h)"
                        :class="['px-2.5 py-0.5 rounded-lg font-bold border transition-all cursor-pointer', (rawBaseWidth === p.w && rawBaseHeight === p.h) ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 dark:bg-slate-700/50 dark:text-slate-200 dark:border-slate-600']"
                    >
                        {{ p.w }}×{{ p.h }} mm
                    </button>
                </div>
            </div>

            <!-- Customization Settings Panel (Expandable) -->
            <div v-if="showSettings" class="rounded-2xl bg-white p-4 shadow-sm border border-slate-200 dark:bg-slate-800 dark:border-slate-700 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        {{ t('লেবেল ডিজাইন ও দৃশ্যমান সেটিংস', 'Label Content & Design Settings') }}
                    </h3>
                    <button type="button" @click="showSettings = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                    <!-- Store Name Toggle & Input -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" v-model="showStoreName" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <span>{{ t('শপের নাম দেখান', 'Show Shop Name') }}</span>
                        </label>
                        <input
                            v-if="showStoreName"
                            type="text"
                            v-model="storeName"
                            :placeholder="t('শপের নাম লিখুন...', 'Store Name...')"
                            class="w-full rounded-lg border-slate-300 bg-white py-1 px-2 text-xs font-medium dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                        />
                    </div>

                    <!-- Product Name Toggle -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" v-model="showProductName" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <span>{{ t('পণ্যের নাম দেখান', 'Show Product Name') }}</span>
                        </label>
                        <p class="text-[10px] text-slate-400">
                            {{ t('স্টিকারে ফিট করার জন্য স্বয়ংক্রিয়ভাবে সংক্ষেপিত হবে', 'Auto-truncated to fit sticker width') }}
                        </p>
                    </div>

                    <!-- Barcode Code Toggle -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" v-model="showBarcodeText" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <span>{{ t('বারকোড কোড (নম্বর) দেখান', 'Show Barcode Number') }}</span>
                        </label>
                        <p class="text-[10px] text-slate-400">
                            {{ t('বারকোড ইমেজের নিচে কোড নম্বর প্রিন্ট হবে', 'Prints numeric code below barcode') }}
                        </p>
                    </div>

                    <!-- Price Toggle & Prefix -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" v-model="showPrice" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <span>{{ t('বিক্রয় মূল্য দেখান', 'Show Selling Price') }}</span>
                        </label>
                        <div v-if="showPrice" class="flex items-center gap-1.5">
                            <span class="text-[11px] text-slate-500">{{ t('প্রিফিক্স:', 'Prefix:') }}</span>
                            <input
                                type="text"
                                v-model="pricePrefix"
                                placeholder="MRP: ৳ "
                                class="w-full rounded-lg border-slate-300 bg-white py-1 px-2 text-xs font-bold dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                            />
                        </div>
                    </div>

                    <!-- Label Border Toggle -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" v-model="showBorder" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <span>{{ t('প্রিন্টে বর্ডার দেখান', 'Print Label Border') }}</span>
                        </label>
                        <p class="text-[10px] text-slate-400">
                            {{ t('থার্মাল রোলের জন্য সাধারণত বর্ডার আনচেক রাখা ভালো', 'Recommended OFF for pre-cut thermal labels') }}
                        </p>
                    </div>

                    <!-- Screen Zoom Control -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="block font-bold text-slate-800 dark:text-slate-200">
                            {{ t('স্ক্রিন প্রিভিউ জুম', 'Screen Preview Zoom') }}
                        </label>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="previewScale = 1"
                                :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', previewScale === 1 ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200']"
                            >
                                100% (Real)
                            </button>
                            <button
                                type="button"
                                @click="previewScale = 1.3"
                                :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', previewScale === 1.3 ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200']"
                            >
                                130%
                            </button>
                            <button
                                type="button"
                                @click="previewScale = 1.6"
                                :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', previewScale === 1.6 ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200']"
                            >
                                160%
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Settings Panel Footer Actions -->
                <div class="border-t border-slate-100 dark:border-slate-700 pt-3 flex flex-wrap items-center justify-between gap-2">
                    <button
                        type="button"
                        @click="saveSettingsAsDefault"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-all cursor-pointer"
                    >
                        <BookmarkIcon class="h-4 w-4" />
                        <span>{{ savedToast ? t('✓ ডিফল্ট হিসেবে সংরক্ষিত হয়েছে!', '✓ Default Saved!') : t('এই সকল সেটিংস ডিফল্ট হিসেবে সেভ করুন', 'Save Settings as Default') }}</span>
                    </button>

                    <button
                        type="button"
                        @click="resetToFactoryDefaults"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 transition-all cursor-pointer"
                    >
                        <ArrowPathIcon class="h-4 w-4" />
                        <span>{{ t('ফ্যাক্টরি ডিফল্টে রিসেট করুন', 'Reset to Factory Defaults') }}</span>
                    </button>
                </div>
            </div>

            <!-- Thermal Printer Instruction Guide (Expandable) -->
            <div v-if="showGuide" class="rounded-2xl bg-amber-50/80 p-4 shadow-sm border border-amber-200 dark:bg-amber-950/30 dark:border-amber-900/50">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-2 text-xs text-amber-950 dark:text-amber-200">
                        <div class="flex items-center gap-2 font-bold text-sm text-amber-900 dark:text-amber-300">
                            <InformationCircleIcon class="h-5 w-5 text-amber-600 shrink-0" />
                            <span>{{ t('Gprinter / থার্মাল প্রিন্টারে (৩৮×২৫ মিমি) নিখুঁত প্রিন্ট করার গাইড', 'Gprinter / Thermal Printer Perfect Print Guide (38x25mm)') }}</span>
                        </div>
                        <p class="text-amber-800 dark:text-amber-300">
                            {{ t('Gprinter GP-3120TUC বা থার্মাল প্রিন্টারে ফুল পেজে সোজা প্রিন্ট পেতে নিচের সেটিংসগুলো ঠিক রাখুন:', 'Keep these settings to print full page straight labels on Gprinter GP-3120TUC:') }}
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 pt-1 font-medium">
                            <div class="bg-white/80 dark:bg-slate-900/60 p-2.5 rounded-xl border border-amber-200/60 dark:border-amber-800/40">
                                <span class="font-bold text-amber-900 dark:text-amber-200">১. Windows ড্রাইভার পেপার সাইজ (গুরুত্বপূর্ণ):</span>
                                <p class="text-slate-600 dark:text-slate-300 mt-0.5">Control Panel > Printers > Gprinter 3120TUC > Printing Preferences > Page Setup এ গিয়ে একটি নতুন সাইজ তৈরি করুন: প্রস্থ 38mm, উচ্চতা 25mm, Margins: 0mm।</p>
                            </div>
                            <div class="bg-white/80 dark:bg-slate-900/60 p-2.5 rounded-xl border border-amber-200/60 dark:border-amber-800/40">
                                <span class="font-bold text-amber-900 dark:text-amber-200">২. Margins (মার্জিন - ১ কোনায় যাওয়া ঠেকায়):</span>
                                <p class="text-emerald-700 dark:text-emerald-400 font-bold mt-0.5">ব্রাউজার প্রিন্ট ডায়ালগে Margins অবশ্যই "None" দিন। Default রাখলে ১০ মিমি মার্জিনের কারণে বারকোড ১ কোনায় চলে যায়!</p>
                            </div>
                            <div class="bg-white/80 dark:bg-slate-900/60 p-2.5 rounded-xl border border-amber-200/60 dark:border-amber-800/40">
                                <span class="font-bold text-amber-900 dark:text-amber-200">৩. পেপার সাইজ ও স্কেল:</span>
                                <p class="text-slate-600 dark:text-slate-300 mt-0.5">Paper Size এ আপনার তৈরি করা "38x25" সিলেক্ট করুন এবং Scale: "Default" (100%) রাখুন।</p>
                            </div>
                            <div class="bg-white/80 dark:bg-slate-900/60 p-2.5 rounded-xl border border-amber-200/60 dark:border-amber-800/40">
                                <span class="font-bold text-amber-900 dark:text-amber-200">৪. গ্যাপ সেন্সর ক্যালিব্রেশন (যদি স্টিকার স্কিপ করে):</span>
                                <p class="text-slate-600 dark:text-slate-300 mt-0.5">প্রিন্টারের পাওয়ার অফ করুন। FEED বাটন চেপে ধরে রেখে পাওয়ার অন করুন। ৩টি স্টিকার বের হয়ে নিজে থেকেই থামবে।</p>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="showGuide = false" class="text-amber-500 hover:text-amber-700">
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Barcode Sheet & Stickers Area -->
        <div class="mx-auto max-w-5xl print:max-w-none print:w-full print:m-0 print:p-0 print:block flex justify-center">
            <div
                :class="['barcode-container', currentModeClass]"
                :style="previewContainerStyle"
            >
                <template v-for="product in products" :key="product.id">
                    <template v-for="copy in (product.copies || 1)" :key="`${product.id}-${copy}`">
                        <div
                            :class="[
                                'label-wrapper',
                                currentSizeClass,
                                showBorder ? 'has-print-border' : 'no-print-border'
                            ]"
                            :style="computedWrapperStyle"
                        >
                            <!-- Label Content (Naturally fills the rotated page size) -->
                            <div class="label-content" :style="computedContentStyle">
                                <!-- Store Name -->
                                <div
                                    v-if="showStoreName && storeName"
                                    class="lbl-store"
                                    :style="computedStoreStyle"
                                    :title="storeName"
                                >
                                    {{ storeName }}
                                </div>

                                <!-- Product Name -->
                                <div
                                    v-if="showProductName"
                                    class="lbl-name"
                                    :style="computedNameStyle"
                                    :title="product.name"
                                >
                                    {{ truncate(product.name, computedMaxNameLength) }}
                                </div>

                                <!-- Barcode Image (Crisp SVG) -->
                                <div class="lbl-barcode-wrapper">
                                    <img
                                        :src="product.image_url"
                                        :alt="product.barcode"
                                        class="lbl-barcode-img"
                                        :style="computedBarcodeStyle"
                                    />
                                </div>

                                <!-- Barcode Number / Code -->
                                <div
                                    v-if="showBarcodeText"
                                    class="lbl-code"
                                    :style="computedCodeStyle"
                                >
                                    {{ product.barcode }}
                                </div>

                                <!-- Price -->
                                <div
                                    v-if="showPrice"
                                    class="lbl-price"
                                    :style="computedPriceStyle"
                                >
                                    <span>{{ pricePrefix }}</span>{{ formatPrice(product.price) }}
                                </div>
                            </div>
                        </div>
                    </template>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
    PrinterIcon,
    ArrowLeftIcon,
    QrCodeIcon,
    AdjustmentsHorizontalIcon,
    InformationCircleIcon,
    XMarkIcon,
    ArrowPathIcon,
    ArrowsRightLeftIcon,
    BookmarkIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline'
import { useLanguage } from '@/composables/useLanguage'

const { t, formatNumber } = useLanguage()
const page = usePage()

const props = defineProps({
    products: {
        type: Array,
        required: true,
        default: () => []
    },
    paperSize: {
        type: String,
        default: '38x25mm'
    },
    initialCustomWidth: {
        type: Number,
        default: 38
    },
    initialCustomHeight: {
        type: Number,
        default: 25
    },
    initialRotation: {
        type: Number,
        default: 0
    },
    initialOrientation: {
        type: String,
        default: 'auto'
    }
})

// UI & Customization State (Loaded from localStorage if saved)
const savedPaperSize = localStorage.getItem('trustcash_barcode_papersize')
const selectedPaperSize = ref(
    props.paperSize && props.paperSize !== '38x25mm'
        ? props.paperSize
        : (savedPaperSize || props.paperSize || '38x25mm')
)

const customWidth = ref(Number(localStorage.getItem('trustcash_barcode_custom_w')) || props.initialCustomWidth || 38)
const customHeight = ref(Number(localStorage.getItem('trustcash_barcode_custom_h')) || props.initialCustomHeight || 25)

// Rotation & Orientation State
const rotation = ref(
    localStorage.getItem('trustcash_barcode_rotation') !== null
        ? Number(localStorage.getItem('trustcash_barcode_rotation'))
        : (Number(props.initialRotation) || 0)
)
const pageOrientation = ref(localStorage.getItem('trustcash_barcode_orientation') || props.initialOrientation || 'auto')
const isSwapped = ref(localStorage.getItem('trustcash_barcode_swapped') === 'true')

const showSettings = ref(false)
const showGuide = ref(false)
const savedToast = ref(false)
const resetToast = ref(false)
const previewScale = ref(Number(localStorage.getItem('trustcash_barcode_preview_scale')) || 1)

// Quick Presets
const commonMmPresets = [
    { w: 35, h: 25 },
    { w: 38, h: 25 },
    { w: 40, h: 20 },
    { w: 40, h: 30 },
    { w: 50, h: 25 },
    { w: 50, h: 30 },
    { w: 50, h: 35 },
    { w: 60, h: 40 },
    { w: 70, h: 50 },
    { w: 100, h: 150 }
]

const setCustomPreset = (w, h) => {
    customWidth.value = w
    customHeight.value = h
    localStorage.setItem('trustcash_barcode_custom_w', w)
    localStorage.setItem('trustcash_barcode_custom_h', h)
    updatePrintStyleTag()
}

const setRotation = (deg) => {
    rotation.value = Number(deg)
    localStorage.setItem('trustcash_barcode_rotation', String(deg))
    updatePrintStyleTag()
}

const toggleSwapDimensions = () => {
    isSwapped.value = !isSwapped.value
    localStorage.setItem('trustcash_barcode_swapped', isSwapped.value ? 'true' : 'false')
    updatePrintStyleTag()
}

// Content Toggles (Loaded from localStorage if saved)
const showStoreName = ref(
    localStorage.getItem('trustcash_barcode_show_store') !== null
        ? localStorage.getItem('trustcash_barcode_show_store') === 'true'
        : true
)
const storeName = ref(
    localStorage.getItem('trustcash_barcode_store_name') !== null
        ? localStorage.getItem('trustcash_barcode_store_name')
        : (page.props.business_name || page.props.app_name || '')
)
const showProductName = ref(
    localStorage.getItem('trustcash_barcode_show_name') !== null
        ? localStorage.getItem('trustcash_barcode_show_name') === 'true'
        : true
)
const showBarcodeText = ref(
    localStorage.getItem('trustcash_barcode_show_code') !== null
        ? localStorage.getItem('trustcash_barcode_show_code') === 'true'
        : true
)
const showPrice = ref(
    localStorage.getItem('trustcash_barcode_show_price') !== null
        ? localStorage.getItem('trustcash_barcode_show_price') === 'true'
        : true
)
const pricePrefix = ref(
    localStorage.getItem('trustcash_barcode_price_prefix') !== null
        ? localStorage.getItem('trustcash_barcode_price_prefix')
        : 'MRP: ৳ '
)
const showBorder = ref(localStorage.getItem('trustcash_barcode_show_border') === 'true')

// Base Size Configurations (Unrotated)
const SIZES = {
    '38x25mm': {
        labelBn: '৩৮ × ২৫ মিমি (১-কলাম থার্মাল স্টিকার)',
        labelEn: '38mm x 25mm (Single Thermal Label)',
        mode: 'mode-thermal-single',
        sizeClass: 'size-38x25',
        baseWidth: 38,
        baseHeight: 25,
        maxNameLength: 22
    },
    '38x25mm_2col': {
        labelBn: '৩৮ × ২৫ মিমি (২-কলাম রোল)',
        labelEn: '38mm x 25mm (2-Column Roll)',
        mode: 'mode-thermal-2col',
        sizeClass: 'size-38x25-2col',
        baseWidth: 38,
        baseHeight: 25,
        maxNameLength: 22
    },
    '40x30mm': {
        labelBn: '৪০ × ৩০ মিমি থার্মাল স্টিকার',
        labelEn: '40mm x 30mm Thermal Label',
        mode: 'mode-thermal-single',
        sizeClass: 'size-40x30',
        baseWidth: 40,
        baseHeight: 30,
        maxNameLength: 26
    },
    '50x25mm': {
        labelBn: '৫০ × ২৫ মিমি (২" × ১")',
        labelEn: '50mm x 25mm Thermal Label',
        mode: 'mode-thermal-single',
        sizeClass: 'size-50x25',
        baseWidth: 50,
        baseHeight: 25,
        maxNameLength: 30
    },
    '50x30mm': {
        labelBn: '৫০ × ৩০ মিমি থার্মাল স্টিকার',
        labelEn: '50mm x 30mm Thermal Label',
        mode: 'mode-thermal-single',
        sizeClass: 'size-50x30',
        baseWidth: 50,
        baseHeight: 30,
        maxNameLength: 32
    },
    '80mm': {
        labelBn: '৮০ মিমি থার্মাল রোল (Continuous Roll)',
        labelEn: '80mm Continuous Roll',
        mode: 'mode-thermal-roll',
        sizeClass: 'size-80mm',
        baseWidth: 72,
        baseHeight: 40,
        maxNameLength: 40
    },
    'A4': {
        labelBn: 'A4 স্টিকার শিট',
        labelEn: 'A4 Multi-label Sheet',
        mode: 'mode-sheet',
        sizeClass: 'size-a4',
        baseWidth: 48,
        baseHeight: 32,
        maxNameLength: 28
    },
    'Letter': {
        labelBn: 'Letter সাইজ শিট',
        labelEn: 'Letter Sheet',
        mode: 'mode-sheet',
        sizeClass: 'size-a4',
        baseWidth: 48,
        baseHeight: 32,
        maxNameLength: 28
    },
    'custom': {
        labelBn: 'কাস্টম সাইজ (Custom mm)',
        labelEn: 'Custom Dimensions (mm)',
        mode: 'mode-custom',
        sizeClass: 'size-custom',
        maxNameLength: 25
    }
}

const currentConfig = computed(() => {
    return SIZES[selectedPaperSize.value] || SIZES['38x25mm']
})

const rawBaseWidth = computed(() => {
    if (selectedPaperSize.value === 'custom') {
        return Number(customWidth.value) || 38
    }
    return currentConfig.value.baseWidth || 38
})

const rawBaseHeight = computed(() => {
    if (selectedPaperSize.value === 'custom') {
        return Number(customHeight.value) || 25
    }
    return currentConfig.value.baseHeight || 25
})

// Swapped base dimensions (if user explicitly toggled Swap W <-> H)
const baseW = computed(() => {
    return isSwapped.value ? rawBaseHeight.value : rawBaseWidth.value
})

const baseH = computed(() => {
    return isSwapped.value ? rawBaseWidth.value : rawBaseHeight.value
})

// Whether 90° or 270° rotation is active
const is90or270 = computed(() => {
    const deg = Number(rotation.value) || 0
    return deg === 90 || deg === 270
})

// ACTUAL PHYSICAL PAGE DIMENSIONS (Sent directly to CSS @page size and physical wrapper element)
const effectiveWidth = computed(() => {
    return is90or270.value ? baseH.value : baseW.value
})

const effectiveHeight = computed(() => {
    return is90or270.value ? baseW.value : baseH.value
})

const currentSizeLabel = computed(() => {
    let base = `${effectiveWidth.value}×${effectiveHeight.value} mm`
    if (Number(rotation.value) !== 0) {
        base += ` (${rotation.value}° রোটেটেড)`
    }
    return base
})

const currentModeClass = computed(() => {
    return currentConfig.value.mode
})

const currentSizeClass = computed(() => {
    return currentConfig.value.sizeClass
})

const computedMaxNameLength = computed(() => {
    return Math.max(12, Math.floor(baseW.value * 0.65))
})

const totalLabels = computed(() => {
    return props.products.reduce((acc, p) => acc + (Number(p.copies) || 1), 0)
})

// Wrapper style (The actual physical rotated card container)
const computedWrapperStyle = computed(() => {
    const w = effectiveWidth.value
    const h = effectiveHeight.value
    return {
        width: `${w}mm`,
        height: `${h}mm`,
        maxWidth: `${w}mm`,
        maxHeight: `${h}mm`,
        minWidth: `${w}mm`,
        minHeight: `${h}mm`,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        overflow: 'hidden',
        boxSizing: 'border-box'
    }
})

// Content style (Lays out at base dimensions and ROTATES according to rotation)
const computedContentStyle = computed(() => {
    const deg = Number(rotation.value) || 0
    const w = baseW.value
    const h = baseH.value
    const padX = Math.max(0.8, w * 0.04).toFixed(1)
    const padY = Math.max(0.8, h * 0.04).toFixed(1)

    if (deg === 0) {
        return {
            width: '100%',
            height: '100%',
            padding: `${padY}mm ${padX}mm`,
            flexShrink: 0,
            boxSizing: 'border-box',
            transform: 'none'
        }
    }

    let transform = 'none'
    if (deg === 90) {
        transform = 'rotate(90deg)'
    } else if (deg === 180) {
        transform = 'rotate(180deg)'
    } else if (deg === 270) {
        transform = 'rotate(270deg)'
    }

    return {
        width: `${w}mm`,
        height: `${h}mm`,
        minWidth: `${w}mm`,
        minHeight: `${h}mm`,
        maxWidth: `${w}mm`,
        maxHeight: `${h}mm`,
        padding: `${padY}mm ${padX}mm`,
        flexShrink: 0,
        boxSizing: 'border-box',
        transform: transform,
        WebkitTransform: transform,
        transformOrigin: 'center center',
        WebkitTransformOrigin: 'center center'
    }
})

// Inner element styles dynamically scaled to base label dimensions
const computedStoreStyle = computed(() => {
    const w = baseW.value
    const h = baseH.value
    const sizeMm = Math.max(1.8, Math.min(3.2, h * 0.11)).toFixed(1)
    return {
        fontSize: `${sizeMm}mm`,
        maxWidth: `${w - 2.5}mm`
    }
})

const computedNameStyle = computed(() => {
    const w = baseW.value
    const h = baseH.value
    const sizeMm = Math.max(2.1, Math.min(3.5, h * 0.13)).toFixed(1)
    return {
        fontSize: `${sizeMm}mm`,
        maxWidth: `${w - 2.5}mm`
    }
})

const computedBarcodeStyle = computed(() => {
    const w = baseW.value
    const h = baseH.value
    const maxH = Math.min(10, Math.max(5, h * 0.38)).toFixed(1)
    const maxW = Math.max(10, w - 2.5).toFixed(1)
    return {
        maxHeight: `${maxH}mm`,
        height: `${maxH}mm`,
        maxWidth: `${maxW}mm`,
        width: 'auto'
    }
})

const computedCodeStyle = computed(() => {
    const h = baseH.value
    const sizeMm = Math.max(1.8, Math.min(2.8, h * 0.10)).toFixed(1)
    return {
        fontSize: `${sizeMm}mm`
    }
})

const computedPriceStyle = computed(() => {
    const h = baseH.value
    const sizeMm = Math.max(2.5, Math.min(4.2, h * 0.15)).toFixed(1)
    return {
        fontSize: `${sizeMm}mm`
    }
})

const previewContainerStyle = computed(() => {
    if (previewScale.value !== 1) {
        return {
            transform: `scale(${previewScale.value})`,
            transformOrigin: 'top center'
        }
    }
    return {}
})

const truncate = (str, length) => {
    if (!str) return ''
    if (str.length <= length) return str
    return str.substring(0, length) + '..'
}

const formatPrice = (price) => {
    const num = Number(price || 0)
    return num % 1 === 0 ? num.toFixed(0) : num.toFixed(2)
}

// Injected dynamic @page print CSS (Rotates the paper size physically)
const updatePrintStyleTag = () => {
    let styleTag = document.getElementById('barcode-dynamic-page-rule')
    if (!styleTag) {
        styleTag = document.createElement('style')
        styleTag.id = 'barcode-dynamic-page-rule'
        document.head.appendChild(styleTag)
    }

    const w = effectiveWidth.value
    const h = effectiveHeight.value
    const orient = pageOrientation.value !== 'auto' ? ` ${pageOrientation.value}` : ''
    let pSize = `${w}mm ${h}mm${orient}`
    let pMargin = '0mm'

    if (selectedPaperSize.value === 'A4') {
        pSize = pageOrientation.value === 'landscape' ? 'A4 landscape' : 'A4 portrait'
        pMargin = '6mm'
    } else if (selectedPaperSize.value === 'Letter') {
        pSize = pageOrientation.value === 'landscape' ? 'letter landscape' : 'letter portrait'
        pMargin = '6mm'
    } else if (selectedPaperSize.value === '80mm') {
        pSize = '80mm auto'
        pMargin = '0mm'
    }

    styleTag.textContent = `
        @media print {
            @page {
                size: ${pSize};
                margin: ${pMargin};
            }
        }
    `
}

// Save Current Settings as Default
const saveSettingsAsDefault = () => {
    localStorage.setItem('trustcash_barcode_papersize', selectedPaperSize.value)
    localStorage.setItem('trustcash_barcode_rotation', String(rotation.value))
    localStorage.setItem('trustcash_barcode_orientation', pageOrientation.value)
    localStorage.setItem('trustcash_barcode_swapped', isSwapped.value ? 'true' : 'false')
    localStorage.setItem('trustcash_barcode_custom_w', String(customWidth.value))
    localStorage.setItem('trustcash_barcode_custom_h', String(customHeight.value))
    localStorage.setItem('trustcash_barcode_show_store', showStoreName.value ? 'true' : 'false')
    localStorage.setItem('trustcash_barcode_store_name', storeName.value || '')
    localStorage.setItem('trustcash_barcode_show_name', showProductName.value ? 'true' : 'false')
    localStorage.setItem('trustcash_barcode_show_code', showBarcodeText.value ? 'true' : 'false')
    localStorage.setItem('trustcash_barcode_show_price', showPrice.value ? 'true' : 'false')
    localStorage.setItem('trustcash_barcode_price_prefix', pricePrefix.value || '')
    localStorage.setItem('trustcash_barcode_show_border', showBorder.value ? 'true' : 'false')
    localStorage.setItem('trustcash_barcode_preview_scale', String(previewScale.value))

    savedToast.value = true
    setTimeout(() => {
        savedToast.value = false
    }, 4000)
}

// Reset to Factory Defaults
const resetToFactoryDefaults = () => {
    const keys = [
        'trustcash_barcode_papersize',
        'trustcash_barcode_rotation',
        'trustcash_barcode_orientation',
        'trustcash_barcode_swapped',
        'trustcash_barcode_custom_w',
        'trustcash_barcode_custom_h',
        'trustcash_barcode_show_store',
        'trustcash_barcode_store_name',
        'trustcash_barcode_show_name',
        'trustcash_barcode_show_code',
        'trustcash_barcode_show_price',
        'trustcash_barcode_price_prefix',
        'trustcash_barcode_show_border',
        'trustcash_barcode_preview_scale',
        'trustcash_barcode_bulk_copies'
    ]
    keys.forEach(k => localStorage.removeItem(k))

    selectedPaperSize.value = '38x25mm'
    rotation.value = 0
    pageOrientation.value = 'auto'
    isSwapped.value = false
    customWidth.value = 38
    customHeight.value = 25
    showStoreName.value = true
    storeName.value = page.props.business_name || page.props.app_name || ''
    showProductName.value = true
    showBarcodeText.value = true
    showPrice.value = true
    pricePrefix.value = 'MRP: ৳ '
    showBorder.value = false
    previewScale.value = 1

    resetToast.value = true
    setTimeout(() => {
        resetToast.value = false
    }, 3000)
}

watch([selectedPaperSize, customWidth, customHeight, rotation, pageOrientation, isSwapped, previewScale], () => {
    if (customWidth.value) localStorage.setItem('trustcash_barcode_custom_w', customWidth.value)
    if (customHeight.value) localStorage.setItem('trustcash_barcode_custom_h', customHeight.value)
    localStorage.setItem('trustcash_barcode_rotation', rotation.value)
    localStorage.setItem('trustcash_barcode_orientation', pageOrientation.value)
    localStorage.setItem('trustcash_barcode_swapped', isSwapped.value ? 'true' : 'false')
    localStorage.setItem('trustcash_barcode_papersize', selectedPaperSize.value)
    localStorage.setItem('trustcash_barcode_preview_scale', previewScale.value)
    updatePrintStyleTag()
}, { immediate: true })

onMounted(() => {
    updatePrintStyleTag()
})

onUnmounted(() => {
    const styleTag = document.getElementById('barcode-dynamic-page-rule')
    if (styleTag) {
        styleTag.remove()
    }
})

const triggerPrint = () => {
    window.print()
}
</script>

<style scoped>
/* ============================================================
   SCREEN PREVIEW STYLES
   ============================================================ */
.barcode-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 12px;
    transition: transform 0.2s ease-in-out;
}

.label-wrapper {
    background: #ffffff;
    box-sizing: border-box;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px dashed #cbd5e1;
    overflow: hidden;
    transition: width 0.2s, height 0.2s;
}

.label-content {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    text-align: center;
    box-sizing: border-box;
    transition: transform 0.2s ease-in-out;
}

/* Base typography for sticker items */
.lbl-store {
    font-weight: 800;
    line-height: 1;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.lbl-name {
    font-weight: 700;
    line-height: 1.15;
    color: #000000;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.lbl-barcode-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.lbl-barcode-img {
    object-fit: contain;
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
    image-rendering: pixelated;
    display: block;
    margin: 0.2mm auto;
}

.lbl-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-weight: 700;
    letter-spacing: 0.5px;
    line-height: 1;
    color: #1f2937;
}

.lbl-price {
    font-weight: 900;
    line-height: 1;
    color: #000000;
}

/* ============================================================
   PRINT RULES (Zero-Margin, Page-Break Controlled)
   ============================================================ */
@media print {
    html, body {
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        color: #000000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print\:hidden {
        display: none !important;
    }

    .barcode-container {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        transform: none !important;
    }

    /* Borders in print */
    .has-print-border {
        border: 1px solid #000000 !important;
    }

    .no-print-border {
        border: none !important;
        box-shadow: none !important;
    }

    /* 1-Column Thermal Mode (Every label breaks to new page with exact zero-margin alignment) */
    .mode-thermal-single {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .mode-thermal-single .label-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    .mode-thermal-single .label-wrapper:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
    }

    /* 2-Column Thermal Mode (2 labels per row on 80mm roll) */
    .mode-thermal-2col {
        display: flex !important;
        flex-wrap: wrap !important;
        width: 80mm !important;
        margin: 0 auto !important;
        padding: 0 !important;
        justify-content: space-between !important;
    }

    .mode-thermal-2col .label-wrapper {
        page-break-after: avoid !important;
        break-after: avoid !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border-radius: 0 !important;
    }

    .mode-thermal-2col .label-wrapper:nth-child(2n) {
        page-break-after: always !important;
        break-after: page !important;
    }

    .mode-thermal-2col .label-wrapper:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
    }

    /* Sheet Mode (A4, Letter) */
    .mode-sheet {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 3mm !important;
        justify-content: flex-start !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .mode-sheet .label-wrapper {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border: 1px solid #e2e8f0 !important;
    }

    /* 80mm Continuous Roll Mode */
    .mode-thermal-roll {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        gap: 3mm !important;
        width: 80mm !important;
        margin: 0 auto !important;
    }

    .mode-thermal-roll .label-wrapper {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    /* Custom Mode */
    .mode-custom .label-wrapper {
        margin: 0 auto !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border-radius: 0 !important;
    }

    .mode-custom .label-wrapper:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
    }
}
</style>