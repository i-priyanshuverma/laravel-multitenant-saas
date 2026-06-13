<script setup>
import { Head, usePage, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const page = usePage();
const tenant = page.props.tenant || { name: 'Acme Corp', slug: 'acme' };
const user = page.props.auth?.user || {};
</script>

<template>
    <Head title="Workspace Dashboard" />

    <TenantLayout>
        <div class="space-y-6">
            <!-- Welcome Hero -->
            <div class="p-6 rounded-2xl bg-gradient-to-r from-indigo-900/50 via-slate-900 to-slate-900 border border-indigo-500/20 shadow-xl">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Welcome back, {{ user.name || 'Workspace Admin' }}! 👋
                </h1>
                <p class="mt-2 text-slate-300 max-w-2xl text-sm leading-relaxed">
                    You are currently managing <span class="text-indigo-400 font-semibold">{{ tenant.name }}</span> (Subdomain: <code class="bg-slate-950 px-2 py-0.5 rounded text-indigo-300">{{ tenant.slug }}</code>). Manage team members, upgrade subscription plans, or adjust workspace settings below.
                </p>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-lg space-y-2">
                    <div class="flex justify-between items-center text-slate-400 text-sm">
                        <span>Active Team Members</span>
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                    <p class="text-3xl font-black text-white">3 / 5</p>
                    <p class="text-xs text-emerald-400">+1 invited this week</p>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-lg space-y-2">
                    <div class="flex justify-between items-center text-slate-400 text-sm">
                        <span>Subscription Status</span>
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </div>
                    <p class="text-3xl font-black text-emerald-400">Active Trial</p>
                    <p class="text-xs text-slate-400">14 days remaining in Pro trial</p>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-lg space-y-2">
                    <div class="flex justify-between items-center text-slate-400 text-sm">
                        <span>Cloud Storage Used</span>
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                    </div>
                    <p class="text-3xl font-black text-white">1.2 GB / 10 GB</p>
                    <p class="text-xs text-slate-400">12% storage utilized</p>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
