<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import {
    Store,
    Search,
    Plus,
    ExternalLink,
    ArrowUpRight,
    Clock,
    Lock,
    KeyRound,
    CheckCircle2,
    XCircle,
    X,
    ShieldAlert,
    Percent,
    Tag,
    Sparkles,
    Check,
    AlertCircle,
    Trash2
} from 'lucide-vue-next';

const props = defineProps({
    tenants: Object,
    plans: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const plan_id = ref(props.filters?.plan_id || '');

const handleFilter = () => {
    router.get(route('super-admin.tenants.index'), {
        search: search.value,
        status: status.value,
        plan_id: plan_id.value,
    }, { preserveState: true, replace: true });
};

// Modals state
const showCreateModal = ref(false);
const showApproveModal = ref(false);
const showExtendModal = ref(false);
const showPasswordModal = ref(false);
const selectedTenant = ref(null);
const selectedApproveTenant = ref(null);

// Forms
const createForm = useForm({
    business_name: '',
    subdomain: '',
    owner_name: '',
    email: '',
    phone: '',
    password: 'password123',
    plan_id: props.plans?.[0]?.id || 1,
    status: 'trial',
    trial_days: 14,
    billing_cycle: 'monthly',
    discount_type: 'none',
    discount_value: 0,
    discount_note: '',
    custom_price: null,
});

const approveForm = useForm({
    approval_type: 'trial', // trial or active
    days: 14,
    plan_id: null,
    billing_cycle: 'monthly',
    discount_type: 'none',
    discount_value: 0,
    discount_note: '',
    custom_price: null,
});

const extendForm = useForm({
    days: 15,
});

const passwordForm = useForm({
    password: '',
});

const showDeleteModal = ref(false);
const deleteForm = useForm({
    password: '',
});

// Realtime Subdomain Checking for Create Store
const subdomainStatus = ref({
    checking: false,
    available: null,
    message: '',
});

let debounceTimer = null;

const generateSlug = () => {
    if (!createForm.subdomain || createForm.subdomain === '') {
        const slug = createForm.business_name
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .substring(0, 40);
        createForm.subdomain = slug;
        checkSubdomainDebounced();
    }
};

const checkSubdomainDebounced = () => {
    clearTimeout(debounceTimer);
    const sub = (createForm.subdomain || '').trim().toLowerCase();
    createForm.subdomain = sub;

    if (!sub || sub.length < 3) {
        subdomainStatus.value = { checking: false, available: null, message: 'Subdomain must be at least 3 characters.' };
        return;
    }

    subdomainStatus.value.checking = true;
    debounceTimer = setTimeout(async () => {
        try {
            const res = await fetch(`/api/check-subdomain?subdomain=${encodeURIComponent(sub)}`);
            const data = await res.json();
            subdomainStatus.value = {
                checking: false,
                available: data.available,
                message: data.message,
            };
        } catch (e) {
            subdomainStatus.value = { checking: false, available: null, message: '' };
        }
    }, 350);
};

// Approval Price Calculation
const selectedApprovePlan = computed(() => {
    return props.plans.find(p => p.id === approveForm.plan_id) || props.plans[0];
});

const calculatedApprovePrice = computed(() => {
    if (!selectedApprovePlan.value) return { base: 0, discount: 0, final: 0 };
    const base = approveForm.billing_cycle === 'yearly'
        ? Number(selectedApprovePlan.value.price_yearly || 0)
        : Number(selectedApprovePlan.value.price_monthly || 0);

    if (approveForm.custom_price !== null && approveForm.custom_price !== '' && approveForm.custom_price >= 0) {
        const custom = Number(approveForm.custom_price);
        return {
            base,
            discount: Math.max(0, base - custom),
            final: custom,
        };
    }

    let discount = 0;
    const val = Number(approveForm.discount_value || 0);
    if (approveForm.discount_type === 'percentage' && val > 0) {
        discount = (base * val) / 100;
    } else if (approveForm.discount_type === 'fixed' && val > 0) {
        discount = val;
    }

    discount = Math.min(base, Math.max(0, discount));
    const final = Math.max(0, base - discount);

    return {
        base,
        discount,
        final,
    };
});

// Create Store Price Calculation
const selectedCreatePlan = computed(() => {
    return props.plans.find(p => p.id === createForm.plan_id) || props.plans[0];
});

const calculatedCreatePrice = computed(() => {
    if (!selectedCreatePlan.value) return { base: 0, discount: 0, final: 0 };
    const base = createForm.billing_cycle === 'yearly'
        ? Number(selectedCreatePlan.value.price_yearly || 0)
        : Number(selectedCreatePlan.value.price_monthly || 0);

    let discount = 0;
    const val = Number(createForm.discount_value || 0);
    if (createForm.discount_type === 'percentage' && val > 0) {
        discount = (base * val) / 100;
    } else if (createForm.discount_type === 'fixed' && val > 0) {
        discount = val;
    }

    discount = Math.min(base, Math.max(0, discount));
    const final = Math.max(0, base - discount);

    return { base, discount, final };
});

const openApproveModal = (tenant) => {
    selectedApproveTenant.value = tenant;
    approveForm.approval_type = 'trial';
    approveForm.days = 14;
    approveForm.plan_id = tenant.plan_id || props.plans?.[0]?.id;
    approveForm.billing_cycle = 'monthly';
    approveForm.discount_type = tenant.discount_type || 'none';
    approveForm.discount_value = Number(tenant.discount_value || 0);
    approveForm.discount_note = tenant.discount_note || '';
    approveForm.custom_price = null;
    showApproveModal.value = true;
};

const submitApprove = () => {
    if (!selectedApproveTenant.value) return;
    approveForm.post(route('super-admin.tenants.approve', selectedApproveTenant.value.id), {
        onSuccess: () => {
            showApproveModal.value = false;
        }
    });
};

const openExtendModal = (tenant) => {
    selectedTenant.value = tenant;
    showExtendModal.value = true;
};

const submitExtend = () => {
    if (!selectedTenant.value) return;
    extendForm.post(route('super-admin.tenants.extend-trial', selectedTenant.value.id), {
        onSuccess: () => {
            showExtendModal.value = false;
        }
    });
};

const openPasswordModal = (tenant) => {
    selectedTenant.value = tenant;
    passwordForm.password = '';
    showPasswordModal.value = true;
};

const submitPasswordReset = () => {
    if (!selectedTenant.value) return;
    passwordForm.post(route('super-admin.tenants.reset-password', selectedTenant.value.id), {
        onSuccess: () => {
            showPasswordModal.value = false;
        }
    });
};

const toggleStatus = (tenant) => {
    if (confirm(`Are you sure you want to ${tenant.status === 'suspended' ? 'activate' : 'suspend'} store "${tenant.name}"?`)) {
        router.post(route('super-admin.tenants.toggle-status', tenant.id));
    }
};

const openDeleteModal = (tenant) => {
    selectedTenant.value = tenant;
    deleteForm.reset();
    deleteForm.clearErrors();
    showDeleteModal.value = true;
};

const submitDelete = () => {
    if (!selectedTenant.value) return;
    deleteForm.delete(route('super-admin.tenants.destroy', selectedTenant.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            deleteForm.reset();
        },
    });
};

const submitCreate = () => {
    createForm.post(route('super-admin.tenants.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

const formatPrice = (amount) => {
    return '৳' + Number(amount || 0).toLocaleString('en-BD', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};
</script>

<template>
    <Head title="Store Management | TrustCash Super Admin" />

    <SuperAdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white tracking-tight">Stores & Tenant Directory</h1>
            <p class="text-xs text-slate-400">Complete control over registered shops, approval workflows & access rights</p>
        </template>

        <div class="space-y-6">
            <!-- Actions & Filters Bar -->
            <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
                    <div class="relative flex-1 min-w-[220px]">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="search"
                            @keyup.enter="handleFilter"
                            type="text"
                            placeholder="Search by store name, subdomain, email or phone..."
                            class="w-full pl-9 pr-4 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500/50"
                        />
                    </div>

                    <select
                        v-model="status"
                        @change="handleFilter"
                        class="bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 py-2 px-3 focus:outline-none focus:border-emerald-500/50"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending Approval</option>
                        <option value="active">Active</option>
                        <option value="trial">Trial</option>
                        <option value="suspended">Suspended</option>
                    </select>

                    <select
                        v-model="plan_id"
                        @change="handleFilter"
                        class="bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 py-2 px-3 focus:outline-none focus:border-emerald-500/50"
                    >
                        <option value="">All Plans</option>
                        <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>

                <button
                    @click="showCreateModal = true"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-medium text-xs shadow-md shadow-emerald-500/10 transition"
                >
                    <Plus class="w-4 h-4" />
                    <span>Create Store Manually</span>
                </button>
            </div>

            <!-- Stores Table -->
            <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Store & Domain</th>
                                <th class="py-3 px-4 font-semibold">Owner & Contact</th>
                                <th class="py-3 px-4 font-semibold">Plan & Pricing</th>
                                <th class="py-3 px-4 font-semibold">Status</th>
                                <th class="py-3 px-4 font-semibold">Expiry / Trial</th>
                                <th class="py-3 px-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-slate-800/30 transition">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white text-sm flex items-center gap-1.5">
                                        {{ tenant.name }}
                                        <span v-if="tenant.discount_type && tenant.discount_type !== 'none' && tenant.discount_value > 0"
                                            class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30"
                                            :title="tenant.discount_note || 'Special Approved Discount'">
                                            <Tag class="w-3 h-3" />
                                            {{ tenant.discount_type === 'percentage' ? `${tenant.discount_value}% OFF` : `৳${tenant.discount_value} OFF` }}
                                        </span>
                                    </div>
                                    <a
                                        :href="'http://' + (tenant.domains?.[0]?.domain || tenant.id)"
                                        target="_blank"
                                        class="text-[11px] text-emerald-400 hover:underline inline-flex items-center gap-1 mt-0.5"
                                    >
                                        {{ tenant.domains?.[0]?.domain || tenant.id }}
                                        <ExternalLink class="w-3 h-3" />
                                    </a>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="text-slate-200 font-medium">{{ tenant.email }}</div>
                                    <div class="text-slate-400 text-[11px]">{{ tenant.phone || 'No phone' }}</div>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-200 font-semibold text-[11px]">
                                            {{ tenant.plan?.name || 'Default' }}
                                        </span>
                                        <span class="text-slate-300 font-bold text-[11px]">
                                            ৳{{ tenant.plan?.price_monthly || 0 }}/mo
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-1">
                                        Max {{ tenant.plan?.max_products || 500 }} prods • {{ tenant.plan?.max_users || 2 }} users
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border',
                                            tenant.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                                            tenant.status === 'trial' ? 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20' :
                                            tenant.status === 'pending' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20 animate-pulse' :
                                            'bg-rose-500/10 text-rose-400 border-rose-500/20'
                                        ]"
                                    >
                                        {{ tenant.status === 'pending' ? 'Pending Approval' : tenant.status }}
                                    </span>
                                </td>

                                <td class="py-3 px-4">
                                    <div v-if="tenant.trial_ends_at" class="text-slate-300 font-medium">
                                        {{ new Date(tenant.trial_ends_at).toLocaleDateString() }}
                                    </div>
                                    <div v-else class="text-slate-400">Regular Subscription</div>
                                    <button
                                        @click="openExtendModal(tenant)"
                                        class="text-[10px] text-cyan-400 hover:underline mt-0.5 flex items-center gap-1"
                                    >
                                        <Clock class="w-3 h-3" /> Extend Validity
                                    </button>
                                </td>

                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Approve with Discount Button for Pending Stores -->
                                        <button
                                            v-if="tenant.status === 'pending'"
                                            @click="openApproveModal(tenant)"
                                            title="Approve Store with Discount"
                                            class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white border border-emerald-500/30 text-xs font-bold flex items-center gap-1.5 transition shadow-sm mr-1"
                                        >
                                            <CheckCircle2 class="w-3.5 h-3.5" />
                                            <span>Approve</span>
                                        </button>

                                        <!-- Profile / Details Link -->
                                        <Link
                                            :href="route('super-admin.tenants.show', tenant.id)"
                                            title="Store Profile & Quotas"
                                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition"
                                        >
                                            <Store class="w-3.5 h-3.5" />
                                        </Link>

                                        <!-- Impersonate Button -->
                                        <a
                                            :href="route('super-admin.tenants.impersonate', tenant.id)"
                                            target="_blank"
                                            title="1-Click Login As Store Admin"
                                            class="p-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-xs font-semibold flex items-center gap-1 border border-emerald-500/20 transition"
                                        >
                                            <span>Login</span>
                                            <ArrowUpRight class="w-3 h-3" />
                                        </a>

                                        <!-- Password Reset Button -->
                                        <button
                                            @click="openPasswordModal(tenant)"
                                            title="Reset Admin Password"
                                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition"
                                        >
                                            <KeyRound class="w-3.5 h-3.5" />
                                        </button>

                                        <!-- Suspend / Activate Button -->
                                        <button
                                            @click="toggleStatus(tenant)"
                                            :title="tenant.status === 'suspended' ? 'Activate Store' : 'Suspend Store'"
                                            :class="[
                                                'p-1.5 rounded-lg transition',
                                                tenant.status === 'suspended'
                                                    ? 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400'
                                                    : 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-400'
                                            ]"
                                        >
                                            <Lock class="w-3.5 h-3.5" />
                                        </button>

                                        <!-- Permanent Delete Button -->
                                        <button
                                            @click="openDeleteModal(tenant)"
                                            title="Delete Store Permanently"
                                            class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 transition"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="tenants.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    No stores match your search filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tenants.links?.length > 3" class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                    <div>
                        Showing {{ tenants.from || 0 }} to {{ tenants.to || 0 }} of {{ tenants.total }} stores
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in tenants.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-medium transition',
                                link.active ? 'bg-emerald-500 text-white font-bold' : 'hover:bg-slate-800 text-slate-400',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval with Discount Modal -->
        <div v-if="showApproveModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-lg max-h-[92vh] overflow-y-auto bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <CheckCircle2 class="w-5 h-5 text-emerald-400" />
                        <span>শপ অনুমোদন ও স্পেশাল ডিসকাউন্ট</span>
                    </h3>
                    <button @click="showApproveModal = false" class="p-1 rounded-lg text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Store Preview Card -->
                <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800/80 text-xs space-y-1.5">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400">প্রতিষ্ঠানের নাম:</span>
                        <span class="font-bold text-white text-sm">{{ selectedApproveTenant?.name }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400">সাবডোমেইন:</span>
                        <span class="font-mono text-emerald-400 font-semibold">{{ selectedApproveTenant?.domains?.[0]?.domain || selectedApproveTenant?.id }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400">মালিকের ইমেইল / ফোন:</span>
                        <span class="text-slate-300">{{ selectedApproveTenant?.email }} • {{ selectedApproveTenant?.phone || 'ফোন নেই' }}</span>
                    </div>
                </div>

                <form @submit.prevent="submitApprove" class="space-y-4 text-xs">
                    <!-- Approval Mode -->
                    <div>
                        <label class="block font-bold text-slate-300 mb-1.5">অনুমোদন প্রক্রিয়া (Approval Mode) *</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label :class="[
                                'flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition',
                                approveForm.approval_type === 'trial' ? 'bg-cyan-500/10 border-cyan-500/40 text-cyan-300' : 'bg-slate-950 border-slate-800 text-slate-400 hover:bg-slate-800/40'
                            ]">
                                <input type="radio" value="trial" v-model="approveForm.approval_type" class="sr-only" />
                                <Clock class="w-4 h-4 shrink-0" />
                                <div>
                                    <div class="font-bold text-xs">ফ্রি ট্রায়াল অ্যাক্টিভেশন</div>
                                    <div class="text-[10px] text-slate-500">নির্দিষ্ট দিনের ফ্রি ট্রায়াল</div>
                                </div>
                            </label>

                            <label :class="[
                                'flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition',
                                approveForm.approval_type === 'active' ? 'bg-emerald-500/10 border-emerald-500/40 text-emerald-300' : 'bg-slate-950 border-slate-800 text-slate-400 hover:bg-slate-800/40'
                            ]">
                                <input type="radio" value="active" v-model="approveForm.approval_type" class="sr-only" />
                                <Sparkles class="w-4 h-4 shrink-0" />
                                <div>
                                    <div class="font-bold text-xs">সরাসরি সাবস্ক্রিপশন</div>
                                    <div class="text-[10px] text-slate-500">পেইড প্যাকেজ হিসেবে অ্যাক্টিভ</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Trial Days (if trial) -->
                    <div v-if="approveForm.approval_type === 'trial'">
                        <label class="block font-semibold text-slate-300 mb-1">ট্রায়াল মেয়াদ (দিন) *</label>
                        <input
                            v-model.number="approveForm.days"
                            type="number"
                            min="1"
                            max="365"
                            required
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-bold text-center text-sm focus:border-cyan-500"
                        />
                    </div>

                    <!-- Plan & Cycle -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">নির্ধারিত প্ল্যান *</label>
                            <select
                                v-model="approveForm.plan_id"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-medium focus:border-emerald-500"
                            >
                                <option v-for="p in plans" :key="p.id" :value="p.id">
                                    {{ p.name }} (৳{{ p.price_monthly }}/মাস)
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">বিলিং সাইকেল</label>
                            <select
                                v-model="approveForm.billing_cycle"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-medium focus:border-emerald-500"
                            >
                                <option value="monthly">মাসিক (Monthly)</option>
                                <option value="yearly">বাৎসরিক (Yearly)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Special Discount Section -->
                    <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800/90 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-800/60 pb-2">
                            <span class="font-bold text-xs text-amber-400 flex items-center gap-1.5">
                                <Tag class="w-4 h-4" />
                                <span>স্পেশাল ডিসকাউন্ট যুক্ত করুন (Special Discount)</span>
                            </span>
                            <span class="text-[10px] text-slate-500">ঐচ্ছিক (Optional)</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-400 mb-1">ডিসকাউন্ট এর ধরণ</label>
                                <select
                                    v-model="approveForm.discount_type"
                                    class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white focus:border-amber-500 font-medium"
                                >
                                    <option value="none">কোনো ডিসকাউন্ট নেই</option>
                                    <option value="percentage">শতকরা ছাড় (%)</option>
                                    <option value="fixed">নির্দিষ্ট টাকা ছাড় (BDT)</option>
                                </select>
                            </div>

                            <div v-if="approveForm.discount_type !== 'none'">
                                <label class="block text-slate-400 mb-1">
                                    {{ approveForm.discount_type === 'percentage' ? 'ডিসকাউন্ট শতকরা (%)' : 'ডিসকাউন্ট টাকা (BDT)' }}
                                </label>
                                <div class="relative">
                                    <input
                                        v-model.number="approveForm.discount_value"
                                        type="number"
                                        min="0"
                                        step="0.1"
                                        class="w-full pl-3 pr-8 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white font-bold focus:border-amber-500"
                                        placeholder="0"
                                    />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">
                                        {{ approveForm.discount_type === 'percentage' ? '%' : '৳' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-if="approveForm.discount_type !== 'none'">
                            <label class="block text-slate-400 mb-1">ডিসকাউন্ট নোট / মন্তব্য</label>
                            <input
                                v-model="approveForm.discount_note"
                                type="text"
                                placeholder="যেমন: ঈদের স্পেশাল প্রোমো / আজীবন ২০% ছাড়"
                                class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white focus:border-amber-500"
                            />
                        </div>

                        <!-- Real-time Price Calculation Summary Card -->
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-slate-400 block text-[11px]">প্ল্যানের আসল মূল্য:</span>
                                <span class="font-bold text-slate-300 line-through">
                                    {{ formatPrice(calculatedApprovePrice.base) }}
                                </span>
                            </div>
                            <div v-if="calculatedApprovePrice.discount > 0" class="text-center">
                                <span class="text-amber-400 block text-[11px]">প্রযোজ্য ছাড়:</span>
                                <span class="font-bold text-amber-400">
                                    - {{ formatPrice(calculatedApprovePrice.discount) }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-emerald-400 block text-[11px] font-bold">অনুমোদিত চূড়ান্ত মূল্য:</span>
                                <span class="text-base font-extrabold text-emerald-400">
                                    {{ formatPrice(calculatedApprovePrice.final) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                        <button
                            type="button"
                            @click="showApproveModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold"
                        >
                            বাতিল
                        </button>
                        <button
                            type="submit"
                            :disabled="approveForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold shadow-md shadow-emerald-500/20 disabled:opacity-50 transition"
                        >
                            {{ approveForm.processing ? 'অনুমোদন হচ্ছে...' : 'শপ অনুমোদন করুন' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Create Store Manually Modal (with Live Subdomain Checker & Upfront Discount) -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-lg max-h-[92vh] overflow-y-auto bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <Store class="w-5 h-5 text-emerald-400" />
                        <span>সরাসরি নতুন শপ তৈরি করুন (Admin Creator)</span>
                    </h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-3.5 text-xs">
                    <!-- Business Name -->
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">প্রতিষ্ঠানের নাম (Business / Store Name) *</label>
                        <input
                            v-model="createForm.business_name"
                            type="text"
                            required
                            placeholder="যেমন: ঢাকা ইলেকট্রনিক্স এন্ড গ্যাজেট"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500 font-medium"
                            @input="generateSlug"
                        />
                    </div>

                    <!-- Subdomain Slug with live availability check -->
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">সাবডোমেইন লিংক (Subdomain URL) *</label>
                        <div class="relative flex items-center">
                            <input
                                v-model="createForm.subdomain"
                                type="text"
                                required
                                placeholder="dhakagadgets"
                                class="w-full pl-3.5 pr-28 py-2.5 bg-slate-950 border rounded-xl text-white font-mono text-xs focus:outline-none"
                                :class="subdomainStatus.available === true
                                    ? 'border-emerald-500 focus:border-emerald-500'
                                    : subdomainStatus.available === false
                                    ? 'border-rose-500 focus:border-rose-500'
                                    : 'border-slate-800 focus:border-emerald-500'"
                                @input="checkSubdomainDebounced"
                            />
                            <span class="absolute right-3 text-xs font-mono text-slate-500 select-none pointer-events-none">.localhost</span>
                        </div>
                        <div v-if="subdomainStatus.message" class="text-[11px] mt-1 font-medium" :class="subdomainStatus.available ? 'text-emerald-400' : 'text-rose-400'">
                            {{ subdomainStatus.message }}
                        </div>
                    </div>

                    <!-- Owner Details -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">মালিকের নাম (Owner Name) *</label>
                            <input
                                v-model="createForm.owner_name"
                                type="text"
                                required
                                placeholder="মোঃ রফিকুল ইসলাম"
                                class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">ফোন নম্বর (Phone)</label>
                            <input
                                v-model="createForm.phone"
                                type="text"
                                placeholder="017XXXXXXXX"
                                class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">লগইন ইমেইল (Login Email) *</label>
                            <input
                                v-model="createForm.email"
                                type="email"
                                required
                                placeholder="owner@store.com"
                                class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">লগইন পাসওয়ার্ড (Password) *</label>
                            <input
                                v-model="createForm.password"
                                type="text"
                                required
                                minlength="4"
                                placeholder="কমপক্ষে ৪ সংখ্যা..."
                                class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <!-- Status & Plan -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">শপের স্ট্যাটাস</label>
                            <select
                                v-model="createForm.status"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500 font-medium"
                            >
                                <option value="trial">ট্রায়াল (Trial)</option>
                                <option value="active">সরাসরি সক্রিয় (Active)</option>
                                <option value="pending">পেন্ডিং (Pending)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">নির্ধারিত প্ল্যান</label>
                            <select
                                v-model="createForm.plan_id"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500 font-medium"
                            >
                                <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">মেয়াদ (দিন)</label>
                            <input
                                v-model.number="createForm.trial_days"
                                type="number"
                                min="0"
                                max="365"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500 font-bold text-center"
                            />
                        </div>
                    </div>

                    <!-- Upfront Discount (Optional) -->
                    <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800/90 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-800/60 pb-1.5">
                            <span class="font-bold text-xs text-amber-400 flex items-center gap-1.5">
                                <Tag class="w-3.5 h-3.5" />
                                <span>শপ তৈরির সময়ই স্পেশাল ডিসকাউন্ট দিন</span>
                            </span>
                            <span class="text-[10px] text-slate-500">ঐচ্ছিক</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-400 text-[11px] mb-1">ডিসকাউন্ট টাইপ</label>
                                <select
                                    v-model="createForm.discount_type"
                                    class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-white text-xs"
                                >
                                    <option value="none">কোনো ছাড় নেই</option>
                                    <option value="percentage">শতকরা (%)</option>
                                    <option value="fixed">টাকা (BDT)</option>
                                </select>
                            </div>
                            <div v-if="createForm.discount_type !== 'none'">
                                <label class="block text-slate-400 text-[11px] mb-1">ডিসকাউন্ট মান</label>
                                <input
                                    v-model.number="createForm.discount_value"
                                    type="number"
                                    min="0"
                                    class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-white text-xs font-bold"
                                />
                            </div>
                        </div>

                        <div v-if="createForm.discount_type !== 'none'">
                            <input
                                v-model="createForm.discount_note"
                                type="text"
                                placeholder="ডিসকাউন্ট নোট (যেমন: প্রতিষ্ঠাতা স্পেশাল অফার)"
                                class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-white text-xs"
                            />
                        </div>

                        <div class="flex justify-between items-center text-[11px] text-slate-400 pt-1">
                            <span>প্ল্যান ফি: <strong>{{ formatPrice(calculatedCreatePrice.base) }}</strong></span>
                            <span v-if="calculatedCreatePrice.discount > 0" class="text-amber-400">ছাড়: <strong>-{{ formatPrice(calculatedCreatePrice.discount) }}</strong></span>
                            <span class="text-emerald-400 font-bold">চূড়ান্ত: {{ formatPrice(calculatedCreatePrice.final) }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold"
                        >
                            বাতিল
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing || subdomainStatus.available === false"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold shadow-md shadow-emerald-500/20 disabled:opacity-50 transition"
                        >
                            {{ createForm.processing ? 'শপ প্রস্তুত হচ্ছে...' : 'শপ তৈরি ও প্রোভিশন করুন' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Extend Validity Modal -->
        <div v-if="showExtendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <Clock class="w-4 h-4 text-cyan-400" />
                        <span>মেয়াদ বৃদ্ধি: {{ selectedTenant?.name }}</span>
                    </h3>
                    <button @click="showExtendModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitExtend" class="space-y-4 text-xs">
                    <div>
                        <label class="block text-slate-400 mb-1">অতিরিক্ত দিন সংখ্যা যোগ করুন</label>
                        <input
                            v-model.number="extendForm.days"
                            type="number"
                            min="1"
                            max="365"
                            required
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-bold text-center text-lg focus:border-cyan-500"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="showExtendModal = false" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300">
                            বাতিল
                        </button>
                        <button type="submit" :disabled="extendForm.processing" class="px-4 py-1.5 rounded-lg bg-cyan-500 hover:bg-cyan-600 text-white font-bold">
                            মেয়াদ বৃদ্ধি করুন
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Password Reset Modal -->
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <KeyRound class="w-4 h-4 text-amber-400" />
                        <span>অ্যাডমিন পাসওয়ার্ড রিসেট</span>
                    </h3>
                    <button @click="showPasswordModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPasswordReset" class="space-y-4 text-xs">
                    <p class="text-slate-400 text-[11px]">
                        শপ মালিকের পাসওয়ার্ড পরিবর্তন: <strong>{{ selectedTenant?.name }}</strong>
                    </p>

                    <div>
                        <label class="block text-slate-400 mb-1">নতুন পাসওয়ার্ড</label>
                        <input
                            v-model="passwordForm.password"
                            type="text"
                            required
                            minlength="4"
                            placeholder="কমপক্ষে ৪ সংখ্যা..."
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-amber-500"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="showPasswordModal = false" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300">
                            বাতিল
                        </button>
                        <button type="submit" :disabled="passwordForm.processing" class="px-4 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold">
                            পাসওয়ার্ড আপডেট করুন
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Store Modal with Password Confirmation -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md bg-slate-900 border border-rose-500/30 rounded-3xl p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in duration-200">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-rose-400 flex items-center gap-2">
                        <Trash2 class="w-5 h-5 text-rose-500" />
                        <span>শপ সম্পূর্ণ ডিলিট করুন (Permanent Delete)</span>
                    </h3>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-xs text-rose-300 space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <AlertCircle class="w-4 h-4 text-rose-400 shrink-0" />
                        <span>চূড়ান্ত সতর্কতা!</span>
                    </div>
                    <p class="leading-relaxed text-[11px] text-rose-300/90">
                        এই শপটি ডিলিট করলে এর ডাটাবেজ, প্রোডাক্ট, সেলস, কাস্টমার, ইউজার এবং সমস্ত ডাটা সম্পূর্ণ মুছে ফেলা হবে। এটি কোনোভাবেই ফিরিয়ে আনা সম্ভব নয়।
                    </p>
                </div>

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-500">প্রতিষ্ঠানের নাম:</span>
                        <span class="font-bold text-white">{{ selectedTenant?.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">সাবডোমেইন:</span>
                        <span class="font-mono text-emerald-400 font-semibold">{{ selectedTenant?.domains?.[0]?.domain || selectedTenant?.id }}</span>
                    </div>
                </div>

                <form @submit.prevent="submitDelete" class="space-y-4 text-xs">
                    <div>
                        <label class="block text-slate-300 font-bold mb-1.5">
                            কনফার্ম করতে আপনার সুপার অ্যাডমিন পাসওয়ার্ড দিন *
                        </label>
                        <input
                            v-model="deleteForm.password"
                            type="password"
                            required
                            placeholder="সুপার অ্যাডমিন পাসওয়ার্ড..."
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-rose-500 text-sm transition"
                            :class="{ 'border-rose-500': deleteForm.errors.password }"
                        />
                        <div v-if="deleteForm.errors.password" class="text-rose-400 text-[11px] mt-1 font-semibold">
                            {{ deleteForm.errors.password }}
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="showDeleteModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold"
                        >
                            বাতিল
                        </button>
                        <button
                            type="submit"
                            :disabled="deleteForm.processing || !deleteForm.password"
                            class="px-5 py-2 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white font-bold shadow-lg shadow-rose-600/30 disabled:opacity-50 transition flex items-center gap-2 cursor-pointer disabled:cursor-not-allowed"
                        >
                            <span v-if="deleteForm.processing" class="w-3.5 h-3.5 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                            <span>{{ deleteForm.processing ? 'ডাটাবেজ ও ফাইল ডিলিট হচ্ছে...' : 'হ্যাঁ, স্থায়ীভাবে ডিলিট করুন' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </SuperAdminLayout>
</template>