<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import {
    ShieldCheck,
    Calendar,
    Mail,
    Sparkles,
    UserCheck,
    Camera,
    Trash2,
    UploadCloud,
    Loader2,
    CheckCircle2
} from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const avatarFileInput = ref(null);
const isUploadingAvatar = ref(false);

const formatDate = (dateStr) => {
    if (!dateStr) return 'Active Member';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

function onAvatarSelected(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('avatar', file);

    isUploadingAvatar.value = true;
    router.post(route('profile.avatar'), formData, {
        preserveScroll: true,
        onFinish: () => {
            isUploadingAvatar.value = false;
        },
    });
}

function removeAvatar() {
    if (confirm('Remove profile photo and restore default initials avatar?')) {
        isUploadingAvatar.value = true;
        router.post(route('profile.avatar'), {
            remove_avatar: true,
        }, {
            preserveScroll: true,
            onFinish: () => {
                isUploadingAvatar.value = false;
            },
        });
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Profile Settings - Admin" />

        <template #header>
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Profile & Security</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Manage your administrative credentials, personal details, photo, and account security</p>
            </div>
        </template>

        <div class="space-y-6 w-full">
            <!-- Executive Profile Hero Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs relative overflow-hidden">
                <!-- Background ambient accent -->
                <div class="absolute -right-12 -top-12 w-64 h-64 bg-gradient-to-br from-indigo-500/10 via-purple-500/5 to-transparent rounded-full blur-2xl pointer-events-none" />

                <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-6">
                    <!-- Avatar with Hover Upload Trigger -->
                    <div class="relative group shrink-0">
                        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-3xl p-1 bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 shadow-lg shadow-indigo-500/20 relative overflow-hidden">
                            <img
                                :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=6366f1&color=fff&size=128`"
                                :alt="user.name"
                                class="w-full h-full rounded-[22px] object-cover bg-white dark:bg-slate-800"
                            />

                            <!-- Loading overlay -->
                            <div
                                v-if="isUploadingAvatar"
                                class="absolute inset-0 bg-slate-900/70 rounded-[22px] flex items-center justify-center text-white"
                            >
                                <Loader2 class="w-6 h-6 animate-spin text-indigo-400" />
                            </div>

                            <!-- Hover Overlay for Upload -->
                            <div
                                v-else
                                @click="avatarFileInput?.click()"
                                class="absolute inset-0 bg-slate-950/60 rounded-[22px] opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white cursor-pointer"
                            >
                                <Camera class="w-6 h-6 mb-1 text-white drop-shadow" />
                                <span class="text-[10px] font-bold">Change Photo</span>
                            </div>
                        </div>

                        <!-- Active status badge -->
                        <span class="absolute -bottom-1 -right-1 w-6 h-6 rounded-xl bg-emerald-500 text-white flex items-center justify-center ring-2 ring-white dark:ring-slate-900" title="Active">
                            <UserCheck class="w-3.5 h-3.5" />
                        </span>

                        <input
                            ref="avatarFileInput"
                            type="file"
                            accept="image/jpeg, image/png, image/webp, image/gif"
                            class="hidden"
                            @change="onAvatarSelected"
                        />
                    </div>

                    <!-- Profile Meta & Quick Photo Actions -->
                    <div class="flex-1 text-center sm:text-left space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                    {{ user.name }}
                                </h2>
                                <span class="inline-flex items-center gap-1.5 self-center sm:self-auto px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                                    <Sparkles class="w-3.5 h-3.5" />
                                    {{ user.role || 'Super Admin' }}
                                </span>
                            </div>

                            <!-- Avatar Quick Buttons -->
                            <div class="flex items-center justify-center sm:justify-start gap-2">
                                <button
                                    type="button"
                                    :disabled="isUploadingAvatar"
                                    @click="avatarFileInput?.click()"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition-colors cursor-pointer"
                                >
                                    <UploadCloud class="w-3.5 h-3.5" />
                                    Upload Photo
                                </button>
                                <button
                                    v-if="user.avatar"
                                    type="button"
                                    :disabled="isUploadingAvatar"
                                    @click="removeAvatar"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-900/40 transition-colors cursor-pointer"
                                    title="Restore default avatar"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                    Remove
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-y-2 gap-x-4 text-xs text-slate-500 dark:text-slate-400 pt-1">
                            <span class="flex items-center gap-1.5">
                                <Mail class="w-4 h-4 text-slate-400" />
                                {{ user.email }}
                            </span>
                            <span class="inline-block w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700 hidden sm:inline-block" />
                            <span class="flex items-center gap-1.5">
                                <Calendar class="w-4 h-4 text-slate-400" />
                                Joined {{ formatDate(user.created_at) }}
                            </span>
                            <span class="inline-block w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700 hidden sm:inline-block" />
                            <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-medium">
                                <ShieldCheck class="w-4 h-4" />
                                Two-Factor Secured
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-Column Cards: Profile Info & Password -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
                <!-- Personal Information -->
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="h-full"
                />

                <!-- Security & Password -->
                <UpdatePasswordForm class="h-full" />
            </div>

            <!-- Danger Zone: Account Deletion -->
            <DeleteUserForm />
        </div>
    </AdminLayout>
</template>
