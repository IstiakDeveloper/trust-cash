<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import {
    Store,
    ArrowLeft,
    ArrowUpRight,
    ExternalLink,
    Package,
    Users,
    ShoppingCart,
    CreditCard,
    Clock,
    Lock,
    KeyRound
} from 'lucide-vue-next';

const props = defineProps({
    tenant: Object,
    resourceStats: Object,
    allPlans: Array,
});

const subForm = useForm({
    plan_id: props.tenant.plan_id || props.allPlans?.[0]?.id,
    billing_cycle: props.tenant.subscriptions?.[0]?.billing_cycle || 'monthly',
    status: props.tenant.status,
    ends_at: props.tenant.trial_ends_at ? props.tenant.trial_ends_at.slice(0, 10) : '',
});

const updateSub = () => {
    subForm.post(route('super-admin.tenants.update-subscription', props.tenant.id));
};

const formatCurrency = (val) => {
    return '৳ ' + Number(val || 0).toLocaleString('en-BD', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};
</script>

<template>
    <Head :title="`${tenant.name} | Store Profile | TrustCash`" />

    <SuperAdminLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('super-admin.tenants.index')"
                    class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition"
                >
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-white tracking-tight">{{ tenant.name }}</h1>
                    <p class="text-xs text-slate-400">Store configuration, quotas & subscription details</p>
                </div>
            </div>
        </template>

        <div class="space-y-6 max-w-5xl">
            <!-- Top Store Bar -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center font-bold text-white text-2xl shadow-lg shadow-emerald-500/20">
                        {{ tenant.name.charAt(0) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-white">{{ tenant.name }}</h2>
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border',
                                    tenant.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                                    tenant.status === 'trial' ? 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20' :
                                    'bg-rose-500/10 text-rose-400 border-rose-500/20'
                                ]"
                            >
                                {{ tenant.status }}
                            </span>
                        </div>
                        <a
                            :href="'http://' + (tenant.domains?.[0]?.domain || tenant.id)"
                            target="_blank"
                            class="text-xs text-emerald-400 hover:underline flex items-center gap-1 mt-0.5"
                        >
                            {{ tenant.domains?.[0]?.domain || tenant.id }}
                            <ExternalLink class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="route('super-admin.tenants.impersonate', tenant.id)"
                        target="_blank"
                        class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition"
                    >
                        <span>Login As Store Admin</span>
                        <ArrowUpRight class="w-4 h-4" />
                    </a>
                </div>
            </div>

            <!-- Resource Usage Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center shrink-0">
                        <Package class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-400">Total Products</div>
                        <div class="text-2xl font-black text-white mt-0.5">
                            {{ resourceStats.products_count }} <span class="text-xs font-normal text-slate-500">/ {{ tenant.plan?.max_products || 500 }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center shrink-0">
                        <Users class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-400">Active Staff & Users</div>
                        <div class="text-2xl font-black text-white mt-0.5">
                            {{ resourceStats.users_count }} <span class="text-xs font-normal text-slate-500">/ {{ tenant.plan?.max_users || 2 }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                        <ShoppingCart class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-400">Total Invoices / Sales</div>
                        <div class="text-2xl font-black text-emerald-400 mt-0.5">
                            {{ resourceStats.sales_count }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Column: Store Details & Subscription Override Form -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Store Metadata -->
                <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-3">
                        Registration & System Details
                    </h3>
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-1 border-b border-slate-800/60">
                            <span class="text-slate-400">Store ID (Tenant UUID):</span>
                            <span class="font-mono text-slate-200">{{ tenant.id }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-800/60">
                            <span class="text-slate-400">Primary Domain:</span>
                            <span class="font-mono text-emerald-400">{{ tenant.domains?.[0]?.domain }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-800/60">
                            <span class="text-slate-400">Owner Email:</span>
                            <span class="text-slate-200">{{ tenant.email }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-800/60">
                            <span class="text-slate-400">Phone:</span>
                            <span class="text-slate-200">{{ tenant.phone || 'Not specified' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-800/60">
                            <span class="text-slate-400">Registered On:</span>
                            <span class="text-slate-200">{{ new Date(tenant.created_at).toLocaleDateString() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Plan & Subscription Override -->
                <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-3">
                        Manual Plan & Subscription Override
                    </h3>

                    <form @submit.prevent="updateSub" class="space-y-3 text-xs">
                        <div>
                            <label class="block text-slate-400 mb-1">Assigned Tier / Plan</label>
                            <select
                                v-model="subForm.plan_id"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            >
                                <option v-for="p in allPlans" :key="p.id" :value="p.id">
                                    {{ p.name }} (৳{{ p.price_monthly }}/mo)
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-400 mb-1">Billing Cycle</label>
                                <select
                                    v-model="subForm.billing_cycle"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500 capitalize"
                                >
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">Account Status</label>
                                <select
                                    v-model="subForm.status"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500 capitalize"
                                >
                                    <option value="active">Active</option>
                                    <option value="trial">Trial</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-400 mb-1">Custom Expiration / Renewal Date</label>
                            <input
                                v-model="subForm.ends_at"
                                type="date"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:border-emerald-500"
                            />
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="subForm.processing"
                                class="w-full py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold transition disabled:opacity-50"
                            >
                                {{ subForm.processing ? 'Saving...' : 'Apply Subscription Override' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Store Billing History -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center gap-2">
                    <CreditCard class="w-4 h-4 text-purple-400" />
                    Billing & Payment Records for {{ tenant.name }}
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="py-2.5 font-medium">Transaction ID</th>
                                <th class="py-2.5 font-medium">Gateway</th>
                                <th class="py-2.5 font-medium">Amount</th>
                                <th class="py-2.5 font-medium">Date</th>
                                <th class="py-2.5 font-medium text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="p in tenant.payments" :key="p.id">
                                <td class="py-3 font-mono text-slate-200">{{ p.transaction_id }}</td>
                                <td class="py-3 uppercase text-slate-400 text-[11px]">{{ p.payment_method }}</td>
                                <td class="py-3 font-bold text-emerald-400">{{ formatCurrency(p.amount) }}</td>
                                <td class="py-3 text-slate-300">{{ new Date(p.created_at).toLocaleDateString() }}</td>
                                <td class="py-3 text-right">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        {{ p.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!tenant.payments || tenant.payments.length === 0">
                                <td colspan="5" class="py-6 text-center text-slate-500">
                                    No billing records on file for this store.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>