<script setup>
import { ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Store,
    Layers,
    CreditCard,
    Settings,
    LogOut,
    ExternalLink,
    Menu,
    X,
    ShieldCheck,
    Bell,
    CheckCircle2,
    AlertTriangle
} from 'lucide-vue-next';

const isSidebarOpen = ref(false);
const page = usePage();

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex font-sans">
        <!-- Sidebar Backdrop for Mobile -->
        <div
            v-if="isSidebarOpen"
            @click="isSidebarOpen = false"
            class="fixed inset-0 bg-black/60 z-40 lg:hidden backdrop-blur-sm"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 border-r border-slate-800 flex flex-col transition-transform duration-200 lg:static lg:translate-x-0',
                isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- Logo Header -->
            <div class="p-6 border-b border-slate-800/80 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center font-bold text-white shadow-lg shadow-emerald-500/20 text-xl tracking-wider">
                        TC
                    </div>
                    <div>
                        <div class="font-bold text-lg text-white tracking-tight flex items-center gap-1.5">
                            TrustCash
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20">PRO</span>
                        </div>
                        <p class="text-xs text-slate-400">Super Admin Portal</p>
                    </div>
                </div>
                <button @click="isSidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white p-1">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto">
                <div class="px-3 pb-2 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                    Core Platform
                </div>

                <Link
                    :href="route('super-admin.dashboard')"
                    :class="[
                        'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                        route().current('super-admin.dashboard')
                            ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-sm'
                            : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'
                    ]"
                >
                    <LayoutDashboard class="w-5 h-5" />
                    <span>Dashboard & Analytics</span>
                </Link>

                <Link
                    :href="route('super-admin.tenants.index')"
                    :class="[
                        'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                        route().current('super-admin.tenants.*')
                            ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-sm'
                            : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'
                    ]"
                >
                    <Store class="w-5 h-5" />
                    <span>Stores & Tenants</span>
                </Link>

                <Link
                    :href="route('super-admin.plans.index')"
                    :class="[
                        'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                        route().current('super-admin.plans.*')
                            ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-sm'
                            : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'
                    ]"
                >
                    <Layers class="w-5 h-5" />
                    <span>Subscription Plans</span>
                </Link>

                <Link
                    :href="route('super-admin.payments.index')"
                    :class="[
                        'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                        route().current('super-admin.payments.*')
                            ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-sm'
                            : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'
                    ]"
                >
                    <CreditCard class="w-5 h-5" />
                    <span>Billing & Payments</span>
                </Link>

                <div class="pt-5 px-3 pb-2 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                    Configuration
                </div>

                <Link
                    :href="route('super-admin.settings.index')"
                    :class="[
                        'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                        route().current('super-admin.settings.*')
                            ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-sm'
                            : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'
                    ]"
                >
                    <Settings class="w-5 h-5" />
                    <span>Global Settings</span>
                </Link>
            </nav>

            <!-- Bottom User & Live Storefront Link -->
            <div class="p-4 border-t border-slate-800/80 space-y-2">
                <a
                    href="/"
                    target="_blank"
                    class="flex items-center justify-between px-3.5 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800/60 transition"
                >
                    <span class="flex items-center gap-2">
                        <ExternalLink class="w-4 h-4 text-emerald-400" />
                        Live Storefront
                    </span>
                    <span class="text-[10px] text-slate-500">trustcash.com</span>
                </a>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/60">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-bold text-xs shrink-0">
                            <ShieldCheck class="w-4 h-4" />
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-xs font-medium text-white truncate">{{ page.props.auth?.user?.name || 'Super Admin' }}</p>
                            <p class="text-[10px] text-slate-400 truncate">{{ page.props.auth?.user?.email || 'admin@trustcash.com' }}</p>
                        </div>
                    </div>
                    <button
                        @click="logout"
                        title="Logout"
                        class="p-1.5 text-slate-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition"
                    >
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Top Navbar -->
            <header class="h-16 bg-slate-900/60 backdrop-blur-md border-b border-slate-800/80 px-6 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-4">
                    <button
                        @click="isSidebarOpen = true"
                        class="lg:hidden text-slate-400 hover:text-white p-2 rounded-lg hover:bg-slate-800"
                    >
                        <Menu class="w-5 h-5" />
                    </button>
                    <div>
                        <slot name="header" />
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Platform Healthy
                    </div>
                </div>
            </header>

            <!-- Flash Alerts -->
            <div v-if="$page.props.flash?.success" class="mx-6 mt-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center gap-3">
                <CheckCircle2 class="w-5 h-5 shrink-0" />
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <div v-if="$page.props.flash?.error" class="mx-6 mt-4 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex items-center gap-3">
                <AlertTriangle class="w-5 h-5 shrink-0" />
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- Page Body -->
            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>