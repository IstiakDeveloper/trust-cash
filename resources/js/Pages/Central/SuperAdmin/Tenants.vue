<script setup>
import { ref } from 'vue';
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
    ShieldAlert
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
const showExtendModal = ref(false);
const showPasswordModal = ref(false);
const selectedTenant = ref(null);

// Forms
const createForm = useForm({
    business_name: '',
    subdomain: '',
    owner_name: '',
    email: '',
    phone: '',
    password: 'password123',
    plan_id: props.plans?.[0]?.id || 1,
    trial_days: 14,
});

const extendForm = useForm({
    days: 15,
});

const passwordForm = useForm({
    password: '',
});

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

const approveStore = (tenant) => {
    if (confirm(`Approve and activate store "${tenant.name}" for a 14-day trial?`)) {
        router.post(route('super-admin.tenants.approve', tenant.id), { days: 14 });
    }
};

const toggleStatus = (tenant) => {
    if (confirm(`Are you sure you want to ${tenant.status === 'suspended' ? 'activate' : 'suspend'} store "${tenant.name}"?`)) {
        router.post(route('super-admin.tenants.toggle-status', tenant.id));
    }
};

const submitCreate = () => {
    createForm.post(route('super-admin.tenants.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};
</script>

<template>
    <Head title="Store Management | TrustCash Super Admin" />

    <SuperAdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white tracking-tight">Stores & Tenant Directory</h1>
            <p class="text-xs text-slate-400">Complete control over registered shops, domains & access rights</p>
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
                                <th class="py-3 px-4 font-semibold">Plan & Limits</th>
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
                                    <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-200 font-semibold text-[11px]">
                                        {{ tenant.plan?.name || 'Default' }}
                                    </span>
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
                                    <div v-if="tenant.trial_ends_at" class="text-slate-300">
                                        {{ new Date(tenant.trial_ends_at).toLocaleDateString() }}
                                    </div>
                                    <div v-else class="text-slate-400">Regular Sub</div>
                                    <button
                                        @click="openExtendModal(tenant)"
                                        class="text-[10px] text-cyan-400 hover:underline mt-0.5 flex items-center gap-1"
                                    >
                                        <Clock class="w-3 h-3" /> Extend Validity
                                    </button>
                                </td>

                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Approve Button for Pending Stores -->
                                        <button
                                            v-if="tenant.status === 'pending'"
                                            @click="approveStore(tenant)"
                                            title="Approve & Activate Store"
                                            class="px-2.5 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/30 text-xs font-bold flex items-center gap-1 transition shadow-sm mr-1"
                                        >
                                            <CheckCircle2 class="w-3.5 h-3.5" />
                                            <span>Approve</span>
                                        </button>

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
                                                    : 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-400'
                                            ]"
                                        >
                                            <Lock class="w-3.5 h-3.5" />
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

        <!-- Create Store Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <Store class="w-5 h-5 text-emerald-400" />
                        Create Store Manually
                    </h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-3 text-xs">
                    <div>
                        <label class="block text-slate-400 mb-1">Business / Store Name</label>
                        <input
                            v-model="createForm.business_name"
                            type="text"
                            required
                            placeholder="e.g. Dhaka Electronics & Gadgets"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">Subdomain</label>
                        <div class="flex items-center bg-slate-950 border border-slate-800 rounded-xl px-3 py-2">
                            <input
                                v-model="createForm.subdomain"
                                type="text"
                                required
                                placeholder="dhakagadgets"
                                class="bg-transparent flex-1 text-white focus:outline-none"
                            />
                            <span class="text-slate-500 font-mono text-[11px]">.localhost:8000</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-400 mb-1">Owner Name</label>
                            <input
                                v-model="createForm.owner_name"
                                type="text"
                                required
                                placeholder="Mr. Owner"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Phone Number</label>
                            <input
                                v-model="createForm.phone"
                                type="text"
                                placeholder="01700000000"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-400 mb-1">Owner Email</label>
                            <input
                                v-model="createForm.email"
                                type="email"
                                required
                                placeholder="owner@store.com"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Admin Password</label>
                            <input
                                v-model="createForm.password"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-400 mb-1">Assigned Plan</label>
                            <select
                                v-model="createForm.plan_id"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            >
                                <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }} (৳{{ p.price_monthly }}/mo)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Trial Period (Days)</label>
                            <input
                                v-model.number="createForm.trial_days"
                                type="number"
                                min="0"
                                max="365"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Provisioning DB...' : 'Create & Provision Store' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Extend Validity Modal -->
        <div v-if="showExtendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <Clock class="w-4 h-4 text-cyan-400" />
                        Extend Validity: {{ selectedTenant?.name }}
                    </h3>
                    <button @click="showExtendModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitExtend" class="space-y-4 text-xs">
                    <div>
                        <label class="block text-slate-400 mb-1">Add Additional Days</label>
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
                            Cancel
                        </button>
                        <button type="submit" :disabled="extendForm.processing" class="px-4 py-1.5 rounded-lg bg-cyan-500 hover:bg-cyan-600 text-white font-bold">
                            Save Extension
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Password Reset Modal -->
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <KeyRound class="w-4 h-4 text-amber-400" />
                        Reset Admin Password
                    </h3>
                    <button @click="showPasswordModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPasswordReset" class="space-y-4 text-xs">
                    <p class="text-slate-400 text-[11px]">
                        Directly overwrite password for store owner: <strong>{{ selectedTenant?.name }}</strong>
                    </p>

                    <div>
                        <label class="block text-slate-400 mb-1">New Password</label>
                        <input
                            v-model="passwordForm.password"
                            type="text"
                            required
                            minlength="4"
                            placeholder="Min 4 characters..."
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-amber-500"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="showPasswordModal = false" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300">
                            Cancel
                        </button>
                        <button type="submit" :disabled="passwordForm.processing" class="px-4 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </SuperAdminLayout>
</template>