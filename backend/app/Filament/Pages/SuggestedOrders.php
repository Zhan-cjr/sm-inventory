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
use Filament\Resources\Components\Tab;

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
            \Filament\Actions\Action::make('faq')
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
                    ->with(['product', 'branch'])
            )
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
                TextColumn::make('target_days')
                    ->label('Target Stok (Hari)')
                    ->state(fn ($record) => app(SuggestedOrderService::class)->calculateForStock($record)['target_days']),
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
                \Filament\Tables\Filters\Filter::make('perlu_kulakan')
                    ->label('Perlu Kulakan (Saran > 0)')
                    ->toggle()
                    ->default(true)
                    ->query(function (Builder $query) {
                        $branchId = request('tableFilters.branch_id.value') ?? (auth()->user()->branch_id ?? \App\Models\Branch::first()->id ?? null);
                        
                        $query->where(function ($q) use ($branchId) {
                            // 1. Termasuk dari Fallback Logic (Stok <= 0)
                            $q->where('quantity_on_hand', '<=', 0);
                            
                            // 2. Termasuk dari rekomendasi AI Service
                            if ($branchId) {
                                $aiData = app(SuggestedOrderService::class)->calculateForBranch($branchId);
                                $productIds = collect($aiData)
                                    ->filter(fn($item) => $item['suggested_qty'] > 0 || $item['status'] !== 'OK')
                                    ->pluck('product_id');
                                    
                                if ($productIds->isNotEmpty()) {
                                    $q->orWhereIn('product_id', $productIds);
                                }
                            }
                        });
                        
                        return $query;
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
                                'quantity_suggested' => $suggestion['suggested_qty'],
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
