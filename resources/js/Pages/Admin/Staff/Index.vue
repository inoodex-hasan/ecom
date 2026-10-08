<script setup>
import { ref, computed, watch } from 'vue';
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
    Eye,
    Boxes,
    Image as ImageIcon,
    Zap,
    Ticket,
    Star,
    Newspaper,
    LayoutGrid,
    Sliders,
    ArrowRight,
    Plus
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
        permissions: [...(role.permissions?.map(p => p.name) || [])],
    }))
);

watch(
    () => props.roles,
    (newRoles) => {
        matrixState.value = newRoles.map(role => ({
            role_id: role.id,
            role_name: role.name,
            permissions: [...(role.permissions?.map(p => p.name) || [])],
        }));
    },
    { deep: true }
);

// View Mode: 'table' (Comparison Grid) | 'role' (Role Inspector)
const matrixViewMode = ref('table');
const selectedFocusRoleId = ref(
    props.roles.find(r => r.name !== 'Super Admin')?.id || props.roles[0]?.id
);

const selectedFocusRole = computed(() => {
    return props.roles.find(r => r.id === selectedFocusRoleId.value) || props.roles[0];
});

// Role Create / Edit Modal State
const isRoleModalOpen = ref(false);
const editingRole = ref(null);

const roleForm = useForm({
    name: '',
    permissions: [],
});

function openCreateRoleModal() {
    editingRole.value = null;
    roleForm.reset();
    roleForm.clearErrors();
    roleForm.name = '';
    roleForm.permissions = [];
    isRoleModalOpen.value = true;
}

function openEditRoleModal(role) {
    editingRole.value = role;
    roleForm.reset();
    roleForm.clearErrors();
    roleForm.name = role.name;
    const currentEntry = matrixState.value.find(r => r.role_id === role.id);
    roleForm.permissions = [...(currentEntry?.permissions || role.permissions?.map(p => p.name) || [])];
    isRoleModalOpen.value = true;
}

function toggleRoleFormPermission(permName) {
    const idx = roleForm.permissions.indexOf(permName);
    if (idx >= 0) {
        roleForm.permissions.splice(idx, 1);
    } else {
        roleForm.permissions.push(permName);
    }
}

function toggleRoleFormModule(moduleName) {
    const modulePerms = (props.groupedPermissions[moduleName] || []).map(p => p.name);
    const allSelected = modulePerms.every(p => roleForm.permissions.includes(p));
    if (allSelected) {
        roleForm.permissions = roleForm.permissions.filter(p => !modulePerms.includes(p));
    } else {
        const set = new Set([...roleForm.permissions, ...modulePerms]);
        roleForm.permissions = Array.from(set);
    }
}

function selectAllRoleFormPermissions() {
    const all = Object.values(props.groupedPermissions).flatMap(g => g.map(p => p.name));
    roleForm.permissions = [...all];
}

function clearAllRoleFormPermissions() {
    roleForm.permissions = [];
}

function submitRoleModal() {
    if (editingRole.value) {
        roleForm.put(route('admin.roles.update', editingRole.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isRoleModalOpen.value = false;
            },
        });
    } else {
        roleForm.post(route('admin.roles.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isRoleModalOpen.value = false;
            },
        });
    }
}

function deleteRole(role) {
    if (confirm(`Are you sure you want to delete the role "${role.name}"? This action cannot be undone.`)) {
        router.delete(route('admin.roles.destroy', role.id), {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedFocusRoleId.value === role.id) {
                    selectedFocusRoleId.value = props.roles.find(r => r.id !== role.id)?.id || null;
                }
            },
        });
    }
}

// Matrix changes calculation
const matrixChangesCount = computed(() => {
    let diff = 0;
    for (const role of props.roles) {
        if (role.name === 'Super Admin') continue;
        const initialPerms = new Set(role.permissions?.map(p => p.name) || []);
        const currentEntry = matrixState.value.find(r => r.role_id === role.id);
        const currentPerms = new Set(currentEntry?.permissions || []);

        for (const p of currentPerms) {
            if (!initialPerms.has(p)) diff++;
        }
        for (const p of initialPerms) {
            if (!currentPerms.has(p)) diff++;
        }
    }
    return diff;
});

