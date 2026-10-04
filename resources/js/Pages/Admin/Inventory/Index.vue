<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import {
    Boxes,
    Package,
    AlertTriangle,
    AlertOctagon,
    Search,
    Download,
    Plus,
    Minus,
    SlidersHorizontal,
    History,
    RefreshCw,
    ArrowUpRight,
    ArrowDownRight,
    FileText,
    CheckCircle2,
    X,
    Filter,
    Layers,
    Warehouse,
    TrendingUp,
    ShieldAlert
} from 'lucide-vue-next';

const props = defineProps({
    products: Object,
    transactions: Object,
    categories: Array,
    filters: Object,
    metrics: Object,
});

const activeTab = ref('levels'); // 'levels' | 'audit' | 'restock'

// Filter states
const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');
const selectedProductType = ref(props.filters.product_type || '');
const selectedStockStatus = ref(props.filters.stock_status || '');
const sortField = ref(props.filters.sort || 'name');
const sortDirection = ref(props.filters.direction || 'asc');

const productTypes = [
    { value: '', label: 'All Product Types' },
    { value: 'standard', label: 'Standard' },
    { value: 'apparel', label: 'Apparel & Clothing' },
    { value: 'building_material', label: 'Building Materials' },
    { value: 'liquid', label: 'Liquid / Bottled' },
    { value: 'electronics', label: 'Electronics & Hardware' },
    { value: 'digital', label: 'Digital' },
];

const stockStatuses = [
    { value: '', label: 'All Stock Statuses' },
    { value: 'in_stock', label: 'In Stock' },
    { value: 'low_stock', label: 'Low Stock Alerts' },
    { value: 'out_of_stock', label: 'Out of Stock' },
];

const categoryOptions = computed(() => [
    { value: '', label: 'All Categories' },
    ...props.categories.map(c => ({ value: c.id, label: c.name }))
]);

// Search & filter handler
let searchTimeout = null;
function handleSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
}

