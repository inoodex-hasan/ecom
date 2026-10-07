<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Store,
    DollarSign,
    Percent,
    Truck,
    Save,
    CheckCircle2,
    Sliders,
    Image as ImageIcon,
    UploadCloud,
    Trash2,
    Globe,
    Loader2,
    Sparkles
} from 'lucide-vue-next';

const props = defineProps({
    settings: Object,
});

const logoInput = ref(null);
const faviconInput = ref(null);

const logoPreview = ref(props.settings?.store_logo?.value || null);
const faviconPreview = ref(props.settings?.store_favicon?.value || null);

const form = useForm({
    settings: {
        store_name: props.settings?.store_name?.value || 'ApexStore',
        store_email: props.settings?.store_email?.value || 'support@apexstore.io',
        store_phone: props.settings?.store_phone?.value || '+1 (555) 234-5678',
        currency_symbol: props.settings?.currency_symbol?.value || '৳',
        currency_code: props.settings?.currency_code?.value || 'BDT',
        tax_rate_percentage: props.settings?.tax_rate_percentage?.value || '8.25',
        flat_shipping_rate: props.settings?.flat_shipping_rate?.value || '12.50',
        free_shipping_threshold: props.settings?.free_shipping_threshold?.value || '150.00',
    },
    store_logo_file: null,
    store_favicon_file: null,
    remove_logo: false,
    remove_favicon: false,
});

function handleLogoChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.store_logo_file = file;
        form.remove_logo = false;
        logoPreview.value = URL.createObjectURL(file);
    }
}

function removeLogo() {
    form.store_logo_file = null;
    form.remove_logo = true;
    logoPreview.value = null;
    if (logoInput.value) logoInput.value.value = '';
}

function handleFaviconChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.store_favicon_file = file;
        form.remove_favicon = false;
        faviconPreview.value = URL.createObjectURL(file);
    }
}

function removeFavicon() {
    form.store_favicon_file = null;
    form.remove_favicon = true;
    faviconPreview.value = null;
    if (faviconInput.value) faviconInput.value.value = '';
}

