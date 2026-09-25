<?php

namespace App\Providers;

use App\Models\TeamInvitation;
use App\Models\User;
use App\Policies\TeamInvitationPolicy;
use App\Policies\UserPolicy;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantManager::class, function ($app) {
            return new TenantManager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(TeamInvitation::class, TeamInvitationPolicy::class);
    }
}
