<script setup>
import { ref, onMounted, computed, watch, watchEffect } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    ShoppingBag,
    ShoppingCart,
    FolderTree,
    Users,
    Settings,
    LogOut,
    Menu,
    X,
    Sun,
    Moon,
    Bell,
    ChevronDown,
    Store,
    CheckCircle2,
    AlertCircle,
    ShieldCheck,
    ShieldAlert,
    Boxes,
    Image,
    Zap,
    Ticket,
    Star
} from 'lucide-vue-next';

const page = usePage();
const isSidebarOpen = ref(true);
const isMobileMenuOpen = ref(false);
const isUserDropdownOpen = ref(false);
const isDarkMode = ref(false);

// Toast notification state with auto-fade after 3 seconds
const showFlash = ref(false);
const flashMessage = ref('');
const flashType = ref('success');
let flashTimer = null;

function triggerFlash(message, type = 'success') {
    if (!message) return;
    if (flashTimer) clearTimeout(flashTimer);
    flashMessage.value = message;
    flashType.value = type;
    showFlash.value = true;

    flashTimer = setTimeout(() => {
        showFlash.value = false;
    }, 3000);
}

function closeFlash() {
    if (flashTimer) clearTimeout(flashTimer);
    showFlash.value = false;
}

watch(
    () => page.props.flash,
    (newFlash) => {
        if (newFlash?.success) {
            triggerFlash(newFlash.success, 'success');
        } else if (newFlash?.error) {
            triggerFlash(newFlash.error, 'error');
        }
    },
    { deep: true, immediate: true }
);

const storeName = computed(() => page.props.site_settings?.store_name || 'ApexStore');
const storeLogo = computed(() => page.props.site_settings?.store_logo || null);
const storeFavicon = computed(() => page.props.site_settings?.store_favicon || null);

watchEffect(() => {
    if (storeFavicon.value && typeof document !== 'undefined') {
        let link = document.querySelector("link[rel~='icon']");
        if (!link) {
            link = document.createElement('link');
            link.rel = 'icon';
            document.getElementsByTagName('head')[0].appendChild(link);
        }
        link.href = storeFavicon.value;
    }
});

const allNavItems = [
    { name: 'Dashboard', route: 'admin.dashboard', icon: LayoutDashboard, permission: 'dashboard.view' },
    { name: 'Products', route: 'admin.products.index', icon: ShoppingBag, permission: 'products.view' },
    { name: 'Inventory', route: 'admin.inventory.index', icon: Boxes, permission: 'inventory.view' },
    { name: 'Orders', route: 'admin.orders.index', icon: ShoppingCart, permission: 'orders.view' },
    { name: 'Fraud Shield', route: 'admin.fraud.index', icon: ShieldAlert, permission: 'fraud.view' },
    { name: 'Categories', route: 'admin.categories.index', icon: FolderTree, permission: 'categories.view' },
    { name: 'Banners', route: 'admin.banners.index', icon: Image, permission: 'banners.view' },
    { name: 'Campaigns', route: 'admin.campaigns.index', icon: Zap, permission: 'promotions.view' },
    { name: 'Coupons', route: 'admin.coupons.index', icon: Ticket, permission: 'coupons.view' },
    { name: 'Reviews', route: 'admin.reviews.index', icon: Star, permission: 'reviews.view' },
    { name: 'Customers', route: 'admin.customers.index', icon: Users, permission: 'customers.view' },
    { name: 'Staff & Roles', route: 'admin.staff.index', icon: ShieldCheck, permission: 'staff.view' },
    { name: 'Settings', route: 'admin.settings.index', icon: Settings, permission: 'settings.view' },
];

function hasPermission(permission) {
    if (!permission) return true;
    const user = page.props.auth?.user;
    if (!user) return false;
    if (user.is_super_admin || (Array.isArray(user.roles) && user.roles.includes('Super Admin'))) {
        return true;
    }
    return Array.isArray(user.permissions) && user.permissions.includes(permission);
}

