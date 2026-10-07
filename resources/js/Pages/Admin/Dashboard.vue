<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import VueApexCharts from 'vue3-apexcharts';
import {
    DollarSign,
    ShoppingCart,
    Users,
    Package,
    AlertTriangle,
    TrendingUp,
    ArrowUpRight,
    CheckCircle2,
    ChevronRight,
    Plus,
    Sparkles,
    Activity,
    Clock,
    BarChart3,
    Eye,
    LayoutGrid,
    List,
    Newspaper,
} from 'lucide-vue-next';

const props = defineProps({
    metrics: Object,
    recentOrders: Array,
    lowStockProducts: Array,
    topProducts: Array,
    statusDistribution: Object,
    salesChart: Object,
    categoryDistribution: Array,
});

// ─── Layout toggle ─────────────────────────────────────────
// 'card' = rich card layout, 'compact' = dense list layout
const layout = ref('card');

onMounted(() => {
    const saved = localStorage.getItem('dashboard_layout');
    if (saved === 'compact' || saved === 'card') layout.value = saved;
});

function toggleLayout() {
    layout.value = layout.value === 'card' ? 'compact' : 'card';
    localStorage.setItem('dashboard_layout', layout.value);
}

// ─── Timeframe ─────────────────────────────────────────────
const selectedTimeframe = ref('12m');

// ─── Formatters ────────────────────────────────────────────
const formatCurrency = (val) =>
    '৳' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

const formatNumber = (val) => new Intl.NumberFormat('en-US').format(val || 0);

// ─── Chart data ────────────────────────────────────────────
const filteredChartData = computed(() => {
    const c = props.salesChart?.categories || [];
    const r = props.salesChart?.revenue || [];
    const o = props.salesChart?.orders || [];
    if (selectedTimeframe.value === '3m') return { categories: c.slice(-3), revenue: r.slice(-3), orders: o.slice(-3) };
    if (selectedTimeframe.value === '6m') return { categories: c.slice(-6), revenue: r.slice(-6), orders: o.slice(-6) };
    return { categories: c, revenue: r, orders: o };
});

const revenueChartOptions = computed(() => ({
    chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent', animations: { enabled: true, easing: 'easeinout', speed: 600 } },
    colors: ['#818cf8', '#34d399'],
    stroke: { curve: 'smooth', width: [3, 2], dashArray: [0, 5] },
    fill: {
        type: 'gradient',
        gradient: {
            type: 'vertical',
            colorStops: [
                [{ offset: 0, color: '#818cf8', opacity: 0.3 }, { offset: 100, color: '#818cf8', opacity: 0.0 }],
                [{ offset: 0, color: '#34d399', opacity: 0.15 }, { offset: 100, color: '#34d399', opacity: 0.0 }],
            ],
        },
    },
    xaxis: { categories: filteredChartData.value.categories, labels: { style: { colors: '#64748b', fontSize: '11px', fontWeight: 500 } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: [
        { labels: { formatter: (v) => '৳' + Number(v / 1000).toFixed(1) + 'k', style: { colors: '#64748b', fontSize: '11px' } } },
        { opposite: true, labels: { formatter: (v) => Math.round(v).toString(), style: { colors: '#64748b', fontSize: '11px' } } },
    ],
    grid: { borderColor: '#1e293b40', strokeDashArray: 4, yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } } },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark', shared: true, intersect: false, y: [{ formatter: (v) => '৳' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) }, { formatter: (v) => v + ' orders' }] },
    legend: { position: 'top', horizontalAlign: 'right', labels: { colors: '#94a3b8' }, markers: { radius: 4 } },
}));

