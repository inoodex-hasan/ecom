<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import {
    Plus,
    FolderTree,
    Folder,
    FolderOpen,
    Edit3,
    Trash2,
    X,
    Layers,
    Tv,
    Smartphone,
    Laptop,
    Headphones,
    Watch,
    Cable,
    ShoppingBag,
    Shirt,
    Sparkles,
    Home,
    Utensils,
    Wrench,
    Droplet,
    Zap,
    Book,
    Gift,
    Armchair,
    Camera,
    Activity,
    Search,
    LayoutGrid,
    List,
    ExternalLink,
    CornerDownRight,
    CheckCircle2,
    AlertCircle,
    UploadCloud,
    Check,
    ChevronDown,
    ChevronRight,
    ChevronsUpDown,
    Tag,
    Eye,
    EyeOff,
    SlidersHorizontal,
    ArrowUpRight
} from 'lucide-vue-next';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    metrics: {
        type: Object,
        default: () => ({}),
    },
});

// View mode: 'tree' | 'grid' | 'table'
const viewMode = ref('tree');

// Search & Filter state
const searchQuery = ref('');
const statusFilter = ref(''); // '' | 'active' | 'inactive'
const levelFilter = ref(''); // '' | 'root' | 'sub'
const sortBy = ref('sort_order'); // 'sort_order' | 'name_asc' | 'name_desc' | 'products_desc'

// Tree expand/collapse tracking for root categories (all expanded by default)
const expandedRoots = ref({});

// Watch categories to auto-expand all roots initially
watch(
    () => props.categories,
    (cats) => {
        if (cats && cats.length > 0) {
            cats.filter(c => !c.parent_id).forEach(r => {
                if (expandedRoots.value[r.id] === undefined) {
                    expandedRoots.value[r.id] = true;
                }
            });
        }
    },
    { immediate: true }
);

function toggleRootExpand(rootId) {
    expandedRoots.value[rootId] = !expandedRoots.value[rootId];
}

function expandAllRoots() {
    (props.categories || []).filter(c => !c.parent_id).forEach(r => {
        expandedRoots.value[r.id] = true;
    });
}

function collapseAllRoots() {
    (props.categories || []).filter(c => !c.parent_id).forEach(r => {
        expandedRoots.value[r.id] = false;
    });
}

// Available icon options with friendly names
const availableIcons = [
    { value: 'FolderTree', label: 'General / Tree', icon: FolderTree },
    { value: 'Shirt', label: 'Apparel & Clothing', icon: Shirt },
    { value: 'ShoppingBag', label: 'Retail & Bags', icon: ShoppingBag },
    { value: 'Sparkles', label: 'Beauty & Cosmetics', icon: Sparkles },
    { value: 'Watch', label: 'Watches & Wearables', icon: Watch },
    { value: 'Tv', label: 'TV & Displays', icon: Tv },
    { value: 'Smartphone', label: 'Phones & Tech', icon: Smartphone },
    { value: 'Laptop', label: 'Computers', icon: Laptop },
    { value: 'Headphones', label: 'Audio & Sound', icon: Headphones },
    { value: 'Cable', label: 'Accessories', icon: Cable },
    { value: 'Home', label: 'Home & Living', icon: Home },
    { value: 'Armchair', label: 'Furniture & Decor', icon: Armchair },
    { value: 'Utensils', label: 'Kitchen & Dining', icon: Utensils },
    { value: 'Activity', label: 'Sports & Active', icon: Activity },
    { value: 'Camera', label: 'Cameras & Optics', icon: Camera },
    { value: 'Zap', label: 'Electronics', icon: Zap },
    { value: 'Droplet', label: 'Fragrances & Care', icon: Droplet },
    { value: 'Gift', label: 'Gifts & Festive', icon: Gift },
    { value: 'Book', label: 'Books & Stationery', icon: Book },
    { value: 'Wrench', label: 'Tools & Hardware', icon: Wrench },
];

const iconMap = {
    FolderTree,
    Folder,
    Shirt,
    ShoppingBag,
    Sparkles,
    Watch,
    Tv,
    Smartphone,
    Laptop,
    Headphones,
    Cable,
    Home,
    Armchair,
    Utensils,
    Activity,
    Camera,
    Zap,
    Droplet,
    Gift,
    Book,
    Wrench,
};

// Filtered Categories based on search & filters
const filteredCategories = computed(() => {
    let list = [...(props.categories || [])];

    // Search query filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase().trim();
        list = list.filter(cat =>
            cat.name.toLowerCase().includes(query) ||
            cat.slug.toLowerCase().includes(query) ||
            (cat.description && cat.description.toLowerCase().includes(query)) ||
            (cat.parent && cat.parent.name.toLowerCase().includes(query))
        );
    }

    // Status filter
    if (statusFilter.value === 'active') {
        list = list.filter(cat => cat.is_active);
    } else if (statusFilter.value === 'inactive') {
        list = list.filter(cat => !cat.is_active);
    }

    // Level filter
    if (levelFilter.value === 'root') {
        list = list.filter(cat => !cat.parent_id);
    } else if (levelFilter.value === 'sub') {
        list = list.filter(cat => !!cat.parent_id);
    }

    // Sorting
    list.sort((a, b) => {
        if (sortBy.value === 'sort_order') {
            return (a.sort_order || 0) - (b.sort_order || 0) || a.name.localeCompare(b.name);
        } else if (sortBy.value === 'name_asc') {
            return a.name.localeCompare(b.name);
        } else if (sortBy.value === 'name_desc') {
            return b.name.localeCompare(a.name);
        } else if (sortBy.value === 'products_desc') {
            return (b.products_count || 0) - (a.products_count || 0);
        }
        return 0;
    });

    return list;
});

