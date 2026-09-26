<script setup>
import { ref, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { AlertOctagon, Trash2, X, Lock, Loader2 } from 'lucide-vue-next';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <div class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900/40 rounded-3xl p-6 sm:p-8 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <AlertOctagon class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">
                        Delete Account Permanently
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl">
                        Once your account is deleted, all assigned permissions, activity logs, and administrative privileges will be permanently purged. This action is irreversible.
                    </p>
                </div>
            </div>

            <div class="shrink-0">
                <button
                    type="button"
                    @click="confirmUserDeletion"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-600 hover:text-white border border-rose-200 dark:border-rose-900/60 transition-all cursor-pointer"
                >
                    <Trash2 class="w-4 h-4" />
                    Delete Account
                </button>
            </div>
        </div>

        <!-- Custom Glassmorphic Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="confirmingUserDeletion"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
            >
                <div
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150"
                >
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                                <AlertOctagon class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Confirm Account Purge</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Please authenticate to continue</p>
                            </div>
                        </div>
                        <button
                            @click="closeModal"
                            class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-xl"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Are you sure you want to permanently delete your administrator account? Enter your current master password below to confirm.
                    </p>

                    <div>
                        <label for="delete_password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                            Account Password
                        </label>
                        <div class="relative">
                            <Lock class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                            <input
                                id="delete_password"
                                ref="passwordInput"
                                v-model="form.password"
                                type="password"
                                placeholder="Enter your password"
                                @keyup.enter="deleteUser"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all"
                            />
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-500 font-medium">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 rounded-2xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="form.processing"
                            @click="deleteUser"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-2xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-md shadow-rose-600/25 transition-all cursor-pointer disabled:opacity-60"
                        >
                            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                            <Trash2 v-else class="w-4 h-4" />
                            Permanently Delete
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
