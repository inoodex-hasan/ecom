<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import {
    Ticket,
    Plus,
    Search,
    Filter,
    Percent,
    DollarSign,
    Truck,
    Calendar,
    Clock,
    Users,
    Check,
    Copy,
    Edit3,
    Trash2,
    X,
    Sparkles,
    AlertCircle,
    CheckCircle2,
    RefreshCw,
    SlidersHorizontal,
    Tag,
    Layers,
    ChevronDown,
    ArrowUpRight
} from 'lucide-vue-next';

const props = defineProps({
    coupons: Array,
    metrics: Object,
    categories: Array,
    filters: Object,
});

// Filters State
const searchQuery = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || '');
const selectedStatus = ref(props.filters?.status || '');

const typeOptions = [
    { label: 'All Discount Types', value: '' },
    { label: 'Percentage (% OFF)', value: 'percentage' },
    { label: 'Fixed Cart ($ OFF)', value: 'fixed_cart' },
    { label: 'Free Shipping', value: 'free_shipping' },
];

const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Scheduled', value: 'upcoming' },
    { label: 'Expired', value: 'expired' },
    { label: 'Disabled', value: 'disabled' },
];

function applyFilters() {
    router.get(
        route('admin.coupons.index'),
        {
            search: searchQuery.value || undefined,
            type: selectedType.value || undefined,
            status: selectedStatus.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

function resetFilters() {
    searchQuery.value = '';
    selectedType.value = '';
    selectedStatus.value = '';
    applyFilters();
}

// Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const couponToEdit = ref(null);
const copiedCode = ref(null);

const form = useForm({
    code: '',
    description: '',
    type: 'percentage',
    value: 15,
    min_spend: '',
    max_discount: '',
    usage_limit_total: '',
    usage_limit_per_customer: 1,
    is_active: true,
    starts_at: '',
    expires_at: '',
    applicable_categories: [],
});

function generateRandomCode() {
    const prefixes = ['SAVE', 'DEAL', 'VIP', 'FLASH', 'SPECIAL', 'EXTRA'];
    const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
    const randomChars = Math.random().toString(36).substring(2, 6).toUpperCase();
    form.code = `${prefix}-${randomChars}`;
}

function openCreateModal() {
    isEditing.value = false;
    couponToEdit.value = null;
    form.reset();
    form.type = 'percentage';
    form.value = 15;
    form.usage_limit_per_customer = 1;
    form.is_active = true;
    form.applicable_categories = [];
    generateRandomCode();
    isModalOpen.value = true;
}

function openEditModal(coupon) {
    isEditing.value = true;
    couponToEdit.value = coupon;
    form.code = coupon.code;
    form.description = coupon.description || '';
    form.type = coupon.type;
    form.value = coupon.value;
    form.min_spend = coupon.min_spend || '';
    form.max_discount = coupon.max_discount || '';
    form.usage_limit_total = coupon.usage_limit_total || '';
    form.usage_limit_per_customer = coupon.usage_limit_per_customer || 1;
    form.is_active = !!coupon.is_active;
    form.starts_at = coupon.starts_at ? coupon.starts_at.substring(0, 16) : '';
    form.expires_at = coupon.expires_at ? coupon.expires_at.substring(0, 16) : '';
    form.applicable_categories = Array.isArray(coupon.applicable_categories) ? coupon.applicable_categories : [];
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    form.reset();
}

function submitForm() {
    if (isEditing.value && couponToEdit.value) {
        form.put(route('admin.coupons.update', couponToEdit.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.coupons.store'), {
            onSuccess: () => closeModal(),
        });
    }
}

function toggleStatus(coupon) {
    router.patch(route('admin.coupons.toggle-status', coupon.id), {}, { preserveScroll: true });
}

function deleteCoupon(coupon) {
    if (confirm(`Are you sure you want to permanently delete coupon '${coupon.code}'?`)) {
        router.delete(route('admin.coupons.destroy', coupon.id), { preserveScroll: true });
    }
}

function copyToClipboard(code) {
    navigator.clipboard.writeText(code).then(() => {
        copiedCode.value = code;
        setTimeout(() => {
            copiedCode.value = null;
        }, 2000);
    });
}

function getTypeBadge(type) {
    switch (type) {
        case 'percentage':
            return { label: 'Percentage', icon: Percent, color: 'text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800' };
        case 'fixed_cart':
            return { label: 'Fixed Cart', icon: DollarSign, color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' };
        case 'free_shipping':
            return { label: 'Free Shipping', icon: Truck, color: 'text-cyan-600 bg-cyan-50 dark:bg-cyan-950/40 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800' };
        default:
            return { label: type, icon: Ticket, color: 'text-slate-600 bg-slate-100 dark:bg-slate-800 border-slate-200' };
    }
}

function getStatusBadge(status) {
    switch (status) {
        case 'active':
            return { label: 'Active', class: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' };
        case 'upcoming':
            return { label: 'Scheduled', class: 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800' };
        case 'expired':
            return { label: 'Expired', class: 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800' };
        case 'depleted':
            return { label: 'Depleted', class: 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800' };
        case 'disabled':
        default:
            return { label: 'Disabled', class: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700' };
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Coupons & Promo Codes - Admin" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                        Coupons & Promo Codes
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Create discount codes, set minimum cart rules, limits, and schedule marketing campaigns.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6 pb-12">
            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Vouchers</span>
                        <div class="w-7 h-7 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <Ticket class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white">
                        {{ metrics?.total_coupons || 0 }}
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium">All campaign vouchers</span>
                </div>

                <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Active Now</span>
                        <div class="w-7 h-7 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <CheckCircle2 class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                        {{ metrics?.active_coupons || 0 }}
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium">Currently claimable</span>
                </div>

                <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Redemptions</span>
                        <div class="w-7 h-7 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <Users class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400">
                        {{ metrics?.total_redemptions || 0 }}
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium">Orders with discounts</span>
                </div>

                <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Savings Given</span>
                        <div class="w-7 h-7 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <DollarSign class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div class="text-2xl font-black text-purple-600 dark:text-purple-400">
                        ৳{{ Number(metrics?.total_discount_claimed || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium">Customer savings</span>
                </div>
            </div>

            <!-- Toolbar & Filter Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchQuery"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search by coupon code or title..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Type Filter -->
                    <div class="w-48">
                        <CustomSelect
                            v-model="selectedType"
                            :options="typeOptions"
                            placeholder="Discount Type"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div class="w-36">
                        <CustomSelect
                            v-model="selectedStatus"
                            :options="statusOptions"
                            placeholder="Status"
                            @change="applyFilters"
                        />
                    </div>

                    <button
                        v-if="searchQuery || selectedType || selectedStatus"
                        @click="resetFilters"
                        class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer"
                    >
                        <X class="w-3.5 h-3.5" /> Reset
                    </button>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end shrink-0">
                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all cursor-pointer active:scale-95 shrink-0"
                    >
                        <Plus class="w-4 h-4" /> Create Coupon
                    </button>
                </div>
            </div>

            <!-- Coupons Grid / Cards -->
            <div v-if="coupons.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                    v-for="coupon in coupons"
                    :key="coupon.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between relative group overflow-hidden"
                >
                    <!-- Ticket Notch Visuals -->
                    <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800"></div>
                    <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800"></div>

                    <!-- Top Card: Code & Actions -->
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <!-- Code Badge -->
                            <div class="flex items-center gap-2">
                                <div class="px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-dashed border-indigo-300 dark:border-indigo-700 flex items-center gap-2">
                                    <span class="font-mono font-black text-sm tracking-wider text-indigo-700 dark:text-indigo-300">
                                        {{ coupon.code }}
                                    </span>
                                    <button
                                        @click="copyToClipboard(coupon.code)"
                                        type="button"
                                        class="p-1 rounded-lg text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-200 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition-colors"
                                        title="Copy Coupon Code"
                                    >
                                        <Check v-if="copiedCode === coupon.code" class="w-3.5 h-3.5 text-emerald-600" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <span :class="[getStatusBadge(coupon.status).class, 'px-2 py-0.5 rounded-lg text-[10px] font-bold border capitalize']">
                                {{ getStatusBadge(coupon.status).label }}
                            </span>
                        </div>

                        <!-- Discount Highlight -->
                        <div class="my-2">
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                    {{ coupon.formatted_discount }}
                                </span>
                                <span
                                    :class="[
                                        getTypeBadge(coupon.type).color,
                                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold border'
                                    ]"
                                >
                                    <component :is="getTypeBadge(coupon.type).icon" class="w-3 h-3" />
                                    {{ getTypeBadge(coupon.type).label }}
                                </span>
                            </div>

                            <p v-if="coupon.description" class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                                {{ coupon.description }}
                            </p>
                        </div>

                        <!-- Rule Constraints List -->
                        <div class="space-y-1.5 py-3 border-y border-slate-100 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-300">
                            <div v-if="coupon.min_spend" class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Min Order Spend:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">৳{{ Number(coupon.min_spend).toFixed(2) }}</span>
                            </div>
                            <div v-if="coupon.max_discount && coupon.type === 'percentage'" class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Max Discount Cap:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">৳{{ Number(coupon.max_discount).toFixed(2) }}</span>
                            </div>
                            <div v-if="coupon.usage_limit_per_customer" class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Limit per customer:</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ coupon.usage_limit_per_customer }} use(s)</span>
                            </div>
                            <div v-if="coupon.starts_at || coupon.expires_at" class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Valid:</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">
                                    {{ coupon.starts_at ? new Date(coupon.starts_at).toLocaleDateString() : 'Now' }} -
                                    {{ coupon.expires_at ? new Date(coupon.expires_at).toLocaleDateString() : 'No expiry' }}
                                </span>
                            </div>
                        </div>

                        <!-- Usage Progress Bar -->
                        <div class="mt-3">
                            <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 mb-1">
                                <span>Usage limit</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ coupon.total_used }} / {{ coupon.usage_limit_total ? coupon.usage_limit_total : '∞ Unlimited' }}
                                </span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div
                                    class="h-full bg-indigo-600 rounded-full transition-all duration-300"
                                    :style="{
                                        width: coupon.usage_limit_total
                                            ? Math.min(100, Math.round((coupon.total_used / coupon.usage_limit_total) * 100)) + '%'
                                            : (coupon.total_used > 0 ? '100%' : '0%')
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Bar: Toggle & Actions -->
                    <div class="flex items-center justify-between pt-4 mt-3 border-t border-slate-100 dark:border-slate-800">
                        <!-- Toggle Switch -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="toggleStatus(coupon)"
                                type="button"
                                :class="[
                                    coupon.is_active ? 'bg-indigo-600' : 'bg-slate-300 dark:bg-slate-700',
                                    'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
                                ]"
                            >
                                <span
                                    :class="[
                                        coupon.is_active ? 'translate-x-4' : 'translate-x-0',
                                        'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                    ]"
                                />
                            </button>
                            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                {{ coupon.is_active ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1">
                            <button
                                @click="openEditModal(coupon)"
                                class="p-1.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors"
                                title="Edit Coupon"
                            >
                                <Edit3 class="w-4 h-4" />
                            </button>
                            <button
                                @click="deleteCoupon(coupon)"
                                class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                                title="Delete Coupon"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-12 text-center shadow-xs"
            >
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center mb-4">
                    <Ticket class="w-7 h-7" />
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">No Coupons Found</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1 mb-6">
                    {{ searchQuery || selectedType || selectedStatus ? 'No coupons matched your filters. Try resetting your search.' : 'Create your first promotional discount voucher to boost customer checkout conversions.' }}
                </p>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition-all cursor-pointer shadow-md shadow-indigo-600/20"
                >
                    <Plus class="w-4 h-4" /> Create Coupon
                </button>
            </div>
        </div>

        <!-- Create / Edit Coupon Modal -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto"
        >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl my-6 flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <Ticket class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">
                                {{ isEditing ? 'Edit Coupon' : 'Create New Coupon' }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Configure discount rates, cart criteria, and limits.
                            </p>
                        </div>
                    </div>
                    <button @click="closeModal" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Form Content -->
                <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-6 space-y-5">
                    
                    <!-- Code & Auto-generator -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Coupon Code *
                            </label>
                            <button
                                @click="generateRandomCode"
                                type="button"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                            >
                                <RefreshCw class="w-3 h-3" /> Auto-generate
                            </button>
                        </div>
                        <input
                            v-model="form.code"
                            type="text"
                            required
                            maxlength="50"
                            placeholder="e.g. SUMMER20 or FLASH50"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-mono font-bold uppercase tracking-wider text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.code" class="text-xs text-rose-500 mt-1">{{ form.errors.code }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Campaign Description
                        </label>
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="e.g. 20% off for new summer season drop"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Discount Type Radio Selector -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                            Discount Type *
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <button
                                type="button"
                                @click="form.type = 'percentage'"
                                :class="[
                                    form.type === 'percentage'
                                        ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-bold ring-2 ring-indigo-500/20'
                                        : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 hover:bg-slate-50',
                                    'p-3 rounded-2xl border text-left transition-all cursor-pointer flex flex-col justify-between'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <Percent class="w-4 h-4 text-indigo-600" />
                                    <span v-if="form.type === 'percentage'" class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                </div>
                                <span class="text-xs font-bold">Percentage</span>
                                <span class="text-[10px] text-slate-500 mt-0.5">e.g. 20% off total</span>
                            </button>

                            <button
                                type="button"
                                @click="form.type = 'fixed_cart'"
                                :class="[
                                    form.type === 'fixed_cart'
                                        ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-bold ring-2 ring-emerald-500/20'
                                        : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 hover:bg-slate-50',
                                    'p-3 rounded-2xl border text-left transition-all cursor-pointer flex flex-col justify-between'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <DollarSign class="w-4 h-4 text-emerald-600" />
                                    <span v-if="form.type === 'fixed_cart'" class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                </div>
                                <span class="text-xs font-bold">Fixed Cart</span>
                                <span class="text-[10px] text-slate-500 mt-0.5">e.g. ৳150 flat discount</span>
                            </button>

                            <button
                                type="button"
                                @click="form.type = 'free_shipping'"
                                :class="[
                                    form.type === 'free_shipping'
                                        ? 'border-cyan-600 bg-cyan-50 dark:bg-cyan-950/40 text-cyan-700 dark:text-cyan-300 font-bold ring-2 ring-cyan-500/20'
                                        : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 hover:bg-slate-50',
                                    'p-3 rounded-2xl border text-left transition-all cursor-pointer flex flex-col justify-between'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <Truck class="w-4 h-4 text-cyan-600" />
                                    <span v-if="form.type === 'free_shipping'" class="w-2 h-2 rounded-full bg-cyan-600"></span>
                                </div>
                                <span class="text-xs font-bold">Free Shipping</span>
                                <span class="text-[10px] text-slate-500 mt-0.5">Waives shipping charges</span>
                            </button>
                        </div>
                    </div>

                    <!-- Discount Value & Maximum Cap -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-if="form.type !== 'free_shipping'">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ form.type === 'percentage' ? 'Discount Percentage (%) *' : 'Discount Amount (৳) *' }}
                            </label>
                            <input
                                v-model="form.value"
                                type="number"
                                step="0.01"
                                min="0"
                                :max="form.type === 'percentage' ? 100 : undefined"
                                required
                                placeholder="0.00"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div v-if="form.type === 'percentage'">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Max Discount Cap (৳)
                            </label>
                            <input
                                v-model="form.max_discount"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="Optional limit, e.g. 50.00"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                            />
                            <p class="text-[10px] text-slate-400 mt-0.5">Limits maximum amount saved.</p>
                        </div>
                    </div>

                    <!-- Spend & Limits Card -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-750 space-y-3">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                            <SlidersHorizontal class="w-3.5 h-3.5 text-indigo-500" /> Cart & Usage Rules
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                    Min Order Spend (৳)
                                </label>
                                <input
                                    v-model="form.min_spend"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="e.g. 50.00"
                                    class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                    Total Usage Limit
                                </label>
                                <input
                                    v-model="form.usage_limit_total"
                                    type="number"
                                    min="1"
                                    placeholder="Blank = Unlimited"
                                    class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                    Limit Per Customer
                                </label>
                                <input
                                    v-model="form.usage_limit_per_customer"
                                    type="number"
                                    min="1"
                                    placeholder="Default: 1"
                                    class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Window -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Start Date & Time
                            </label>
                            <input
                                v-model="form.starts_at"
                                type="datetime-local"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Expiration Date & Time
                            </label>
                            <input
                                v-model="form.expires_at"
                                type="datetime-local"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Status Active Toggle -->
                    <div class="flex items-center gap-2.5 pt-2">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            id="is_active_checkbox"
                            class="w-4 h-4 rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label for="is_active_checkbox" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            Activate coupon immediately for checkout
                        </label>
                    </div>
                </form>

                <!-- Modal Footer -->
                <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-end gap-3">
                    <button
                        @click="closeModal"
                        type="button"
                        class="px-4 py-2.5 rounded-2xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        @click="submitForm"
                        :disabled="form.processing"
                        type="button"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all cursor-pointer disabled:opacity-50"
                    >
                        <Sparkles class="w-3.5 h-3.5" />
                        <span>{{ isEditing ? 'Update Coupon' : 'Create Coupon' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
