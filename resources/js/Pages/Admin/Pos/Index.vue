<template>
    <AdminLayout :title="t('ক্যাশ কাউন্টার (POS)', 'Point of Sale')">
        <div class="flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-900">
            <!-- Compact Header -->
            <div class="bg-white dark:bg-slate-800 shadow-xs border-b border-slate-200 dark:border-slate-700 px-4 py-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-cash-register text-emerald-600 dark:text-emerald-400"></i>
                        <h1 class="text-sm font-bold text-slate-900 dark:text-white">{{ t('ক্যাশ কাউন্টার (POS)', 'Point of Sale (POS)') }}</h1>
                    </div>
                    <div class="text-xs font-mono text-slate-500 dark:text-slate-400">
                        {{ new Date().toLocaleTimeString() }}
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex flex-1 min-h-0">
                <!-- Left Panel - Products & Cart (65%) -->
                <div class="flex flex-col w-2/3 min-w-0 bg-white border-r border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <!-- Search Section -->
                    <div class="p-2 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                        <PosSearch
                            :categories="categories"
                            :selected-category="selectedCategory"
                            @search="handleSearch"
                            @filter="handleCategoryFilter"
                            @add-scanned-product="handleScannedProduct"
                        />
                    </div>

                    <!-- Products and Cart Container -->
                    <div class="flex flex-col flex-1 min-h-0">
                        <!-- Product Grid -->
                        <div class="p-2 overflow-auto h-5/5">
                            <div class="mb-1 flex items-center justify-between">
                                <h2 class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ t('পণ্যের তালিকা', 'Products Catalog') }}</h2>
                                <span class="text-[10px] text-slate-400">{{ displayProducts.length }} {{ t('টি পণ্য', 'items') }}</span>
                            </div>
                            <PosProductGrid
                                :products="displayProducts"
                                :loading="loading"
                                @add-to-cart="addToCart"
                            />
                        </div>

                        <!-- Cart Section -->
                        <div class="border-t border-slate-200 h-3/5 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                            <div class="flex flex-col h-full p-2">
                                <div class="flex items-center justify-between mb-2">
                                    <h2 class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                        <i class="fas fa-shopping-cart text-indigo-500"></i>
                                        <span>{{ t('কার্ট ও মেমো আইটেম', 'Cart Items') }}</span>
                                    </h2>
                                    <span class="bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold px-2 py-0.5 rounded-full text-xs">
                                        {{ cartItems.length }}
                                    </span>
                                </div>
                                <div class="flex-1 overflow-auto">
                                    <!-- Enhanced Cart Table -->
                                    <div class="bg-white border border-slate-200 rounded-xl dark:bg-slate-800 dark:border-slate-700 overflow-hidden shadow-xs">
                                        <table class="min-w-full text-xs">
                                            <thead class="bg-slate-100 dark:bg-slate-700/60 font-bold text-slate-600 dark:text-slate-300">
                                                <tr>
                                                    <th class="px-2.5 py-1.5 text-left">{{ t('পণ্য', 'Item') }}</th>
                                                    <th class="px-2 py-1.5 text-right">{{ t('মূল্য', 'Price') }}</th>
                                                    <th class="px-2 py-1.5 text-center">{{ t('পরিমাণ', 'Qty') }}</th>
                                                    <th class="px-2 py-1.5 text-right">{{ t('মোট', 'Total') }}</th>
                                                    <th class="px-1 py-1.5"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                                <tr v-for="(item, index) in cartItems" :key="index"
                                                    class="hover:bg-slate-50 dark:hover:bg-slate-750 transition-colors">
                                                    <td class="px-2.5 py-1.5">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0 w-7 h-7 mr-2 bg-slate-100 rounded-lg dark:bg-slate-700 overflow-hidden">
                                                                <img v-if="item.image" :src="item.image" :alt="item.name"
                                                                     class="object-cover w-full h-full" />
                                                            </div>
                                                            <div class="min-w-0">
                                                                <div class="text-xs font-bold text-slate-900 truncate dark:text-slate-100">
                                                                    {{ item.name }}
                                                                </div>
                                                                <div class="text-[11px] text-slate-400 font-mono">
                                                                    {{ item.sku }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-2 py-1.5 text-right text-xs font-medium text-slate-600 dark:text-slate-300">
                                                        ৳{{ formatNumber(item.unit_price) }}
                                                    </td>
                                                    <td class="px-2 py-1.5">
                                                        <div class="flex items-center justify-center space-x-1">
                                                            <button @click="decrementQuantity(index)"
                                                                class="flex items-center justify-center w-5 h-5 text-slate-500 rounded hover:bg-slate-200 dark:hover:bg-slate-600">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                                                </svg>
                                                            </button>
                                                            <input type="number" v-model.number="item.quantity"
                                                                class="w-11 text-center font-bold border border-slate-200 rounded text-xs p-0.5 dark:bg-slate-700 dark:border-slate-600 dark:text-white"
                                                                min="1" :max="item.max_stock"
                                                                @change="updateCartItemQuantity(index, item.quantity)"
                                                                @blur="updateCartItemQuantity(index, item.quantity)" />
                                                            <button @click="incrementQuantity(index)"
                                                                class="flex items-center justify-center w-5 h-5 text-slate-500 rounded hover:bg-slate-200 dark:hover:bg-slate-600">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="px-2 py-1.5 text-right text-xs font-bold text-slate-900 dark:text-white">
                                                        ৳{{ formatNumber(item.quantity * item.unit_price) }}
                                                    </td>
                                                    <td class="px-1 py-1.5 text-right">
                                                        <button @click="removeCartItem(index)"
                                                            class="text-rose-500 hover:text-rose-700 p-1 rounded hover:bg-rose-50 dark:hover:bg-rose-950">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr v-if="cartItems.length === 0">
                                                    <td colspan="5" class="px-4 py-8 text-xs text-center text-slate-400">
                                                        <i class="fas fa-shopping-basket text-2xl mb-1 text-slate-300"></i>
                                                        <div>{{ t('কার্ট খালি (কোনো পণ্য যোগ করা হয়নি)', 'Cart is empty') }}</div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot v-if="cartItems.length > 0" class="bg-slate-50 dark:bg-slate-700">
                                                <tr>
                                                    <td colspan="3" class="px-2 py-1.5 text-xs font-medium text-right text-slate-500 dark:text-slate-400">
                                                        {{ t('মোট আইটেম', 'Total Items') }}: {{ cartItems.length }}
                                                    </td>
                                                    <td class="px-2 py-1.5 text-xs font-bold text-right text-indigo-700 dark:text-indigo-300">
                                                        ৳{{ formatNumber(cartSummary.subtotal) }}
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Panel - Payment & Checkout (35%) -->
                <div class="flex flex-col w-1/3 bg-slate-50 dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800">
                    <!-- Payment Header -->
                    <div class="p-3 text-white bg-indigo-600 dark:bg-indigo-700 flex items-center justify-between shadow-xs">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-credit-card"></i>
                            <h2 class="text-xs font-bold uppercase tracking-wider">{{ t('পেমেন্ট ও চেকআউট', 'Payment & Checkout') }}</h2>
                        </div>
                        <span class="text-[10px] font-mono bg-white/20 px-1.5 py-0.5 rounded">F4</span>
                    </div>

                    <!-- Payment Content -->
                    <div class="p-3 space-y-2.5 overflow-auto flex-1">
                        <!-- Customer Selection -->
                        <div class="p-2.5 bg-white rounded-xl border border-slate-200/90 shadow-xs dark:bg-slate-800 dark:border-slate-700">
                            <label class="block mb-1 text-xs font-bold text-slate-700 dark:text-slate-300">
                                {{ t('কাস্টমার নির্বাচন', 'Select Customer') }}
                            </label>
                            <select v-model="selectedCustomer"
                                class="w-full py-1.5 text-xs font-medium text-slate-900 bg-slate-50 border-slate-200 rounded-lg dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-500">
                                <option :value="null">{{ t('সাধারণ কাস্টমার (Walk-in)', 'Walk-in Customer') }}</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.name }} ({{ customer.phone || 'N/A' }})
                                </option>
                            </select>
                        </div>

                        <!-- Bank / Cash Account -->
                        <div class="p-2.5 bg-white rounded-xl border border-slate-200/90 shadow-xs dark:bg-slate-800 dark:border-slate-700">
                            <label class="block mb-1 text-xs font-bold text-slate-700 dark:text-slate-300">
                                {{ t('পেমেন্ট অ্যাকাউন্ট', 'Payment Account') }}
                            </label>
                            <select v-model="selectedBankAccount"
                                class="w-full py-1.5 text-xs font-medium text-slate-900 bg-slate-50 border-slate-200 rounded-lg dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-500">
                                <option :value="null">{{ t('অ্যাকাউন্ট সিলেক্ট করুন', 'Select Account') }}</option>
                                <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                    {{ account.account_name }} ({{ account.account_type || 'Cash' }})
                                </option>
                            </select>
                        </div>

                        <!-- Order Summary Box -->
                        <div class="p-3 bg-white rounded-xl border border-slate-200/90 shadow-xs dark:bg-slate-800 dark:border-slate-700 space-y-2">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider pb-1 border-b border-slate-100 dark:border-slate-700">
                                {{ t('বিল সারাংশ', 'Bill Summary') }}
                            </h3>

                            <div class="space-y-1.5 text-xs font-medium">
                                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                    <span>{{ t('সাবটোটাল', 'Subtotal') }}</span>
                                    <span class="font-bold text-slate-900 dark:text-white">৳{{ formatNumber(cartSummary.subtotal) }}</span>
                                </div>

                                <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                                    <span>{{ t('ডিসকাউন্ট (ছাড়)', 'Discount') }}</span>
                                    <input type="number" v-model.number="discount"
                                        class="w-20 text-right font-bold text-xs border border-slate-200 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white px-2 py-1"
                                        min="0" step="0.01" placeholder="0" />
                                </div>

                                <div class="pt-2 border-t border-slate-100 dark:border-slate-700">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-bold text-slate-900 dark:text-white">{{ t('সর্বমোট টাকা', 'Total Payable') }}</span>
                                        <span class="text-base font-black text-indigo-600 dark:text-indigo-400">
                                            ৳{{ formatNumber(total) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pay Amount Box -->
                        <div class="p-3 rounded-xl border border-emerald-200 bg-emerald-50/70 dark:bg-emerald-950/30 dark:border-emerald-800 text-center">
                            <p class="text-[11px] font-bold text-emerald-800 dark:text-emerald-400 uppercase tracking-wider">
                                {{ t('পরিশোধিত টাকা (ক্যাশ)', 'Payable Amount') }}
                            </p>
                            <p class="text-xl font-black text-emerald-700 dark:text-emerald-300 mt-0.5">
                                ৳{{ formatNumber(total) }}
                            </p>
                        </div>

                        <!-- Note Box -->
                        <div class="p-2.5 bg-white rounded-xl border border-slate-200/90 shadow-xs dark:bg-slate-800 dark:border-slate-700">
                            <label class="block mb-1 text-xs font-bold text-slate-700 dark:text-slate-300">{{ t('নোট / মন্তব্য (ঐচ্ছিক)', 'Note (Optional)') }}</label>
                            <textarea v-model="note" rows="2"
                                class="w-full p-2 text-xs text-slate-900 bg-slate-50 border border-slate-200 rounded-lg dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-1 focus:ring-indigo-500"
                                :placeholder="t('মেমো সম্পর্কিত কোনো মন্তব্য...', 'Add note...')"></textarea>
                        </div>
                    </div>

                    <!-- Bottom Action Buttons -->
                    <div class="p-3 space-y-2 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700">
                        <button @click="resetCart"
                            class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                            <i class="fas fa-redo-alt text-xs"></i>
                            <span>{{ t('কার্ট খালি করুন (Reset)', 'Reset Cart') }}</span>
                        </button>

                        <button @click="processSale" :disabled="!canProcessSale || processing"
                            class="flex items-center justify-center w-full py-3 text-xs font-bold text-white transition-all rounded-xl shadow-md bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed active:scale-98">
                            <i v-if="!processing" class="fas fa-check-circle mr-1.5 text-sm"></i>
                            <i v-else class="fas fa-spinner fa-spin mr-1.5 text-sm"></i>
                            <span>{{ processing ? t('প্রসেসিং হচ্ছে...', 'Processing...') : t('বিক্রি সম্পন্ন করুন (Print Invoice)', 'Complete Sale') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <PosSuccessModal
            v-if="showSuccessModal"
            :sale="lastSale"
            @close="closeSuccessModal"
            @print="printReceipt"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PosSearch from './components/PosSearch.vue'
import PosProductGrid from './components/PosProductGrid.vue'
import PosSuccessModal from './components/PosSuccessModal.vue'
import { Howl } from 'howler'
import { useLanguage } from '@/composables/useLanguage'

const { t } = useLanguage()

const props = defineProps({
    customers: Array,
    bankAccounts: Array,
    categories: Array
})

// State
const searchQuery = ref('')
const products = ref([])
const searchResults = ref([])
const cartItems = ref([])
const selectedCategory = ref(null)
const loading = ref(false)
const processing = ref(false)
const showSuccessModal = ref(false)
const lastSale = ref(null)

// Payment state
const selectedCustomer = ref(null)
const selectedBankAccount = ref(1)
const discount = ref(0)
const note = ref('')

// Computed
const displayProducts = computed(() => {
    return searchQuery.value ? searchResults.value : products.value
})

const cartSummary = computed(() => {
    const subtotal = cartItems.value.reduce((sum, item) =>
        sum + (item.quantity * item.unit_price), 0
    )
    return {
        subtotal,
        items: cartItems.value.length
    }
})

const total = computed(() => {
    return Math.max(0, cartSummary.value.subtotal - discount.value)
})

const canProcessSale = computed(() => {
    return cartItems.value.length > 0 && selectedBankAccount.value && total.value > 0
})

// Methods
const formatNumber = (value) => {
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const fetchProducts = async () => {
    loading.value = true
    try {
        const response = await axios.get(route('admin.pos.products'));
        products.value = response.data;
    } catch (error) {
        console.error('Error fetching products:', error);
    } finally {
        loading.value = false;
    }
};

const handleSearch = async (query) => {
    searchQuery.value = query;
    if (!query) {
        searchResults.value = [];
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get(route('admin.pos.search-products'), {
            params: { search: query }
        });
        searchResults.value = response.data;
    } catch (error) {
        console.error('Error searching products:', error);
    } finally {
        loading.value = false;
    }
};

const handleCategoryFilter = async (categoryId) => {
    selectedCategory.value = categoryId;
    loading.value = true;
    try {
        const response = await axios.get(route('admin.pos.products.by.category'), {
            params: { category_id: categoryId }
        });
        products.value = response.data;
        searchQuery.value = '';
    } catch (error) {
        console.error('Error filtering products:', error);
    } finally {
        loading.value = false;
    }
};

const addToCart = (product) => {
    // Check if product has stock
    if (!product.stock || product.stock <= 0) {
        const errorSound = new Howl({ src: ['/sounds/error.mp3'] })
        errorSound.play()
        alert(`❌ ${product.name} is out of stock!`)
        return
    }

    const existingItem = cartItems.value.find(item => item.product_id === product.id)
    const imageUrl = product.image_url ?? (product.image ? `/storage/${product.image}` : null)

    if (existingItem) {
        // Check if we can increment quantity
        if (existingItem.quantity >= product.stock) {
            const errorSound = new Howl({ src: ['/sounds/error.mp3'] })
            errorSound.play()
            alert(`⚠️ Only ${product.stock} units available for ${product.name}`)
            return
        }
        existingItem.quantity++
        existingItem.max_stock = product.stock // Track max available
        existingItem.image = imageUrl
    } else {
        cartItems.value.push({
            product_id: product.id,
            name: product.name,
            sku: product.sku,
            unit_price: Number(product.selling_price),
            quantity: 1,
            max_stock: product.stock, // Track max available
            image: imageUrl,
        })
    }

    const beepSound = new Howl({ src: ['/sounds/beep.mp3'] })
    beepSound.play()
}

const handleScannedProduct = (product) => {
    addToCart(product);
};

const updateCartItemQuantity = (index, quantity) => {
    const item = cartItems.value[index]

    if (quantity <= 0) {
        alert('❌ Quantity must be at least 1')
        item.quantity = 1
        return
    }

    if (quantity > item.max_stock) {
        alert(`⚠️ Only ${item.max_stock} units available for ${item.name}`)
        item.quantity = item.max_stock
        return
    }

    item.quantity = quantity
}

const incrementQuantity = (index) => {
    const item = cartItems.value[index]

    if (item.quantity >= item.max_stock) {
        const errorSound = new Howl({ src: ['/sounds/error.mp3'] })
        errorSound.play()
        alert(`⚠️ Only ${item.max_stock} units available for ${item.name}`)
        return
    }

    item.quantity++
}

const decrementQuantity = (index) => {
    if (cartItems.value[index].quantity > 1) {
        cartItems.value[index].quantity--
    }
}

const removeCartItem = (index) => {
    cartItems.value.splice(index, 1)
}

const resetCart = () => {
    cartItems.value = []
    selectedCategory.value = null
    searchQuery.value = ''
    discount.value = 0
    note.value = ''
}

const processSale = () => {
    if (!canProcessSale.value || processing.value) return

    processing.value = true

    const saleData = {
        customer_id: selectedCustomer.value,
        items: cartItems.value,
        subtotal: cartSummary.value.subtotal,
        discount: discount.value,
        total: total.value,
        paid: total.value,
        due: 0,
        bank_account_id: selectedBankAccount.value,
        note: note.value
    }

    router.post(route('admin.pos.store'), saleData, {
        preserveScroll: true,
        onSuccess: (page) => {
            processing.value = false
            if (page.props.flash?.sale) {
                lastSale.value = page.props.flash.sale
                new Howl({ src: ['/sounds/success.mp3'] }).play()
                showSuccessModal.value = true
            }
        },
        onError: (errors) => {
            processing.value = false
            console.error('Sale error:', errors)
            new Howl({ src: ['/sounds/error.mp3'] }).play()
            alert(errors.error || 'Failed to process sale. Please try again.')
        },
        onFinish: () => {
            processing.value = false
        }
    })
};

const closeSuccessModal = () => {
    showSuccessModal.value = false
    resetCart()
}

const printReceipt = (saleId) => {
    window.open(route('admin.pos.print-receipt', saleId), '_blank');
};

// Lifecycle
onMounted(() => {
    fetchProducts()
})
</script>

<style scoped>
::-webkit-scrollbar {
    width: 4px;
}

::-webkit-scrollbar-track {
    @apply bg-gray-100 dark:bg-gray-800;
}

::-webkit-scrollbar-thumb {
    @apply bg-gray-400 dark:bg-gray-600 rounded-full;
}

::-webkit-scrollbar-thumb:hover {
    @apply bg-gray-500 dark:bg-gray-500;
}
</style>
