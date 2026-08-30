<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Daftar Barang';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplierDivision.name')
                    ->label('Sub Divisi')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_division_id')
                    ->label('Sub Divisi')
                    ->options(function ($livewire) {
                        $currentSupplierId = $livewire->getOwnerRecord()->id;
                        return \App\Models\SupplierDivision::where('supplier_id', $currentSupplierId)
                            ->pluck('name', 'id');
                    }),
            ])
            ->headerActions([
                Action::make('tambahkan_barang')
                    ->label('Tambah Barang')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form([
                        \Filament\Forms\Components\Select::make('product_ids')
                            ->label('Pilih Barang')
                            ->multiple()
                            ->searchable()
                            ->options(function ($livewire) {
                                $currentSupplierId = $livewire->getOwnerRecord()->id;
                                return \App\Models\Product::where('supplier_id', '!=', $currentSupplierId)
                                    ->orWhereNull('supplier_id')
                                    ->pluck('name', 'id');
                            })
                            ->required(),
                        \Filament\Forms\Components\Select::make('supplier_division_id')
                            ->label('Tetapkan Sub Divisi')
                            ->placeholder('Tanpa Sub Divisi (Pemasok Global)')
                            ->options(function ($livewire) {
                                $currentSupplierId = $livewire->getOwnerRecord()->id;
                                return \App\Models\SupplierDivision::where('supplier_id', $currentSupplierId)
                                    ->pluck('name', 'id');
                            })
                            ->searchable(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $supplierId = $livewire->getOwnerRecord()->id;
                        \App\Models\Product::whereIn('id', $data['product_ids'])
                            ->update([
                                'supplier_id' => $supplierId,
                                'supplier_division_id' => $data['supplier_division_id'] ?? null,
                            ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil menambahkan barang ke pemasok ini')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('pindah_pemasok')
                    ->label('Pindah Pemasok / Sub Divisi')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('warning')
                    ->fillForm(fn ($record) => [
                        'new_supplier_id' => $record->supplier_id,
                        'new_supplier_division_id' => $record->supplier_division_id,
                    ])
                    ->form([
                        \Filament\Forms\Components\Select::make('new_supplier_id')
                            ->label('Pilih Pemasok Tujuan')
                            ->options(function () {
                                return \App\Models\Supplier::where('is_active', true)->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('new_supplier_division_id', null)),
                        \Filament\Forms\Components\Select::make('new_supplier_division_id')
                            ->label('Pilih Sub Divisi Tujuan')
                            ->placeholder('Tanpa Sub Divisi (Pemasok Global)')
                            ->options(function (callable $get, $record) {
                                $supplierId = $get('new_supplier_id') ?? $record?->supplier_id;
                                if (!$supplierId) {
                                    return [];
                                }
                                return \App\Models\SupplierDivision::where('supplier_id', $supplierId)
                                    ->pluck('name', 'id');
                            })
                            ->searchable(),
                    ])
                    ->action(function (array $data, $record) {
                        $record->update([
                            'supplier_id' => $data['new_supplier_id'],
                            'supplier_division_id' => $data['new_supplier_division_id'] ?? null,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil memperbarui Pemasok / Sub Divisi barang')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('pindah_pemasok_bulk')
                        ->label('Pindah Pemasok / Sub Divisi (Terpilih)')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->color('warning')
                        ->form([
                            \Filament\Forms\Components\Select::make('new_supplier_id')
                                ->label('Pilih Pemasok Tujuan')
                                ->options(function () {
                                    return \App\Models\Supplier::where('is_active', true)->pluck('name', 'id');
                                })
                                ->required()
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('new_supplier_division_id', null)),
                            \Filament\Forms\Components\Select::make('new_supplier_division_id')
                                ->label('Pilih Sub Divisi Tujuan')
                                ->placeholder('Tanpa Sub Divisi (Pemasok Global)')
                                ->options(function (callable $get) {
                                    $supplierId = $get('new_supplier_id');
                                    if (!$supplierId) {
                                        return [];
                                    }
                                    return \App\Models\SupplierDivision::where('supplier_id', $supplierId)
                                        ->pluck('name', 'id');
                                })
                                ->searchable(),
                        ])
                        ->action(function (array $data, \Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'supplier_id' => $data['new_supplier_id'],
                                    'supplier_division_id' => $data['new_supplier_division_id'] ?? null,
                                ]);
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil pindah pemasok / sub divisi untuk barang terpilih')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
