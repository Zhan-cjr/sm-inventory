<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->required()
                    ->default(fn() => auth()->user()->branch_id)
                    ->disabled(fn ($record) => $record !== null || auth()->user()->branch_id !== null)
                    ->dehydrated(),
                \Filament\Forms\Components\Select::make('racks')
                    ->label('No Rak')
                    ->relationship('racks', 'rack_code', function ($query, $record) {
                        $branchId = $record?->branch_id ?? auth()->user()->branch_id;
                        if ($branchId) {
                            return $query->where('branch_id', $branchId);
                        }
                        return $query;
                    })
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->rack_code} - {$record->rack_name}")
                    ->multiple()
                    ->searchable()
                    ->preload(),
                \Filament\Schemas\Components\Section::make('Harga Bertingkat & Margin')
                    ->columns(3)
                    ->schema([
                        \Filament\Schemas\Components\Group::make([
                            \Filament\Forms\Components\TextInput::make('cost_price')
                                ->label('Harga Beli Cabang')
                                ->helperText('Kosongkan untuk menggunakan harga default produk')
                                ->numeric()
                                ->prefix('Rp')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) $state;
                                    $set('cost_price_tax', round($cost * 1.11, 2));
                                    foreach([1, 2, 3] as $i) {
                                        $margin = (float) $get("margin_gol_{$i}");
                                        if ($margin > 0) {
                                            $set("harga_jual_{$i}", round($cost * (1 + ($margin / 100)), 2));
                                        }
                                    }
                                    $set('selling_price', $get('harga_jual_1'));
                                }),
                            \Filament\Forms\Components\TextInput::make('cost_price_tax')
                                ->label('Harga Beli + PPN')
                                ->numeric()
                                ->prefix('Rp'),
                        ])->columnSpanFull()->columns(2),
                        \Filament\Schemas\Components\Group::make([
                            \Filament\Forms\Components\TextInput::make('qty_min_gol_1')->label('Min Qty Gol 1')->numeric()->default(1)->required(),
                            \Filament\Forms\Components\TextInput::make('margin_gol_1')->label('Margin Gol 1 (%)')->numeric()->default(0)->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) $get('cost_price_tax');
                                    $margin = (float) $state;
                                    $price = round($cost * (1 + ($margin / 100)), 2);
                                    $set('harga_jual_1', $price);
                                    $set('selling_price', $price);
                                }),
                            \Filament\Forms\Components\TextInput::make('harga_jual_1')->label('Harga Jual Gol 1')->numeric()->default(0)->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) $get('cost_price_tax');
                                    $price = (float) $state;
                                    if ($cost > 0) {
                                        $set('margin_gol_1', round((($price - $cost) / $cost) * 100, 2));
                                    }
                                    $set('selling_price', $price);
                                }),
                        ])->columnSpan(1),
                        \Filament\Schemas\Components\Group::make([
                            \Filament\Forms\Components\TextInput::make('qty_min_gol_2')->label('Min Qty Gol 2')->numeric(),
                            \Filament\Forms\Components\TextInput::make('margin_gol_2')->label('Margin Gol 2 (%)')->numeric()->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) $get('cost_price_tax');
                                    $set('harga_jual_2', round($cost * (1 + ((float)$state / 100)), 2));
                                }),
                            \Filament\Forms\Components\TextInput::make('harga_jual_2')->label('Harga Jual Gol 2')->numeric()->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) $get('cost_price_tax');
                                    if ($cost > 0) {
                                        $set('margin_gol_2', round((((float)$state - $cost) / $cost) * 100, 2));
                                    }
                                }),
                        ])->columnSpan(1),
                        \Filament\Schemas\Components\Group::make([
                            \Filament\Forms\Components\TextInput::make('qty_min_gol_3')->label('Min Qty Gol 3')->numeric(),
                            \Filament\Forms\Components\TextInput::make('margin_gol_3')->label('Margin Gol 3 (%)')->numeric()->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) $get('cost_price_tax');
                                    $set('harga_jual_3', round($cost * (1 + ((float)$state / 100)), 2));
                                }),
                            \Filament\Forms\Components\TextInput::make('harga_jual_3')->label('Harga Jual Gol 3')->numeric()->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) $get('cost_price_tax');
                                    if ($cost > 0) {
                                        $set('margin_gol_3', round((((float)$state - $cost) / $cost) * 100, 2));
                                    }
                                }),
                        ])->columnSpan(1),
                        \Filament\Forms\Components\Hidden::make('selling_price')->default(0)
                    ]),
                TextInput::make('quantity_on_hand')
                    ->label('Stok Saat Ini')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('min_qty')
                    ->label('Min. Stok')
                    ->required()
                    ->numeric()
                    ->default(10),
                TextInput::make('max_qty')
                    ->label('Max. Stok')
                    ->required()
                    ->numeric()
                    ->default(500),
                TextInput::make('lead_time')
                    ->label('Lead Time (Hari)')
                    ->helperText('Waktu pengiriman dari supplier')
                    ->numeric()
                    ->default(3)
                    ->suffix('Hari'),
                TextInput::make('safety_stock')
                    ->label('Safety Stock')
                    ->helperText('Stok cadangan minimum')
                    ->numeric()
                    ->default(0),
                TextInput::make('desired_inventory_days')
                    ->label('Target Inventori (Hari)')
                    ->helperText('Berapa hari stok yang ingin dipertahankan')
                    ->numeric()
                    ->default(14)
                    ->suffix('Hari'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('branch.name')
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('racks.rack_code')
                    ->label('No Rak')
                    ->badge()
                    ->separator(','),
                TextColumn::make('cost_price')
                    ->label('Harga Beli (Cabang)')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder(fn ($record) => $record && $record->product ? 'Default: Rp ' . number_format($record->product->cost_price, 0, ',', '.') : 'Rp 0'),
                TextColumn::make('selling_price')
                    ->label('Harga Jual (Cabang)')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder(fn ($record) => $record && $record->product ? 'Default: Rp ' . number_format($record->product->selling_price, 0, ',', '.') : 'Rp 0'),
                TextColumn::make('quantity_on_hand')
                    ->label('Stok')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('min_qty')
                    ->label('Min')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('max_qty')
                    ->label('Max')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lead_time')
                    ->label('Lead Time')
                    ->suffix(' Hr')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('desired_inventory_days')
                    ->label('Target Days')
                    ->suffix(' Hr')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Update Terakhir')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Stok Cabang'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Hapus dari Cabang'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
