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
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Schemas\Components\Group::make([
                            \Filament\Forms\Components\TextInput::make('cost_price')
                                ->label('Harga Beli Cabang')
                                ->helperText('Kosongkan untuk menggunakan harga default produk')
                                ->rupiah()
                                ->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => str_replace('.', ',', (string) $livewire->ownerRecord?->cost_price))
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    $taxed_cost = round($cost * 1.11, 2);
                                    $set('cost_price_tax', str_replace('.', ',', (string)$taxed_cost));
                                    foreach([1, 2, 3] as $i) {
                                        $harga_jual_state = $get("harga_jual_{$i}");
                                        $price = (float) str_replace(',', '.', str_replace('.', '', $harga_jual_state));
                                        if ($taxed_cost > 0 && $price > 0) {
                                            $new_margin = round((($price - $taxed_cost) / $taxed_cost) * 100, 2);
                                        }
                                    }
                                    $price1 = (float) str_replace(',', '.', str_replace('.', '', (string) $get('harga_jual_1')));
                                    $set('selling_price', $price1);
                                }),
                            \Filament\Forms\Components\TextInput::make('cost_price_tax')
                                ->label('Harga Beli + PPN')
                                ->rupiah()
                                ->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => str_replace('.', ',', (string) $livewire->ownerRecord?->cost_price_tax)),
                        ])->columnSpanFull()->columns(2),
                        \Filament\Schemas\Components\Group::make([
                            \Filament\Forms\Components\TextInput::make('qty_min_gol_1')->label('Min Qty Gol 1')->numeric()->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => $livewire->ownerRecord?->qty_min_gol_1 ?? 1)->required(),
                            \Filament\Forms\Components\TextInput::make('margin_gol_1')->label('Margin Gol 1 (%)')->numeric()->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => str_replace('.', ',', (string) $livewire->ownerRecord?->margin_gol_1))->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $margin = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    $price = round($cost * (1 + ($margin / 100)), 2);
                                    $set('harga_jual_1', str_replace('.', ',', (string)$price));
                                    $set('selling_price', $price);
                                }),
                            \Filament\Forms\Components\TextInput::make('harga_jual_1')->label('Harga Jual Gol 1')->rupiah()->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => str_replace('.', ',', (string) $livewire->ownerRecord?->harga_jual_1))->live(onBlur: true)
                                ->rules([
                                    fn (\Filament\Schemas\Components\Utilities\Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if (! $value) return;
                                        $cost = (float) str_replace(',', '.', str_replace('.', '', (string) $get('cost_price_tax')));
                                        $price = (float) str_replace(',', '.', str_replace('.', '', (string) $value));
                                        if ($cost > 0 && $price < $cost) {
                                            $fail('Harga Jual tidak boleh lebih kecil dari Harga Beli + PPN.');
                                        }
                                    }
                                ])
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $hpp = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $harga = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    if ($hpp > 0) {
                                        if ($harga > 0) {
                                            $new_margin = round((($harga - $hpp) / $hpp) * 100, 2);
                                            $set("margin_gol_1", str_replace('.', ',', (string)$new_margin));
                                        } else {
                                            $set("margin_gol_1", "0");
                                        }
                                    }
                                    $set('selling_price', $harga);
                                }),
                        ])->columns(3)->columnSpanFull(),
                        \Filament\Schemas\Components\Group::make([
                            \Filament\Forms\Components\TextInput::make('qty_min_gol_2')->label('Min Qty Gol 2')->numeric()->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => $livewire->ownerRecord?->qty_min_gol_2),
                            \Filament\Forms\Components\TextInput::make('margin_gol_2')->label('Margin Gol 2 (%)')->numeric()->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => str_replace('.', ',', (string) $livewire->ownerRecord?->margin_gol_2))->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $margin = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    $price = round($cost * (1 + ($margin / 100)), 2);
                                    $set('harga_jual_2', str_replace('.', ',', (string)$price));
                                }),
                            \Filament\Forms\Components\TextInput::make('harga_jual_2')->label('Harga Jual Gol 2')->rupiah()->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => str_replace('.', ',', (string) $livewire->ownerRecord?->harga_jual_2))->live(onBlur: true)
                                ->rules([
                                    fn (\Filament\Schemas\Components\Utilities\Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if (! $value) return;
                                        $cost = (float) str_replace(',', '.', str_replace('.', '', (string) $get('cost_price_tax')));
                                        $price = (float) str_replace(',', '.', str_replace('.', '', (string) $value));
                                        if ($cost > 0 && $price < $cost) {
                                            $fail('Harga Jual tidak boleh lebih kecil dari Harga Beli + PPN.');
                                        }
                                    }
                                ])
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $hpp = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $harga = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    if ($hpp > 0) {
                                        if ($harga > 0) {
                                            $margin = round((($harga - $hpp) / $hpp) * 100, 2);
                                            $set('margin_gol_2', str_replace('.', ',', (string)$margin));
                                        } else {
                                            $set('margin_gol_2', '0');
                                        }
                                    }
                                }),
                        ])->columns(3)->columnSpanFull(),
                        \Filament\Schemas\Components\Group::make([
                            \Filament\Forms\Components\TextInput::make('qty_min_gol_3')->label('Min Qty Gol 3')->numeric()->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => $livewire->ownerRecord?->qty_min_gol_3),
                            \Filament\Forms\Components\TextInput::make('margin_gol_3')->label('Margin Gol 3 (%)')->numeric()->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => str_replace('.', ',', (string) $livewire->ownerRecord?->margin_gol_3))->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $margin = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    $price = round($cost * (1 + ($margin / 100)), 2);
                                    $set('harga_jual_3', str_replace('.', ',', (string)$price));
                                }),
                            \Filament\Forms\Components\TextInput::make('harga_jual_3')->label('Harga Jual Gol 3')->rupiah()->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => str_replace('.', ',', (string) $livewire->ownerRecord?->harga_jual_3))->live(onBlur: true)
                                ->rules([
                                    fn (\Filament\Schemas\Components\Utilities\Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if (! $value) return;
                                        $cost = (float) str_replace(',', '.', str_replace('.', '', (string) $get('cost_price_tax')));
                                        $price = (float) str_replace(',', '.', str_replace('.', '', (string) $value));
                                        if ($cost > 0 && $price < $cost) {
                                            $fail('Harga Jual tidak boleh lebih kecil dari Harga Beli + PPN.');
                                        }
                                    }
                                ])
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $hpp = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $harga = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    if ($hpp > 0) {
                                        if ($harga > 0) {
                                            $margin = round((($harga - $hpp) / $hpp) * 100, 2);
                                            $set('margin_gol_3', str_replace('.', ',', (string)$margin));
                                        } else {
                                            $set('margin_gol_3', '0');
                                        }
                                    }
                                }),
                        ])->columns(3)->columnSpanFull(),
                        \Filament\Forms\Components\Hidden::make('selling_price')->default(fn (\Filament\Resources\RelationManagers\RelationManager $livewire) => $livewire->ownerRecord?->harga_jual_1 ?? 0)
                    ]),
                TextInput::make('quantity_on_hand')
                    ->label('Stok Saat Ini')
                    ->required()
                    ->ribuan_desimal()
                    ->default(0)
                    ->disabled(),
                TextInput::make('min_qty')
                    ->label('Min. Stok')
                    ->required()
                    ->numeric()
                    ->default(3),
                TextInput::make('max_qty')
                    ->label('Max. Stok')
                    ->required()
                    ->numeric()
                    ->default(15),
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
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('Aktif di Cabang Ini')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                $user = auth()->user();
                if ($user && $user->branch_id) {
                    $query->where('branch_id', $user->branch_id);
                }
                return $query;
            })
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
                \Filament\Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status Cabang')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Stok Cabang')
                    ->visible(fn () => auth()->user()->branch_id === null),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Hapus dari Cabang')
                    ->visible(fn () => auth()->user()->branch_id === null),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->branch_id === null),
                ]),
            ]);
    }
}
