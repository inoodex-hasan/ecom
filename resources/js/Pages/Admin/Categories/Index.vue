<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Plus,
    FolderTree,
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
    Search
} from 'lucide-vue-next';
import CustomSelect from '@/Components/CustomSelect.vue';

const categoryIconOptions = [
    { value: 'Tv', label: 'Tv / Displays', icon: Tv },
    { value: 'Smartphone', label: 'Smartphone & Tablets', icon: Smartphone },
    { value: 'Laptop', label: 'Laptop & Computers', icon: Laptop },
    { value: 'Headphones', label: 'Headphones & Audio', icon: Headphones },
    { value: 'Watch', label: 'Watch & Wearables', icon: Watch },
    { value: 'Cable', label: 'Accessories', icon: Cable },
    { value: 'FolderTree', label: 'Generic Category', icon: FolderTree },
];

const props = defineProps({
    categories: Array,
});

const searchQuery = ref('');
const isModalOpen = ref(false);
const editingCategory = ref(null);

const filteredCategories = computed(() => {
    if (!searchQuery.value) return props.categories || [];
    const query = searchQuery.value.toLowerCase();
    return (props.categories || []).filter(cat =>
        cat.name.toLowerCase().includes(query) ||
        (cat.description && cat.description.toLowerCase().includes(query))
    );
});

const form = useForm({
    name: '',
    description: '',
    icon: 'FolderTree',
    is_active: true,
    sort_order: 0,
});

function openCreateModal() {
    editingCategory.value = null;
    form.reset();
    form.is_active = true;
    isModalOpen.value = true;
}

function openEditModal(cat) {
    editingCategory.value = cat;
    form.name = cat.name;
    form.description = cat.description;
    form.icon = cat.icon || 'FolderTree';
    form.is_active = !!cat.is_active;
    form.sort_order = cat.sort_order;
    isModalOpen.value = true;
}

function submit() {
    if (editingCategory.value) {
        form.put(route('admin.categories.update', editingCategory.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post(route('admin.categories.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
}

function deleteCategory(cat) {
    if (confirm(`Are you sure you want to delete category "${cat.name}"?`)) {
        router.delete(route('admin.categories.destroy', cat.id));
    }
}

const iconMap = {
    Tv,
    Smartphone,
    Laptop,
    Headphones,
    Watch,
    Cable,
    FolderTree,
    ShoppingBag,
};
</script>

<template>
    <AdminLayout>
        <Head title="Categories - Admin" />

        <template #header>
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Product Categories</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Organize your store catalog and product taxonomy</p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Integrated Filter Action Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="relative w-full sm:w-80">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search category name or details..."
                        class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                    />
                </div>

                <div class="w-full sm:w-auto flex justify-end">
                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all cursor-pointer"
                    >
                        <Plus class="w-4 h-4" /> Add Category
                    </button>
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="cat in filteredCategories"
                    :key="cat.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs flex flex-col justify-between group hover:border-indigo-500/40 transition-colors"
                >
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <component :is="iconMap[cat.icon] || FolderTree" class="w-5 h-5" />
                            </div>
                            <span
                                :class="[
                                    cat.is_active
                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-500',
                                    'px-2.5 py-0.5 rounded-full text-[11px] font-bold border'
                                ]"
                            >
                                {{ cat.is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </div>

                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ cat.name }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">{{ cat.description || 'No description provided.' }}</p>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                            {{ cat.products_count }} Products
                        </span>

                        <div class="flex items-center gap-1">
                            <button
                                @click="openEditModal(cat)"
                                class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors cursor-pointer"
                                title="Edit Category"
                            >
                                <Edit3 class="w-4 h-4" />
                            </button>
                            <button
                                @click="deleteCategory(cat)"
                                class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                title="Delete Category"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Category Modal -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ editingCategory ? 'Edit Category' : 'Create Category' }}
                    </h3>
                    <button @click="isModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Category Name *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Icon</label>
                        <CustomSelect
                            v-model="form.icon"
                            :options="categoryIconOptions"
                            placeholder="Select Icon"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            id="cat_active"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label for="cat_active" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Active (Visible in store)</label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 cursor-pointer"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
