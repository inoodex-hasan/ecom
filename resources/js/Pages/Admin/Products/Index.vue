<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Plus,
    Search,
    Filter,
    Edit3,
    Trash2,
    Package,
    AlertCircle,
    CheckCircle2,
    X,
    UploadCloud,
    SlidersHorizontal,
    ArrowUpDown,
    ExternalLink,
    Download,
    Shirt,
    Hammer,
    FlaskConical,
    Cpu,
    Boxes,
    Ruler,
    Scale,
    Shield,
    FileText,
    Sparkles,
    ChevronDown,
    ChevronUp,
    Copy,
    Tag,
    Flame,
    Zap
} from 'lucide-vue-next';
import CustomSelect from '@/Components/CustomSelect.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    brands: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.product_type || '');
const selectedCategory = ref(props.filters?.category || '');
const selectedStatus = ref(props.filters?.status || '');
const onlyLowStock = ref(props.filters?.low_stock === 'true' || props.filters?.low_stock === true);

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const productToEdit = ref(null);
const activeModalTab = ref('general'); // 'general' | 'specs' | 'units' | 'variants'

// Industry Type Configurations
const productTypes = [
    { key: 'standard', label: 'Standard Retail', icon: Package, color: 'text-slate-600 bg-slate-100 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700', desc: 'General consumer goods, accessories, packaged items' },
    { key: 'apparel', label: 'Apparel & Fashion', icon: Shirt, color: 'text-purple-600 bg-purple-50 dark:bg-purple-950/40 dark:text-purple-300 border-purple-200 dark:border-purple-800', desc: 'Clothing, footwear, fabrics, sizes & colors' },
    { key: 'building_material', label: 'Building & Hardware', icon: Hammer, color: 'text-amber-700 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-300 border-amber-200 dark:border-amber-800', desc: 'Tiles, wood, cement, pipes, steel, coverage/sqft' },
    { key: 'liquid', label: 'Liquids & Chemicals', icon: FlaskConical, color: 'text-cyan-700 bg-cyan-50 dark:bg-cyan-950/40 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800', desc: 'Oils, beverages, paints, perfumes, volume & hazmat' },
    { key: 'electronics', label: 'Electronics & Tech', icon: Cpu, color: 'text-blue-600 bg-blue-50 dark:bg-blue-950/40 dark:text-blue-300 border-blue-200 dark:border-blue-800', desc: 'Gadgets, appliances, warranty, technical specs' },
];

const unitsList = [
    { value: 'piece', label: 'Piece (pc)' },
    { value: 'box', label: 'Box (bx)' },
    { value: 'pack', label: 'Pack (pk)' },
    { value: 'set', label: 'Set' },
    { value: 'pair', label: 'Pair (pr)' },
    { value: 'sqft', label: 'Square Feet (sq ft)' },
    { value: 'sqm', label: 'Square Meter (sq m)' },
    { value: 'meter', label: 'Linear Meter (m)' },
    { value: 'yard', label: 'Yard (yd)' },
    { value: 'kg', label: 'Kilogram (kg)' },
    { value: 'gram', label: 'Gram (g)' },
    { value: 'ton', label: 'Metric Ton (t)' },
    { value: 'bag', label: 'Bag / Sack' },
    { value: 'bundle', label: 'Bundle' },
    { value: 'roll', label: 'Roll' },
    { value: 'liter', label: 'Liter (L)' },
    { value: 'ml', label: 'Milliliter (ml)' },
    { value: 'gallon', label: 'Gallon (gal)' },
    { value: 'drum', label: 'Drum (55 gal)' },
];

function getTypeConfig(type) {
    return productTypes.find(t => t.key === type) || productTypes[0];
}

const form = useForm({
    product_type: 'standard',
    name: '',
    sku: '',
    category_id: '',
    brand_id: '',
    price: '',
    compare_price: '',
    cost_price: '',
    unit: 'piece',
    min_order_quantity: 1,
    quantity_step: 1,
    unit_coverage_value: '',
    stock_quantity: 10,
    low_stock_threshold: 5,
    weight: '',
    weight_unit: 'kg',
    length: '',
    width: '',
    height: '',
    dimension_unit: 'cm',
    status: 'published',
    is_featured: false,
    badge_label: '',
    is_hot: false,
    is_trending: false,
    is_new_arrival: false,
    primary_image: null,
    short_description: '',
    description: '',
    attributes: {},
    has_variants: false,
    variants: [],
});

// Image Upload
const imagePreview = ref(null);
const isUploadingImage = ref(false);

async function handleImageUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Show local preview immediately
    const reader = new FileReader();
    reader.onload = (ev) => { imagePreview.value = ev.target.result; };
    reader.readAsDataURL(file);

    // Upload to server, get back the stored URL
    isUploadingImage.value = true;
    const data = new FormData();
    data.append('image', file);
    try {
        const res = await axios.post(route('admin.products.upload-image'), data, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
        });
        form.primary_image = res.data.url; // store URL string in form
    } catch (err) {
        console.error('Image upload failed', err);
        imagePreview.value = null;
        form.primary_image = null;
    } finally {
        isUploadingImage.value = false;
    }
}

function clearImageUpload() {
    form.primary_image = null;
    imagePreview.value = null;
}

// Dynamic Spec Builders
const rawSpecRows = ref([{ key: '', value: '' }]);

// Variant Generator State
const variantOption1Name = ref('Size');
const variantOption1Values = ref('');
const variantOption2Name = ref('Color');
const variantOption2Values = ref('');

