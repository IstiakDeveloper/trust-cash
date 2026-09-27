<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import {
    Layers,
    Plus,
    Check,
    Edit2,
    Trash2,
    X,
    Shield,
    CheckCircle2
} from 'lucide-vue-next';

const props = defineProps({
    plans: Array,
});

const showModal = ref(false);
const isEditing = ref(false);
const editingPlan = ref(null);

const availableFeatures = [
    { key: 'pos', label: 'POS Terminal & Fast Checkout' },
    { key: 'inventory', label: 'Inventory & Stock Tracking' },
    { key: 'sales', label: 'Sales Management & Receipts' },
    { key: 'customers', label: 'Customer Due & Credit Ledger' },
    { key: 'cash_register', label: 'Daily Cash Register & Closing' },
    { key: 'suppliers', label: 'Supplier Ledger & Purchases' },
    { key: 'purchases', label: 'Purchase Invoicing & Auto Restock' },
    { key: 'sale_returns', label: 'Sales Returns & Refunds' },
    { key: 'purchase_returns', label: 'Purchase Returns to Suppliers' },
    { key: 'multi_branch', label: 'Multi-Branch & Stock Transfer' },
    { key: 'banking', label: 'Bank Accounts, Cash & Fund Transfer' },
    { key: 'advanced_accounting', label: 'Advanced Accounting & Expenses' },
    { key: 'financial_reports', label: 'Profit & Loss, Balance Sheet' },
    { key: 'barcode_scanner', label: 'Barcode Scanner & Generator' },
    { key: 'receipt_printer', label: 'Thermal Receipt Printing (58mm/80mm)' },
    { key: 'sms_alerts', label: 'SMS Alerts & Customer Notifications' },
    { key: 'api_access', label: 'Developer API & Webhook Access' },
    { key: 'priority_support', label: '24/7 Dedicated Priority Support' },
];

const form = useForm({
    name: '',
    description: '',
    price_monthly: 799,
    price_yearly: 7990,
    max_users: 2,
    max_products: 500,
    max_branches: 1,
    features: ['pos', 'inventory', 'sales', 'customers', 'cash_register', 'barcode_scanner', 'receipt_printer'],
    is_active: true,
    sort_order: 1,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingPlan.value = null;
    form.reset();
    form.features = ['pos', 'inventory', 'sales', 'customers', 'cash_register', 'barcode_scanner', 'receipt_printer'];
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (plan) => {
    isEditing.value = true;
    editingPlan.value = plan;
    form.name = plan.name;
    form.description = plan.description || '';
    form.price_monthly = Number(plan.price_monthly);
    form.price_yearly = Number(plan.price_yearly);
    form.max_users = plan.max_users;
    form.max_products = plan.max_products;
    form.max_branches = plan.max_branches;
    form.features = Array.isArray(plan.features) ? [...plan.features] : [];
    form.is_active = Boolean(plan.is_active);
    form.sort_order = plan.sort_order || 1;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('super-admin.plans.update', editingPlan.value.id), {
            onSuccess: () => {
                showModal.value = false;
            }
        });
    } else {
        form.post(route('super-admin.plans.store'), {
            onSuccess: () => {
                showModal.value = false;
            }
        });
    }
};

const deletePlan = (plan) => {
    if (confirm(`Are you sure you want to delete plan "${plan.name}"?`)) {
        router.delete(route('super-admin.plans.destroy', plan.id));
    }
};
</script>

