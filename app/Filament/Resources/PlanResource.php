<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Billing & Monetization';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('stripe_price_id')
                    ->label('Stripe Price ID')
                    ->nullable(),

                Forms\Components\TextInput::make('price_monthly')
                    ->numeric()
                    ->prefix('$')
                    ->default(0.00)
                    ->required(),

                Forms\Components\TextInput::make('price_yearly')
                    ->numeric()
                    ->prefix('$')
                    ->default(0.00)
                    ->required(),

                Forms\Components\TextInput::make('max_users')
                    ->numeric()
                    ->default(5)
                    ->required(),

                Forms\Components\TextInput::make('max_storage_gb')
                    ->numeric()
                    ->default(10)
                    ->required(),

                Forms\Components\KeyValue::make('features')
                    ->label('Plan Features')
                    ->nullable(),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_monthly')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_users')
                    ->label('Max Users')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_storage_gb')
                    ->label('Storage (GB)')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
