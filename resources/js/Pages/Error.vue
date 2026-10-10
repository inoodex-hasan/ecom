<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    AlertTriangle,
    ShieldAlert,
    Clock,
    ServerCrash,
    Wrench,
    ArrowLeft,
    Home,
    RotateCw,
} from 'lucide-vue-next';

const props = defineProps({
    status: {
        type: Number,
        default: 404,
    },
    message: {
        type: String,
        default: '',
    },
});

function formatMessage(msg, fallback) {
    if (!msg || typeof msg !== 'string') return fallback;
    const lower = msg.toLowerCase();
    if (
        lower.includes('no query results for model') ||
        lower.includes('app\\models') ||
        lower.includes('modelnotfoundexception') ||
        lower.includes('sqlstate') ||
        lower.includes('call to undefined') ||
        (lower.includes('the route') && lower.includes('could not be found'))
    ) {
        return fallback;
    }
    return msg;
}

const errorConfig = computed(() => {
    switch (props.status) {
        case 403:
            return {
                title: '403 Forbidden',
                subtitle: 'Access Denied',
                description: formatMessage(
                    props.message,
                    'You do not have the required permissions or administrative privileges to view this section.'
                ),
                badge: 'Restricted Access',
                badgeClass: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                icon: ShieldAlert,
                iconColor: 'text-amber-500',
            };
        case 419:
            return {
                title: '419 Page Expired',
                subtitle: 'CSRF Token Expired',
                description: formatMessage(
                    props.message,
                    'Your security session has timed out due to inactivity. Please refresh the page to renew your session.'
                ),
                badge: 'Session Timeout',
                badgeClass: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                icon: Clock,
                iconColor: 'text-rose-500',
            };
        case 500:
            return {
                title: '500 Server Error',
                subtitle: 'Internal System Error',
                description: formatMessage(
                    props.message,
                    'An unexpected error occurred while processing your request. Our technical team has been notified.'
                ),
                badge: 'System Error',
                badgeClass: 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20',
                icon: ServerCrash,
                iconColor: 'text-red-500',
            };
        case 503:
            return {
                title: '503 Maintenance',
                subtitle: 'Service Unavailable',
                description: formatMessage(
                    props.message,
                    'Loomora is currently undergoing scheduled maintenance or system upgrades. Please check back shortly.'
                ),
                badge: 'Maintenance Mode',
                badgeClass: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
                icon: Wrench,
                iconColor: 'text-purple-500',
            };
        case 404:
        default:
            return {
                title: '404 Not Found',
                subtitle: 'Page Not Found',
                description: formatMessage(
                    props.message,
                    'The administrative resource, record, or page you requested could not be found.'
                ),
                badge: 'Resource Missing',
                badgeClass: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
                icon: AlertTriangle,
                iconColor: 'text-sky-500',
            };
    }
});

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/admin/dashboard';
    }
};

const reloadPage = () => {
    if (typeof window !== 'undefined') {
        window.location.reload();
    }
};
</script>

<template>
    <Head :title="errorConfig.title" />

    <!-- ── ADMIN LAYOUT: Header & Sidebar ALWAYS remain intact ──────── -->
    <AdminLayout>
        <div class="min-h-[75vh] flex flex-col items-center justify-center py-12 px-4 text-center">
            <!-- Error Badge -->
            <div
                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider border mb-6 shadow-sm"
                :class="errorConfig.badgeClass"
            >
                <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse" />
                {{ errorConfig.badge }}
            </div>

            <!-- Big Status Code -->
            <h1 class="text-7xl sm:text-9xl font-black text-transparent bg-clip-text bg-gradient-to-b from-slate-900 via-slate-700 to-slate-400 dark:from-white dark:via-slate-200 dark:to-slate-500 tracking-tight mb-2 drop-shadow-sm">
                {{ status }}
            </h1>

            <!-- Subtitle -->
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-3 tracking-tight">
                {{ errorConfig.subtitle }}
            </h2>

            <!-- Description -->
            <p class="text-slate-500 dark:text-slate-400 text-base sm:text-lg max-w-lg mb-8 leading-relaxed">
                {{ errorConfig.description }}
            </p>

            <!-- Action buttons -->
            <div class="flex flex-wrap items-center justify-center gap-3.5">
                <Link
                    href="/admin/dashboard"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 hover:from-sky-400 hover:to-indigo-500 transition-all hover:scale-105 active:scale-95 cursor-pointer"
                >
                    <Home class="w-4 h-4" />
                    Back to Dashboard
                </Link>

                <button
                    type="button"
                    @click="goBack"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-sm border border-slate-200 dark:border-slate-800 transition-all active:scale-95 shadow-sm cursor-pointer"
                >
                    <ArrowLeft class="w-4 h-4" />
                    Go Back
                </button>

                <button
                    v-if="status === 419 || status === 500 || status === 503"
                    type="button"
                    @click="reloadPage"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-sm border border-slate-200 dark:border-slate-800 transition-all active:scale-95 shadow-sm cursor-pointer"
                >
                    <RotateCw class="w-4 h-4" />
                    Refresh
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
