<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class TopProducts extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = '20 Barang Terlaris';

    public function table(Table $table): Table
    {
        $branchId = auth()->user()->branch_id ?? $this->filters['branch_id'] ?? null;

        $query = Product::query()
            ->join('transaction_items', 'products.id', '=', 'transaction_items.product_id')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->selectRaw('products.id, products.name, products.sku, SUM(transaction_items.quantity) as total_sold')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_sold')
            ->limit(20);

        if ($branchId) {
            $query->where('transactions.branch_id', $branchId);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_sold')
                    ->label('Total Terjual')
                    ->numeric()
                    ->badge()
                    ->color('success'),
            ])
            ->paginated(false);
    }
}
