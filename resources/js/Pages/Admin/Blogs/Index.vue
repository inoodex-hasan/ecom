<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import {
    Plus,
    Newspaper,
    Edit3,
    Trash2,
    X,
    Search,
    Check,
    UploadCloud,
    Calendar,
    Clock,
    Tag,
    User,
    CheckCircle2,
    FileText,
    ExternalLink,
    Sparkles,
    Eye
} from 'lucide-vue-next';

const props = defineProps({
    posts: Object, // paginated
    metrics: Object,
    categories: Array,
    filters: Object,
});

// Post list
const postList = computed(() => {
    return Array.isArray(props.posts) ? props.posts : props.posts?.data || [];
});

// Filters
const searchQuery = ref(props.filters?.search || '');
const categoryFilter = ref(props.filters?.category || '');
const statusFilter = ref(props.filters?.status || '');

const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Published & Live', value: 'published' },
    { label: 'Drafts', value: 'draft' },
];

const categoryOptions = computed(() => {
    const list = [{ label: 'All Categories', value: '' }];
    (props.categories || []).forEach(cat => {
        list.push({ label: cat, value: cat });
    });
    return list;
});

function applyFilters() {
    router.get(route('admin.blogs.index'), {
        search: searchQuery.value || undefined,
        category: categoryFilter.value || undefined,
        status: statusFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function resetFilters() {
    searchQuery.value = '';
    categoryFilter.value = '';
    statusFilter.value = '';
    applyFilters();
}

// Modal State
const isModalOpen = ref(false);
const editingPost = ref(null);
const isUploadingCover = ref(false);

const form = useForm({
    title: '',
    slug: '',
    excerpt: '',
    content: '',
    cover_image: '',
    category: 'General',
    author_name: 'Editorial Team',
    read_time_minutes: 3,
    is_published: true,
    meta_title: '',
    meta_description: '',
});

function openCreateModal() {
    editingPost.value = null;
    form.reset();
    form.clearErrors();
    form.category = 'General';
    form.author_name = 'Editorial Team';
    form.read_time_minutes = 3;
    form.is_published = true;
    isModalOpen.value = true;
}

function openEditModal(post) {
    editingPost.value = post;
    form.clearErrors();
    form.title = post.title;
    form.slug = post.slug;
    form.excerpt = post.excerpt || '';
    form.content = post.content || '';
    form.cover_image = post.cover_image || '';
    form.category = post.category || 'General';
    form.author_name = post.author_name || 'Editorial Team';
    form.read_time_minutes = post.read_time_minutes || 3;
    form.is_published = !!post.is_published;
    form.meta_title = post.meta_title || '';
    form.meta_description = post.meta_description || '';
    isModalOpen.value = true;
}

function onTitleInput() {
    if (!editingPost.value && form.title) {
        form.slug = form.title
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
}

function handleCoverUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    isUploadingCover.value = true;
    const data = new FormData();
    data.append('image', file);

    axios.post(route('admin.blogs.upload-cover'), data, {
        headers: { 'Content-Type': 'multipart/form-data' }
    })
    .then(res => {
        form.cover_image = res.data.url;
    })
    .catch(err => {
        alert(err.response?.data?.message || 'Failed to upload cover image. Max size: 4MB.');
    })
    .finally(() => {
        isUploadingCover.value = false;
        e.target.value = '';
    });
}

function submit() {
    if (editingPost.value) {
        form.put(route('admin.blogs.update', editingPost.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post(route('admin.blogs.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
}

function toggleStatus(post) {
    router.patch(route('admin.blogs.toggle-status', post.id), {}, {
        preserveScroll: true,
    });
}

function deletePost(post) {
    if (confirm(`Are you sure you want to delete article "${post.title}"?`)) {
        router.delete(route('admin.blogs.destroy', post.id), {
            preserveScroll: true,
        });
    }
}

function formatDate(dt) {
    if (!dt) return 'Draft';
    return new Date(dt).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Blogs & Articles - Admin" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                        Blogs & Editorial Stories
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Publish lifestyle articles, buying guides, and trend updates for your Next.js storefront.
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
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Articles</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ metrics.total_posts }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">All editorial entries</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                            <Newspaper class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-500">Live & Published</p>
                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ metrics.published_posts }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Visible on storefront</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                            <CheckCircle2 class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-amber-500">Drafts In Progress</p>
                            <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ metrics.draft_posts }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Unpublished manuscripts</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold">
                            <FileText class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-purple-500">Categories</p>
                            <p class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ metrics.categories_count }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Unique topic themes</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                            <Tag class="w-6 h-6" />
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
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search articles, author, topic..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="w-44">
                        <CustomSelect
                            v-model="categoryFilter"
                            @update:modelValue="applyFilters"
                            :options="categoryOptions"
                            placeholder="Category"
                        />
                    </div>

                    <div class="w-44">
                        <CustomSelect
                            v-model="statusFilter"
                            @update:modelValue="applyFilters"
                            :options="statusOptions"
                            placeholder="Status"
                        />
                    </div>

                    <button
                        v-if="searchQuery || categoryFilter || statusFilter"
                        @click="resetFilters"
                        class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer"
                    >
                        <X class="w-3.5 h-3.5" /> Reset
                    </button>
                </div>

                <div class="w-full sm:w-auto flex items-center justify-end shrink-0">
                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all cursor-pointer active:scale-95 shrink-0"
                    >
                        <Plus class="w-4 h-4" /> New Article
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="postList.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center">
                <Newspaper class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No blog articles found</h3>
                <p class="text-xs text-slate-400 mt-1">Start writing compelling content to boost audience engagement and organic SEO traffic.</p>
                <button
                    @click="openCreateModal"
                    class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                >
                    <Plus class="w-4 h-4" /> Create First Article
                </button>
            </div>

            <!-- Article Cards Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <div
                    v-for="post in postList"
                    :key="post.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs flex flex-col hover:border-slate-300 dark:hover:border-slate-700 transition-all group"
                >
                    <!-- Cover Image Container -->
                    <div class="relative h-48 bg-slate-100 dark:bg-slate-800 overflow-hidden shrink-0">
                        <img
                            :src="post.cover_image || 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800&auto=format&fit=crop&q=80'"
                            :alt="post.title"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent"></div>

                        <!-- Category Pill -->
                        <div class="absolute top-3.5 left-3.5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-white/90 dark:bg-slate-900/90 text-indigo-600 dark:text-indigo-400 backdrop-blur-xs shadow-xs">
                                {{ post.category || 'General' }}
                            </span>
                        </div>

                        <!-- Read time pill -->
                        <div class="absolute top-3.5 right-3.5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-950/70 text-white backdrop-blur-xs flex items-center gap-1">
                                <Clock class="w-3 h-3 text-amber-400" /> {{ post.read_time_minutes }} min read
                            </span>
                        </div>

                        <!-- Status badge on bottom right -->
                        <div class="absolute bottom-3 left-3.5">
                            <span
                                :class="[
                                    post.is_published
                                        ? 'bg-emerald-500 text-white'
                                        : 'bg-slate-600 text-slate-100',
                                    'px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider'
                                ]"
                            >
                                {{ post.is_published ? 'Published' : 'Draft' }}
                            </span>
                        </div>
                    </div>

                    <!-- Article Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white line-clamp-2 leading-snug group-hover:text-indigo-600 transition-colors">
                                {{ post.title }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                {{ post.excerpt || 'No excerpt provided. Click edit to add a summary for search engines and social cards.' }}
                            </p>
                        </div>

                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-[10px]">
                                    {{ (post.author_name || 'E').charAt(0).toUpperCase() }}
                                </div>
                                <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                                    {{ post.author_name || 'Editorial Team' }}
                                </span>
                            </div>

                            <span class="text-[10px] text-slate-400 font-mono">
                                {{ formatDate(post.published_at || post.created_at) }}
                            </span>
                        </div>

                        <!-- Actions footer -->
                        <div class="pt-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button
                                    @click="toggleStatus(post)"
                                    :title="post.is_published ? 'Unpublish to draft' : 'Publish to live storefront'"
                                    :class="[
                                        post.is_published ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700',
                                        'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            post.is_published ? 'translate-x-4' : 'translate-x-0',
                                            'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200'
                                        ]"
                                    />
                                </button>
                                <span class="text-[11px] font-bold text-slate-500">
                                    {{ post.is_published ? 'Live' : 'Draft' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-1">
                                <button
                                    @click="openEditModal(post)"
                                    class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors cursor-pointer"
                                    title="Edit Article"
                                >
                                    <Edit3 class="w-4 h-4" />
                                </button>
                                <button
                                    @click="deletePost(post)"
                                    class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                    title="Delete Article"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= CREATE / EDIT ARTICLE MODAL ================= -->
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
                    <!-- Modal Header -->
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                <Newspaper class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    {{ editingPost ? 'Edit Blog Article' : 'Write New Blog Article' }}
                                </h3>
                                <p class="text-xs text-slate-400">Craft engaging editorial stories with SEO and cover media for Next.js.</p>
                            </div>
                        </div>
                        <button @click="isModalOpen = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 cursor-pointer">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Form -->
                    <form @submit.prevent="submit" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                        <!-- Title & Slug -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Article Title *</label>
                                <input
                                    v-model="form.title"
                                    @input="onTitleInput"
                                    type="text"
                                    required
                                    placeholder="e.g. 10 Essential Summer Wardrobe Essentials"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 font-bold focus:ring-2 focus:ring-indigo-500"
                                />
                                <span v-if="form.errors.title" class="text-[10px] text-rose-500 mt-1 block">{{ form.errors.title }}</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Slug URL</label>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    placeholder="auto-generated-slug"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 font-mono focus:ring-2 focus:ring-indigo-500"
                                />
                                <span v-if="form.errors.slug" class="text-[10px] text-rose-500 mt-1 block">{{ form.errors.slug }}</span>
                            </div>
                        </div>

                        <!-- Category, Author & Read Time -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                                <input
                                    v-model="form.category"
                                    type="text"
                                    placeholder="e.g. Trends, Guides, News"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Author Name</label>
                                <input
                                    v-model="form.author_name"
                                    type="text"
                                    placeholder="e.g. Editorial Team"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Read Time (Mins)</label>
                                <input
                                    v-model.number="form.read_time_minutes"
                                    type="number"
                                    min="1"
                                    max="120"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 font-mono focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- Cover Image Upload -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Cover Banner Image</label>
                            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                                <div v-if="form.cover_image" class="w-24 h-16 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden relative group shrink-0">
                                    <img :src="form.cover_image" class="w-full h-full object-cover" />
                                    <button
                                        type="button"
                                        @click="form.cover_image = ''"
                                        class="absolute inset-0 bg-black/60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>
                                <div class="flex-1 w-full flex items-center gap-2">
                                    <input
                                        v-model="form.cover_image"
                                        type="text"
                                        placeholder="Paste image URL or upload from disk"
                                        class="flex-1 px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 font-mono"
                                    />
                                    <label class="px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-2xl text-xs font-bold cursor-pointer transition-colors shrink-0 flex items-center gap-1.5">
                                        <UploadCloud class="w-4 h-4" />
                                        <span>{{ isUploadingCover ? 'Uploading...' : 'Upload' }}</span>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            :disabled="isUploadingCover"
                                            @change="handleCoverUpload"
                                        />
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Short Excerpt -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Summary / Excerpt</label>
                            <textarea
                                v-model="form.excerpt"
                                rows="2"
                                placeholder="A concise 1-2 sentence teaser shown on blog listing cards and social share previews..."
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            ></textarea>
                        </div>

                        <!-- Full Content Body (Rich Text Editor) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Article Body Content *</label>
                                <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">Rich Text (Tiptap)</span>
                            </div>
                            <RichTextEditor
                                v-model="form.content"
                                placeholder="Write your article content here... Format headings, bullet lists, bold text, quotes, and links."
                                min-height="240px"
                            />
                            <span v-if="form.errors.content" class="text-[10px] text-rose-500 mt-1 block">{{ form.errors.content }}</span>
                        </div>

                        <!-- SEO Meta Box -->
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-3">
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <Sparkles class="w-3.5 h-3.5 text-indigo-500" /> Search Engine Optimization (SEO)
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Meta Title</label>
                                    <input
                                        v-model="form.meta_title"
                                        type="text"
                                        placeholder="Google search title..."
                                        class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Meta Description</label>
                                    <input
                                        v-model="form.meta_description"
                                        type="text"
                                        placeholder="Google search snippet description..."
                                        class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Publish Status Toggle -->
                        <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white block">Published Status</span>
                                <span class="text-[11px] text-slate-400">When enabled, this article is live and accessible on your Next.js storefront</span>
                            </div>
                            <button
                                type="button"
                                @click="form.is_published = !form.is_published"
                                :class="[
                                    form.is_published ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700',
                                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors'
                                ]"
                            >
                                <span
                                    :class="[
                                        form.is_published ? 'translate-x-5' : 'translate-x-0',
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
                                :disabled="form.processing"
                                class="px-5 py-2.5 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all flex items-center gap-2 cursor-pointer active:scale-95"
                            >
                                <Check class="w-4 h-4" />
                                {{ editingPost ? 'Update Article' : 'Publish Article' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
