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
    HelpCircle,
    SlidersHorizontal,
    Filter,
    Shield,
    AlertCircle,
    Info,
    Minus,
    CheckCheck,
    Eye
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
                barBg: 'bg-gradient-to-r from-purple-500 to-indigo-500',
            };
        case 'Store Manager':
            return {
                bg: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',
                dot: 'bg-indigo-500',
                avatarBg: 'from-indigo-600 to-blue-600',
                barBg: 'bg-indigo-500',
            };
        case 'Fulfillment Staff':
            return {
                bg: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
                dot: 'bg-blue-500',
                avatarBg: 'from-blue-600 to-cyan-600',
                barBg: 'bg-blue-500',
            };
        case 'Catalog Specialist':
            return {
                bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                dot: 'bg-emerald-500',
                avatarBg: 'from-emerald-600 to-teal-600',
                barBg: 'bg-emerald-500',
            };
        default:
            return {
                bg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                dot: 'bg-amber-500',
                avatarBg: 'from-amber-600 to-orange-600',
                barBg: 'bg-amber-500',
            };
    }
}

// Matrix search and filtering state
const matrixSearch = ref('');
const selectedModuleFilter = ref('all');

const permissionMeta = {
    'products.view': {
        title: 'View Products',
        description: 'Browse catalog items, stock levels, variants, and pricing structures',
        type: 'read',
    },
    'products.create': {
        title: 'Create Products',
        description: 'Publish new products, configure SKU specifications, and upload galleries',
        type: 'write',
    },
    'products.edit': {
        title: 'Edit Products',
        description: 'Update product information, modify pricing, and adjust stock quantities',
        type: 'write',
    },
    'products.delete': {
        title: 'Delete Products',
        description: 'Permanently remove or archive products from the storefront catalog',
        type: 'danger',
    },
    'orders.view': {
        title: 'View Orders',
        description: 'Access customer orders, billing summaries, and order tracking streams',
        type: 'read',
    },
    'orders.edit': {
        title: 'Edit Orders',
        description: 'Modify ordered items, quantities, customer details, and shipping destinations',
        type: 'write',
    },
    'orders.update_status': {
        title: 'Update Order Status',
        description: 'Transition order lifecycles (processing, shipped, completed, cancelled)',
        type: 'write',
    },
    'orders.invoice': {
        title: 'Generate Invoices',
        description: 'Generate, download, and print official customer tax invoices and receipts',
        type: 'read',
    },
    'categories.view': {
        title: 'View Categories',
        description: 'Explore product taxonomy, category trees, and featured collections',
        type: 'read',
    },
    'categories.create': {
        title: 'Create Categories',
        description: 'Add new category branches, slugs, descriptions, and banner artwork',
        type: 'write',
    },
    'categories.edit': {
        title: 'Edit Categories',
        description: 'Reorganize category trees, rename slugs, and adjust parent hierarchies',
        type: 'write',
    },
    'categories.delete': {
        title: 'Delete Categories',
        description: 'Remove catalog categories and detach associated product taxonomy',
        type: 'danger',
    },
    'customers.view': {
        title: 'View Customers',
        description: 'Inspect registered customer profiles, lifetime metrics, and purchase activity',
        type: 'read',
    },
    'customers.edit': {
        title: 'Edit Customers',
        description: 'Update customer profiles, account statuses, and shipping addresses',
        type: 'write',
    },
    'settings.view': {
        title: 'View Settings',
        description: 'Inspect store configuration parameters, shipping rules, and payment gateways',
        type: 'admin',
    },
    'settings.edit': {
        title: 'Edit Settings',
        description: 'Configure store profile, currency, payment providers, and system parameters',
        type: 'admin',
    },
    'staff.view': {
        title: 'View Staff Directory',
        description: 'Inspect administrative team members and their assigned security roles',
        type: 'admin',
    },
    'staff.manage': {
        title: 'Manage Staff & Roles',
        description: 'Invite administrators, allocate security roles, and modify capability matrices',
        type: 'admin',
    },
};