function generateVariantMatrix() {
    const opt1Vals = variantOption1Values.value.split(',').map(s => s.trim()).filter(Boolean);
    const opt2Vals = variantOption2Values.value.split(',').map(s => s.trim()).filter(Boolean);

    if (opt1Vals.length === 0 && opt2Vals.length === 0) return;

    const matrix = [];
    const baseSku = form.sku || 'SKU';
    const basePrice = Number(form.price) || 0;
    const baseCost = Number(form.cost_price) || 0;
    const baseStock = Math.floor((Number(form.stock_quantity) || 10) / Math.max(1, opt1Vals.length * Math.max(1, opt2Vals.length)));

    if (opt1Vals.length > 0 && opt2Vals.length > 0) {
        opt1Vals.forEach(v1 => {
            opt2Vals.forEach(v2 => {
                const code = `${v1.substring(0, 3)}-${v2.substring(0, 3)}`.toUpperCase();
                matrix.push({
                    sku: `${baseSku}-${code}`,
                    price: basePrice,
                    compare_price: form.compare_price || null,
                    cost_price: baseCost || null,
                    stock_quantity: baseStock,
                    option_values: { [variantOption1Name.value]: v1, [variantOption2Name.value]: v2 },
                    is_active: true,
                });
            });
        });
    } else {
        const activeName = opt1Vals.length > 0 ? variantOption1Name.value : variantOption2Name.value;
        const activeVals = opt1Vals.length > 0 ? opt1Vals : opt2Vals;
        activeVals.forEach(v => {
            const code = v.substring(0, 4).toUpperCase();
            matrix.push({
                sku: `${baseSku}-${code}`,
                price: basePrice,
                compare_price: form.compare_price || null,
                cost_price: baseCost || null,
                stock_quantity: baseStock,
                option_values: { [activeName]: v },
                is_active: true,
            });
        });
    }

    form.variants = matrix;
    form.has_variants = matrix.length > 0;
}

function addSingleVariant() {
    form.has_variants = true;
    form.variants.push({
        sku: `${form.sku || 'SKU'}-VAR${form.variants.length + 1}`,
        price: Number(form.price) || 0,
        compare_price: null,
        cost_price: null,
        stock_quantity: 5,
        option_values: { 'Option': 'Value' },
        is_active: true,
    });
}

function removeVariant(index) {
    form.variants.splice(index, 1);
    if (form.variants.length === 0) {
        form.has_variants = false;
    }
}

function addSpecRow() {
    rawSpecRows.value.push({ key: '', value: '' });
}

function removeSpecRow(idx) {
    rawSpecRows.value.splice(idx, 1);
}

function applyFilters() {
    router.get(
        route('admin.products.index'),
        {
            search: search.value || undefined,
            product_type: selectedType.value || undefined,
            category: selectedCategory.value || undefined,
            status: selectedStatus.value || undefined,
            low_stock: onlyLowStock.value ? true : undefined,
        },
        { preserveState: true, replace: true }
    );
}

let timeout;
watch(search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(applyFilters, 300);
});

function openCreateModal() {
    form.reset();
    form.product_type = selectedType.value || 'standard';
    form.attributes = {};
    form.variants = [];
    form.has_variants = false;
    form.badge_label = '';
    form.is_hot = false;
    form.is_trending = false;
    form.is_new_arrival = false;
    rawSpecRows.value = [{ key: '', value: '' }];
    activeModalTab.value = 'general';
    isCreateModalOpen.value = true;
}

function openEditModal(prod) {
    productToEdit.value = prod;
    form.product_type = prod.product_type || 'standard';
    form.name = prod.name;
    form.sku = prod.sku;
    form.category_id = prod.category_id || '';
    form.brand_id = prod.brand_id || '';
    form.price = prod.price;
    form.compare_price = prod.compare_price;
    form.cost_price = prod.cost_price;
    form.unit = prod.unit || 'piece';
    form.min_order_quantity = prod.min_order_quantity || 1;
    form.quantity_step = prod.quantity_step || 1;
    form.unit_coverage_value = prod.unit_coverage_value || '';
    form.stock_quantity = prod.stock_quantity;
    form.low_stock_threshold = prod.low_stock_threshold;
    form.weight = prod.weight || '';
    form.weight_unit = prod.weight_unit || 'kg';
    form.length = prod.length || '';
    form.width = prod.width || '';
    form.height = prod.height || '';
    form.dimension_unit = prod.dimension_unit || 'cm';
    form.status = prod.status;
    form.is_featured = !!prod.is_featured;
    form.badge_label = prod.badge_label || '';
    form.is_hot = Boolean(prod.is_hot);
    form.is_trending = Boolean(prod.is_trending);
    form.is_new_arrival = Boolean(prod.is_new_arrival);
    form.primary_image = prod.primary_image;
    form.short_description = prod.short_description;
    form.description = prod.description;
    form.attributes = prod.attributes || {};
    form.has_variants = !!prod.has_variants;
    form.variants = prod.variants ? prod.variants.map(v => ({
        id: v.id,
        sku: v.sku,
        price: v.price,
        compare_price: v.compare_price,
        cost_price: v.cost_price,
        stock_quantity: v.stock_quantity,
        option_values: v.option_values || {},
        is_active: v.is_active,
    })) : [];

    // Parse specs for electronics/tech
    if (prod.attributes?.specs && Array.isArray(prod.attributes.specs)) {
        rawSpecRows.value = prod.attributes.specs;
    } else {
        rawSpecRows.value = [{ key: '', value: '' }];
    }

    imagePreview.value = null; // reset file upload preview
    activeModalTab.value = 'general';
    isEditModalOpen.value = true;
}

function syncAttributesBeforeSubmit() {
    if (form.product_type === 'electronics') {
        const filteredSpecs = rawSpecRows.value.filter(r => r.key && r.value);
        form.attributes.specs = filteredSpecs;
    }
    if (form.has_variants) {
        form.stock_quantity = formVariantStockTotal();
    }
}

function submitCreate() {
    syncAttributesBeforeSubmit();
    form.post(route('admin.products.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            imagePreview.value = null;
            form.reset();
        },
    });
}

function submitUpdate() {
    syncAttributesBeforeSubmit();
    form.put(route('admin.products.update', productToEdit.value.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            imagePreview.value = null;
            productToEdit.value = null;
        },
    });
}

function deleteProduct(prod) {
    if (confirm(`Are you sure you want to delete "${prod.name}"?`)) {
        router.delete(route('admin.products.destroy', prod.id));
    }
}

