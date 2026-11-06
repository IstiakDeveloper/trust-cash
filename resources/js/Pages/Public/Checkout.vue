<template>
    <GuestLayout>
        <Head title="অর্ডার চেকআউট (Checkout)" />

        <div class="mx-auto max-w-5xl px-4 py-6 sm:py-10 sm:px-6 lg:px-8">
            <!-- Breadcrumbs / Top Title -->
            <div class="flex items-center justify-between pb-6 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                        অর্ডার সম্পন্ন করুন (Checkout)
                    </h1>
                    <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                        সঠিক নাম ও ঠিকানা প্রদান করে আপনার অর্ডারটি কনফার্ম করুন।
                    </p>
                </div>
                <Link
                    :href="route('home')"
                    class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                >
                    <span>← শপে ফিরে যান</span>
                </Link>
            </div>

            <!-- Main Grid -->
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Left Column: Checkout Form (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- 1. Customer Information Card -->
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                                <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 font-bold text-xs">
                                    ১
                                </span>
                                <h2 class="text-base font-bold text-slate-900 dark:text-white">
                                    ডেলিভারির তথ্য (Customer Details)
                                </h2>
                            </div>

                            <!-- Name -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    আপনার পুরো নাম <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="যেমন: মোহাম্মদ আব্দুল্লাহ"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50/50 p-3 text-xs sm:text-sm text-slate-900 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                                    :class="{ 'border-rose-500': form.errors.name }"
                                    required
                                />
                                <span v-if="form.errors.name" class="mt-1 block text-xs text-rose-500">{{ form.errors.name }}</span>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    মোবাইল নম্বর <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.phone"
                                        type="tel"
                                        placeholder="017XXXXXXXX (১১ ডিজিট)"
                                        class="w-full rounded-xl border-slate-200 bg-slate-50/50 p-3 font-mono text-xs sm:text-sm text-slate-900 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                                        :class="{ 'border-rose-500': form.errors.phone }"
                                        required
                                    />
                                </div>
                                <span v-if="form.errors.phone" class="mt-1 block text-xs text-rose-500">{{ form.errors.phone }}</span>
                                <p class="mt-1 text-[11px] text-slate-400">এই নম্বরে ফোন করে অর্ডার ভেরিফাই করা হবে।</p>
                            </div>

                            <!-- Full Address -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    পূর্ণ ঠিকানা <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.address"
                                    rows="2"
                                    placeholder="বাসা/রোড নম্বর, এলাকা, থানা, জেলা"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50/50 p-3 text-xs sm:text-sm text-slate-900 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                                    :class="{ 'border-rose-500': form.errors.address }"
                                    required
                                ></textarea>
                                <span v-if="form.errors.address" class="mt-1 block text-xs text-rose-500">{{ form.errors.address }}</span>
                            </div>

                            <!-- Delivery Area -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                                    ডেলিভারি এরিয়া সিলেক্ট করুন <span class="text-rose-500">*</span>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label
                                        class="flex items-center justify-between p-3.5 rounded-2xl border cursor-pointer transition select-none"
                                        :class="form.delivery_area === 'inside_dhaka'
                                            ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/40 dark:border-indigo-500'
                                            : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-850'"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <input
                                                type="radio"
                                                value="inside_dhaka"
                                                v-model="form.delivery_area"
                                                class="text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <div>
                                                <span class="block text-xs font-bold text-slate-900 dark:text-white">ঢাকার ভেতরে</span>
                                                <span class="text-[11px] text-slate-500">হোম ডেলিভারি (২-৩ দিন)</span>
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ formatCurrency(deliveryCharges?.inside_dhaka || 70) }}
                                        </span>
                                    </label>

                                    <label
                                        class="flex items-center justify-between p-3.5 rounded-2xl border cursor-pointer transition select-none"
                                        :class="form.delivery_area === 'outside_dhaka'
                                            ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/40 dark:border-indigo-500'
                                            : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-850'"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <input
                                                type="radio"
                                                value="outside_dhaka"
                                                v-model="form.delivery_area"
                                                class="text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <div>
                                                <span class="block text-xs font-bold text-slate-900 dark:text-white">ঢাকার বাইরে</span>
                                                <span class="text-[11px] text-slate-500">কুরিয়ার ডেলিভারি (৩-৫ দিন)</span>
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ formatCurrency(deliveryCharges?.outside_dhaka || 130) }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Payment Method Card -->
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                                <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 font-bold text-xs">
                                    ২
                                </span>
                                <h2 class="text-base font-bold text-slate-900 dark:text-white">
                                    পেমেন্ট মাধ্যম (Payment Method)
                                </h2>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Cash on Delivery -->
                                <label
                                    class="flex items-start gap-3 p-4 rounded-2xl border cursor-pointer transition select-none"
                                    :class="form.payment_method === 'cod'
                                        ? 'border-emerald-600 bg-emerald-50/40 dark:bg-emerald-950/30 dark:border-emerald-600'
                                        : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-850'"
                                >
                                    <input
                                        type="radio"
                                        value="cod"
                                        v-model="form.payment_method"
                                        class="mt-0.5 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <div>
                                        <span class="block text-xs font-bold text-slate-900 dark:text-white">ক্যাশ অন ডেলিভারি</span>
                                        <span class="text-[11px] text-slate-500 leading-tight block mt-0.5">
                                            পণ্য হাতে পেয়ে মূল্য পরিশোধ করুন।
                                        </span>
                                    </div>
                                </label>

                                <!-- bKash / Nagad -->
                                <label
                                    class="flex items-start gap-3 p-4 rounded-2xl border cursor-pointer transition select-none"
                                    :class="form.payment_method === 'bkash'
                                        ? 'border-indigo-600 bg-indigo-50/40 dark:bg-indigo-950/30 dark:border-indigo-600'
                                        : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-850'"
                                >
                                    <input
                                        type="radio"
                                        value="bkash"
                                        v-model="form.payment_method"
                                        class="mt-0.5 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <div>
                                        <span class="block text-xs font-bold text-slate-900 dark:text-white">বিকাশ / নগদ (Send Money)</span>
                                        <span class="text-[11px] text-slate-500 leading-tight block mt-0.5">
                                            শপের নম্বরে আগে টাকা পাঠিয়ে TrxID দিন।
                                        </span>
                                    </div>
                                </label>
                            </div>

                            <!-- bKash TrxID Field if selected -->
                            <div v-if="form.payment_method === 'bkash'" class="rounded-2xl bg-indigo-50/60 p-4 border border-indigo-200 dark:bg-indigo-950/40 dark:border-indigo-900/60 space-y-2">
                                <p class="text-xs text-indigo-950 dark:text-indigo-200 font-medium">
                                    বিকাশ/নগদ নম্বর: <span class="font-mono font-bold text-slate-900 dark:text-white bg-white dark:bg-slate-800 px-2 py-0.5 rounded">{{ shop.phone || '01XXXXXXXXX' }}</span> (Personal/Merchant)
                                </p>
                                <div>
                                    <label class="block text-[11px] font-bold text-indigo-900 dark:text-indigo-300 mb-1">
                                        ট্রানজেকশন আইডি (Trx ID)
                                    </label>
                                    <input
                                        v-model="form.payment_trx"
                                        type="text"
                                        placeholder="যেমন: BKH9234A..."
                                        class="w-full rounded-xl border-indigo-300 bg-white p-2.5 font-mono text-xs text-slate-900 focus:border-indigo-600 dark:bg-slate-900 dark:border-slate-700 dark:text-white"
                                    />
                                </div>
                            </div>

                            <!-- Order Note (Optional) -->
                            <div class="pt-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    অর্ডার নোট বা বিশেষ নির্দেশনা (ঐচ্ছিক)
                                </label>
                                <input
                                    v-model="form.note"
                                    type="text"
                                    placeholder="যেমন: বিকেলে ডেলিভারি দিলে ভালো হয়..."
                                    class="w-full rounded-xl border-slate-200 bg-slate-50/50 p-3 text-xs text-slate-900 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Mobile Submit Button (Visible on Small screens) -->
                        <div class="lg:hidden">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm py-4 shadow-lg shadow-emerald-600/25 transition disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                <span v-if="form.processing">অর্ডার প্রসেস হচ্ছে...</span>
                                <span v-else>অর্ডার নিশ্চিত করুন ({{ formatCurrency(grandTotal) }})</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Order Summary (5 cols, Sticky) -->
                <div class="lg:col-span-5 sticky top-24">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-5">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">
                                অর্ডার সামারি ({{ cartItems.length }}টি পণ্য)
                            </h2>
                            <Link :href="route('cart')" class="text-xs text-indigo-600 hover:underline">
                                পরিবর্তন করুন
                            </Link>
                        </div>

                        <!-- Items List -->
                        <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-72 overflow-y-auto pr-1">
                            <div
                                v-for="it in cartItems"
                                :key="it.product.id"
                                class="py-3 flex items-center justify-between gap-3 text-xs"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                        <img
                                            v-if="it.product.image_url || it.product.image"
                                            :src="it.product.image_url || `/storage/${it.product.image}`"
                                            class="h-full w-full object-cover"
                                        />
                                        <div v-else class="flex h-full w-full items-center justify-center text-[10px] text-slate-400">
                                            ছবি নেই
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-slate-900 dark:text-white">{{ it.product.name }}</p>
                                        <p class="text-slate-500 dark:text-slate-400">
                                            {{ formatCurrency(it.product.selling_price) }} × {{ it.quantity }}
                                        </p>
                                    </div>
                                </div>
                                <span class="font-bold text-slate-900 dark:text-white shrink-0">
                                    {{ formatCurrency(it.line_total) }}
                                </span>
                            </div>
                        </div>

                        <!-- Calculations -->
                        <div class="space-y-2.5 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
                            <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                <span>সাবটোটাল (Subtotal):</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                <span>ডেলিভারি চার্জ:</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(currentDeliveryCharge) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-black text-slate-900 dark:text-white pt-3 border-t border-slate-200 dark:border-slate-800">
                                <span>সর্বমোট প্রদেয়:</span>
                                <span class="text-emerald-600 dark:text-emerald-400">{{ formatCurrency(grandTotal) }}</span>
                            </div>
                        </div>

                        <!-- Desktop Submit Button -->
                        <div class="hidden lg:block pt-2">
                            <button
                                type="button"
                                @click="submit"
                                :disabled="form.processing"
                                class="w-full rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm py-4 shadow-lg shadow-emerald-600/25 transition transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <span v-if="form.processing">অর্ডার প্রসেস হচ্ছে...</span>
                                <span v-else>অর্ডার নিশ্চিত করুন ({{ formatCurrency(grandTotal) }})</span>
                            </button>
                        </div>

                        <!-- Trust Badge -->
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-800/40 p-3 text-center text-[11px] text-slate-500 dark:text-slate-400 space-y-1">
                            <p class="font-semibold text-slate-700 dark:text-slate-300">🔒 ১০০% নিরাপদ ডেলিভারি নিশ্চয়তা</p>
                            <p>প্রয়োজনে সরাসরি কল করুন: <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ shop.phone || 'হেল্পলাইনে' }}</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { formatCurrency } from '@/utils'

const props = defineProps({
    cartItems: {
        type: Array,
        default: () => [],
    },
    subtotal: {
        type: Number,
        default: 0,
    },
    cartCount: {
        type: Number,
        default: 0,
    },
    deliveryCharges: {
        type: Object,
        default: () => ({ inside_dhaka: 70, outside_dhaka: 130 }),
    },
    shop: {
        type: Object,
        default: () => ({}),
    },
})

const form = useForm({
    name: '',
    phone: '',
    address: '',
    delivery_area: 'inside_dhaka',
    payment_method: 'cod',
    payment_trx: '',
    note: '',
})

const currentDeliveryCharge = computed(() => {
    if (form.delivery_area === 'outside_dhaka') {
        return Number(props.deliveryCharges?.outside_dhaka ?? 130)
    }
    return Number(props.deliveryCharges?.inside_dhaka ?? 70)
})

const grandTotal = computed(() => {
    return Number(props.subtotal) + currentDeliveryCharge.value
})

function submit() {
    form.post(route('checkout.submit'))
}
</script>
