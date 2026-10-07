<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Search,
    User,
    Mail,
    Phone,
    MapPin,
    Eye,
    ShieldAlert,
    CheckCircle2,
    DollarSign,
    ShoppingCart,
    ChevronDown,
    Check,
    Ban,
    Clock,
    Download
} from 'lucide-vue-next';
import CustomSelect from '@/Components/CustomSelect.vue';

const props = defineProps({
    customers: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const openStatusMenuId = ref(null);

const availableCustomerStatuses = [
    { key: 'active', label: 'Active', icon: CheckCircle2, color: 'text-emerald-500' },
    { key: 'inactive', label: 'Inactive', icon: Clock, color: 'text-amber-500' },
    { key: 'blocked', label: 'Blocked', icon: Ban, color: 'text-rose-500' },
];

function applyFilters() {
    router.get(
        route('admin.customers.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

let timeout;
watch(search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(applyFilters, 300);
});

function toggleStatusMenu(custId) {
    openStatusMenuId.value = openStatusMenuId.value === custId ? null : custId;
}

function selectStatus(cust, newStatus) {
    openStatusMenuId.value = null;
    router.patch(route('admin.customers.update-status', cust.id), {
        status: newStatus,
    }, { preserveScroll: true });
}

function closeMenus() {
    openStatusMenuId.value = null;
}

const formatCurrency = (val) => {
    return '৳' + Number(val || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

function getCustomerBadge(st) {
    switch (st) {
        case 'active':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
        case 'inactive':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        default:
            return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20';
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Customers - Admin" />

        <template #header>
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Customer Directory</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">View customer lifetime value, order history, and account status</p>
            </div>
        </template>

        <div class="space-y-6" @click="closeMenus">
            <!-- Filter Bar with Export -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col md:flex-row gap-3 items-center justify-between" @click.stop>
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-1">
                    <div class="relative w-full sm:w-80 md:w-96">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by customer name, email, phone..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="flex items-center gap-2.5 w-full sm:w-56">
                        <CustomSelect
                            v-model="status"
                            @change="applyFilters"
                            :options="[
                                { value: '', label: 'All Account Statuses' },
                                { value: 'active', label: 'Active', icon: CheckCircle2 },
                                { value: 'inactive', label: 'Inactive', icon: Clock },
                                { value: 'blocked', label: 'Blocked', icon: Ban },
                            ]"
                            placeholder="All Account Statuses"
                            compact
                        />
                    </div>
                </div>

                <!-- Export Actions -->
                <div class="flex items-center gap-2 w-full md:w-auto justify-end shrink-0">
                    <a
                        :href="route('admin.customers.export', { format: 'xlsx', search: search || undefined, status: status || undefined })"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors shrink-0"
                        title="Export Customers as Excel"
                    >
                        <Download class="w-3.5 h-3.5" />
                        <span>Excel</span>
                    </a>
                    <a
                        :href="route('admin.customers.export', { format: 'csv', search: search || undefined, status: status || undefined })"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-xs transition-colors shrink-0"
                        title="Export Customers as CSV"
                    >
                        <Download class="w-3.5 h-3.5" />
                        <span>CSV</span>
                    </a>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-visible">
                <div class="overflow-x-auto min-h-[360px] pb-24">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-xs uppercase text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="px-6 py-4">Customer</th>
                                <th class="px-6 py-4">Contact</th>
                                <th class="px-6 py-4">Location</th>
                                <th class="px-6 py-4">Orders</th>
                                <th class="px-6 py-4">Total Spent</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr
                                v-for="(cust, idx) in customers.data"
                                :key="cust.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="cust.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(cust.name)}&background=random`"
                                            alt="Customer"
                                            class="w-10 h-10 rounded-full object-cover ring-2 ring-indigo-500/20 shrink-0"
                                        />
                                        <div>
                                            <Link :href="route('admin.customers.show', cust.id)" class="text-xs font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ cust.name }}
                                            </Link>
                                            <p class="text-[11px] text-slate-400">{{ cust.email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-xs text-slate-600 dark:text-slate-300">
                                    {{ cust.phone || '—' }}
                                </td>

                                <td class="px-6 py-4 text-xs text-slate-600 dark:text-slate-300">
                                    {{ cust.city ? `${cust.city}, ${cust.state || cust.country}` : '—' }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        <ShoppingCart class="w-3.5 h-3.5 text-slate-400" />
                                        {{ cust.orders_count || cust.total_orders || 0 }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 font-bold text-xs text-emerald-600 dark:text-emerald-400">
                                    {{ formatCurrency(cust.total_spent) }}
                                </td>

                                <!-- Status with Smart Dropup/Dropdown positioning -->
                                <td class="px-6 py-4 whitespace-nowrap relative">
                                    <div class="relative inline-block text-left" @click.stop>
                                        <button
                                            type="button"
                                            @click="toggleStatusMenu(cust.id)"
                                            :class="[
                                                getCustomerBadge(cust.status),
                                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold border transition-all cursor-pointer'
                                            ]"
                                        >
                                            <span class="capitalize">{{ cust.status }}</span>
                                            <ChevronDown class="w-3 h-3 opacity-60" />
                                        </button>

                                        <!-- Custom Popover Menu -->
                                        <div
                                            v-if="openStatusMenuId === cust.id"
                                            :class="[
                                                idx >= customers.data.length - 2 && customers.data.length > 2
                                                    ? 'bottom-full mb-2 origin-bottom-left'
                                                    : 'top-full mt-2 origin-top-left',
                                                'absolute left-0 w-36 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl z-50 p-1.5 space-y-1 animate-in fade-in zoom-in-95 duration-150'
                                            ]"
                                        >
                                            <button
                                                v-for="cStat in availableCustomerStatuses"
                                                :key="cStat.key"
                                                @click="selectStatus(cust, cStat.key)"
                                                :class="[
                                                    cust.status === cStat.key ? 'bg-slate-100 dark:bg-slate-800 font-bold' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60',
                                                    'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs text-slate-800 dark:text-slate-200 transition-colors cursor-pointer'
                                                ]"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <component :is="cStat.icon" :class="[cStat.color, 'w-3.5 h-3.5']" />
                                                    <span>{{ cStat.label }}</span>
                                                </div>
                                                <Check v-if="cust.status === cStat.key" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                                            </button>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="route('admin.customers.show', cust.id)"
                                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition-colors"
                                    >
                                        Profile
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="customers.links?.length > 3" class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Showing {{ customers.from }} to {{ customers.to }} of {{ customers.total }} customers
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in customers.links"
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
