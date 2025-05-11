<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign In to Tenant Workspace" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-600 text-white font-black text-2xl shadow-lg shadow-indigo-500/30">
                S
            </div>
            <h2 class="mt-4 text-3xl font-extrabold text-white tracking-tight">
                Sign In to Workspace
            </h2>
            <p class="mt-2 text-sm text-slate-400">
                Enter your credentials to access your tenant dashboard.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div v-if="status" class="mb-4 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm">
                {{ status }}
            </div>

            <div class="bg-slate-900 border border-slate-800 py-8 px-6 shadow-2xl rounded-2xl sm:px-10">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Email Address</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="name@company.com"
                            class="mt-1 block w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                        <div v-if="form.errors.email" class="mt-1 text-xs text-rose-400">{{ form.errors.email }}</div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-slate-300">Password</label>
                            <Link href="/forgot-password" class="text-xs text-indigo-400 hover:text-indigo-300">
                                Forgot password?
                            </Link>
                        </div>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="mt-1 block w-full rounded-lg bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                        <div v-if="form.errors.password" class="mt-1 text-xs text-rose-400">{{ form.errors.password }}</div>
                    </div>

                    <div class="flex items-center">
                        <input
                            v-model="form.remember"
                            id="remember_me"
                            type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-700 rounded bg-slate-950"
                        />
                        <label for="remember_me" class="ml-2 block text-sm text-slate-300">
                            Remember me on this browser
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition disabled:opacity-50"
                    >
                        <span v-if="form.processing">Signing in...</span>
                        <span v-else>Sign In</span>
                    </button>
                </form>

                <div class="mt-6 text-center text-sm">
                    <span class="text-slate-400">Don't have a workspace yet?</span>
                    <Link href="/register" class="ml-1 font-medium text-indigo-400 hover:text-indigo-300">
                        Create one
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
