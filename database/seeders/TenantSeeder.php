<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Default Subscription Plans
        $freePlan = Plan::create([
            'name' => 'Free',
            'slug' => 'free',
            'stripe_price_id' => 'price_free_tier',
            'price_monthly' => 0.00,
            'price_yearly' => 0.00,
            'max_users' => 5,
            'max_storage_gb' => 5,
            'features' => ['5 Team Members', '5GB Storage', 'Community Support'],
            'is_active' => true,
        ]);

        $proPlan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'stripe_price_id' => 'price_pro_tier',
            'price_monthly' => 29.00,
            'price_yearly' => 290.00,
            'max_users' => 25,
            'max_storage_gb' => 50,
            'features' => ['25 Team Members', '50GB Storage', 'Priority Support', 'Custom Subdomains'],
            'is_active' => true,
        ]);

        $enterprisePlan = Plan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'stripe_price_id' => 'price_enterprise_tier',
            'price_monthly' => 99.00,
            'price_yearly' => 990.00,
            'max_users' => 100,
            'max_storage_gb' => 500,
            'features' => ['Unlimited Team Members', '500GB Storage', 'Dedicated Support', 'Custom Domain SSL'],
            'is_active' => true,
        ]);

        // 2. Create Super Admin User (No tenant context)
        User::create([
            'name' => 'Platform Super Admin',
            'email' => 'admin@saas.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_super_admin' => true,
        ]);

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        // 3. Create Sample Tenant 1: Acme Corp
        $acme = Tenant::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme',
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'trial_ends_at' => now()->addDays(14),
        ]);

        Domain::create([
            'tenant_id' => $acme->id,
            'domain' => 'acme.localhost',
            'is_primary' => true,
        ]);

        $tenantManager->setTenant($acme);
        $acmeOwner = User::create([
            'tenant_id' => $acme->id,
            'name' => 'Wile E. Coyote',
            'email' => 'owner@acme.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);
        $acme->update(['owner_id' => $acmeOwner->id]);

        Subscription::create([
            'tenant_id' => $acme->id,
            'plan_id' => $proPlan->id,
            'type' => 'main',
            'stripe_id' => 'sub_seed_acme',
            'stripe_status' => 'active',
            'quantity' => 1,
        ]);

        // 4. Create Sample Tenant 2: Stark Tech
        $stark = Tenant::create([
            'name' => 'Stark Industries',
            'slug' => 'stark',
            'plan_id' => $enterprisePlan->id,
            'status' => 'active',
        ]);

        Domain::create([
            'tenant_id' => $stark->id,
            'domain' => 'stark.localhost',
            'is_primary' => true,
        ]);

        $tenantManager->setTenant($stark);
        $starkOwner = User::create([
            'tenant_id' => $stark->id,
            'name' => 'Tony Stark',
            'email' => 'tony@stark.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);
        $stark->update(['owner_id' => $starkOwner->id]);

        Subscription::create([
            'tenant_id' => $stark->id,
            'plan_id' => $enterprisePlan->id,
            'type' => 'main',
            'stripe_id' => 'sub_seed_stark',
            'stripe_status' => 'active',
            'quantity' => 1,
        ]);

        $tenantManager->forgetTenant();
    }
}