const moduleMeta = {
    products: {
        title: 'Products & Inventory',
        description: 'SKU management, catalog listings, variant options, and stock control',
        icon: Package,
    },
    orders: {
        title: 'Orders & Fulfillment',
        description: 'Sales transactions, delivery status updates, customer invoices, and refunds',
        icon: ShoppingCart,
    },
    categories: {
        title: 'Categories & Taxonomy',
        description: 'Store navigation, category hierarchies, and product classifications',
        icon: FolderTree,
    },
    customers: {
        title: 'Customer Directory',
        description: 'Customer accounts, purchase histories, and contact profiles',
        icon: Users,
    },
    settings: {
        title: 'System Settings',
        description: 'Global business parameters, payment gateways, and shipping options',
        icon: Settings,
    },
    staff: {
        title: 'Staff & Security',
        description: 'Administrative access control, role assignments, and capability policies',
        icon: ShieldCheck,
    },
};

function getPermissionMeta(permName) {
    if (permissionMeta[permName]) {
        return permissionMeta[permName];
    }
    const parts = permName.split('.');
    const action = parts[1] || 'access';
    const isDanger = ['delete', 'destroy', 'remove'].includes(action);
    const isAdmin = ['manage', 'configure', 'settings'].includes(action);
    const isWrite = ['create', 'edit', 'update', 'store'].includes(action);
    return {
        title: permName.replace('.', ' ').replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()),
        description: `Perform ${permName} operations across the application`,
        type: isDanger ? 'danger' : isAdmin ? 'admin' : isWrite ? 'write' : 'read',
    };
}

function getPermissionTypeBadge(type) {
    switch (type) {
        case 'danger':
            return {
                label: 'Destructive',
                class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
            };
        case 'admin':
            return {
                label: 'Admin',
                class: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
            };
        case 'write':
            return {
                label: 'Write',
                class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
            };
        case 'read':
        default:
            return {
                label: 'Read',
                class: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
            };
    }
}

const totalPermissionsCount = computed(() => {
    return Object.values(props.groupedPermissions).reduce((acc, group) => acc + group.length, 0);
});

function getRolePermissionCount(roleId) {
    const role = props.roles.find(r => r.id === roleId);
    if (!role) return 0;
    if (role.name === 'Super Admin') {
        return totalPermissionsCount.value;
    }
    const roleEntry = matrixState.value.find(r => r.role_id === roleId);
    return roleEntry ? roleEntry.permissions.length : 0;
}

function getRolePermissionPercentage(roleId) {
    if (totalPermissionsCount.value === 0) return 0;
    return Math.round((getRolePermissionCount(roleId) / totalPermissionsCount.value) * 100);
}

function grantAllForRole(roleId) {
    const roleEntry = matrixState.value.find(r => r.role_id === roleId);
    if (!roleEntry || roleEntry.role_name === 'Super Admin') return;

    const allPermNames = Object.values(props.groupedPermissions).flatMap(group => group.map(p => p.name));
    roleEntry.permissions = [...allPermNames];
    isMatrixDirty.value = true;
}

function revokeAllForRole(roleId) {
    const roleEntry = matrixState.value.find(r => r.role_id === roleId);
    if (!roleEntry || roleEntry.role_name === 'Super Admin') return;

    roleEntry.permissions = [];
    isMatrixDirty.value = true;
}

function isModuleAllActive(moduleName, roleId) {
    const roleEntry = matrixState.value.find(r => r.role_id === roleId);
    if (!roleEntry) return false;
    if (roleEntry.role_name === 'Super Admin') return true;

    const modulePerms = props.groupedPermissions[moduleName] || [];
    if (modulePerms.length === 0) return false;
    return modulePerms.every(p => roleEntry.permissions.includes(p.name));
}

function isModulePartiallyActive(moduleName, roleId) {
    const roleEntry = matrixState.value.find(r => r.role_id === roleId);
    if (!roleEntry || roleEntry.role_name === 'Super Admin') return false;

    const modulePerms = props.groupedPermissions[moduleName] || [];
    const activeCount = modulePerms.filter(p => roleEntry.permissions.includes(p.name)).length;
    return activeCount > 0 && activeCount < modulePerms.length;
}

