<template>
    <GuestLayout>
        <Head title="শপিং কার্ট - Cart" />

        <div class="bg-slate-50/50 dark:bg-slate-950/40 min-h-[70vh]">
            <div class="mx-auto max-w-4xl px-4 py-8 sm:py-12 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between pb-6 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                            শপিং কার্ট (Cart)
                        </h1>
                        <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            অর্ডার নিশ্চিত করার আগে পণ্যের সংখ্যা যাচাই করে নিন।
                        </p>
                    </div>
                    <Link
                        :href="route('home')"
                        class="text-xs sm:text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                    >
                        ← পণ্য যোগ করুন
                    </Link>
                </div>

                <div v-if="items.length" class="mt-8 space-y-4">
                    <div
                        v-for="item in localItems"
                        :key="item.product.id"
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <img
                                    v-if="item.product.image_url || item.product.image"
                                    :src="item.product.image_url || `/storage/${item.product.image}`"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center text-[10px] text-slate-400">
                                    ছবি নেই
                                </div>
                            </div>
                            <div>
                                <p class="font-bold text-sm text-slate-900 dark:text-white">{{ item.product.name }}</p>
                                <p v-if="item.product.sku" class="text-[11px] font-mono text-slate-400">SKU: {{ item.product.sku }}</p>
                                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                    {{ formatCurrency(item.product.selling_price) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-3 pt-3 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                            <!-- Qty Controls -->
                            <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                                <button
                                    type="button"
                                    class="h-8 w-8 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-white font-bold text-sm shadow-xs hover:bg-slate-50"
                                    @click="updateQty(item.product.id, item.quantity - 1)"
                                >-</button>
                                <span class="w-8 text-center font-bold text-xs text-slate-900 dark:text-white">
                                    {{ item.quantity }}
                                </span>
                                <button
                                    type="button"
                                    class="h-8 w-8 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-white font-bold text-sm shadow-xs hover:bg-slate-50"
                                    @click="updateQty(item.product.id, item.quantity + 1)"
                                >+</button>
                            </div>

                            <!-- Line total -->
                            <p class="w-24 text-right text-sm font-black text-slate-900 dark:text-white">
                                {{ formatCurrency(item.quantity * item.product.selling_price) }}
                            </p>

                            <!-- Delete button -->
                            <button
                                type="button"
                                @click="remove(item.product.id)"
                                class="rounded-xl p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                title="মুছে ফেলুন"
                            >
                                🗑
                            </button>
                        </div>
                    </div>

                    <!-- Summary & Checkout bar -->
                    <div class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">সর্বমোট পণ্যের মূল্য (Subtotal)</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white">{{ formatCurrency(computedSubtotal) }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">ডেলিভারি চার্জ চেকআউট পেইজে যুক্ত হবে।</p>
                        </div>
                        <div class="flex gap-2.5">
                            <Link
                                :href="route('checkout')"
                                class="w-full sm:w-auto rounded-2xl bg-emerald-600 hover:bg-emerald-500 px-8 py-3.5 text-center text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition transform hover:-translate-y-0.5 active:translate-y-0"
                            >
                                অর্ডার করতে এগিয়ে যান →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="mt-12 rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-4xl mb-3">🛍️</p>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">আপনার কার্ট খালি</h3>
                    <p class="mt-1 text-xs text-slate-500">শপ থেকে আপনার পছন্দের পণ্য যোগ করুন।</p>
                    <Link
                        :href="route('home')"
                        class="mt-5 inline-flex items-center rounded-2xl bg-slate-900 dark:bg-slate-800 px-6 py-3 text-xs font-bold text-white shadow-md hover:bg-slate-800 dark:hover:bg-slate-700 transition"
                    >
                        পণ্য দেখুন
                    </Link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { formatCurrency } from '@/utils'

const props = defineProps({
    items: Array,
    subtotal: Number,
    cartCount: Number,
})

const localItems = ref((props.items || []).map(i => ({ ...i })))
watch(
    () => props.items,
    (val) => {
        localItems.value = (val || []).map(i => ({ ...i }))
    }
)

const computedSubtotal = computed(() =>
    localItems.value.reduce((sum, i) => sum + (i.quantity * i.product.selling_price), 0)
)

function updateQty(productId, qty) {
    if (qty < 1) {
        remove(productId)
        return
    }
    const item = localItems.value.find(x => x.product.id === productId)
    if (!item) return
    item.quantity = qty

    router.post(route('cart.update'), {
        items: localItems.value.map(i => ({ product_id: i.product.id, quantity: i.quantity })),
    }, { preserveScroll: true, preserveState: true })
}

function remove(productId) {
    router.post(route('cart.remove'), { product_id: productId }, { preserveScroll: true })
}
</script>
