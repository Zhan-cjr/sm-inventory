<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeadStockAlert extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Peringatan Dead Stock (> 60 Hari Tanpa Penjualan)';

    public function table(Table $table): Table
    {
        $branchId = auth()->user()->branch_id ?? $this->filters['branch_id'] ?? null;
        $sixtyDaysAgo = Carbon::now()->subDays(60);

        $recentActiveProductIds = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.created_at', '>=', $sixtyDaysAgo)
            ->when($branchId, fn($q) => $q->where('transactions.branch_id', $branchId))
            ->pluck('transaction_items.product_id')
            ->unique();

        $query = Stock::query()
            ->with(['product', 'branch'])
            ->where('is_active', true)
            ->where('quantity_on_hand', '>', 0)
            ->whereNotIn('product_id', $recentActiveProductIds)
            ->orderBy('quantity_on_hand', 'desc')
            ->limit(10);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->visible(fn () => auth()->user()->branch_id === null),
                Tables\Columns\TextColumn::make('quantity_on_hand')
                    ->label('Stok Tertahan')
                    ->numeric(decimalPlaces: 0)
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('stock_value')
                    ->label('Nilai Modal (Rp)')
                    ->state(fn ($record) => 'Rp ' . number_format($record->quantity_on_hand * ($record->cost_price > 0 ? $record->cost_price : ($record->product->cost_price ?? 0)), 0, ',', '.'))
                    ->color('danger'),
            ])
            ->actions([
                Action::make('create_promo')
                    ->label('Buat Diskon Clearance')
                    ->icon('heroicon-o-tag')
                    ->color('warning')
                    ->action(function ($record) {
                        $orgId = auth()->user()->organization_id ?? \App\Models\Organization::first()?->id;

                        $promo = \App\Models\Promotion::create([
                            'organization_id' => $orgId,
                            'name' => 'Diskon Obral Clearance: ' . $record->product->name,
                            'promo_type' => 'PERCENTAGE',
                            'discount_value' => 20,
                            'applicable_to' => 'PRODUCT',
                            'target_ids' => [$record->product_id],
                            'valid_from' => now(),
                            'valid_until' => now()->addDays(14),
                            'is_active' => true,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Draft Promo Clearance berhasil dibuat!')
                            ->success()
                            ->send();

                        return redirect()->to(route('filament.admin.resources.promotions.edit', $promo));
                    }),
            ])
            ->paginated(false);
    }
}
