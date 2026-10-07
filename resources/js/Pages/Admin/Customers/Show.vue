<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ArrowLeft,
    Mail,
    Phone,
    MapPin,
    DollarSign,
    ShoppingCart,
    Clock,
    Truck,
    PackageCheck,
    CheckCircle2,
    XCircle,
    Calendar,
    ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
    customer: Object,
});

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
            return { label: 'Delivered', bg: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200', icon: CheckCircle2 };
        case 'shipped':
            return { label: 'Shipped', bg: 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border-blue-200', icon: Truck };
        case 'processing':
            return { label: 'Processing', bg: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border-indigo-200', icon: PackageCheck };
        case 'pending':
            return { label: 'Pending', bg: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border-amber-200', icon: Clock };
        case 'cancelled':
            return { label: 'Cancelled', bg: 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border-rose-200', icon: XCircle };
        default:
            return { label: status, bg: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300', icon: Clock };
    }
}
</script>

<template>
    <AdminLayout>
        <Head :title="`${customer.name} - Profile`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.customers.index')"
                    class="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ customer.name }}</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Customer profile & lifetime purchase records</p>
                </div>
            </div>

            <!-- Profile Summary & Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Customer Details Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-4 mb-4">
                            <img
                                :src="customer.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(customer.name)}&background=random`"
                                alt="Customer"
                                class="w-16 h-16 rounded-2xl object-cover ring-2 ring-indigo-500/20"
                            />
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ customer.name }}</h3>
                                <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                                    {{ customer.status }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2">
                                <Mail class="w-4 h-4 text-slate-400" />
                                <span>{{ customer.email }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Phone class="w-4 h-4 text-slate-400" />
                                <span>{{ customer.phone || 'No phone' }}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <MapPin class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                                <span>{{ customer.full_address || 'No address provided' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-slate-400" />
                                <span>Member since {{ formatDate(customer.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Spent LTV -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Customer Lifetime Value</span>
                        <h2 class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-2">
                            {{ formatCurrency(customer.total_spent) }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">Total revenue generated from this customer</p>
                    </div>
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl text-xs text-emerald-800 dark:text-emerald-300 font-semibold">
                        ★ High-value Repeat Customer
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Purchases</span>
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white mt-2">
                            {{ customer.orders?.length || 0 }} Orders
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">Completed order transactions</p>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl text-xs text-slate-600 dark:text-slate-300">
                        Average order: {{ formatCurrency((customer.total_spent / (customer.orders?.length || 1))) }}
                    </div>
                </div>
            </div>

            <!-- Customer Orders History -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Order History</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-xs uppercase text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="px-6 py-4">Order #</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Items</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr v-for="order in customer.orders" :key="order.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-6 py-4 font-bold text-xs text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                    {{ order.order_number }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 whitespace-nowrap">
                                    {{ formatDate(order.created_at) }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-700 dark:text-slate-300">
                                    {{ order.items?.length }} products
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                                        :class="getStatusBadge(order.status).bg"
                                    >
                                        <component :is="getStatusBadge(order.status).icon" class="w-3.5 h-3.5" />
                                        {{ getStatusBadge(order.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ formatCurrency(order.total) }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <Link
                                        :href="route('admin.orders.show', order.id)"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200"
                                    >
                                        Details
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
