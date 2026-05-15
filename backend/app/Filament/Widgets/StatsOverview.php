<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\Shift;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->startOfDay();
        
        $salesToday = Transaction::where('created_at', '>=', $today)->sum('total_amount');
        $transCount = Transaction::where('created_at', '>=', $today)->count();
        $activeShifts = Shift::where('status', 'OPEN')->count();
        $lowStock = Product::whereHas('stocks', function($query) {
            $query->where('quantity_on_hand', '<=', 10); // Threshold 10
        })->count();

        return [
            Stat::make('Penjualan Hari Ini', 'Rp ' . number_format($salesToday, 0, ',', '.'))
                ->description('Total pendapatan kotor')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]), // Dummy chart trend

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
