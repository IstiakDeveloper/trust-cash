<template>
    <div v-if="isOpen">
        <!-- Backdrop -->
        <div class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="close"></div>

        <!-- Modal Box -->
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white dark:bg-slate-900 shadow-2xl ring-1 ring-slate-900/10 dark:ring-white/10 transition-all animate-in fade-in zoom-in-95 duration-200">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm shadow-sm">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">কুইক এন্ট্রি (Quick Actions)</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">১ ক্লিকে যেকোনো কাজ দ্রুত শুরু করুন</p>
                        </div>
                    </div>
                    <button @click="close" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i class="fas fa-times text-base"></i>
                    </button>
                </div>

                <!-- Grid of Actions -->
                <div class="p-6 grid grid-cols-2 gap-3 sm:gap-4">
                    <!-- Action 1: POS -->
                    <button
                        @click="navigate('/admin/pos')"
                        class="flex flex-col items-start p-4 rounded-xl border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/60 dark:bg-emerald-950/20 hover:bg-emerald-100/60 dark:hover:bg-emerald-950/40 text-left transition-all group hover:shadow-md"
                    >
                        <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-lg mb-3 shadow-sm group-hover:scale-105 transition-transform">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <div class="font-bold text-slate-900 dark:text-white text-sm group-hover:text-emerald-700 dark:group-hover:text-emerald-400">
                            নতুন বিক্রি (POS)
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            ক্যাশ কাউন্টারে মেমো কাটুন [F1]
                        </div>
                    </button>

                    <!-- Action 2: Expense -->
                    <button
                        @click="navigate('/admin/expenses')"
                        class="flex flex-col items-start p-4 rounded-xl border border-rose-200/80 dark:border-rose-900/40 bg-rose-50/60 dark:bg-rose-950/20 hover:bg-rose-100/60 dark:hover:bg-rose-950/40 text-left transition-all group hover:shadow-md"
                    >
                        <div class="w-10 h-10 rounded-lg bg-rose-600 text-white flex items-center justify-center text-lg mb-3 shadow-sm group-hover:scale-105 transition-transform">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="font-bold text-slate-900 dark:text-white text-sm group-hover:text-rose-700 dark:group-hover:text-rose-400">
                            দোকানের খরচ (Expense)
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            চা, ভাড়া, কারেন্ট বিল বা খরচ
                        </div>
                    </button>

                    <!-- Action 3: Customer Due -->
                    <button
                        @click="navigate('/admin/customers')"
                        class="flex flex-col items-start p-4 rounded-xl border border-amber-200/80 dark:border-amber-900/40 bg-amber-50/60 dark:bg-amber-950/20 hover:bg-amber-100/60 dark:hover:bg-amber-950/40 text-left transition-all group hover:shadow-md"
                    >
                        <div class="w-10 h-10 rounded-lg bg-amber-500 text-white flex items-center justify-center text-lg mb-3 shadow-sm group-hover:scale-105 transition-transform">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="font-bold text-slate-900 dark:text-white text-sm group-hover:text-amber-700 dark:group-hover:text-amber-400">
                            বাকি খাতা ও কালেকশন
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            কাস্টমার থেকে বাকি জমা নিন
                        </div>
                    </button>

                    <!-- Action 4: Add Product -->
                    <button
                        @click="navigate('/admin/products')"
                        class="flex flex-col items-start p-4 rounded-xl border border-indigo-200/80 dark:border-indigo-900/40 bg-indigo-50/60 dark:bg-indigo-950/20 hover:bg-indigo-100/60 dark:hover:bg-indigo-950/40 text-left transition-all group hover:shadow-md"
                    >
                        <div class="w-10 h-10 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-lg mb-3 shadow-sm group-hover:scale-105 transition-transform">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div class="font-bold text-slate-900 dark:text-white text-sm group-hover:text-indigo-700 dark:group-hover:text-indigo-400">
                            নতুন পণ্য যোগ (Product)
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            পণ্য, বারকোড ও বিক্রয় মূল্য
                        </div>
                    </button>

                    <!-- Action 5: New Purchase -->
                    <button
                        @click="navigate('/admin/purchases/create')"
                        class="flex flex-col items-start p-4 rounded-xl border border-sky-200/80 dark:border-sky-900/40 bg-sky-50/60 dark:bg-sky-950/20 hover:bg-sky-100/60 dark:hover:bg-sky-950/40 text-left transition-all group hover:shadow-md"
                    >
                        <div class="w-10 h-10 rounded-lg bg-sky-600 text-white flex items-center justify-center text-lg mb-3 shadow-sm group-hover:scale-105 transition-transform">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                        <div class="font-bold text-slate-900 dark:text-white text-sm group-hover:text-sky-700 dark:group-hover:text-sky-400">
                            মাল ক্রয় (Purchase)
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            সাপ্লায়ার থেকে স্টক ইনভয়েস
                        </div>
                    </button>

                    <!-- Action 6: Sales Return -->
                    <button
                        @click="navigate('/admin/returns')"
                        class="flex flex-col items-start p-4 rounded-xl border border-violet-200/80 dark:border-violet-900/40 bg-violet-50/60 dark:bg-violet-950/20 hover:bg-violet-100/60 dark:hover:bg-violet-950/40 text-left transition-all group hover:shadow-md"
                    >
                        <div class="w-10 h-10 rounded-lg bg-violet-600 text-white flex items-center justify-center text-lg mb-3 shadow-sm group-hover:scale-105 transition-transform">
                            <i class="fas fa-undo-alt"></i>
                        </div>
                        <div class="font-bold text-slate-900 dark:text-white text-sm group-hover:text-violet-700 dark:group-hover:text-violet-400">
                            পণ্য ফেরত (Return)
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            কাস্টমার রিটার্ন ও অ্যাডজাস্টমেন্ট
                        </div>
                    </button>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                    <span>💡 শর্টকাট: কিবোর্ডে <b>F1</b> চাপলে সরাসরি POS ওপেন হবে</span>
                    <button @click="close" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">বন্ধ করুন</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
})

const close = () => {
    isOpen.value = false
}

const navigate = (url) => {
    close()
    router.visit(url)
}
</script>
