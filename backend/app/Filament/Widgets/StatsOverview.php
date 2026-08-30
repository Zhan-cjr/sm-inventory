<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Stock;
use App\Models\Shift;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $branchId = auth()->user()->branch_id ?? $this->filters['branch_id'] ?? null;
        
        $todayTransactions = Transaction::where('created_at', '>=', $today)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $salesToday = (float) $todayTransactions->sum('final_amount');
        $transCount = $todayTransactions->count();

        // Calculate Gross Profit & Margin
        $cogsToday = 0;
        foreach ($todayTransactions as $tx) {
            $cogsToday += $tx->cogs;
        }

        $grossProfitToday = max(0, $salesToday - $cogsToday);
        $profitMargin = $salesToday > 0 ? round(($grossProfitToday / $salesToday) * 100, 1) : 0;
            
        // Low Stock Count
        $lowStock = Stock::where('is_active', true)
            ->whereRaw('quantity_on_hand <= COALESCE(min_qty, 3)')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();

        // Dead Stock Count (Stock > 0 but 0 sales in 60 days)
        $sixtyDaysAgo = Carbon::now()->subDays(60);
        $recentActiveProductIds = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.created_at', '>=', $sixtyDaysAgo)
            ->when($branchId, fn($q) => $q->where('transactions.branch_id', $branchId))
            ->pluck('transaction_items.product_id')
            ->unique();

        $deadStockCount = Stock::where('is_active', true)
            ->where('quantity_on_hand', '>', 0)
            ->whereNotIn('product_id', $recentActiveProductIds)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();

        return [
            Stat::make('Penjualan Hari Ini', 'Rp ' . number_format($salesToday, 0, ',', '.'))
                ->description("{$transCount} transaksi kasir")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Laba Kotor (Gross Profit)', 'Rp ' . number_format($grossProfitToday, 0, ',', '.'))
                ->description("Margin: {$profitMargin}% dari omset")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Stok Menipis (Kritis)', $lowStock)
                ->description('Produk perlu kulakan')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Dead Stock (> 60 Hari)', $deadStockCount)
                ->description('Produk modal tertahan')
                ->descriptionIcon('heroicon-m-archive-box-x-mark')
                ->color('warning'),
        ];
    }
}
