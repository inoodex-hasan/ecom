<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { KeyRound, Lock, CheckCircle2, Save, Loader2, ShieldAlert } from 'lucide-vue-next';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs flex flex-col justify-between">
        <div>
            <!-- Header -->
            <div class="flex items-center gap-3 pb-6 border-b border-slate-100 dark:border-slate-800">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <KeyRound class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">
                        Security & Password
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Ensure your administrator account uses a strong, unique secret key
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="updatePassword" class="mt-6 space-y-5">
                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                        Current Password
                    </label>
                    <div class="relative">
                        <Lock class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <input
                            id="current_password"
                            ref="currentPasswordInput"
                            v-model="form.current_password"
                            type="password"
                            placeholder="Enter current password"
                            autocomplete="current-password"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        />
                    </div>
                    <p v-if="form.errors.current_password" class="mt-1.5 text-xs text-rose-500 font-medium">
                        {{ form.errors.current_password }}
                    </p>
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                        New Password
                    </label>
                    <div class="relative">
                        <Lock class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <input
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            placeholder="Create new strong password"
                            autocomplete="new-password"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        />
                    </div>
                    <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-500 font-medium">
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                        Confirm New Password
                    </label>
                    <div class="relative">
                        <Lock class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Repeat new password"
                            autocomplete="new-password"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        />
                    </div>
                    <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs text-rose-500 font-medium">
                        {{ form.errors.password_confirmation }}
                    </p>
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
                                Password Updated
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
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
