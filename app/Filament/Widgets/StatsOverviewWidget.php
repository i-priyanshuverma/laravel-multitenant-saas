<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Monthly Recurring Revenue calculation
        $mrr = (float) Subscription::where('stripe_status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price_monthly');

        $activeTenants = Tenant::where('status', 'active')->count();
        $canceledSubscriptions = Subscription::where('stripe_status', 'canceled')->count();
        $totalUsers = User::count();

        return [
            Stat::make('Monthly Recurring Revenue (MRR)', '$' . number_format($mrr, 2))
                ->description('Active recurring subscriptions')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Active Tenants', (string) $activeTenants)
                ->description('Total active tenant organizations')
                ->color('primary'),

            Stat::make('Churned Subscriptions', (string) $canceledSubscriptions)
                ->description('Canceled tenant subscriptions')
                ->color('danger'),

            Stat::make('Total Platform Users', (string) $totalUsers)
                ->description('Across all tenant organizations')
                ->color('info'),
        ];
    }
}