function submit() {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Store Settings & Branding - Admin" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Store Preferences & Brand Identity</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Configure store logo, favicon, metadata, currency, tax rules, and delivery fees</p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="w-full space-y-6">
            <!-- Brand Identity & Assets Card (Logo & Favicon) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <Sparkles class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Brand Assets & Logo</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Upload your store's dashboard logo and browser tab favicon</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Dashboard Logo Upload -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Dashboard & Store Logo
                            </label>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Recommended size: 200×200px or rectangular vector. Supports PNG, SVG, JPG, WebP.
                            </p>
                        </div>

                        <!-- Logo Preview & Actions -->
                        <div class="flex flex-col sm:flex-row items-center gap-5 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60">
                            <!-- Logo Box Preview -->
                            <div class="w-24 h-24 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 p-2 flex items-center justify-center shrink-0 shadow-xs relative overflow-hidden">
                                <img
                                    v-if="logoPreview"
                                    :src="logoPreview"
                                    alt="Logo Preview"
                                    class="max-w-full max-h-full object-contain"
                                />
                                <div v-else class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                                    <Store class="w-8 h-8 opacity-40" />
                                    <span class="text-[9px] font-bold mt-1 uppercase tracking-wider">Default</span>
                                </div>
                            </div>

                            <div class="flex-1 space-y-2.5 text-center sm:text-left">
                                <input
                                    ref="logoInput"
                                    type="file"
                                    accept="image/png, image/jpeg, image/webp, image/svg+xml"
                                    class="hidden"
                                    @change="handleLogoChange"
                                />
                                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                    <button
                                        type="button"
                                        @click="logoInput?.click()"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs cursor-pointer transition-all"
                                    >
                                        <UploadCloud class="w-4 h-4" />
                                        {{ logoPreview ? 'Change Logo' : 'Upload Logo' }}
                                    </button>
                                    <button
                                        v-if="logoPreview"
                                        type="button"
                                        @click="removeLogo"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-900/40 cursor-pointer transition-colors"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                        Remove
                                    </button>
                                </div>
                                <p class="text-[11px] text-slate-400">Appears in sidebar, mobile header, and invoices.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Favicon Upload -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Browser Favicon
                            </label>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Displayed in browser tabs and bookmarks. Supports ICO, PNG, SVG (32×32px recommended).
                            </p>
                        </div>

                        <!-- Favicon Mockup & Actions -->
                        <div class="flex flex-col sm:flex-row items-center gap-5 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60">
                            <!-- Mini Browser Tab Preview Mockup -->
                            <div class="w-36 h-20 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 p-2.5 flex flex-col justify-between shrink-0 shadow-xs">
                                <div class="flex items-center gap-1.5 pb-1 border-b border-slate-100 dark:border-slate-800">
                                    <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                </div>
                                <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg">
                                    <img
                                        v-if="faviconPreview"
                                        :src="faviconPreview"
                                        alt="Favicon"
                                        class="w-4 h-4 rounded object-contain shrink-0"
                                    />
                                    <Globe v-else class="w-4 h-4 text-indigo-500 shrink-0" />
                                    <span class="text-[10px] font-medium text-slate-700 dark:text-slate-300 truncate">
                                        {{ form.settings.store_name }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex-1 space-y-2.5 text-center sm:text-left">
                                <input
                                    ref="faviconInput"
                                    type="file"
                                    accept=".ico, image/png, image/svg+xml, image/webp"
                                    class="hidden"
                                    @change="handleFaviconChange"
                                />
                                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                    <button
                                        type="button"
                                        @click="faviconInput?.click()"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs cursor-pointer transition-all"
                                    >
                                        <UploadCloud class="w-4 h-4" />
                                        {{ faviconPreview ? 'Change Favicon' : 'Upload Favicon' }}
                                    </button>
                                    <button
                                        v-if="faviconPreview"
                                        type="button"
                                        @click="removeFavicon"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-900/40 cursor-pointer transition-colors"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                        Remove
                                    </button>
                                </div>
                                <p class="text-[11px] text-slate-400">Updates the browser tab icon automatically.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- General Store Information & Currency Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- General Store Information -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                <Store class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">General Information</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Primary store identity and contact details</p>
                            </div>
                        </div>

                        <div class="space-y-4 pt-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Store Name</label>
                                <input
                                    v-model="form.settings.store_name"
                                    type="text"
                                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Support Email</label>
                                <input
                                    v-model="form.settings.store_email"
                                    type="email"
                                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Contact Phone</label>
                                <input
                                    v-model="form.settings.store_phone"
                                    type="text"
                                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Currency & Localization -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                <DollarSign class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Currency & Pricing</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Display currency format and ISO code</p>
                            </div>
                        </div>

                        <div class="space-y-4 pt-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Currency Symbol</label>
                                <input
                                    v-model="form.settings.currency_symbol"
                                    type="text"
                                    placeholder="৳"
                                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Currency Code (ISO)</label>
                                <input
                                    v-model="form.settings.currency_code"
                                    type="text"
                                    placeholder="BDT"
                                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 uppercase transition-all"
                                />
                            </div>
                            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/40 rounded-2xl text-xs text-slate-500 dark:text-slate-400">
                                💡 Prices across the store and customer invoices will automatically reflect <strong class="text-slate-800 dark:text-slate-200">{{ form.settings.currency_symbol }} ({{ form.settings.currency_code }})</strong>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Taxes & Shipping (Full Width) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs space-y-4">
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <Truck class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Shipping & Tax Rules</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Default checkout tax percentages and flat rate delivery fees</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Tax Rate (%)</label>
                        <input
                            v-model="form.settings.tax_rate_percentage"
                            type="number"
                            step="0.01"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">Applied automatically to checkout order totals.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Flat Shipping Fee (৳)</label>
                        <input
                            v-model="form.settings.flat_shipping_rate"
                            type="number"
                            step="0.01"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">Standard courier shipping rate for deliveries.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Free Shipping Above (৳)</label>
                        <input
                            v-model="form.settings.free_shipping_threshold"
                            type="number"
                            step="0.01"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">Orders exceeding this amount receive free delivery.</p>
                    </div>
                </div>
            </div>

            <!-- Save Action Bar -->
            <div class="flex items-center justify-between pt-2">
                <p class="text-xs text-slate-400">Settings take effect immediately upon saving.</p>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/25 transition-all cursor-pointer disabled:opacity-60"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <Save v-else class="w-4 h-4" />
                    Save All Preferences
                </button>
            </div>
        </form>
    </AdminLayout>
</template>
