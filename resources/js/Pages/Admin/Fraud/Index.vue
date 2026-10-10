<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ShieldAlert,
    ShieldCheck,
    Search,
    AlertTriangle,
    CheckCircle2,
    XCircle,
    RotateCcw,
    Smartphone,
    CreditCard,
    DollarSign,
    Ban,
    UserCheck,
    Truck,
    Filter,
    X,
    ExternalLink,
    Clock,
    Plus,
    Trash2,
    Sliders,
    Layers,
    Save,
    Eye,
    ChevronRight,
    Loader2,
    Info,
    AlertCircle,
    ArrowUpRight,
    Sparkles,
    Check
} from 'lucide-vue-next';
import CustomSelect from '@/Components/CustomSelect.vue';

const props = defineProps({
    metrics: Object,
    orders: Object,
    blacklist: Array,
    settings: Object,
    filters: Object,
    hasCourierApi: Boolean,
    hasFraudApi: Boolean,
});

const activeTab = ref('orders'); // 'orders' | 'checker' | 'blacklist' | 'settings'

// Blacklist / Whitelist sub-filter state
const blacklistFilter = ref('all'); // 'all' | 'blacklist' | 'whitelist'

const blacklistItemsCount = computed(() => {
    return props.blacklist ? props.blacklist.filter((i) => i.list_type === 'blacklist').length : 0;
});

const whitelistItemsCount = computed(() => {
    return props.blacklist ? props.blacklist.filter((i) => i.list_type === 'whitelist').length : 0;
});

const filteredBlacklist = computed(() => {
    if (!props.blacklist) return [];
    if (blacklistFilter.value === 'all') return props.blacklist;
    return props.blacklist.filter((i) => i.list_type === blacklistFilter.value);
});

// Filters state
const search = ref(props.filters?.search || '');
const selectedRisk = ref(props.filters?.risk || 'all');
const selectedStatus = ref(props.filters?.status || 'all');

function applyFilters() {
    router.get(
        route('admin.fraud.index'),
        {
            search: search.value || undefined,
            risk: selectedRisk.value !== 'all' ? selectedRisk.value : undefined,
            status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
        },
        { preserveState: true, replace: true }
    );
}

let searchTimer = null;
function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
}

// Phone Checker Tool State
const lookupPhoneInput = ref('');
const isLookingUp = ref(false);
const lookupResult = ref(null);
const lookupError = ref('');

async function performPhoneLookup() {
    if (!lookupPhoneInput.value.trim()) return;
    isLookingUp.value = true;
    lookupError.value = '';
    lookupResult.value = null;

    try {
        const response = await fetch(route('admin.fraud.lookup'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ phone: lookupPhoneInput.value }),
        });

        if (!response.ok) {
            throw new Error('Failed to query phone lookup database.');
        }

        lookupResult.value = await response.json();
    } catch (err) {
        lookupError.value = err.message || 'Error occurred while verifying phone number.';
    } finally {
        isLookingUp.value = false;
    }
}

// Advance Payment Modal State
const showAdvanceModal = ref(false);
const selectedOrderForAdvance = ref(null);
const advanceForm = useForm({
    method: 'bkash',
    transaction_id: '',
});

function openAdvanceModal(order) {
    selectedOrderForAdvance.value = order;
    advanceForm.method = 'bkash';
    advanceForm.transaction_id = '';
    showAdvanceModal.value = true;
}

function submitAdvanceConfirmation() {
    if (!selectedOrderForAdvance.value) return;
    advanceForm.post(route('admin.fraud.confirm-advance', selectedOrderForAdvance.value.id), {
        onSuccess: () => {
            showAdvanceModal.value = false;
            selectedOrderForAdvance.value = null;
        },
    });
}

// Request Advance Fee Action
function requestAdvanceFee(order, fee = 100) {
    router.post(route('admin.fraud.request-advance', order.id), { fee });
}

// Order verification and block actions
function verifyOrder(order) {
    router.post(route('admin.fraud.verify', order.id));
}

function blockOrder(order) {
    if (confirm(`Block Order #${order.order_number} and cancel fulfillment?`)) {
        router.post(route('admin.fraud.block', order.id), {
            add_to_blacklist: true,
            reason: 'Flagged as high-risk fake/return order.',
        });
    }
}

function recheckOrder(order) {
    router.post(route('admin.fraud.recheck', order.id));
}

// Blacklist / Whitelist Form
const showAddEntryModal = ref(false);
const entryForm = useForm({
    type: 'phone',
    value: '',
    list_type: 'blacklist',
    reason: '',
});

function submitAddEntry() {
    entryForm.post(route('admin.fraud.blacklist.store'), {
        onSuccess: () => {
            showAddEntryModal.value = false;
            entryForm.reset();
        },
    });
}

