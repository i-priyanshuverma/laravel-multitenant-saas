<script setup>
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    members: Array,
    invitations: Array,
});

const inviteForm = useForm({
    email: '',
    role: 'member',
});

const sendInvite = () => {
    inviteForm.post('/team/invitations', {
        onSuccess: () => inviteForm.reset('email'),
    });
};
</script>

<template>
    <Head title="Team Management" />

    <TenantLayout>
        <div class="space-y-8 max-w-5xl">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Team Roster & Invitations</h1>
                    <p class="text-sm text-slate-400">Invite new team members and assign role permissions.</p>
                </div>
            </div>

            <!-- Invite Form -->
            <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-xl space-y-4">
                <h2 class="text-lg font-semibold text-white">Invite Team Member</h2>
                <form @submit.prevent="sendInvite" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <input
                            v-model="inviteForm.email"
                            type="email"
                            required
                            placeholder="colleague@company.com"
                            class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div class="flex gap-2">
                        <select
                            v-model="inviteForm.role"
                            class="rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                            <option value="viewer">Viewer</option>
                        </select>
                        <button
                            type="submit"
                            :disabled="inviteForm.processing"
                            class="px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm transition disabled:opacity-50 whitespace-nowrap"
                        >
                            Send Invite
                        </button>
                    </div>
                </form>
            </div>

            <!-- Roster Table -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-slate-800">
                    <h2 class="text-lg font-semibold text-white">Active Members ({{ members ? members.length : 0 }})</h2>
                </div>
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950 text-xs uppercase text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5 font-medium">Name</th>
                            <th class="px-6 py-3.5 font-medium">Email</th>
                            <th class="px-6 py-3.5 font-medium">Role</th>
                            <th class="px-6 py-3.5 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <tr v-for="member in members" :key="member.id" class="hover:bg-slate-800/40">
                            <td class="px-6 py-4 font-medium text-white flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-indigo-400">
                                    {{ member.name ? member.name.substring(0, 1).toUpperCase() : 'U' }}
                                </div>
                                {{ member.name }}
                            </td>
                            <td class="px-6 py-4">{{ member.email }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                    {{ member.role }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link
                                    v-if="member.role !== 'owner'"
                                    :href="`/settings/team/${member.id}`"
                                    method="delete"
                                    as="button"
                                    class="text-rose-400 hover:text-rose-300 text-xs font-medium"
                                >
                                    Remove
                                </Link>
                                <span v-else class="text-slate-500 text-xs font-medium">Owner</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </TenantLayout>
</template>
