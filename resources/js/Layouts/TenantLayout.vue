<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const tenant = ref(page.props.tenant || { name: 'Workspace', slug: 'app' });
const user = ref(page.props.auth?.user || { name: 'User', email: '' });

const isUserMenuOpen = ref(false);
const isMobileSidebarOpen = ref(false);

const navItems = [
    { name: 'Dashboard', href: '/dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Team Roster', href: '/settings/team', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { name: 'Billing & Plans', href: '/billing', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z' },
    { name: 'Settings', href: '/settings/profile', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
];
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex font-sans antialiased selection:bg-indigo-500 selection:text-white">
        <!-- Sidebar Navigation -->
        <aside class="hidden md:flex flex-col w-64 border-r border-slate-800/80 bg-slate-900/80 backdrop-blur-2xl">
            <div class="h-16 flex items-center px-6 border-b border-slate-800/80 justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center font-extrabold text-white shadow-lg shadow-indigo-500/25">
                        {{ tenant.name ? tenant.name.substring(0, 1).toUpperCase() : 'S' }}
                    </div>
                    <div class="truncate">
                        <h1 class="text-sm font-bold text-white truncate leading-tight">{{ tenant.name }}</h1>
                        <p class="text-xs text-indigo-400 font-mono truncate">{{ tenant.slug }}.saas.com</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-3 py-6 space-y-1">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition duration-200"
                    :class="page.url.startsWith(item.href) ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <div class="p-4 border-t border-slate-800/80">
                <div class="p-3.5 bg-gradient-to-b from-slate-900 to-slate-950 rounded-xl border border-slate-800 text-xs space-y-2">
                    <div class="flex justify-between items-center text-slate-400">
                        <span>Current Tier</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                            {{ tenant.plan?.name || 'Pro Trial' }}
                        </span>
                    </div>
                    <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-indigo-500 h-full w-2/5"></div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <header class="h-16 border-b border-slate-800/80 bg-slate-900/40 backdrop-blur-xl px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Tenant Workspace Active
                    </span>
                </div>

                <div class="relative">
                    <button
                        @click="isUserMenuOpen = !isUserMenuOpen"
                        class="flex items-center gap-3 text-sm focus:outline-none bg-slate-900/80 px-3 py-1.5 rounded-xl border border-slate-800 hover:border-slate-700 transition"
                    >
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-500 text-white flex items-center justify-center font-bold text-xs shadow-md">
                            {{ user.name ? user.name.substring(0, 1).toUpperCase() : 'U' }}
                        </div>
                        <span class="hidden sm:inline font-medium text-slate-200">{{ user.name }}</span>
                    </button>

                    <div
                        v-if="isUserMenuOpen"
                        class="absolute right-0 mt-2 w-52 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl py-2 z-50 text-sm animate-in fade-in zoom-in-95"
                    >
                        <div class="px-4 py-2 border-b border-slate-800">
                            <p class="font-bold text-white truncate">{{ user.name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ user.email }}</p>
                        </div>
                        <Link href="/settings/profile" class="block px-4 py-2 text-slate-300 hover:bg-slate-800/60 transition">Profile & Account</Link>
                        <Link href="/logout" method="post" as="button" class="w-full text-left px-4 py-2 text-rose-400 hover:bg-rose-500/10 transition">Sign Out</Link>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
