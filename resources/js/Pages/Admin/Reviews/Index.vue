<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import {
    Star,
    Search,
    Filter,
    CheckCircle2,
    XCircle,
    AlertCircle,
    ShieldCheck,
    MessageSquare,
    CornerDownRight,
    Send,
    Trash2,
    Check,
    X,
    ExternalLink,
    Image as ImageIcon,
    Clock,
    Sparkles,
    Flag,
    ThumbsUp
} from 'lucide-vue-next';

const props = defineProps({
    reviews: [Array, Object],
    metrics: Object,
    filters: Object,
});

const reviewList = computed(() => {
    if (Array.isArray(props.reviews)) return props.reviews;
    return props.reviews?.data || [];
});

// Filter State
const searchQuery = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || '');
const selectedRating = ref(props.filters?.rating || '');

const ratingOptions = [
    { label: 'All Ratings', value: '' },
    { label: '5 Stars ★★★★★', value: '5' },
    { label: '4 Stars ★★★★☆', value: '4' },
    { label: '3 Stars ★★★☆☆', value: '3' },
    { label: '2 Stars ★★☆☆☆', value: '2' },
    { label: '1 Star ★☆☆☆☆', value: '1' },
];

function applyFilters() {
    router.get(
        route('admin.reviews.index'),
        {
            search: searchQuery.value || undefined,
            status: selectedStatus.value || undefined,
            rating: selectedRating.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

function resetFilters() {
    searchQuery.value = '';
    selectedStatus.value = '';
    selectedRating.value = '';
    applyFilters();
}

function filterByRating(stars) {
    selectedRating.value = selectedRating.value == stars ? '' : stars;
    applyFilters();
}

// Moderation Actions
function updateStatus(review, status) {
    router.patch(route('admin.reviews.update-status', review.id), { status }, { preserveScroll: true });
}

function deleteReview(review) {
    if (confirm(`Are you sure you want to delete this review from '${review.reviewer_name}'?`)) {
        router.delete(route('admin.reviews.destroy', review.id), { preserveScroll: true });
    }
}

// Merchant Reply State
const replyingReviewId = ref(null);
const replyForm = useForm({
    merchant_reply: '',
});

function openReplyBox(review) {
    replyingReviewId.value = review.id;
    replyForm.merchant_reply = review.merchant_reply || '';
}

function cancelReply() {
    replyingReviewId.value = null;
    replyForm.reset();
}

function submitReply(review) {
    replyForm.post(route('admin.reviews.reply', review.id), {
        preserveScroll: true,
        onSuccess: () => {
            replyingReviewId.value = null;
            replyForm.reset();
        },
    });
}

// Lightbox Preview State
const activeLightboxPhoto = ref(null);

function openPhotoLightbox(photoUrl) {
    activeLightboxPhoto.value = photoUrl;
}

function closePhotoLightbox() {
    activeLightboxPhoto.value = null;
}

function getStatusBadge(status) {
    switch (status) {
        case 'approved':
            return { label: 'Approved', class: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' };
        case 'pending':
            return { label: 'Pending Review', class: 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800' };
        case 'rejected':
            return { label: 'Rejected', class: 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800' };
        case 'spam':
            return { label: 'Flagged Spam', class: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700' };
        default:
            return { label: status, class: 'bg-slate-100 text-slate-600 border-slate-200' };
    }
}

const totalBreakdownCount = computed(() => {
    if (!props.metrics?.star_breakdown) return 0;
    return Object.values(props.metrics.star_breakdown).reduce((acc, val) => acc + val, 0);
});

function getStarPercent(count) {
    if (!totalBreakdownCount.value || totalBreakdownCount.value === 0) return 0;
    return Math.round((count / totalBreakdownCount.value) * 100);
}
</script>

<template>
    <AdminLayout>
        <Head title="Customer Reviews & Moderation Desk - Admin" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                        Customer Reviews & Ratings
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Moderate customer feedback, inspect photo uploads, verify orders, and post official store responses.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6 pb-12">
            <!-- KPI Metric Cards + Star Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <!-- Summary Metrics (3 columns) -->
                <div class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Total Reviews -->
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Reviews</span>
                            <div class="w-7 h-7 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <MessageSquare class="w-3.5 h-3.5" />
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white">
                                {{ metrics?.total_reviews || 0 }}
                            </div>
                            <span class="text-[10px] text-slate-400 font-medium">Customer submissions</span>
                        </div>
                    </div>

                    <!-- Pending Moderation -->
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between relative overflow-hidden">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Needs Moderation</span>
                            <div class="w-7 h-7 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                <AlertCircle class="w-3.5 h-3.5" />
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-black" :class="(metrics?.pending_count || 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'">
                                {{ metrics?.pending_count || 0 }}
                            </div>
                            <span class="text-[10px] text-slate-400 font-medium">Pending approval queue</span>
                        </div>
                    </div>

                    <!-- Average Store Rating -->
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Store Average</span>
                            <div class="w-7 h-7 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-500 flex items-center justify-center">
                                <Star class="w-3.5 h-3.5 fill-amber-500" />
                            </div>
                        </div>
                        <div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 dark:text-white">
                                    {{ Number(metrics?.average_rating || 0).toFixed(1) }}
                                </span>
                                <div class="flex items-center text-amber-400">
                                    <Star v-for="i in 5" :key="i" class="w-3 h-3" :class="i <= Math.round(metrics?.average_rating || 0) ? 'fill-amber-400 text-amber-400' : 'text-slate-300 dark:text-slate-700'" />
                                </div>
                            </div>
                            <span class="text-[10px] text-slate-400 font-medium">From {{ metrics?.approved_count || 0 }} approved reviews</span>
                        </div>
                    </div>
                </div>

                <!-- Star Rating Distribution breakdown (1 column) -->
                <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                    <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 mb-1">Rating Distribution</span>
                    <div class="space-y-1 flex-1 justify-center flex flex-col">
                        <div
                            v-for="star in [5, 4, 3, 2, 1]"
                            :key="star"
                            @click="filterByRating(star)"
                            :class="[
                                selectedRating == star
                                    ? 'bg-amber-50 dark:bg-amber-950/40 ring-1 ring-amber-400 text-amber-900 dark:text-amber-200 font-bold'
                                    : 'hover:bg-slate-50 dark:hover:bg-slate-800/40 text-slate-700 dark:text-slate-300',
                                'flex items-center gap-2 px-1.5 py-0.5 rounded-lg cursor-pointer transition-all text-[11px]'
                            ]"
                            :title="`Filter by ${star} Stars`"
                        >
                            <span class="font-bold w-5 text-slate-700 dark:text-slate-300 flex items-center gap-0.5 text-[10px]">
                                {{ star }} <Star class="w-2.5 h-2.5 fill-amber-400 text-amber-400" />
                            </span>
                            <div class="flex-1 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div
                                    class="h-full bg-amber-400 rounded-full transition-all duration-300"
                                    :style="{ width: getStarPercent(metrics?.star_breakdown?.[star] || 0) + '%' }"
                                ></div>
                            </div>
                            <span class="w-6 text-right font-mono font-medium text-slate-400 text-[10px]">
                                {{ metrics?.star_breakdown?.[star] || 0 }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
                    <!-- Status Filter Tabs with Counts -->
                    <div class="flex items-center bg-slate-100 dark:bg-slate-800/60 p-1 rounded-2xl gap-1 overflow-x-auto scrollbar-none">
                        <button
                            v-for="st in [
                                { key: '', label: 'All', count: metrics?.total_reviews || 0 },
                                { key: 'pending', label: 'Pending', count: metrics?.pending_count || 0, alert: (metrics?.pending_count || 0) > 0 },
                                { key: 'approved', label: 'Approved', count: metrics?.approved_count || 0 },
                                { key: 'rejected', label: 'Rejected', count: metrics?.rejected_count || 0 },
                                { key: 'spam', label: 'Spam', count: metrics?.spam_count || 0 },
                            ]"
                            :key="st.key"
                            @click="selectedStatus = st.key; applyFilters()"
                            :class="[
                                selectedStatus === st.key
                                    ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs font-bold'
                                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium',
                                'px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer inline-flex items-center gap-1.5 shrink-0'
                            ]"
                        >
                            <span>{{ st.label }}</span>
                            <span
                                :class="[
                                    st.alert
                                        ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200 font-bold'
                                        : 'bg-slate-200/70 text-slate-600 dark:bg-slate-750 dark:text-slate-400 font-medium',
                                    'px-1.5 py-0.2 rounded-full text-[10px] font-mono'
                                ]"
                            >
                                {{ st.count }}
                            </span>
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchQuery"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search reviewer, product or review content..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Rating Filter -->
                    <div class="w-44">
                        <CustomSelect
                            v-model="selectedRating"
                            :options="ratingOptions"
                            placeholder="Rating"
                            @change="applyFilters"
                        />
                    </div>

                    <button
                        v-if="searchQuery || selectedStatus || selectedRating"
                        @click="resetFilters"
                        class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer"
                    >
                        <X class="w-3.5 h-3.5" /> Reset
                    </button>
                </div>
            </div>

            <!-- Review Cards Feed -->
            <div v-if="reviewList.length > 0" class="space-y-3">
                <div
                    v-for="review in reviewList"
                    :key="review.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs transition-all hover:border-slate-300 dark:hover:border-slate-700 space-y-2.5"
                >
                    <!-- Top Row: Product info on Left, Status + Moderation on Right -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                        <!-- Product Preview Info -->
                        <div class="flex items-center gap-2.5 min-w-0">
                            <img
                                :src="review.product?.primary_image || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=100'"
                                :alt="review.product?.name"
                                class="w-9 h-9 rounded-xl object-cover bg-slate-100 dark:bg-slate-800 shrink-0 border border-slate-200/60 dark:border-slate-700/60"
                            />
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                    {{ review.product?.name || 'Unknown Product' }}
                                </h4>
                                <div class="flex items-center gap-1.5 text-[10px] text-slate-400">
                                    <span class="font-mono">SKU: {{ review.product?.sku || 'N/A' }}</span>
                                    <span>·</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">${{ Number(review.product?.price || 0).toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge + Actions -->
                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                            <span :class="[getStatusBadge(review.status).class, 'px-2 py-0.5 rounded-lg text-[10px] font-bold border capitalize']">
                                {{ getStatusBadge(review.status).label }}
                            </span>

                            <!-- Fast Moderation Buttons -->
                            <div class="flex items-center gap-1 bg-slate-50 dark:bg-slate-800/60 p-0.5 rounded-xl border border-slate-200/60 dark:border-slate-700/60">
                                <button
                                    v-if="review.status !== 'approved'"
                                    @click="updateStatus(review, 'approved')"
                                    class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors flex items-center gap-1 cursor-pointer"
                                    title="Approve Review"
                                >
                                    <Check class="w-3 h-3" /> Approve
                                </button>
                                <button
                                    v-if="review.status !== 'rejected'"
                                    @click="updateStatus(review, 'rejected')"
                                    class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-600 hover:bg-rose-100 transition-colors flex items-center gap-1 cursor-pointer"
                                    title="Reject Review"
                                >
                                    <X class="w-3 h-3" /> Reject
                                </button>
                                <button
                                    v-if="review.status !== 'spam'"
                                    @click="updateStatus(review, 'spam')"
                                    class="p-1 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-slate-100 transition-colors"
                                    title="Mark as Spam"
                                >
                                    <Flag class="w-3 h-3" />
                                </button>
                                <button
                                    @click="deleteReview(review)"
                                    class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                    title="Delete Review"
                                >
                                    <Trash2 class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Middle Row: Reviewer Details + Star Rating + Review Text -->
                    <div class="space-y-1.5">
                        <!-- Reviewer Info Bar with Stars inline -->
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-bold text-[10px] flex items-center justify-center shrink-0">
                                    {{ review.reviewer_name?.charAt(0).toUpperCase() || 'U' }}
                                </div>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    {{ review.reviewer_name }}
                                </span>
                                <span v-if="review.reviewer_email" class="text-[10px] text-slate-400">
                                    ({{ review.reviewer_email }})
                                </span>
                                <span
                                    v-if="review.is_verified_purchase"
                                    class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                                >
                                    <ShieldCheck class="w-2.5 h-2.5 text-emerald-600" /> Verified Buyer
                                </span>

                                <div class="flex items-center text-amber-400 ml-1">
                                    <Star
                                        v-for="i in 5"
                                        :key="i"
                                        class="w-3 h-3"
                                        :class="i <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300 dark:text-slate-700'"
                                    />
                                </div>
                                <span v-if="review.title" class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ review.title }}
                                </span>
                            </div>

                            <span class="text-[10px] text-slate-400 flex items-center gap-1">
                                <Clock class="w-3 h-3" />
                                {{ new Date(review.created_at).toLocaleDateString() }}
                            </span>
                        </div>

                        <!-- Comment Content -->
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line">
                            {{ review.comment || 'No comment provided with this rating.' }}
                        </p>

                        <!-- Attached Review Photos Gallery -->
                        <div v-if="review.photos && review.photos.length > 0" class="pt-1 flex flex-wrap items-center gap-1.5">
                            <div
                                v-for="(photo, idx) in review.photos"
                                :key="idx"
                                @click="openPhotoLightbox(photo)"
                                class="relative w-12 h-12 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 cursor-pointer hover:opacity-90 group transition-all"
                            >
                                <img :src="photo" alt="Review Photo" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-slate-950/20 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                    <ImageIcon class="w-3.5 h-3.5 text-white" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Section: Official Merchant Response -->
                    <div class="pt-1">
                        <!-- Existing Merchant Reply -->
                        <div
                            v-if="review.merchant_reply && replyingReviewId !== review.id"
                            class="p-2.5 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 space-y-1"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5 text-[11px] font-bold text-indigo-700 dark:text-indigo-300">
                                    <CornerDownRight class="w-3 h-3" />
                                    <span>Store Merchant Response</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="review.merchant_replied_at" class="text-[10px] text-slate-400">
                                        {{ new Date(review.merchant_replied_at).toLocaleDateString() }}
                                    </span>
                                    <button
                                        @click="openReplyBox(review)"
                                        class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer"
                                    >
                                        Edit
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line pl-4">
                                {{ review.merchant_reply }}
                            </p>
                        </div>

                        <!-- Inline Reply Box -->
                        <div v-else-if="replyingReviewId === review.id" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <MessageSquare class="w-3.5 h-3.5 text-indigo-600" />
                                    Write Official Merchant Reply
                                </span>
                                <button @click="cancelReply" class="text-xs text-slate-400 hover:text-slate-600 cursor-pointer">
                                    Cancel
                                </button>
                            </div>
                            <textarea
                                v-model="replyForm.merchant_reply"
                                rows="2"
                                required
                                placeholder="Write a professional, helpful response to this customer..."
                                class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                            ></textarea>
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    @click="cancelReply"
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg text-xs text-slate-500 hover:bg-slate-200/60 cursor-pointer"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="submitReply(review)"
                                    :disabled="replyForm.processing || !replyForm.merchant_reply"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                                >
                                    <Send class="w-3 h-3" /> Post Reply
                                </button>
                            </div>
                        </div>

                        <!-- Prompt to Reply if none exists -->
                        <button
                            v-else
                            @click="openReplyBox(review)"
                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 hover:underline cursor-pointer"
                        >
                            <MessageSquare class="w-3 h-3" />
                            <span>Reply to review</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-12 text-center shadow-xs"
            >
                <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-500 mx-auto flex items-center justify-center mb-4">
                    <Star class="w-7 h-7 fill-amber-500" />
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">No Reviews Found</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1 mb-6">
                    {{ searchQuery || selectedStatus || selectedRating ? 'No customer reviews matched your search filter criteria. Try resetting.' : 'Customer product ratings and submitted reviews will appear here for moderation.' }}
                </p>
                <button
                    v-if="searchQuery || selectedStatus || selectedRating"
                    @click="resetFilters"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold bg-slate-900 dark:bg-slate-700 text-white transition-all cursor-pointer shadow-xs"
                >
                    Reset Filters
                </button>
            </div>
        </div>

        <!-- Lightbox Photo Preview Modal -->
        <div
            v-if="activeLightboxPhoto"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-sm"
            @click.self="closePhotoLightbox"
        >
            <div class="relative max-w-3xl max-h-[90vh] overflow-hidden rounded-3xl bg-slate-900 border border-slate-800 p-2 shadow-2xl">
                <button
                    @click="closePhotoLightbox"
                    class="absolute top-4 right-4 p-2 rounded-full bg-slate-950/80 text-white hover:bg-slate-950 transition-colors z-10"
                >
                    <X class="w-5 h-5" />
                </button>
                <img
                    :src="activeLightboxPhoto"
                    alt="Review Photo Full"
                    class="max-w-full max-h-[85vh] rounded-2xl object-contain mx-auto"
                />
            </div>
        </div>
    </AdminLayout>
</template>
