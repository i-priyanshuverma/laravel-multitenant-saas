<?php

namespace App\Actions;

use App\Mail\TenantWelcomeMail;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OnboardTenantAction
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Execute the tenant onboarding and owner provisioning workflow.
     *
     * @param  array{company_name: string, company_slug: string, name: string, email: string, password: string}  $data
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $data['company_name'],
                'slug' => strtolower($data['company_slug']),
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14),
            ]);

            // 2. Create primary domain for tenant
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $domain = strtolower($data['company_slug']).'.'.$appHost;

            Domain::create([
                'tenant_id' => $tenant->id,
                'domain' => $domain,
                'is_primary' => true,
            ]);

            // 3. Set Tenant Context to allow user creation under this tenant
            $this->tenantManager->setTenant($tenant);

            // 4. Create Owner User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'owner',
                'is_super_admin' => false,
            ]);

            // 5. Assign owner_id to tenant
            $tenant->update(['owner_id' => $user->id]);

            Log::info('Tenant workspace onboarded', [
                'tenant_id' => $tenant->id,
                'slug' => $tenant->slug,
                'owner_id' => $user->id,
                'email' => $user->email,
            ]);

            // 6. Send welcome email notification
            try {
                Mail::to($user)->queue(new TenantWelcomeMail($tenant, $user));
            } catch (\Throwable $e) {
                Log::warning('Failed to queue tenant welcome email: '.$e->getMessage(), [
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                ]);
            }

            return $user;
        });
    }
}