// Tree Structure: Grouped by Root Categories with their nested children
const treeRoots = computed(() => {
    const all = props.categories || [];
    const roots = all.filter(c => !c.parent_id);
    const subMap = new Map();

    all.forEach(c => {
        if (c.parent_id) {
            if (!subMap.has(c.parent_id)) subMap.set(c.parent_id, []);
            subMap.get(c.parent_id).push(c);
        }
    });

    // If there is an active search or status filter, apply it to the children as well
    const query = searchQuery.value.toLowerCase().trim();

    return roots.map(root => {
        let children = subMap.get(root.id) || [];

        // Apply filters to children
        if (query) {
            children = children.filter(child =>
                child.name.toLowerCase().includes(query) ||
                child.slug.toLowerCase().includes(query) ||
                root.name.toLowerCase().includes(query)
            );
        }
        if (statusFilter.value === 'active') {
            children = children.filter(c => c.is_active);
        } else if (statusFilter.value === 'inactive') {
            children = children.filter(c => !c.is_active);
        }

        // Calculate total products in department (root + children)
        const totalDeptProducts = (root.products_count || 0) +
            (subMap.get(root.id) || []).reduce((sum, c) => sum + (c.products_count || 0), 0);

        const rootMatches = !query ||
            root.name.toLowerCase().includes(query) ||
            root.slug.toLowerCase().includes(query);

        return {
            ...root,
            children,
            rawChildrenCount: (subMap.get(root.id) || []).length,
            totalDeptProducts,
            isRootVisible: rootMatches || children.length > 0,
        };
    }).filter(root => {
        if (levelFilter.value === 'sub') return false;
        return root.isRootVisible;
    });
});

// Category metrics
const computedMetrics = computed(() => {
    if (props.metrics && Object.keys(props.metrics).length > 0) return props.metrics;
    const cats = props.categories || [];
    return {
        total_categories: cats.length,
        root_categories: cats.filter(c => !c.parent_id).length,
        sub_categories: cats.filter(c => !!c.parent_id).length,
        active_categories: cats.filter(c => c.is_active).length,
        total_products: cats.reduce((sum, c) => sum + (c.products_count || 0), 0),
    };
});

// Modal State
const isModalOpen = ref(false);
const editingCategory = ref(null);
const autoGenerateSlug = ref(true);
const isUploadingImage = ref(false);

const form = useForm({
    name: '',
    slug: '',
    parent_id: '',
    description: '',
    icon: 'FolderTree',
    image: '',
    is_active: true,
    sort_order: 0,
});

// Parent Category Dropdown options
const parentOptions = computed(() => {
    const options = [{ value: '', label: 'None (Top-Level Root Category)' }];
    const currentId = editingCategory.value?.id;

    (props.categories || []).forEach(cat => {
        // Exclude self when editing
        if (currentId && cat.id === currentId) return;
        // Exclude direct children of current category to prevent circular hierarchy
        if (currentId && cat.parent_id === currentId) return;

        options.push({
            value: cat.id,
            label: cat.parent ? `↳ ${cat.parent.name} → ${cat.name}` : `📁 ${cat.name} (Root)`,
        });
    });

    return options;
});

// Selected parent details for modal preview
const selectedParent = computed(() => {
    if (!form.parent_id) return null;
    return (props.categories || []).find(c => c.id === Number(form.parent_id) || c.id === form.parent_id);
});

// Live slug sync when autoGenerateSlug is true
watch(() => form.name, (newName) => {
    if (autoGenerateSlug.value && newName) {
        form.slug = newName
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
});

function openCreateModal(parentId = '') {
    editingCategory.value = null;
    autoGenerateSlug.value = true;
    form.reset();
    form.parent_id = parentId ? Number(parentId) : '';
    form.is_active = true;
    form.sort_order = (props.categories?.length || 0) + 1;
    isModalOpen.value = true;
}

function openEditModal(cat) {
    editingCategory.value = cat;
    autoGenerateSlug.value = false;
    form.name = cat.name;
    form.slug = cat.slug;
    form.parent_id = cat.parent_id ? Number(cat.parent_id) : '';
    form.description = cat.description || '';
    form.icon = cat.icon || 'FolderTree';
    form.image = cat.image || '';
    form.is_active = !!cat.is_active;
    form.sort_order = cat.sort_order || 0;
    isModalOpen.value = true;
}

function handleImageUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    isUploadingImage.value = true;
    const data = new FormData();
    data.append('image', file);

    axios.post(route('admin.categories.upload-image', undefined, false) || '/admin/categories/upload-image', data, {
        headers: { 'Content-Type': 'multipart/form-data' }
    })
    .then(res => {
        form.image = res.data.url;
    })
    .catch(err => {
        alert(err.response?.data?.message || 'Failed to upload image. Max file size: 2MB.');
    })
    .finally(() => {
        isUploadingImage.value = false;
        e.target.value = '';
    });
}

