<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ShieldCheck,
    Users,
    UserPlus,
    Search,
    Edit3,
    Trash2,
    CheckCircle2,
    X,
    Lock,
    Mail,
    User,
    KeyRound,
    Sparkles,
    ShieldAlert,
    ChevronRight,
    Loader2,
    Layers,
    Save,
    RotateCcw,
    Check,
    Package,
    ShoppingCart,
    FolderTree,
    Settings,
    HelpCircle
} from 'lucide-vue-next';
import CustomSelect from '@/Components/CustomSelect.vue';

const props = defineProps({
    staff: Object,
    roles: Array,
    groupedPermissions: Object,
    filters: Object,
});

const activeTab = ref('staff'); // 'staff' | 'matrix'
const search = ref(props.filters?.search || '');
const selectedRoleFilter = ref(props.filters?.role || '');

// Matrix state initialization
const matrixState = ref(
    props.roles.map(role => ({
        role_id: role.id,
        role_name: role.name,
        permissions: role.permissions?.map(p => p.name) || [],
    }))
);

const isMatrixDirty = ref(false);
const matrixForm = useForm({
    matrix: [],
});

function applyFilters() {
    router.get(
        route('admin.staff.index'),
        {
            search: search.value || undefined,
            role: selectedRoleFilter.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

let searchTimeout;
function onSearchChange() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
}

function deleteStaff(user) {
    if (confirm(`Are you sure you want to remove team member "${user.name}"?`)) {
        router.delete(route('admin.staff.destroy', user.id));
    }
}

function isPermissionActive(roleId, permName) {
    const roleEntry = matrixState.value.find(r => r.role_id === roleId);
    if (!roleEntry) return false;
    if (roleEntry.role_name === 'Super Admin') return true;
    return roleEntry.permissions.includes(permName);
}

function toggleMatrixPermission(roleId, permName) {
    const roleEntry = matrixState.value.find(r => r.role_id === roleId);
    if (!roleEntry || roleEntry.role_name === 'Super Admin') return;

    const idx = roleEntry.permissions.indexOf(permName);
    if (idx > -1) {
        roleEntry.permissions.splice(idx, 1);
    } else {
        roleEntry.permissions.push(permName);
    }
    isMatrixDirty.value = true;
}

function toggleAllForRole(roleId) {
    const roleEntry = matrixState.value.find(r => r.role_id === roleId);
    if (!roleEntry || roleEntry.role_name === 'Super Admin') return;

    const allPermNames = Object.values(props.groupedPermissions).flatMap(group => group.map(p => p.name));
    const allSelected = allPermNames.every(p => roleEntry.permissions.includes(p));

    if (allSelected) {
        roleEntry.permissions = [];
    } else {
        roleEntry.permissions = [...allPermNames];
    }
    isMatrixDirty.value = true;
}

function resetMatrix() {
    matrixState.value = props.roles.map(role => ({
        role_id: role.id,
        role_name: role.name,
        permissions: role.permissions?.map(p => p.name) || [],
    }));
    isMatrixDirty.value = false;
}

function saveMatrix() {
    matrixForm.matrix = matrixState.value.map(r => ({
        role_id: r.role_id,
        permissions: r.permissions,
    }));

    matrixForm.post(route('admin.roles.bulk-update'), {
        preserveScroll: true,
        onSuccess: () => {
            isMatrixDirty.value = false;
        },
    });
}

function getRoleBadge(roleName) {
    switch (roleName) {
        case 'Super Admin':
            return {
                bg: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
                dot: 'bg-purple-500',
                avatarBg: 'from-purple-600 to-indigo-600',
            };
        case 'Store Manager':
            return {
                bg: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',
                dot: 'bg-indigo-500',
                avatarBg: 'from-indigo-600 to-blue-600',
            };
        case 'Fulfillment Staff':
            return {
                bg: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
                dot: 'bg-blue-500',
                avatarBg: 'from-blue-600 to-cyan-600',
            };
        case 'Catalog Specialist':
            return {
                bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                dot: 'bg-emerald-500',
                avatarBg: 'from-emerald-600 to-teal-600',
            };
        default:
            return {
                bg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                dot: 'bg-amber-500',
                avatarBg: 'from-amber-600 to-orange-600',
            };
    }
}

function getModuleIcon(module) {
    switch (module) {
        case 'products':
            return Package;
        case 'orders':
            return ShoppingCart;
        case 'categories':
            return FolderTree;
        case 'customers':
            return Users;
        case 'settings':
            return Settings;
        default:
            return ShieldCheck;
    }
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
        <Head title="Staff & RBAC Roles - Admin" />

        <template #header>
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Staff & Role Access (RBAC)</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Manage administrator accounts, assign security roles, and customize granular permissions</p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Navigation Tabs -->
            <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
                <button
                    @click="activeTab = 'staff'"
                    :class="[
                        activeTab === 'staff'
                            ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/20'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800',
                        'px-4 py-2 rounded-2xl text-xs flex items-center gap-2 transition-all cursor-pointer'
                    ]"
                >
                    <Users class="w-4 h-4" />
                    <span>Team Members</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-bold bg-white/20 dark:bg-slate-800 text-current">
                        {{ staff.total || staff.data?.length || 0 }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'matrix'"
                    :class="[
                        activeTab === 'matrix'
                            ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/20'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800',
                        'px-4 py-2 rounded-2xl text-xs flex items-center gap-2 transition-all cursor-pointer'
                    ]"
                >
                    <ShieldCheck class="w-4 h-4" />
                    <span>Roles & Permissions</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-bold bg-white/20 dark:bg-slate-800 text-current">
                        {{ roles.length }} Roles
                    </span>
                </button>
            </div>

            <!-- TAB 1: TEAM MEMBERS LIST -->
            <div v-if="activeTab === 'staff'" class="space-y-6">
                <!-- Integrated Filter Action Bar -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                        <!-- Search Input -->
                        <div class="relative w-full sm:w-80">
                            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                            <input
                                v-model="search"
                                type="text"
                                @input="onSearchChange"
                                placeholder="Search by name or email..."
                                class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- Role Filter -->
                        <div class="w-full sm:w-52">
                            <CustomSelect
                                v-model="selectedRoleFilter"
                                @change="applyFilters"
                                :options="[
                                    { value: '', label: 'All Assigned Roles', icon: ShieldCheck },
                                    ...roles.map(r => ({ value: r.name, label: r.name, sublabel: `${r.permissions?.length || 0} perms`, icon: ShieldCheck }))
                                ]"
                                placeholder="All Assigned Roles"
                                :icon="ShieldCheck"
                                compact
                            />
                        </div>
                    </div>

                    <!-- Add Staff Action Button -->
                    <div class="w-full sm:w-auto flex justify-end">
                        <Link
                            :href="route('admin.staff.create')"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all cursor-pointer"
                        >
                            <UserPlus class="w-4 h-4" /> Add Team Member
                        </Link>
                    </div>
                </div>

                <!-- Staff Table -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-xs uppercase text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-6 py-4">User</th>
                                    <th class="px-6 py-4">Email</th>
                                    <th class="px-6 py-4">Assigned Role</th>
                                    <th class="px-6 py-4">Direct Permissions</th>
                                    <th class="px-6 py-4">Added Date</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                <tr
                                    v-for="user in staff.data"
                                    :key="user.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors"
                                >
                                    <!-- User Avatar & Name -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img
                                                :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=6366f1&color=fff`"
                                                alt="Avatar"
                                                class="w-10 h-10 rounded-2xl object-cover ring-2 ring-indigo-500/20"
                                            />
                                            <div>
                                                <p class="font-bold text-slate-900 dark:text-white text-xs">{{ user.name }}</p>
                                                <p class="text-[11px] text-slate-400">ID #{{ user.id }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Email -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                                        {{ user.email }}
                                    </td>

                                    <!-- Role Badge -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            v-for="role in user.roles"
                                            :key="role.id"
                                            :class="[
                                                getRoleBadge(role.name).bg,
                                                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border'
                                            ]"
                                        >
                                            <Sparkles class="w-3 h-3" />
                                            {{ role.name }}
                                        </span>
                                        <span v-if="!user.roles?.length" class="text-xs text-slate-400">No Role</span>
                                    </td>

                                    <!-- Permissions Count -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        <span
                                            v-if="user.roles?.some(r => r.name === 'Super Admin')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 font-semibold text-[11px]"
                                        >
                                            <ShieldCheck class="w-3.5 h-3.5" /> Full Access (All)
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono text-[11px]"
                                        >
                                            {{ (user.permissions?.length || 0) }} Custom Direct
                                        </span>
                                    </td>

                                    <!-- Date -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">
                                        {{ formatDate(user.created_at) }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link
                                                :href="route('admin.staff.edit', user.id)"
                                                class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors"
                                                title="Edit Team Member Details"
                                            >
                                                <Edit3 class="w-4 h-4" />
                                            </Link>
                                            <button
                                                @click="deleteStaff(user)"
                                                class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                                title="Remove Team Member"
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

            <!-- TAB 2: RICH ROLES & PERMISSIONS MATRIX GRID -->
            <div v-if="activeTab === 'matrix'" class="space-y-6">
                <!-- Matrix Action / Status Bar -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <Layers class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Role Capability Matrix</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Click any toggle in the grid to grant or revoke capabilities across roles</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                        <button
                            v-if="isMatrixDirty"
                            type="button"
                            @click="resetMatrix"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer"
                        >
                            <RotateCcw class="w-3.5 h-3.5" /> Reset
                        </button>
                        <button
                            type="button"
                            :disabled="matrixForm.processing || !isMatrixDirty"
                            @click="saveMatrix"
                            :class="[
                                isMatrixDirty
                                    ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 ring-2 ring-indigo-500/20'
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-400 cursor-not-allowed',
                                'inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-bold transition-all cursor-pointer'
                            ]"
                        >
                            <Loader2 v-if="matrixForm.processing" class="w-4 h-4 animate-spin" />
                            <Save v-else class="w-4 h-4" />
                            <span>{{ isMatrixDirty ? 'Save Matrix Changes' : 'Matrix Synchronized' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Matrix Grid Container -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <!-- Matrix Column Headers (Roles) -->
                            <thead>
                                <tr class="bg-slate-50/90 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800">
                                    <th class="p-5 min-w-[240px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                                        System Capabilities & Modules
                                    </th>

                                    <th
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="p-5 min-w-[160px] text-center border-l border-slate-200/60 dark:border-slate-800"
                                    >
                                        <div class="flex flex-col items-center space-y-1.5">
                                            <div :class="['w-8 h-8 rounded-xl bg-gradient-to-tr text-white flex items-center justify-center font-bold text-xs shadow-xs', getRoleBadge(role.name).avatarBg]">
                                                <ShieldCheck v-if="role.name === 'Super Admin'" class="w-4 h-4" />
                                                <Users v-else class="w-4 h-4" />
                                            </div>
                                            <p class="font-bold text-slate-900 dark:text-white text-xs tracking-tight">{{ role.name }}</p>
                                            <span class="text-[10px] text-slate-400 font-mono">
                                                {{ role.users_count || 0 }} members
                                            </span>

                                            <button
                                                v-if="role.name !== 'Super Admin'"
                                                type="button"
                                                @click="toggleAllForRole(role.id)"
                                                class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline pt-0.5"
                                            >
                                                Toggle All
                                            </button>
                                        </div>
                                    </th>
                                </tr>
                            </thead>

                            <!-- Matrix Body Grouped by Module -->
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                <template v-for="(perms, module) in groupedPermissions" :key="module">
                                    <!-- Module Section Header Row -->
                                    <tr class="bg-slate-100/60 dark:bg-slate-800/40 font-bold text-slate-800 dark:text-slate-200">
                                        <td :colspan="roles.length + 1" class="px-5 py-2.5">
                                            <div class="flex items-center gap-2">
                                                <component :is="getModuleIcon(module)" class="w-4 h-4 text-indigo-500 shrink-0" />
                                                <span class="uppercase text-[11px] tracking-wider">{{ module }} Management</span>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Permission Rows inside Module -->
                                    <tr
                                        v-for="p in perms"
                                        :key="p.id"
                                        class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors"
                                    >
                                        <!-- Permission Name & Description -->
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-600"></span>
                                                <span class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-200">{{ p.name }}</span>
                                            </div>
                                        </td>

                                        <!-- Role Intersection Cells -->
                                        <td
                                            v-for="role in roles"
                                            :key="role.id"
                                            class="px-5 py-3.5 text-center border-l border-slate-100 dark:border-slate-800/60"
                                        >
                                            <!-- Super Admin Lock Badge -->
                                            <div v-if="role.name === 'Super Admin'" class="inline-flex items-center justify-center">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20">
                                                    <Lock class="w-2.5 h-2.5" /> Full
                                                </span>
                                            </div>

                                            <!-- Interactive Checkbox Toggle -->
                                            <div v-else class="flex items-center justify-center">
                                                <button
                                                    type="button"
                                                    @click="toggleMatrixPermission(role.id, p.name)"
                                                    :class="[
                                                        isPermissionActive(role.id, p.name)
                                                            ? 'bg-indigo-600 text-white shadow-xs'
                                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-300 dark:text-slate-600 hover:bg-slate-200 dark:hover:bg-slate-700',
                                                        'w-6 h-6 rounded-lg flex items-center justify-center transition-all cursor-pointer'
                                                    ]"
                                                >
                                                    <Check v-if="isPermissionActive(role.id, p.name)" class="w-3.5 h-3.5 stroke-[3]" />
                                                    <span v-else class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
