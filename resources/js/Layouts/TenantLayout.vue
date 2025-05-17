<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const tenant = ref(page.props.tenant || { name: 'Workspace', slug: 'app' });
const user = ref(page.props.auth?.user || { name: 'User', email: '' });
const flash = ref(page.props.flash || {});

const isUserMenuOpen = ref(false);
const isMobileSidebarOpen = ref(false);

const navItems = [
    { name: 'Dashboard', href: '/dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Team Management', href: '/settings/team', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { name: 'Billing & Plans', href: '/billing', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z' },
    { name: 'Settings', href: '/settings/profile', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
];
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex">
        <!-- Sidebar Navigation -->
        <aside class="hidden md:flex flex-col w-64 border-r border-slate-800 bg-slate-900/60 backdrop-blur-xl">
            <div class="h-16 flex items-center px-6 border-b border-slate-800 justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white shadow-md shadow-indigo-500/20">
                        {{ tenant.name ? tenant.name.substring(0, 1).toUpperCase() : 'T' }}
                    </div>
                    <div class="truncate">
                        <h1 class="text-sm font-bold text-white truncate">{{ tenant.name }}</h1>
                        <p class="text-xs text-slate-400 truncate">{{ tenant.slug }}.saas.com</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-1.5">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition text-slate-300 hover:bg-slate-800/80 hover:text-white"
                >
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <div class="p-4 border-t border-slate-800">
                <div class="p-3 bg-slate-800/50 rounded-xl border border-slate-700/50 text-xs space-y-1">
                    <div class="flex justify-between text-slate-400">
                        <span>Current Plan</span>
                        <span class="text-indigo-400 font-semibold uppercase">{{ tenant.plan?.name || 'Free Trial' }}</span>
                    </div>
                    <div class="w-full bg-slate-700 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-indigo-500 h-full w-1/3"></div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <header class="h-16 border-b border-slate-800 bg-slate-900/40 px-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-semibold text-white">Workspace Overview</h2>
                </div>

                <div class="relative">
                    <button
                        @click="isUserMenuOpen = !isUserMenuOpen"
                        class="flex items-center gap-3 text-sm focus:outline-none"
                    >
                        <div class="w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center font-bold">
                            {{ user.name ? user.name.substring(0, 1).toUpperCase() : 'U' }}
                        </div>
                        <span class="hidden sm:inline font-medium text-slate-200">{{ user.name }}</span>
                    </button>

                    <div
                        v-if="isUserMenuOpen"
                        class="absolute right-0 mt-2 w-48 bg-slate-900 border border-slate-800 rounded-xl shadow-2xl py-1 z-50 text-sm"
                    >
                        <div class="px-4 py-2 border-b border-slate-800">
                            <p class="font-medium text-white truncate">{{ user.name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ user.email }}</p>
                        </div>
                        <Link href="/settings/profile" class="block px-4 py-2 text-slate-300 hover:bg-slate-800">Profile Settings</Link>
                        <Link href="/logout" method="post" as="button" class="w-full text-left px-4 py-2 text-rose-400 hover:bg-slate-800">Sign Out</Link>
                    </div>
                </div>
            </header>

            <!-- Flash notifications -->
            <div v-if="page.props.flash?.success" class="mx-6 mt-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center justify-between">
                <span>{{ page.props.flash.success }}</span>
            </div>

            <!-- Page Content -->
            <main class="flex-1 p-6 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