const compactChartOptions = computed(() => ({
    chart: { type: 'bar', height: 140, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent', animations: { enabled: true, speed: 500 } },
    colors: ['#818cf8'],
    plotOptions: { bar: { columnWidth: '60%', borderRadius: 4 } },
    xaxis: { categories: filteredChartData.value.categories, labels: { style: { colors: '#64748b', fontSize: '10px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { formatter: (v) => '৳' + Number(v / 1000).toFixed(0) + 'k', style: { colors: '#64748b', fontSize: '10px' } } },
    grid: { borderColor: '#1e293b20', strokeDashArray: 3 },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark', y: { formatter: (v) => '৳' + Number(v).toLocaleString() } },
}));

const revenueChartSeries = computed(() => [
    { name: 'Revenue', type: 'area', data: filteredChartData.value.revenue },
    { name: 'Orders', type: 'line', data: filteredChartData.value.orders },
]);

const compactChartSeries = computed(() => [
    { name: 'Revenue', data: filteredChartData.value.revenue },
]);

const statusConfigs = {
    delivered:  { label: 'Delivered',  bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20', dot: 'bg-emerald-500',             bar: 'bg-emerald-500', pill: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' },
    shipped:    { label: 'Shipped',    bg: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',             dot: 'bg-blue-500',               bar: 'bg-blue-500',    pill: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' },
    processing: { label: 'Processing', bg: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',     dot: 'bg-indigo-500 animate-pulse', bar: 'bg-indigo-500', pill: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20' },
    pending:    { label: 'Pending',    bg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',         dot: 'bg-amber-500',              bar: 'bg-amber-400',   pill: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' },
    cancelled:  { label: 'Cancelled',  bg: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',             dot: 'bg-rose-500',               bar: 'bg-rose-500',    pill: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' },
};

function getStatusConfig(status) {
    return statusConfigs[status] || { label: status, bg: 'bg-slate-500/10 text-slate-400 border-slate-500/20', dot: 'bg-slate-400', bar: 'bg-slate-400', pill: 'bg-slate-500/10 text-slate-400 border-slate-500/20' };
}

const pipelineTotal = computed(() => {
    const d = props.statusDistribution || {};
    return (d.delivered || 0) + (d.shipped || 0) + (d.processing || 0) + (d.pending || 0) + (d.cancelled || 0) || 1;
});

const pipelineRows = computed(() => [
    { key: 'delivered',  count: props.statusDistribution?.delivered  || 0 },
    { key: 'shipped',    count: props.statusDistribution?.shipped    || 0 },
    { key: 'processing', count: props.statusDistribution?.processing || 0 },
    { key: 'pending',    count: props.statusDistribution?.pending    || 0 },
    { key: 'cancelled',  count: props.statusDistribution?.cancelled  || 0 },
]);

const donutSeries = computed(() => [
    props.statusDistribution?.delivered || 0,
    props.statusDistribution?.shipped || 0,
    props.statusDistribution?.processing || 0,
    props.statusDistribution?.pending || 0,
    props.statusDistribution?.cancelled || 0,
]);

const donutOptions = computed(() => ({
    chart: {
        type: 'donut',
        fontFamily: 'Inter, sans-serif',
        background: 'transparent',
        animations: { enabled: true, speed: 600 },
    },
    labels: ['Delivered', 'Shipped', 'Processing', 'Pending', 'Cancelled'],
    colors: ['#10b981', '#3b82f6', '#6366f1', '#f59e0b', '#f43f5e'],
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent'],
    },
    dataLabels: {
        enabled: false,
    },
    legend: {
        show: false,
    },
    plotOptions: {
        pie: {
            donut: {
                size: '72%',
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: '12px',
                        fontFamily: 'Inter, sans-serif',
                        fontWeight: 600,
                        color: '#64748b',
                        offsetY: -4,
                    },
                    value: {
                        show: true,
                        fontSize: '22px',
                        fontFamily: 'Inter, sans-serif',
                        fontWeight: 800,
                        color: '#64748b',
                        offsetY: 6,
                        formatter: (val) => val,
                    },
                    total: {
                        show: true,
                        label: 'Total Orders',
                        fontSize: '11px',
                        fontFamily: 'Inter, sans-serif',
                        fontWeight: 600,
                        color: '#94a3b8',
                        formatter: (w) => {
                            const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            return sum || props.metrics?.total_orders || 0;
                        },
                    },
                },
            },
        },
    },
    tooltip: {
        theme: 'dark',
        y: {
            formatter: (val) => `${val} order${val !== 1 ? 's' : ''}`,
        },
    },
}));

const medalColors = ['text-amber-400', 'text-slate-400', 'text-amber-700', 'text-slate-500', 'text-slate-500'];
const medalBg    = ['bg-amber-500/10', 'bg-slate-500/10', 'bg-amber-700/10', 'bg-slate-500/10', 'bg-slate-500/10'];

const hasAlerts = computed(() => (props.metrics?.low_stock_count || 0) > 0 || (props.metrics?.pending_orders || 0) > 0);

// Compact stat rows
const statRows = computed(() => [
    { icon: DollarSign, label: 'Net Revenue',      value: formatCurrency(props.metrics?.total_revenue),   sub: 'AOV ' + formatCurrency(props.metrics?.avg_order_value), color: 'text-indigo-500', bg: 'bg-indigo-500/10' },
    { icon: ShoppingCart, label: 'Total Orders',   value: formatNumber(props.metrics?.total_orders),       sub: props.metrics?.fulfillment_rate + '% fulfilled',        color: 'text-blue-500',   bg: 'bg-blue-500/10' },
    { icon: Users, label: 'Customers',             value: formatNumber(props.metrics?.total_customers),    sub: '88.4% retention',                                       color: 'text-emerald-500',bg: 'bg-emerald-500/10' },
    { icon: Package, label: 'SKUs',                value: formatNumber(props.metrics?.total_products),     sub: (props.metrics?.low_stock_count || 0) + ' low stock',    color: 'text-amber-500',  bg: 'bg-amber-500/10' },
]);
</script>

<template>
    <AdminLayout>
        <Head title="Dashboard" />

        <div class="space-y-5">

            <!-- ── Page Header ──────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        Store Overview <Sparkles class="w-5 h-5 text-indigo-400" />
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Revenue, orders, and inventory at a glance</p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Layout toggle pill -->
                    <div class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-xl p-1 gap-0.5">
                        <button
                            @click="layout = 'card'; localStorage.setItem('dashboard_layout', 'card')"
                            :class="[layout === 'card' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200', 'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200']"
                        >
                            <LayoutGrid class="w-3.5 h-3.5" /> Card
                        </button>
                        <button
                            @click="layout = 'compact'; localStorage.setItem('dashboard_layout', 'compact')"
                            :class="[layout === 'compact' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200', 'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200']"
                        >
                            <List class="w-3.5 h-3.5" /> Compact
                        </button>
                    </div>

                    <Link
                        :href="route('admin.products.index')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/25 transition-all duration-200"
                    >
                        <Plus class="w-3.5 h-3.5" /> New Product
                    </Link>
                    <Link
                        :href="route('admin.orders.index')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm"
                    >
                        <ShoppingCart class="w-3.5 h-3.5 text-slate-400" /> View Orders
                    </Link>
                    <Link
                        :href="route('admin.blogs.index')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm"
                    >
                        <Newspaper class="w-3.5 h-3.5 text-slate-400" /> Blogs & Stories
                    </Link>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════
                 CARD LAYOUT
            ════════════════════════════════════════════════════════ -->
            <template v-if="layout === 'card'">

                <!-- Alert Banners -->
                <div v-if="hasAlerts" class="flex flex-col sm:flex-row gap-3">
                    <div v-if="(metrics?.pending_orders || 0) > 0" class="flex-1 flex items-center gap-3 px-4 py-3 rounded-2xl bg-amber-500/10 border border-amber-500/25 text-amber-700 dark:text-amber-400">
                        <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-amber-500/15 flex items-center justify-center"><Clock class="w-4 h-4" /></div>
                        <p class="flex-1 text-xs font-bold min-w-0">{{ metrics.pending_orders }} pending order{{ metrics.pending_orders > 1 ? 's' : '' }} awaiting action</p>
                        <Link :href="route('admin.orders.index', { status: 'pending' })" class="flex-shrink-0 inline-flex items-center gap-1 text-xs font-bold hover:underline">Review <ChevronRight class="w-3.5 h-3.5" /></Link>
                    </div>
                    <div v-if="(metrics?.low_stock_count || 0) > 0" class="flex-1 flex items-center gap-3 px-4 py-3 rounded-2xl bg-rose-500/10 border border-rose-500/25 text-rose-700 dark:text-rose-400">
                        <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-rose-500/15 flex items-center justify-center"><AlertTriangle class="w-4 h-4" /></div>
                        <p class="flex-1 text-xs font-bold min-w-0">{{ metrics.low_stock_count }} product{{ metrics.low_stock_count > 1 ? 's' : '' }} running low on stock</p>
                        <Link :href="route('admin.products.index', { low_stock: true })" class="flex-shrink-0 inline-flex items-center gap-1 text-xs font-bold hover:underline">Restock <ChevronRight class="w-3.5 h-3.5" /></Link>
                    </div>
                </div>

                <!-- Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <!-- Revenue -->
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 group hover:shadow-lg hover:shadow-indigo-500/5 hover:-translate-y-0.5 transition-all duration-300">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-transparent to-transparent pointer-events-none"></div>
                        <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-indigo-500/10 blur-2xl group-hover:bg-indigo-500/20 transition-all duration-500"></div>
                        <div class="relative flex items-start justify-between">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/30"><DollarSign class="w-5 h-5 text-white" /></div>
                            <span class="inline-flex items-center gap-0.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full"><TrendingUp class="w-3 h-3" /> +24.8%</span>
                        </div>
                        <div class="relative mt-4">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Net Revenue</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1 tracking-tight">{{ formatCurrency(metrics?.total_revenue) }}</p>
                            <p class="text-xs text-slate-400 mt-1.5">Avg order: <span class="font-bold text-slate-600 dark:text-slate-300">{{ formatCurrency(metrics?.avg_order_value) }}</span></p>
                        </div>
                    </div>
                    <!-- Orders -->
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 group hover:shadow-lg hover:shadow-blue-500/5 hover:-translate-y-0.5 transition-all duration-300">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-transparent to-transparent pointer-events-none"></div>
                        <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-blue-500/10 blur-2xl group-hover:bg-blue-500/20 transition-all duration-500"></div>
                        <div class="relative flex items-start justify-between">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/30"><ShoppingCart class="w-5 h-5 text-white" /></div>
                            <span v-if="(metrics?.pending_orders || 0) > 0" class="inline-flex items-center gap-0.5 text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full"><Clock class="w-3 h-3" /> {{ metrics.pending_orders }} pending</span>
                            <span v-else class="inline-flex items-center gap-0.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full"><CheckCircle2 class="w-3 h-3" /> All clear</span>
                        </div>
                        <div class="relative mt-4">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Orders</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1 tracking-tight">{{ formatNumber(metrics?.total_orders) }}</p>
                            <p class="text-xs text-slate-400 mt-1.5">Fulfillment: <span class="font-bold text-blue-600 dark:text-blue-400">{{ metrics?.fulfillment_rate }}%</span></p>
                        </div>
                    </div>
                    <!-- Customers -->
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 group hover:shadow-lg hover:shadow-emerald-500/5 hover:-translate-y-0.5 transition-all duration-300">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 via-transparent to-transparent pointer-events-none"></div>
                        <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-emerald-500/10 blur-2xl group-hover:bg-emerald-500/20 transition-all duration-500"></div>
                        <div class="relative flex items-start justify-between">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/30"><Users class="w-5 h-5 text-white" /></div>
                            <span class="inline-flex items-center gap-0.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full"><TrendingUp class="w-3 h-3" /> +14.2%</span>
                        </div>
                        <div class="relative mt-4">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Customers</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1 tracking-tight">{{ formatNumber(metrics?.total_customers) }}</p>
                            <p class="text-xs text-slate-400 mt-1.5">Retention: <span class="font-bold text-slate-600 dark:text-slate-300">88.4%</span></p>
                        </div>
                    </div>
                    <!-- Inventory -->
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 group hover:shadow-lg hover:shadow-amber-500/5 hover:-translate-y-0.5 transition-all duration-300">
                        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 via-transparent to-transparent pointer-events-none"></div>
                        <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-amber-500/10 blur-2xl group-hover:bg-amber-500/20 transition-all duration-500"></div>
                        <div class="relative flex items-start justify-between">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center shadow-lg shadow-amber-500/30"><Package class="w-5 h-5 text-white" /></div>
                            <span v-if="(metrics?.low_stock_count || 0) > 0" class="inline-flex items-center gap-0.5 text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded-full"><AlertTriangle class="w-3 h-3" /> {{ metrics.low_stock_count }} low</span>
                            <span v-else class="inline-flex items-center gap-0.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full"><CheckCircle2 class="w-3 h-3" /> Optimal</span>
                        </div>
                        <div class="relative mt-4">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Products / SKUs</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1 tracking-tight">{{ formatNumber(metrics?.total_products) }}</p>
                            <Link :href="route('admin.products.index')" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline mt-1.5 inline-block">Manage inventory →</Link>
                        </div>
                    </div>
                </div>

                <!-- Editorial & Blog Quick Strip -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-2xl bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-transparent border border-indigo-500/20">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600/15 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <Newspaper class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900 dark:text-white">Storefront Blog & Editorial Engine</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ metrics?.published_blog_count || 0 }}</span> published articles live on Next.js storefront · 
                                <span class="font-bold text-slate-600 dark:text-slate-300">{{ Math.max(0, (metrics?.blog_count || 0) - (metrics?.published_blog_count || 0)) }}</span> draft in progress
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.blogs.index')" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm transition-colors">
                            Manage Articles <ChevronRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- Charts + Pipeline -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2"><BarChart3 class="w-4 h-4 text-indigo-400" /> Revenue & Order Trajectory</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Sales performance over time</p>
                            </div>
                            <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl gap-0.5 self-start sm:self-auto">
                                <button v-for="tf in [{ key: '3m', label: '3M' }, { key: '6m', label: '6M' }, { key: '12m', label: '12M' }]" :key="tf.key" @click="selectedTimeframe = tf.key" :class="[selectedTimeframe === tf.key ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200', 'px-3 py-1.5 text-xs rounded-lg transition-all duration-150']">{{ tf.label }}</button>
                            </div>
                        </div>
                        <div class="h-72"><VueApexCharts type="area" height="100%" :options="revenueChartOptions" :series="revenueChartSeries" /></div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2"><Activity class="w-4 h-4 text-indigo-400" /> Order Pipeline</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Live fulfillment breakdown</p>
                            </div>
                            <Link :href="route('admin.orders.index')" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">All orders</Link>
                        </div>

                        <!-- Donut chart -->
                        <div class="h-56 flex items-center justify-center">
                            <VueApexCharts type="donut" height="220" :options="donutOptions" :series="donutSeries" />
                        </div>

                        <!-- Status pills -->
                        <div class="grid grid-cols-2 gap-1.5 mt-4">
                            <div
                                v-for="row in pipelineRows"
                                :key="row.key"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border text-[11px] font-bold"
                                :class="getStatusConfig(row.key).pill"
                            >
                                <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="getStatusConfig(row.key).dot"></div>
                                <span class="flex-1 truncate">{{ getStatusConfig(row.key).label }}</span>
                                <span class="tabular-nums">{{ row.count }}</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                            <span class="text-slate-500 dark:text-slate-400">Total tracked</span>
                            <span class="font-black text-slate-900 dark:text-white text-base">{{ metrics?.total_orders }}</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders + Top Products -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Recent Transactions</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Latest incoming orders</p>
                            </div>
                            <Link :href="route('admin.orders.index')" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">View all <ChevronRight class="w-3.5 h-3.5" /></Link>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <div v-for="order in recentOrders" :key="order.id" class="flex items-center gap-4 px-6 py-3.5 hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors group">
                                <img :src="order.customer?.avatar || ('https://ui-avatars.com/api/?name=' + encodeURIComponent(order.customer?.name || 'User') + '&background=random&size=80')" alt="Customer" class="w-9 h-9 rounded-full object-cover ring-2 ring-white dark:ring-slate-800 flex-shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate">{{ order.customer?.name }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <Link :href="route('admin.orders.show', order.id)" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">{{ order.order_number }}</Link>
                                        <span class="text-[10px] text-slate-400">·</span>
                                        <span class="text-[11px] text-slate-400">{{ order.items_count }} item{{ order.items_count !== 1 ? 's' : '' }}</span>
                                    </div>
                                </div>
                                <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold border flex-shrink-0" :class="getStatusConfig(order.status).bg">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="getStatusConfig(order.status).dot"></span>
                                    {{ getStatusConfig(order.status).label }}
                                </span>
                                <p class="text-sm font-black text-slate-900 dark:text-white flex-shrink-0">{{ formatCurrency(order.total) }}</p>
                                <Link :href="route('admin.orders.show', order.id)" class="flex-shrink-0 opacity-0 group-hover:opacity-100 w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all">
                                    <Eye class="w-3.5 h-3.5" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col gap-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Top Sellers</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">By total units sold</p>
                            </div>
                            <Link :href="route('admin.products.index')" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Catalog →</Link>
                        </div>
                        <div class="space-y-4">
                            <div v-for="(prod, idx) in topProducts" :key="prod.product_id" class="flex items-center gap-3">
                                <div :class="[medalBg[idx], 'w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0']"><span :class="[medalColors[idx], 'text-xs font-black']">{{ idx + 1 }}</span></div>
                                <img :src="prod.image || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=80'" alt="Product" class="w-8 h-8 rounded-lg object-cover bg-slate-100 dark:bg-slate-800 flex-shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between text-xs mb-1 gap-2">
                                        <span class="font-semibold text-slate-800 dark:text-slate-200 truncate">{{ prod.product_name }}</span>
                                        <span class="font-black text-slate-900 dark:text-white flex-shrink-0">{{ formatCurrency(prod.revenue) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-700" :style="{ width: prod.progress_percentage + '%' }"></div>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-mono flex-shrink-0">{{ prod.total_sold }} sold</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="lowStockProducts.length > 0" class="mt-auto p-3.5 rounded-xl bg-rose-500/8 border border-rose-500/20">
                            <div class="flex items-start gap-2.5">
                                <AlertTriangle class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-rose-700 dark:text-rose-400">{{ lowStockProducts.length }} product{{ lowStockProducts.length > 1 ? 's' : '' }} low on stock</p>
                                    <Link :href="route('admin.products.index', { low_stock: true })" class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 dark:text-rose-400 mt-1 hover:underline">Review items <ArrowUpRight class="w-3 h-3" /></Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════
                 COMPACT LAYOUT
            ════════════════════════════════════════════════════════ -->
            <template v-else>

                <!-- Compact: Inline stat row + mini alerts -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
                    <div
                        v-for="stat in statRows"
                        :key="stat.label"
                        class="flex items-center gap-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 hover:border-slate-300 dark:hover:border-slate-700 transition-all"
                    >
                        <div :class="[stat.bg, 'w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0']">
                            <component :is="stat.icon" :class="[stat.color, 'w-4.5 h-4.5']" class="w-4 h-4" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ stat.label }}</p>
                            <p class="text-lg font-black text-slate-900 dark:text-white leading-tight">{{ stat.value }}</p>
                            <p class="text-[10px] text-slate-400 truncate">{{ stat.sub }}</p>
                        </div>
                    </div>
                </div>

                <!-- Compact: Alerts inline -->
                <div v-if="hasAlerts" class="flex flex-col sm:flex-row gap-2">
                    <div v-if="(metrics?.pending_orders || 0) > 0" class="flex-1 flex items-center gap-2.5 px-3 py-2 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-400">
                        <Clock class="w-3.5 h-3.5 flex-shrink-0" />
                        <p class="flex-1 text-xs font-semibold">{{ metrics.pending_orders }} orders pending review</p>
                        <Link :href="route('admin.orders.index', { status: 'pending' })" class="text-xs font-bold hover:underline flex-shrink-0">Act now →</Link>
                    </div>
                    <div v-if="(metrics?.low_stock_count || 0) > 0" class="flex-1 flex items-center gap-2.5 px-3 py-2 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-400">
                        <AlertTriangle class="w-3.5 h-3.5 flex-shrink-0" />
                        <p class="flex-1 text-xs font-semibold">{{ metrics.low_stock_count }} low-stock products</p>
                        <Link :href="route('admin.products.index', { low_stock: true })" class="text-xs font-bold hover:underline flex-shrink-0">Restock →</Link>
                    </div>
                </div>

                <!-- Compact: 3-col middle row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                    <!-- Mini bar chart -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <BarChart3 class="w-3.5 h-3.5 text-indigo-400" />
                                <span class="text-xs font-bold text-slate-800 dark:text-white">Monthly Revenue</span>
                            </div>
                            <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg gap-0.5">
                                <button v-for="tf in [{ key: '3m', label: '3M' }, { key: '6m', label: '6M' }, { key: '12m', label: '12M' }]" :key="tf.key" @click="selectedTimeframe = tf.key" :class="[selectedTimeframe === tf.key ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm font-bold' : 'text-slate-500', 'px-2.5 py-1 text-[10px] rounded-md transition-all']">{{ tf.label }}</button>
                            </div>
                        </div>
                        <div class="h-36">
                            <VueApexCharts type="bar" height="100%" :options="compactChartOptions" :series="compactChartSeries" />
                        </div>
                    </div>

                    <!-- Compact pipeline -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-800 dark:text-white flex items-center gap-1.5"><Activity class="w-3.5 h-3.5 text-indigo-400" /> Pipeline</span>
                            <Link :href="route('admin.orders.index')" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">All →</Link>
                        </div>
                        <!-- Stacked bar -->
                        <div class="flex h-1.5 rounded-full overflow-hidden gap-0.5 mb-3">
                            <div v-for="row in pipelineRows" :key="row.key + '-c'" :class="getStatusConfig(row.key).bar" :style="{ width: ((row.count / pipelineTotal) * 100) + '%' }" class="rounded-full"></div>
                        </div>
                        <div class="space-y-2">
                            <div v-for="row in pipelineRows" :key="row.key" class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="getStatusConfig(row.key).dot"></div>
                                    <span class="text-[11px] text-slate-600 dark:text-slate-400">{{ getStatusConfig(row.key).label }}</span>
                                </div>
                                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 tabular-nums">{{ row.count }}</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400">Total</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white">{{ metrics?.total_orders }}</span>
                        </div>
                    </div>
                </div>

                <!-- Compact: Orders table + Top sellers -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                    <!-- Compact orders table (2 cols) -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 dark:text-white">Recent Orders</span>
                            <Link :href="route('admin.orders.index')" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">View all →</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                    <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                        <th class="px-4 py-2.5">Order</th>
                                        <th class="px-4 py-2.5">Customer</th>
                                        <th class="px-4 py-2.5">Status</th>
                                        <th class="px-4 py-2.5 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                    <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/20 transition-colors">
                                        <td class="px-4 py-2.5">
                                            <Link :href="route('admin.orders.show', order.id)" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">{{ order.order_number }}</Link>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="flex items-center gap-2">
                                                <img :src="order.customer?.avatar || ('https://ui-avatars.com/api/?name=' + encodeURIComponent(order.customer?.name || 'User') + '&background=random&size=40')" alt="" class="w-5 h-5 rounded-full object-cover flex-shrink-0" />
                                                <span class="font-medium text-slate-700 dark:text-slate-300 truncate max-w-[100px]">{{ order.customer?.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="getStatusConfig(order.status).bg">
                                                <span class="w-1 h-1 rounded-full" :class="getStatusConfig(order.status).dot"></span>
                                                {{ getStatusConfig(order.status).label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-bold text-slate-900 dark:text-white tabular-nums">{{ formatCurrency(order.total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Compact top sellers (1 col) -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-800 dark:text-white">Top Sellers</span>
                            <Link :href="route('admin.products.index')" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">All →</Link>
                        </div>
                        <div class="space-y-3">
                            <div v-for="(prod, idx) in topProducts" :key="prod.product_id" class="flex items-center gap-2.5">
                                <span :class="[medalColors[idx], 'text-[10px] font-black w-4 text-center flex-shrink-0']">{{ idx + 1 }}</span>
                                <img :src="prod.image || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=80'" alt="" class="w-7 h-7 rounded-lg object-cover bg-slate-100 dark:bg-slate-800 flex-shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1 text-[11px]">
                                        <span class="font-semibold text-slate-700 dark:text-slate-200 truncate">{{ prod.product_name }}</span>
                                        <span class="font-bold text-slate-900 dark:text-white flex-shrink-0">{{ formatCurrency(prod.revenue) }}</span>
                                    </div>
                                    <div class="h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mt-1">
                                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-500" :style="{ width: prod.progress_percentage + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="lowStockProducts.length > 0" class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400">
                                <AlertTriangle class="w-3 h-3 flex-shrink-0" />
                                <span class="text-[11px] font-bold">{{ lowStockProducts.length }} low stock</span>
                                <Link :href="route('admin.products.index', { low_stock: true })" class="ml-auto text-[10px] font-bold hover:underline">Fix →</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </AdminLayout>
</template>
