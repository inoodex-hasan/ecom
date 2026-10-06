<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import {
    Plus,
    Zap,
    Flame,
    Edit3,
    Trash2,
    X,
    Search,
    Calendar,
    Clock,
    Check,
    UploadCloud,
    Tag,
    ShoppingBag,
    Percent,
    AlertCircle,
    TrendingUp,
    Timer
} from 'lucide-vue-next';

const props = defineProps({
    flashSales: Array,
    metrics: Object,
    availableProducts: Array,
});

// Filters
const searchQuery = ref('');
const statusFilter = ref('');

const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Live / Running', value: 'running' },
    { label: 'Upcoming', value: 'upcoming' },
    { label: 'Expired / Ended', value: 'expired' },
    { label: 'Disabled / Inactive', value: 'inactive' },
];

const filteredFlashSales = computed(() => {
    let list = props.flashSales || [];

    if (statusFilter.value) {
        if (statusFilter.value === 'inactive') {
            list = list.filter(sale => !sale.is_active);
        } else {
            list = list.filter(sale => {
                if (statusFilter.value === 'running') {
                    return sale.is_active && getCountdown(sale).status === 'running';
                }
                return getCountdown(sale).status === statusFilter.value;
            });
        }
    }

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(sale => {
            const matchTitle = sale.title?.toLowerCase().includes(q);
            const matchDesc = sale.description?.toLowerCase().includes(q);
            const matchProducts = sale.items?.some(item =>
                item.product?.name?.toLowerCase().includes(q) ||
                item.product?.sku?.toLowerCase().includes(q)
            );
            return matchTitle || matchDesc || matchProducts;
        });
    }

    return list;
});

// Real-time ticking clock for countdowns
const currentTime = ref(new Date());
let timerInterval = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

function getCountdown(sale) {
    const now = currentTime.value.getTime();
    const start = new Date(sale.starts_at).getTime();
    const end = new Date(sale.ends_at).getTime();

    if (now < start) {
        const diff = Math.max(0, start - now);
        return { status: 'upcoming', text: formatTimeDiff(diff), label: 'Starts in' };
    } else if (now >= start && now <= end) {
        const diff = Math.max(0, end - now);
        return { status: 'running', text: formatTimeDiff(diff), label: 'Ends in' };
    } else {
        return { status: 'expired', text: 'Ended', label: 'Campaign Expired' };
    }
}

function formatTimeDiff(ms) {
    const totalSecs = Math.floor(ms / 1000);
    const days = Math.floor(totalSecs / 86400);
    const hours = Math.floor((totalSecs % 86400) / 3600);
    const minutes = Math.floor((totalSecs % 3600) / 60);
    const seconds = totalSecs % 60;

    if (days > 0) {
        return `${days}d ${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m`;
    }
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

// Modal State
const isModalOpen = ref(false);
const editingSale = ref(null);
const isUploadingBanner = ref(false);
const productSearch = ref('');

const form = useForm({
    title: '',
    slug: '',
    banner_image: '',
    description: '',
    starts_at: '',
    ends_at: '',
    is_active: true,
    items: [], // [{ product_id, product_name, price, flash_price, discount_percentage, quantity_limit }]
});

const filteredAvailableProducts = computed(() => {
    if (!productSearch.value) return props.availableProducts || [];
    const q = productSearch.value.toLowerCase().trim();
    return (props.availableProducts || []).filter(p =>
        p.name.toLowerCase().includes(q) ||
        p.sku.toLowerCase().includes(q)
    );
});

function openCreateModal() {
    editingSale.value = null;
    form.reset();
    form.is_active = true;
    const now = new Date();
    const tomorrow = new Date(now.getTime() + 86400000 * 2);
    form.starts_at = now.toISOString().substring(0, 16);
    form.ends_at = tomorrow.toISOString().substring(0, 16);
    isModalOpen.value = true;
}

function openEditModal(sale) {
    editingSale.value = sale;
    form.title = sale.title;
    form.slug = sale.slug;
    form.banner_image = sale.banner_image || '';
    form.description = sale.description || '';
    form.starts_at = sale.starts_at ? sale.starts_at.substring(0, 16) : '';
    form.ends_at = sale.ends_at ? sale.ends_at.substring(0, 16) : '';
    form.is_active = !!sale.is_active;

    form.items = (sale.items || []).map(item => ({
        product_id: item.product_id,
        product_name: item.product?.name || 'Product',
        product_sku: item.product?.sku || '',
        price: item.product?.price || 0,
        flash_price: item.flash_price,
        discount_percentage: item.discount_percentage,
        quantity_limit: item.quantity_limit || '',
    }));

    isModalOpen.value = true;
}

function addProductToSale(product) {
    if (form.items.some(i => i.product_id === product.id)) return;

    const discountRate = 20; // default 20% off
    const flashPrice = Math.max(0, +(product.price * (1 - discountRate / 100)).toFixed(2));

    form.items.push({
        product_id: product.id,
        product_name: product.name,
        product_sku: product.sku,
        price: product.price,
        flash_price: flashPrice,
        discount_percentage: discountRate,
        quantity_limit: 50,
    });
}

function removeProductFromSale(index) {
    form.items.splice(index, 1);
}

function onFlashPriceChange(item) {
    if (item.price > 0 && item.flash_price >= 0) {
        item.discount_percentage = Math.max(0, Math.round(((item.price - item.flash_price) / item.price) * 100));
    }
}

function onDiscountPercentChange(item) {
    if (item.price > 0 && item.discount_percentage >= 0) {
        item.flash_price = Math.max(0, +(item.price * (1 - item.discount_percentage / 100)).toFixed(2));
    }
}

function handleBannerUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    isUploadingBanner.value = true;
    const data = new FormData();
    data.append('image', file);

    axios.post(route('admin.campaigns.upload-image'), data, {
        headers: { 'Content-Type': 'multipart/form-data' }
    })
    .then(res => {
        form.banner_image = res.data.url;
    })
    .catch(err => {
        alert(err.response?.data?.message || 'Failed to upload banner. Max size: 3MB.');
    })
    .finally(() => {
        isUploadingBanner.value = false;
        e.target.value = '';
    });
}

