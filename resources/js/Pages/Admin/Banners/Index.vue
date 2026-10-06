<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import {
    Plus,
    Image,
    Edit3,
    Trash2,
    X,
    Layers,
    Search,
    SlidersHorizontal,
    ExternalLink,
    Check,
    UploadCloud,
    Calendar,
    ArrowUpRight,
    Eye,
    Smartphone,
    Monitor,
    Sparkles,
    Megaphone,
    Clock
} from 'lucide-vue-next';

const props = defineProps({
    banners: Array,
    metrics: Object,
    filters: Object,
});

// Filter state
const activePlacement = ref(props.filters?.placement || '');
const activeStatus = ref(props.filters?.status || '');
const searchQuery = ref(props.filters?.search || '');

const placementOptions = [
    { value: '', label: 'All Placements' },
    { value: 'hero_slider', label: 'Hero Carousel Slider' },
    { value: 'home_banner', label: 'Mid-Page Promo Banner' },
    { value: 'category_banner', label: 'Category Header Banner' },
    { value: 'popup_promo', label: 'Popup Promotional Modal' },
];

const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'active', label: 'Active & Scheduled' },
    { value: 'inactive', label: 'Inactive / Draft' },
];

const filteredBanners = computed(() => {
    let list = [...(props.banners || [])];

    if (activePlacement.value) {
        list = list.filter(b => b.placement === activePlacement.value);
    }

    if (activeStatus.value === 'active') {
        list = list.filter(b => b.is_active);
    } else if (activeStatus.value === 'inactive') {
        list = list.filter(b => !b.is_active);
    }

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(b =>
            b.title.toLowerCase().includes(q) ||
            (b.subtitle && b.subtitle.toLowerCase().includes(q)) ||
            (b.badge_text && b.badge_text.toLowerCase().includes(q))
        );
    }

    return list;
});

// Modal state
const isModalOpen = ref(false);
const editingBanner = ref(null);
const isUploadingDesktop = ref(false);
const isUploadingMobile = ref(false);

const form = useForm({
    title: '',
    subtitle: '',
    badge_text: '',
    image_url: '',
    mobile_image_url: '',
    button_text: 'Shop Now',
    link_url: '',
    placement: 'hero_slider',
    sort_order: 0,
    is_active: true,
    starts_at: '',
    ends_at: '',
});

function openCreateModal() {
    editingBanner.value = null;
    form.reset();
    form.placement = activePlacement.value || 'hero_slider';
    form.button_text = 'Shop Now';
    form.is_active = true;
    form.sort_order = (props.banners?.length || 0) + 1;
    isModalOpen.value = true;
}

function openEditModal(banner) {
    editingBanner.value = banner;
    form.title = banner.title;
    form.subtitle = banner.subtitle || '';
    form.badge_text = banner.badge_text || '';
    form.image_url = banner.image_url;
    form.mobile_image_url = banner.mobile_image_url || '';
    form.button_text = banner.button_text || '';
    form.link_url = banner.link_url || '';
    form.placement = banner.placement;
    form.sort_order = banner.sort_order;
    form.is_active = !!banner.is_active;
    form.starts_at = banner.starts_at ? banner.starts_at.substring(0, 16) : '';
    form.ends_at = banner.ends_at ? banner.ends_at.substring(0, 16) : '';
    isModalOpen.value = true;
}

function handleImageUpload(e, isMobile = false) {
    const file = e.target.files?.[0];
    if (!file) return;

    const uploadingRef = isMobile ? isUploadingMobile : isUploadingDesktop;
    uploadingRef.value = true;

    const data = new FormData();
    data.append('image', file);

    axios.post(route('admin.banners.upload-image'), data, {
        headers: { 'Content-Type': 'multipart/form-data' }
    })
    .then(res => {
        if (isMobile) {
            form.mobile_image_url = res.data.url;
        } else {
            form.image_url = res.data.url;
        }
    })
    .catch(err => {
        alert(err.response?.data?.message || 'Failed to upload image. Max file size: 3MB.');
    })
    .finally(() => {
        uploadingRef.value = false;
        e.target.value = '';
    });
}

