<template>
    <AdminLayout :title="t('ক্যাশ কাউন্টার (POS)', 'Point of Sale')">
        <div class="flex flex-col h-[calc(100vh-4rem)] overflow-hidden bg-slate-50 dark:bg-slate-900">
            <!-- Compact Header -->
            <div class="bg-white dark:bg-slate-800 shadow-xs border-b border-slate-200 dark:border-slate-700 px-3 sm:px-4 py-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-cash-register text-emerald-600 dark:text-emerald-400"></i>
                        <h1 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">{{ t('ক্যাশ কাউন্টার (POS)', 'Point of Sale (POS)') }}</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <span v-if="cartItems.length > 0" class="lg:hidden text-xs font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                            ৳{{ formatNumber(total) }}
                        </span>
                        <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">
                            {{ new Date().toLocaleTimeString() }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile POS Segmented Tabs (Shown only on mobile/tablet < lg) -->
            <div class="lg:hidden bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 p-1.5 flex items-center gap-1 shrink-0 shadow-xs">
                <button
                    @click="mobilePosTab = 'catalog'"
                    type="button"
                    :class="[
                        'flex-1 py-1.5 px-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5',
                        mobilePosTab === 'catalog'
                            ? 'bg-indigo-600 text-white shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-750'
                    ]"
                >
                    <i class="fas fa-boxes text-xs"></i>
                    <span>{{ t('পণ্য', 'Catalog') }}</span>
                </button>
                <button
                    @click="mobilePosTab = 'cart'"
                    type="button"
                    :class="[
                        'flex-1 py-1.5 px-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 relative',
                        mobilePosTab === 'cart'
                            ? 'bg-indigo-600 text-white shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-750'
                    ]"
                >
                    <i class="fas fa-shopping-cart text-xs"></i>
                    <span>{{ t('কার্ট', 'Cart') }}</span>
                    <span v-if="cartItems.length > 0" class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-emerald-500 text-white font-black">
                        {{ cartItems.length }}
                    </span>
                </button>
                <button
                    @click="mobilePosTab = 'checkout'"
                    type="button"
                    :class="[
                        'flex-1 py-1.5 px-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5',
                        mobilePosTab === 'checkout'
                            ? 'bg-emerald-600 text-white shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-750'
                    ]"
                >
                    <i class="fas fa-credit-card text-xs"></i>
                    <span>{{ t('পেমেন্ট', 'Pay') }}</span>
                </button>
            </div>

            <!-- Main Content -->
            <div class="flex flex-col lg:flex-row flex-1 min-h-0 relative">
                <!-- Left Panel - Products & Cart -->
                <div :class="[
                    'flex-col lg:w-2/3 min-w-0 bg-white border-r border-slate-200 dark:bg-slate-800 dark:border-slate-700 flex-1 min-h-0',
                    mobilePosTab === 'checkout' ? 'hidden lg:flex' : 'flex'
                ]">
                    <!-- Search Section -->
                    <div :class="['p-2 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50', mobilePosTab === 'cart' ? 'hidden lg:block' : 'block']">
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
                        <div :class="['p-2 overflow-auto flex-1 min-h-0', mobilePosTab === 'cart' ? 'hidden lg:block' : 'block']">
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

                        <!-- Floating Bottom Cart Indicator in Mobile Catalog Tab -->
                        <div
                            v-if="cartItems.length > 0 && mobilePosTab === 'catalog'"
                            class="lg:hidden p-2.5 bg-indigo-900 text-white flex items-center justify-between shadow-xl shrink-0 border-t border-indigo-700"
                        >
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-xs">
                                    {{ cartItems.length }}
                                </div>
                                <div>
                                    <p class="text-xs font-bold">৳{{ formatNumber(total) }}</p>
                                    <p class="text-[10px] text-indigo-300">{{ t('মোট বিল', 'Total Bill') }}</p>
                                </div>
                            </div>
                            <button
                                @click="mobilePosTab = 'cart'"
                                type="button"
                                class="px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm active:scale-95"
                            >
                                <span>{{ t('কার্ট দেখুন', 'View Cart') }}</span>
                                <i class="fas fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>

                        <!-- Cart Section -->
                        <div :class="[
                            'border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 flex flex-col',
                            mobilePosTab === 'cart' ? 'flex flex-1 min-h-0' : 'hidden lg:flex lg:h-3/5'
                        ]">
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
                                                                <img v-if="item.image" :src="getImageUrl(item.image)" :alt="item.name"
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
                                <!-- Mobile Proceed to Payment button on Cart tab -->
                                <div v-if="mobilePosTab === 'cart'" class="lg:hidden p-3 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between shrink-0">
                                    <div>
                                        <p class="text-[11px] text-slate-400">{{ t('সর্বমোট প্রদেয়', 'Total Payable') }}</p>
                                        <p class="text-base font-black text-indigo-600 dark:text-indigo-400">৳{{ formatNumber(total) }}</p>
                                    </div>
                                    <button
                                        @click="mobilePosTab = 'checkout'"
                                        :disabled="cartItems.length === 0"
                                        type="button"
                                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm disabled:opacity-50 active:scale-95"
                                    >
                                        <span>{{ t('পেমেন্ট ও চেকআউটে যান', 'Proceed to Pay') }}</span>
                                        <i class="fas fa-arrow-right text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Panel - Payment & Checkout (35% on desktop, full screen tab on mobile) -->
                <div :class="[
                    'flex-col lg:w-1/3 bg-slate-50 dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 flex-1 lg:flex-none min-h-0',
                    mobilePosTab === 'checkout' ? 'flex' : 'hidden lg:flex'
                ]">
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

                            <!-- Customer Info Badge if selected -->
                            <div v-if="selectedCustomerObj" class="mt-2 p-2 bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-lg flex items-center justify-between text-[11px]">
                                <div class="flex items-center gap-1.5 text-amber-800 dark:text-amber-300 font-medium">
                                    <i class="fas fa-user-tag text-xs"></i>
                                    <span>{{ t('পূর্বের বকেয়া', 'Previous Due') }}:</span>
                                </div>
                                <span class="font-bold text-amber-900 dark:text-amber-200 font-mono">
                                    ৳{{ formatNumber(selectedCustomerObj.balance || 0) }}
                                </span>
                            </div>
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

                        <!-- Pay Amount / Cash & Due Box -->
                        <div class="p-3 bg-white rounded-xl border border-slate-200/90 shadow-xs dark:bg-slate-800 dark:border-slate-700 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <i class="fas fa-money-bill-wave text-emerald-500"></i>
                                    <span>{{ t('নগদ পরিশোধ (ক্যাশ)', 'Paid Cash') }}</span>
                                </label>

                                <!-- Walk-in badge vs Customer helper buttons -->
                                <span v-if="!selectedCustomer" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                    <i class="fas fa-lock text-[9px]"></i>
                                    <span>{{ t('ফিক্সড (সম্পূর্ণ নগদ)', 'Fixed (Full Cash)') }}</span>
                                </span>
                                <div v-else class="flex items-center gap-1">
                                    <button type="button" @click="setFullPaid"
                                        class="px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/60 dark:text-emerald-300 transition-colors">
                                        {{ t('সম্পূর্ণ নগদ', 'Full Paid') }}
                                    </button>
                                    <button type="button" @click="setFullDue"
                                        class="px-2 py-0.5 text-[10px] font-bold rounded bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-900/60 dark:text-rose-300 transition-colors">
                                        {{ t('সম্পূর্ণ বাকি', 'Full Due') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Case 1: Walk-in Customer (Not editable, auto fixed, no credit) -->
                            <div v-if="!selectedCustomer" class="space-y-1.5">
                                <div class="flex items-center justify-between w-full px-3 py-2 bg-slate-100 dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 font-bold text-sm cursor-not-allowed select-none">
                                    <span class="font-mono text-emerald-600 dark:text-emerald-400 text-base font-black">
                                        ৳{{ formatNumber(total) }}
                                    </span>
                                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                        <i class="fas fa-lock text-[10px] text-amber-500"></i>
                                        <span>{{ t('সম্পূর্ণ নগদ আবশ্যক', 'Full cash only') }}</span>
                                    </span>
                                </div>
                                <p class="text-[11px] text-amber-700 dark:text-amber-400 flex items-center gap-1 leading-tight">
                                    <i class="fas fa-info-circle text-xs shrink-0"></i>
                                    <span>{{ t('সাধারণ কাস্টমারের জন্য বাকি বিক্রি সম্ভব নয় (নগদ এডিটেবল নয়)।', 'Walk-in customer must pay full in cash (Due not allowed).') }}</span>
                                </p>
                            </div>

                            <!-- Case 2: Registered Customer (Auto-filled with total, editable for partial/due) -->
                            <div v-else class="space-y-2">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">৳</span>
                                    <input type="number" v-model.number="paidAmount" @input="onPaidInput"
                                        class="w-full pl-7 pr-3 py-2 font-bold text-sm border rounded-lg transition-all focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                        :class="[
                                            dueAmount > 0
                                                ? 'border-amber-300 bg-amber-50/50 text-slate-900 dark:bg-slate-900 dark:border-amber-600 dark:text-white'
                                                : 'border-slate-200 bg-slate-50 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-white'
                                        ]"
                                        min="0" :max="total" step="0.01" placeholder="0.00" />
                                </div>

                                <!-- Due Banner if due > 0 -->
                                <div v-if="dueAmount > 0" class="p-2.5 rounded-lg bg-rose-50 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-900/60 flex items-center justify-between">
                                    <div class="flex items-center gap-1.5 text-xs text-rose-700 dark:text-rose-300 font-semibold">
                                        <i class="fas fa-hand-holding-usd text-rose-500"></i>
                                        <span>{{ t('বাকি টাকা (Due)', 'Due Balance') }}:</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-black text-rose-600 dark:text-rose-400 font-mono">
                                            ৳{{ formatNumber(dueAmount) }}
                                        </span>
                                        <p class="text-[10px] text-rose-500 dark:text-rose-400">
                                            {{ t('কাস্টমারের বকেয়ায় যুক্ত হবে', 'Added to customer balance') }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Full Paid badge if due == 0 -->
                                <div v-else-if="total > 0 && effectivePaid >= total" class="p-2 rounded-lg bg-emerald-50 border border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-800 flex items-center justify-between text-xs text-emerald-700 dark:text-emerald-300 font-semibold">
                                    <span class="flex items-center gap-1.5">
                                        <i class="fas fa-check-circle text-emerald-500"></i>
                                        <span>{{ t('সম্পূর্ণ পরিশোধিত (Paid in Full)', 'Paid in Full') }}</span>
                                    </span>
                                    <span class="font-mono font-bold">৳{{ formatNumber(total) }}</span>
                                </div>

                                <!-- Change amount if customer gave more cash than total -->
                                <div v-if="changeAmount > 0" class="p-2 rounded-lg bg-indigo-50 border border-indigo-200 dark:bg-indigo-950/40 dark:border-indigo-800 flex items-center justify-between text-xs text-indigo-700 dark:text-indigo-300 font-semibold">
                                    <span>{{ t('কাস্টমারকে ফেরত দিন (Change)', 'Change Return') }}:</span>
                                    <span class="font-mono font-bold">৳{{ formatNumber(changeAmount) }}</span>
                                </div>
                            </div>
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
                            <span v-if="processing">{{ t('প্রসেসিং হচ্ছে...', 'Processing...') }}</span>
                            <span v-else-if="dueAmount > 0">
                                {{ t(`বিক্রি সম্পন্ন করুন (নগদ: ৳${formatNumber(effectivePaid)}, বাকি: ৳${formatNumber(dueAmount)})`, `Complete Sale (Cash: ৳${formatNumber(effectivePaid)}, Due: ৳${formatNumber(dueAmount)})`) }}
                            </span>
                            <span v-else>
                                {{ t(`বিক্রি সম্পন্ন করুন (৳${formatNumber(total)})`, `Complete Sale (৳${formatNumber(total)})`) }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fixed Display Corner Overlay Complete Sale Button (Desktop Only, hidden on mobile) -->
        <div class="hidden lg:flex fixed bottom-6 right-6 z-50 items-center gap-2 drop-shadow-2xl">
            <button
                @click="processSale"
                :disabled="!canProcessSale || processing"
                class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-sm shadow-2xl shadow-emerald-950/60 border border-emerald-400/40 hover:scale-105 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 disabled:shadow-none"
            >
                <i v-if="!processing" class="fas fa-check-circle text-lg text-emerald-200"></i>
                <i v-else class="fas fa-spinner fa-spin text-lg"></i>

                <div class="flex flex-col text-left">
                    <span class="text-sm font-black tracking-wide">
                        {{ processing ? t('প্রসেসিং হচ্ছে...', 'Processing...') : t('বিক্রি সম্পন্ন করুন', 'Complete Sale') }}
                    </span>
                    <span v-if="dueAmount > 0" class="text-[10px] text-amber-200 font-semibold">
                        {{ t('বাকি:', 'Due:') }} ৳{{ formatNumber(dueAmount) }} | {{ t('নগদ:', 'Paid:') }} ৳{{ formatNumber(effectivePaid) }}
                    </span>
                    <span v-else class="text-[10px] text-emerald-100 font-medium">
                        {{ t('সম্পূর্ণ নগদ পরিশোধ', 'Full Paid') }}
                    </span>
                </div>

                <div class="ml-1 pl-3 border-l border-white/25 flex items-center gap-2">
                    <span class="bg-black/30 px-2.5 py-0.5 rounded-lg font-mono text-xs font-black text-white">
                        ৳{{ formatNumber(total) }}
                    </span>
                    <span class="hidden sm:inline-block bg-white/20 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold">
                        F4
                    </span>
                </div>
            </button>
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
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PosSearch from './components/PosSearch.vue'
import PosProductGrid from './components/PosProductGrid.vue'
import PosSuccessModal from './components/PosSuccessModal.vue'
import { Howl } from 'howler'
import { useLanguage } from '@/composables/useLanguage'
import { getImageUrl } from '@/utils/image'
import { offlineSync } from '@/services/offlineSync'

const { t } = useLanguage()

const props = defineProps({
    customers: Array,
    bankAccounts: Array,
    categories: Array
})

// State
const mobilePosTab = ref('catalog') // 'catalog' | 'cart' | 'checkout'
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
const paidAmount = ref(0)
const isPaidManuallyEdited = ref(false)

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

const selectedCustomerObj = computed(() => {
    if (!selectedCustomer.value) return null
    return props.customers?.find(c => c.id === selectedCustomer.value) || null
})

const effectivePaid = computed(() => {
    if (!selectedCustomer.value) {
        return total.value
    }
    const val = Number(paidAmount.value)
    if (isNaN(val) || val < 0) return 0
    return Math.min(total.value, val)
})

const dueAmount = computed(() => {
    if (!selectedCustomer.value) return 0
    return Math.max(0, total.value - effectivePaid.value)
})

const changeAmount = computed(() => {
    if (!selectedCustomer.value) return 0
    const rawVal = Number(paidAmount.value) || 0
    return Math.max(0, rawVal - total.value)
})

const canProcessSale = computed(() => {
    if (cartItems.value.length === 0 || !selectedBankAccount.value || total.value <= 0) {
        return false
    }
    // Walk-in customer cannot have due: effectivePaid must cover total
    if (!selectedCustomer.value && effectivePaid.value < total.value) {
        return false
    }
    return true
})

// Cash payment helpers
const setFullPaid = () => {
    paidAmount.value = total.value
    isPaidManuallyEdited.value = false
}

const setFullDue = () => {
    paidAmount.value = 0
    isPaidManuallyEdited.value = true
}

const onPaidInput = () => {
    isPaidManuallyEdited.value = true
}

// Watchers
watch(total, (newTotal) => {
    if (!selectedCustomer.value || !isPaidManuallyEdited.value) {
        paidAmount.value = newTotal
    } else if (paidAmount.value > newTotal) {
        paidAmount.value = newTotal
    }
})

watch(selectedCustomer, () => {
    paidAmount.value = total.value
    isPaidManuallyEdited.value = false
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
        // Cache to IndexedDB for offline resilience
        offlineSync.cacheItems('pos_products', response.data).catch(() => {});
    } catch (error) {
        console.error('Error fetching products, checking offline cache:', error);
        const cached = await offlineSync.getCachedItems('pos_products');
        if (cached && cached.length > 0) {
            products.value = cached;
        }
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
    const imageUrl = getImageUrl(product.image_url || product.image || product.images?.[0]?.url || product.images?.[0]?.image)

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
    paidAmount.value = 0
    isPaidManuallyEdited.value = false
}

const processSale = async () => {
    if (!canProcessSale.value || processing.value) return

    processing.value = true

    const finalPaid = !selectedCustomer.value
        ? total.value
        : effectivePaid.value
    const finalDue = !selectedCustomer.value
        ? 0
        : dueAmount.value

    const saleData = {
        customer_id: selectedCustomer.value,
        items: cartItems.value,
        subtotal: cartSummary.value.subtotal,
        discount: discount.value,
        total: total.value,
        paid: finalPaid,
        due: finalDue,
        bank_account_id: selectedBankAccount.value,
        note: note.value
    }

    // If device is offline, store in offline outbox immediately
    if (!navigator.onLine) {
        try {
            const queued = await offlineSync.queueOfflineSale(saleData)
            processing.value = false
            new Howl({ src: ['/sounds/success.mp3'] }).play()
            lastSale.value = {
                id: queued.offline_token,
                invoice_no: queued.offline_token,
                total: total.value,
                paid: finalPaid,
                due: finalDue,
                created_at: new Date().toISOString(),
                is_offline: true,
                sale_items: cartItems.value.map(ci => ({
                    product: { name: ci.name, sku: ci.sku },
                    quantity: ci.quantity,
                    unit_price: ci.unit_price,
                    total_price: ci.quantity * ci.unit_price
                }))
            }
            showSuccessModal.value = true
            return
        } catch (err) {
            processing.value = false
            console.error('Offline save error:', err)
            alert('অফলাইন সংরক্ষণ ব্যর্থ হয়েছে: ' + err.message)
            return
        }
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
        onError: async (errors) => {
            processing.value = false
            console.error('Sale error:', errors)
            if (!navigator.onLine) {
                const queued = await offlineSync.queueOfflineSale(saleData)
                new Howl({ src: ['/sounds/success.mp3'] }).play()
                lastSale.value = {
                    id: queued.offline_token,
                    invoice_no: queued.offline_token,
                    total: total.value,
                    paid: finalPaid,
                    due: finalDue,
                    created_at: new Date().toISOString(),
                    is_offline: true,
                    sale_items: cartItems.value
                }
                showSuccessModal.value = true
                return
            }
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
    if (lastSale.value?.is_offline) {
        window.print();
        return;
    }
    window.open(route('admin.pos.print-receipt', saleId), '_blank');
};

const handleGlobalKeydown = (e) => {
    // If modal is open, let Escape close it
    if (showSuccessModal.value) {
        if (e.key === 'Escape') {
            closeSuccessModal()
        }
        return
    }

    // F4 or F8 -> Complete Sale
    if (e.key === 'F4' || e.key === 'F8') {
        e.preventDefault()
        if (canProcessSale.value && !processing.value) {
            processSale()
        }
    }
    // F2 -> Reset Cart
    else if (e.key === 'F2') {
        e.preventDefault()
        resetCart()
    }
}

// Lifecycle
onMounted(() => {
    fetchProducts()
    window.addEventListener('keydown', handleGlobalKeydown)

    // Cache customers & bank accounts for offline selection
    if (props.customers?.length) {
        offlineSync.cacheItems('pos_customers', props.customers).catch(() => {})
    }
    if (props.bankAccounts?.length) {
        offlineSync.cacheItems('pos_bank_accounts', props.bankAccounts).catch(() => {})
    }
})

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown)
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
