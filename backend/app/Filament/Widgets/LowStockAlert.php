<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class LowStockAlert extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Peringatan Stok Menipis & Kritis';

    public function table(Table $table): Table
    {
        $branchId = auth()->user()->branch_id ?? $this->filters['branch_id'] ?? null;

        $query = Stock::query()
            ->with(['product', 'branch'])
            ->where('is_active', true)
            ->whereRaw('quantity_on_hand <= COALESCE(min_qty, 10)')
            ->orderBy('quantity_on_hand', 'asc')
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
                    ->label('Sisa Stok')
                    ->numeric(decimalPlaces: 0)
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('min_qty')
                    ->label('Batas Minimum')
                    ->numeric(decimalPlaces: 0)
                    ->formatStateUsing(fn ($state) => $state ?? 10),
            ])
            ->actions([
                Action::make('restock_po')
                    ->label('Saran Restock AI')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->action(function ($record) {
                        return redirect()->to(route('filament.admin.pages.suggested-orders'));
                    }),
            ])
            ->paginated(false);
    }
}
