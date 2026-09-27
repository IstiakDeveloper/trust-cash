<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import {
    Store,
    Users,
    TrendingUp,
    CreditCard,
    AlertCircle,
    CheckCircle,
    Clock,
    ArrowUpRight,
    ExternalLink,
    ChevronRight,
    Layers
} from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    plansDistribution: Array,
    recentTenants: Array,
    recentPayments: Array,
});

const formatCurrency = (val) => {
    return '৳ ' + Number(val || 0).toLocaleString('en-BD', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};
</script>

<template>
    <Head title="Super Admin Dashboard | TrustCash" />

    <SuperAdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white tracking-tight">Executive SaaS Overview</h1>
            <p class="text-xs text-slate-400">Live platform performance, revenue & store analytics</p>
        </template>

        <div class="space-y-6">
            <!-- Top Alert if Expiring or Pending Payments -->
            <div v-if="stats.expiring_soon > 0 || stats.pending_payments_count > 0" class="flex flex-wrap items-center gap-4 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-sm">
                <AlertCircle class="w-5 h-5 shrink-0 text-amber-400" />
                <div class="flex-1 flex flex-wrap items-center gap-4">
                    <span v-if="stats.expiring_soon > 0">
                        ⚠️ <strong>{{ stats.expiring_soon }}</strong> stores have trials/subscriptions expiring within 7 days.
                    </span>
                    <span v-if="stats.pending_payments_count > 0">
                        💳 <strong>{{ stats.pending_payments_count }}</strong> manual payment approvals pending review.
                    </span>
                </div>
                <Link
                    v-if="stats.pending_payments_count > 0"
                    :href="route('super-admin.payments.index', { status: 'pending' })"
                    class="px-3 py-1.5 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-200 text-xs font-semibold transition"
                >
                    Review Payments →
                </Link>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Card 1: Total Stores -->
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Total Stores</span>
                        <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                            <Store class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-extrabold text-white tracking-tight">{{ stats.total_tenants }}</div>
                        <div class="mt-2 flex items-center gap-2 text-xs text-slate-400">
                            <span class="text-emerald-400 font-semibold">{{ stats.active_tenants }} Active</span>
                            <span>•</span>
                            <span class="text-cyan-400">{{ stats.trial_tenants }} Trial</span>
                            <span>•</span>
                            <span class="text-rose-400">{{ stats.suspended_tenants }} Suspended</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Monthly Recurring Revenue (MRR) -->
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Monthly Revenue (MRR)</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                            <TrendingUp class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-extrabold text-emerald-400 tracking-tight">{{ formatCurrency(stats.mrr) }}</div>
                        <div class="mt-2 text-xs text-slate-400">
                            Projected ARR: <strong class="text-slate-200">{{ formatCurrency(stats.arr) }}</strong> /yr
                        </div>
                    </div>
                </div>

                <!-- Card 3: Total Collections -->
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Total Collected</span>
                        <div class="w-9 h-9 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center">
                            <CreditCard class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-extrabold text-white tracking-tight">{{ formatCurrency(stats.total_revenue) }}</div>
                        <div class="mt-2 text-xs text-slate-400">
                            Cumulative lifetime receipts
                        </div>
                    </div>
                </div>

                <!-- Card 4: Active Free Trials -->
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Onboarding Pipeline</span>
                        <div class="w-9 h-9 rounded-xl bg-teal-500/10 border border-teal-500/20 text-teal-400 flex items-center justify-center">
                            <Clock class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-extrabold text-teal-400 tracking-tight">{{ stats.trial_tenants }}</div>
                        <div class="mt-2 text-xs text-slate-400">
                            {{ stats.expiring_soon }} stores near conversion deadline
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plan Distribution & Fast Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Plan Breakdown -->
                <div class="lg:col-span-2 p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <Layers class="w-4 h-4 text-emerald-400" />
                            Store Distribution by Plan
                        </h2>
                        <Link :href="route('super-admin.plans.index')" class="text-xs text-emerald-400 hover:underline flex items-center gap-1">
                            Configure Plans <ChevronRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                        <div
                            v-for="plan in plansDistribution"
                            :key="plan.id"
                            class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 flex flex-col justify-between"
                        >
                            <div>
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ plan.name }}</span>
                                <div class="text-2xl font-bold text-white mt-1">{{ plan.tenants_count }}</div>
                            </div>
                            <div class="mt-3 w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                <div
                                    class="bg-emerald-400 h-1.5 rounded-full"
                                    :style="{ width: stats.total_tenants > 0 ? (plan.tenants_count / stats.total_tenants * 100) + '%' : '0%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Super Admin Quick Commands -->
                <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm flex flex-col justify-between space-y-4">
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-3">
                            Direct Commands
                        </h2>
                        <div class="mt-4 space-y-2">
                            <Link
                                :href="route('super-admin.tenants.index')"
                                class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-950/80 hover:bg-emerald-500/10 border border-slate-800 hover:border-emerald-500/30 text-xs font-medium text-slate-200 transition group"
                            >
                                <span class="flex items-center gap-2">
                                    <Store class="w-4 h-4 text-emerald-400" />
                                    Manage All Stores
                                </span>
                                <ChevronRight class="w-4 h-4 text-slate-500 group-hover:text-emerald-400 transition" />
                            </Link>

                            <Link
                                :href="route('super-admin.payments.index')"
                                class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-950/80 hover:bg-emerald-500/10 border border-slate-800 hover:border-emerald-500/30 text-xs font-medium text-slate-200 transition group"
                            >
                                <span class="flex items-center gap-2">
                                    <CreditCard class="w-4 h-4 text-purple-400" />
                                    Approve Payments & Invoices
                                </span>
                                <ChevronRight class="w-4 h-4 text-slate-500 group-hover:text-emerald-400 transition" />
                            </Link>

                            <Link
                                :href="route('super-admin.settings.index')"
                                class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-950/80 hover:bg-emerald-500/10 border border-slate-800 hover:border-emerald-500/30 text-xs font-medium text-slate-200 transition group"
                            >
                                <span class="flex items-center gap-2">
                                    <TrendingUp class="w-4 h-4 text-cyan-400" />
                                    Payment Gateways (SSL / bKash)
                                </span>
                                <ChevronRight class="w-4 h-4 text-slate-500 group-hover:text-emerald-400 transition" />
                            </Link>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs text-slate-300">
                        <p class="font-semibold text-emerald-400 mb-0.5">TrustCash Cloud v1.0</p>
                        <p class="text-[11px] text-slate-400">Isolated database per tenant engine active.</p>
                    </div>
                </div>
            </div>

            <!-- Two Column: Recent Stores & Recent Transactions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Stores -->
                <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <Store class="w-4 h-4 text-blue-400" />
                            Recent Stores Registered
                        </h2>
                        <Link :href="route('super-admin.tenants.index')" class="text-xs text-emerald-400 hover:underline">
                            View All →
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="py-2.5 font-medium">Store & Subdomain</th>
                                    <th class="py-2.5 font-medium">Plan</th>
                                    <th class="py-2.5 font-medium">Status</th>
                                    <th class="py-2.5 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="tenant in recentTenants" :key="tenant.id" class="hover:bg-slate-800/30">
                                    <td class="py-3">
                                        <div class="font-semibold text-white">{{ tenant.name }}</div>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1">
                                            <span>{{ tenant.domains?.[0]?.domain || tenant.id }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 font-medium text-[11px]">
                                            {{ tenant.plan?.name || 'Default' }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider border',
                                                tenant.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                                                tenant.status === 'trial' ? 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20' :
                                                'bg-rose-500/10 text-rose-400 border-rose-500/20'
                                            ]"
                                        >
                                            {{ tenant.status }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <a
                                            :href="route('super-admin.tenants.impersonate', tenant.id)"
                                            target="_blank"
                                            title="1-Click Login As Shop Admin"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-[11px] font-medium transition"
                                        >
                                            Login
                                            <ArrowUpRight class="w-3 h-3" />
                                        </a>
                                    </td>
                                </tr>
                                <tr v-if="recentTenants.length === 0">
                                    <td colspan="4" class="py-6 text-center text-slate-500">
                                        No stores registered yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Payments -->
                <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <CreditCard class="w-4 h-4 text-purple-400" />
                            Recent Billing Transactions
                        </h2>
                        <Link :href="route('super-admin.payments.index')" class="text-xs text-emerald-400 hover:underline">
                            View All →
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="py-2.5 font-medium">Store</th>
                                    <th class="py-2.5 font-medium">Trx ID / Gateway</th>
                                    <th class="py-2.5 font-medium">Amount</th>
                                    <th class="py-2.5 font-medium text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="payment in recentPayments" :key="payment.id" class="hover:bg-slate-800/30">
                                    <td class="py-3">
                                        <div class="font-medium text-white">{{ payment.tenant?.name || 'Store #' + payment.tenant_id }}</div>
                                        <div class="text-[10px] text-slate-500">{{ new Date(payment.created_at).toLocaleDateString() }}</div>
                                    </td>
                                    <td class="py-3">
                                        <div class="font-mono text-[11px] text-slate-300">{{ payment.transaction_id }}</div>
                                        <div class="text-[10px] text-slate-400 uppercase">{{ payment.payment_method || 'SSLCommerz' }}</div>
                                    </td>
                                    <td class="py-3 font-bold text-emerald-400">
                                        {{ formatCurrency(payment.amount) }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider border',
                                                payment.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                                                payment.status === 'pending' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' :
                                                'bg-rose-500/10 text-rose-400 border-rose-500/20'
                                            ]"
                                        >
                                            {{ payment.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="recentPayments.length === 0">
                                    <td colspan="4" class="py-6 text-center text-slate-500">
                                        No billing records found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>