<template>
    <Head title="Subscription Plans | TrustCash Super Admin" />

    <SuperAdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white tracking-tight">Subscription Plans & Pricing</h1>
            <p class="text-xs text-slate-400">Configure tiers, pricing in ৳, limits & feature permissions</p>
        </template>

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <p class="text-xs text-slate-400">
                    Changes here immediately reflect on public landing pricing cards and new registrations.
                </p>
                <button
                    @click="openCreateModal"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-medium text-xs shadow-md shadow-emerald-500/10 transition"
                >
                    <Plus class="w-4 h-4" />
                    <span>Create New Plan</span>
                </button>
            </div>

            <!-- Plans Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="rounded-2xl bg-slate-900 border border-slate-800 p-6 flex flex-col justify-between shadow-sm relative overflow-hidden"
                >
                    <div
                        v-if="!plan.is_active"
                        class="absolute top-3 right-3 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20"
                    >
                        INACTIVE
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-bold text-white">{{ plan.name }}</h2>
                            <span class="text-xs px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20">
                                {{ plan.tenants_count || 0 }} stores active
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 min-h-[32px]">{{ plan.description || 'Standard plan tier.' }}</p>

                        <!-- Pricing -->
                        <div class="mt-4 pt-4 border-t border-slate-800 flex items-baseline gap-1">
                            <span class="text-2xl font-black text-white">৳ {{ Number(plan.price_monthly).toLocaleString() }}</span>
                            <span class="text-xs text-slate-400">/month</span>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            Annual: <strong class="text-slate-200">৳ {{ Number(plan.price_yearly).toLocaleString() }}</strong> /year
                        </div>

                        <!-- Quota Limits -->
                        <div class="mt-4 p-3 rounded-xl bg-slate-950/70 border border-slate-800/80 space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Max Products:</span>
                                <span class="font-bold text-white">{{ plan.max_products.toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Staff / Users:</span>
                                <span class="font-bold text-white">{{ plan.max_users }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Branches:</span>
                                <span class="font-bold text-white">{{ plan.max_branches }}</span>
                            </div>
                        </div>

                        <!-- Features Preview -->
                        <div class="mt-4 space-y-1.5 text-[11px]">
                            <div class="text-slate-400 font-semibold uppercase tracking-wider text-[10px]">Included Features:</div>
                            <div
                                v-for="(feat, idx) in (plan.features || []).slice(0, 7)"
                                :key="idx"
                                class="flex items-center gap-1.5 text-slate-300"
                            >
                                <Check class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                                <span class="capitalize">{{ feat.replace(/_/g, ' ') }}</span>
                            </div>
                            <div v-if="(plan.features || []).length > 7" class="text-[10px] text-emerald-400 pt-1">
                                + {{ plan.features.length - 7 }} more features enabled
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between gap-2">
                        <button
                            @click="openEditModal(plan)"
                            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 transition"
                        >
                            <Edit2 class="w-3.5 h-3.5" />
                            <span>Edit Tier</span>
                        </button>
                        <button
                            @click="deletePlan(plan)"
                            title="Delete Plan"
                            class="p-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plan Modal (Create / Edit) -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto">
            <div class="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl space-y-4 my-8 max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <Layers class="w-5 h-5 text-emerald-400" />
                        {{ isEditing ? 'Edit Plan: ' + editingPlan?.name : 'Create New Subscription Plan' }}
                    </h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4 text-xs overflow-y-auto pr-1 flex-1">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-400 mb-1">Plan Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. Enterprise Plus"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Display Sort Order</label>
                            <input
                                v-model.number="form.sort_order"
                                type="number"
                                min="1"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">Description / Tagline</label>
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="Ideal for growing businesses and multi-store shops..."
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-400 mb-1">Monthly Price (৳ BDT)</label>
                            <input
                                v-model.number="form.price_monthly"
                                type="number"
                                required
                                min="0"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-bold focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Yearly Price (৳ BDT)</label>
                            <input
                                v-model.number="form.price_yearly"
                                type="number"
                                required
                                min="0"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-bold focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-slate-400 mb-1">Max Products</label>
                            <input
                                v-model.number="form.max_products"
                                type="number"
                                min="1"
                                required
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Max Users / Staff</label>
                            <input
                                v-model.number="form.max_users"
                                type="number"
                                min="1"
                                required
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Max Branches</label>
                            <input
                                v-model.number="form.max_branches"
                                type="number"
                                min="1"
                                required
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <!-- Features Checklist -->
                    <div>
                        <label class="block text-slate-400 mb-2 font-semibold">Enabled Modules & Features</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3 bg-slate-950 rounded-xl border border-slate-800 max-h-48 overflow-y-auto">
                            <label
                                v-for="feat in availableFeatures"
                                :key="feat.key"
                                class="flex items-center gap-2 text-slate-300 hover:text-white cursor-pointer select-none py-1"
                            >
                                <input
                                    type="checkbox"
                                    :value="feat.key"
                                    v-model="form.features"
                                    class="rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0"
                                />
                                <span>{{ feat.label }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="is_active"
                            v-model="form.is_active"
                            class="rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0"
                        />
                        <label for="is_active" class="text-slate-300 font-medium cursor-pointer">
                            Active and Visible to Public Customers
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold"
                        >
                            {{ isEditing ? 'Save Changes' : 'Create Plan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </SuperAdminLayout>
</template>