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
                    ->with(['product', 'product.supplier', 'branch'])
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
                TextColumn::make('product.supplier.name')
                    ->label('Pemasok')
                    ->searchable()
                    ->placeholder('-'),
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
                \Filament\Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('product.supplier', 'name'),
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
                        
                        $po = \App\Models\PurchaseOrder::create([
                            'organization_id' => $record->product->organization_id,
                            'branch_id' => $record->branch_id,
                            'supplier_id' => $record->product->supplier_id,
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

                        $recordsBySupplier = $records->groupBy(fn($rec) => $rec->product->supplier_id);
                        $createdPoCount = 0;
                        $lastPo = null;

                        foreach ($recordsBySupplier as $supplierId => $supplierRecords) {
                            if (!$supplierId) continue;
                            
                            $firstRecord = $supplierRecords->first();
                            $po = \App\Models\PurchaseOrder::create([
                                'organization_id' => $firstRecord->product->organization_id,
                                'branch_id' => $firstRecord->branch_id,
                                'supplier_id' => $supplierId,
                                'po_number' => 'PO-' . date('YmdHis') . '-' . rand(10,99),
                                'po_date' => now(),
                                'status' => 'DRAFT',
                                'total_amount' => 0,
                                'created_by' => auth()->id(),
                            ]);

                            $totalAmount = 0;
                            foreach ($supplierRecords as $record) {
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
                            ->title("{$createdPoCount} Draft Pesanan Pembelian berhasil dibuat berdasarkan Pemasok")
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