function submit() {
    if (editingBanner.value) {
        form.put(route('admin.banners.update', editingBanner.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post(route('admin.banners.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
}

function toggleStatus(banner) {
    router.patch(route('admin.banners.toggle-status', banner.id), {}, {
        preserveScroll: true,
    });
}

function deleteBanner(banner) {
    if (confirm(`Are you sure you want to delete banner "${banner.title}"?`)) {
        router.delete(route('admin.banners.destroy', banner.id), {
            preserveScroll: true,
        });
    }
}

function formatPlacement(placement) {
    const map = {
        hero_slider: 'Hero Slider',
        home_banner: 'Mid-Page Promo',
        category_banner: 'Category Header',
        popup_promo: 'Popup Modal',
    };
    return map[placement] || placement;
}
</script>

<template>
    <AdminLayout>
        <Head title="Banners & Sliders Management" />

        <template #header>
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    Banners & Hero Sliders
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Control homepage hero carousels, promotional visual banners, CTA links, and campaign scheduling.
                </p>
            </div>
        </template>

        <div class="space-y-6 pb-12">
            <!-- Metric KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Banners</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ metrics.total_banners }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Across all placements</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                            <Image class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-purple-500">Hero Carousel Slides</p>
                            <p class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ metrics.hero_slides }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Primary homepage hero</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                            <Layers class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-500">Active & Published</p>
                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ metrics.active_count }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Currently live on storefront</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                            <Sparkles class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-amber-500">Mid-Page Promos</p>
                            <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ metrics.home_promos }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Feature promotional banners</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                            <Megaphone class="w-6 h-6" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toolbar & Filter Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
                    <div class="relative w-full sm:w-64">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search banner title, subtitle..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="w-48">
                        <CustomSelect
                            v-model="activePlacement"
                            :options="placementOptions"
                            placeholder="Placement"
                        />
                    </div>

                    <div class="w-36">
                        <CustomSelect
                            v-model="activeStatus"
                            :options="statusOptions"
                            placeholder="Status"
                        />
                    </div>

                    <button
                        v-if="searchQuery || activePlacement || activeStatus"
                        @click="searchQuery = ''; activePlacement = ''; activeStatus = '';"
                        class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer"
                    >
                        <X class="w-3.5 h-3.5" /> Reset
                    </button>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end shrink-0">
                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all cursor-pointer active:scale-95 shrink-0"
                    >
                        <Plus class="w-4 h-4" /> Add New Banner
                    </button>
                </div>
            </div>

            <!-- Visual Slider & Banner Cards -->
            <div v-if="filteredBanners.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center">
                <Image class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No banners found</h3>
                <p class="text-xs text-slate-400 mt-1">Create your first hero slider or promotional banner to enhance your storefront.</p>
                <button
                    @click="openCreateModal"
                    class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5"
                >
                    <Plus class="w-4 h-4" /> Add Banner
                </button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="banner in filteredBanners"
                    :key="banner.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs hover:border-indigo-500/50 hover:shadow-md transition-all flex flex-col justify-between group"
                >
                    <!-- Visual Card Simulation Preview -->
                    <div class="relative h-56 w-full bg-slate-900 overflow-hidden">
                        <img
                            :src="banner.image_url"
                            :alt="banner.title"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-90"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>

                        <!-- Badge Tag on top-left -->
                        <div class="absolute top-4 left-4 flex items-center gap-2">
                            <span v-if="banner.badge_text" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500 text-white shadow-sm">
                                {{ banner.badge_text }}
                            </span>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-white/20 backdrop-blur-md text-white border border-white/20">
                                {{ formatPlacement(banner.placement) }}
                            </span>
                        </div>

                        <!-- Active Toggle on top-right -->
                        <div class="absolute top-4 right-4">
                            <button
                                @click="toggleStatus(banner)"
                                :title="banner.is_active ? 'Click to deactivate' : 'Click to activate'"
                                :class="[
                                    banner.is_active ? 'bg-emerald-500' : 'bg-slate-700/80',
                                    'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors duration-200 shadow-md backdrop-blur-md'
                                ]"
                            >
                                <span
                                    :class="[
                                        banner.is_active ? 'translate-x-4' : 'translate-x-0',
                                        'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200'
                                    ]"
                                />
                            </button>
                        </div>

                        <!-- Slide Text Content Overlay -->
                        <div class="absolute bottom-4 left-4 right-4">
                            <p v-if="banner.subtitle" class="text-xs font-semibold text-indigo-300 drop-shadow-sm">{{ banner.subtitle }}</p>
                            <h3 class="text-lg font-black text-white leading-tight drop-shadow-md mt-0.5">{{ banner.title }}</h3>

                            <div class="flex items-center justify-between mt-3">
                                <span v-if="banner.button_text" class="px-3 py-1 rounded-xl bg-white text-slate-900 text-xs font-bold shadow-md flex items-center gap-1.5">
                                    {{ banner.button_text }}
                                    <ArrowUpRight class="w-3 h-3" />
                                </span>
                                <span v-else></span>

                                <span v-if="banner.mobile_image_url" class="text-[10px] text-white/70 flex items-center gap-1 bg-black/40 px-2 py-0.5 rounded-md backdrop-blur-sm">
                                    <Smartphone class="w-3 h-3" /> Responsive Mobile Asset
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Banner Details & Controls Bar -->
                    <div class="p-4 bg-slate-50/50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-3 text-slate-500 dark:text-slate-400 text-[11px]">
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-300">Sort: #{{ banner.sort_order }}</span>
                            <span v-if="banner.starts_at || banner.ends_at" class="flex items-center gap-1">
                                <Clock class="w-3 h-3 text-indigo-500" />
                                Scheduled
                            </span>
                            <span v-else class="text-slate-400">Continuous</span>
                            <span v-if="banner.link_url" class="truncate max-w-[140px] text-slate-400" :title="banner.link_url">
                                🔗 {{ banner.link_url }}
                            </span>
                        </div>

                        <div class="flex items-center gap-1">
                            <button
                                @click="openEditModal(banner)"
                                class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors"
                                title="Edit Banner"
                            >
                                <Edit3 class="w-4 h-4" />
                            </button>
                            <button
                                @click="deleteBanner(banner)"
                                class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                                title="Delete Banner"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= ADD / EDIT BANNER MODAL ================= -->
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
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl my-8">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                <Image class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    {{ editingBanner ? 'Edit Banner / Slide' : 'Create Banner / Slider' }}
                                </h3>
                                <p class="text-xs text-slate-400">Configure visual slide imagery, call to action, and timing.</p>
                            </div>
                        </div>
                        <button @click="isModalOpen = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        <!-- Title & Subtitle -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Headline Title *</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    required
                                    placeholder="e.g. Summer Essentials 2026"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 font-bold focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Subtitle / Tagline</label>
                                <input
                                    v-model="form.subtitle"
                                    type="text"
                                    placeholder="e.g. Up to 40% Off Premium Gear"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- Badge Text & Placement -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Promo Badge Tag (Optional)</label>
                                <input
                                    v-model="form.badge_text"
                                    type="text"
                                    placeholder="e.g. FLASH DEAL, NEW, LIMITED"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Placement *</label>
                                <CustomSelect
                                    v-model="form.placement"
                                    :options="placementOptions.filter(o => o.value)"
                                    placeholder="Select Placement"
                                />
                            </div>
                        </div>

                        <!-- CTA Button Text & Destination Link -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Button CTA Text</label>
                                <input
                                    v-model="form.button_text"
                                    type="text"
                                    placeholder="e.g. Shop Now"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Destination Target Link</label>
                                <input
                                    v-model="form.link_url"
                                    type="text"
                                    placeholder="e.g. /products?category=apparel or /campaigns"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 font-mono focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- Desktop Banner Image Upload -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Desktop Banner Image (1920x600 recommended) *</label>
                            <div v-if="form.image_url" class="relative rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 h-32 bg-slate-900 group">
                                <img :src="form.image_url" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <button
                                        type="button"
                                        @click="form.image_url = ''"
                                        class="px-3 py-1.5 bg-rose-600 text-white rounded-xl text-xs font-bold flex items-center gap-1"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" /> Replace Image
                                    </button>
                                </div>
                            </div>
                            <div
                                v-else
                                class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-indigo-500 rounded-2xl p-4 text-center cursor-pointer bg-slate-50/50 dark:bg-slate-800/40"
                            >
                                <div v-if="isUploadingDesktop" class="py-2 text-indigo-600 font-bold text-xs">Uploading desktop banner...</div>
                                <label v-else class="cursor-pointer block">
                                    <UploadCloud class="w-6 h-6 text-slate-400 mx-auto mb-1" />
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Upload Desktop Banner</span>
                                    <input type="file" accept="image/*" @change="handleImageUpload($event, false)" class="hidden" />
                                </label>
                            </div>
                        </div>

                        <!-- Mobile Banner Image Upload (Optional) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Mobile Optimized Banner (Optional, 750x600 recommended)</label>
                            <div v-if="form.mobile_image_url" class="relative rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 h-24 bg-slate-900 group">
                                <img :src="form.mobile_image_url" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <button
                                        type="button"
                                        @click="form.mobile_image_url = ''"
                                        class="px-3 py-1.5 bg-rose-600 text-white rounded-xl text-xs font-bold flex items-center gap-1"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" /> Remove
                                    </button>
                                </div>
                            </div>
                            <div
                                v-else
                                class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-indigo-500 rounded-2xl p-3 text-center cursor-pointer bg-slate-50/50 dark:bg-slate-800/40"
                            >
                                <div v-if="isUploadingMobile" class="py-2 text-indigo-600 font-bold text-xs">Uploading mobile banner...</div>
                                <label v-else class="cursor-pointer block">
                                    <Smartphone class="w-5 h-5 text-slate-400 mx-auto mb-1" />
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Upload Mobile Banner (Optional)</span>
                                    <input type="file" accept="image/*" @change="handleImageUpload($event, true)" class="hidden" />
                                </label>
                            </div>
                        </div>

                        <!-- Sort Order & Scheduling -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Sort Order</label>
                                <input
                                    v-model.number="form.sort_order"
                                    type="number"
                                    min="0"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-bold font-mono text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Start Schedule (Optional)</label>
                                <input
                                    v-model="form.starts_at"
                                    type="datetime-local"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">End Schedule (Optional)</label>
                                <input
                                    v-model="form.ends_at"
                                    type="datetime-local"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white block">Active in Storefront</span>
                                <span class="text-[11px] text-slate-400">Controls whether this slide is visible to customers</span>
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

                        <!-- Form Actions -->
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                @click="isModalOpen = false"
                                class="px-4 py-2.5 rounded-2xl text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || isUploadingDesktop || !form.image_url"
                                class="px-5 py-2.5 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all flex items-center gap-2"
                            >
                                <Check class="w-4 h-4" />
                                {{ editingBanner ? 'Update Banner' : 'Create Banner' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