const modifiedRolesCount = computed(() => {
    let count = 0;
    for (const role of props.roles) {
        if (role.name === 'Super Admin') continue;
        const initialPerms = new Set(role.permissions?.map(p => p.name) || []);
        const currentEntry = matrixState.value.find(r => r.role_id === role.id);
        const currentPerms = new Set(currentEntry?.permissions || []);
        if (initialPerms.size !== currentPerms.size || [...initialPerms].some(p => !currentPerms.has(p))) {
            count++;
        }
    }
    return count;
});

const isMatrixDirty = computed(() => matrixChangesCount.value > 0);

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
}

function grantAllForRole(roleId) {
    const roleEntry = matrixState.value.find(r => r.role_id === roleId);
    if (!roleEntry || roleEntry.role_name === 'Super Admin') return;

    const allPermNames = Object.values(props.groupedPermissions).flatMap(group => group.map(p => p.name));
    roleEntry.permissions = [...allPermNames];
}

function revokeAllForRole(roleId) {
    const roleEntry = matrixState.value.find(r => r.role_id === roleId);
    if (!roleEntry || roleEntry.role_name === 'Super Admin') return;

    roleEntry.permissions = [];
}

function resetMatrix() {
    matrixState.value = props.roles.map(role => ({
        role_id: role.id,
        role_name: role.name,
        permissions: [...(role.permissions?.map(p => p.name) || [])],
    }));
}

