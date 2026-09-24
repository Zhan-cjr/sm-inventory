<?php

namespace App\Filament\Pages;

use App\Services\SuggestedOrderService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Database\Eloquent\Builder;

class SuggestedOrders extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Saran Order AI';
    protected static ?string $title = 'Saran Order AI (Smart Restock)';
    protected static string|\UnitEnum|null $navigationGroup = 'ANALISA AI';
    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.suggested-orders';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh_ai')
                ->label('Segarkan Data AI')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    $branchId = request('tableFilters.branch_id.value') ?? (auth()->user()->branch_id ?? null);
                    app(SuggestedOrderService::class)->clearCache($branchId);
                    \Filament\Notifications\Notification::make()
                        ->title('Data Saran Order AI berhasil diperbarui')
                        ->success()
                        ->send();
                }),
            Action::make('faq')
                ->label('Cara Membaca Saran AI')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modalHeading('Panduan Membaca Saran Order AI')
                ->modalContent(view('filament.components.saran-order-faq'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\Stock::query()
                    ->where('is_active', true)
                    ->whereHas('product', fn($q) => $q->where('is_active', true))
                    ->with(['product', 'product.supplier', 'product.supplierDivision', 'supplier', 'supplierDivision', 'branch'])
            )
            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('pemasok')
                    ->label('Pemasok & Sub Divisi')
                    ->state(function ($record) {
                        return $record->supplier?->name ?? $record->product?->supplier?->name ?? '-';
                    })
                    ->description(function ($record) {
                        $division = $record->supplier_id 
                            ? $record->supplierDivision?->name 
                            : $record->product?->supplierDivision?->name;
                        
                        $parts = [];
                        if ($division) {
                            $parts[] = "Divisi: {$division}";
                        }
                        if (!empty($record->supplier_id)) {
                            $parts[] = 'Pemasok Khusus Cabang';
                        }
                        return !empty($parts) ? implode(' • ', $parts) : null;
                    })
                    ->badge(fn ($record) => !empty($record->supplier_id))
                    ->color(fn ($record) => !empty($record->supplier_id) ? 'info' : null)
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->whereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                              ->orWhereHas('supplierDivision', fn ($sdq) => $sdq->where('name', 'like', "%{$search}%"))
                              ->orWhere(function ($sq) use ($search) {
                                  $sq->whereNull('stocks.supplier_id')
                                     ->where(function ($pq) use ($search) {
                                         $pq->whereHas('product.supplier', fn ($psq) => $psq->where('name', 'like', "%{$search}%"))
                                            ->orWhereHas('product.supplierDivision', fn ($psdq) => $psdq->where('name', 'like', "%{$search}%"));
                                     });
                              });
                        });
                    }),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->sortable(),
                TextColumn::make('quantity_on_hand')
                    ->label('Stok Saat Ini')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('ads')
                    ->label('ADS (Sales/Hari)')
                    ->numeric(decimalPlaces: 2)
                    ->state(fn ($record) => app(SuggestedOrderService::class)->calculateForStock($record)['ads']),
                TextColumn::make('reorder_point')
                    ->label('Titik Pesan (ROP)')
                    ->numeric(decimalPlaces: 0)
                    ->state(fn ($record) => app(SuggestedOrderService::class)->calculateForStock($record)['reorder_point']),
                TextColumn::make('target_days')
                    ->label('Target Stok (Hari)')
                    ->state(fn ($record) => app(SuggestedOrderService::class)->calculateForStock($record)['target_days']),
                TextColumn::make('suggested_qty')
                    ->label('Saran Pesan')
                    ->weight('bold')
                    ->color('primary')
                    ->numeric(decimalPlaces: 0)
                    ->state(fn ($record) => app(SuggestedOrderService::class)->calculateForStock($record)['suggested_qty']),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CRITICAL' => 'danger',
                        'REORDER' => 'warning',
                        'OK' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'CRITICAL' => 'HABIS (CRITICAL)',
                        'REORDER' => 'PERLU ORDER',
                        'OK' => 'AMAN',
                        default => $state,
                    })
                    ->state(fn ($record) => app(SuggestedOrderService::class)->calculateForStock($record)['status']),
            ])
            ->filters([
                \Filament\Tables\Filters\Filter::make('perlu_kulakan')
                    ->label('Perlu Kulakan (Kritis & Perlu Order)')
                    ->toggle()
                    ->default(true)
                    ->query(function (Builder $query) {
                        $branchId = request('tableFilters.branch_id.value') ?? (auth()->user()->branch_id ?? null);
                        $neededIds = app(SuggestedOrderService::class)->getRestockNeededStockIds($branchId);
                        return $query->whereIn('stocks.id', $neededIds);
                    }),
                \Filament\Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => auth()->user()->branch_id !== null),
                \Filament\Tables\Filters\Filter::make('pemasok_filter')
                    ->form([
                        \Filament\Forms\Components\Select::make('supplier_id')
                            ->label('Pemasok')
                            ->placeholder('Semua Pemasok')
                            ->options(fn () => \App\Models\Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('supplier_division_id', null)),
                        \Filament\Forms\Components\Select::make('supplier_division_id')
                            ->label('Sub Divisi Pemasok')
                            ->placeholder('Semua Sub Divisi')
                            ->options(function ($get) {
                                $supplierId = $get('supplier_id');
                                if (filled($supplierId)) {
                                    return \App\Models\SupplierDivision::where('supplier_id', $supplierId)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                }
                                return \App\Models\SupplierDivision::orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['supplier_id'] ?? null, function ($q, $supplierId) {
                                $q->where(function ($sq) use ($supplierId) {
                                    $sq->where('stocks.supplier_id', $supplierId)
                                       ->orWhere(function ($psq) use ($supplierId) {
                                           $psq->whereNull('stocks.supplier_id')
                                              ->whereHas('product', fn ($pq) => $pq->where('supplier_id', $supplierId));
                                       });
                                });
                            })
                            ->when($data['supplier_division_id'] ?? null, function ($q, $divisionId) {
                                $q->where(function ($sq) use ($divisionId) {
                                    $sq->where('stocks.supplier_division_id', $divisionId)
                                       ->orWhere(function ($psq) use ($divisionId) {
                                           $psq->whereNull('stocks.supplier_id')
                                              ->whereHas('product', fn ($pq) => $pq->where('supplier_division_id', $divisionId));
                                       });
                                });
                            });
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['supplier_id'])) {
                            $supplier = \App\Models\Supplier::find($data['supplier_id']);
                            if ($supplier) {
                                $indicators[] = \Filament\Tables\Filters\Indicator::make('Pemasok: ' . $supplier->name)
                                    ->removeField('supplier_id');
                            }
                        }
                        if (!empty($data['supplier_division_id'])) {
                            $division = \App\Models\SupplierDivision::find($data['supplier_division_id']);
                            if ($division) {
                                $indicators[] = \Filament\Tables\Filters\Indicator::make('Sub Divisi: ' . $division->name)
                                    ->removeField('supplier_division_id');
                            }
                        }
                        return $indicators;
                    }),
            ])
            ->recordActions([
                Action::make('create_po')
                    ->label('Buat PO')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn ($record) => app(SuggestedOrderService::class)->calculateForStock($record)['suggested_qty'] > 0)
                    ->action(function ($record) {
                        $suggestion = app(SuggestedOrderService::class)->calculateForStock($record);
                        
                        $costPrice = $record->product->cost_price_tax > 0 ? $record->product->cost_price_tax : $record->product->cost_price;
                        $targetSupplierId = $record->supplier_id ?: $record->product->supplier_id;
                        $targetDivisionId = $record->supplier_id ? $record->supplier_division_id : $record->product->supplier_division_id;
                        
                        $po = \App\Models\PurchaseOrder::create([
                            'organization_id' => $record->product->organization_id,
                            'branch_id' => $record->branch_id,
                            'supplier_id' => $targetSupplierId,
                            'supplier_division_id' => $targetDivisionId,
                            'po_number' => 'PO-' . date('YmdHis'),
                            'po_date' => now(),
                            'status' => 'DRAFT',
                            'total_amount' => $suggestion['suggested_qty'] * $costPrice,
                            'created_by' => auth()->id(),
                        ]);

                        \App\Models\PurchaseOrderItem::create([
                            'purchase_order_id' => $po->id,
                            'product_id' => $record->product_id,
                            'quantity_suggested' => $suggestion['suggested_qty'],
                            'quantity_ordered' => $suggestion['suggested_qty'],
                            'unit_cost' => $costPrice,
                            'subtotal' => $suggestion['suggested_qty'] * $costPrice,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Draft Pesanan Pembelian berhasil dibuat')
                            ->success()
                            ->send();

                        return redirect()->to(route('filament.admin.resources.purchase-orders.edit', $po));
                    }),
            ])
            ->bulkActions([
                BulkAction::make('create_po_bulk')
                    ->label('Buat PO Terpilih')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->action(function (\Illuminate\Support\Collection $records) {
                        if ($records->isEmpty()) return;

                        $recordsGrouped = $records->groupBy(function ($rec) {
                            $supplierId = $rec->supplier_id ?: ($rec->product->supplier_id ?? 'unknown');
                            $divisionId = $rec->supplier_id ? $rec->supplier_division_id : ($rec->product->supplier_division_id ?? 'none');
                            return "{$supplierId}_{$divisionId}";
                        });
                        $createdPoCount = 0;
                        $lastPo = null;

                        foreach ($recordsGrouped as $groupKey => $groupRecords) {
                            $firstRecord = $groupRecords->first();
                            $supplierId = $firstRecord->supplier_id ?: ($firstRecord->product->supplier_id ?? null);
                            if (!$supplierId || $supplierId === 'unknown') continue;

                            $targetDivisionId = $firstRecord->supplier_id ? $firstRecord->supplier_division_id : ($firstRecord->product->supplier_division_id ?? null);

                            $po = \App\Models\PurchaseOrder::create([
                                'organization_id' => $firstRecord->product->organization_id,
                                'branch_id' => $firstRecord->branch_id,
                                'supplier_id' => $supplierId,
                                'supplier_division_id' => $targetDivisionId,
                                'po_number' => 'PO-' . date('YmdHis') . '-' . rand(10, 99),
                                'po_date' => now(),
                                'status' => 'DRAFT',
                                'total_amount' => 0,
                                'created_by' => auth()->id(),
                            ]);

                            $totalAmount = 0;
                            foreach ($groupRecords as $record) {
                                $suggestion = app(SuggestedOrderService::class)->calculateForStock($record);
                                $qty = $suggestion['suggested_qty'] > 0 ? $suggestion['suggested_qty'] : 1;

                                $costPrice = $record->product->cost_price_tax > 0 ? $record->product->cost_price_tax : $record->product->cost_price;
                                $subtotal = $qty * $costPrice;
                                
                                \App\Models\PurchaseOrderItem::create([
                                    'purchase_order_id' => $po->id,
                                    'product_id' => $record->product_id,
                                    'quantity_suggested' => $suggestion['suggested_qty'],
                                    'quantity_ordered' => $qty,
                                    'unit_cost' => $costPrice,
                                    'subtotal' => $subtotal,
                                ]);
                                $totalAmount += $subtotal;
                            }

                            $po->update(['total_amount' => $totalAmount]);
                            $createdPoCount++;
                            $lastPo = $po;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title("{$createdPoCount} Draft Pesanan Pembelian berhasil dibuat berdasarkan Pemasok & Sub Divisi")
                            ->success()
                            ->send();

                        if ($createdPoCount === 1 && $lastPo) {
                            return redirect()->to(route('filament.admin.resources.purchase-orders.edit', $lastPo));
                        }

                        return redirect()->to(route('filament.admin.resources.purchase-orders.index'));
                    }),
            ]);
    }
}
