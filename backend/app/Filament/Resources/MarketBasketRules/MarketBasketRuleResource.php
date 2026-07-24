<?php

namespace App\Filament\Resources\MarketBasketRules;

use App\Filament\Resources\MarketBasketRules\Pages\ManageMarketBasketRules;
use App\Models\MarketBasketRule;
use App\Models\Promotion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
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
                    ->sortable()
                    ->wrap(),
                TextColumn::make('consequent_name')
                    ->label('Barang Pendamping')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('support')
                    ->label('Frekuensi (Support)')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('confidence')
                    ->label('Kepastian (Confidence)')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 1) . '%')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                TextColumn::make('lift')
                    ->label('Kekuatan Hubungan (Lift)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state >= 1.5 ? 'success' : ($state >= 1.0 ? 'warning' : 'gray')),
                TextColumn::make('created_at')
                    ->label('Dianalisis Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('confidence', 'desc')
            ->actions([
                Action::make('create_bundling_promo')
                    ->label('Buat Promo Bundling')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->action(function (MarketBasketRule $record) {
                        $orgId = auth()->user()->organization_id ?? \App\Models\Organization::first()?->id;
                        
                        $promo = Promotion::create([
                            'organization_id' => $orgId,
                            'name' => 'Paket Bundling: ' . $record->antecedent_name . ' + ' . $record->consequent_name,
                            'promo_type' => 'BUNDLING',
                            'discount_value' => 10,
                            'applicable_to' => 'PRODUCT',
                            'target_ids' => [$record->antecedent_id, $record->consequent_id],
                            'valid_from' => now(),
                            'valid_until' => now()->addDays(30),
                            'is_active' => true,
                            'promo_config' => [
                                'buy_product_id' => $record->antecedent_id,
                                'get_product_id' => $record->consequent_id,
                                'buy_qty' => 1,
                                'get_qty' => 1,
                            ],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Draft Promosi Bundling berhasil dibuat!')
                            ->success()
                            ->send();

                        return redirect()->to(route('filament.admin.resources.promotions.edit', $promo));
                    }),
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
