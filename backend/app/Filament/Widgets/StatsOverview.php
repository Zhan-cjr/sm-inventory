<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\Shift;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $branchId = auth()->user()->branch_id ?? $this->filters['branch_id'] ?? null;
        
        $salesToday = Transaction::where('created_at', '>=', $today)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('total_amount');
            
        $transCount = Transaction::where('created_at', '>=', $today)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();
            
        $activeShifts = Shift::where('status', 'OPEN')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();
            
        $lowStock = Product::whereHas('stocks', function($query) use ($branchId) {
            $query->where('quantity_on_hand', '<=', \DB::raw('COALESCE(min_qty, 10)'));
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        })->count();

        return [
            Stat::make('Penjualan Hari Ini', 'Rp ' . number_format($salesToday, 0, ',', '.'))
                ->description('Total pendapatan kotor')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Jumlah Transaksi', $transCount)
                ->description('Total struk tercetak hari ini')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),

            Stat::make('Shift Aktif', $activeShifts)
                ->description('Kasir yang sedang bertugas')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),

            Stat::make('Stok Menipis', $lowStock)
                ->description('Produk di bawah ambang batas')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
