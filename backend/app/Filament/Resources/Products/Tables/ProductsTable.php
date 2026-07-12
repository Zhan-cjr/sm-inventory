<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query, \Filament\Tables\Contracts\HasTable $livewire) {
                $user = \Illuminate\Support\Facades\Auth::user();
                $branchId = null;

                if ($user && $user->branch_id) {
                    $branchId = $user->branch_id;
                } else {
                    $branchId = $livewire->tableFilters['branch']['value'] ?? null;
                }

                if ($branchId) {
                    $query->with(['stocks' => function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId)->with('racks');
                    }]);
                    $query->withSum(['stocks' => function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    }], 'quantity_on_hand');
                } else {
                    $query->with('stocks.racks');
                    $query->withSum('stocks', 'quantity_on_hand');
                }
                
                // Prioritize exact barcode or sku match in global search if search term exists
                $search = $livewire->getTableSearch();
                if ($search) {
                    $query->orderByRaw("CASE WHEN barcode = ? THEN 1 WHEN sku = ? THEN 2 ELSE 3 END", [$search, $search]);
                    
                    // Force exact phrase match across the searchable columns
                    // to prevent Filament's default behavior of splitting words and returning irrelevant results.
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%")
                          ->orWhere('barcode', 'like', "%{$search}%")
                          ->orWhere('metadata->additional_barcodes', 'like', "%{$search}%")
                          ->orWhereHas('stocks.racks', function ($q2) use ($search) {
                              $q2->where('rack_code', 'like', "%{$search}%");
                          });
                    });
                }

                return $query;
            })
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Foto')
                    ->square(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('barcode')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cost_price_tax')
                    ->label('Harga Beli (+PPN)')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('stocks_sum_quantity_on_hand')
                    ->label('Stok')
                    ->formatStateUsing(fn ($state) => (float) $state)
                    ->sortable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()),
                TextColumn::make('stocks.racks.rack_code')
                    ->label('No Rak')
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                \Filament\Tables\Columns\ToggleColumn::make('is_ecommerce_active')
                    ->label('Tampil E-Commerce')
                    ->disabled(fn () => auth()->user()->branch_id !== null),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('branch')
                    ->label('Cabang')
                    ->relationship('stocks.branch', 'name')
                    ->hidden(fn () => auth()->user()->branch_id !== null),
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Produk')
                    ->placeholder('Semua Produk')
                    ->trueLabel('Produk Aktif')
                    ->falseLabel('Produk Non Aktif')
                    ->default(true),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('kartu_stok')
                    ->label('Kartu Stok')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info')
                    ->modalHeading(fn ($record) => "Kartu Stok: {$record->name}")
                    ->modalWidth('4xl')
                    ->modalFooterActions([])
                    ->modalContent(fn ($record, \Filament\Tables\Contracts\HasTable $livewire) => view(
                        'filament.components.stock-card-livewire', 
                        [
                            'record' => $record,
                            'branchId' => $livewire->tableFilters['branch']['value'] ?? null
                        ]
                    )),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assign_to_branch')
                        ->label('Tetapkan ke Cabang')
                        ->icon('heroicon-o-building-storefront')
                        ->form([
                            \Filament\Forms\Components\Select::make('branch_id')
                                ->label('Pilih Cabang')
                                ->options(fn () => \App\Models\Branch::all()->pluck('name', 'id'))
                                ->searchable()
                                ->default(fn() => auth()->user()->branch_id)
                                ->disabled(fn() => auth()->user()->branch_id !== null)
                                ->dehydrated()
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('quantity')
                                ->label('Stok Awal')
                                ->numeric()
                                ->default(0)
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each(function ($record) use ($data) {
                                \App\Models\Stock::updateOrCreate(
                                    [
                                        'branch_id' => $data['branch_id'],
                                        'product_id' => $record->id,
                                    ],
                                    [
                                        'quantity_on_hand' => $data['quantity'],
                                    ]
                                );
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('cetak_label_barcode')
                        ->label('Cetak Label Barcode')
                        ->icon('heroicon-o-qr-code')
                        ->color('success')
                        ->form([
                            \Filament\Forms\Components\TextInput::make('copies')
                                ->label('Jumlah Label per Produk')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data) {
                            $productIds = $records->pluck('id')->toArray();
                            return redirect()->route('print.barcode.label', [
                                'product_ids' => $productIds,
                                'copies' => $data['copies'],
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('cetak_pricecard_rak')
                        ->label('Cetak Pricecard Rak')
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
                        ->action(function (\Illuminate\Support\Collection $records, array $data) {
                            $productIds = $records->pluck('id')->toArray();
                            return redirect()->route('print.barcode.pricecard', [
                                'product_ids' => $productIds,
                                'copies' => $data['copies'],
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->branch_id === null),
                ]),
            ]);
    }
}
