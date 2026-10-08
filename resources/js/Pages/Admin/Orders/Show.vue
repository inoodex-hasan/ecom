<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ArrowLeft,
    Printer,
    CheckCircle2,
    Truck,
    PackageCheck,
    Package,
    Clock,
    XCircle,
    User,
    Mail,
    Phone,
    MapPin,
    CreditCard,
    DollarSign,
    ShieldCheck,
    ShieldAlert,
    AlertTriangle,
    RotateCcw,
    RefreshCw,
    Ban,
    ExternalLink,
    Send,
    Check,
    Loader2
} from 'lucide-vue-next';
import CustomSelect from '@/Components/CustomSelect.vue';

const props = defineProps({
    order: Object,
});

const showAdvanceModal = ref(false);
const advanceForm = ref({
    advance_payment_method: 'bKash',
    advance_transaction_id: '',
});
const isProcessing = ref(false);

// Courier Dispatch State
const selectedCourier = ref(props.order.courier_provider || 'steadfast');
const courierNote = ref('');
const isDispatching = ref(false);
const isSyncingCourier = ref(false);

function triggerCourierDispatch() {
    if (!confirm(`Book and dispatch Order #${props.order.order_number} with ${selectedCourier.value === 'pathao' ? 'Pathao' : 'Steadfast'} Courier?`)) {
        return;
    }
    isDispatching.value = true;
    router.post(route('admin.orders.courier-dispatch', props.order.id), {
        provider: selectedCourier.value,
        note: courierNote.value || undefined,
    }, {
        preserveScroll: true,
        onFinish: () => isDispatching.value = false,
    });
}

function triggerCourierSync() {
    isSyncingCourier.value = true;
    router.post(route('admin.orders.courier-sync', props.order.id), {}, {
        preserveScroll: true,
        onFinish: () => isSyncingCourier.value = false,
    });
}

function triggerRecheck() {
    isProcessing.value = true;
    router.post(route('admin.fraud.recheck', props.order.id), {}, {
        preserveScroll: true,
        onFinish: () => isProcessing.value = false,
    });
}

function triggerVerify() {
    if (!confirm('Mark this order as safely verified and clear any risk flags?')) return;
    router.post(route('admin.fraud.verify', props.order.id), {}, {
        preserveScroll: true,
    });
}

function triggerRequestAdvance(amount) {
    router.post(route('admin.fraud.request-advance', props.order.id), {
        amount: amount,
    }, {
        preserveScroll: true,
    });
}

function submitConfirmAdvance() {
    if (!advanceForm.value.advance_transaction_id) {
        alert('Please enter the bKash/Nagad Transaction ID (TrxID)');
        return;
    }
    router.post(route('admin.fraud.confirm-advance', props.order.id), advanceForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showAdvanceModal.value = false;
            advanceForm.value.advance_transaction_id = '';
        }
    });
}

function triggerBlockOrder() {
    const reason = prompt('Reason for blocking this order & blacklisting customer phone:');
    if (reason === null) return;
    router.post(route('admin.fraud.block', props.order.id), {
        reason: reason || 'Suspicious COD / High Fraud Risk',
    }, {
        preserveScroll: true,
    });
}

const fulfillmentStatusOptions = [
    { value: 'pending', label: 'Pending', icon: Clock, sublabel: 'Awaiting fulfillment' },
    { value: 'processing', label: 'Processing', icon: PackageCheck, sublabel: 'Order in progress' },
    { value: 'shipped', label: 'Shipped', icon: Truck, sublabel: 'Out for delivery' },
    { value: 'delivered', label: 'Delivered', icon: CheckCircle2, sublabel: 'Order delivered' },
    { value: 'cancelled', label: 'Cancelled', icon: XCircle, sublabel: 'Order cancelled' },
];

const paymentStatusOptions = [
    { value: 'paid', label: 'Paid', icon: CheckCircle2, sublabel: 'Payment captured' },
    { value: 'unpaid', label: 'Unpaid', icon: Clock, sublabel: 'Pending settlement' },
    { value: 'refunded', label: 'Refunded', icon: RotateCcw, sublabel: 'Refund issued' },
];

function updateStatus(newStatus) {
    if (!newStatus || newStatus === props.order.status) return;
    router.patch(route('admin.orders.update-status', props.order.id), {
        status: newStatus,
    });
}

function updatePayment(newPaymentStatus) {
    if (!newPaymentStatus || newPaymentStatus === props.order.payment_status) return;
    router.patch(route('admin.orders.update-payment-status', props.order.id), {
        payment_status: newPaymentStatus,
    });
}

