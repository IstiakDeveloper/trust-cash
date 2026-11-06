<template>
    <GuestLayout>
        <Head :title="`${shopName} - অনলাইন শপ`" />

        <template #nav-right>
            <div class="flex items-center gap-2">
                <a
                    v-if="shop?.whatsapp || shop?.phone"
                    :href="whatsappDirectUrl"
                    target="_blank"
                    class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800 transition"
                    title="WhatsApp এ মেসেজ দিন"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>হটলাইন: {{ shop?.phone || 'যোগাযোগ' }}</span>
                </a>

                <button
                    type="button"
                    @click="cartOpen = true"
                    class="relative inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 shadow-md transition"
                >
                    <i class="fas fa-shopping-bag text-xs"></i>
                    <span>কার্ট</span>
                    <span
                        v-if="cartCount"
                        class="inline-flex min-w-5 h-5 items-center justify-center rounded-full bg-emerald-500 px-1.5 text-[10px] font-black text-white shadow-xs"
                    >
                        {{ cartCount }}
                    </span>
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <!-- Toast notification -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-2"
            >
                <div
                    v-if="toast"
                    class="fixed right-4 top-20 z-[60] max-w-sm rounded-2xl bg-slate-900/95 backdrop-blur-md px-4 py-3 text-xs font-bold text-white shadow-xl border border-slate-700 flex items-center gap-2"
                >
                    <span class="text-emerald-400">✓</span>
                    <span>{{ toast }}</span>
                </div>
            </transition>

            <!-- 1. Storefront Hero & Identity Banner -->
            <div class="rounded-3xl bg-gradient-to-br from-white via-indigo-50/20 to-slate-50 p-6 sm:p-8 shadow-sm border border-slate-200/80 backdrop-blur dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-900 dark:to-slate-850 dark:border-slate-800">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs font-bold mb-3 shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>অফিসিয়াল অনলাইন স্টোর ও ক্যাটালগ</span>
                        </div>
                        <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-slate-900 dark:text-white">
                            {{ shopName }}
                        </h1>
                        <p class="mt-1.5 max-w-xl text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed font-normal">
                            পণ্য নির্বাচন করে সহজে অর্ডার করুন। সারা দেশে হোম ডেলিভারি ও ক্যাশ অন ডেলিভারি (COD) সুবিধা রয়েছে।
                        </p>
                    </div>

                    <!-- Search Input & Category Dropdown -->
                    <div class="flex w-full flex-col sm:flex-row items-center gap-2.5 lg:w-auto">
                        <div class="relative w-full sm:w-80">
                            <input
                                v-model="q"
                                type="text"
                                placeholder="পণ্য খুঁজুন (নাম বা বারকোড)..."
                                class="w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-xs sm:text-sm text-slate-900 shadow-xs placeholder:text-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                            <span class="absolute right-3.5 top-3.5 text-xs text-slate-400">🔍</span>
                        </div>
                        <select
                            v-model="categoryId"
                            class="w-full sm:w-48 rounded-2xl border-slate-200 bg-white px-3.5 py-3 text-xs sm:text-sm text-slate-900 shadow-xs focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                        >
                            <option value="">সকল ক্যাটাগরি</option>
                            <option v-for="c in categories" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Category Chips Horizontal Bar -->
                <div v-if="categories && categories.length" class="mt-6 pt-5 border-t border-slate-200/80 dark:border-slate-800 flex items-center gap-2 overflow-x-auto no-scrollbar">
                    <button
                        type="button"
                        @click="categoryId = ''"
                        class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition shadow-xs"
                        :class="!categoryId
                            ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900'
                            : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'"
                    >
                        সবগুলো
                    </button>
                    <button
                        v-for="c in categories"
                        :key="c.id"
                        type="button"
                        @click="categoryId = String(c.id)"
                        class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition shadow-xs"
                        :class="categoryId === String(c.id)
                            ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900'
                            : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'"
                    >
                        {{ c.name }}
                    </button>
                </div>
            </div>

            <!-- 2. Product Grid -->
            <div class="mt-8">
                <div v-if="products.data && products.data.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5">
                    <div
                        v-for="p in products.data"
                        :key="p.id"
                        class="group flex flex-col justify-between overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xs hover:shadow-lg hover:border-indigo-200 transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
                    >
                        <!-- Image Container with stock badge -->
                        <div class="relative aspect-square bg-slate-100 dark:bg-slate-800/80 overflow-hidden">
                            <img
                                v-if="p.image_url || p.image"
                                :src="p.image_url || `/storage/${p.image}`"
                                :alt="p.name"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center text-xs text-slate-400">
                                ছবি নেই
                            </div>

                            <!-- Stock Badge -->
                            <div class="absolute top-2.5 left-2.5">
                                <span
                                    v-if="(p.available_quantity ?? 0) > 0"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/90 text-emerald-700 border border-emerald-200 backdrop-blur-xs shadow-xs dark:bg-slate-900/90 dark:text-emerald-400 dark:border-emerald-800"
                                >
                                    স্টকে আছে
                                </span>
                                <span
                                    v-else
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200 shadow-xs dark:bg-rose-950/80 dark:text-rose-400 dark:border-rose-900"
                                >
                                    স্টক শেষ
                                </span>
                            </div>
                        </div>

                        <!-- Product Info & Actions -->
                        <div class="p-3.5 sm:p-4 flex flex-col justify-between flex-1">
                            <div>
                                <p v-if="p.sku" class="text-[10px] font-mono text-slate-400 uppercase tracking-wider">
                                    SKU: {{ p.sku }}
                                </p>
                                <h3 class="mt-1 line-clamp-2 text-xs sm:text-sm font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ p.name }}
                                </h3>
                            </div>

                            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                                <div class="flex items-baseline justify-between mb-2.5">
                                    <span class="text-sm sm:text-base font-black text-slate-900 dark:text-white">
                                        {{ formatCurrency(p.selling_price) }}
                                    </span>
                                </div>

                                <!-- Action Buttons (Add to cart & Direct Order) -->
                                <div class="grid grid-cols-2 gap-1.5">
                                    <button
                                        type="button"
                                        @click="add(p)"
                                        :disabled="(p.available_quantity ?? 0) <= 0"
                                        class="w-full inline-flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 py-2 text-[11px] font-bold transition disabled:opacity-40 disabled:cursor-not-allowed"
                                        title="কার্টে যোগ করুন"
                                    >
                                        + কার্টে
                                    </button>

                                    <button
                                        type="button"
                                        @click="buyNow(p)"
                                        :disabled="(p.available_quantity ?? 0) <= 0"
                                        class="w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white py-2 text-[11px] font-bold shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed"
                                        title="সরাসরি অর্ডার করুন"
                                    >
                                        অর্ডার করুন
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="rounded-3xl border border-slate-200 bg-white p-12 text-center dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-3xl mb-2">🔍</p>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">কোনো পণ্য পাওয়া যায়নি</h3>
                    <p class="mt-1 text-xs text-slate-500">অন্য কোনো নাম বা ক্যাটাগরি দিয়ে সার্চ করে দেখুন।</p>
                </div>

                <!-- Pagination -->
                <div class="mt-10 flex justify-center" v-if="products.links?.length">
                    <Pagination :links="products.links" />
                </div>
            </div>
        </div>

        <!-- 3. Slide-out Cart Drawer -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="cartOpen" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs" @click="cartOpen = false" />
        </transition>

        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <aside
                v-if="cartOpen"
                class="fixed right-0 top-0 z-[55] h-full w-full max-w-md bg-white shadow-2xl dark:bg-slate-900 flex flex-col justify-between"
            >
                <!-- Drawer Header -->
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">আপনার শপিং ব্যাগ</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ cartCount }}টি পণ্য যুক্ত আছে</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        @click="cartOpen = false"
                    >
                        ✕
                    </button>
                </div>

                <!-- Drawer Items List -->
                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
                    <div v-if="localCartItems.length" class="space-y-3">
                        <div
                            v-for="it in localCartItems"
                            :key="it.product.id"
                            class="flex items-center gap-3 rounded-2xl border border-slate-200 p-3 dark:border-slate-800"
                        >
                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800">
                                <img
                                    v-if="it.product.image_url || it.product.image"
                                    :src="it.product.image_url || `/storage/${it.product.image}`"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-bold text-slate-900 dark:text-white">{{ it.product.name }}</p>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">
                                    {{ formatCurrency(it.product.selling_price) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    class="h-7 w-7 rounded-lg bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 font-bold text-xs"
                                    @click="setQty(it.product.id, it.quantity - 1)"
                                >-</button>
                                <span class="w-7 text-center font-bold text-xs text-slate-900 dark:text-white">
                                    {{ it.quantity }}
                                </span>
                                <button
                                    type="button"
                                    class="h-7 w-7 rounded-lg bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 font-bold text-xs"
                                    @click="setQty(it.product.id, it.quantity + 1)"
                                >+</button>
                            </div>
                            <button
                                type="button"
                                class="text-slate-400 hover:text-rose-500 p-1 text-xs transition"
                                @click="remove(it.product.id)"
                                title="মুছে ফেলুন"
                            >
                                🗑
                            </button>
                        </div>
                    </div>
                    <div v-else class="rounded-2xl border border-slate-200 p-8 text-center text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                        আপনার কার্ট খালি। পছন্দের পণ্য যুক্ত করুন।
                    </div>
                </div>

                <!-- Drawer Footer -->
                <div class="border-t border-slate-200 p-5 dark:border-slate-800 space-y-3 bg-slate-50/50 dark:bg-slate-950/50">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-600 dark:text-slate-400">পণ্যের মোট মূল্য:</span>
                        <span class="text-base font-black text-slate-900 dark:text-white">{{ formatCurrency(cartSubtotal) }}</span>
                    </div>
                    <div class="flex gap-2">
                        <Link
                            :href="route('checkout')"
                            class="flex-1 rounded-2xl bg-emerald-600 hover:bg-emerald-500 py-3.5 text-center text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition"
                            @click="cartOpen = false"
                        >
                            অর্ডার করতে এগিয়ে যান →
                        </Link>
                    </div>
                </div>
            </aside>
        </transition>

        <!-- 4. Floating Mobile Bottom Bar -->
        <div v-if="cartCount" class="sm:hidden fixed bottom-4 left-4 right-4 z-40">
            <Link
                :href="route('checkout')"
                class="flex items-center justify-between px-5 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xl shadow-emerald-600/30 transition"
            >
                <div class="flex items-center gap-2">
                    <span class="h-6 w-6 rounded-full bg-white/20 flex items-center justify-center font-mono text-[10px]">
                        {{ cartCount }}
                    </span>
                    <span>অর্ডার কনফার্ম করুন</span>
                </div>
                <span class="font-black text-sm">{{ formatCurrency(cartSubtotal) }}</span>
            </Link>
        </div>
    </GuestLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { formatCurrency } from '@/utils'

const props = defineProps({
    products: Object,
    categories: Array,
    query: Object,
    cartCount: Number,
    cart: Object,
    shop: Object,
})

const page = usePage()

const shopName = computed(() => {
    return page.props.business_name || props.shop?.name || page.props.platform?.name || 'TrustCash Store'
})

const whatsappDirectUrl = computed(() => {
    const rawNumber = props.shop?.whatsapp || props.shop?.phone || ''
    const cleanNumber = rawNumber.replace(/[^0-9]/g, '')
    if (!cleanNumber) return '#'

    const formattedNumber = cleanNumber.startsWith('88') ? cleanNumber : `88${cleanNumber.replace(/^0+/, '')}`
    const text = `আসসালামু আলাইকুম ${shopName.value}, আমি আপনাদের অনলাইন শপ থেকে কিছু প্রোডাক্ট কিনতে চাই।`

    return `https://wa.me/${formattedNumber}?text=${encodeURIComponent(text)}`
})

const q = ref(props.query?.q || '')
const categoryId = ref(props.query?.category_id ? String(props.query.category_id) : '')
const cartOpen = ref(false)
const toast = ref('')
let searchTimer = null

watch(
    () => page.props.flash?.success,
    (val) => {
        if (!val) return
        toast.value = val
        setTimeout(() => (toast.value = ''), 2500)
    },
    { immediate: true }
)

const localCartItems = ref((props.cart?.items || []).map(i => ({ ...i })))
watch(
    () => props.cart?.items,
    (items) => {
        localCartItems.value = (items || []).map(i => ({ ...i }))
    }
)

const cartSubtotal = computed(() =>
    localCartItems.value.reduce((sum, it) => sum + (it.quantity * it.product.selling_price), 0)
)

watch(q, (val) => {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get(route('home'), { q: val || '', category_id: categoryId.value || '' }, { preserveScroll: true, preserveState: true })
    }, 350)
})

