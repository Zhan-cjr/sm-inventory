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
                
                // Integrate Laravel Scout (Meilisearch) for table search
                $search = $livewire->getTableSearch();
                if (filled($search)) {
                    $scoutIds = \App\Models\Product::search($search)->take(1000)->keys();
                    
                    if ($scoutIds->isEmpty()) {
                        $query->whereRaw('1 = 0'); // Force empty result if Scout finds nothing
                    } else {
                        $query->whereIn('products.id', $scoutIds);
                        // Preserve Meilisearch relevance ordering
                        $scoutIdsStr = $scoutIds->implode("','");
                        $query->orderByRaw("FIELD(products.id, '$scoutIdsStr')");
                    }
                }

                return $query;
            })
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Foto')
                    ->disk('public')
                    ->square()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable(query: fn (\Illuminate\Database\Eloquent\Builder $query) => $query)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(query: fn (\Illuminate\Database\Eloquent\Builder $query) => $query)
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable(query: fn (\Illuminate\Database\Eloquent\Builder $query) => $query)
                    ->sortable(),
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
                    ->searchable(query: fn (\Illuminate\Database\Eloquent\Builder $query) => $query)
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
            ->headerActions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ExportAction::make('export_products')
                        ->label('Export Xlsx (Raw Data)')
                        ->exporter(\App\Filament\Exports\ProductExporter::class)
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->modalHeading('Pilih Kolom Export')
                        ->modalSubmitActionLabel('Proses Export'),
                    \Filament\Actions\Action::make('export_xls')
                        ->label('Export Xls (Format Cetak)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                            'type' => 'produk',
                            'export' => 'xls',
                            'tableFilters' => $livewire->tableFilters,
                            'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null
                        ]), true)
                ])
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button(),
            ])
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