function saveMatrix() {
    matrixForm.matrix = matrixState.value.map(r => ({
        role_id: r.role_id,
        permissions: r.permissions,
    }));

    matrixForm.post(route('admin.roles.bulk-update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Inertia will refresh props.roles and trigger watcher
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
        case 'Customer Support':
        default:
            return {
                bg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                dot: 'bg-amber-500',
                avatarBg: 'from-amber-600 to-orange-600',
                barBg: 'bg-amber-500',
            };
    }
}

function getRoleDescription(roleName) {
    switch (roleName) {
        case 'Super Admin':
            return 'Full unrestricted administrative privileges and immutable system control across all operational modules.';
        case 'Store Manager':
            return 'Broad store administration including inventory, catalog, promotions, orders, and customer management.';
        case 'Fulfillment Staff':
            return 'Order processing, packing, shipping statuses, dispatch invoices, and warehouse stock levels.';
        case 'Catalog Specialist':
            return 'Product catalog publishing, SKU listings, taxonomy organization, and category trees.';
        case 'Customer Support':
            return 'Frontline customer query resolution, order lookups, customer account profiles, and reviews.';
        default:
            return 'Custom defined administrative role with tailored operational capabilities.';
    }
}

// Matrix search and filtering state
const matrixSearch = ref('');
const selectedModuleFilter = ref('all');

const permissionMeta = {
    // Products
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

    // Inventory
    'inventory.view': {
        title: 'View Inventory',
        description: 'Inspect real-time stock balances, warehouse tracking, and low-stock alerts',
        type: 'read',
    },
    'inventory.adjust': {
        title: 'Adjust Stock Levels',
        description: 'Perform manual stock adjustments, restock audits, and warehouse allocations',
        type: 'write',
    },

    // Orders
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

    // Categories
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

    // Banners & Sliders
    'banners.view': {
        title: 'View Banners',
        description: 'View storefront hero carousels, promotional sliders, and banner impressions',
        type: 'read',
    },
    'banners.manage': {
        title: 'Manage Banners',
        description: 'Upload banner graphics, schedule flight dates, set CTA targets, and reorder',
        type: 'write',
    },

    // Campaigns & Promotions
    'promotions.view': {
        title: 'View Campaigns',
        description: 'Monitor active campaigns, countdown deals, and time-limited promotions',
        type: 'read',
    },
    'promotions.manage': {
        title: 'Manage Campaigns',
        description: 'Create promotional campaigns, set countdown timers, and assign discount items',
        type: 'write',
    },

    // Coupons & Promo Codes
    'coupons.view': {
        title: 'View Coupons',
        description: 'Review promo code records, discount metrics, redemption counts, and caps',
        type: 'read',
    },
    'coupons.manage': {
        title: 'Manage Coupons',
        description: 'Create discount codes, configure percentage/fixed discounts, and usage limits',
        type: 'write',
    },

    // Customer Reviews & Ratings
    'reviews.view': {
        title: 'View Reviews',
        description: 'Browse customer ratings, review feedback, submitted photos, and verified badges',
        type: 'read',
    },
    'reviews.manage': {
        title: 'Moderate Reviews',
        description: 'Approve, feature, unpublish, reject spam reviews, and reply to customers',
        type: 'write',
    },

    // Blogs & Editorial Articles
    'blogs.view': {
        title: 'View Blog Articles',
        description: 'Browse editorial articles, draft stories, and reader statistics',
        type: 'read',
    },
    'blogs.manage': {
        title: 'Manage Blog Articles',
        description: 'Create, write, publish, and delete blog posts and upload cover media',
        type: 'write',
    },

    // Customers
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

    // Staff & Security
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

    // Fraud Shield & Risk Control
    'fraud.view': {
        title: 'View Fraud Shield',
        description: 'Inspect order risk assessments, delivery courier scores, and flagged queues',
        type: 'read',
    },
    'fraud.manage': {
        title: 'Manage Fraud & Blacklist',
        description: 'Manage phone blacklist/whitelist, request advance fees, and block fraudulent orders',
        type: 'admin',
    },

    // System Settings
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
};

const moduleMeta = {
    products: {
        title: 'Products & Catalog',
        description: 'SKU management, catalog listings, variant options, and stock control',
        icon: Package,
    },
    inventory: {
        title: 'Inventory & Warehousing',
        description: 'Real-time stock tracking, stock adjustments, and low-inventory alerts',
        icon: Boxes,
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
    banners: {
        title: 'Banners & Sliders',
        description: 'Homepage hero banners, promotional sliders, and CTA link destinations',
        icon: ImageIcon,
    },
    promotions: {
        title: 'Campaigns & Promotions',
        description: 'Time-limited promotional campaigns, countdown sales, and promotional pricing events',
        icon: Zap,
    },
    coupons: {
        title: 'Coupons & Promo Codes',
        description: 'Promotional discount codes, usage quotas, minimum spend caps, and expiry dates',
        icon: Ticket,
    },
    reviews: {
        title: 'Customer Reviews & Ratings',
        description: 'Customer ratings, photo testimonials, moderation, and feedback replies',
        icon: Star,
    },
    blogs: {
        title: 'Blogs & Articles',
        description: 'Store news, editorial stories, trend guides, and promotional articles',
        icon: Newspaper,
    },
    customers: {
        title: 'Customer Directory',
        description: 'Customer accounts, purchase histories, and contact profiles',
        icon: Users,
    },
    fraud: {
        title: 'Fraud Shield & Risk Control',
        description: 'Bangladeshi phone validation, COD high-risk checks, courier RTO history, and blacklist registry',
        icon: ShieldAlert,
    },
    staff: {
        title: 'Staff & Security',
        description: 'Administrative access control, role assignments, and capability policies',
        icon: ShieldCheck,
    },
    settings: {
        title: 'System Settings',
        description: 'Global business parameters, payment gateways, and shipping options',
        icon: Settings,
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

function getModuleActiveCount(moduleName, roleId) {
    const roleEntry = matrixState.value.find(r => r.role_id === roleId);
    if (!roleEntry) return 0;
    if (roleEntry.role_name === 'Super Admin') {
        return (props.groupedPermissions[moduleName] || []).length;
    }
    const modulePerms = props.groupedPermissions[moduleName] || [];
    return modulePerms.filter(p => roleEntry.permissions.includes(p.name)).length;
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
    return moduleMeta[module]?.icon || ShieldCheck;
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

            <!-- TAB 2: RICH ROLES & PERMISSIONS CAPABILITY MATRIX -->
            <div v-if="activeTab === 'matrix'" class="space-y-6">
                <!-- 1. Top Role Overview Cards Deck -->
                <div class="space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Organizational Security Roles</h2>
                            <p class="text-xs text-slate-400 dark:text-slate-500">Overview of configured authority tiers and permission allocations</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                Total Capabilities: <strong class="text-slate-800 dark:text-slate-200">{{ totalPermissionsCount }} granular rights</strong>
                            </span>
                        </div>
                    </div>

                    <!-- Role Cards Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                        <div
                            v-for="role in roles"
                            :key="role.id"
                            :class="[
                                selectedFocusRoleId === role.id && matrixViewMode === 'role'
                                    ? 'ring-2 ring-indigo-500 shadow-md border-indigo-500/50'
                                    : 'hover:border-slate-300 dark:hover:border-slate-700',
                                'bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-4 shadow-xs flex flex-col justify-between transition-all'
                            ]"
                        >
                            <!-- Card Top -->
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

                            <!-- Meter -->
                            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80">
                                <div class="flex items-center justify-between text-[11px] mb-1.5 font-medium">
                                    <span class="text-slate-500 dark:text-slate-400">Capabilities</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200">
                                        {{ getRolePermissionCount(role.id) }} / {{ totalPermissionsCount }}
                                        <span class="text-[10px] text-slate-400 font-mono ml-0.5">({{ getRolePermissionPercentage(role.id) }}%)</span>
                                    </span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                    <div
                                        :class="['h-full rounded-full transition-all duration-300', getRoleBadge(role.name).barBg]"
                                        :style="{ width: `${getRolePermissionPercentage(role.id)}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                                <template v-if="role.name === 'Super Admin'">
                                    <span class="text-[11px] text-purple-600 dark:text-purple-400 flex items-center gap-1 font-semibold">
                                        <Shield class="w-3 h-3" /> System Enforced
                                    </span>
                                    <button
                                        type="button"
                                        @click="selectedFocusRoleId = role.id; matrixViewMode = 'role'"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-purple-600 dark:text-purple-400 hover:underline cursor-pointer"
                                    >
                                        Inspect <ArrowRight class="w-3 h-3" />
                                    </button>
                                </template>
                                <template v-else>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="grantAllForRole(role.id)"
                                            class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 cursor-pointer"
                                        >
                                            Grant
                                        </button>
                                        <span class="text-slate-300 dark:text-slate-700 text-xs">·</span>
                                        <button
                                            type="button"
                                            @click="revokeAllForRole(role.id)"
                                            class="text-[11px] font-semibold text-rose-500 hover:text-rose-600 dark:hover:text-rose-400 cursor-pointer"
                                        >
                                            Revoke
                                        </button>
                                    </div>
                                    <button
                                        type="button"
                                        @click="selectedFocusRoleId = role.id; matrixViewMode = 'role'"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 cursor-pointer"
                                    >
                                        Inspect <ArrowRight class="w-3 h-3" />
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Matrix Control Hub & Filters -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs space-y-4">
                    <!-- Top Row: Hub Header, View Mode Switcher, & Save Actions -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                <Layers class="w-5 h-5" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Role Capability Matrix</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Configure precise operational permissions across all organizational roles</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto justify-start lg:justify-end">
                            <!-- View Mode Segment Switcher -->
                            <div class="inline-flex items-center p-1 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60">
                                <button
                                    type="button"
                                    @click="matrixViewMode = 'table'"
                                    :class="[
                                        matrixViewMode === 'table'
                                            ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer'
                                    ]"
                                >
                                    <LayoutGrid class="w-3.5 h-3.5" />
                                    <span>Comparison Grid</span>
                                </button>
                                <button
                                    type="button"
                                    @click="matrixViewMode = 'role'"
                                    :class="[
                                        matrixViewMode === 'role'
                                            ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer'
                                    ]"
                                >
                                    <Sliders class="w-3.5 h-3.5" />
                                    <span>Role Inspector</span>
                                </button>
                            </div>

                            <!-- Reset, New Role & Save Buttons -->
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="openCreateRoleModal"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 transition-all cursor-pointer active:scale-95"
                                >
                                    <Plus class="w-4 h-4" />
                                    <span>New Role</span>
                                </button>
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
                                            ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/25 ring-2 ring-emerald-500/20'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-400 cursor-not-allowed',
                                        'inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold transition-all cursor-pointer'
                                    ]"
                                >
                                    <Loader2 v-if="matrixForm.processing" class="w-4 h-4 animate-spin" />
                                    <Save v-else class="w-4 h-4" />
                                    <span>{{ isMatrixDirty ? `Save (${matrixChangesCount})` : 'Matrix Saved' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Row: Search Box & 11 Module Filter Pills -->
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
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                            >
                                <X class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <!-- Module Filter Pills with Icons -->
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
                                <span>{{ moduleMeta[moduleKey]?.title?.split('&')[0]?.trim() || moduleKey }}</span>
                                <span class="text-[10px] opacity-75 font-mono">({{ perms.length }})</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 3A. VIEW MODE 1: COMPARISON GRID TABLE -->
                <div v-if="matrixViewMode === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50/90 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-20 backdrop-blur-md">
                                    <th class="p-5 min-w-[320px] max-w-[420px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px] sticky left-0 z-10 bg-slate-50/90 dark:bg-slate-800/80">
                                        <div class="flex items-center justify-between">
                                            <span>Operational Capabilities</span>
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

                                            <div class="flex items-center gap-1.5 pt-0.5 flex-wrap justify-center">
                                                <button
                                                    v-if="role.name !== 'Super Admin'"
                                                    type="button"
                                                    @click="toggleAllForRole(role.id)"
                                                    class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer"
                                                >
                                                    Toggle
                                                </button>
                                                <span v-if="role.name !== 'Super Admin'" class="text-slate-300 dark:text-slate-700 text-[10px]">·</span>
                                                <button
                                                    type="button"
                                                    @click="selectedFocusRoleId = role.id; matrixViewMode = 'role'"
                                                    class="text-[10px] font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 cursor-pointer"
                                                >
                                                    Inspect
                                                </button>
                                                <template v-if="role.name !== 'Super Admin'">
                                                    <span class="text-slate-300 dark:text-slate-700 text-[10px]">·</span>
                                                    <button
                                                        type="button"
                                                        @click="openEditRoleModal(role)"
                                                        class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 hover:underline cursor-pointer"
                                                        title="Edit role name & permissions"
                                                    >
                                                        Edit
                                                    </button>
                                                    <span class="text-slate-300 dark:text-slate-700 text-[10px]">·</span>
                                                    <button
                                                        type="button"
                                                        @click="deleteRole(role)"
                                                        class="text-[10px] font-semibold text-rose-500 hover:underline cursor-pointer"
                                                        title="Delete role"
                                                    >
                                                        Delete
                                                    </button>
                                                </template>
                                            </div>
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

                <!-- 3B. VIEW MODE 2: ROLE INSPECTOR (Bento Cards View) -->
                <div v-else-if="matrixViewMode === 'role'" class="space-y-6">
                    <!-- Role Selector Ribbon -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-3 shadow-xs">
                        <div class="flex items-center gap-2 overflow-x-auto scrollbar-none">
                            <button
                                v-for="role in roles"
                                :key="role.id"
                                type="button"
                                @click="selectedFocusRoleId = role.id"
                                :class="[
                                    selectedFocusRoleId === role.id
                                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-bold'
                                        : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60',
                                    'px-4 py-2 rounded-2xl text-xs flex items-center gap-2.5 transition-all cursor-pointer shrink-0'
                                ]"
                            >
                                <div :class="['w-5 h-5 rounded-lg flex items-center justify-center text-[10px]', selectedFocusRoleId === role.id ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700']">
                                    <ShieldCheck v-if="role.name === 'Super Admin'" class="w-3 h-3" />
                                    <Users v-else class="w-3 h-3" />
                                </div>
                                <span>{{ role.name }}</span>
                                <span :class="['text-[10px] font-mono px-2 py-0.5 rounded-full', selectedFocusRoleId === role.id ? 'bg-white/20 text-white' : 'bg-slate-200/70 dark:bg-slate-700/70 text-slate-600 dark:text-slate-300']">
                                    {{ getRolePermissionCount(role.id) }}/{{ totalPermissionsCount }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Selected Role Focus Header Card -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <div :class="['w-14 h-14 rounded-3xl bg-gradient-to-tr text-white flex items-center justify-center font-bold text-lg shadow-md shrink-0', getRoleBadge(selectedFocusRole.name).avatarBg]">
                                <ShieldCheck v-if="selectedFocusRole.name === 'Super Admin'" class="w-7 h-7" />
                                <Users v-else class="w-7 h-7" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">{{ selectedFocusRole.name }}</h3>
                                    <span :class="['text-[11px] font-bold px-2.5 py-0.5 rounded-full border', getRoleBadge(selectedFocusRole.name).bg]">
                                        {{ selectedFocusRole.users_count || 0 }} {{ selectedFocusRole.users_count === 1 ? 'member' : 'members' }}
                                    </span>
                                    <span v-if="selectedFocusRole.name === 'Super Admin'" class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 flex items-center gap-1">
                                        <Lock class="w-3 h-3" /> Root Security Role
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl">
                                    {{ getRoleDescription(selectedFocusRole.name) }}
                                </p>
                            </div>
                        </div>

                        <!-- Right Stats & Actions -->
                        <div class="w-full md:w-auto flex flex-col sm:flex-row items-start sm:items-center gap-4 pt-4 md:pt-0 border-t md:border-t-0 border-slate-100 dark:border-slate-800">
                            <!-- Progress Stats -->
                            <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl px-4 py-2.5 min-w-[160px]">
                                <p class="text-[11px] text-slate-400 font-medium">Granted Rights</p>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                    {{ getRolePermissionCount(selectedFocusRole.id) }} of {{ totalPermissionsCount }}
                                    <span class="text-xs font-normal text-slate-400">({{ getRolePermissionPercentage(selectedFocusRole.id) }}%)</span>
                                </p>
                            </div>

                            <!-- Bulk Action & Edit Buttons -->
                            <div class="flex items-center gap-2 flex-wrap">
                                <template v-if="selectedFocusRole.name !== 'Super Admin'">
                                    <button
                                        type="button"
                                        @click="openEditRoleModal(selectedFocusRole)"
                                        class="px-3.5 py-2 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer flex items-center gap-1.5"
                                    >
                                        <Edit3 class="w-3.5 h-3.5" />
                                        <span>Edit Role</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteRole(selectedFocusRole)"
                                        class="px-3.5 py-2 rounded-2xl text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/50 transition-colors cursor-pointer flex items-center gap-1.5"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                        <span>Delete Role</span>
                                    </button>
                                </template>
                                <button
                                    v-if="selectedFocusRole.name !== 'Super Admin'"
                                    type="button"
                                    @click="grantAllForRole(selectedFocusRole.id)"
                                    class="px-3.5 py-2 rounded-2xl text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors cursor-pointer"
                                >
                                    Grant All
                                </button>
                                <button
                                    v-if="selectedFocusRole.name !== 'Super Admin'"
                                    type="button"
                                    @click="revokeAllForRole(selectedFocusRole.id)"
                                    class="px-3.5 py-2 rounded-2xl text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/50 transition-colors cursor-pointer"
                                >
                                    Revoke All
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Bento Module Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div
                            v-for="(perms, moduleKey) in filteredGroupedPermissions"
                            :key="moduleKey"
                            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs space-y-4"
                        >
                            <!-- Bento Card Header -->
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                        <component :is="getModuleIcon(moduleKey)" class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-xs font-bold text-slate-900 dark:text-white">{{ moduleMeta[moduleKey]?.title || moduleKey }}</h4>
                                            <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                                {{ getModuleActiveCount(moduleKey, selectedFocusRole.id) }}/{{ perms.length }} Active
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-0.5">{{ moduleMeta[moduleKey]?.description }}</p>
                                    </div>
                                </div>

                                <div v-if="selectedFocusRole.name !== 'Super Admin'" class="shrink-0">
                                    <button
                                        type="button"
                                        @click="toggleModuleForRole(moduleKey, selectedFocusRole.id)"
                                        :class="[
                                            isModuleAllActive(moduleKey, selectedFocusRole.id)
                                                ? 'bg-indigo-600 text-white font-bold shadow-xs'
                                                : isModulePartiallyActive(moduleKey, selectedFocusRole.id)
                                                    ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 font-bold'
                                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] transition-all cursor-pointer'
                                        ]"
                                    >
                                        <CheckCheck v-if="isModuleAllActive(moduleKey, selectedFocusRole.id)" class="w-3 h-3" />
                                        <Minus v-else-if="isModulePartiallyActive(moduleKey, selectedFocusRole.id)" class="w-3 h-3" />
                                        <span>{{ isModuleAllActive(moduleKey, selectedFocusRole.id) ? 'All' : isModulePartiallyActive(moduleKey, selectedFocusRole.id) ? 'Partial' : 'Enable All' }}</span>
                                    </button>
                                </div>
                                <div v-else>
                                    <span class="text-[10px] text-purple-600 dark:text-purple-400 font-bold flex items-center gap-1 opacity-70">
                                        <Lock class="w-3 h-3" /> Full Access
                                    </span>
                                </div>
                            </div>

                            <!-- Permissions List -->
                            <div class="space-y-2 pt-1 border-t border-slate-100 dark:border-slate-800/80">
                                <div
                                    v-for="p in perms"
                                    :key="p.id"
                                    class="p-3 rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 hover:bg-slate-50 dark:hover:bg-slate-800/70 transition-colors flex items-center justify-between gap-4 border border-slate-100 dark:border-slate-800/60"
                                >
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-slate-900 dark:text-white text-xs">
                                                {{ getPermissionMeta(p.name).title }}
                                            </span>
                                            <span class="font-mono text-[10px] px-1.5 py-0.5 rounded-md bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200/60 dark:border-slate-700/60">
                                                {{ p.name }}
                                            </span>
                                            <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', getPermissionTypeBadge(getPermissionMeta(p.name).type).class]">
                                                {{ getPermissionTypeBadge(getPermissionMeta(p.name).type).label }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                            {{ getPermissionMeta(p.name).description }}
                                        </p>
                                    </div>

                                    <!-- Switch Toggle -->
                                    <div class="shrink-0 flex items-center">
                                        <div v-if="selectedFocusRole.name === 'Super Admin'" class="inline-flex items-center">
                                            <span class="text-[10px] font-bold text-purple-600 dark:text-purple-400 flex items-center gap-1">
                                                <Lock class="w-3 h-3" /> Locked
                                            </span>
                                        </div>
                                        <button
                                            v-else
                                            type="button"
                                            @click="toggleMatrixPermission(selectedFocusRole.id, p.name)"
                                            :class="[
                                                isPermissionActive(selectedFocusRole.id, p.name)
                                                    ? 'bg-indigo-600'
                                                    : 'bg-slate-200 dark:bg-slate-700',
                                                'relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none cursor-pointer'
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    isPermissionActive(selectedFocusRole.id, p.name) ? 'translate-x-5' : 'translate-x-0',
                                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out'
                                                ]"
                                            />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                <p class="text-xs font-bold text-white flex items-center gap-2">
                                    <span>Unsaved Permissions Modifications</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        {{ matrixChangesCount }} {{ matrixChangesCount === 1 ? 'change' : 'changes' }}
                                    </span>
                                </p>
                                <p class="text-[11px] text-slate-300">
                                    Pending modifications across {{ modifiedRolesCount }} {{ modifiedRolesCount === 1 ? 'role' : 'roles' }}. Save to enforce these rules.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end shrink-0">
                            <button
                                type="button"
                                @click="resetMatrix"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors cursor-pointer"
                            >
                                <RotateCcw class="w-3.5 h-3.5" /> Discard
                            </button>
                            <button
                                type="button"
                                :disabled="matrixForm.processing"
                                @click="saveMatrix"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/30 transition-all cursor-pointer"
                            >
                                <Loader2 v-if="matrixForm.processing" class="w-4 h-4 animate-spin" />
                                <Save v-else class="w-4 h-4" />
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Role Create / Edit Modal Dialog -->
        <div v-if="isRoleModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" @click="isRoleModalOpen = false"></div>

            <div class="relative bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col z-10">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                            <ShieldCheck class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">
                                {{ editingRole ? `Edit Role: ${editingRole.name}` : 'Create New Security Role' }}
                            </h3>
                            <p class="text-xs text-slate-400">
                                {{ editingRole ? 'Update the role title and modify its assigned permissions.' : 'Define a new role and configure its baseline operational permissions.' }}
                            </p>
                        </div>
                    </div>
                    <button @click="isRoleModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="submitRoleModal" class="flex-1 overflow-y-auto p-6 space-y-6">
                    <!-- Role Name Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Role Name *
                        </label>
                        <input
                            v-model="roleForm.name"
                            type="text"
                            placeholder="e.g. Inventory Supervisor, Marketing Manager, Support Lead"
                            :disabled="editingRole?.name === 'Super Admin'"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            required
                        />
                        <p v-if="roleForm.errors.name" class="mt-1 text-xs text-rose-500 font-medium">
                            {{ roleForm.errors.name }}
                        </p>
                    </div>

                    <!-- Permission Matrix Selection in Modal -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    Assigned Capabilities
                                </h4>
                                <p class="text-[11px] text-slate-400">
                                    {{ roleForm.permissions.length }} of {{ totalPermissionsCount }} permissions selected
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="selectAllRoleFormPermissions"
                                    class="px-2.5 py-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 rounded-lg cursor-pointer"
                                >
                                    Select All
                                </button>
                                <span class="text-slate-300 dark:text-slate-700">·</span>
                                <button
                                    type="button"
                                    @click="clearAllRoleFormPermissions"
                                    class="px-2.5 py-1 text-[11px] font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 rounded-lg cursor-pointer"
                                >
                                    Clear All
                                </button>
                            </div>
                        </div>

                        <!-- Module by Module Selector -->
                        <div class="space-y-3">
                            <div
                                v-for="(perms, moduleKey) in groupedPermissions"
                                :key="moduleKey"
                                class="p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <component :is="getModuleIcon(moduleKey)" class="w-4 h-4 text-indigo-500" />
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            {{ moduleMeta[moduleKey]?.title || moduleKey }}
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="toggleRoleFormModule(moduleKey)"
                                        class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer"
                                    >
                                        Toggle Module
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label
                                        v-for="p in perms"
                                        :key="p.name"
                                        class="flex items-start gap-2 p-2 rounded-xl hover:bg-white dark:hover:bg-slate-800 border border-transparent hover:border-slate-200/60 dark:hover:border-slate-700/60 transition-colors cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="p.name"
                                            :checked="roleForm.permissions.includes(p.name)"
                                            @change="toggleRoleFormPermission(p.name)"
                                            class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700"
                                        />
                                        <div>
                                            <p class="text-xs font-medium text-slate-800 dark:text-slate-200 leading-tight">
                                                {{ getPermissionMeta(p.name).title }}
                                            </p>
                                            <p class="text-[10px] text-slate-400 leading-tight mt-0.5">
                                                {{ getPermissionMeta(p.name).description }}
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="isRoleModalOpen = false"
                        class="px-4 py-2 rounded-2xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="roleForm.processing"
                        @click="submitRoleModal"
                        class="px-5 py-2 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all cursor-pointer flex items-center gap-1.5"
                    >
                        <Loader2 v-if="roleForm.processing" class="w-4 h-4 animate-spin" />
                        <Check v-else class="w-4 h-4" />
                        <span>{{ editingRole ? 'Update Role' : 'Create Role' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
