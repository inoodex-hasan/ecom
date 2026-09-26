<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ShieldCheck,
    User,
    Mail,
    Lock,
    Edit3,
    ArrowLeft,
    CheckCircle2,
    Sparkles,
    Loader2,
    Save,
    Calendar,
    ChevronDown,
    Check
} from 'lucide-vue-next';
import CustomSelect from '@/Components/CustomSelect.vue';

const props = defineProps({
    staff: Object,
    roles: Array,
    groupedPermissions: Object,
});

const isRoleDropdownOpen = ref(false);

const selectedRoleObject = computed(() => {
    return props.roles?.find(r => r.name === form.role) || { name: form.role, permissions: [] };
});

const form = useForm({
    name: props.staff.name || '',
    email: props.staff.email || '',
    password: '',
    role: props.staff.roles?.[0]?.name || props.roles?.[0]?.name || 'Store Manager',
    permissions: props.staff.permissions?.map(p => p.name) || [],
});

function selectRole(roleName) {
    form.role = roleName;
    isRoleDropdownOpen.value = false;
}

function togglePermission(permName) {
    const idx = form.permissions.indexOf(permName);
    if (idx > -1) {
        form.permissions.splice(idx, 1);
    } else {
        form.permissions.push(permName);
    }
}

function selectModulePermissions(modulePerms) {
    const allSelected = modulePerms.every(p => form.permissions.includes(p.name));
    if (allSelected) {
        form.permissions = form.permissions.filter(p => !modulePerms.some(mp => mp.name === p));
    } else {
        modulePerms.forEach(p => {
            if (!form.permissions.includes(p.name)) {
                form.permissions.push(p.name);
            }
        });
    }
}

function submit() {
    form.put(route('admin.staff.update', props.staff.id));
}

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};
</script>

<template>
    <AdminLayout>
        <Head :title="`Edit ${staff.name} - Admin`" />

        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.staff.index')"
                    class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Edit Team Member: {{ staff.name }}</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Update administrative details, change role, and configure custom permissions</p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="w-full space-y-6">
            <!-- Staff Summary Banner Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs flex flex-col sm:flex-row items-center gap-5">
                <img
                    :src="staff.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(staff.name)}&background=6366f1&color=fff&size=100`"
                    :alt="staff.name"
                    class="w-16 h-16 rounded-2xl object-cover ring-2 ring-indigo-500/20 shadow-xs shrink-0"
                />
                <div class="flex-1 text-center sm:text-left space-y-1">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ staff.name }}</h2>
                        <span class="inline-flex items-center gap-1 self-center sm:self-auto px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                            {{ form.role }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ staff.email }} • Member since {{ formatDate(staff.created_at) }}
                    </p>
                </div>
            </div>

            <!-- Basic Details & Role Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <Edit3 class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Account Information</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Manage user identity, credentials, and role assignments</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Full Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Full Name
                        </label>
                        <div class="relative">
                            <User class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all"
                            />
                        </div>
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500 font-medium">{{ form.errors.name }}</p>
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Email Address
                        </label>
                        <div class="relative">
                            <Mail class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-xs text-rose-500 font-medium">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password (Optional on edit) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Reset Password (Leave blank to keep current)
                        </label>
                        <div class="relative">
                            <Lock class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                            <input
                                v-model="form.password"
                                type="password"
                                placeholder="Enter new password if changing"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 transition-all"
                            />
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-rose-500 font-medium">{{ form.errors.password }}</p>
                    </div>

                    <!-- Primary Role Selector -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Assigned Security Role
                        </label>
                        <CustomSelect
                            v-model="form.role"
                            :options="roles.map(r => ({
                                value: r.name,
                                label: r.name,
                                sublabel: `${r.permissions?.length || 0} permissions`,
                                icon: ShieldCheck
                            }))"
                            :icon="ShieldCheck"
                            placeholder="Select Security Role"
                        />
                    </div>
                </div>
            </div>

            <!-- Custom Direct Permissions Override Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <ShieldCheck class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Custom Direct Permissions</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Grant specific permissions to this user independent of their role</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="(perms, module) in groupedPermissions"
                        :key="module"
                        class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 capitalize">
                                {{ module }}
                            </span>
                            <button
                                type="button"
                                @click="selectModulePermissions(perms)"
                                class="text-[11px] font-semibold text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                            >
                                {{ perms.every(p => form.permissions.includes(p.name)) ? 'Deselect All' : 'Select All' }}
                            </button>
                        </div>

                        <div class="space-y-1.5">
                            <label
                                v-for="p in perms"
                                :key="p.id"
                                class="flex items-center gap-2 p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 hover:border-indigo-500 cursor-pointer transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    :checked="form.permissions.includes(p.name)"
                                    @change="togglePermission(p.name)"
                                    class="rounded text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="font-mono text-[11px] text-slate-800 dark:text-slate-200 truncate">{{ p.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="flex items-center justify-between pt-2">
                <Link
                    :href="route('admin.staff.index')"
                    class="px-5 py-2.5 rounded-2xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/25 transition-all cursor-pointer disabled:opacity-60"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <Save v-else class="w-4 h-4" />
                    Save Staff Changes
                </button>
            </div>
        </form>
    </AdminLayout>
</template>