function toggleModuleForRole(moduleName, roleId) {
    const roleEntry = matrixState.value.find(r => r.role_id === roleId);
    if (!roleEntry || roleEntry.role_name === 'Super Admin') return;

    const modulePerms = props.groupedPermissions[moduleName] || [];
    const modulePermNames = modulePerms.map(p => p.name);
    const allActive = isModuleAllActive(moduleName, roleId);

    if (allActive) {
        roleEntry.permissions = roleEntry.permissions.filter(p => !modulePermNames.includes(p));
    } else {
        const newPerms = new Set([...roleEntry.permissions, ...modulePermNames]);
        roleEntry.permissions = Array.from(newPerms);
    }
    isMatrixDirty.value = true;
}

const filteredGroupedPermissions = computed(() => {
    const query = matrixSearch.value.trim().toLowerCase();
    const result = {};

    for (const [moduleName, perms] of Object.entries(props.groupedPermissions)) {
        if (selectedModuleFilter.value !== 'all' && selectedModuleFilter.value !== moduleName) {
            continue;
        }

        const filtered = perms.filter(p => {
            if (!query) return true;
            const meta = getPermissionMeta(p.name);
            return (
                p.name.toLowerCase().includes(query) ||
                meta.title.toLowerCase().includes(query) ||
                meta.description.toLowerCase().includes(query) ||
                meta.type.toLowerCase().includes(query) ||
                moduleName.toLowerCase().includes(query)
            );
        });

        if (filtered.length > 0) {
            result[moduleName] = filtered;
        }
    }

    return result;
});

