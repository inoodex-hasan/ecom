<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ArrowLeft,
    Printer,
    CheckCircle2,
    Truck,
    PackageCheck,
    Clock,
    XCircle,
    User,
    Mail,
    Phone,
    MapPin,
    CreditCard,
    DollarSign,
    ShieldCheck,
    RotateCcw
} from 'lucide-vue-next';
import CustomSelect from '@/Components/CustomSelect.vue';

const props = defineProps({
    order: Object,
});

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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Order Items (2 Cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Items Table Card -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                        <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Ordered Products ({{ order.items?.length }})</h3>
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
                                                    :src="item.image || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=100'"
                                                    alt="Item"
                                                    class="w-12 h-12 rounded-xl object-cover bg-slate-100 dark:bg-slate-800 shrink-0"
                                                />
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
                </div>

                <!-- Customer, Shipping & Payment Summary (1 Col) -->
                <div class="space-y-6">
                    <!-- Customer Card -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Customer Details</h3>
                            <Link
                                v-if="order.customer"
                                :href="route('admin.customers.show', order.customer.id)"
                                class="text-xs font-semibold text-indigo-600 hover:underline"
                            >
                                Profile
                            </Link>
                        </div>

                        <div class="flex items-center gap-3 mb-4">
                            <img
                                :src="order.customer?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(order.customer?.name || 'User')}&background=random`"
                                alt="Customer"
                                class="w-12 h-12 rounded-full object-cover ring-2 ring-indigo-500/20"
                            />
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ order.customer?.name }}</h4>
                                <p class="text-xs text-slate-400">{{ order.customer?.email }}</p>
                            </div>
                        </div>

                        <div class="space-y-2 text-xs border-t border-slate-100 dark:border-slate-800 pt-3">
                            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                <Phone class="w-4 h-4 text-slate-400" />
                                <span>{{ order.customer?.phone || 'No phone provided' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                <ShieldCheck class="w-4 h-4 text-emerald-500" />
                                <span>Verified Customer ({{ order.customer?.total_orders }} orders)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-4">Payment Information</h3>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 mb-4">
                            <div class="flex items-center gap-2.5">
                                <CreditCard class="w-5 h-5 text-indigo-600" />
                                <span class="text-xs font-semibold capitalize">{{ order.payment_method?.replace('_', ' ') }}</span>
                            </div>
                            <span
                                :class="[
                                    order.payment_status === 'paid'
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400'
                                        : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400',
                                    'px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase'
                                ]"
                            >
                                {{ order.payment_status }}
                            </span>
                        </div>

                        <div class="flex gap-2">
                            <button
                                v-if="order.payment_status !== 'paid'"
                                @click="updatePayment('paid')"
                                class="w-full py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white"
                            >
                                Mark as Paid
                            </button>
                            <button
                                v-if="order.payment_status === 'paid'"
                                @click="updatePayment('refunded')"
                                class="w-full py-2 rounded-xl text-xs font-semibold bg-purple-50 hover:bg-purple-100 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300"
                            >
                                Process Refund
                            </button>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Delivery Address</h3>
                        <div class="flex items-start gap-2.5 text-xs text-slate-700 dark:text-slate-300 leading-relaxed">
                            <MapPin class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ order.shipping_address?.name || order.customer?.name }}</p>
                                <p>{{ order.shipping_address?.address || order.customer?.address_line_1 }}</p>
                                <p>{{ order.shipping_address?.city }}, {{ order.shipping_address?.state }} {{ order.shipping_address?.postal_code }}</p>
                                <p>{{ order.shipping_address?.country }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