const navItems = computed(() => allNavItems.filter((item) => hasPermission(item.permission)));

function toggleDarkMode() {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
}

onMounted(() => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDarkMode.value = false;
        document.documentElement.classList.remove('dark');
    }
});

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col font-sans transition-colors duration-200">
        <!-- Toast Notification from flash session (Auto-fades after 3s) -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4 scale-95"
        >
            <div
                v-if="showFlash && flashMessage"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-2xl text-sm font-medium text-white backdrop-blur-md transition-all duration-300 border border-white/10"
                :class="flashType === 'success' ? 'bg-emerald-600/95 shadow-emerald-600/20' : 'bg-rose-600/95 shadow-rose-600/20'"
            >
                <CheckCircle2 v-if="flashType === 'success'" class="w-5 h-5 shrink-0" />
                <AlertCircle v-else class="w-5 h-5 shrink-0" />
                <span class="pr-1">{{ flashMessage }}</span>
                <button
                    @click="closeFlash"
                    type="button"
                    class="p-1 rounded-lg hover:bg-white/20 transition-colors ml-1 -mr-1 text-white/80 hover:text-white"
                    aria-label="Close notification"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>
        </Transition>

        <div class="flex flex-1 overflow-hidden">
            <!-- Sidebar Desktop -->
            <aside
                :class="[
                    isSidebarOpen ? 'w-52' : 'w-18',
                    'hidden lg:flex flex-col bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 transition-all duration-300 z-30'
                ]"
            >
                <!-- Brand / Logo Header -->
                <div
                    class="h-16 flex items-center border-b border-slate-200 dark:border-slate-800 transition-all px-3.5"
                    :class="isSidebarOpen ? 'justify-start' : 'justify-center'"
                >
                    <Link :href="route('admin.dashboard')" class="flex items-center gap-2.5 overflow-hidden">
                        <div v-if="storeLogo" class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 p-1 flex items-center justify-center border border-slate-200 dark:border-slate-700 shrink-0 overflow-hidden">
                            <img :src="storeLogo" :alt="storeName" class="max-w-full max-h-full object-contain" />
                        </div>
                        <div v-else class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white font-bold shadow-md shadow-indigo-500/20 shrink-0">
                            <Store class="w-4 h-4" />
                        </div>
                        <div v-if="isSidebarOpen" class="flex flex-col min-w-0">
                            <span class="font-bold text-slate-900 dark:text-white text-sm tracking-tight leading-none truncate">{{ storeName }}</span>
                            <span class="text-[9px] text-indigo-600 dark:text-indigo-400 font-semibold tracking-wider uppercase mt-1">Admin Hub</span>
                        </div>
                    </Link>
                </div>

                <!-- Navigation Links -->
                <div class="flex-1 py-5 px-2.5 space-y-1 overflow-y-auto">
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="route(item.route)"
                        :class="[
                            route().current(item.route + '*')
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-semibold'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100',
                            isSidebarOpen ? 'px-3' : 'justify-center px-0',
                            'flex items-center gap-2.5 py-2.5 rounded-xl text-sm transition-all duration-150 group'
                        ]"
                        :title="!isSidebarOpen ? item.name : ''"
                    >
                        <component
                            :is="item.icon"
                            :class="[
                                route().current(item.route + '*')
                                    ? 'text-white'
                                    : 'text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300',
                                'w-5 h-5 shrink-0 transition-colors'
                            ]"
                        />
                        <span v-if="isSidebarOpen" class="truncate">{{ item.name }}</span>
                    </Link>
                </div>
            </aside>

            <!-- Mobile Sidebar Overlay -->
            <div
                v-if="isMobileMenuOpen"
                @click="isMobileMenuOpen = false"
                class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40 lg:hidden"
            />

            <!-- Mobile Drawer -->
            <aside
                :class="[
                    isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
                    'fixed inset-y-0 left-0 w-72 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 z-50 flex flex-col transition-transform duration-300 lg:hidden'
                ]"
            >
                <div class="h-16 flex items-center justify-between px-4 border-b border-slate-200 dark:border-slate-800">
                    <Link :href="route('admin.dashboard')" class="flex items-center gap-3">
                        <div v-if="storeLogo" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 p-1 flex items-center justify-center border border-slate-200 dark:border-slate-700 shrink-0 overflow-hidden">
                            <img :src="storeLogo" :alt="storeName" class="max-w-full max-h-full object-contain" />
                        </div>
                        <div v-else class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white font-bold">
                            <Store class="w-5 h-5" />
                        </div>
                        <span class="font-bold text-slate-900 dark:text-white text-base">{{ storeName }}</span>
                    </Link>
                    <button @click="isMobileMenuOpen = false" class="p-2 text-slate-400">
                        <X class="w-6 h-6" />
                    </button>
                </div>
                <div class="flex-1 py-4 px-3 space-y-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="route(item.route)"
                        @click="isMobileMenuOpen = false"
                        :class="[
                            route().current(item.route + '*')
                                ? 'bg-indigo-600 text-white font-semibold'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm'
                        ]"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        <span>{{ item.name }}</span>
                    </Link>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
                <!-- Top Navbar -->
                <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-20">
                    <div class="flex items-center gap-3">
                        <!-- Mobile Hamburger Button -->
                        <button
                            @click="isMobileMenuOpen = true"
                            class="p-2 -ml-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 lg:hidden"
                        >
                            <Menu class="w-6 h-6" />
                        </button>

                        <!-- Desktop Sidebar Toggle Button -->
                        <button
                            @click="isSidebarOpen = !isSidebarOpen"
                            class="hidden lg:flex p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-colors"
                            title="Toggle Sidebar"
                        >
                            <Menu class="w-5 h-5" />
                        </button>

                        <slot name="header">
                            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                                Dashboard
                            </h1>
                        </slot>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-4">
                        <!-- Dark Mode Switcher -->
                        <button
                            @click="toggleDarkMode"
                            class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            title="Toggle Dark Mode"
                        >
                            <Sun v-if="isDarkMode" class="w-5 h-5 text-amber-400" />
                            <Moon v-else class="w-5 h-5 text-slate-600" />
                        </button>

                        <!-- Notification Button -->
                        <div class="relative">
                            <button
                                class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 relative transition-colors"
                            >
                                <Bell class="w-5 h-5" />
                                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-indigo-600 ring-2 ring-white dark:ring-slate-900"></span>
                            </button>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="relative" v-if="page.props.auth?.user">
                            <button
                                @click="isUserDropdownOpen = !isUserDropdownOpen"
                                class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                            >
                                <img
                                    :src="page.props.auth.user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(page.props.auth.user.name)}&background=6366f1&color=fff`"
                                    alt="Avatar"
                                    class="w-8 h-8 rounded-full object-cover ring-2 ring-indigo-500/20"
                                />
                                <span class="hidden md:inline-block text-xs font-semibold text-slate-700 dark:text-slate-200">
                                    {{ page.props.auth.user.name }}
                                </span>
                                <ChevronDown class="w-4 h-4 text-slate-400" />
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-if="isUserDropdownOpen"
                                @click="isUserDropdownOpen = false"
                                class="fixed inset-0 z-30"
                            />
                            <div
                                v-if="isUserDropdownOpen"
                                class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl py-1 z-40"
                            >
                                <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-800">
                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Signed in as</p>
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ page.props.auth.user.email }}</p>
                                </div>
                                <Link
                                    :href="route('profile.edit')"
                                    class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
                                >
                                    <Users class="w-4 h-4 text-slate-400" />
                                    Profile Settings
                                </Link>
                                <button
                                    @click="logout"
                                    class="w-full text-left flex items-center gap-2 px-4 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 cursor-pointer"
                                >
                                    <LogOut class="w-4 h-4 text-rose-500" />
                                    Sign Out
                                </button>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Main Content Body -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
