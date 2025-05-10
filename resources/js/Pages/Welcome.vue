<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Multi-tenant SaaS Engine" />
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl w-full text-center space-y-8">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-indigo-500/30 bg-indigo-500/10 text-indigo-400 text-sm font-medium">
                <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                Laravel 11 • Vue 3 • Inertia.js • FilamentPHP v3
            </div>

            <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white">
                Multi-Tenant Enterprise <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">SaaS Engine</span>
            </h1>

            <p class="text-lg text-slate-400 max-w-xl mx-auto">
                Isolated database schemas, custom domains, Stripe billing engine, FilamentPHP super-admin panel, and Vue 3 frontend.
            </p>

            <div class="flex justify-center gap-4 pt-4">
                <a href="/login" class="px-6 py-3 rounded-lg font-medium bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition">
                    Tenant Login
                </a>
                <a href="/register" class="px-6 py-3 rounded-lg font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition">
                    Onboard New Tenant
                </a>
            </div>
        </div>
    </div>
</template>
