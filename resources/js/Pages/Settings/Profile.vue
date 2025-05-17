<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    user: Object,
});

const profileForm = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    profileForm.patch('/settings/profile');
};

const updatePassword = () => {
    passwordForm.put('/settings/password', {
        onSuccess: () => passwordForm.reset(),
    });
};
</script>

<template>
    <Head title="Profile Settings" />

    <TenantLayout>
        <div class="max-w-4xl space-y-8">
            <div>
                <h1 class="text-2xl font-bold text-white">Account & Profile Settings</h1>
                <p class="text-sm text-slate-400">Update your name, email, and password credentials.</p>
            </div>

            <!-- Profile Info Form -->
            <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-xl space-y-6">
                <h2 class="text-lg font-semibold text-white border-b border-slate-800 pb-4">Personal Information</h2>
                <form @submit.prevent="updateProfile" class="space-y-4 max-w-lg">
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Name</label>
                        <input
                            v-model="profileForm.name"
                            type="text"
                            class="mt-1 block w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Email Address</label>
                        <input
                            v-model="profileForm.email"
                            type="email"
                            class="mt-1 block w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="profileForm.processing"
                        class="px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm shadow-md transition disabled:opacity-50"
                    >
                        Save Changes
                    </button>
                </form>
            </div>

            <!-- Password Form -->
            <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-xl space-y-6">
                <h2 class="text-lg font-semibold text-white border-b border-slate-800 pb-4">Update Password</h2>
                <form @submit.prevent="updatePassword" class="space-y-4 max-w-lg">
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Current Password</label>
                        <input
                            v-model="passwordForm.current_password"
                            type="password"
                            class="mt-1 block w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300">New Password</label>
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            class="mt-1 block w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Confirm New Password</label>
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            class="mt-1 block w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="passwordForm.processing"
                        class="px-5 py-2.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-medium text-sm border border-slate-700 transition disabled:opacity-50"
                    >
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
