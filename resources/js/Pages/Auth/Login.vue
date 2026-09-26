<script setup>
import { ref, computed, onMounted, watchEffect } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    Store,
    Mail,
    Lock,
    Eye,
    EyeOff,
    Sun,
    Moon,
    CheckCircle2,
    AlertCircle,
    Loader2
} from 'lucide-vue-next';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const showPassword = ref(false);
const isDarkMode = ref(false);

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

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

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

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans flex flex-col justify-center items-center px-4 py-12 relative transition-colors duration-200">
        <Head title="Sign In" />

        <!-- Top Right Theme Toggle -->
        <div class="absolute top-6 right-6">
            <button
                @click="toggleDarkMode"
                type="button"
                class="p-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shadow-xs cursor-pointer"
                title="Toggle Theme"
            >
                <Sun v-if="isDarkMode" class="w-4 h-4 text-amber-400" />
                <Moon v-else class="w-4 h-4 text-slate-700" />
            </button>
        </div>

        <div class="w-full max-w-sm space-y-6">
            <!-- Brand Logo & Store Header -->
            <div class="flex flex-col items-center text-center space-y-3">
                <div v-if="storeLogo" class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-1.5 flex items-center justify-center shrink-0 shadow-xs overflow-hidden">
                    <img :src="storeLogo" :alt="storeName" class="max-w-full max-h-full object-contain" />
                </div>
                <div v-else class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 flex items-center justify-center text-white font-bold shadow-md shadow-indigo-500/25 shrink-0">
                    <Store class="w-6 h-6" />
                </div>
                <div>
                    <h1 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">
                        {{ storeName }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Sign in to your account
                    </p>
                </div>
            </div>

            <!-- Login Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl shadow-slate-200/50 dark:shadow-none space-y-5">
                <!-- Status Message -->
                <div
                    v-if="status"
                    class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs font-semibold flex items-center gap-2.5"
                >
                    <CheckCircle2 class="w-4 h-4 shrink-0" />
                    {{ status }}
                </div>

                <!-- Prominent Error Alert Banner -->
                <div
                    v-if="form.errors.email || form.errors.password || page.props.flash?.error"
                    class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/25 text-rose-700 dark:text-rose-400 text-xs font-semibold flex items-start gap-2.5 animate-pulse"
                >
                    <AlertCircle class="w-4 h-4 shrink-0 mt-0.5 text-rose-600 dark:text-rose-400" />
                    <div class="flex-1 leading-snug">
                        <span class="font-bold block">Invalid Credentials</span>
                        <span>{{ form.errors.email || form.errors.password || page.props.flash?.error || 'These credentials do not match our records.' }}</span>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Email Address
                        </label>
                        <div class="relative">
                            <Mail :class="['w-4 h-4 absolute left-3.5 top-3 transition-colors', form.errors.email ? 'text-rose-500' : 'text-slate-400']" />
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="name@example.com"
                                :class="[
                                    'w-full pl-10 pr-4 py-2.5 rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:border-transparent transition-all',
                                    form.errors.email
                                        ? 'bg-rose-50/50 dark:bg-rose-950/20 border border-rose-500/80 focus:ring-rose-500 text-rose-900 dark:text-rose-200'
                                        : 'bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 focus:ring-indigo-500'
                                ]"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-500 font-semibold flex items-center gap-1.5">
                            <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                Password
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <div class="relative">
                            <Lock :class="['w-4 h-4 absolute left-3.5 top-3 transition-colors', form.errors.password ? 'text-rose-500' : 'text-slate-400']" />
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                :class="[
                                    'w-full pl-10 pr-10 py-2.5 rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:border-transparent transition-all',
                                    form.errors.password
                                        ? 'bg-rose-50/50 dark:bg-rose-950/20 border border-rose-500/80 focus:ring-rose-500 text-rose-900 dark:text-rose-200'
                                        : 'bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 focus:ring-indigo-500'
                                ]"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                            >
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-500 font-semibold flex items-center gap-1.5">
                            <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="rounded-lg border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-800"
                            />
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-400">
                                Remember me
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full inline-flex items-center justify-center gap-2 py-3 px-5 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/25 transition-all cursor-pointer disabled:opacity-60"
                        >
                            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                            <span v-else>Sign In</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
