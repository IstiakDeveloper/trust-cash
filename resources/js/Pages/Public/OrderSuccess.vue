<template>
    <GuestLayout>
        <Head :title="`অর্ডার নিশ্চিতকরণ - ${order.order_no}`" />

        <div class="mx-auto max-w-3xl py-6 sm:py-10 px-4 sm:px-6">
            <!-- Success Card -->
            <div class="overflow-hidden rounded-3xl border border-emerald-100 bg-white shadow-xl shadow-emerald-500/5 dark:border-emerald-950/50 dark:bg-slate-900">
                <!-- Header Banner -->
                <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 px-6 py-8 sm:px-10 text-center text-white">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-md shadow-inner text-3xl">
                        ✓
                    </div>
                    <h1 class="mt-4 text-2xl sm:text-3xl font-black tracking-tight">
                        আপনার অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে!
                    </h1>
                    <p class="mt-1 text-sm text-emerald-100 font-medium">
                        অর্ডার নং: <span class="font-mono font-bold text-white bg-black/20 px-2.5 py-0.5 rounded-lg select-all">{{ order.order_no }}</span>
                    </p>
                    <p class="mt-2 text-xs text-emerald-200">
                        আমাদের প্রতিনিধি শীঘ্রই আপনার সাথে ফোনে যোগাযোগ করে অর্ডারটি কনফার্ম করবেন।
                    </p>
                </div>

                <div class="p-6 sm:p-8 space-y-6">
                    <!-- WhatsApp Confirmation CTA Button -->
                    <div v-if="shop.whatsapp || shop.phone" class="rounded-2xl bg-emerald-50 p-4 border border-emerald-200/80 dark:bg-emerald-950/40 dark:border-emerald-900/60 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-center sm:text-left">
                            <h4 class="text-sm font-bold text-emerald-950 dark:text-emerald-200">
                                দ্রুত ডেলিভারির জন্য হোয়াটসঅ্যাপে মেসেজ দিন
                            </h4>
                            <p class="text-xs text-emerald-800 dark:text-emerald-400">
                                এক ক্লিকেই শপ ওনারের সাথে অর্ডার নিশ্চিত করতে পারবেন।
                            </p>
                        </div>
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="shrink-0 inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 text-xs font-bold shadow-md shadow-emerald-600/20 transition transform hover:-translate-y-0.5 active:translate-y-0"
                        >
                            <span>📱 হোয়াটসঅ্যাপে কনফার্ম করুন</span>
                        </a>
                    </div>

                    <!-- Customer & Delivery Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 text-xs">
                        <div>
                            <span class="font-bold text-slate-500 dark:text-slate-400 block uppercase tracking-wider text-[10px]">কাস্টমারের তথ্য</span>
                            <p class="mt-1 font-bold text-sm text-slate-900 dark:text-white">{{ order.customer?.name }}</p>
                            <p class="text-slate-600 dark:text-slate-300 font-mono font-semibold">{{ order.customer?.phone }}</p>
                        </div>
                        <div>
                            <span class="font-bold text-slate-500 dark:text-slate-400 block uppercase tracking-wider text-[10px]">ডেলিভারি ঠিকানা</span>
                            <p class="mt-1 text-slate-800 dark:text-slate-200 leading-relaxed font-medium">
                                {{ order.customer?.address || '—' }}
                            </p>
                        </div>
                    </div>

                    <!-- Note Details if present -->
                    <div v-if="order.note" class="rounded-xl bg-amber-50/70 dark:bg-amber-950/30 p-3.5 border border-amber-200/70 dark:border-amber-900/50 text-xs">
                        <span class="font-bold text-amber-900 dark:text-amber-300 block mb-1">অর্ডার বিবরণী:</span>
                        <p class="text-amber-800 dark:text-amber-200 whitespace-pre-line leading-relaxed font-medium">
                            {{ order.note }}
                        </p>
                    </div>

                    <!-- Items Table -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">
                            অর্ডারকৃত পণ্যের তালিকা ({{ order.items.length }})
                        </h3>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800 border-t border-b border-slate-200 dark:border-slate-800">
                            <div v-for="item in order.items" :key="item.id" class="py-3 flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                        <img v-if="item.image_url" :src="item.image_url" :alt="item.product_name" class="h-full w-full object-cover" />
                                        <div v-else class="flex h-full w-full items-center justify-center text-[10px] text-slate-400">
                                            ছবি নেই
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-slate-900 dark:text-white">{{ item.product_name }}</p>
                                        <p class="text-slate-500 dark:text-slate-400">
                                            {{ formatCurrency(item.unit_price) }} × {{ item.quantity }}
                                        </p>
                                    </div>
                                </div>
                                <span class="font-bold text-slate-900 dark:text-white shrink-0">
                                    {{ formatCurrency(item.subtotal) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="space-y-2 pt-2 text-xs">
                        <div class="flex justify-between text-slate-600 dark:text-slate-400">
                            <span>পণ্যের মূল্য (Subtotal):</span>
                            <span class="font-semibold text-slate-900 dark:text-white">{{ formatCurrency(order.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600 dark:text-slate-400">
                            <span>ডেলিভারি চার্জ:</span>
                            <span class="font-semibold text-slate-900 dark:text-white">{{ formatCurrency(order.delivery_charge) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-bold text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-800">
                            <span>সর্বমোট প্রদেয় টাকা (Total):</span>
                            <span class="text-emerald-600 dark:text-emerald-400">{{ formatCurrency(order.total) }}</span>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-100 dark:border-slate-800">
                        <Link
                            :href="route('home')"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-800 dark:hover:bg-slate-700 px-6 py-3 text-xs font-bold transition shadow-xs"
                        >
                            <span>← আরো কেনাকাটা করুন</span>
                        </Link>
                        <span class="text-[11px] text-slate-500 text-center sm:text-right">
                            সহায়তার জন্য কল করুন: <span class="font-bold text-slate-700 dark:text-slate-300">{{ shop.phone || 'দোকানদারের সাথে যোগাযোগ করুন' }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { formatCurrency } from '@/utils'

const props = defineProps({
    order: Object,
    shop: Object,
})

const whatsappUrl = computed(() => {
    const rawNumber = props.shop?.whatsapp || props.shop?.phone || ''
    const cleanNumber = rawNumber.replace(/[^0-9]/g, '')
    if (!cleanNumber) return '#'

    const formattedNumber = cleanNumber.startsWith('88') ? cleanNumber : `88${cleanNumber.replace(/^0+/, '')}`
    const text = `আসসালামু আলাইকুম ${props.shop?.name || ''}, আমি ওয়েবসাইট থেকে একটি অর্ডার করেছি।\nঅর্ডার নং: #${props.order?.order_no}\nমোট টাকা: ৳${props.order?.total}\nঅনুগ্রহ করে অর্ডারটি নিশ্চিত করুন। ধন্যবাদ!`

    return `https://wa.me/${formattedNumber}?text=${encodeURIComponent(text)}`
})
</script>