function submit() {
    if (form.items.length === 0) {
        alert('Please attach at least one product to this campaign.');
        return;
    }

    if (editingSale.value) {
        form.put(route('admin.campaigns.update', editingSale.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post(route('admin.campaigns.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
}

function toggleStatus(sale) {
    router.patch(route('admin.campaigns.toggle-status', sale.id), {}, {
        preserveScroll: true,
    });
}

function deleteSale(sale) {
    if (confirm(`Are you sure you want to delete campaign "${sale.title}"?`)) {
        router.delete(route('admin.campaigns.destroy', sale.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Campaigns - Admin" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                        Campaigns & Promotional Events
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Drive urgency with live countdown timers, steep discounts, and limited-time promotional campaigns.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6 pb-12">
            <!-- Metric KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Campaigns</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ metrics.total_campaigns }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">All-time promotional events</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold">
                            <Zap class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-rose-500">Live Running Now</p>
                            <p class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ metrics.active_running }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Active countdown ticking</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center font-bold animate-pulse">
                            <Flame class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-500">Items On Sale</p>
                            <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ metrics.total_items_on_sale }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Products with campaign discounts</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                            <Tag class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-500">Units Claimed</p>
                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ metrics.total_units_claimed }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Sold at promotional prices</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                            <TrendingUp class="w-6 h-6" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toolbar & Filter Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
                    <div class="relative w-full sm:w-72">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search campaigns, products..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-amber-500"
                        />
                    </div>

                    <div class="w-44">
                        <CustomSelect
                            v-model="statusFilter"
                            :options="statusOptions"
                            placeholder="Status"
                        />
                    </div>

                    <button
                        v-if="searchQuery || statusFilter"
                        @click="searchQuery = ''; statusFilter = '';"
                        class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer"
                    >
                        <X class="w-3.5 h-3.5" /> Reset
                    </button>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end shrink-0">
                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-md shadow-amber-500/25 transition-all cursor-pointer active:scale-95 shrink-0"
                    >
                        <Plus class="w-4 h-4" /> Launch Campaign
                    </button>
                </div>
            </div>

            <!-- Campaign Cards List -->
            <div v-if="flashSales.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center">
                <Zap class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No campaigns created yet</h3>
                <p class="text-xs text-slate-400 mt-1">Set up a countdown promotional event with special prices to drive conversion spikes.</p>
                <button
                    @click="openCreateModal"
                    class="mt-4 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                >
                    <Plus class="w-4 h-4" /> Launch Campaign
                </button>
            </div>

            <div v-else-if="filteredFlashSales.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center">
                <Search class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No campaigns match your filters</h3>
                <p class="text-xs text-slate-400 mt-1">Try adjusting your search keywords or status filter.</p>
                <button
                    @click="searchQuery = ''; statusFilter = '';"
                    class="mt-4 px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                >
                    <X class="w-3.5 h-3.5" /> Clear Filters
                </button>
            </div>

            <div v-else class="space-y-6">
                <div
                    v-for="sale in filteredFlashSales"
                    :key="sale.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-5"
                >
                    <!-- Header of Campaign: Title, Countdown Timer, and Actions -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold shrink-0">
                                <Flame class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-black text-slate-900 dark:text-white">{{ sale.title }}</h3>
                                    <span
                                        :class="[
                                            getCountdown(sale).status === 'running'
                                                ? 'bg-rose-500 text-white animate-pulse'
                                                : (getCountdown(sale).status === 'upcoming' ? 'bg-indigo-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500'),
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider'
                                        ]"
                                    >
                                        {{ getCountdown(sale).status }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">{{ sale.description || 'Exclusive promotional campaign event.' }}</p>
                            </div>
                        </div>

                        <!-- Live Countdown Pill -->
                        <div class="flex items-center gap-4">
                            <div class="px-4 py-2 rounded-2xl bg-slate-950 text-white flex items-center gap-2.5 shadow-sm border border-slate-800">
                                <Timer class="w-4 h-4 text-amber-400 animate-spin" style="animation-duration: 4s;" />
                                <div>
                                    <span class="text-[9px] uppercase tracking-wider text-slate-400 block -mb-0.5">
                                        {{ getCountdown(sale).label }}
                                    </span>
                                    <span class="font-mono text-sm font-black text-amber-400">
                                        {{ getCountdown(sale).text }}
                                    </span>
                                </div>
                            </div>

                            <button
                                @click="toggleStatus(sale)"
                                :title="sale.is_active ? 'Click to deactivate' : 'Click to activate'"
                                :class="[
                                    sale.is_active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700',
                                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors'
                                ]"
                            >
                                <span
                                    :class="[
                                        sale.is_active ? 'translate-x-5' : 'translate-x-0',
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200'
                                    ]"
                                />
                            </button>

                            <div class="flex items-center gap-1">
                                <button
                                    @click="openEditModal(sale)"
                                    class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors cursor-pointer"
                                    title="Edit Campaign"
                                >
                                    <Edit3 class="w-4 h-4" />
                                </button>
                                <button
                                    @click="deleteSale(sale)"
                                    class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                    title="Delete Campaign"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Products in this Campaign Grid -->
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3">
                            Products in Campaign ({{ sale.items?.length || 0 }})
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                            <div
                                v-for="item in sale.items"
                                :key="item.id"
                                class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 flex items-center justify-between gap-3"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <img
                                        :src="item.product?.primary_image || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=100&auto=format&fit=crop&q=60'"
                                        class="w-10 h-10 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shrink-0 bg-slate-100"
                                    />
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ item.product?.name }}</p>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-xs font-black text-rose-600 dark:text-rose-400 font-mono">${{ item.flash_price }}</span>
                                            <span class="text-[10px] text-slate-400 line-through font-mono">${{ item.product?.price }}</span>
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
                                                -{{ Math.round(item.discount_percentage) }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="item.quantity_limit" class="text-right shrink-0">
                                    <span class="text-[10px] font-mono text-slate-400 block">{{ item.sold_count }}/{{ item.quantity_limit }}</span>
                                    <span class="text-[9px] font-bold text-amber-500">Limit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= LAUNCH / EDIT CAMPAIGN MODAL ================= -->
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
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-3xl overflow-hidden shadow-2xl my-8">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold">
                                <Zap class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    {{ editingSale ? 'Edit Promotional Campaign' : 'Launch New Campaign' }}
                                </h3>
                                <p class="text-xs text-slate-400">Set campaign timeline, add featured products, and configure price drops.</p>
                            </div>
                        </div>
                        <button @click="isModalOpen = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 cursor-pointer">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                        <!-- Campaign Title & Slug -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Campaign Title *</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    required
                                    placeholder="e.g. 72-Hour Weekend Flash Sale"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 font-bold focus:ring-2 focus:ring-amber-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Campaign Slug</label>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    placeholder="auto-generated-slug"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 font-mono focus:ring-2 focus:ring-amber-500"
                                />
                            </div>
                        </div>

                        <!-- Starts At & Ends At -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Start Date & Time *</label>
                                <input
                                    v-model="form.starts_at"
                                    type="datetime-local"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 font-mono focus:ring-2 focus:ring-amber-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">End Date & Time *</label>
                                <input
                                    v-model="form.ends_at"
                                    type="datetime-local"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 font-mono focus:ring-2 focus:ring-amber-500"
                                />
                            </div>
                        </div>

                        <!-- Product Selector -->
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Attach Products to Campaign ({{ form.items.length }} Selected) *
                            </label>

                            <div class="relative">
                                <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input
                                    v-model="productSearch"
                                    type="text"
                                    placeholder="Search catalog products to add..."
                                    class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-amber-500"
                                />
                            </div>

                            <!-- Available Products Quick Picker Chips -->
                            <div class="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto p-2 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200 dark:border-slate-700">
                                <button
                                    v-for="p in filteredAvailableProducts"
                                    :key="p.id"
                                    type="button"
                                    @click="addProductToSale(p)"
                                    :disabled="form.items.some(i => i.product_id === p.id)"
                                    :class="[
                                        form.items.some(i => i.product_id === p.id)
                                            ? 'opacity-40 cursor-not-allowed bg-slate-200 dark:bg-slate-700'
                                            : 'hover:bg-amber-100 dark:hover:bg-amber-950/60 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700',
                                        'px-2.5 py-1 rounded-xl text-[11px] font-semibold transition-colors flex items-center gap-1.5 cursor-pointer'
                                    ]"
                                >
                                    <Plus class="w-3 h-3 text-amber-500" />
                                    <span>{{ p.name }}</span>
                                    <span class="font-mono text-slate-400">(${{ p.price }})</span>
                                </button>
                            </div>

                            <!-- Selected Items Pricing Table -->
                            <div v-if="form.items.length > 0" class="border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-100 dark:bg-slate-800 text-[10px] font-bold uppercase text-slate-500">
                                        <tr>
                                            <th class="px-3 py-2">Product</th>
                                            <th class="px-2 py-2">Reg Price</th>
                                            <th class="px-2 py-2">Flash Price ($)</th>
                                            <th class="px-2 py-2">Discount (%)</th>
                                            <th class="px-2 py-2">Qty Limit</th>
                                            <th class="px-2 py-2 text-right">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="(item, idx) in form.items" :key="item.product_id" class="bg-white dark:bg-slate-900">
                                            <td class="px-3 py-2 font-bold text-slate-800 dark:text-slate-200 text-xs">
                                                {{ item.product_name }}
                                            </td>
                                            <td class="px-2 py-2 font-mono text-slate-400">
                                                ${{ item.price }}
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model.number="item.flash_price"
                                                    @input="onFlashPriceChange(item)"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="w-20 px-2 py-1 bg-slate-50 dark:bg-slate-800 border rounded-lg text-xs font-mono font-bold text-rose-600 focus:ring-1 focus:ring-amber-500"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model.number="item.discount_percentage"
                                                    @input="onDiscountPercentChange(item)"
                                                    type="number"
                                                    min="0"
                                                    max="100"
                                                    class="w-16 px-2 py-1 bg-slate-50 dark:bg-slate-800 border rounded-lg text-xs font-mono font-bold focus:ring-1 focus:ring-amber-500"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model.number="item.quantity_limit"
                                                    type="number"
                                                    min="1"
                                                    placeholder="Unlimited"
                                                    class="w-20 px-2 py-1 bg-slate-50 dark:bg-slate-800 border rounded-lg text-xs font-mono focus:ring-1 focus:ring-amber-500"
                                                />
                                            </td>
                                            <td class="px-2 py-2 text-right">
                                                <button
                                                    type="button"
                                                    @click="removeProductFromSale(idx)"
                                                    class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer"
                                                >
                                                    <Trash2 class="w-3.5 h-3.5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white block">Active Campaign Status</span>
                                <span class="text-[11px] text-slate-400">Controls whether countdown & discounts apply automatically during timeline</span>
                            </div>
                            <button
                                type="button"
                                @click="form.is_active = !form.is_active"
                                :class="[
                                    form.is_active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700',
                                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors'
                                ]"
                            >
                                <span
                                    :class="[
                                        form.is_active ? 'translate-x-5' : 'translate-x-0',
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200'
                                    ]"
                                />
                            </button>
                        </div>

                        <!-- Actions -->
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                @click="isModalOpen = false"
                                class="px-4 py-2.5 rounded-2xl text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || form.items.length === 0"
                                class="px-5 py-2.5 rounded-2xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-md shadow-amber-500/25 transition-all flex items-center gap-2 cursor-pointer"
                            >
                                <Check class="w-4 h-4" />
                                {{ editingSale ? 'Update Campaign' : 'Launch Campaign' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
