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
                    session(['active_selected_branch_id' => $branchId]);
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
                
                // Integrate Laravel Scout (Meilisearch) with SQL Fallback
                $search = $livewire->getTableSearch();
                if (filled($search)) {
                    try {
                        $scoutIds = \App\Models\Product::search($search)->take(1000)->keys();
                        
                        if ($scoutIds->isEmpty()) {
                            $query->whereRaw('1 = 0'); // Force empty result if Scout finds nothing
                        } else {
                            $query->whereIn('products.id', $scoutIds);
                        }
                    } catch (\Exception $e) {
                        // Fallback: If Scout/Meilisearch fails or is unreachable, use fast SQL LIKE query
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('sku', 'like', "%{$search}%")
                              ->orWhere('barcode', 'like', "%{$search}%")
                              ->orWhere('barcode_2', 'like', "%{$search}%")
                              ->orWhere('barcode_3', 'like', "%{$search}%")
                              ->orWhere('barcode_4', 'like', "%{$search}%")
                              ->orWhere('barcode_5', 'like', "%{$search}%")
                              ->orWhere('barcode_6', 'like', "%{$search}%");
                        });
                    }
                }
                
                return $query;
            })
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-product.png')),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('SKU berhasil disalin')
                    ->copyMessageDuration(1500),
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('unit_of_measure')
                    ->label('Satuan')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('supplier.name')
                    ->label('Suplier')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cost_price')
                    ->label('Harga Pokok')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('selling_price')
                    ->label('Harga Jual 1')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('stocks_sum_quantity_on_hand')
                    ->label('Sisa Stok')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->badge()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : ($state < 10 ? 'warning' : 'success')),
                TextColumn::make('stocks.racks.name')
                    ->label('Rak')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),
            ])
            ->recordUrl(function ($record, \Filament\Tables\Contracts\HasTable $livewire) {
                $branchId = \Illuminate\Support\Facades\Auth::user()?->branch_id 
                    ?? ($livewire->tableFilters['branch']['value'] ?? null);
                $url = route('filament.admin.resources.products.edit', ['record' => $record->id]);
                return $branchId ? "{$url}?branch_id={$branchId}" : $url;
            })
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Kategori')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->label('Suplier')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\Filter::make('supplier_division_filter')
                    ->form([
                        \Filament\Forms\Components\Select::make('supplier_id')
                            ->label('Suplier')
                            ->options(fn () => \App\Models\Supplier::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('supplier_division_id', null)),
                        \Filament\Forms\Components\Select::make('supplier_division_id')
                            ->label('Sub Divisi')
                            ->options(function (callable $get) {
                                $supplierId = $get('supplier_id');
                                if (!$supplierId) {
                                    return [];
                                }
                                return \App\Models\SupplierDivision::where('supplier_id', $supplierId)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->disabled(fn (callable $get) => !$get('supplier_id')),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['supplier_id'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $supplierId): \Illuminate\Database\Eloquent\Builder => $query->where('supplier_id', $supplierId),
                            )
                            ->when(
                                $data['supplier_division_id'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $divisionId): \Illuminate\Database\Eloquent\Builder => $query->where('supplier_division_id', $divisionId),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['supplier_id'] ?? null) {
                            $supplier = \App\Models\Supplier::find($data['supplier_id']);
                            if ($supplier) {
                                $indicators[] = \Filament\Tables\Filters\Indicator::make('Suplier: ' . $supplier->name)
                                    ->removeField('supplier_id');
                            }
                            if ($data['supplier_division_id'] ?? null) {
                                $division = \App\Models\SupplierDivision::find($data['supplier_division_id']);
                                if ($division) {
                                    $indicators[] = \Filament\Tables\Filters\Indicator::make('Sub Divisi: ' . $division->name)
                                        ->removeField('supplier_division_id');
                                }
                            }
                        }
                        return $indicators;
                    }),
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
                        ->label('Export Xlsx (Format Cetak)')
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
                EditAction::make()
                    ->url(function ($record, \Filament\Tables\Contracts\HasTable $livewire) {
                        $branchId = \Illuminate\Support\Facades\Auth::user()?->branch_id 
                            ?? ($livewire->tableFilters['branch']['value'] ?? null);
                        $url = route('filament.admin.resources.products.edit', ['record' => $record->id]);
                        return $branchId ? "{$url}?branch_id={$branchId}" : $url;
                    }),
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
                            @set_time_limit(0);
                            $productIds = $records->pluck('id')->filter()->unique()->toArray();
                            if (empty($productIds)) {
                                return;
                            }

                            $branchId = $data['branch_id'];
                            $quantity = $data['quantity'] ?? 0;
                            $now = now();

                            $payload = [];
                            foreach ($productIds as $productId) {
                                $payload[] = [
                                    'id' => (string) \Illuminate\Support\Str::uuid(),
                                    'branch_id' => $branchId,
                                    'product_id' => $productId,
                                    'quantity_on_hand' => $quantity,
                                    'quantity_reserved' => 0,
                                    'min_qty' => 3,
                                    'max_qty' => 15,
                                    'is_active' => 1,
                                    'lead_time' => 3,
                                    'safety_stock' => 0,
                                    'desired_inventory_days' => 14,
                                    'version' => 1,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }

                            foreach (array_chunk($payload, 500) as $chunk) {
                                \App\Models\Stock::upsert(
                                    $chunk,
                                    ['branch_id', 'product_id'],
                                    ['quantity_on_hand', 'updated_at']
                                );
                            }

                            // Invalidate cache once after mass assignment
                            \Illuminate\Support\Facades\Cache::forget('ecommerce_products_all');
                            \Illuminate\Support\Facades\Cache::forget('ecommerce_products_' . $branchId);
                            \Illuminate\Support\Facades\Cache::forget('pos_products_json_gz_branch_' . $branchId);

                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil Menetapkan ke Cabang')
                                ->body(count($productIds) . ' produk berhasil ditetapkan ke cabang.')
                                ->success()
                                ->send();
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