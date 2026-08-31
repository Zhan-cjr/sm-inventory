<?php

namespace App\Filament\Resources\StockOpname\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\StockOpnameRack;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\Action;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\BulkActionGroup;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'Barang di Rak Ini';
    
    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_on_hand')
                    ->label('Stok Sistem')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('tambah_barang')
                    ->label('Tambah ke Rak')
                    ->form([
                        \Filament\Forms\Components\Select::make('stock_id')
                            ->label('Cari Barang')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $branchId = \Illuminate\Support\Facades\Auth::user()->branch_id;
                                $query = \App\Models\Stock::with('product');
                                if ($branchId) {
                                    $query->where('branch_id', $branchId);
                                }
                                return $query->whereHas('product', function($q) use ($search) {
                                        $q->where('name', 'like', "%{$search}%")
                                          ->orWhere('sku', 'like', "%{$search}%")
                                          ->orWhere('barcode', 'like', "%{$search}%");
                                    })
                                    ->limit(30)
                                    ->get()
                                    ->mapWithKeys(function ($stock) {
                                        $name = $stock->product ? $stock->product->name : 'Unknown';
                                        $sku = $stock->product ? $stock->product->sku : '-';
                                        return [$stock->id => "{$name} ({$sku})"];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $stock = \App\Models\Stock::with('product')->find($value);
                                if ($stock) {
                                    $name = $stock->product ? $stock->product->name : 'Unknown';
                                    $sku = $stock->product ? $stock->product->sku : '-';
                                    return "{$name} ({$sku})";
                                }
                                return '-';
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire) {
                        if (method_exists($livewire, 'getOwnerRecord') && $livewire->getOwnerRecord()) {
                            $livewire->getOwnerRecord()->stocks()->syncWithoutDetaching([$data['stock_id']]);
                            \Filament\Notifications\Notification::make()
                                ->title('Barang ditambahkan ke rak')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Action::make('pindah_rak')
                    ->label('Pindah Rak')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Select::make('new_rack_id')
                            ->label('Pilih Rak Tujuan')
                            ->options(function () {
                                $branchId = \Illuminate\Support\Facades\Auth::user()->branch_id;
                                $query = \App\Models\StockOpnameRack::query();
                                if ($branchId) {
                                    $query->where('branch_id', $branchId);
                                }
                                return $query->pluck('rack_name', 'id');
                            })
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (array $data, $record, $livewire) {
                        if (method_exists($livewire, 'getOwnerRecord') && $livewire->getOwnerRecord()) {
                            $livewire->getOwnerRecord()->stocks()->detach($record->id);
                        }
                        
                        $newRack = \App\Models\StockOpnameRack::find($data['new_rack_id']);
                        if ($newRack) {
                            $newRack->stocks()->syncWithoutDetaching([$record->id]);
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil pindah rak')
                            ->success()
                            ->send();
                    }),
                DetachAction::make()->label('Keluarkan'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('cetak_pricecard_rak')
                        ->label('Cetak Pricecard')
                        ->icon('heroicon-o-tag')
                        ->color('warning')
                        ->form([
                            \Filament\Forms\Components\TextInput::make('copies')
                                ->label('Jumlah Pricecard per Produk')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data, $livewire) {
                            $productIds = $records->pluck('product_id')->unique()->toArray();
                            $branchId = (method_exists($livewire, 'getOwnerRecord') && $livewire->getOwnerRecord()) 
                                ? $livewire->getOwnerRecord()->branch_id 
                                : \Illuminate\Support\Facades\Auth::user()->branch_id;
                            return redirect()->route('print.barcode.pricecard', [
                                'product_ids' => $productIds,
                                'copies' => $data['copies'],
                                'branch_id' => $branchId,
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    DetachBulkAction::make()->label('Keluarkan Terpilih'),
                ]),
            ]);
    }
}
