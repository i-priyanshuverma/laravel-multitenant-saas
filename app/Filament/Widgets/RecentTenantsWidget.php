<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTenantsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Tenant::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tenant Name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Subdomain'),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'canceled',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Onboarded')
                    ->dateTime(),
            ]);
    }
}