const formatCurrency = (val) => {
    return '৳' + Number(val || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
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
            return { label: 'Delivered', bg: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800', icon: CheckCircle2 };
        case 'shipped':
            return { label: 'Shipped', bg: 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border-blue-200 dark:border-blue-800', icon: Truck };
        case 'processing':
            return { label: 'Processing', bg: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800', icon: PackageCheck };
        case 'pending':
            return { label: 'Pending', bg: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border-amber-200 dark:border-amber-800', icon: Clock };
        case 'cancelled':
            return { label: 'Cancelled', bg: 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border-rose-200 dark:border-rose-800', icon: XCircle };
        default:
            return { label: status, bg: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300', icon: Clock };
    }
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Order ${order.order_number} - Details`" />

        <div class="space-y-6">
            <!-- Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.orders.index')"
                        class="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100"
                    >
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ order.order_number }}</h1>
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                                :class="getStatusBadge(order.status).bg"
                            >
                                <component :is="getStatusBadge(order.status).icon" class="w-3.5 h-3.5" />
                                {{ getStatusBadge(order.status).label }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Placed on {{ formatDate(order.created_at) }}</p>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center gap-3">
                    <Link
                        v-if="order.courier_consignment_id"
                        :href="route('admin.orders.courier-label', order.id)"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 transition-colors shadow-xs"
                    >
                        <Truck class="w-4 h-4 text-indigo-600 dark:text-indigo-400" /> Courier Label
                    </Link>

                    <Link
                        :href="route('admin.orders.invoice', order.id)"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-xs transition-colors"
                    >
                        <Printer class="w-4 h-4 text-slate-500" /> Print Invoice
                    </Link>
                </div>
            </div>

            <!-- Quick Status Management Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <PackageCheck class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Manage Order Status</h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Select new fulfillment or payment state to automatically update order</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <!-- Fulfillment Status Dropdown -->
                    <div class="w-full sm:w-60">
                        <CustomSelect
                            :model-value="order.status"
                            @update:model-value="updateStatus"
                            :options="fulfillmentStatusOptions"
                            placeholder="Change Status"
                        />
                    </div>

                    <!-- Payment Status Dropdown -->
                    <div class="w-full sm:w-52">
                        <CustomSelect
                            :model-value="order.payment_status"
                            @update:model-value="updatePayment"
                            :options="paymentStatusOptions"
                            placeholder="Payment Status"
                        />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                <!-- 1. Ordered Products & Financial Summary (Row 1, Left) -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                    <PackageCheck class="w-4 h-4" />
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Ordered Products ({{ order.items?.length }})</h3>
                            </div>
                            <span class="text-xs font-semibold text-slate-400">Total Units: {{ order.items?.reduce((acc, i) => acc + i.quantity, 0) }}</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-xs uppercase text-slate-500 font-semibold border-b border-slate-100 dark:border-slate-800">
                                    <tr>
                                        <th class="px-6 py-3.5">Product</th>
                                        <th class="px-6 py-3.5">Unit Price</th>
                                        <th class="px-6 py-3.5">Qty</th>
                                        <th class="px-6 py-3.5 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                    <tr v-for="item in order.items" :key="item.id">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <img
                                                    v-if="item.image"
                                                    :src="item.image"
                                                    alt="Item"
                                                    class="w-12 h-12 rounded-xl object-cover bg-slate-100 dark:bg-slate-800 shrink-0"
                                                />
                                                <div
                                                    v-else
                                                    class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shrink-0 flex items-center justify-center text-slate-400"
                                                >
                                                    <Package class="w-6 h-6" />
                                                </div>
                                                <div>
                                                    <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ item.product_name }}</p>
                                                    <p class="text-[11px] font-mono text-slate-400">{{ item.sku }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            {{ formatCurrency(item.unit_price) }}
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-slate-900 dark:text-white">
                                            × {{ item.quantity }}
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-slate-900 dark:text-white text-right">
                                            {{ formatCurrency(item.total) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Customer Notes (if present) -->
                    <div v-if="order.notes" class="px-6 py-3 bg-amber-50/50 dark:bg-amber-950/20 border-t border-amber-200/50 dark:border-amber-900/30 text-xs">
                        <span class="font-bold text-amber-900 dark:text-amber-300">Order Note:</span>
                        <span class="text-amber-800 dark:text-amber-400 ml-1.5 italic">{{ order.notes }}</span>
                    </div>

                    <!-- Financial Summary -->
                    <div class="p-6 bg-slate-50/50 dark:bg-slate-800/20 border-t border-slate-100 dark:border-slate-800">
                        <div class="max-w-xs ml-auto space-y-2 text-xs">
                            <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                <span>Subtotal</span>
                                <span>{{ formatCurrency(order.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                <span>Estimated Tax</span>
                                <span>{{ formatCurrency(order.tax) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                <span>Shipping</span>
                                <span>{{ formatCurrency(order.shipping_cost) }}</span>
                            </div>
                            <div v-if="order.discount > 0" class="flex justify-between text-emerald-600">
                                <span>Discount</span>
                                <span>- {{ formatCurrency(order.discount) }}</span>
                            </div>
                            <div class="pt-3 border-t border-slate-200 dark:border-slate-700 flex justify-between text-sm font-bold text-slate-900 dark:text-white">
                                <span>Grand Total</span>
                                <span class="text-indigo-600 dark:text-indigo-400">{{ formatCurrency(order.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Customer, Delivery & Payment Hub (Row 1, Right) -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-5">
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                <User class="w-4 h-4" />
                            </div>
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Customer & Destination</h3>
                                <p class="text-[10px] text-slate-400">Customer profile, delivery address & payment status</p>
                            </div>
                        </div>

                        <Link
                            v-if="order.customer"
                            :href="route('admin.customers.show', order.customer.id)"
                            class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1"
                        >
                            Profile <ExternalLink class="w-3 h-3" />
                        </Link>
                    </div>

                    <!-- Customer Profile Banner -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <img
                                :src="order.customer?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(order.customer?.name || 'User')}&background=random`"
                                alt="Customer"
                                class="w-12 h-12 rounded-2xl object-cover ring-2 ring-indigo-500/20 shrink-0"
                            />
                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ order.customer?.name || order.shipping_address?.name }}</h4>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    <span class="flex items-center gap-1 truncate"><Mail class="w-3.5 h-3.5 text-slate-400 shrink-0" /> {{ order.customer?.email || 'No email' }}</span>
                                    <a v-if="order.shipping_address?.phone || order.customer?.phone" :href="`tel:${order.shipping_address?.phone || order.customer?.phone}`" class="flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:underline font-mono">
                                        <Phone class="w-3.5 h-3.5 shrink-0" /> {{ order.shipping_address?.phone || order.customer?.phone }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 shrink-0 whitespace-nowrap">
                            {{ order.customer?.total_orders ?? 0 }} Orders
                        </span>
                    </div>

                    <!-- Side-by-Side Subgrid: Delivery Address & Payment Information -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Delivery Address Card -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800/80 flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <MapPin class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Delivery Address</span>
                                </div>
                                <div class="text-xs text-slate-600 dark:text-slate-300 space-y-1">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ order.shipping_address?.name || order.customer?.name }}</p>
                                    <p class="leading-relaxed">{{ order.shipping_address?.address || order.customer?.address_line_1 }}</p>
                                    <p>{{ [order.shipping_address?.city, order.shipping_address?.state, order.shipping_address?.postal_code].filter(Boolean).join(', ') }}</p>
                                    <p class="font-semibold text-slate-700 dark:text-slate-300">{{ order.shipping_address?.country || 'Bangladesh' }}</p>
                                </div>
                            </div>
                            <div v-if="order.shipping_address?.phone" class="pt-2 border-t border-slate-200/60 dark:border-slate-700/60 text-[11px] text-slate-500 font-mono">
                                Phone: <span class="font-bold text-slate-800 dark:text-slate-200">{{ order.shipping_address.phone }}</span>
                            </div>
                        </div>

                        <!-- Payment Information Card -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800/80 flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <CreditCard class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Payment & Settlement</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-500">Method:</span>
                                        <span class="font-bold text-slate-900 dark:text-white capitalize">
                                            {{ order.payment_method?.replace('_', ' ') }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-500">Status:</span>
                                        <span
                                            :class="[
                                                order.payment_status === 'paid'
                                                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20'
                                                    : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20',
                                                'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase'
                                            ]"
                                        >
                                            {{ order.payment_status }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-500">Total Bill:</span>
                                        <span class="font-bold text-indigo-600 dark:text-indigo-400 font-mono">
                                            {{ formatCurrency(order.total) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-200/60 dark:border-slate-700/60">
                                <button
                                    v-if="order.payment_status !== 'paid'"
                                    @click="updatePayment('paid')"
                                    class="w-full py-1.5 px-3 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors cursor-pointer"
                                >
                                    Mark as Paid
                                </button>
                                <button
                                    v-if="order.payment_status === 'paid'"
                                    @click="updatePayment('refunded')"
                                    class="w-full py-1.5 px-3 rounded-xl text-xs font-semibold bg-purple-50 hover:bg-purple-100 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 transition-colors cursor-pointer"
                                >
                                    Process Refund
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Bangladeshi Fraud Risk Assessment Card (Row 2, Left) -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs relative overflow-hidden space-y-4">
                    <!-- Top Accent Stripe -->
                    <div
                        class="absolute top-0 left-0 right-0 h-1.5"
                        :class="[
                            order.fraud_risk_level === 'high' ? 'bg-rose-500' :
                            order.fraud_risk_level === 'medium' ? 'bg-amber-500' :
                            'bg-emerald-500'
                        ]"
                    ></div>

                    <div class="flex items-center justify-between pb-1">
                        <div class="flex items-center gap-2">
                            <ShieldAlert v-if="order.fraud_risk_level === 'high'" class="w-4 h-4 text-rose-500" />
                            <ShieldAlert v-else-if="order.fraud_risk_level === 'medium'" class="w-4 h-4 text-amber-500" />
                            <ShieldCheck v-else class="w-4 h-4 text-emerald-500" />
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Bangladeshi Fraud Shield</h3>
                                <p class="text-[10px] text-slate-400">Heuristic COD risk analysis & network phone intelligence</p>
                            </div>
                        </div>

                        <Link
                            :href="route('admin.fraud.index')"
                            class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1"
                        >
                            Fraud Hub <ExternalLink class="w-3 h-3" />
                        </Link>
                    </div>

                    <!-- Score & Risk Badge -->
                    <div
                        class="flex items-center justify-between p-3.5 rounded-2xl"
                        :class="[
                            order.fraud_risk_level === 'high' ? 'bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/40' :
                            order.fraud_risk_level === 'medium' ? 'bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/40' :
                            'bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/40'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-sm shrink-0"
                                :class="[
                                    order.fraud_risk_level === 'high' ? 'bg-rose-600 text-white shadow-sm shadow-rose-600/30' :
                                    order.fraud_risk_level === 'medium' ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30' :
                                    'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30'
                                ]"
                            >
                                {{ order.fraud_score ?? 0 }}%
                            </div>
                            <div>
                                <p class="text-xs font-bold capitalize text-slate-900 dark:text-white">
                                    {{ order.fraud_risk_level || 'Low' }} Risk Score
                                </p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 capitalize">
                                    Status: {{ order.fraud_status || 'screened' }}
                                </p>
                            </div>
                        </div>

                        <button
                            @click="triggerRecheck"
                            :disabled="isProcessing"
                            class="p-2 rounded-xl text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                            title="Re-run Bangladeshi Fraud Check"
                        >
                            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isProcessing }" />
                        </button>
                    </div>

                    <!-- Phone Operator & Courier Intel (Side by Side Subgrid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Mobile Operator</span>
                            <span class="font-bold text-slate-900 dark:text-white text-xs mt-0.5 block">
                                {{ order.fraud_flags?.operator || 'BD Mobile' }}
                            </span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Courier Network Intel</span>
                            <span
                                class="font-bold text-xs mt-0.5 block"
                                :class="(order.fraud_flags?.courier_history?.return_rate || 0) > 30 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                            >
                                {{ 100 - (order.fraud_flags?.courier_history?.return_rate || 0) }}% Success
                                <span class="text-[10px] text-slate-400 font-normal">
                                    ({{ order.fraud_flags?.courier_history?.delivered_parcels || 0 }} del / {{ order.fraud_flags?.courier_history?.cancelled_parcels || 0 }} rto)
                                </span>
                            </span>
                        </div>
                    </div>

                    <!-- Fraud Notes -->
                    <div v-if="order.fraud_notes" class="text-[11px] text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 leading-relaxed">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Analysis Note:</span> {{ order.fraud_notes }}
                    </div>

                    <!-- Detected Risk Signals -->
                    <div v-if="order.fraud_flags?.flags && order.fraud_flags.flags.length > 0" class="space-y-1.5">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Detected Risk Signals</p>
                        <div
                            v-for="(flag, fIdx) in order.fraud_flags.flags"
                            :key="fIdx"
                            class="flex items-start gap-2 p-2.5 rounded-2xl bg-rose-50/80 dark:bg-rose-950/25 border border-rose-200/50 dark:border-rose-900/30 text-rose-700 dark:text-rose-400 text-xs"
                        >
                            <AlertTriangle class="w-4 h-4 shrink-0 mt-0.5" />
                            <span>{{ typeof flag === 'object' ? (flag.description || flag.title || JSON.stringify(flag)) : flag }}</span>
                        </div>
                    </div>

                    <!-- Advance Delivery Charge Section -->
                    <div
                        class="p-4 rounded-2xl border border-dashed space-y-2.5"
                        :class="order.advance_payment_status === 'paid' ? 'bg-emerald-50/60 dark:bg-emerald-950/20 border-emerald-300 dark:border-emerald-800' : 'bg-amber-50/60 dark:bg-amber-950/20 border-amber-300 dark:border-amber-800'"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Advance Delivery Fee (COD Protection)</span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                :class="order.advance_payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300'"
                            >
                                {{ order.advance_payment_status || 'not requested' }}
                            </span>
                        </div>

                        <div v-if="order.advance_delivery_charge" class="space-y-2 text-xs">
                            <div class="flex items-baseline justify-between">
                                <span class="text-slate-600 dark:text-slate-400">Required Amount:</span>
                                <span class="font-bold text-slate-900 dark:text-white font-mono text-sm">৳{{ order.advance_delivery_charge }}</span>
                            </div>
                            <div v-if="order.advance_transaction_id" class="text-[11px] text-slate-500 flex items-center justify-between font-mono">
                                <span>TrxID ({{ order.advance_payment_method }}):</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ order.advance_transaction_id }}</span>
                            </div>
                            <div v-if="order.advance_payment_status !== 'paid'" class="pt-1">
                                <button
                                    @click="showAdvanceModal = true"
                                    class="w-full py-2 px-3 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors cursor-pointer"
                                >
                                    Record bKash/Nagad Payment
                                </button>
                            </div>
                        </div>
                        <div v-else class="space-y-2 text-xs">
                            <p class="text-[11px] text-slate-500">
                                Collect delivery fee upfront via bKash/Nagad to prevent courier return losses:
                            </p>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="triggerRequestAdvance(100)"
                                    class="flex-1 py-1.5 rounded-xl text-[11px] font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-200 transition-colors cursor-pointer"
                                >
                                    ৳100 (Dhaka)
                                </button>
                                <button
                                    @click="triggerRequestAdvance(150)"
                                    class="flex-1 py-1.5 rounded-xl text-[11px] font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-200 transition-colors cursor-pointer"
                                >
                                    ৳150 (Outside)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Administrative Quick Actions -->
                    <div class="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button
                            v-if="order.fraud_status !== 'verified'"
                            @click="triggerVerify"
                            class="flex-1 py-2 px-3 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-emerald-700 dark:text-emerald-400 transition-colors inline-flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <Check class="w-3.5 h-3.5" /> Mark Safe
                        </button>
                        <button
                            @click="triggerBlockOrder"
                            class="py-2 px-3 rounded-xl text-xs font-semibold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-400 transition-colors inline-flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <Ban class="w-3.5 h-3.5" /> Block & Blacklist
                        </button>
                    </div>
                </div>

                <!-- 4. Bangladeshi Courier Parcel Dispatch Card (Row 2, Right) -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs relative overflow-hidden space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                <Truck class="w-4 h-4" />
                            </div>
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Courier Dispatch</h3>
                                <p class="text-[10px] text-slate-400">Steadfast / Pathao Parcel Booking & Label</p>
                            </div>
                        </div>

                        <span
                            v-if="order.courier_consignment_id"
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                            :class="order.courier_status === 'delivered' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300'"
                        >
                            {{ order.courier_status || 'booked' }}
                        </span>
                    </div>

                    <!-- State A: Already Booked with Courier -->
                    <div v-if="order.courier_consignment_id" class="space-y-3.5 text-xs">
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Provider:</span>
                                <span class="font-bold text-slate-900 dark:text-white capitalize flex items-center gap-1.5">
                                    <Truck class="w-3.5 h-3.5 text-indigo-500" />
                                    {{ order.courier_provider }} Courier
                                </span>
                            </div>
                            <div class="flex items-center justify-between font-mono">
                                <span class="text-slate-500 font-sans">Tracking Code:</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 select-all">{{ order.courier_tracking_code }}</span>
                            </div>
                            <div class="flex items-center justify-between font-mono">
                                <span class="text-slate-500 font-sans">Consignment ID:</span>
                                <span class="text-slate-700 dark:text-slate-300">{{ order.courier_consignment_id }}</span>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                                <span class="text-slate-500">Net COD to Collect:</span>
                                <span class="font-bold text-slate-900 dark:text-white font-mono text-sm">
                                    {{ (order.courier_cod_amount ?? order.total) > 0 ? '৳' + Number(order.courier_cod_amount ?? order.total).toLocaleString('en-US') : 'Prepaid (৳0)' }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <Link
                                :href="route('admin.orders.courier-label', order.id)"
                                target="_blank"
                                class="py-2.5 px-3 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors flex items-center justify-center gap-1.5 shadow-xs"
                            >
                                <Printer class="w-3.5 h-3.5" /> Print Label
                            </Link>

                            <button
                                type="button"
                                @click="triggerCourierSync"
                                :disabled="isSyncingCourier"
                                class="py-2.5 px-3 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isSyncingCourier }" /> Sync Status
                            </button>
                        </div>
                    </div>

                    <!-- State B: Not Yet Booked -> Dispatch Form -->
                    <div v-else class="space-y-3.5 text-xs">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">Select Courier Network</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="selectedCourier = 'steadfast'"
                                    :class="[
                                        selectedCourier === 'steadfast'
                                            ? 'bg-indigo-600 text-white font-bold ring-2 ring-indigo-500/30'
                                            : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60',
                                        'py-2 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer'
                                    ]"
                                >
                                    <Truck class="w-3.5 h-3.5" /> Steadfast
                                </button>
                                <button
                                    type="button"
                                    @click="selectedCourier = 'pathao'"
                                    :class="[
                                        selectedCourier === 'pathao'
                                            ? 'bg-indigo-600 text-white font-bold ring-2 ring-indigo-500/30'
                                            : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60',
                                        'py-2 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer'
                                    ]"
                                >
                                    <Truck class="w-3.5 h-3.5" /> Pathao
                                </button>
                            </div>
                        </div>

                        <!-- COD Calculation Preview -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-1.5">
                            <div class="flex items-center justify-between text-slate-500">
                                <span>Collect on Delivery:</span>
                                <span class="font-bold text-slate-900 dark:text-white font-mono text-sm">
                                    {{ order.payment_status === 'paid' ? 'Prepaid (৳0)' : formatCurrency(order.advance_payment_status === 'paid' && order.advance_delivery_charge ? Math.max(0, order.total - order.advance_delivery_charge) : order.total) }}
                                </span>
                            </div>
                            <p v-if="order.advance_payment_status === 'paid' && order.advance_delivery_charge" class="text-[10px] text-emerald-600 font-medium">
                                * Deducted ৳{{ order.advance_delivery_charge }} advance fee received via {{ order.advance_payment_method || 'bKash' }}.
                            </p>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">Delivery Instruction / Note</label>
                            <input
                                v-model="courierNote"
                                type="text"
                                placeholder="e.g. Call before delivery, handle with care"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <button
                            type="button"
                            @click="triggerCourierDispatch"
                            :disabled="isDispatching"
                            class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <Loader2 v-if="isDispatching" class="w-4 h-4 animate-spin" />
                            <Truck v-else class="w-4 h-4" />
                            <span>Send to {{ selectedCourier === 'pathao' ? 'Pathao' : 'Steadfast' }} Courier</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advance Payment Confirmation Modal -->
        <div v-if="showAdvanceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-md p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Advance Payment</h3>
                        <p class="text-xs text-slate-500">Record bKash / Nagad advance delivery fee for order {{ order.order_number }}</p>
                    </div>
                    <button @click="showAdvanceModal = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <XCircle class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">MFS Provider</label>
                        <select
                            v-model="advanceForm.advance_payment_method"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white"
                        >
                            <option value="bKash">bKash Merchant / Personal</option>
                            <option value="Nagad">Nagad</option>
                            <option value="Rocket">Rocket (DBBL)</option>
                            <option value="Upay">Upay</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Transaction ID (TrxID)</label>
                        <input
                            v-model="advanceForm.advance_transaction_id"
                            type="text"
                            placeholder="e.g. BL9A7XYZ12"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono uppercase text-slate-900 dark:text-white"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="showAdvanceModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitConfirmAdvance"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs"
                    >
                        Confirm Received
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
