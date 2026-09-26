<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3';
import { User, Mail, ShieldCheck, CheckCircle2, AlertTriangle, Save, Loader2 } from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs flex flex-col justify-between">
        <div>
            <!-- Header -->
            <div class="flex items-center gap-3 pb-6 border-b border-slate-100 dark:border-slate-800">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <User class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">
                        Personal Information
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Update your administrator name, contact email, and public profile
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="mt-6 space-y-5">
                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                        Full Name
                    </label>
                    <div class="relative">
                        <User class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Your full name"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        />
                    </div>
                    <p v-if="form.errors.name" class="mt-1.5 text-xs text-rose-500 font-medium">
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                        Email Address
                    </label>
                    <div class="relative">
                        <Mail class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="admin@example.com"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        />
                    </div>
                    <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-500 font-medium">
                        {{ form.errors.email }}
                    </p>
                </div>

                <!-- Email Verification Notice -->
                <div
                    v-if="mustVerifyEmail && user.email_verified_at === null"
                    class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-400"
                >
                    <div class="flex items-start gap-3">
                        <AlertTriangle class="w-5 h-5 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
                        <div class="text-xs space-y-2">
                            <p class="font-medium">Your email address is currently unverified.</p>
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="font-bold underline hover:text-amber-800 dark:hover:text-amber-300 cursor-pointer"
                            >
                                Click here to resend the verification link.
                            </Link>
                            <div
                                v-show="status === 'verification-link-sent'"
                                class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 mt-2"
                            >
                                <CheckCircle2 class="w-4 h-4" />
                                A fresh verification email has been dispatched to your inbox!
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="pt-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition ease-in duration-150"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 translate-y-1"
                        >
                            <span
                                v-if="form.recentlySuccessful"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-3 py-1.5 rounded-xl"
                            >
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                Changes Saved Successfully
                            </span>
                        </Transition>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all cursor-pointer disabled:opacity-60"
                    >
                        <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                        <Save v-else class="w-4 h-4" />
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
