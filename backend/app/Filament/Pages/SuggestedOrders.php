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

class SuggestedOrders extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Saran Pemesanan';
    protected static ?string $title = 'Saran Pemesanan (Forecasting)';
    protected static string|\UnitEnum|null $navigationGroup = 'PERSEDIAAN';
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.suggested-orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(\App\Models\Stock::query()->with(['product', 'branch']))
            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('branch.name')
                    ->label('Cabang'),
                TextColumn::make('quantity_on_hand')
                    ->label('Stok Saat Ini')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ads')
                    ->label('ADS (Sales/Hari)')
                    ->state(fn ($record) => app(SuggestedOrderService::class)->calculateForStock($record)['ads']),
                TextColumn::make('reorder_point')
                    ->label('Titik Pesan (ROP)')
                    ->state(fn ($record) => app(SuggestedOrderService::class)->calculateForStock($record)['reorder_point']),
                TextColumn::make('suggested_qty')
                    ->label('Saran Pesan')
                    ->weight('bold')
                    ->color('primary')
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
                    ->state(fn ($record) => app(SuggestedOrderService::class)->calculateForStock($record)['status']),
            ])
            ->filters([
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

                        $firstRecord = $records->first();
                        
                        $po = \App\Models\PurchaseOrder::create([
                            'organization_id' => $firstRecord->product->organization_id,
                            'branch_id' => $firstRecord->branch_id,
                            'supplier_id' => $firstRecord->product->supplier_id,
                            'po_number' => 'PO-' . date('YmdHis'),
                            'po_date' => now(),
                            'status' => 'DRAFT',
                            'total_amount' => 0,
                            'created_by' => auth()->id(),
                        ]);

                        $totalAmount = 0;
                        foreach ($records as $record) {
                            $suggestion = app(SuggestedOrderService::class)->calculateForStock($record);
                            if ($suggestion['suggested_qty'] <= 0) continue;

                            $costPrice = $record->product->cost_price_tax > 0 ? $record->product->cost_price_tax : $record->product->cost_price;
                            $subtotal = $suggestion['suggested_qty'] * $costPrice;
                            \App\Models\PurchaseOrderItem::create([
                                'purchase_order_id' => $po->id,
                                'product_id' => $record->product_id,
                                'quantity_ordered' => $suggestion['suggested_qty'],
                                'unit_cost' => $costPrice,
                                'subtotal' => $subtotal,
                            ]);
                            $totalAmount += $subtotal;
                        }

                        $po->update(['total_amount' => $totalAmount]);

                        \Filament\Notifications\Notification::make()
                            ->title('Draft Pesanan Pembelian Massal berhasil dibuat')
                            ->success()
                            ->send();

                        return redirect()->to(route('filament.admin.resources.purchase-orders.edit', $po));
                    }),
            ]);
    }
}
