<?php

namespace App\Filament\Resources\MarketBasketRules;

use App\Filament\Resources\MarketBasketRules\Pages\ManageMarketBasketRules;
use App\Models\MarketBasketRule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MarketBasketRuleResource extends Resource
{
    protected static ?string $model = MarketBasketRule::class;

    protected static ?string $modelLabel = 'Rekomendasi Bundling (MBA)';
    protected static ?string $pluralModelLabel = 'Rekomendasi Bundling (MBA)';
    protected static \UnitEnum|string|null $navigationGroup = 'ANALISA AI';
    protected static ?int $navigationSort = 100;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $recordTitleAttribute = 'antecedent_name';

    public static function canViewAny(): bool
    {
        return auth()->user()->can('ViewAny:MarketBasketRule');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('antecedent_name')
            ->columns([
                TextColumn::make('antecedent_name')
                    ->label('Barang Utama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('consequent_name')
                    ->label('Barang Pendamping')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('support')
                    ->label('Frekuensi (Support)')
                    ->numeric(
                        decimalPlaces: 4,
                    )
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('confidence')
                    ->label('Kepastian (Confidence)')
                    ->numeric(
                        decimalPlaces: 2,
                    )
                    ->formatStateUsing(fn ($state) => ($state * 100) . '%')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                TextColumn::make('lift')
                    ->label('Kekuatan Hubungan (Lift)')
                    ->numeric(
                        decimalPlaces: 2,
                    )
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                TextColumn::make('created_at')
                    ->label('Dianalisis Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('confidence', 'desc')
            ->filters([
                //
            ])
            ->actions([
                // No individual actions
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMarketBasketRules::route('/'),
        ];
    }
}