const filteredPermissionsCount = computed(() => {
    return Object.values(filteredGroupedPermissions.value).reduce((acc, perms) => acc + perms.length, 0);
});

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
                <!-- 1. Top Role Overview Cards Deck -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <div
                        v-for="role in roles"
                        :key="role.id"
                        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col justify-between hover:border-slate-300 dark:hover:border-slate-700 transition-all"
                    >
                        <!-- Top: Avatar & Name -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <div :class="['w-9 h-9 rounded-2xl bg-gradient-to-tr text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0', getRoleBadge(role.name).avatarBg]">
                                    <ShieldCheck v-if="role.name === 'Super Admin'" class="w-4 h-4" />
                                    <Users v-else class="w-4 h-4" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 dark:text-white text-xs leading-snug tracking-tight">{{ role.name }}</h3>
                                    <p class="text-[11px] text-slate-400 font-medium">
                                        {{ role.users_count || 0 }} {{ role.users_count === 1 ? 'member' : 'members' }}
                                    </p>
                                </div>
                            </div>
                            <span
                                v-if="role.name === 'Super Admin'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20"
                            >
                                <Lock class="w-2.5 h-2.5" /> Root
                            </span>
                        </div>

                        <!-- Middle: Progress Meter -->
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80">
                            <div class="flex items-center justify-between text-[11px] mb-1.5 font-medium">
                                <span class="text-slate-500 dark:text-slate-400">Capabilities</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    <template v-if="role.name === 'Super Admin'">
                                        All ({{ totalPermissionsCount }}/{{ totalPermissionsCount }})
                                    </template>
                                    <template v-else>
                                        {{ getRolePermissionCount(role.id) }} / {{ totalPermissionsCount }}
                                    </template>
                                </span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div
                                    :class="['h-full rounded-full transition-all duration-300', getRoleBadge(role.name).barBg]"
                                    :style="{ width: role.name === 'Super Admin' ? '100%' : `${getRolePermissionPercentage(role.id)}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Bottom: Action Controls -->
                        <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                            <template v-if="role.name === 'Super Admin'">
                                <span class="text-[11px] text-purple-600 dark:text-purple-400 flex items-center gap-1 font-semibold">
                                    <Shield class="w-3 h-3" /> System Enforced
                                </span>
                                <span class="text-[10px] text-slate-400 font-mono">100%</span>
                            </template>
                            <template v-else>
                                <button
                                    type="button"
                                    @click="grantAllForRole(role.id)"
                                    class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 cursor-pointer"
                                >
                                    Grant All
                                </button>
                                <button
                                    type="button"
                                    @click="revokeAllForRole(role.id)"
                                    class="text-[11px] font-semibold text-rose-500 hover:text-rose-600 dark:hover:text-rose-400 cursor-pointer"
                                >
                                    Revoke All
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 2. Search, Module Filters, & Action Bar -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs space-y-4">
                    <!-- Top Row: Title & Save/Reset Actions -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                <Layers class="w-5 h-5" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Role Capability Matrix</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Configure precise operational permissions across all organizational roles</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                            <button
                                v-if="isMatrixDirty"
                                type="button"
                                @click="resetMatrix"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer"
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
                                    'inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold transition-all cursor-pointer'
                                ]"
                            >
                                <Loader2 v-if="matrixForm.processing" class="w-4 h-4 animate-spin" />
                                <Save v-else class="w-4 h-4" />
                                <span>{{ isMatrixDirty ? 'Save Matrix Changes' : 'Matrix Synchronized' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Bottom Row: Search Box & Module Pills -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                        <!-- Search Input -->
                        <div class="relative w-full md:w-80">
                            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="matrixSearch"
                                type="text"
                                placeholder="Search capabilities or actions..."
                                class="w-full pl-9 pr-8 py-2 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            />
                            <button
                                v-if="matrixSearch"
                                @click="matrixSearch = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                            >
                                <X class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <!-- Module Filter Pills -->
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
                            <button
                                type="button"
                                @click="selectedModuleFilter = 'all'"
                                :class="[
                                    selectedModuleFilter === 'all'
                                        ? 'bg-indigo-600 text-white font-bold shadow-xs'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                                    'px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition-colors cursor-pointer flex items-center gap-1.5'
                                ]"
                            >
                                <span>All Modules</span>
                                <span class="text-[10px] opacity-75 font-mono">({{ totalPermissionsCount }})</span>
                            </button>
                            <button
                                v-for="(perms, moduleKey) in groupedPermissions"
                                :key="moduleKey"
                                type="button"
                                @click="selectedModuleFilter = moduleKey"
                                :class="[
                                    selectedModuleFilter === moduleKey
                                        ? 'bg-indigo-600 text-white font-bold shadow-xs'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                                    'px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition-colors cursor-pointer flex items-center gap-1.5'
                                ]"
                            >
                                <component :is="getModuleIcon(moduleKey)" class="w-3.5 h-3.5 opacity-80" />
                                <span class="capitalize">{{ moduleKey }}</span>
                                <span class="text-[10px] opacity-75 font-mono">({{ perms.length }})</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 3. Matrix Grid Table Container -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50/90 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-20 backdrop-blur-md">
                                    <th class="p-5 min-w-[320px] max-w-[420px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px] sticky left-0 z-10 bg-slate-50/90 dark:bg-slate-800/80">
                                        <div class="flex items-center justify-between">
                                            <span>System Capabilities & Modules</span>
                                            <span class="text-[10px] normal-case text-slate-400 font-normal">
                                                Showing {{ filteredPermissionsCount }} of {{ totalPermissionsCount }}
                                            </span>
                                        </div>
                                    </th>

                                    <th
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="p-4 min-w-[170px] text-center border-l border-slate-200/60 dark:border-slate-800"
                                    >
                                        <div class="flex flex-col items-center space-y-1.5">
                                            <div :class="['w-8 h-8 rounded-xl bg-gradient-to-tr text-white flex items-center justify-center font-bold text-xs shadow-xs', getRoleBadge(role.name).avatarBg]">
                                                <ShieldCheck v-if="role.name === 'Super Admin'" class="w-4 h-4" />
                                                <Users v-else class="w-4 h-4" />
                                            </div>
                                            <p class="font-bold text-slate-900 dark:text-white text-xs tracking-tight">{{ role.name }}</p>
                                            
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[10px] text-slate-400 font-mono">
                                                    {{ getRolePermissionCount(role.id) }}/{{ totalPermissionsCount }} active
                                                </span>
                                            </div>

                                            <button
                                                v-if="role.name !== 'Super Admin'"
                                                type="button"
                                                @click="toggleAllForRole(role.id)"
                                                class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline pt-0.5 cursor-pointer"
                                            >
                                                Toggle All
                                            </button>
                                        </div>
                                    </th>
                                </tr>
                            </thead>

                            <!-- Matrix Body -->
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                <template v-for="(perms, module) in filteredGroupedPermissions" :key="module">
                                    <!-- Module Section Header Row -->
                                    <tr class="bg-slate-100/75 dark:bg-slate-800/60 font-bold text-slate-800 dark:text-slate-200 border-t border-b border-slate-200/80 dark:border-slate-700/60">
                                        <!-- Module Details Column (sticky) -->
                                        <td class="px-5 py-3 sticky left-0 z-10 bg-slate-100/90 dark:bg-slate-800/90">
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-7 h-7 rounded-xl bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                                        <component :is="getModuleIcon(module)" class="w-4 h-4" />
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-bold text-slate-900 dark:text-white text-xs tracking-wide">
                                                                {{ moduleMeta[module]?.title || module }}
                                                            </span>
                                                            <span class="text-[10px] font-normal px-2 py-0.5 rounded-full bg-slate-200/60 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300">
                                                                {{ perms.length }} {{ perms.length === 1 ? 'perm' : 'perms' }}
                                                            </span>
                                                        </div>
                                                        <p class="text-[11px] font-normal text-slate-500 dark:text-slate-400 hidden sm:block">
                                                            {{ moduleMeta[module]?.description }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Module-Level Bulk Toggle per Role -->
                                        <td
                                            v-for="role in roles"
                                            :key="role.id"
                                            class="px-4 py-3 text-center border-l border-slate-200/60 dark:border-slate-800"
                                        >
                                            <div v-if="role.name === 'Super Admin'" class="inline-flex items-center justify-center">
                                                <span class="text-[10px] text-purple-600 dark:text-purple-400 font-bold flex items-center gap-1 opacity-70">
                                                    <Lock class="w-3 h-3" /> Full
                                                </span>
                                            </div>
                                            <div v-else class="flex items-center justify-center">
                                                <button
                                                    type="button"
                                                    @click="toggleModuleForRole(module, role.id)"
                                                    :title="isModuleAllActive(module, role.id) ? `Revoke all ${module} permissions for ${role.name}` : `Grant all ${module} permissions for ${role.name}`"
                                                    :class="[
                                                        isModuleAllActive(module, role.id)
                                                            ? 'bg-indigo-600 text-white font-bold shadow-xs'
                                                            : isModulePartiallyActive(module, role.id)
                                                                ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 font-bold'
                                                                : 'bg-slate-200/80 dark:bg-slate-700/80 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600',
                                                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] transition-all cursor-pointer'
                                                    ]"
                                                >
                                                    <CheckCheck v-if="isModuleAllActive(module, role.id)" class="w-3 h-3" />
                                                    <Minus v-else-if="isModulePartiallyActive(module, role.id)" class="w-3 h-3" />
                                                    <span>
                                                        {{ isModuleAllActive(module, role.id) ? 'All' : isModulePartiallyActive(module, role.id) ? 'Partial' : 'None' }}
                                                    </span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Individual Permission Rows -->
                                    <tr
                                        v-for="p in perms"
                                        :key="p.id"
                                        class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors group"
                                    >
                                        <!-- Capability Details Column (sticky) -->
                                        <td class="px-5 py-3.5 sticky left-0 z-10 bg-white group-hover:bg-slate-50/70 dark:bg-slate-900 dark:group-hover:bg-slate-800/40 transition-colors">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-bold text-slate-900 dark:text-white text-xs">
                                                        {{ getPermissionMeta(p.name).title }}
                                                    </span>
                                                    <span class="font-mono text-[10px] px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200/60 dark:border-slate-700/60">
                                                        {{ p.name }}
                                                    </span>
                                                    <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', getPermissionTypeBadge(getPermissionMeta(p.name).type).class]">
                                                        {{ getPermissionTypeBadge(getPermissionMeta(p.name).type).label }}
                                                    </span>
                                                </div>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                                                    {{ getPermissionMeta(p.name).description }}
                                                </p>
                                            </div>
                                        </td>

                                        <!-- Role Check Cells -->
                                        <td
                                            v-for="role in roles"
                                            :key="role.id"
                                            class="px-4 py-3.5 text-center border-l border-slate-100 dark:border-slate-800/60 align-middle"
                                        >
                                            <!-- Super Admin: System Lock -->
                                            <div v-if="role.name === 'Super Admin'" class="inline-flex items-center justify-center">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20">
                                                    <Lock class="w-2.5 h-2.5" /> Full
                                                </span>
                                            </div>

                                            <!-- Other Roles: Toggle Button -->
                                            <div v-else class="flex items-center justify-center">
                                                <button
                                                    type="button"
                                                    @click="toggleMatrixPermission(role.id, p.name)"
                                                    :title="isPermissionActive(role.id, p.name) ? `Revoke ${p.name} from ${role.name}` : `Grant ${p.name} to ${role.name}`"
                                                    :class="[
                                                        isPermissionActive(role.id, p.name)
                                                            ? 'bg-indigo-600 text-white shadow-xs hover:bg-indigo-700 ring-2 ring-indigo-500/20 scale-100'
                                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-300 dark:text-slate-600 hover:bg-slate-200 dark:hover:bg-slate-700',
                                                        'w-7 h-7 rounded-xl flex items-center justify-center transition-all cursor-pointer'
                                                    ]"
                                                >
                                                    <Check v-if="isPermissionActive(role.id, p.name)" class="w-4 h-4 stroke-[3]" />
                                                    <span v-else class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <!-- Empty Search State -->
                                <tr v-if="filteredPermissionsCount === 0">
                                    <td :colspan="roles.length + 1" class="py-12 text-center">
                                        <div class="max-w-sm mx-auto flex flex-col items-center">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                                                <Search class="w-6 h-6" />
                                            </div>
                                            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">No matching capabilities</h3>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                                                No permissions matched "<span class="font-medium text-slate-700 dark:text-slate-300">{{ matrixSearch }}</span>"
                                                in the selected module filter.
                                            </p>
                                            <button
                                                type="button"
                                                @click="matrixSearch = ''; selectedModuleFilter = 'all'"
                                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors cursor-pointer"
                                            >
                                                Clear Search & Filters
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. Floating Unsaved Changes Notification Drawer -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="transform translate-y-8 opacity-0"
                    enter-to-class="transform translate-y-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="transform translate-y-0 opacity-100"
                    leave-to-class="transform translate-y-8 opacity-0"
                >
                    <div
                        v-if="isMatrixDirty"
                        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 w-[92%] max-w-2xl bg-slate-900/95 dark:bg-slate-800/95 backdrop-blur-md text-white border border-slate-700/60 rounded-3xl p-4 shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-4"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                                <AlertCircle class="w-5 h-5 animate-pulse" />
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Unsaved Capability Changes</p>
                                <p class="text-[11px] text-slate-300">You have adjusted permissions. Save to apply them immediately across all staff.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end shrink-0">
                            <button
                                type="button"
                                @click="resetMatrix"
                                class="px-3.5 py-2 rounded-2xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors cursor-pointer"
                            >
                                Discard
                            </button>
                            <button
                                type="button"
                                :disabled="matrixForm.processing"
                                @click="saveMatrix"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/30 transition-all cursor-pointer"
                            >
                                <Loader2 v-if="matrixForm.processing" class="w-4 h-4 animate-spin" />
                                <Save v-else class="w-4 h-4" />
                                <span>Save Permissions</span>
                            </button>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </AdminLayout>
</template>