function quickStock(prod, delta) {
    if (prod.has_variants) return; // stock is managed per-variant
    const newQty = Math.max(0, prod.stock_quantity + delta);
    router.patch(route('admin.products.quick-update', prod.id), {
        stock_quantity: newQty,
    }, { preserveScroll: true });
}

function totalVariantStock(prod) {
    if (!prod.variants || prod.variants.length === 0) return prod.stock_quantity;
    return prod.variants.reduce((sum, v) => sum + (Number(v.stock_quantity) || 0), 0);
}

function formVariantStockTotal() {
    return form.variants.reduce((sum, v) => sum + (Number(v.stock_quantity) || 0), 0);
}

const formatCurrency = (val) => {
    return '৳' + Number(val || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Products Catalog - Universal Admin" />

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        Product Catalog <Sparkles class="w-4 h-4 text-indigo-500" />
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Universal multi-industry inventory and catalog manager</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Top Industry Type Switcher Bar -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <button
                    @click="selectedType = ''; applyFilters()"
                    :class="[
                        selectedType === ''
                            ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-bold'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800',
                        'px-3.5 py-2 rounded-2xl text-xs flex items-center gap-2 shrink-0 transition-all cursor-pointer'
                    ]"
                >
                    <Boxes class="w-3.5 h-3.5" />
                    <span>All Product Types</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono bg-black/10 dark:bg-white/10">{{ products.total }}</span>
                </button>

                <button
                    v-for="pType in productTypes"
                    :key="pType.key"
                    @click="selectedType = pType.key; applyFilters()"
                    :class="[
                        selectedType === pType.key
                            ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-bold'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800',
                        'px-3.5 py-2 rounded-2xl text-xs flex items-center gap-2 shrink-0 transition-all cursor-pointer'
                    ]"
                >
                    <component :is="pType.icon" class="w-3.5 h-3.5" />
                    <span>{{ pType.label }}</span>
                </button>
            </div>

            <!-- Integrated Filter Action Bar with Export -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col xl:flex-row gap-3 items-center justify-between">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full xl:w-auto">
                    <!-- Search -->
                    <div class="relative w-full sm:w-80">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search product, SKU, or specs..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Select Filters -->
                    <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                        <!-- Category Filter -->
                        <div class="w-full sm:w-44">
                            <CustomSelect
                                v-model="selectedCategory"
                                @change="applyFilters"
                                :options="[
                                    { value: '', label: 'All Categories' },
                                    ...categories.map(c => ({ value: c.id, label: c.name }))
                                ]"
                                placeholder="All Categories"
                                compact
                            />
                        </div>

                        <!-- Status Filter -->
                        <div class="w-full sm:w-36">
                            <CustomSelect
                                v-model="selectedStatus"
                                @change="applyFilters"
                                :options="[
                                    { value: '', label: 'All Statuses' },
                                    { value: 'published', label: 'Published' },
                                    { value: 'draft', label: 'Draft' },
                                    { value: 'archived', label: 'Archived' },
                                ]"
                                placeholder="All Statuses"
                                compact
                            />
                        </div>

                        <!-- Low Stock Toggle -->
                        <button
                            @click="onlyLowStock = !onlyLowStock; applyFilters()"
                            :class="[
                                onlyLowStock
                                    ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold ring-1 ring-amber-500/30'
                                    : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100',
                                'px-3.5 py-2 rounded-2xl text-xs flex items-center gap-1.5 transition-colors cursor-pointer'
                            ]"
                        >
                            <AlertCircle class="w-3.5 h-3.5" />
                            Low Stock
                        </button>
                    </div>
                </div>

                <!-- Integrated Action Buttons -->
                <div class="w-full xl:w-auto flex flex-wrap items-center justify-end gap-2">
                    <a
                        :href="route('admin.products.export', { format: 'xlsx', product_type: selectedType || undefined, search: search || undefined, category: selectedCategory || undefined, status: selectedStatus || undefined, low_stock: onlyLowStock ? 'true' : undefined })"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors shrink-0"
                        title="Export Products as Excel"
                    >
                        <Download class="w-3.5 h-3.5" />
                        <span>Excel</span>
                    </a>
                    <a
                        :href="route('admin.products.export', { format: 'csv', product_type: selectedType || undefined, search: search || undefined, category: selectedCategory || undefined, status: selectedStatus || undefined, low_stock: onlyLowStock ? 'true' : undefined })"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-xs transition-colors shrink-0"
                        title="Export Products as CSV"
                    >
                        <Download class="w-3.5 h-3.5" />
                        <span>CSV</span>
                    </a>
                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all cursor-pointer shrink-0"
                    >
                        <Plus class="w-4 h-4" /> Add Product
                    </button>
                </div>
            </div>

            <!-- Products Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-[11px] uppercase text-slate-500 dark:text-slate-400 font-bold tracking-wider border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="px-6 py-4">Product</th>
                                <th class="px-5 py-4">Type / Specs</th>
                                <th class="px-5 py-4">SKU / UOM</th>
                                <th class="px-5 py-4">Category</th>
                                <th class="px-5 py-4">Price / Unit</th>
                                <th class="px-5 py-4">Stock</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr
                                v-for="prod in products.data"
                                :key="prod.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group"
                            >
                                <!-- Name & Image -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="prod.primary_image || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=100'"
                                            alt="Product"
                                            class="w-12 h-12 rounded-2xl object-cover bg-slate-100 dark:bg-slate-800 shrink-0 border border-slate-200/50 dark:border-slate-700/50 shadow-xs"
                                        />
                                        <div class="truncate max-w-[200px]">
                                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                                {{ prod.name }}
                                            </p>
                                            <div class="flex flex-wrap items-center gap-1 mt-1">
                                                <span v-if="prod.is_featured" class="inline-flex items-center text-[10px] font-bold text-amber-600 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-400 px-1.5 py-0.5 rounded-md">
                                                    ★ Featured
                                                </span>
                                                <span v-if="prod.is_hot" class="inline-flex items-center gap-0.5 text-[10px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-400 px-1.5 py-0.5 rounded-md">
                                                    <Flame class="w-2.5 h-2.5" /> HOT
                                                </span>
                                                <span v-if="prod.is_trending" class="inline-flex items-center gap-0.5 text-[10px] font-bold text-amber-600 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-400 px-1.5 py-0.5 rounded-md">
                                                    <Zap class="w-2.5 h-2.5" /> TRENDING
                                                </span>
                                                <span v-if="prod.is_new_arrival" class="inline-flex items-center gap-0.5 text-[10px] font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400 px-1.5 py-0.5 rounded-md">
                                                    <Sparkles class="w-2.5 h-2.5" /> NEW
                                                </span>
                                                <span v-if="prod.badge_label" class="inline-flex items-center text-[10px] font-bold text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 dark:text-indigo-400 px-1.5 py-0.5 rounded-md">
                                                    {{ prod.badge_label }}
                                                </span>
                                                <span v-if="prod.has_variants && prod.variants_count > 0" class="inline-flex items-center gap-1 text-[10px] font-semibold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded-md">
                                                    <Tag class="w-2.5 h-2.5" /> {{ prod.variants_count }} vars
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Product Type & Specs Pill -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1 items-start">
                                        <span
                                            :class="[
                                                getTypeConfig(prod.product_type).color,
                                                'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border'
                                            ]"
                                        >
                                            <component :is="getTypeConfig(prod.product_type).icon" class="w-3 h-3" />
                                            {{ getTypeConfig(prod.product_type).label }}
                                        </span>
                                        <span v-if="prod.attributes?.fabric" class="text-[10px] text-slate-500 dark:text-slate-400 truncate max-w-[120px]">
                                            {{ prod.attributes.fabric }}
                                        </span>
                                        <span v-else-if="prod.attributes?.volume_ml" class="text-[10px] text-slate-500 dark:text-slate-400">
                                            {{ prod.attributes.volume_ml }} · {{ prod.attributes.packaging_type || 'Liquid' }}
                                        </span>
                                        <span v-else-if="prod.unit_coverage_value" class="text-[10px] text-slate-500 dark:text-slate-400">
                                            Cov: {{ prod.unit_coverage_value }} sqft/{{ prod.unit }}
                                        </span>
                                    </div>
                                </td>

                                <!-- SKU & UOM -->
                                <td class="px-5 py-4">
                                    <p class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">{{ prod.sku }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Unit: <span class="font-medium text-slate-600 dark:text-slate-300">{{ prod.unit || 'piece' }}</span></p>
                                </td>

                                <!-- Category & Brand -->
                                <td class="px-5 py-4">
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ prod.category?.name || 'Uncategorized' }}</p>
                                    <p v-if="prod.brand?.name" class="text-[10px] text-slate-400 mt-0.5">{{ prod.brand.name }}</p>
                                </td>

                                <!-- Price & UOM -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-xs font-black text-slate-900 dark:text-white">
                                                {{ formatCurrency(prod.price) }}
                                            </span>
                                            <span class="text-[10px] font-semibold text-slate-400">
                                                / {{ prod.unit || 'pc' }}
                                            </span>
                                        </div>
                                        <span v-if="prod.compare_price" class="text-[10px] text-slate-400 line-through">
                                            {{ formatCurrency(prod.compare_price) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Stock Controls -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            @click="quickStock(prod, -1)"
                                            :disabled="prod.has_variants"
                                            :class="prod.has_variants ? 'opacity-30 cursor-not-allowed' : 'hover:bg-slate-200 dark:hover:bg-slate-700'"
                                            class="w-5 h-5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold flex items-center justify-center text-xs transition-colors"
                                        >
                                            -
                                        </button>
                                        <span
                                            :class="[
                                                totalVariantStock(prod) <= prod.low_stock_threshold
                                                    ? 'text-rose-600 dark:text-rose-400 font-bold bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 rounded-md'
                                                    : 'text-slate-800 dark:text-slate-200',
                                                'text-xs font-mono font-semibold'
                                            ]"
                                        >
                                            {{ totalVariantStock(prod) }}
                                            <span v-if="prod.has_variants" class="text-[9px] text-slate-400 font-normal ml-0.5">({{ prod.variants_count }}v)</span>
                                        </span>
                                        <button
                                            @click="quickStock(prod, 1)"
                                            :disabled="prod.has_variants"
                                            :class="prod.has_variants ? 'opacity-30 cursor-not-allowed' : 'hover:bg-slate-200 dark:hover:bg-slate-700'"
                                            class="w-5 h-5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold flex items-center justify-center text-xs transition-colors"
                                        >
                                            +
                                        </button>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            prod.status === 'published'
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/40'
                                                : prod.status === 'draft'
                                                ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border-amber-200 dark:border-amber-800/40'
                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                                            'px-2.5 py-1 rounded-full text-[10px] font-bold border capitalize'
                                        ]"
                                    >
                                        {{ prod.status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            @click="openEditModal(prod)"
                                            class="p-1.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-all"
                                            title="Edit Product"
                                        >
                                            <Edit3 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="deleteProduct(prod)"
                                            class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-all"
                                            title="Delete Product"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="products.links?.length > 3" class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Showing {{ products.from }} to {{ products.to }} of {{ products.total }} products
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in products.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white font-bold'
                                    : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-750',
                                !link.url ? 'opacity-50 cursor-not-allowed' : '',
                                'px-3 py-1.5 rounded-xl text-xs transition-colors'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Universal Multi-Industry Product Modal Dialog -->
        <div
            v-if="isCreateModalOpen || isEditModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto"
        >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-4xl overflow-hidden shadow-2xl my-6 flex flex-col max-h-[92vh]">
                
                <!-- Modal Header with Industry Type Selector -->
                <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex-shrink-0">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/20">
                                <component :is="getTypeConfig(form.product_type).icon" class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">
                                    {{ isEditModalOpen ? 'Edit Product' : 'Add New Product' }}
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ getTypeConfig(form.product_type).desc }}
                                </p>
                            </div>
                        </div>
                        <button @click="isCreateModalOpen = false; isEditModalOpen = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Product Type Pill Switcher -->
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                        <button
                            v-for="pType in productTypes"
                            :key="pType.key"
                            type="button"
                            @click="form.product_type = pType.key"
                            :class="[
                                form.product_type === pType.key
                                    ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-bold ring-2 ring-indigo-600/30'
                                    : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 hover:bg-slate-100',
                                'flex items-center gap-2 p-2.5 rounded-xl border text-xs text-left transition-all cursor-pointer'
                            ]"
                        >
                            <component :is="pType.icon" class="w-4 h-4 shrink-0" />
                            <span class="truncate">{{ pType.label }}</span>
                        </button>
                    </div>

                    <!-- Modal Navigation Tabs -->
                    <div class="flex items-center gap-2 mt-4 border-b border-slate-200/80 dark:border-slate-800 pt-2 overflow-x-auto scrollbar-none">
                        <button
                            type="button"
                            @click="activeModalTab = 'general'"
                            :class="[
                                activeModalTab === 'general'
                                    ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold'
                                    : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200',
                                'pb-2.5 px-3 border-b-2 text-xs transition-colors shrink-0'
                            ]"
                        >
                            1. General & Pricing
                        </button>
                        <button
                            type="button"
                            @click="activeModalTab = 'specs'"
                            :class="[
                                activeModalTab === 'specs'
                                    ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold'
                                    : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200',
                                'pb-2.5 px-3 border-b-2 text-xs transition-colors shrink-0 flex items-center gap-1.5'
                            ]"
                        >
                            <span>2. {{ getTypeConfig(form.product_type).label }} Specs</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                        </button>
                        <button
                            type="button"
                            @click="activeModalTab = 'units'"
                            :class="[
                                activeModalTab === 'units'
                                    ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold'
                                    : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200',
                                'pb-2.5 px-3 border-b-2 text-xs transition-colors shrink-0'
                            ]"
                        >
                            3. Units & Freight Dimensions
                        </button>
                        <button
                            type="button"
                            @click="activeModalTab = 'variants'"
                            :class="[
                                activeModalTab === 'variants'
                                    ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold'
                                    : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200',
                                'pb-2.5 px-3 border-b-2 text-xs transition-colors shrink-0 flex items-center gap-1.5'
                            ]"
                        >
                            <span>4. Variants Matrix</span>
                            <span v-if="form.variants.length > 0" class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300">
                                {{ form.variants.length }}
                            </span>
                        </button>
                    </div>
                </div>

                <form @submit.prevent="isEditModalOpen ? submitUpdate() : submitCreate()" class="flex-1 overflow-y-auto p-6 space-y-6">
                    
                    <!-- ── TAB 1: GENERAL & PRICING ──────────────────────────────── -->
                    <div v-show="activeModalTab === 'general'" class="space-y-4">
                        <!-- Title & SKU -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Product Name *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="e.g. Italian Glazed Floor Tile or Oversized Hoodie"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">SKU Code *</label>
                                <input
                                    v-model="form.sku"
                                    type="text"
                                    required
                                    placeholder="e.g. TILE-PORC-6060"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 uppercase font-mono"
                                />
                            </div>
                        </div>

                        <!-- Category & Brand -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                                <CustomSelect
                                    v-model="form.category_id"
                                    :options="[
                                        { value: '', label: 'Select Category' },
                                        ...categories.map(cat => ({ value: cat.id, label: cat.name }))
                                    ]"
                                    placeholder="Select Category"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Brand / Manufacturer</label>
                                <CustomSelect
                                    v-model="form.brand_id"
                                    :options="[
                                        { value: '', label: 'Select Brand' },
                                        ...brands.map(brand => ({ value: brand.id, label: brand.name }))
                                    ]"
                                    placeholder="Select Brand"
                                />
                            </div>
                        </div>

                        <!-- Pricing & Base Unit -->
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-750 space-y-3">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <Tag class="w-3.5 h-3.5 text-indigo-500" /> Pricing & Sales Unit
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Sales Price (৳) *</label>
                                    <input
                                        v-model="form.price"
                                        type="number"
                                        step="0.01"
                                        required
                                        placeholder="0.00"
                                        class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Compare / MRP (৳)</label>
                                    <input
                                        v-model="form.compare_price"
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                        class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Cost Price (৳)</label>
                                    <input
                                        v-model="form.cost_price"
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                        class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Sold Per (UOM)</label>
                                    <CustomSelect
                                        v-model="form.unit"
                                        :options="unitsList"
                                        placeholder="Select Unit"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Inventory & Stock Thresholds -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    {{ form.has_variants ? 'Total Stock (auto from variants)' : `Stock Quantity (${form.unit}) *` }}
                                </label>
                                <!-- Computed read-only total when variants are active -->
                                <div v-if="form.has_variants" class="w-full px-3.5 py-2.5 bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-2xl text-xs font-bold text-slate-500 dark:text-slate-400 flex items-center gap-2">
                                    <span class="text-slate-800 dark:text-slate-200 font-mono text-sm">{{ formVariantStockTotal() }}</span>
                                    <span class="text-[10px]">units across {{ form.variants.length }} variants</span>
                                </div>
                                <input
                                    v-else
                                    v-model="form.stock_quantity"
                                    type="number"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 font-bold"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Low Stock Threshold</label>
                                <input
                                    v-model="form.low_stock_threshold"
                                    type="number"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- Primary Image Upload -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Primary Image</label>
                            <div class="flex items-start gap-4">
                                <!-- Preview -->
                                <div class="relative flex-shrink-0">
                                    <div class="w-24 h-24 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-600 overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                        <img
                                            v-if="imagePreview || (typeof form.primary_image === 'string' && form.primary_image)"
                                            :src="imagePreview || form.primary_image"
                                            class="w-full h-full object-cover"
                                            alt="Preview"
                                        />
                                        <svg v-else class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <button
                                        v-if="imagePreview || form.primary_image"
                                        @click="clearImageUpload"
                                        type="button"
                                        class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center text-white shadow"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <!-- Upload Area -->
                                <label class="flex-1 cursor-pointer">
                                    <div class="w-full px-4 py-4 bg-slate-50 dark:bg-slate-800 border-2 border-dashed border-slate-300 dark:border-slate-600 hover:border-indigo-400 dark:hover:border-indigo-500 rounded-2xl text-center transition-colors">
                                        <template v-if="isUploadingImage">
                                            <svg class="w-6 h-6 mx-auto mb-1.5 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                            <p class="text-xs font-semibold text-indigo-500">Uploading…</p>
                                        </template>
                                        <template v-else>
                                            <svg class="w-6 h-6 mx-auto mb-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Click to upload</p>
                                            <p class="text-xs text-slate-400 mt-0.5">JPG, PNG, WebP — max 2MB</p>
                                        </template>
                                    </div>
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        class="hidden"
                                        @change="handleImageUpload"
                                    />
                                </label>
                            </div>
                            <p v-if="form.errors.primary_image" class="mt-1 text-xs text-red-500">{{ form.errors.primary_image }}</p>
                        </div>

                        <!-- Status & Featured -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Publication Status</label>
                                <CustomSelect
                                    v-model="form.status"
                                    :options="[
                                        { value: 'published', label: 'Published' },
                                        { value: 'draft', label: 'Draft' },
                                        { value: 'archived', label: 'Archived' },
                                    ]"
                                    placeholder="Select Status"
                                />
                            </div>
                            <div class="flex items-center gap-2.5 pt-5">
                                <input
                                    v-model="form.is_featured"
                                    type="checkbox"
                                    id="is_featured"
                                    class="w-4 h-4 rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <label for="is_featured" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">Feature in storefront showcases</label>
                            </div>
                        </div>

                        <!-- Merchandising & Badges -->
                        <div class="p-4 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <Sparkles class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" /> Merchandising & Badges
                                </h4>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Highlight this product across storefront collections</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer hover:border-rose-300 dark:hover:border-rose-700 transition-colors">
                                    <input
                                        v-model="form.is_hot"
                                        type="checkbox"
                                        class="w-4 h-4 rounded-md border-slate-300 text-rose-600 focus:ring-rose-500"
                                    />
                                    <div class="flex items-center gap-1 text-xs font-bold text-slate-700 dark:text-slate-200">
                                        <Flame class="w-3.5 h-3.5 text-rose-500" /> Hot Sale
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer hover:border-amber-300 dark:hover:border-amber-700 transition-colors">
                                    <input
                                        v-model="form.is_trending"
                                        type="checkbox"
                                        class="w-4 h-4 rounded-md border-slate-300 text-amber-600 focus:ring-amber-500"
                                    />
                                    <div class="flex items-center gap-1 text-xs font-bold text-slate-700 dark:text-slate-200">
                                        <Zap class="w-3.5 h-3.5 text-amber-500" /> Trending
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors">
                                    <input
                                        v-model="form.is_new_arrival"
                                        type="checkbox"
                                        class="w-4 h-4 rounded-md border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <div class="flex items-center gap-1 text-xs font-bold text-slate-700 dark:text-slate-200">
                                        <Sparkles class="w-3.5 h-3.5 text-emerald-500" /> New Arrival
                                    </div>
                                </label>
                            </div>
                            <div class="pt-1">
                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Custom Badge Ribbon (e.g. "50% OFF", "Clearance", "Bestseller")</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="form.badge_label"
                                        type="text"
                                        maxlength="50"
                                        placeholder="Optional ribbon badge"
                                        class="flex-1 px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                                    />
                                    <span v-if="form.badge_label" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 text-white shadow-xs">
                                        {{ form.badge_label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Description (Rich Text Editor) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Product Description</label>
                                <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">Rich Text (Tiptap)</span>
                            </div>
                            <RichTextEditor
                                v-model="form.description"
                                placeholder="Enter detailed product description, specifications, and highlights..."
                                min-height="160px"
                            />
                        </div>
                    </div>

                    <!-- ── TAB 2: INDUSTRY-SPECIFIC SPECIFICATIONS ───────────────── -->
                    <div v-show="activeModalTab === 'specs'" class="space-y-4">
                        
                        <!-- 👗 APPAREL / FASHION SPECIFICATIONS -->
                        <div v-if="form.product_type === 'apparel'" class="p-5 rounded-3xl bg-purple-500/5 border border-purple-500/20 space-y-4">
                            <h4 class="text-sm font-black text-purple-950 dark:text-purple-300 flex items-center gap-2">
                                <Shirt class="w-4 h-4 text-purple-600" /> Fashion & Apparel Specifications
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Fabric Composition</label>
                                    <input
                                        v-model="form.attributes.fabric"
                                        type="text"
                                        placeholder="e.g. 100% Organic Ring-Spun Cotton, 240 GSM"
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Fit Type</label>
                                    <CustomSelect
                                        v-model="form.attributes.fit_type"
                                        :options="[
                                            { value: 'Regular Fit', label: 'Regular Fit' },
                                            { value: 'Slim Fit', label: 'Slim Fit' },
                                            { value: 'Oversized', label: 'Oversized / Boxy' },
                                            { value: 'Relaxed', label: 'Relaxed Fit' },
                                            { value: 'Athletic', label: 'Athletic Fit' }
                                        ]"
                                        placeholder="Select Fit Type"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Target Gender</label>
                                    <CustomSelect
                                        v-model="form.attributes.gender"
                                        :options="[
                                            { value: 'Unisex', label: 'Unisex' },
                                            { value: 'Men', label: 'Men' },
                                            { value: 'Women', label: 'Women' },
                                            { value: 'Kids', label: 'Kids & Youth' }
                                        ]"
                                        placeholder="Select Gender"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Care & Washing Instructions</label>
                                    <input
                                        v-model="form.attributes.care_instructions"
                                        type="text"
                                        placeholder="e.g. Machine wash cold 30°C, do not tumble dry"
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- 🧱 BUILDING & CONSTRUCTION SPECIFICATIONS -->
                        <div v-else-if="form.product_type === 'building_material'" class="p-5 rounded-3xl bg-amber-500/5 border border-amber-500/20 space-y-4">
                            <h4 class="text-sm font-black text-amber-950 dark:text-amber-300 flex items-center gap-2">
                                <Hammer class="w-4 h-4 text-amber-600" /> Building & Construction Specifications
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Material Grade / Standard</label>
                                    <input
                                        v-model="form.attributes.grade"
                                        type="text"
                                        placeholder="e.g. Class 4 Porcelain / Grade 60 Rebar"
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Surface Finish / Texture</label>
                                    <input
                                        v-model="form.attributes.finish"
                                        type="text"
                                        placeholder="e.g. Matte Glazed, Anti-Slip R10, Polished"
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Thickness / Gauge</label>
                                    <input
                                        v-model="form.attributes.thickness"
                                        type="text"
                                        placeholder="e.g. 9.5 mm / 16 Gauge / 0.5 inch"
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Coverage per {{ form.unit }} (sq ft)</label>
                                    <input
                                        v-model="form.unit_coverage_value"
                                        type="number"
                                        step="0.01"
                                        placeholder="e.g. 14.4 (1 box = 14.4 sq ft)"
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                    />
                                </div>
                            </div>
                            <div class="flex items-center gap-2 pt-2">
                                <input
                                    v-model="form.attributes.heavy_freight"
                                    type="checkbox"
                                    id="heavy_freight"
                                    class="w-4 h-4 rounded-md border-slate-300 text-amber-600 focus:ring-amber-500"
                                />
                                <label for="heavy_freight" class="text-xs font-bold text-slate-700 dark:text-slate-300">Requires Heavy Freight / Pallet Truck Delivery</label>
                            </div>
                        </div>

                        <!-- 🧪 LIQUIDS, CHEMICALS & F&B SPECIFICATIONS -->
                        <div v-else-if="form.product_type === 'liquid'" class="p-5 rounded-3xl bg-cyan-500/5 border border-cyan-500/20 space-y-4">
                            <h4 class="text-sm font-black text-cyan-950 dark:text-cyan-300 flex items-center gap-2">
                                <FlaskConical class="w-4 h-4 text-cyan-600" /> Liquids, Chemicals & Capacity Specs
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Net Volume / Capacity</label>
                                    <input
                                        v-model="form.attributes.volume_ml"
                                        type="text"
                                        placeholder="e.g. 500 ml / 5 Liters / 1 Gallon"
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Packaging Container Type</label>
                                    <CustomSelect
                                        v-model="form.attributes.packaging_type"
                                        :options="[
                                            { value: 'Glass Bottle', label: 'Glass Bottle' },
                                            { value: 'Plastic Jug / HDPE', label: 'Plastic Jug / HDPE' },
                                            { value: 'Aerosol Spray Can', label: 'Aerosol Spray Can' },
                                            { value: 'Steel Drum (55 Gal)', label: 'Steel Drum (55 Gal)' },
                                            { value: 'Tin Pail', label: 'Tin Pail' },
                                            { value: 'Bag in Box', label: 'Bag in Box' }
                                        ]"
                                        placeholder="Select Container"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Hazmat / Safety Classification</label>
                                    <CustomSelect
                                        v-model="form.attributes.hazard_class"
                                        :options="[
                                            { value: 'Non-Hazardous / Food Safe', label: 'Non-Hazardous / Food Safe' },
                                            { value: 'Class 3 Flammable Liquid', label: 'Class 3: Flammable Liquid' },
                                            { value: 'Class 8 Corrosive', label: 'Class 8: Corrosive' },
                                            { value: 'Class 6.1 Toxic', label: 'Class 6.1: Toxic / Bio' }
                                        ]"
                                        placeholder="Select Hazmat Class"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Storage Requirements / Temp</label>
                                    <input
                                        v-model="form.attributes.storage_temp"
                                        type="text"
                                        placeholder="e.g. Store cool between 15°C - 25°C, away from sunlight"
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- ⚡ ELECTRONICS & HARDWARE SPECIFICATIONS -->
                        <div v-else-if="form.product_type === 'electronics'" class="p-5 rounded-3xl bg-blue-500/5 border border-blue-500/20 space-y-4">
                            <h4 class="text-sm font-black text-blue-950 dark:text-blue-300 flex items-center gap-2">
                                <Cpu class="w-4 h-4 text-blue-600" /> Electronics & Technical Specifications
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Warranty Period</label>
                                    <input
                                        v-model="form.attributes.warranty_months"
                                        type="text"
                                        placeholder="e.g. 24 Months Official Brand Warranty"
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Power / Voltage Rating</label>
                                    <input
                                        v-model="form.attributes.power_rating"
                                        type="text"
                                        placeholder="e.g. 100-240V, 65W GaN Fast Charging"
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Key-Value Specs Table -->
                            <div class="space-y-2 pt-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Technical Specs Matrix</label>
                                <div v-for="(spec, idx) in rawSpecRows" :key="idx" class="flex items-center gap-2">
                                    <input
                                        v-model="spec.key"
                                        type="text"
                                        placeholder="Spec Name (e.g. Battery)"
                                        class="flex-1 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs"
                                    />
                                    <input
                                        v-model="spec.value"
                                        type="text"
                                        placeholder="Spec Value (e.g. 5000 mAh)"
                                        class="flex-1 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs"
                                    />
                                    <button
                                        type="button"
                                        @click="removeSpecRow(idx)"
                                        class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    @click="addSpecRow"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline pt-1"
                                >
                                    <Plus class="w-3.5 h-3.5" /> Add Another Spec Row
                                </button>
                            </div>
                        </div>

                        <!-- 📦 STANDARD RETAIL FALLBACK -->
                        <div v-else class="p-5 rounded-3xl bg-slate-100/60 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-750 text-center py-8">
                            <Package class="w-8 h-8 text-slate-400 mx-auto mb-2" />
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Standard Product</p>
                            <p class="text-[11px] text-slate-400 max-w-sm mx-auto mt-1">General retail item uses standard SKU, dimensions, weight, and pricing configured in other tabs.</p>
                        </div>
                    </div>

                    <!-- ── TAB 3: UNITS, ORDER RULES & LOGISTICS ────────────────── -->
                    <div v-show="activeModalTab === 'units'" class="space-y-4">
                        <div class="p-5 rounded-3xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-750 space-y-4">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <Boxes class="w-4 h-4 text-indigo-500" /> Minimum Order Quantity (MOQ) & Increments
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Minimum Order Quantity (MOQ)</label>
                                    <input
                                        v-model="form.min_order_quantity"
                                        type="number"
                                        step="0.01"
                                        placeholder="1"
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                    />
                                    <p class="text-[10px] text-slate-400 mt-1">Customers cannot checkout with less than this quantity.</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Quantity Step Increment</label>
                                    <input
                                        v-model="form.quantity_step"
                                        type="number"
                                        step="0.01"
                                        placeholder="1"
                                        class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                    />
                                    <p class="text-[10px] text-slate-400 mt-1">Buy in multiples of this step (e.g. 5, 10, or 1).</p>
                                </div>
                            </div>
                        </div>

                        <!-- Physical Dimensions & Weight -->
                        <div class="p-5 rounded-3xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-750 space-y-4">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <Scale class="w-4 h-4 text-indigo-500" /> Package Weight & Dimensions
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Weight</label>
                                    <div class="flex gap-2">
                                        <input
                                            v-model="form.weight"
                                            type="number"
                                            step="0.001"
                                            placeholder="0.000"
                                            class="flex-1 px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs"
                                        />
                                        <select
                                            v-model="form.weight_unit"
                                            class="w-24 px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-bold"
                                        >
                                            <option value="kg">kg</option>
                                            <option value="g">g</option>
                                            <option value="lbs">lbs</option>
                                            <option value="ton">ton</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Dimensions (L × W × H)</label>
                                    <div class="flex gap-2 items-center">
                                        <input v-model="form.length" type="number" step="0.1" placeholder="L" class="w-full px-2.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-center" />
                                        <span class="text-slate-400 text-xs">×</span>
                                        <input v-model="form.width" type="number" step="0.1" placeholder="W" class="w-full px-2.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-center" />
                                        <span class="text-slate-400 text-xs">×</span>
                                        <input v-model="form.height" type="number" step="0.1" placeholder="H" class="w-full px-2.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-center" />
                                        <select v-model="form.dimension_unit" class="w-20 px-2 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold">
                                            <option value="cm">cm</option>
                                            <option value="inch">in</option>
                                            <option value="mm">mm</option>
                                            <option value="m">m</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── TAB 4: VARIANTS MATRIX ───────────────────────────────── -->
                    <div v-show="activeModalTab === 'variants'" class="space-y-5">
                        <div class="p-5 rounded-3xl bg-indigo-500/5 border border-indigo-500/20 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-black text-indigo-950 dark:text-indigo-300 flex items-center gap-2">
                                        <Tag class="w-4 h-4 text-indigo-600" /> Multi-Option Variant Matrix Generator
                                    </h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Quickly generate variations (e.g. Size + Color, Volume + Packaging, Thickness + Length)</p>
                                </div>
                                <button
                                    type="button"
                                    @click="addSingleVariant"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 shadow-xs"
                                >
                                    <Plus class="w-3.5 h-3.5" /> Manual Row
                                </button>
                            </div>

                            <!-- Generator Input Row -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Option 1 Name & Comma Values</label>
                                    <div class="flex gap-2">
                                        <input v-model="variantOption1Name" type="text" placeholder="e.g. Size" class="w-28 px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold" />
                                        <input v-model="variantOption1Values" type="text" placeholder="S, M, L, XL" class="flex-1 px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Option 2 Name & Comma Values</label>
                                    <div class="flex gap-2">
                                        <input v-model="variantOption2Name" type="text" placeholder="e.g. Color" class="w-28 px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold" />
                                        <input v-model="variantOption2Values" type="text" placeholder="Navy, Olive, Black" class="flex-1 px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs" />
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="generateVariantMatrix"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 cursor-pointer"
                            >
                                <Sparkles class="w-3.5 h-3.5" /> Generate Variant Matrix
                            </button>
                        </div>

                        <!-- Variants List Table -->
                        <div v-if="form.variants.length > 0" class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-800 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th class="px-3 py-2.5">Options</th>
                                        <th class="px-3 py-2.5">Variant SKU</th>
                                        <th class="px-3 py-2.5">Price (৳)</th>
                                        <th class="px-3 py-2.5">Stock</th>
                                        <th class="px-3 py-2.5 text-right"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="(v, idx) in form.variants" :key="idx" class="bg-white dark:bg-slate-900">
                                        <td class="px-3 py-2 font-bold text-indigo-600 dark:text-indigo-400">
                                            <span v-for="(val, key) in v.option_values" :key="key" class="mr-1.5 inline-block bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-[10px]">
                                                {{ key }}: {{ val }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input v-model="v.sku" type="text" class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-mono uppercase" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input v-model="v.price" type="number" step="0.01" class="w-24 px-2 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input v-model="v.stock_quantity" type="number" class="w-20 px-2 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs" />
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <button type="button" @click="removeVariant(idx)" class="p-1 text-rose-500 hover:bg-rose-50 rounded-lg">
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-6 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                            <Tag class="w-6 h-6 text-slate-300 dark:text-slate-600 mx-auto mb-1.5" />
                            <p class="text-xs text-slate-500">No variants generated yet. Use the generator above or add manual variant rows.</p>
                        </div>
                    </div>

                    <!-- Modal Actions Footer -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between sticky bottom-0 bg-white dark:bg-slate-900 pb-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-500">Selected Type:</span>
                            <span :class="[getTypeConfig(form.product_type).color, 'px-2 py-0.5 rounded-lg text-[10px] font-bold border inline-flex items-center gap-1']">
                                <component :is="getTypeConfig(form.product_type).icon" class="w-3 h-3" />
                                {{ getTypeConfig(form.product_type).label }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="isCreateModalOpen = false; isEditModalOpen = false"
                                class="px-4 py-2.5 rounded-2xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2.5 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/25 transition-all cursor-pointer"
                            >
                                {{ isEditModalOpen ? 'Save Changes' : 'Create Product' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