function deleteBlacklistEntry(item) {
    if (confirm(`Remove "${item.value}" from ${item.list_type}?`)) {
        router.delete(route('admin.fraud.blacklist.destroy', item.id));
    }
}

// Settings Form
const settingsForm = useForm({
    fraud_check_enabled: props.settings.fraud_check_enabled,
    fraud_cod_threshold: props.settings.fraud_cod_threshold,
    fraud_advance_fee_inside_dhaka: props.settings.fraud_advance_fee_inside_dhaka,
    fraud_advance_fee_outside_dhaka: props.settings.fraud_advance_fee_outside_dhaka,
    fraud_auto_flag_high_risk: props.settings.fraud_auto_flag_high_risk,
    fraud_courier_provider: props.settings.fraud_courier_provider,
    fraud_courier_api_endpoint: props.settings.fraud_courier_api_endpoint,
    fraud_courier_api_key: props.settings.fraud_courier_api_key,
    steadfast_api_key: props.settings.steadfast_api_key,
    steadfast_secret_key: props.settings.steadfast_secret_key,
    pathao_store_id: props.settings.pathao_store_id,
});

function saveSettings() {
    settingsForm.post(route('admin.fraud.settings.update'));
}

// Helper formats
const formatCurrency = (val) => {
    if (val === 'N/A' || val === null || val === undefined) return 'N/A';
    return '৳' + Number(val || 0).toLocaleString('en-US');
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

function getRiskBadge(level, score) {
    switch (level) {
        case 'high':
            return {
                label: 'High Risk',
                scoreText: `${score}/100`,
                bg: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                dot: 'bg-rose-500',
            };
        case 'medium':
            return {
                label: 'Medium Risk',
                scoreText: `${score}/100`,
                bg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                dot: 'bg-amber-500',
            };
        case 'low':
        default:
            return {
                label: 'Low Risk',
                scoreText: `${score}/100`,
                bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                dot: 'bg-emerald-500',
            };
    }
}

function getStatusBadge(status) {
    switch (status) {
        case 'blocked':
            return { label: 'Blocked / Cancelled', class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' };
        case 'advance_requested':
            return { label: 'Advance Fee Pending', class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' };
        case 'verified':
            return { label: 'Verified & Approved', class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' };
        case 'suspicious':
            return { label: 'Needs Phone Call', class: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' };
        case 'flagged':
            return { label: 'Flagged High Risk', class: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20' };
        default:
            return { label: 'Clean', class: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700' };
    }
}

function getOperatorInitial(operator) {
    if (!operator) return 'B';
    const name = operator.name || operator.code || '';
    return name.trim().charAt(0).toUpperCase() || 'B';
}

function getOperatorColor(operator) {
    const initial = getOperatorInitial(operator);
    switch (initial) {
        case 'G':
            return 'bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border-sky-200/60 dark:border-sky-800/60';
        case 'R':
            return 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border-rose-200/60 dark:border-rose-800/60';
        case 'B':
            return 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border-amber-200/60 dark:border-amber-800/60';
        case 'A':
            return 'bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 border-red-200/60 dark:border-red-800/60';
        case 'T':
            return 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-800/60';
        default:
            return 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border-indigo-200/60 dark:border-indigo-800/60';
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Bangladeshi Fraud Shield & COD Protection - Admin" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Bangladeshi Fraud Shield & COD Defense</h1>
                        <span
                            v-if="!hasFraudApi && !hasCourierApi"
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 flex items-center gap-1"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> API Not Configured (N/A)
                        </span>
                        <span
                            v-else
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Active Protection
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Heuristic risk scoring, BD mobile operator validation, courier return rate (RTO) intelligence, and advance delivery fee recovery
                    </p>
                </div>

               
            </div>
        </template>

        <div class="space-y-6">
            <!-- Warning Banner when API is not configured -->
            <div
                v-if="!hasFraudApi || !hasCourierApi"
                class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300 text-xs flex items-center gap-3"
            >
                <AlertTriangle class="w-5 h-5 text-amber-500 shrink-0" />
                <div>
                    <span class="font-bold">Notice:</span> External Fraud and Courier APIs are not yet configured. Real-time courier parcel intelligence and live risk scores will display <span class="font-mono font-bold bg-amber-500/20 px-1.5 py-0.5 rounded">N/A</span> until live API keys/endpoints are entered in <span class="font-semibold underline cursor-pointer" @click="activeTab = 'settings'">Shield Rules & Thresholds</span>.
                </div>
            </div>

            <!-- 1. Executive Bento Stats Deck -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Screened Orders -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-400">Total Screened Orders</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                            {{ hasFraudApi ? metrics.total_screened : 'N/A' }}
                        </h3>
                        <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1 flex items-center gap-1">
                            <CheckCircle2 class="w-3.5 h-3.5" />
                            {{ hasFraudApi ? `${metrics.low_risk} Clean & Auto-Approved` : 'N/A' }}
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <ShieldCheck class="w-6 h-6" />
                    </div>
                </div>

                <!-- High Risk Orders -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-400">High Risk & Flagged</p>
                        <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">
                            {{ hasFraudApi ? metrics.high_risk : 'N/A' }}
                        </h3>
                        <p class="text-[11px] text-amber-500 font-semibold mt-1 flex items-center gap-1">
                            <AlertTriangle class="w-3.5 h-3.5" />
                            {{ hasFraudApi ? `${metrics.medium_risk} Under Phone Review` : 'N/A' }}
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <ShieldAlert class="w-6 h-6" />
                    </div>
                </div>

                <!-- Courier Return Loss Prevented -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-400">Courier Loss Prevented</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                            {{ hasFraudApi && hasCourierApi ? formatCurrency(metrics.estimated_saved_bdt) : 'N/A' }}
                        </h3>
                        <p class="text-[11px] text-slate-400 font-medium mt-1">
                            {{ hasFraudApi && hasCourierApi ? 'Based on ৳120 return cost saved per blocked order' : 'Real-time calculation unavailable (N/A)' }}
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <DollarSign class="w-6 h-6" />
                    </div>
                </div>

                <!-- Blacklist Registry -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-400">Blacklisted Entities</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ metrics.blacklist_count }}</h3>
                        <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold mt-1">
                            +{{ metrics.whitelist_count }} VIP Whitelisted Numbers
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0">
                        <Ban class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- 2. Navigation Tabs Bar -->
            <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
                <button
                    @click="activeTab = 'orders'"
                    :class="[
                        activeTab === 'orders'
                            ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/20'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800',
                        'px-4 py-2 rounded-2xl text-xs flex items-center gap-2 transition-all cursor-pointer'
                    ]"
                >
                    <ShieldAlert class="w-4 h-4" />
                    <span>Risk Screening Stream</span>
                    <span v-if="metrics.high_risk > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white">
                        {{ metrics.high_risk }} high
                    </span>
                </button>

                <button
                    @click="activeTab = 'checker'"
                    :class="[
                        activeTab === 'checker'
                            ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/20'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800',
                        'px-4 py-2 rounded-2xl text-xs flex items-center gap-2 transition-all cursor-pointer'
                    ]"
                >
                    <Smartphone class="w-4 h-4" />
                    <span>Live Phone Verification</span>
                </button>

                <button
                    @click="activeTab = 'blacklist'"
                    :class="[
                        activeTab === 'blacklist'
                            ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/20'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800',
                        'px-4 py-2 rounded-2xl text-xs flex items-center gap-2 transition-all cursor-pointer'
                    ]"
                >
                    <Ban class="w-4 h-4" />
                    <span>Blacklist / Whitelist Registry</span>
                    <span
                        :class="[
                            activeTab === 'blacklist'
                                ? 'bg-white/25 text-white'
                                : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300',
                            'px-2 py-0.5 rounded-full text-[10px] font-mono font-bold transition-colors'
                        ]"
                    >
                        {{ blacklist.length }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'settings'"
                    :class="[
                        activeTab === 'settings'
                            ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/20'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800',
                        'px-4 py-2 rounded-2xl text-xs flex items-center gap-2 transition-all cursor-pointer'
                    ]"
                >
                    <Sliders class="w-4 h-4" />
                    <span>Shield Rules & Thresholds</span>
                </button>
            </div>

            <!-- TAB 1: RISK SCREENING STREAM -->
            <div v-if="activeTab === 'orders'" class="space-y-6">
                <!-- Filter Bar -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col md:flex-row gap-3 items-center justify-between">
                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                        <!-- Search Box -->
                        <div class="relative w-full sm:w-72">
                            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                            <input
                                v-model="search"
                                type="text"
                                @input="onSearchInput"
                                placeholder="Search by Order #, BD phone, name..."
                                class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- Risk Level Filter -->
                        <div class="w-full sm:w-44">
                            <CustomSelect
                                v-model="selectedRisk"
                                @change="applyFilters"
                                :options="[
                                    { value: 'all', label: 'All Risk Tiers' },
                                    { value: 'high', label: 'High Risk Only' },
                                    { value: 'medium', label: 'Medium Risk' },
                                    { value: 'low', label: 'Low Risk (Safe)' },
                                ]"
                                placeholder="All Risk Tiers"
                                compact
                            />
                        </div>

                        <!-- Status Filter -->
                        <div class="w-full sm:w-48">
                            <CustomSelect
                                v-model="selectedStatus"
                                @change="applyFilters"
                                :options="[
                                    { value: 'all', label: 'All Statuses' },
                                    { value: 'flagged', label: 'Flagged High Risk' },
                                    { value: 'suspicious', label: 'Needs Phone Call' },
                                    { value: 'advance_requested', label: 'Advance Fee Pending' },
                                    { value: 'verified', label: 'Verified & Safe' },
                                    { value: 'blocked', label: 'Blocked / Cancelled' },
                                ]"
                                placeholder="All Statuses"
                                compact
                            />
                        </div>
                    </div>

                    <div class="text-xs text-slate-500 dark:text-slate-400 w-full md:w-auto text-right">
                        Showing {{ orders.total || orders.data?.length || 0 }} assessed orders
                    </div>
                </div>

                <!-- Orders Assessment Table -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-xs uppercase text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-6 py-4">Order</th>
                                    <th class="px-6 py-4">Customer & BD Phone</th>
                                    <th class="px-6 py-4">Value & Payment</th>
                                    <th class="px-6 py-4">Fraud Risk Score</th>
                                    <th class="px-6 py-4">Detected Factors</th>
                                    <th class="px-6 py-4">Status & Action</th>
                                    <th class="px-6 py-4 text-right">Management</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                                <tr
                                    v-for="order in orders.data"
                                    :key="order.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors"
                                >
                                    <!-- Order Number & Date -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link
                                            :href="route('admin.orders.show', order.id)"
                                            class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400"
                                        >
                                            #{{ order.order_number }}
                                        </Link>
                                        <p class="text-[11px] text-slate-400">{{ formatDate(order.created_at) }}</p>
                                    </td>

                                    <!-- Customer Details & BD Mobile -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ order.shipping_address?.name || order.customer?.name || 'Guest Customer' }}
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="font-mono text-slate-600 dark:text-slate-300">
                                                {{ order.shipping_address?.phone || order.customer?.phone || 'No Phone' }}
                                            </span>
                                            <span class="text-[10px] text-slate-400">· {{ order.shipping_address?.city || 'BD' }}</span>
                                        </div>
                                    </td>

                                    <!-- Total & Payment Method -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ formatCurrency(order.total) }}
                                        </div>
                                        <span
                                            :class="[
                                                order.payment_method === 'cod'
                                                    ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
                                                    : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                                'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border mt-0.5 uppercase'
                                            ]"
                                        >
                                            {{ order.payment_method }}
                                        </span>
                                    </td>

                                    <!-- Risk Score Meter -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="hasFraudApi" class="space-y-1">
                                            <span
                                                :class="[
                                                    getRiskBadge(order.fraud_risk_level, order.fraud_score).bg,
                                                    'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border'
                                                ]"
                                            >
                                                <span :class="['w-1.5 h-1.5 rounded-full', getRiskBadge(order.fraud_risk_level, order.fraud_score).dot]"></span>
                                                {{ getRiskBadge(order.fraud_risk_level, order.fraud_score).label }} ({{ order.fraud_score }}/100)
                                            </span>
                                            <div class="w-24 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                                <div
                                                    :class="[
                                                        order.fraud_risk_level === 'high' ? 'bg-rose-500' : (order.fraud_risk_level === 'medium' ? 'bg-amber-500' : 'bg-emerald-500'),
                                                        'h-full rounded-full transition-all'
                                                    ]"
                                                    :style="{ width: `${order.fraud_score}%` }"
                                                ></div>
                                            </div>
                                        </div>
                                        <span v-else class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-slate-200/60 dark:border-slate-700/60">
                                            N/A
                                        </span>
                                    </td>

                                    <!-- Primary Risk Flags Summary -->
                                    <td class="px-6 py-4 max-w-xs">
                                        <div v-if="hasFraudApi && order.fraud_flags && order.fraud_flags.length" class="space-y-1">
                                            <div
                                                v-for="(flag, i) in order.fraud_flags.slice(0, 2)"
                                                :key="i"
                                                class="flex items-center gap-1.5 text-[11px]"
                                            >
                                                <AlertCircle v-if="flag.severity === 'danger'" class="w-3.5 h-3.5 text-rose-500 shrink-0" />
                                                <AlertTriangle v-else-if="flag.severity === 'warning'" class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                                <CheckCircle2 v-else class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                                                <span class="truncate text-slate-600 dark:text-slate-300">{{ flag.title }}</span>
                                            </div>
                                            <p v-if="order.fraud_flags.length > 2" class="text-[10px] text-slate-400 font-mono">
                                                +{{ order.fraud_flags.length - 2 }} more flags
                                            </p>
                                        </div>
                                        <span v-else-if="hasFraudApi" class="text-[11px] text-slate-400">No flags</span>
                                        <span v-else class="text-[11px] text-slate-400 font-mono">N/A</span>
                                    </td>

                                    <!-- Status Badge & Action -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="hasFraudApi">
                                            <span
                                                :class="[
                                                    getStatusBadge(order.fraud_status).class,
                                                    'inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-bold border'
                                                ]"
                                            >
                                                {{ getStatusBadge(order.fraud_status).label }}
                                            </span>
                                            <p v-if="order.advance_delivery_charge" class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">
                                                Fee: ৳{{ order.advance_delivery_charge }} ({{ order.advance_payment_status }})
                                            </p>
                                        </div>
                                        <span v-else class="inline-flex items-center px-2.5 py-1 rounded-xl text-[11px] font-semibold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-slate-200/60 dark:border-slate-700/60">
                                            N/A
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Request Advance Fee -->
                                            <button
                                                v-if="order.fraud_status !== 'verified' && order.fraud_status !== 'blocked'"
                                                type="button"
                                                @click="requestAdvanceFee(order, 150)"
                                                class="px-2.5 py-1.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 font-bold text-[10px] transition-all cursor-pointer"
                                                title="Request ৳150 Advance Delivery Fee via bKash/Nagad"
                                            >
                                                Req ৳150
                                            </button>

                                            <!-- Confirm Advance Modal -->
                                            <button
                                                v-if="order.fraud_status === 'advance_requested' || order.advance_payment_status === 'pending'"
                                                type="button"
                                                @click="openAdvanceModal(order)"
                                                class="px-2.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[10px] shadow-xs transition-all cursor-pointer"
                                                title="Enter bKash/Nagad TrxID to confirm payment"
                                            >
                                                Confirm TrxID
                                            </button>

                                            <!-- Approve / Verify Order -->
                                            <button
                                                v-if="order.fraud_status !== 'verified'"
                                                type="button"
                                                @click="verifyOrder(order)"
                                                class="p-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 transition-colors cursor-pointer"
                                                title="Mark as Verified & Safe"
                                            >
                                                <CheckCircle2 class="w-4 h-4" />
                                            </button>

                                            <!-- Cancel / Block Order -->
                                            <button
                                                v-if="order.fraud_status !== 'blocked' && order.status !== 'cancelled'"
                                                type="button"
                                                @click="blockOrder(order)"
                                                class="p-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 transition-colors cursor-pointer"
                                                title="Block & Blacklist Customer"
                                            >
                                                <Ban class="w-4 h-4" />
                                            </button>

                                            <!-- Re-scan Button -->
                                            <button
                                                type="button"
                                                @click="recheckOrder(order)"
                                                class="p-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors cursor-pointer"
                                                title="Re-run Fraud Analysis"
                                            >
                                                <RotateCcw class="w-4 h-4" />
                                            </button>

                                            <Link
                                                :href="route('admin.orders.show', order.id)"
                                                class="p-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-indigo-600 transition-colors"
                                                title="View Order Details"
                                            >
                                                <Eye class="w-4 h-4" />
                                            </Link>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!orders.data || orders.data.length === 0">
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <div class="max-w-sm mx-auto flex flex-col items-center">
                                            <ShieldCheck class="w-12 h-12 text-emerald-500/50 mb-3" />
                                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">No Flagged Orders Found</h4>
                                            <p class="text-xs text-slate-400 mt-1">All incoming orders meet security standards or filter criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: LIVE PHONE LOOKUP TOOL (ফোন নম্বর ভেরিফিকেশন) -->
            <div v-if="activeTab === 'checker'" class="space-y-6">
                <!-- Search Box Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs max-w-3xl mx-auto space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <Smartphone class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Bangladeshi Phone Number Inspection Tool</h3>
                            <p class="text-xs text-slate-400">Instant lookup for operator series, courier network return rate (RTO), and blacklist status</p>
                        </div>
                    </div>

                    <form @submit.prevent="performPhoneLookup" class="flex flex-col sm:flex-row gap-3 pt-2">
                        <div class="relative flex-1">
                            <Smartphone class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
                            <input
                                v-model="lookupPhoneInput"
                                type="text"
                                placeholder="Enter BD Phone: 017XXXXXXXX, 018..., 019..."
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs font-mono text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="isLookingUp"
                            class="px-5 py-2.5 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 flex items-center justify-center gap-2 transition-all cursor-pointer"
                        >
                            <Loader2 v-if="isLookingUp" class="w-4 h-4 animate-spin" />
                            <Search v-else class="w-4 h-4" />
                            <span>Verify Phone</span>
                        </button>
                    </form>

                    <div v-if="lookupError" class="p-3 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs flex items-center gap-2">
                        <AlertCircle class="w-4 h-4 shrink-0" />
                        <span>{{ lookupError }}</span>
                    </div>
                </div>

                <!-- Lookup Result Details Card -->
                <div v-if="lookupResult" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs max-w-3xl mx-auto space-y-6">
                    <!-- Top Result Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    getOperatorColor(lookupResult.operator),
                                    'w-12 h-12 rounded-2xl border flex items-center justify-center font-black text-2xl select-none shrink-0 shadow-xs'
                                ]"
                            >
                                {{ getOperatorInitial(lookupResult.operator) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="text-base font-bold font-mono text-slate-900 dark:text-white">{{ lookupResult.normalized || lookupResult.phone }}</h4>
                                    <span v-if="lookupResult.is_valid" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        Valid Subscriber Line
                                    </span>
                                    <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                        Invalid Format
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ lookupResult.operator ? `${lookupResult.operator.name} Network` : lookupResult.reason }}
                                </p>
                            </div>
                        </div>

                        <!-- Blacklist / Whitelist Status -->
                        <div>
                            <span v-if="lookupResult.is_blacklisted" class="px-3 py-1 rounded-xl text-xs font-bold bg-rose-500 text-white flex items-center gap-1.5 shadow-xs">
                                <Ban class="w-3.5 h-3.5" /> Blacklisted Phone
                            </span>
                            <span v-else-if="lookupResult.is_whitelisted" class="px-3 py-1 rounded-xl text-xs font-bold bg-emerald-500 text-white flex items-center gap-1.5 shadow-xs">
                                <UserCheck class="w-3.5 h-3.5" /> VIP Whitelisted
                            </span>
                            <span v-else class="px-3 py-1 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                <ShieldCheck class="w-3.5 h-3.5 text-emerald-500" /> Normal Standing
                            </span>
                        </div>
                    </div>

                    <!-- Courier Performance Gauges -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-4 text-center border border-slate-100 dark:border-slate-800">
                            <p class="text-[11px] text-slate-400 font-medium">Total Courier Parcels</p>
                            <h5 class="text-xl font-black text-slate-900 dark:text-white mt-1">
                                {{ hasCourierApi && lookupResult.courier_history?.has_api ? (lookupResult.courier_history?.total_parcels || 0) : 'N/A' }}
                            </h5>
                            <span class="text-[10px] text-slate-400">{{ hasCourierApi && lookupResult.courier_history?.has_api ? lookupResult.courier_history?.source : 'N/A (API Not Configured)' }}</span>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-4 text-center border border-slate-100 dark:border-slate-800">
                            <p class="text-[11px] text-slate-400 font-medium">Successful Deliveries</p>
                            <h5 class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                                {{ hasCourierApi && lookupResult.courier_history?.has_api ? (lookupResult.courier_history?.delivered || 0) : 'N/A' }}
                            </h5>
                            <span class="text-[10px] text-emerald-500 font-semibold">
                                {{ hasCourierApi && lookupResult.courier_history?.has_api ? `${lookupResult.courier_history?.success_rate}% Success Rate` : 'N/A' }}
                            </span>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-4 text-center border border-slate-100 dark:border-slate-800">
                            <p class="text-[11px] text-slate-400 font-medium">Returned / Cancelled (RTO)</p>
                            <h5 class="text-xl font-black text-rose-600 dark:text-rose-400 mt-1">
                                {{ hasCourierApi && lookupResult.courier_history?.has_api ? (lookupResult.courier_history?.returned || 0) : 'N/A' }}
                            </h5>
                            <span :class="[lookupResult.courier_history?.rto_risk === 'critical' ? 'text-rose-500' : 'text-amber-500', 'text-[10px] font-bold uppercase']">
                                {{ hasCourierApi && lookupResult.courier_history?.has_api ? `${lookupResult.courier_history?.rto_risk} Risk` : 'N/A' }}
                            </span>
                        </div>
                    </div>

                    <!-- Quick Registry Action -->
                    <div class="flex items-center justify-between pt-2">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Found {{ lookupResult.store_orders_count }} previous orders in this store.
                        </p>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="!lookupResult.is_blacklisted"
                                type="button"
                                @click="entryForm.type = 'phone'; entryForm.value = lookupResult.normalized; entryForm.list_type = 'blacklist'; showAddEntryModal = true"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 transition-colors cursor-pointer"
                            >
                                Add to Blacklist
                            </button>
                            <button
                                v-if="!lookupResult.is_whitelisted"
                                type="button"
                                @click="entryForm.type = 'phone'; entryForm.value = lookupResult.normalized; entryForm.list_type = 'whitelist'; showAddEntryModal = true"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 hover:bg-emerald-500/20 transition-colors cursor-pointer"
                            >
                                Add to Whitelist
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: BLACKLIST & WHITELIST REGISTRY -->
            <div v-if="activeTab === 'blacklist'" class="space-y-6">
                <!-- Action Bar -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Fraud Blacklist & VIP Whitelist Registry</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-900/50">
                                {{ blacklist.length }} Total Entries
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">Manage blocked scammer numbers, fake email domains, and trusted VIP customer phones</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                        <!-- Sub-filter Pills -->
                        <div class="flex items-center p-1 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/60">
                            <button
                                type="button"
                                @click="blacklistFilter = 'all'"
                                :class="blacklistFilter === 'all' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                                class="px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer"
                            >
                                All ({{ blacklist.length }})
                            </button>
                            <button
                                type="button"
                                @click="blacklistFilter = 'blacklist'"
                                :class="blacklistFilter === 'blacklist' ? 'bg-white dark:bg-slate-900 text-rose-600 dark:text-rose-400 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-rose-600'"
                                class="px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer"
                            >
                                Blacklist ({{ blacklistItemsCount }})
                            </button>
                            <button
                                type="button"
                                @click="blacklistFilter = 'whitelist'"
                                :class="blacklistFilter === 'whitelist' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-emerald-600'"
                                class="px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer"
                            >
                                Whitelist ({{ whitelistItemsCount }})
                            </button>
                        </div>

                        <button
                            type="button"
                            @click="showAddEntryModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 transition-all cursor-pointer shrink-0"
                        >
                            <Plus class="w-4 h-4" /> Add Registry Entry
                        </button>
                    </div>
                </div>

                <!-- Registry Table -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-xs uppercase text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-6 py-4">Type</th>
                                    <th class="px-6 py-4">Target Value</th>
                                    <th class="px-6 py-4">List Tier</th>
                                    <th class="px-6 py-4">Reason / Notes</th>
                                    <th class="px-6 py-4">Added Date</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                                <tr v-for="item in filteredBlacklist" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="px-6 py-4 font-bold uppercase text-slate-400 text-[10px]">
                                        {{ item.type }}
                                    </td>
                                    <td class="px-6 py-4 font-mono font-bold text-slate-900 dark:text-white">
                                        {{ item.value }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="[
                                                item.list_type === 'blacklist'
                                                    ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
                                                    : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border'
                                            ]"
                                        >
                                            {{ item.list_type === 'blacklist' ? 'Blocked' : 'Whitelisted VIP' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300">
                                        {{ item.reason || '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-400">
                                        {{ formatDate(item.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            type="button"
                                            @click="deleteBlacklistEntry(item)"
                                            class="p-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 transition-colors cursor-pointer"
                                            title="Remove entry"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="filteredBlacklist.length === 0">
                                    <td colspan="6" class="py-12 text-center text-slate-400">
                                        No {{ blacklistFilter === 'all' ? 'registry' : blacklistFilter }} entries found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 4: SHIELD RULES & SETTINGS -->
            <div v-if="activeTab === 'settings'" class="w-full space-y-6">
                <form @submit.prevent="saveSettings" class="space-y-6">
                    <!-- General Rules Card -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 lg:p-8 shadow-xs space-y-6">
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                <ShieldCheck class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Fraud Shield Configuration</h3>
                                <p class="text-xs text-slate-400">Adjust automated scoring triggers, Cash on Delivery threshold, and advance fee policies</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                            <!-- COD Max Limit -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Cash on Delivery (COD) Threshold (৳)
                                </label>
                                <input
                                    v-model="settingsForm.fraud_cod_threshold"
                                    type="number"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Orders above this amount recommend advance confirmation</p>
                            </div>

                            <!-- Advance Inside Dhaka -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Advance Fee - Inside Dhaka (৳)
                                </label>
                                <input
                                    v-model="settingsForm.fraud_advance_fee_inside_dhaka"
                                    type="number"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Standard inside Dhaka courier charge (e.g. ৳100)</p>
                            </div>

                            <!-- Advance Outside Dhaka -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Advance Fee - Outside Dhaka (৳)
                                </label>
                                <input
                                    v-model="settingsForm.fraud_advance_fee_outside_dhaka"
                                    type="number"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Standard outside Dhaka courier charge (e.g. ৳150)</p>
                            </div>

                            <!-- Auto Flag Toggle -->
                            <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                                <div>
                                    <p class="text-xs font-bold text-slate-900 dark:text-white">Auto-Flag High Risk</p>
                                    <p class="text-[11px] text-slate-400">Put high-risk orders on hold automatically</p>
                                </div>
                                <input
                                    v-model="settingsForm.fraud_auto_flag_high_risk"
                                    type="checkbox"
                                    class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Courier Integration Card -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 lg:p-8 shadow-xs space-y-6">
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                <Truck class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Bangladeshi Courier Network API Integration</h3>
                                <p class="text-xs text-slate-400">Configure Steadfast Courier, Pathao, and FraudCheck BD API credentials for 1-click parcel dispatch and RTO lookups</p>
                            </div>
                        </div>

                        <!-- Steadfast Courier Section -->
                        <div class="p-4 rounded-2xl bg-slate-50/75 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-800 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white">Steadfast Courier Integration</h4>
                                </div>
                                <span class="text-[10px] font-semibold text-slate-400">portal.steadfast.com.bd</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                        Steadfast API Key
                                    </label>
                                    <input
                                        v-model="settingsForm.steadfast_api_key"
                                        type="password"
                                        placeholder="Paste your Steadfast API Key..."
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-mono text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                    <p class="text-[11px] text-slate-400 mt-1">Found in your Steadfast Portal &gt; API Settings</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                        Steadfast Secret Key
                                    </label>
                                    <input
                                        v-model="settingsForm.steadfast_secret_key"
                                        type="password"
                                        placeholder="Paste your Steadfast Secret Key..."
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-mono text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                    <p class="text-[11px] text-slate-400 mt-1">Leave empty to run in simulated parcel dispatch sandbox mode</p>
                                </div>
                            </div>
                        </div>

                        <!-- Pathao & Courier Fraud Check Section -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Pathao Store ID
                                </label>
                                <input
                                    v-model="settingsForm.pathao_store_id"
                                    type="text"
                                    placeholder="e.g. 12849"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs font-mono text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Optional Pathao Merchant Store identifier</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Fraud Check Endpoint URL
                                </label>
                                <input
                                    v-model="settingsForm.fraud_courier_api_endpoint"
                                    type="text"
                                    placeholder="https://api.steadfast.com.bd/v1/courier-check"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs font-mono text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Steadfast / FraudCheck BD lookup endpoint</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Fraud API Key / Token
                                </label>
                                <input
                                    v-model="settingsForm.fraud_courier_api_key"
                                    type="password"
                                    placeholder="Fraud check lookup token..."
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs font-mono text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Optional for external live return verification</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="settingsForm.processing"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all cursor-pointer"
                        >
                            <Loader2 v-if="settingsForm.processing" class="w-4 h-4 animate-spin" />
                            <Save v-else class="w-4 h-4" />
                            <span>Save Fraud Settings</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Advance Payment Confirmation Modal -->
        <div v-if="showAdvanceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl max-w-md w-full space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <CreditCard class="w-4 h-4" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Confirm Advance Delivery Fee</h3>
                    </div>
                    <button @click="showAdvanceModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitAdvanceConfirmation" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Method</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="m in ['bkash', 'nagad', 'rocket']"
                                :key="m"
                                type="button"
                                @click="advanceForm.method = m"
                                :class="[
                                    advanceForm.method === m
                                        ? 'bg-indigo-600 text-white font-bold'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300',
                                    'py-2 rounded-xl text-xs capitalize cursor-pointer transition-colors'
                                ]"
                            >
                                {{ m }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            bKash / Nagad Transaction ID (TrxID)
                        </label>
                        <input
                            v-model="advanceForm.transaction_id"
                            type="text"
                            required
                            placeholder="e.g. BKH8792X91"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs font-mono uppercase font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showAdvanceModal = false"
                            class="px-4 py-2 rounded-2xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="advanceForm.processing"
                            class="px-5 py-2 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 cursor-pointer"
                        >
                            Confirm & Approve Order
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Blacklist Entry Modal -->
        <div v-if="showAddEntryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl max-w-md w-full space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add Entry to Security Registry</h3>
                    <button @click="showAddEntryModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitAddEntry" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">List Type</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="entryForm.list_type = 'blacklist'"
                                :class="[
                                    entryForm.list_type === 'blacklist' ? 'bg-rose-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300',
                                    'py-2 rounded-xl text-xs cursor-pointer'
                                ]"
                            >
                                Blacklist (Block)
                            </button>
                            <button
                                type="button"
                                @click="entryForm.list_type = 'whitelist'"
                                :class="[
                                    entryForm.list_type === 'whitelist' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300',
                                    'py-2 rounded-xl text-xs cursor-pointer'
                                ]"
                            >
                                Whitelist (VIP Safe)
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Type</label>
                        <select
                            v-model="entryForm.type"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-900 dark:text-white"
                        >
                            <option value="phone">Phone Number</option>
                            <option value="email">Email Address</option>
                            <option value="ip">IP Address</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Value</label>
                        <input
                            v-model="entryForm.value"
                            type="text"
                            required
                            placeholder="e.g. 017XXXXXXXX or email@example.com"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs font-mono text-slate-900 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Reason / Note</label>
                        <input
                            v-model="entryForm.reason"
                            type="text"
                            placeholder="e.g. Refused Steadfast COD delivery twice"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-900 dark:text-white"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showAddEntryModal = false"
                            class="px-4 py-2 rounded-2xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="entryForm.processing"
                            class="px-5 py-2 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 cursor-pointer"
                        >
                            Save Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