function applyFilters() {
    router.get(
        route('admin.inventory.index'),
        {
            search: search.value || undefined,
            category: selectedCategory.value || undefined,
            product_type: selectedProductType.value || undefined,
            stock_status: selectedStockStatus.value || undefined,
            sort: sortField.value,
            direction: sortDirection.value,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

function resetFilters() {
    search.value = '';
    selectedCategory.value = '';
    selectedProductType.value = '';
    selectedStockStatus.value = '';
    applyFilters();
}

// Modal State for Stock Adjustment
const isAdjustModalOpen = ref(false);
const selectedItem = ref(null); // { product, variant: null|variant }

const adjustForm = useForm({
    product_id: null,
    product_variant_id: null,
    mode: 'add', // 'add' | 'remove' | 'set'
    quantity: 1,
    reason: 'restock',
    reference_number: '',
    notes: '',
});

const reasonOptions = [
    { value: 'restock', label: '📦 Received Shipment / Restock' },
    { value: 'physical_count', label: '📋 Physical Count / Inventory Stocktake' },
    { value: 'customer_return', label: '↩️ Customer Return' },
    { value: 'damaged', label: '💥 Damaged / Defective Stock' },
    { value: 'order_fulfillment', label: '🚚 Order Fulfillment / Manual Deduction' },
    { value: 'loss_theft', label: '⚠️ Loss / Shrinkage / Theft' },
    { value: 'expired', label: '⏳ Expired / Spoiled' },
    { value: 'other', label: '📝 Other Reason' },
];

function openAdjustModal(product, variant = null, prefillMode = 'add', prefillQty = 1) {
    selectedItem.value = { product, variant };
    adjustForm.reset();
    adjustForm.product_id = product.id;
    adjustForm.product_variant_id = variant ? variant.id : null;
    adjustForm.mode = prefillMode;
    adjustForm.quantity = prefillQty;
    adjustForm.reason = prefillMode === 'add' ? 'restock' : (prefillMode === 'set' ? 'physical_count' : 'damaged');
    isAdjustModalOpen.value = true;
}

const currentStockOfSelected = computed(() => {
    if (!selectedItem.value) return 0;
    if (selectedItem.value.variant) {
        return Number(selectedItem.value.variant.stock_quantity) || 0;
    }
    return Number(selectedItem.value.product.stock_quantity) || 0;
});

const previewNewStock = computed(() => {
    const cur = currentStockOfSelected.value;
    const qty = Math.max(0, Number(adjustForm.quantity) || 0);
    if (adjustForm.mode === 'add') return cur + qty;
    if (adjustForm.mode === 'remove') return Math.max(0, cur - qty);
    if (adjustForm.mode === 'set') return qty;
    return cur;
});

function submitAdjustment() {
    adjustForm.post(route('admin.inventory.adjust'), {
        preserveScroll: true,
        onSuccess: () => {
            isAdjustModalOpen.value = false;
            adjustForm.reset();
            selectedItem.value = null;
        },
    });
}

// Quick 1-click +/- 1
function quickAdjust(product, variant, delta) {
    const mode = delta > 0 ? 'add' : 'remove';
    const form = useForm({
        product_id: product.id,
        product_variant_id: variant ? variant.id : null,
        mode: mode,
        quantity: Math.abs(delta),
        reason: delta > 0 ? 'restock' : 'damaged',
        notes: `Quick 1-click ${delta > 0 ? 'addition' : 'deduction'} from inventory grid`,
    });
    form.post(route('admin.inventory.adjust'), { preserveScroll: true });
}

// Low Stock Items for Restock Tab
const lowStockProducts = computed(() => {
    if (!props.products || !props.products.data) return [];
    const items = [];
    props.products.data.forEach(prod => {
        if (prod.has_variants && prod.variants && prod.variants.length > 0) {
            prod.variants.forEach(v => {
                if (v.stock_quantity <= prod.low_stock_threshold) {
                    items.push({
                        product: prod,
                        variant: v,
                        sku: v.sku,
                        name: prod.name,
                        option_values: v.option_values,
                        stock_quantity: v.stock_quantity,
                        low_stock_threshold: prod.low_stock_threshold,
                        unit: prod.unit,
                        deficit: Math.max(0, (prod.low_stock_threshold * 2) - v.stock_quantity)
                    });
                }
            });
        } else if (prod.stock_quantity <= prod.low_stock_threshold) {
            items.push({
                product: prod,
                variant: null,
                sku: prod.sku,
                name: prod.name,
                option_values: null,
                stock_quantity: prod.stock_quantity,
                low_stock_threshold: prod.low_stock_threshold,
                unit: prod.unit,
                deficit: Math.max(0, (prod.low_stock_threshold * 2) - prod.stock_quantity)
            });
        }
    });
    return items;
});

function formatReason(reason) {
    const map = {
        restock: 'Received Restock',
        physical_count: 'Physical Stocktake',
        customer_return: 'Customer Return',
        damaged: 'Damaged Goods',
        order_fulfillment: 'Order Fulfillment',
        loss_theft: 'Loss / Shrinkage',
        expired: 'Expired / Spoiled',
        other: 'Manual Adjustment',
    };
    return map[reason] || reason;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Inventory & Stock Management" />

        <template #header>
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                    <Boxes class="w-7 h-7 text-indigo-600 dark:text-indigo-400" />
                    Inventory & Stock Control
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Track SKU stock levels, perform batch adjustments, and inspect full movement audit history.
                </p>
            </div>
        </template>

        <div class="space-y-6 pb-12">
            <!-- Metric KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Active SKUs -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total SKUs</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ metrics.total_skus }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Standalone & Variant Items</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                            <Boxes class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <!-- Total Units on Hand -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Units in Stock</p>
                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ metrics.total_units.toLocaleString() }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Live aggregated inventory</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                            <Package class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <!-- Low Stock Warnings -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-amber-500">Low Stock Warnings</p>
                            <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ metrics.low_stock_count }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">At or below reorder point</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <!-- Out of Stock -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-rose-500">Out of Stock</p>
                            <p class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ metrics.out_of_stock_count }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Zero quantity remaining</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold">
                            <AlertOctagon class="w-6 h-6" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation & Filters Bar -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <!-- Navigation Tabs -->
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                        <button
                            @click="activeTab = 'levels'"
                            :class="[
                                activeTab === 'levels'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                                'px-4 py-2 rounded-xl text-xs font-bold transition-colors flex items-center gap-2 shrink-0'
                            ]"
                        >
                            <Boxes class="w-4 h-4" />
                            Stock Levels & Quick Adjust
                        </button>

                        <button
                            @click="activeTab = 'audit'"
                            :class="[
                                activeTab === 'audit'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                                'px-4 py-2 rounded-xl text-xs font-bold transition-colors flex items-center gap-2 shrink-0'
                            ]"
                        >
                            <History class="w-4 h-4" />
                            Stock Movements (Audit Log)
                        </button>

                        <button
                            @click="activeTab = 'restock'"
                            :class="[
                                activeTab === 'restock'
                                    ? 'bg-amber-500 text-white shadow-sm'
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                                'px-4 py-2 rounded-xl text-xs font-bold transition-colors flex items-center gap-2 shrink-0'
                            ]"
                        >
                            <ShieldAlert class="w-4 h-4" />
                            Replenishment Planner
                            <span v-if="metrics.low_stock_count + metrics.out_of_stock_count > 0" class="px-1.5 py-0.2 bg-white/30 text-white text-[10px] rounded-full font-extrabold">
                                {{ metrics.low_stock_count + metrics.out_of_stock_count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Filter Controls (for Stock Levels tab) -->
                <div v-if="activeTab === 'levels'" class="flex flex-col xl:flex-row gap-3 items-stretch xl:items-center justify-between">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 flex-1">
                        <div class="relative">
                            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="search"
                                @input="handleSearch"
                                type="text"
                                placeholder="Search SKU or Product Name..."
                                class="w-full pl-9 pr-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <CustomSelect
                                v-model="selectedStockStatus"
                                :options="stockStatuses"
                                placeholder="Stock Status"
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <div>
                            <CustomSelect
                                v-model="selectedCategory"
                                :options="categoryOptions"
                                placeholder="Category"
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <div>
                            <CustomSelect
                                v-model="selectedProductType"
                                :options="productTypes"
                                placeholder="Product Type"
                                @update:model-value="applyFilters"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 self-end xl:self-center">
                        <button
                            v-if="search || selectedStockStatus || selectedCategory || selectedProductType"
                            @click="resetFilters"
                            class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors flex items-center gap-1.5"
                            title="Reset filters"
                        >
                            <X class="w-3.5 h-3.5" />
                            Reset
                        </button>
                        <a
                            :href="route('admin.inventory.export')"
                            class="inline-flex items-center gap-2 px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl shadow-xs transition-colors whitespace-nowrap"
                        >
                            <Download class="w-3.5 h-3.5 text-slate-500" />
                            Export CSV
                        </a>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 1: STOCK LEVELS & QUICK ADJUST ================= -->
            <div v-if="activeTab === 'levels'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3.5">Product & SKU</th>
                                <th class="px-4 py-3.5">Category & Type</th>
                                <th class="px-4 py-3.5">Reorder Point</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-4 py-3.5 text-center">Current Stock</th>
                                <th class="px-5 py-3.5 text-right">Quick Stock Adjustment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <template v-for="product in products.data" :key="product.id">
                                <!-- Base Row for Standard (Non-variant) Product -->
                                <tr v-if="!product.has_variants || !product.variants || product.variants.length === 0" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <img
                                                :src="product.primary_image || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=100&auto=format&fit=crop&q=60'"
                                                alt="Thumb"
                                                class="w-10 h-10 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shrink-0 bg-slate-100"
                                            />
                                            <div>
                                                <p class="font-bold text-slate-900 dark:text-white text-xs">{{ product.name }}</p>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <span class="font-mono text-[10px] text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-1.5 py-0.5 rounded font-semibold">
                                                        {{ product.sku }}
                                                    </span>
                                                    <span class="text-[10px] text-slate-400">({{ product.unit }})</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <p class="font-medium text-slate-800 dark:text-slate-200 text-xs">{{ product.category?.name || 'Uncategorized' }}</p>
                                        <span class="text-[10px] capitalize text-slate-400">{{ product.product_type }}</span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="text-xs font-mono font-bold text-slate-600 dark:text-slate-300">{{ product.low_stock_threshold }}</span>
                                        <span class="text-[10px] text-slate-400 ml-1">{{ product.unit }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span
                                            v-if="product.stock_quantity <= 0"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800/40"
                                        >
                                            Out of Stock
                                        </span>
                                        <span
                                            v-else-if="product.stock_quantity <= product.low_stock_threshold"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40"
                                        >
                                            Low Stock
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40"
                                        >
                                            In Stock
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="text-sm font-black font-mono text-slate-900 dark:text-white">{{ product.stock_quantity }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                @click="quickAdjust(product, null, -1)"
                                                title="Deduct 1"
                                                class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center text-xs transition-colors"
                                            >
                                                <Minus class="w-3.5 h-3.5" />
                                            </button>
                                            <button
                                                @click="quickAdjust(product, null, 1)"
                                                title="Add 1"
                                                class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center text-xs transition-colors"
                                            >
                                                <Plus class="w-3.5 h-3.5" />
                                            </button>
                                            <button
                                                @click="openAdjustModal(product, null)"
                                                class="ml-1 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 font-bold rounded-lg text-xs transition-colors flex items-center gap-1.5"
                                            >
                                                <SlidersHorizontal class="w-3 h-3" />
                                                Adjust
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Parent Header Row for Multi-Variant Product -->
                                <template v-else>
                                    <tr class="bg-slate-50/50 dark:bg-slate-800/30 border-t border-slate-200 dark:border-slate-800">
                                        <td class="px-5 py-3" colspan="2">
                                            <div class="flex items-center gap-3">
                                                <img
                                                    :src="product.primary_image || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=100&auto=format&fit=crop&q=60'"
                                                    alt="Thumb"
                                                    class="w-8 h-8 rounded-lg object-cover border border-slate-200 dark:border-slate-700 shrink-0 bg-slate-100"
                                                />
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-bold text-slate-900 dark:text-white text-xs">{{ product.name }}</span>
                                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                                            {{ product.variants.length }} Variants
                                                        </span>
                                                    </div>
                                                    <span class="text-[10px] text-slate-400 font-mono">{{ product.sku }} • {{ product.category?.name || 'Uncategorized' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-xs font-mono text-slate-500">Threshold: {{ product.low_stock_threshold }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase">Aggregated Total</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded-md">
                                                {{ product.stock_quantity }} {{ product.unit }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <span class="text-[10px] text-slate-400 italic">Adjust individual variants below</span>
                                        </td>
                                    </tr>

                                    <!-- Variant Sub-Rows -->
                                    <tr
                                        v-for="variant in product.variants"
                                        :key="variant.id"
                                        class="hover:bg-indigo-50/30 dark:hover:bg-indigo-950/10 transition-colors bg-white dark:bg-slate-900 pl-4 border-l-4 border-l-indigo-400/40"
                                    >
                                        <td class="px-5 py-2.5 pl-12">
                                            <div class="flex items-center gap-2">
                                                <span class="text-slate-400 text-xs">↳</span>
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <span
                                                        v-for="(val, key) in (variant.option_values || {})"
                                                        :key="key"
                                                        class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded text-[10px] font-semibold border border-slate-200 dark:border-slate-700"
                                                    >
                                                        <strong class="text-slate-400">{{ key }}:</strong> {{ val }}
                                                    </span>
                                                </div>
                                                <span class="font-mono text-[10px] text-slate-500 ml-2">{{ variant.sku }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5 text-slate-400 text-[11px]">
                                            {{ product.unit }}
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <span class="text-xs font-mono text-slate-400">{{ product.low_stock_threshold }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span
                                                v-if="variant.stock_quantity <= 0"
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200"
                                            >
                                                Out of Stock
                                            </span>
                                            <span
                                                v-else-if="variant.stock_quantity <= product.low_stock_threshold"
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200"
                                            >
                                                Low Stock
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200"
                                            >
                                                In Stock
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span class="text-xs font-mono font-black text-slate-800 dark:text-slate-100">{{ variant.stock_quantity }}</span>
                                        </td>
                                        <td class="px-5 py-2.5 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button
                                                    @click="quickAdjust(product, variant, -1)"
                                                    title="Deduct 1"
                                                    class="w-6 h-6 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center text-xs transition-colors"
                                                >
                                                    <Minus class="w-3 h-3" />
                                                </button>
                                                <button
                                                    @click="quickAdjust(product, variant, 1)"
                                                    title="Add 1"
                                                    class="w-6 h-6 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center text-xs transition-colors"
                                                >
                                                    <Plus class="w-3 h-3" />
                                                </button>
                                                <button
                                                    @click="openAdjustModal(product, variant)"
                                                    class="ml-1 px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 font-bold rounded-md text-[11px] transition-colors flex items-center gap-1"
                                                >
                                                    <SlidersHorizontal class="w-2.5 h-2.5" />
                                                    Adjust
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Products -->
                <div v-if="products.links && products.links.length > 3" class="px-5 py-3.5 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Showing <span class="font-bold text-slate-800 dark:text-slate-200">{{ products.from }}</span> to <span class="font-bold text-slate-800 dark:text-slate-200">{{ products.to }}</span> of <span class="font-bold text-slate-800 dark:text-slate-200">{{ products.total }}</span> products
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in products.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
                                !link.url ? 'opacity-40 cursor-not-allowed' : '',
                                'px-3 py-1.5 rounded-lg text-xs transition-colors'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

            <!-- ================= TAB 2: STOCK MOVEMENTS AUDIT LOG ================= -->
            <div v-if="activeTab === 'audit'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                            <History class="w-4 h-4 text-indigo-500" />
                            Inventory Transaction History & Audit Ledger
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Every quantity change is permanently recorded with staff attribution and reason.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3.5">Timestamp & User</th>
                                <th class="px-4 py-3.5">Item & SKU</th>
                                <th class="px-4 py-3.5 text-center">Movement</th>
                                <th class="px-4 py-3.5 text-center">Stock Shift</th>
                                <th class="px-4 py-3.5">Reason & Reference</th>
                                <th class="px-5 py-3.5">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr v-if="!transactions.data || transactions.data.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    No stock movement records found yet.
                                </td>
                            </tr>
                            <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <p class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ formatDate(tx.created_at) }}</p>
                                    <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-medium">By: {{ tx.user?.name || 'System / Auto' }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="font-bold text-slate-900 dark:text-white text-xs">{{ tx.product?.name || 'Unknown Product' }}</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="font-mono text-[10px] text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                                            {{ tx.product_variant ? tx.product_variant.sku : tx.product?.sku }}
                                        </span>
                                        <span v-if="tx.product_variant" class="text-[10px] text-slate-400">
                                            ({{ Object.values(tx.product_variant.option_values || {}).join(', ') }})
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            tx.quantity_change > 0
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/40'
                                                : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border-rose-200 dark:border-rose-800/40',
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-mono font-bold border'
                                        ]"
                                    >
                                        <ArrowUpRight v-if="tx.quantity_change > 0" class="w-3.5 h-3.5" />
                                        <ArrowDownRight v-else class="w-3.5 h-3.5" />
                                        {{ tx.quantity_change > 0 ? `+${tx.quantity_change}` : tx.quantity_change }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center font-mono text-xs whitespace-nowrap">
                                    <span class="text-slate-400">{{ tx.previous_stock }}</span>
                                    <span class="text-slate-300 dark:text-slate-600 mx-1.5">→</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ tx.new_stock }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded text-[10px] font-bold">
                                        {{ formatReason(tx.reason) }}
                                    </span>
                                    <p v-if="tx.reference_number" class="text-[10px] text-slate-400 font-mono mt-0.5">Ref: {{ tx.reference_number }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400 text-xs">
                                    {{ tx.notes || '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Transactions -->
                <div v-if="transactions.links && transactions.links.length > 3" class="px-5 py-3.5 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Page {{ transactions.current_page }} of {{ transactions.last_page }} ({{ transactions.total }} total audit events)
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in transactions.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
                                !link.url ? 'opacity-40 cursor-not-allowed' : '',
                                'px-3 py-1.5 rounded-lg text-xs transition-colors'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

            <!-- ================= TAB 3: REPLENISHMENT / RESTOCK PLANNER ================= -->
            <div v-if="activeTab === 'restock'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                            <ShieldAlert class="w-4 h-4 text-amber-500" />
                            Stock Replenishment & Purchase Order Planner
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Showing all SKUs that have reached or fallen below safety stock thresholds.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3.5">SKU & Item Name</th>
                                <th class="px-4 py-3.5 text-center">Current Stock</th>
                                <th class="px-4 py-3.5 text-center">Safety Threshold</th>
                                <th class="px-4 py-3.5 text-center">Suggested Order Qty</th>
                                <th class="px-5 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr v-if="lowStockProducts.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                    <CheckCircle2 class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                                    <p class="font-bold text-slate-700 dark:text-slate-200 text-sm">All Stock Levels Healthy!</p>
                                    <p class="text-xs text-slate-400 mt-0.5">No products or variants currently need replenishment.</p>
                                </td>
                            </tr>
                            <tr v-for="item in lowStockProducts" :key="item.sku" class="hover:bg-amber-50/40 dark:hover:bg-amber-950/10 transition-colors">
                                <td class="px-5 py-3.5">
                                    <p class="font-bold text-slate-900 dark:text-white text-xs">{{ item.name }}</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="font-mono text-[10px] text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 px-1.5 py-0.5 rounded font-bold">
                                            {{ item.sku }}
                                        </span>
                                        <span v-if="item.option_values" class="text-[10px] text-slate-500">
                                            ({{ Object.values(item.option_values).join(', ') }})
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span
                                        :class="[
                                            item.stock_quantity <= 0 ? 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40' : 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40',
                                            'font-mono font-black text-xs px-2.5 py-1 rounded-md'
                                        ]"
                                    >
                                        {{ item.stock_quantity }} {{ item.unit }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center font-mono text-xs text-slate-600 dark:text-slate-300">
                                    {{ item.low_stock_threshold }} {{ item.unit }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded text-xs">
                                        +{{ item.deficit }} {{ item.unit }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <button
                                        @click="openAdjustModal(item.product, item.variant, 'add', item.deficit)"
                                        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-xs transition-colors inline-flex items-center gap-1.5"
                                    >
                                        <Plus class="w-3.5 h-3.5" />
                                        Restock Now
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= STOCK ADJUSTMENT MODAL ================= -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isAdjustModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 border border-slate-200 dark:border-slate-800 shadow-xl space-y-5">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                <SlidersHorizontal class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-base">Adjust Stock Level</h3>
                                <p class="text-[11px] text-slate-400">All adjustments are recorded with audit reasons.</p>
                            </div>
                        </div>
                        <button @click="isAdjustModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Selected Target Summary -->
                    <div v-if="selectedItem" class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/80 dark:border-slate-700/60">
                        <p class="font-bold text-slate-900 dark:text-white text-xs">{{ selectedItem.product.name }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-1 text-[11px]">
                            <span class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold">
                                SKU: {{ selectedItem.variant ? selectedItem.variant.sku : selectedItem.product.sku }}
                            </span>
                            <span v-if="selectedItem.variant" class="text-slate-400">
                                • ({{ Object.values(selectedItem.variant.option_values || {}).join(', ') }})
                            </span>
                            <span class="text-slate-400">• Current Stock: <strong>{{ currentStockOfSelected }} {{ selectedItem.product.unit }}</strong></span>
                        </div>
                    </div>

                    <form @submit.prevent="submitAdjustment" class="space-y-4">
                        <!-- Mode Selector -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Adjustment Action</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="adjustForm.mode = 'add'"
                                    :class="[
                                        adjustForm.mode === 'add'
                                            ? 'bg-emerald-600 text-white font-bold'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200',
                                        'py-2 px-3 rounded-xl text-xs transition-colors flex items-center justify-center gap-1.5'
                                    ]"
                                >
                                    <Plus class="w-3.5 h-3.5" />
                                    Add (+)
                                </button>
                                <button
                                    type="button"
                                    @click="adjustForm.mode = 'remove'"
                                    :class="[
                                        adjustForm.mode === 'remove'
                                            ? 'bg-rose-600 text-white font-bold'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200',
                                        'py-2 px-3 rounded-xl text-xs transition-colors flex items-center justify-center gap-1.5'
                                    ]"
                                >
                                    <Minus class="w-3.5 h-3.5" />
                                    Remove (-)
                                </button>
                                <button
                                    type="button"
                                    @click="adjustForm.mode = 'set'"
                                    :class="[
                                        adjustForm.mode === 'set'
                                            ? 'bg-indigo-600 text-white font-bold'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200',
                                        'py-2 px-3 rounded-xl text-xs transition-colors flex items-center justify-center gap-1.5'
                                    ]"
                                >
                                    <RefreshCw class="w-3.5 h-3.5" />
                                    Set Exact (=)
                                </button>
                            </div>
                        </div>

                        <!-- Quantity and Live Calculation Preview -->
                        <div class="grid grid-cols-2 gap-3 items-center">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    {{ adjustForm.mode === 'set' ? 'New Exact Count *' : 'Quantity Units *' }}
                                </label>
                                <input
                                    v-model="adjustForm.quantity"
                                    type="number"
                                    min="0"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 font-bold focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Calculation Preview</label>
                                <div class="px-3.5 py-2.5 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/40 rounded-xl text-xs font-mono font-bold text-indigo-700 dark:text-indigo-300 flex items-center justify-between">
                                    <span>{{ currentStockOfSelected }}</span>
                                    <span>{{ adjustForm.mode === 'add' ? `+ ${adjustForm.quantity}` : (adjustForm.mode === 'remove' ? `- ${adjustForm.quantity}` : '➔') }}</span>
                                    <span class="text-sm font-black">{{ previewNewStock }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Audit Reason -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Audit Reason *</label>
                            <CustomSelect
                                v-model="adjustForm.reason"
                                :options="reasonOptions"
                                placeholder="Select Reason"
                            />
                        </div>

                        <!-- Reference Number -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Reference Number (PO / Order / Audit #)</label>
                            <input
                                v-model="adjustForm.reference_number"
                                type="text"
                                placeholder="e.g. PO-2026-089"
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- Staff Notes -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Internal Notes</label>
                            <textarea
                                v-model="adjustForm.notes"
                                rows="2"
                                placeholder="Optional explanation for audit records..."
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            ></textarea>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <button
                                type="button"
                                @click="isAdjustModalOpen = false"
                                class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="adjustForm.processing"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2"
                            >
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                Save Stock Adjustment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
