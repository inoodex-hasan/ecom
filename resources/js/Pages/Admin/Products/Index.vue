<script setup>
import { ref, watch } from 'vue';
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
    Download
} from 'lucide-vue-next';
import CustomSelect from '@/Components/CustomSelect.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    brands: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || '');
const selectedStatus = ref(props.filters?.status || '');
const onlyLowStock = ref(props.filters?.low_stock === 'true' || props.filters?.low_stock === true);

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const productToEdit = ref(null);

const form = useForm({
    name: '',
    sku: '',
    category_id: '',
    brand_id: '',
    price: '',
    compare_price: '',
    cost_price: '',
    stock_quantity: 10,
    low_stock_threshold: 5,
    status: 'published',
    is_featured: false,
    primary_image: '',
    short_description: '',
    description: '',
});

function applyFilters() {
    router.get(
        route('admin.products.index'),
        {
            search: search.value || undefined,
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
    isCreateModalOpen.value = true;
}

function openEditModal(prod) {
    productToEdit.value = prod;
    form.name = prod.name;
    form.sku = prod.sku;
    form.category_id = prod.category_id;
    form.brand_id = prod.brand_id;
    form.price = prod.price;
    form.compare_price = prod.compare_price;
    form.cost_price = prod.cost_price;
    form.stock_quantity = prod.stock_quantity;
    form.low_stock_threshold = prod.low_stock_threshold;
    form.status = prod.status;
    form.is_featured = !!prod.is_featured;
    form.primary_image = prod.primary_image;
    form.short_description = prod.short_description;
    form.description = prod.description;
    isEditModalOpen.value = true;
}

function submitCreate() {
    form.post(route('admin.products.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            form.reset();
        },
    });
}

function submitUpdate() {
    form.put(route('admin.products.update', productToEdit.value.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
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
    const newQty = Math.max(0, prod.stock_quantity + delta);
    router.patch(route('admin.products.quick-update', prod.id), {
        stock_quantity: newQty,
    }, { preserveScroll: true });
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(val || 0);
};
</script>

<template>
    <AdminLayout>
        <Head title="Products Inventory - Admin" />

        <template #header>
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Product Catalog</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Manage products, stock inventories, and pricing</p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Integrated Filter Action Bar with Export -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col xl:flex-row gap-3 items-center justify-between">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full xl:w-auto">
                    <!-- Search -->
                    <div class="relative w-full sm:w-80">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search product or SKU..."
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
                        <div class="w-full sm:w-40">
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
                        :href="route('admin.products.export', { format: 'xlsx', search: search || undefined, category: selectedCategory || undefined, status: selectedStatus || undefined, low_stock: onlyLowStock ? 'true' : undefined })"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors shrink-0"
                        title="Export Products as Excel"
                    >
                        <Download class="w-3.5 h-3.5" />
                        <span>Excel</span>
                    </a>
                    <a
                        :href="route('admin.products.export', { format: 'csv', search: search || undefined, category: selectedCategory || undefined, status: selectedStatus || undefined, low_stock: onlyLowStock ? 'true' : undefined })"
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
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-xs uppercase text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="px-6 py-4">Product</th>
                                <th class="px-6 py-4">SKU</th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4">Price</th>
                                <th class="px-6 py-4">Stock</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr
                                v-for="prod in products.data"
                                :key="prod.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors"
                            >
                                <!-- Name & Image -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="prod.primary_image || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=100'"
                                            alt="Product"
                                            class="w-12 h-12 rounded-xl object-cover bg-slate-100 dark:bg-slate-800 shrink-0 border border-slate-200/50 dark:border-slate-700/50"
                                        />
                                        <div class="truncate max-w-[200px]">
                                            <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                                                {{ prod.name }}
                                            </p>
                                            <span v-if="prod.is_featured" class="inline-block mt-0.5 text-[10px] font-bold text-amber-600 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-400 px-1.5 py-0.2 rounded">
                                                ★ Featured
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- SKU -->
                                <td class="px-6 py-4 text-xs font-mono text-slate-500 dark:text-slate-400">
                                    {{ prod.sku }}
                                </td>

                                <!-- Category -->
                                <td class="px-6 py-4 text-xs text-slate-700 dark:text-slate-300">
                                    {{ prod.category?.name || 'Uncategorized' }}
                                </td>

                                <!-- Price -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">
                                            {{ formatCurrency(prod.price) }}
                                        </span>
                                        <span v-if="prod.compare_price" class="text-[10px] text-slate-400 line-through">
                                            {{ formatCurrency(prod.compare_price) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Stock Controls -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="quickStock(prod, -1)"
                                            class="w-5 h-5 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold flex items-center justify-center text-xs"
                                        >
                                            -
                                        </button>
                                        <span
                                            :class="[
                                                prod.stock_quantity <= prod.low_stock_threshold
                                                    ? 'text-rose-600 dark:text-rose-400 font-bold'
                                                    : 'text-slate-800 dark:text-slate-200',
                                                'text-xs'
                                            ]"
                                        >
                                            {{ prod.stock_quantity }}
                                        </span>
                                        <button
                                            @click="quickStock(prod, 1)"
                                            class="w-5 h-5 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold flex items-center justify-center text-xs"
                                        >
                                            +
                                        </button>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            prod.status === 'published'
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200'
                                                : prod.status === 'draft'
                                                ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border-amber-200'
                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border-slate-200',
                                            'px-2.5 py-1 rounded-full text-[11px] font-semibold border capitalize'
                                        ]"
                                    >
                                        {{ prod.status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="openEditModal(prod)"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors"
                                            title="Edit Product"
                                        >
                                            <Edit3 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="deleteProduct(prod)"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
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
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Showing {{ products.from }} to {{ products.to }} of {{ products.total }} results
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in products.links"
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

        <!-- Create/Edit Product Modal Dialog -->
        <div
            v-if="isCreateModalOpen || isEditModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto"
        >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl my-8">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ isEditModalOpen ? 'Edit Product' : 'Add New Product' }}
                    </h3>
                    <button @click="isCreateModalOpen = false; isEditModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="isEditModalOpen ? submitUpdate() : submitCreate()" class="p-6 space-y-4">
                    <!-- Title & SKU -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Product Name *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">SKU *</label>
                            <input
                                v-model="form.sku"
                                type="text"
                                required
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 uppercase"
                            />
                        </div>
                    </div>

                    <!-- Category & Brand -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Category</label>
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
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Brand</label>
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

                    <!-- Prices & Inventory -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Price ($) *</label>
                            <input
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                required
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Compare Price ($)</label>
                            <input
                                v-model="form.compare_price"
                                type="number"
                                step="0.01"
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Stock Quantity *</label>
                            <input
                                v-model="form.stock_quantity"
                                type="number"
                                required
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Image URL -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Primary Image URL</label>
                        <input
                            v-model="form.primary_image"
                            type="url"
                            placeholder="https://images.unsplash.com/photo-..."
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Status & Featured -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status</label>
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
                        <div class="flex items-center gap-2 pt-5">
                            <input
                                v-model="form.is_featured"
                                type="checkbox"
                                id="is_featured"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label for="is_featured" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Feature on homepage</label>
                        </div>
                    </div>

                    <!-- Short Description -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="isCreateModalOpen = false; isEditModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20"
                        >
                            {{ isEditModalOpen ? 'Save Changes' : 'Create Product' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
