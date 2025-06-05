<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TenantsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Tenant Growth Trend';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $data[] = Tenant::whereYear('created_at', '<=', $month->year)
                ->whereMonth('created_at', '<=', $month->month)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Tenants',
                    'data' => $data,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
