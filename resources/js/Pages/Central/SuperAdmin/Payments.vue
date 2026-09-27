<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import {
    CreditCard,
    Search,
    CheckCircle,
    XCircle,
    Clock,
    AlertCircle,
    Store,
    ExternalLink
} from 'lucide-vue-next';

const props = defineProps({
    payments: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

const handleFilter = () => {
    router.get(route('super-admin.payments.index'), {
        search: search.value,
        status: status.value,
    }, { preserveState: true, replace: true });
};

const approvePayment = (payment) => {
    if (confirm(`Approve payment ${payment.transaction_id} for store "${payment.tenant?.name}"? This will activate their subscription immediately.`)) {
        router.post(route('super-admin.payments.approve', payment.id));
    }
};

const rejectPayment = (payment) => {
    if (confirm(`Reject payment ${payment.transaction_id}?`)) {
        router.post(route('super-admin.payments.reject', payment.id));
    }
};

const formatCurrency = (val) => {
    return '৳ ' + Number(val || 0).toLocaleString('en-BD', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};
</script>

<template>
    <Head title="Billing & Payments | TrustCash Super Admin" />

    <SuperAdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white tracking-tight">Billing & Payment Approvals</h1>
            <p class="text-xs text-slate-400">Review automated gateway transactions & approve manual bank/bKash receipts</p>
        </template>

        <div class="space-y-6">
            <!-- Filter Bar -->
            <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
                    <div class="relative flex-1 min-w-[220px]">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="search"
                            @keyup.enter="handleFilter"
                            type="text"
                            placeholder="Search by Transaction ID, Store Name or Gateway..."
                            class="w-full pl-9 pr-4 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500/50"
                        />
                    </div>

                    <select
                        v-model="status"
                        @change="handleFilter"
                        class="bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 py-2 px-3 focus:outline-none focus:border-emerald-500/50"
                    >
                        <option value="">All Statuses</option>
                        <option value="paid">Paid / Approved</option>
                        <option value="pending">Pending Approval</option>
                        <option value="failed">Failed / Rejected</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                    <span>SSLCommerz & bKash Active</span>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Store / Tenant</th>
                                <th class="py-3 px-4 font-semibold">Transaction ID & Gateway</th>
                                <th class="py-3 px-4 font-semibold">Plan & Cycle</th>
                                <th class="py-3 px-4 font-semibold">Amount</th>
                                <th class="py-3 px-4 font-semibold">Date & Time</th>
                                <th class="py-3 px-4 font-semibold">Status</th>
                                <th class="py-3 px-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-slate-800/30 transition">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white text-sm">
                                        {{ payment.tenant?.name || 'Store #' + payment.tenant_id }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ payment.tenant?.email }}
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="font-mono text-slate-200 font-semibold">{{ payment.transaction_id }}</div>
                                    <div class="text-[10px] text-slate-400 uppercase tracking-wider font-medium">
                                        {{ payment.payment_method || 'SSLCommerz' }}
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-200 font-semibold text-[11px]">
                                        {{ payment.subscription?.plan?.name || 'Standard' }}
                                    </span>
                                    <div class="text-[10px] text-slate-400 capitalize mt-0.5">
                                        {{ payment.subscription?.billing_cycle || 'monthly' }}
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="font-black text-emerald-400 text-sm">
                                        {{ formatCurrency(payment.amount) }}
                                    </div>
                                </td>

                                <td class="py-3 px-4 text-slate-300">
                                    <div>{{ new Date(payment.created_at).toLocaleDateString() }}</div>
                                    <div class="text-[10px] text-slate-500">{{ new Date(payment.created_at).toLocaleTimeString() }}</div>
                                </td>

                                <td class="py-3 px-4">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border inline-flex items-center gap-1',
                                            payment.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                                            payment.status === 'pending' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' :
                                            'bg-rose-500/10 text-rose-400 border-rose-500/20'
                                        ]"
                                    >
                                        <CheckCircle v-if="payment.status === 'paid'" class="w-3 h-3" />
                                        <Clock v-else-if="payment.status === 'pending'" class="w-3 h-3" />
                                        <XCircle v-else class="w-3 h-3" />
                                        {{ payment.status }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-right">
                                    <div v-if="payment.status === 'pending'" class="inline-flex items-center gap-1.5">
                                        <button
                                            @click="approvePayment(payment)"
                                            class="px-2.5 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold transition"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            @click="rejectPayment(payment)"
                                            class="px-2.5 py-1 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 text-xs font-semibold transition"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-500 text-[11px]">—</span>
                                </td>
                            </tr>

                            <tr v-if="payments.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-500">
                                    No billing records found matching your filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payments.links?.length > 3" class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                    <div>
                        Showing {{ payments.from || 0 }} to {{ payments.to || 0 }} of {{ payments.total }} transactions
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in payments.links"
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
    </SuperAdminLayout>
</template>