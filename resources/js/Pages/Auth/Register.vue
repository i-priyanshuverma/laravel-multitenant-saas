<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const form = useForm({
    company_name: '',
    company_slug: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const autoSlug = () => {
    if (!form.company_slug || form.company_slug.trim() === '') {
        form.company_slug = form.company_name
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
};

const hostDomain = computed(() => {
    if (typeof window !== 'undefined') {
        return '.' + window.location.hostname;
    }
    return '.saas.com';
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Create Tenant Account" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-600 text-white font-black text-2xl shadow-lg shadow-indigo-500/30">
                S
            </div>
            <h2 class="mt-4 text-3xl font-extrabold text-white tracking-tight">
                Create Your Tenant Workspace
            </h2>
            <p class="mt-2 text-sm text-slate-400">
                Start your 14-day free trial. No credit card required.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-slate-900 border border-slate-800 py-8 px-6 shadow-2xl rounded-2xl sm:px-10">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Company Name</label>
                        <input
                            v-model="form.company_name"
                            @blur="autoSlug"
                            type="text"
                            required
                            placeholder="Acme Corporation"
                            class="mt-1 block w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                        <div v-if="form.errors.company_name" class="mt-1 text-xs text-rose-400">{{ form.errors.company_name }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300">Workspace Subdomain</label>
                        <div class="mt-1 flex rounded-lg shadow-sm">
                            <input
                                v-model="form.company_slug"
                                type="text"
                                required
                                placeholder="acme"
                                class="block w-full rounded-l-lg bg-slate-950 border border-r-0 border-slate-700 px-3.5 py-2.5 text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            />
                            <span class="inline-flex items-center rounded-r-lg border border-l-0 border-slate-700 bg-slate-800 px-3.5 text-sm text-slate-400">
                                {{ hostDomain }}
                            </span>
                        </div>
                        <div v-if="form.errors.company_slug" class="mt-1 text-xs text-rose-400">{{ form.errors.company_slug }}</div>
                    </div>

                    <div class="border-t border-slate-800 pt-5">
                        <label class="block text-sm font-medium text-slate-300">Your Full Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Jane Doe"
                            class="mt-1 block w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-xs text-rose-400">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300">Work Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="jane@acme.com"
                            class="mt-1 block w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                        <div v-if="form.errors.email" class="mt-1 text-xs text-rose-400">{{ form.errors.email }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300">Password</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="mt-1 block w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                        <div v-if="form.errors.password" class="mt-1 text-xs text-rose-400">{{ form.errors.password }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300">Confirm Password</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            class="mt-1 block w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition disabled:opacity-50"
                    >
                        <span v-if="form.processing">Creating Account...</span>
                        <span v-else>Start Free Trial</span>
                    </button>
                </form>

                <div class="mt-6 text-center text-sm">
                    <span class="text-slate-400">Already have a workspace?</span>
                    <Link href="/login" class="ml-1 font-medium text-indigo-400 hover:text-indigo-300">
                        Log in
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