watch(categoryId, () => {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get(route('home'), { q: q.value || '', category_id: categoryId.value || '' }, { preserveScroll: true, preserveState: true })
    }, 150)
})

function add(product) {
    if ((product.available_quantity ?? 0) <= 0) {
        toast.value = 'পণ্যটি বর্তমানে স্টকে নেই।'
        setTimeout(() => (toast.value = ''), 2500)
        return
    }
    router.post(route('cart.add'), { product_id: product.id, quantity: 1 }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.value = `"${product.name}" কার্টে যোগ করা হয়েছে।`
            cartOpen.value = true
            setTimeout(() => (toast.value = ''), 2500)
        }
    })
}

function buyNow(product) {
    if ((product.available_quantity ?? 0) <= 0) {
        toast.value = 'পণ্যটি বর্তমানে স্টকে নেই।'
        setTimeout(() => (toast.value = ''), 2500)
        return
    }
    router.post(route('cart.add'), { product_id: product.id, quantity: 1 }, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            router.visit(route('checkout'))
        }
    })
}

function setQty(productId, qty) {
    const it = localCartItems.value.find(x => x.product.id === productId)
    if (!it) return
    const max = Math.max(1, Number(it.product.available_quantity || 1))
    it.quantity = Math.max(1, Math.min(max, qty))
    updateCart()
}

function updateCart() {
    router.post(route('cart.update'), {
        items: localCartItems.value.map(i => ({ product_id: i.product.id, quantity: i.quantity })),
    }, { preserveScroll: true, preserveState: true })
}

function remove(productId) {
    router.post(route('cart.remove'), { product_id: productId }, { preserveScroll: true, preserveState: true })
}
</script>