function removeImage() {
    form.image = '';
}

function submit() {
    if (editingCategory.value) {
        form.put(route('admin.categories.update', editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post(route('admin.categories.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
}

function toggleStatus(cat) {
    router.patch(route('admin.categories.toggle-status', cat.id), {}, {
        preserveScroll: true,
    });
}

function deleteCategory(cat) {
    const warning = cat.products_count > 0
        ? `"${cat.name}" has ${cat.products_count} product(s) assigned. Deleting this category will unassign them. Continue?`
        : `Are you sure you want to delete category "${cat.name}"?`;

    if (confirm(warning)) {
        router.delete(route('admin.categories.destroy', cat.id), {
            preserveScroll: true,
        });
    }
}

function resetFilters() {
    searchQuery.value = '';
    statusFilter.value = '';
    levelFilter.value = '';
    sortBy.value = 'sort_order';
}

// Background gradient picker for icons based on category name
function getIconGradient(name = '') {
    const gradients = [
        'from-indigo-500/20 to-purple-500/20 text-indigo-600 dark:text-indigo-400 border-indigo-500/25',
        'from-blue-500/20 to-cyan-500/20 text-blue-600 dark:text-blue-400 border-blue-500/25',
        'from-emerald-500/20 to-teal-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-500/25',
        'from-amber-500/20 to-orange-500/20 text-amber-600 dark:text-amber-400 border-amber-500/25',
        'from-rose-500/20 to-pink-500/20 text-rose-600 dark:text-rose-400 border-rose-500/25',
        'from-violet-500/20 to-fuchsia-500/20 text-violet-600 dark:text-violet-400 border-violet-500/25',
    ];
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash) % gradients.length;
    return gradients[index];
}
</script>

<template>
    <AdminLayout>
        <Head title="Catalog Categories & Hierarchy - Admin" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                            Categories & Hierarchy
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                            {{ computedMetrics.total_categories }} Total
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Organize your store departments, manage subcategories, and control storefront navigation.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        @click="openCreateModal('')"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white shadow-lg shadow-indigo-600/25 hover:shadow-indigo-600/40 transition-all cursor-pointer active:scale-95"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Add Category</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6 pb-12">
            <!-- Metric KPI Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                <!-- Total Categories -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group hover:border-indigo-500/30 transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Categories</p>
                            <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-1">{{ computedMetrics.total_categories }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ computedMetrics.root_categories }} root</span>
                                <span class="text-slate-300 dark:text-slate-700">•</span>
                                <span class="font-bold text-purple-600 dark:text-purple-400">{{ computedMetrics.sub_categories }} sub</span>
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                            <FolderTree class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <!-- Root Departments -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group hover:border-purple-500/30 transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Root Departments</p>
                            <p class="text-2xl sm:text-3xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ computedMetrics.root_categories }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Primary storefront tiers</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                            <Layers class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <!-- Catalog Products -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group hover:border-emerald-500/30 transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Categorized Products</p>
                            <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ computedMetrics.total_products }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Assigned to catalog</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                            <ShoppingBag class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <!-- Storefront Visibility -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group hover:border-blue-500/30 transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Store Visibility</p>
                            <p class="text-2xl sm:text-3xl font-black text-blue-600 dark:text-blue-400 mt-1">
                                {{ computedMetrics.total_categories ? Math.round((computedMetrics.active_categories / computedMetrics.total_categories) * 100) : 0 }}%
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                {{ computedMetrics.active_categories }} active in catalog
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                            <CheckCircle2 class="w-6 h-6" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integrated Filter & Action Toolbar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-4 shadow-xs space-y-3.5">
                <div class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between">
                    <!-- Left: Search Box -->
                    <div class="flex flex-wrap items-center gap-2.5 flex-1">
                        <div class="relative w-full sm:w-80">
                            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by name, slug, or parent..."
                                class="w-full pl-10 pr-9 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                            >
                                <X class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <!-- Status Filter -->
                        <div class="w-36">
                            <CustomSelect
                                v-model="statusFilter"
                                :options="[
                                    { value: '', label: 'All Statuses' },
                                    { value: 'active', label: 'Active Only' },
                                    { value: 'inactive', label: 'Disabled Only' },
                                ]"
                                placeholder="Status"
                            />
                        </div>

                        <!-- Hierarchy Depth Filter -->
                        <div class="w-40">
                            <CustomSelect
                                v-model="levelFilter"
                                :options="[
                                    { value: '', label: 'All Levels' },
                                    { value: 'root', label: 'Root Only' },
                                    { value: 'sub', label: 'Subcategories Only' },
                                ]"
                                placeholder="Hierarchy"
                            />
                        </div>

                        <!-- Sort By -->
                        <div class="w-40">
                            <CustomSelect
                                v-model="sortBy"
                                :options="[
                                    { value: 'sort_order', label: 'Sort Order' },
                                    { value: 'name_asc', label: 'Name (A to Z)' },
                                    { value: 'name_desc', label: 'Name (Z to A)' },
                                    { value: 'products_desc', label: 'Most Products' },
                                ]"
                                placeholder="Sort By"
                            />
                        </div>

                        <!-- Reset Filter Button -->
                        <button
                            v-if="searchQuery || statusFilter || levelFilter || sortBy !== 'sort_order'"
                            @click="resetFilters"
                            class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            <X class="w-3.5 h-3.5" />
                            Reset
                        </button>
                    </div>

                    <!-- Right: View Mode Toggle (Tree, Grid, Table) -->
                    <div class="flex items-center gap-1 self-start sm:self-end lg:self-center bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
                        <button
                            @click="viewMode = 'tree'"
                            :class="[
                                viewMode === 'tree'
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold'
                                    : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300',
                                'px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer'
                            ]"
                            title="Interactive Hierarchy Tree"
                        >
                            <FolderTree class="w-3.5 h-3.5" />
                            Tree View
                        </button>

                        <button
                            @click="viewMode = 'grid'"
                            :class="[
                                viewMode === 'grid'
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold'
                                    : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300',
                                'px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer'
                            ]"
                            title="Grid Cards"
                        >
                            <LayoutGrid class="w-3.5 h-3.5" />
                            Cards
                        </button>

                        <button
                            @click="viewMode = 'table'"
                            :class="[
                                viewMode === 'table'
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold'
                                    : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300',
                                'px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer'
                            ]"
                            title="Compact Data Table"
                        >
                            <List class="w-3.5 h-3.5" />
                            Table
                        </button>
                    </div>
                </div>

                <!-- Quick Filter Badges -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800/80 text-xs">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1">Quick Filter:</span>
                        <button
                            @click="levelFilter = ''; statusFilter = ''"
                            :class="[!levelFilter && !statusFilter ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200']"
                            class="px-2.5 py-1 rounded-xl text-[11px] font-bold transition-colors cursor-pointer"
                        >
                            All ({{ computedMetrics.total_categories }})
                        </button>
                        <button
                            @click="levelFilter = 'root'; statusFilter = ''"
                            :class="[levelFilter === 'root' ? 'bg-purple-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200']"
                            class="px-2.5 py-1 rounded-xl text-[11px] font-bold transition-colors cursor-pointer"
                        >
                            Root Departments ({{ computedMetrics.root_categories }})
                        </button>
                        <button
                            @click="levelFilter = 'sub'; statusFilter = ''"
                            :class="[levelFilter === 'sub' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200']"
                            class="px-2.5 py-1 rounded-xl text-[11px] font-bold transition-colors cursor-pointer"
                        >
                            Subcategories ({{ computedMetrics.sub_categories }})
                        </button>
                        <button
                            @click="statusFilter = 'active'; levelFilter = ''"
                            :class="[statusFilter === 'active' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200']"
                            class="px-2.5 py-1 rounded-xl text-[11px] font-bold transition-colors cursor-pointer"
                        >
                            Active Only ({{ computedMetrics.active_categories }})
                        </button>
                    </div>

                    <div v-if="viewMode === 'tree'" class="hidden sm:flex items-center gap-2 text-[11px] text-slate-400">
                        <button @click="expandAllRoots" class="hover:text-indigo-600 dark:hover:text-indigo-400 font-bold transition-colors cursor-pointer">
                            Expand All
                        </button>
                        <span>•</span>
                        <button @click="collapseAllRoots" class="hover:text-indigo-600 dark:hover:text-indigo-400 font-bold transition-colors cursor-pointer">
                            Collapse All
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================= VIEW 1: HERO HIERARCHY TREE VIEW ================= -->
            <div v-if="viewMode === 'tree'" class="space-y-4">
                <div v-if="treeRoots.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center">
                    <FolderTree class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No matching categories found</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Try clearing your filters or create a new category.</p>
                    <button
                        @click="openCreateModal('')"
                        class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5"
                    >
                        <Plus class="w-4 h-4" /> Add Root Category
                    </button>
                </div>

                <!-- Tree Root Cards -->
                <div
                    v-for="root in treeRoots"
                    :key="root.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs hover:border-indigo-500/40 transition-all duration-200"
                >
                    <!-- Root Department Header Bar -->
                    <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-slate-50/80 via-white to-indigo-50/30 dark:from-slate-850 dark:via-slate-900 dark:to-indigo-950/20 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3.5">
                            <!-- Expand/Collapse toggle button -->
                            <button
                                @click="toggleRootExpand(root.id)"
                                class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center justify-center transition-colors cursor-pointer shrink-0"
                                :title="expandedRoots[root.id] ? 'Collapse Subcategories' : 'Expand Subcategories'"
                            >
                                <ChevronDown
                                    class="w-4 h-4 transition-transform duration-200"
                                    :class="{ '-rotate-90': !expandedRoots[root.id] }"
                                />
                            </button>

                            <!-- Department Thumbnail / Icon -->
                            <div
                                v-if="root.image"
                                class="w-12 h-12 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 bg-slate-100"
                            >
                                <img :src="root.image" :alt="root.name" class="w-full h-full object-cover" />
                            </div>
                            <div
                                v-else
                                :class="[
                                    getIconGradient(root.name),
                                    'w-12 h-12 rounded-2xl bg-gradient-to-br border flex items-center justify-center shrink-0 shadow-xs'
                                ]"
                            >
                                <component :is="iconMap[root.icon] || FolderTree" class="w-6 h-6" />
                            </div>

                            <!-- Department Info -->
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">
                                        {{ root.name }}
                                    </h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/40">
                                        Root Department
                                    </span>
                                    <span class="font-mono text-[10px] text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md">
                                        /{{ root.slug }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ root.description || 'Primary storefront department.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Right Stats & Actions -->
                        <div class="flex items-center gap-3 self-end md:self-center">
                            <!-- Subcategory count chip -->
                            <div class="px-3 py-1.5 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200/50 dark:border-indigo-800/40 text-center">
                                <span class="text-[10px] font-bold text-indigo-500 dark:text-indigo-400 uppercase tracking-wider block">Subcategories</span>
                                <span class="text-sm font-black text-indigo-700 dark:text-indigo-300 font-mono">{{ root.rawChildrenCount }}</span>
                            </div>

                            <!-- Products in Department -->
                            <Link
                                :href="route('admin.products.index', { category: root.id })"
                                class="px-3 py-1.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/50 dark:border-emerald-800/40 text-center hover:bg-emerald-100/70 transition-colors group/link"
                                title="View all products under this department"
                            >
                                <span class="text-[10px] font-bold text-emerald-500 dark:text-emerald-400 uppercase tracking-wider flex items-center justify-center gap-1">
                                    <span>Products</span>
                                    <ArrowUpRight class="w-2.5 h-2.5 group-hover/link:translate-x-0.5 group-hover/link:-translate-y-0.5 transition-transform" />
                                </span>
                                <span class="text-sm font-black text-emerald-700 dark:text-emerald-300 font-mono">{{ root.totalDeptProducts }}</span>
                            </Link>

                            <!-- Active Switch -->
                            <button
                                @click="toggleStatus(root)"
                                :title="root.is_active ? 'Department active - click to disable' : 'Department inactive - click to activate'"
                                :class="[
                                    root.is_active ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-400',
                                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors'
                                ]"
                            >
                                <span
                                    :class="[
                                        root.is_active ? 'translate-x-5' : 'translate-x-0',
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-xs transition duration-200'
                                    ]"
                                />
                            </button>

                            <!-- Add Sub Button -->
                            <button
                                @click="openCreateModal(root.id)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 transition-colors cursor-pointer"
                                title="Add a subcategory directly under this department"
                            >
                                <Plus class="w-3.5 h-3.5" />
                                <span>Add Sub</span>
                            </button>

                            <!-- Edit Root -->
                            <button
                                @click="openEditModal(root)"
                                class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors cursor-pointer"
                                title="Edit Department"
                            >
                                <Edit3 class="w-4 h-4" />
                            </button>

                            <!-- Delete Root -->
                            <button
                                @click="deleteCategory(root)"
                                class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                title="Delete Department"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Nested Subcategories Grid (Collapsible) -->
                    <div v-show="expandedRoots[root.id]" class="p-5 bg-slate-50/50 dark:bg-slate-900/40">
                        <div v-if="root.children.length === 0" class="text-center py-6 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                            <Layers class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                            <p class="text-xs font-bold text-slate-600 dark:text-slate-400">No subcategories under {{ root.name }} yet</p>
                            <button
                                @click="openCreateModal(root.id)"
                                class="mt-2 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1"
                            >
                                <Plus class="w-3.5 h-3.5" /> Add First Subcategory
                            </button>
                        </div>

                        <div v-else>
                            <div class="flex items-center justify-between mb-3 px-1">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <CornerDownRight class="w-3.5 h-3.5 text-indigo-500" />
                                    Subcategories under {{ root.name }} ({{ root.children.length }})
                                </span>
                                <span class="text-[11px] text-slate-400">Click any card to edit or add products</span>
                            </div>

                            <!-- Subcategories Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                                <div
                                    v-for="child in root.children"
                                    :key="child.id"
                                    class="bg-white dark:bg-slate-850 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-3.5 shadow-2xs hover:border-indigo-500/50 hover:shadow-md hover:shadow-indigo-500/5 transition-all group flex flex-col justify-between"
                                >
                                    <div>
                                        <div class="flex items-start justify-between gap-2 mb-2">
                                            <div class="flex items-center gap-2.5">
                                                <!-- Small icon or image -->
                                                <div
                                                    v-if="child.image"
                                                    class="w-8 h-8 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 bg-slate-100"
                                                >
                                                    <img :src="child.image" :alt="child.name" class="w-full h-full object-cover" />
                                                </div>
                                                <div
                                                    v-else
                                                    :class="[
                                                        getIconGradient(child.name),
                                                        'w-8 h-8 rounded-xl bg-gradient-to-br border flex items-center justify-center shrink-0 text-xs'
                                                    ]"
                                                >
                                                    <component :is="iconMap[child.icon] || Tag" class="w-4 h-4" />
                                                </div>

                                                <div>
                                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-1">
                                                        {{ child.name }}
                                                    </h4>
                                                    <span class="font-mono text-[9px] text-slate-400 block">
                                                        /{{ child.slug }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Active switch -->
                                            <button
                                                @click="toggleStatus(child)"
                                                :title="child.is_active ? 'Active' : 'Disabled'"
                                                :class="[
                                                    child.is_active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700',
                                                    'relative inline-flex h-4 w-7 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors'
                                                ]"
                                            >
                                                <span
                                                    :class="[
                                                        child.is_active ? 'translate-x-3' : 'translate-x-0',
                                                        'pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow-xs transition duration-200'
                                                    ]"
                                                />
                                            </button>
                                        </div>

                                        <p v-if="child.description" class="text-[11px] text-slate-400 line-clamp-1 mb-2">
                                            {{ child.description }}
                                        </p>
                                    </div>

                                    <!-- Bottom Row of Child: Products badge + Quick actions -->
                                    <div class="pt-2.5 mt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                        <Link
                                            :href="route('admin.products.index', { category: child.id })"
                                            class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group/p"
                                            title="View products in this subcategory"
                                        >
                                            <span class="px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-mono text-[10px]">
                                                {{ child.products_count || 0 }}
                                            </span>
                                            <span>items</span>
                                            <ExternalLink class="w-2.5 h-2.5 text-slate-400 group-hover/p:translate-x-0.5 transition-transform" />
                                        </Link>

                                        <div class="flex items-center gap-1">
                                            <!-- Add 3rd level subcategory -->
                                            <button
                                                @click="openCreateModal(child.id)"
                                                class="p-1 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors cursor-pointer"
                                                title="Add Child Subcategory"
                                            >
                                                <Plus class="w-3.5 h-3.5" />
                                            </button>
                                            <button
                                                @click="openEditModal(child)"
                                                class="p-1 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors cursor-pointer"
                                                title="Edit Subcategory"
                                            >
                                                <Edit3 class="w-3.5 h-3.5" />
                                            </button>
                                            <button
                                                @click="deleteCategory(child)"
                                                class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                                title="Delete Subcategory"
                                            >
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= VIEW 2: VISUAL CARD GRID ================= -->
            <div v-else-if="viewMode === 'grid'">
                <div v-if="filteredCategories.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center">
                    <FolderTree class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No categories found</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Try refining your search terms or create a new category to get started.</p>
                    <button
                        @click="openCreateModal('')"
                        class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5"
                    >
                        <Plus class="w-4 h-4" /> Add Category
                    </button>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="cat in filteredCategories"
                        :key="cat.id"
                        class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex flex-col justify-between group hover:border-indigo-500/50 hover:shadow-md hover:shadow-indigo-500/5 transition-all duration-200"
                    >
                        <div>
                            <!-- Header Bar of Card: Thumbnail / Icon + Status Switch -->
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        v-if="cat.image"
                                        class="w-12 h-12 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 bg-slate-100 dark:bg-slate-800"
                                    >
                                        <img :src="cat.image" :alt="cat.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                    </div>
                                    <div
                                        v-else
                                        :class="[
                                            getIconGradient(cat.name),
                                            'w-12 h-12 rounded-2xl bg-gradient-to-br border flex items-center justify-center shrink-0 shadow-xs'
                                        ]"
                                    >
                                        <component :is="iconMap[cat.icon] || FolderTree" class="w-6 h-6" />
                                    </div>

                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                                {{ cat.name }}
                                            </h3>
                                        </div>
                                        <span class="font-mono text-[10px] text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                                            /{{ cat.slug }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Active Status Toggle Button -->
                                <button
                                    @click="toggleStatus(cat)"
                                    :title="cat.is_active ? 'Click to disable' : 'Click to activate'"
                                    :class="[
                                        cat.is_active ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-400',
                                        'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors duration-200 ease-in-out focus:outline-none'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            cat.is_active ? 'translate-x-4' : 'translate-x-0',
                                            'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xs transition duration-200'
                                        ]"
                                    />
                                </button>
                            </div>

                            <!-- Hierarchy Breadcrumb / Parent Info -->
                            <div v-if="cat.parent" class="mb-2.5">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-800/40">
                                    <CornerDownRight class="w-3 h-3" />
                                    Sub of {{ cat.parent.name }}
                                </span>
                            </div>
                            <div v-else class="mb-2.5">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/40">
                                    <Layers class="w-3 h-3" />
                                    Root Category
                                </span>
                            </div>

                            <!-- Description -->
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 min-h-[2rem]">
                                {{ cat.description || 'No description provided for this category.' }}
                            </p>

                            <!-- Subcategories Chips (if any) -->
                            <div v-if="cat.children && cat.children.length > 0" class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/80">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5 flex items-center justify-between">
                                    <span>Subcategories ({{ cat.children.length }})</span>
                                </p>
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="child in cat.children.slice(0, 4)"
                                        :key="child.id"
                                        class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] rounded-lg font-medium"
                                    >
                                        {{ child.name }}
                                    </span>
                                    <span v-if="cat.children.length > 4" class="px-1.5 py-0.5 text-slate-400 text-[10px]">
                                        +{{ cat.children.length - 4 }} more
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="mt-4 pt-3.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <Link
                                :href="route('admin.products.index', { category: cat.id })"
                                class="text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-1.5 transition-colors group/link"
                                title="View products in this category"
                            >
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-mono text-[11px] font-bold">
                                    {{ cat.products_count }}
                                </span>
                                <span class="text-[11px]">Products</span>
                                <ExternalLink class="w-3 h-3 text-slate-400 group-hover/link:translate-x-0.5 group-hover/link:-translate-y-0.5 transition-transform" />
                            </Link>

                            <div class="flex items-center gap-1">
                                <button
                                    @click="openCreateModal(cat.id)"
                                    class="p-1.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors cursor-pointer"
                                    title="Add Subcategory under this"
                                >
                                    <Plus class="w-4 h-4" />
                                </button>
                                <button
                                    @click="openEditModal(cat)"
                                    class="p-1.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors cursor-pointer"
                                    title="Edit Category"
                                >
                                    <Edit3 class="w-4 h-4" />
                                </button>
                                <button
                                    @click="deleteCategory(cat)"
                                    class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                    title="Delete Category"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= VIEW 3: STRUCTURED DATA TABLE ================= -->
            <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3.5">Category Name & Slug</th>
                                <th class="px-4 py-3.5">Hierarchy Tier</th>
                                <th class="px-4 py-3.5 text-center">Subcategories</th>
                                <th class="px-4 py-3.5 text-center">Products</th>
                                <th class="px-4 py-3.5 text-center">Sort Order</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr v-if="filteredCategories.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                    No categories match the specified criteria.
                                </td>
                            </tr>
                            <tr
                                v-for="cat in filteredCategories"
                                :key="cat.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                            >
                                <!-- Name & Media -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            v-if="cat.image"
                                            class="w-9 h-9 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 bg-slate-100"
                                        >
                                            <img :src="cat.image" :alt="cat.name" class="w-full h-full object-cover" />
                                        </div>
                                        <div
                                            v-else
                                            :class="[
                                                getIconGradient(cat.name),
                                                'w-9 h-9 rounded-xl bg-gradient-to-br border flex items-center justify-center shrink-0 shadow-xs'
                                            ]"
                                        >
                                            <component :is="iconMap[cat.icon] || FolderTree" class="w-4 h-4" />
                                        </div>

                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white text-xs">{{ cat.name }}</p>
                                            <span class="font-mono text-[10px] text-slate-400">/{{ cat.slug }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Hierarchy Tier -->
                                <td class="px-4 py-3.5">
                                    <div v-if="cat.parent" class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                                        <CornerDownRight class="w-3.5 h-3.5 text-purple-500" />
                                        <span class="font-semibold text-xs">{{ cat.parent.name }}</span>
                                    </div>
                                    <div v-else class="flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400 font-semibold text-xs">
                                        <Layers class="w-3.5 h-3.5" />
                                        <span>Root Department</span>
                                    </div>
                                </td>

                                <!-- Subcategories Count -->
                                <td class="px-4 py-3.5 text-center">
                                    <span v-if="cat.children_count > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 font-mono">
                                        {{ cat.children_count }} subs
                                    </span>
                                    <span v-else class="text-slate-400 text-xs">—</span>
                                </td>

                                <!-- Products Count -->
                                <td class="px-4 py-3.5 text-center">
                                    <Link
                                        :href="route('admin.products.index', { category: cat.id })"
                                        class="inline-flex items-center gap-1 font-mono font-bold text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        {{ cat.products_count }}
                                        <ExternalLink class="w-3 h-3 text-slate-400" />
                                    </Link>
                                </td>

                                <!-- Sort Order -->
                                <td class="px-4 py-3.5 text-center font-mono text-xs text-slate-500">
                                    #{{ cat.sort_order }}
                                </td>

                                <!-- Active Status -->
                                <td class="px-4 py-3.5 text-center">
                                    <button
                                        @click="toggleStatus(cat)"
                                        :class="[
                                            cat.is_active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700',
                                            'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                cat.is_active ? 'translate-x-4' : 'translate-x-0',
                                                'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xs transition duration-200'
                                            ]"
                                        />
                                    </button>
                                </td>

                                <!-- Action Buttons -->
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="openCreateModal(cat.id)"
                                            class="p-1.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors"
                                            title="Add Subcategory"
                                        >
                                            <Plus class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="openEditModal(cat)"
                                            class="p-1.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors"
                                            title="Edit Category"
                                        >
                                            <Edit3 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="deleteCategory(cat)"
                                            class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                                            title="Delete Category"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= ADD / EDIT CATEGORY MODAL ================= -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto"
            >
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-xl overflow-hidden shadow-2xl my-8">
                    <!-- Modal Header -->
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-850">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                <FolderTree class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    {{ editingCategory ? `Edit "${editingCategory.name}"` : 'Create New Category' }}
                                </h3>
                                <p class="text-xs text-slate-400">Configure hierarchy placement, visuals, and storefront metadata.</p>
                            </div>
                        </div>
                        <button
                            @click="isModalOpen = false"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Hierarchy Preview Box -->
                    <div class="px-6 py-3 bg-indigo-50/50 dark:bg-indigo-950/20 border-b border-indigo-100/50 dark:border-indigo-900/30 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500 dark:text-slate-400 font-medium">Placement:</span>
                            <span v-if="!form.parent_id" class="inline-flex items-center gap-1 font-bold text-indigo-600 dark:text-indigo-400">
                                <Layers class="w-3.5 h-3.5" />
                                Top-Level Root Department
                            </span>
                            <span v-else class="inline-flex items-center gap-1 font-bold text-purple-600 dark:text-purple-400">
                                <CornerDownRight class="w-3.5 h-3.5" />
                                Subcategory under {{ selectedParent?.name || 'Selected Parent' }}
                            </span>
                        </div>
                        <span class="text-[10px] text-slate-400 font-mono">
                            {{ form.slug ? `/${form.slug}` : '' }}
                        </span>
                    </div>

                    <!-- Modal Form -->
                    <form @submit.prevent="submit" class="p-6 space-y-5 max-h-[72vh] overflow-y-auto">
                        <!-- Category Name & Slug -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Category Name *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="e.g. Mens Casual Shirts"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 font-medium focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                        URL Slug *
                                    </label>
                                    <button
                                        type="button"
                                        @click="autoGenerateSlug = !autoGenerateSlug"
                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold hover:underline"
                                    >
                                        {{ autoGenerateSlug ? 'Auto' : 'Custom' }}
                                    </button>
                                </div>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-mono">/</span>
                                    <input
                                        v-model="form.slug"
                                        :readonly="autoGenerateSlug"
                                        type="text"
                                        required
                                        placeholder="category-slug"
                                        class="w-full pl-7 pr-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 font-mono focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Parent Category & Sort Order -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Parent Category (Hierarchy)
                                </label>
                                <CustomSelect
                                    v-model="form.parent_id"
                                    :options="parentOptions"
                                    placeholder="Select Parent Category"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Display Sort Order
                                </label>
                                <input
                                    v-model.number="form.sort_order"
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- Category Image / Banner Upload -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Category Image / Banner (Optional)
                            </label>

                            <div v-if="form.image" class="relative rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 w-full h-32 bg-slate-100 dark:bg-slate-800 group">
                                <img :src="form.image" alt="Category Preview" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <button
                                        type="button"
                                        @click="removeImage"
                                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-colors flex items-center gap-1 cursor-pointer"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" /> Remove Image
                                    </button>
                                </div>
                            </div>

                            <div
                                v-else
                                class="relative border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-500 rounded-2xl p-4 text-center transition-colors bg-slate-50/50 dark:bg-slate-800/40"
                            >
                                <div v-if="isUploadingImage" class="flex flex-col items-center justify-center py-2">
                                    <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin mb-2"></div>
                                    <span class="text-xs text-indigo-600 font-bold">Uploading Category Image...</span>
                                </div>
                                <label v-else class="cursor-pointer block">
                                    <UploadCloud class="w-6 h-6 text-slate-400 mx-auto mb-1" />
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200">
                                        Click to upload image <span class="text-slate-400 font-normal">or drag & drop</span>
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">PNG, JPG, WEBP up to 2MB</p>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        @change="handleImageUpload"
                                        class="hidden"
                                    />
                                </label>
                            </div>
                        </div>

                        <!-- Department Icon Grid Selector -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                                Department Icon
                            </label>
                            <div class="grid grid-cols-4 sm:grid-cols-5 gap-2 max-h-36 overflow-y-auto p-1 border border-slate-100 dark:border-slate-800 rounded-2xl bg-slate-50/50 dark:bg-slate-850">
                                <button
                                    v-for="opt in availableIcons"
                                    :key="opt.value"
                                    type="button"
                                    @click="form.icon = opt.value"
                                    :class="[
                                        form.icon === opt.value
                                            ? 'bg-indigo-600 text-white font-bold ring-2 ring-indigo-600 ring-offset-2 dark:ring-offset-slate-900 shadow-sm'
                                            : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60 border border-slate-200/60 dark:border-slate-700/60',
                                        'p-2 rounded-xl text-center flex flex-col items-center justify-center gap-1 transition-all cursor-pointer'
                                    ]"
                                    :title="opt.label"
                                >
                                    <component :is="opt.icon" class="w-4 h-4" />
                                    <span class="text-[9px] truncate max-w-full">{{ opt.label.split(' ')[0] }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description (Optional)</label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                placeholder="Summary for SEO and storefront collection pages..."
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            ></textarea>
                        </div>

                        <!-- Active Switch -->
                        <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white block">Active in Storefront</span>
                                <span class="text-[11px] text-slate-400">Controls visibility in menus, filter pills, and product catalog</span>
                            </div>
                            <button
                                type="button"
                                @click="form.is_active = !form.is_active"
                                :class="[
                                    form.is_active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700',
                                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors duration-200 ease-in-out focus:outline-none'
                                ]"
                            >
                                <span
                                    :class="[
                                        form.is_active ? 'translate-x-5' : 'translate-x-0',
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-xs transition duration-200'
                                    ]"
                                />
                            </button>
                        </div>

                        <!-- Form Actions -->
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                @click="isModalOpen = false"
                                class="px-4 py-2.5 rounded-2xl text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || isUploadingImage"
                                class="px-5 py-2.5 rounded-2xl text-xs font-bold bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white shadow-md shadow-indigo-600/25 transition-all cursor-pointer flex items-center gap-2"
                            >
                                <Check class="w-4 h-4" />
                                {{ editingCategory ? 'Update Category' : 'Create Category' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
