<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Store,
    LayoutDashboard,
    ShoppingBag,
    ShoppingCart,
    Users,
    ArrowRight,
    CheckCircle2,
    Shield,
    Sparkles,
    Lock
} from 'lucide-vue-next';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

function loginAsDemo(role) {
    const email = role === 'admin' ? 'admin@ecom.test' : 'manager@ecom.test';
    router.post(route('login'), {
        email: email,
        password: 'password',
        remember: true,
    });
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 font-sans flex flex-col justify-between selection:bg-indigo-500 selection:text-white relative overflow-hidden">
        <Head title="Apex Commerce - Admin Portal" />

        <!-- Background Glow FX -->
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[500px] bg-indigo-600/20 blur-[130px] rounded-full pointer-events-none"></div>
        <div class="absolute -bottom-40 right-10 w-[500px] h-[400px] bg-emerald-600/15 blur-[120px] rounded-full pointer-events-none"></div>

        <!-- Top Header -->
        <header class="max-w-7xl w-full mx-auto px-6 h-20 flex items-center justify-between relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center text-white font-bold shadow-lg shadow-indigo-500/30">
                    <Store class="w-5 h-5" />
                </div>
                <div>
                    <span class="text-lg font-black tracking-tight text-white">ApexStore</span>
                    <span class="text-[10px] uppercase font-bold tracking-widest text-indigo-400 block -mt-1">Admin Suite</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('admin.dashboard')"
                    class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 transition-all"
                >
                    <LayoutDashboard class="w-4 h-4" /> Go to Dashboard
                </Link>
                <Link
                    v-else
                    :href="route('login')"
                    class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold text-xs border border-white/10 backdrop-blur-md transition-all"
                >
                    <Lock class="w-4 h-4" /> Sign In
                </Link>
            </div>
        </header>

        <!-- Hero Body -->
        <main class="max-w-5xl mx-auto px-6 py-12 text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold mb-6">
                <Sparkles class="w-3.5 h-3.5 text-indigo-400" />
                Laravel 12 + Vue 3 • eCommerce Admin Dashboard
            </div>

            <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tight leading-tight max-w-4xl mx-auto">
                Next-Generation Store Management <br />
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 via-purple-300 to-emerald-400">
                    Built for Modern Commerce
                </span>
            </h1>

            <p class="mt-6 text-base sm:text-lg text-slate-400 max-w-2xl mx-auto leading-relaxed">
                Complete control over your products, live inventory tracking, customer orders fulfillment, sales analytics, and store configuration in a lightning-fast Vue 3 Single Page Application.
            </p>

            <!-- Quick Demo Login Cards -->
            <div v-if="!$page.props.auth.user" class="mt-10 max-w-xl mx-auto bg-slate-900/80 border border-slate-800 backdrop-blur-xl p-6 rounded-3xl shadow-2xl">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center justify-center gap-1.5">
                    <Shield class="w-4 h-4 text-indigo-400" /> Instant Demo Access
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button
                        @click="loginAsDemo('admin')"
                        class="p-4 rounded-2xl bg-indigo-600/20 hover:bg-indigo-600/30 border border-indigo-500/40 text-left flex flex-col justify-between group transition-all"
                    >
                        <div>
                            <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-500 text-white mb-2">ADMIN</span>
                            <p class="text-xs font-bold text-white group-hover:text-indigo-300">Super Administrator</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">admin@ecom.test</p>
                        </div>
                        <span class="inline-flex items-center text-xs font-semibold text-indigo-400 mt-3">
                            Sign in as Admin <ArrowRight class="w-3.5 h-3.5 ml-1 group-hover:translate-x-1 transition-transform" />
                        </span>
                    </button>

                    <button
                        @click="loginAsDemo('manager')"
                        class="p-4 rounded-2xl bg-emerald-600/15 hover:bg-emerald-600/25 border border-emerald-500/30 text-left flex flex-col justify-between group transition-all"
                    >
                        <div>
                            <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500 text-white mb-2">MANAGER</span>
                            <p class="text-xs font-bold text-white group-hover:text-emerald-300">Store Manager</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">manager@ecom.test</p>
                        </div>
                        <span class="inline-flex items-center text-xs font-semibold text-emerald-400 mt-3">
                            Sign in as Manager <ArrowRight class="w-3.5 h-3.5 ml-1 group-hover:translate-x-1 transition-transform" />
                        </span>
                    </button>
                </div>

                <p class="text-[11px] text-slate-500 mt-4">Demo password: <code class="text-indigo-400 font-mono">password</code></p>
            </div>

            <!-- Features Highlights Grid -->
            <div class="mt-16 grid grid-cols-1 sm:grid-cols-3 gap-6 text-left">
                <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center mb-3">
                        <ShoppingBag class="w-5 h-5" />
                    </div>
                    <h4 class="text-sm font-bold text-white">Product & Stock Hub</h4>
                    <p class="text-xs text-slate-400 mt-1">Multi-image gallery, instant stock adjustments, low inventory alerts, and taxonomy tags.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mb-3">
                        <ShoppingCart class="w-5 h-5" />
                    </div>
                    <h4 class="text-sm font-bold text-white">Fulfillment & Invoices</h4>
                    <p class="text-xs text-slate-400 mt-1">Real-time status transitions, payment tracking, and printable PDF invoices.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center mb-3">
                        <Users class="w-5 h-5" />
                    </div>
                    <h4 class="text-sm font-bold text-white">Customer Insights</h4>
                    <p class="text-xs text-slate-400 mt-1">Lifetime value metrics, purchase order history, and customer account moderation.</p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="max-w-7xl w-full mx-auto px-6 py-6 flex flex-col sm:flex-row items-center justify-between border-t border-slate-900 text-xs text-slate-500">
            <p>Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }}) • Inertia.js • Vue 3</p>
            <p class="mt-2 sm:mt-0">ApexStore Admin Dashboard</p>
        </footer>
    </div>
</template>
