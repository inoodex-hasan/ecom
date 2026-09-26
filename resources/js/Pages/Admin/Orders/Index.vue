<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Search,
    Clock,
    Truck,
    PackageCheck,
    CheckCircle2,
    XCircle,
    Printer,
    Eye,
    CreditCard,
    ChevronDown,
    Check,
    RotateCcw,
    Layers,
    Package,
    Download
} from 'lucide-vue-next';
import CustomSelect from '@/Components/CustomSelect.vue';

const props = defineProps({
    orders: Object,
    statusCounts: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const activeStatus = ref(props.filters?.status || '');
const paymentStatus = ref(props.filters?.payment_status || '');

// Active popover menu states
const openStatusMenuId = ref(null);
const openPaymentMenuId = ref(null);

const statusTabConfigs = {
    all: {
        label: 'All Orders',
        icon: Layers,
        active: 'bg-slate-900 text-white dark:bg-white dark:text-slate-950 font-bold shadow-md shadow-slate-900/25 ring-2 ring-slate-400/40',
        inactive: 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:border-slate-400 dark:hover:border-slate-600',
        badgeActive: 'bg-slate-800 text-slate-100 dark:bg-slate-200 dark:text-slate-950',
        badgeInactive: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
        iconActive: 'text-slate-200 dark:text-slate-800',
        iconInactive: 'text-slate-400 dark:text-slate-500',
    },
    pending: {
        label: 'Pending',
        icon: Clock,
        active: 'bg-amber-500 text-white font-bold shadow-md shadow-amber-500/30 ring-2 ring-amber-300/40',
        inactive: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/30 hover:bg-amber-500/20',
        badgeActive: 'bg-amber-600/90 text-white',
        badgeInactive: 'bg-amber-500/20 text-amber-800 dark:text-amber-300',
        iconActive: 'text-amber-100',
        iconInactive: 'text-amber-500',
    },
    processing: {
        label: 'Processing',
        icon: PackageCheck,
        active: 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30 ring-2 ring-indigo-300/40',
        inactive: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/20',
        badgeActive: 'bg-indigo-700/90 text-white',
        badgeInactive: 'bg-indigo-500/20 text-indigo-800 dark:text-indigo-300',
        iconActive: 'text-indigo-100',
        iconInactive: 'text-indigo-500',
    },
    shipped: {
        label: 'Shipped',
        icon: Truck,
        active: 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30 ring-2 ring-blue-300/40',
        inactive: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/30 hover:bg-blue-500/20',
        badgeActive: 'bg-blue-700/90 text-white',
        badgeInactive: 'bg-blue-500/20 text-blue-800 dark:text-blue-300',
        iconActive: 'text-blue-100',
        iconInactive: 'text-blue-500',
    },
    delivered: {
        label: 'Delivered',
        icon: CheckCircle2,
        active: 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/30 ring-2 ring-emerald-300/40',
        inactive: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20',
        badgeActive: 'bg-emerald-700/90 text-white',
        badgeInactive: 'bg-emerald-500/20 text-emerald-800 dark:text-emerald-300',
        iconActive: 'text-emerald-100',
        iconInactive: 'text-emerald-500',
    },
    cancelled: {
        label: 'Cancelled',
        icon: XCircle,
        active: 'bg-rose-600 text-white font-bold shadow-md shadow-rose-600/30 ring-2 ring-rose-300/40',
        inactive: 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/30 hover:bg-rose-500/20',
        badgeActive: 'bg-rose-700/90 text-white',
        badgeInactive: 'bg-rose-500/20 text-rose-800 dark:text-rose-300',
        iconActive: 'text-rose-100',
        iconInactive: 'text-rose-500',
    },
};

const availableStatuses = [
    { key: 'pending', label: 'Pending', icon: Clock, color: 'text-amber-500', bg: 'bg-amber-500/10 border-amber-500/20' },
    { key: 'processing', label: 'Processing', icon: PackageCheck, color: 'text-indigo-500', bg: 'bg-indigo-500/10 border-indigo-500/20' },
    { key: 'shipped', label: 'Shipped', icon: Truck, color: 'text-blue-500', bg: 'bg-blue-500/10 border-blue-500/20' },
    { key: 'delivered', label: 'Delivered', icon: CheckCircle2, color: 'text-emerald-500', bg: 'bg-emerald-500/10 border-emerald-500/20' },
    { key: 'cancelled', label: 'Cancelled', icon: XCircle, color: 'text-rose-500', bg: 'bg-rose-500/10 border-rose-500/20' },
];

const availablePaymentStatuses = [
    { key: 'paid', label: 'Paid', icon: CheckCircle2, color: 'text-emerald-500', bg: 'bg-emerald-500/10' },
    { key: 'unpaid', label: 'Unpaid', icon: Clock, color: 'text-amber-500', bg: 'bg-amber-500/10' },
    { key: 'refunded', label: 'Refunded', icon: RotateCcw, color: 'text-purple-500', bg: 'bg-purple-500/10' },
];

function setStatusTab(statusKey) {
    activeStatus.value = statusKey === 'all' ? '' : statusKey;
    applyFilters();
}

function applyFilters() {
    router.get(
        route('admin.orders.index'),
        {
            search: search.value || undefined,
            status: activeStatus.value || undefined,
            payment_status: paymentStatus.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

let timeout;
watch(search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(applyFilters, 300);
});

function toggleStatusMenu(orderId) {
    openPaymentMenuId.value = null;
    openStatusMenuId.value = openStatusMenuId.value === orderId ? null : orderId;
}

function togglePaymentMenu(orderId) {
    openStatusMenuId.value = null;
    openPaymentMenuId.value = openPaymentMenuId.value === orderId ? null : orderId;
}

function closeMenus() {
    openStatusMenuId.value = null;
    openPaymentMenuId.value = null;
}

function selectStatus(order, newStatus) {
    openStatusMenuId.value = null;
    router.patch(route('admin.orders.update-status', order.id), {
        status: newStatus,
    }, { preserveScroll: true });
}

function selectPaymentStatus(order, newPaymentStatus) {
    openPaymentMenuId.value = null;
    router.patch(route('admin.orders.update-payment-status', order.id), {
        payment_status: newPaymentStatus,
    }, { preserveScroll: true });
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(val || 0);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

function getStatusBadge(status) {
    switch (status) {
        case 'delivered':
            return { label: 'Delivered', bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/20', dot: 'bg-emerald-500', icon: CheckCircle2 };
        case 'shipped':
            return { label: 'Shipped', bg: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20 hover:bg-blue-500/20', dot: 'bg-blue-500', icon: Truck };
        case 'processing':
            return { label: 'Processing', bg: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20 hover:bg-indigo-500/20', dot: 'bg-indigo-500 animate-pulse', icon: PackageCheck };
        case 'pending':
            return { label: 'Pending', bg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20 hover:bg-amber-500/20', dot: 'bg-amber-500', icon: Clock };
        case 'cancelled':
            return { label: 'Cancelled', bg: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20 hover:bg-rose-500/20', dot: 'bg-rose-500', icon: XCircle };
        default:
            return { label: status, bg: 'bg-slate-500/10 text-slate-400 border-slate-500/20', dot: 'bg-slate-400', icon: Clock };
    }
}

function getPaymentBadge(status) {
    switch (status) {
        case 'paid':
            return { label: 'Paid', bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/20' };
        case 'unpaid':
            return { label: 'Unpaid', bg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20 hover:bg-amber-500/20' };
        case 'refunded':
            return { label: 'Refunded', bg: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20 hover:bg-purple-500/20' };
        default:
            return { label: status, bg: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20 hover:bg-rose-500/20' };
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Orders Management - Admin" />

        <template #header>
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Orders & Fulfillment</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Track shipments, process invoices, and manage customer payments</p>
            </div>
        </template>

        <div class="space-y-6" @click="closeMenus">
            <!-- Status Filter Tabs with Vibrant Color Coding -->
            <div class="flex items-center gap-2.5 overflow-x-auto pb-1 scrollbar-none" @click.stop>
                <button
                    v-for="(count, key) in statusCounts"
                    :key="key"
                    @click="setStatusTab(key)"
                    :class="[
                        'px-4 py-2.5 rounded-2xl text-xs flex items-center gap-2.5 capitalize whitespace-nowrap transition-all duration-200 cursor-pointer shadow-xs select-none',
                        (activeStatus === key || (key === 'all' && !activeStatus))
                            ? (statusTabConfigs[key]?.active || 'bg-indigo-600 text-white font-bold shadow-md')
                            : (statusTabConfigs[key]?.inactive || 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300')
                    ]"
                >
                    <component
                        :is="statusTabConfigs[key]?.icon || Package"
                        :class="[
                            'w-4 h-4 shrink-0 transition-colors',
                            (activeStatus === key || (key === 'all' && !activeStatus))
                                ? (statusTabConfigs[key]?.iconActive || 'text-white')
                                : (statusTabConfigs[key]?.iconInactive || 'text-slate-400')
                        ]"
                    />
                    <span class="font-semibold">{{ statusTabConfigs[key]?.label || key }}</span>
                    <span
                        :class="[
                            'px-2 py-0.5 rounded-full text-[11px] font-mono font-bold transition-colors',
                            (activeStatus === key || (key === 'all' && !activeStatus))
                                ? (statusTabConfigs[key]?.badgeActive || 'bg-white/20 text-white')
                                : (statusTabConfigs[key]?.badgeInactive || 'bg-slate-100 dark:bg-slate-800 text-slate-600')
                        ]"
                    >
                        {{ count }}
                    </span>
                </button>
            </div>

            <!-- Search & Filter Controls with Export -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col md:flex-row gap-3 items-center justify-between" @click.stop>
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-1">
                    <div class="relative w-full sm:w-80 md:w-96">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search order number or customer name/email..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="flex items-center gap-2.5 w-full sm:w-56">
                        <CustomSelect
                            v-model="paymentStatus"
                            @change="applyFilters"
                            :options="[
                                { value: '', label: 'All Payment Statuses' },
                                { value: 'paid', label: 'Paid', icon: CheckCircle2 },
                                { value: 'unpaid', label: 'Unpaid', icon: Clock },
                                { value: 'refunded', label: 'Refunded', icon: RotateCcw },
                            ]"
                            placeholder="All Payment Statuses"
                            compact
                        />
                    </div>
                </div>

                <!-- Export Actions -->
                <div class="flex items-center gap-2 w-full md:w-auto justify-end shrink-0">
                    <a
                        :href="route('admin.orders.export', { format: 'xlsx', status: activeStatus || undefined, search: search || undefined, payment_status: paymentStatus || undefined })"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors shrink-0"
                        title="Export Orders as Excel"
                    >
                        <Download class="w-3.5 h-3.5" />
                        <span>Excel</span>
                    </a>
                    <a
                        :href="route('admin.orders.export', { format: 'csv', status: activeStatus || undefined, search: search || undefined, payment_status: paymentStatus || undefined })"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-xs transition-colors shrink-0"
                        title="Export Orders as CSV"
                    >
                        <Download class="w-3.5 h-3.5" />
                        <span>CSV</span>
                    </a>
                </div>
            </div>

            <!-- Orders Table Container -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-visible">
                <div class="overflow-x-auto min-h-[380px] pb-28">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-xs uppercase text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="px-6 py-4">Order ID</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Customer</th>
                                <th class="px-6 py-4">Payment</th>
                                <th class="px-6 py-4">Fulfillment Status</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr
                                v-for="(order, idx) in orders.data"
                                :key="order.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors"
                            >
                                <!-- Order Number -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Link :href="route('admin.orders.show', order.id)" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ order.order_number }}
                                    </Link>
                                    <span class="block text-[11px] text-slate-400 mt-0.5">{{ order.items_count }} items</span>
                                </td>

                                <!-- Date -->
                                <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ formatDate(order.created_at) }}
                                </td>

                                <!-- Customer Info -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="order.customer?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(order.customer?.name || 'User')}&background=random`"
                                            alt="Customer"
                                            class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700 shrink-0"
                                        />
                                        <div class="truncate max-w-[150px]">
                                            <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ order.customer?.name }}</p>
                                            <p class="text-[11px] text-slate-400 truncate">{{ order.customer?.email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Payment Status Custom Dropdown with Smart Positioning -->
                                <td class="px-6 py-4 whitespace-nowrap relative">
                                    <div class="relative inline-block text-left" @click.stop>
                                        <button
                                            type="button"
                                            @click="togglePaymentMenu(order.id)"
                                            :class="[
                                                getPaymentBadge(order.payment_status).bg,
                                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold border transition-all cursor-pointer'
                                            ]"
                                        >
                                            <span class="capitalize">{{ order.payment_status }}</span>
                                            <ChevronDown class="w-3 h-3 opacity-60" />
                                        </button>

                                        <!-- Custom Payment Dropdown Popover (Smart Top/Bottom alignment) -->
                                        <div
                                            v-if="openPaymentMenuId === order.id"
                                            :class="[
                                                idx >= orders.data.length - 2 && orders.data.length > 2
                                                    ? 'bottom-full mb-2 origin-bottom-left'
                                                    : 'top-full mt-2 origin-top-left',
                                                'absolute left-0 w-40 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl z-50 p-1.5 space-y-1 animate-in fade-in zoom-in-95 duration-150'
                                            ]"
                                        >
                                            <button
                                                v-for="pStat in availablePaymentStatuses"
                                                :key="pStat.key"
                                                @click="selectPaymentStatus(order, pStat.key)"
                                                :class="[
                                                    order.payment_status === pStat.key ? 'bg-slate-100 dark:bg-slate-800 font-bold' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60',
                                                    'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs text-slate-800 dark:text-slate-200 transition-colors cursor-pointer'
                                                ]"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <component :is="pStat.icon" :class="[pStat.color, 'w-3.5 h-3.5']" />
                                                    <span>{{ pStat.label }}</span>
                                                </div>
                                                <Check v-if="order.payment_status === pStat.key" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                                            </button>
                                        </div>
                                    </div>
                                </td>

                                <!-- Fulfillment Status Custom Dropdown with Smart Positioning -->
                                <td class="px-6 py-4 whitespace-nowrap relative">
                                    <div class="relative inline-block text-left" @click.stop>
                                        <button
                                            type="button"
                                            @click="toggleStatusMenu(order.id)"
                                            :class="[
                                                getStatusBadge(order.status).bg,
                                                'inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all cursor-pointer'
                                            ]"
                                        >
                                            <span class="w-2 h-2 rounded-full" :class="getStatusBadge(order.status).dot"></span>
                                            <span>{{ getStatusBadge(order.status).label }}</span>
                                            <ChevronDown class="w-3.5 h-3.5 opacity-60 ml-0.5" />
                                        </button>

                                        <!-- Custom Interactive Status Popover Menu (Smart Top/Bottom alignment) -->
                                        <div
                                            v-if="openStatusMenuId === order.id"
                                            :class="[
                                                idx >= orders.data.length - 2 && orders.data.length > 2
                                                    ? 'bottom-full mb-2 origin-bottom-left'
                                                    : 'top-full mt-2 origin-top-left',
                                                'absolute left-0 w-48 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl z-50 p-1.5 space-y-1 animate-in fade-in zoom-in-95 duration-150'
                                            ]"
                                        >
                                            <div class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                Update Fulfillment
                                            </div>
                                            <button
                                                v-for="stat in availableStatuses"
                                                :key="stat.key"
                                                @click="selectStatus(order, stat.key)"
                                                :class="[
                                                    order.status === stat.key
                                                        ? 'bg-slate-100 dark:bg-slate-800 font-bold'
                                                        : 'hover:bg-slate-50 dark:hover:bg-slate-800/60',
                                                    'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs text-slate-800 dark:text-slate-200 transition-colors cursor-pointer'
                                                ]"
                                            >
                                                <div class="flex items-center gap-2.5">
                                                    <component :is="stat.icon" :class="[stat.color, 'w-4 h-4']" />
                                                    <span>{{ stat.label }}</span>
                                                </div>
                                                <Check v-if="order.status === stat.key" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                                            </button>
                                        </div>
                                    </div>
                                </td>

                                <!-- Total -->
                                <td class="px-6 py-4 font-bold text-xs text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ formatCurrency(order.total) }}
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link
                                            :href="route('admin.orders.show', order.id)"
                                            class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition-colors"
                                        >
                                            Details
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="orders.links?.length > 3" class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} results
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in orders.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white font-bold'
                                    : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100',
                                !link.url ? 'opacity-50 cursor-not-allowed' : '',
                                'px-3 py-1.5 rounded-lg text-xs'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
