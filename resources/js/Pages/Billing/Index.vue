<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    plans: Array,
    subscription: Object,
    paymentMethods: Array,
    setupIntent: Object,
});

const defaultPlans = [
    { id: 'free', name: 'Free', price_monthly: '0.00', max_users: 5, max_storage_gb: 5, features: ['Up to 5 Users', '5GB Storage', 'Community Support'] },
    { id: 'pro', name: 'Pro', price_monthly: '29.00', max_users: 25, max_storage_gb: 50, features: ['Up to 25 Users', '50GB Storage', 'Priority Email Support', 'Custom Subdomains'] },
    { id: 'enterprise', name: 'Enterprise', price_monthly: '99.00', max_users: 100, max_storage_gb: 500, features: ['Unlimited Users', '500GB Storage', 'Dedicated 24/7 Support', 'Custom Domain SSL'] },
];

const activePlans = computedPlans();

function computedPlans() {
    return (props.plans && props.plans.length > 0) ? props.plans : defaultPlans;
}

const checkoutForm = useForm({
    plan_id: '',
});

const subscribeToPlan = (planId) => {
    checkoutForm.plan_id = planId;
    checkoutForm.post('/billing/checkout');
};
</script>

<template>
    <Head title="Billing & Subscription Plans" />

    <TenantLayout>
        <div class="space-y-8 max-w-6xl">
            <div>
                <h1 class="text-2xl font-bold text-white">Billing & Subscription Plans</h1>
                <p class="text-sm text-slate-400">Choose the best plan for your organization scale.</p>
            </div>

            <!-- Current Plan Banner -->
            <div class="p-6 bg-slate-900 border border-indigo-500/30 rounded-2xl shadow-xl flex items-center justify-between">
                <div>
                    <span class="text-xs uppercase font-bold text-indigo-400">Current Plan Status</span>
                    <h2 class="text-xl font-extrabold text-white mt-1">
                        {{ subscription ? 'Active Subscription' : '14-Day Free Trial' }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ subscription ? `Subscription active. Renewal date: ${subscription.ends_at || 'Next Month'}` : 'Upgrade anytime to unlock higher team limits and storage.' }}
                    </p>
                </div>
                <div class="px-4 py-2 bg-indigo-600/20 border border-indigo-500/40 rounded-xl text-indigo-300 font-bold text-sm">
                    {{ subscription?.stripe_status?.toUpperCase() || 'TRIALING' }}
                </div>
            </div>

            <!-- Pricing Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="plan in activePlans"
                    :key="plan.id"
                    class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl flex flex-col justify-between hover:border-indigo-500/50 transition"
                >
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-bold text-white">{{ plan.name }}</h3>
                            <span v-if="subscription?.plan_id === plan.id" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                Current
                            </span>
                        </div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-white">${{ plan.price_monthly }}</span>
                            <span class="text-slate-400 text-sm">/ month</span>
                        </div>
                        <ul class="space-y-2 text-sm text-slate-300 border-t border-slate-800 pt-4">
                            <li v-for="(feat, idx) in (plan.features || [])" :key="idx" class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ feat }}
                            </li>
                        </ul>
                    </div>

                    <button
                        @click="subscribeToPlan(plan.id)"
                        :disabled="checkoutForm.processing || subscription?.plan_id === plan.id"
                        class="mt-6 w-full py-3 px-4 rounded-xl font-medium text-sm transition shadow-lg disabled:opacity-50"
                        :class="subscription?.plan_id === plan.id ? 'bg-slate-800 text-slate-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-500 text-white'"
                    >
                        {{ subscription?.plan_id === plan.id ? 'Active Plan' : 'Select Plan' }}
                    </button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
