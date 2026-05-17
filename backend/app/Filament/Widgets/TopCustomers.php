<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class TopCustomers extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Pelanggan Terbaik (Berdasarkan Nilai Transaksi)';

    public function table(Table $table): Table
    {
        $branchId = auth()->user()->branch_id ?? $this->filters['branch_id'] ?? null;

        $query = Customer::query()
            ->join('transactions', 'customers.id', '=', 'transactions.customer_id')
            ->selectRaw('customers.id, customers.name, customers.email, SUM(transactions.final_amount) as total_spent, COUNT(transactions.id) as total_transactions')
            ->groupBy('customers.id', 'customers.name', 'customers.email')
            ->orderByDesc('total_spent')
            ->limit(10);

        if ($branchId) {
            $query->where('transactions.branch_id', $branchId);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pelanggan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email'),
                Tables\Columns\TextColumn::make('total_transactions')
                    ->label('Jumlah Transaksi')
                    ->numeric()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Belanja')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
