<?php

namespace App\Filament\Resources\Products\Widgets;

use App\Models\Product;
use App\Services\RetailIntelligenceService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class ProductPerformanceWidget extends BaseWidget
{
    public ?Model $record = null;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /** @var Product|null $product */
        $product = $this->record;

        if (!$product) {
            return [];
        }

        // User cabang MUTLAK terkunci ke cabangnya sendiri
        $userBranchId = auth()->user()?->branch_id;
        $branchId = !empty($userBranchId) 
            ? $userBranchId 
            : (request()->query('branch_id') ?: session('active_selected_branch_id'));

        $stats = RetailIntelligenceService::getPerformanceStats($product, $branchId);

        // Stat 1: Sisa Stok Fisik
        $statStock = Stat::make("Sisa Stok Fisik", number_format($stats["qoh"], 0, ",", ".") . " " . $stats["unit"])
            ->description($stats["stockDesc"])
            ->descriptionIcon($stats["stockIcon"])
            ->color($stats["stockColor"])
            ->chart($stats["salesChart"]);

        // Stat 2: Putaran Barang
        $statVelocity = Stat::make("Putaran Barang", $stats["lajuLabel"])
            ->description("Keluar: ~" . $stats["dailyAvg"] . " " . $stats["unit"] . "/hari (Sebulan: " . (int)$stats["sales30Days"] . " " . $stats["unit"] . ")")
            ->descriptionIcon("heroicon-m-arrow-trending-up")
            ->color($stats["lajuColor"])
            ->chart($stats["salesChart"]);

        // Stat 3: Produktivitas Modal Stok
        $statGmroi = Stat::make("Produktivitas Modal Stok", $stats["modalLabel"])
            ->description($stats["modalDesc"])
            ->descriptionIcon("heroicon-m-banknotes")
            ->color($stats["modalColor"]);

        // Stat 4: Margin Laba Eceran
        $statMargin = Stat::make("Persentase Laba Eceran", ($stats["margin1"] > 0 ? "+" . number_format($stats["margin1"], 2) . "%" : "0%"))
            ->description($stats["marginDesc"])
            ->descriptionIcon("heroicon-m-currency-dollar")
            ->color($stats["marginColor"]);

        return [
            $statStock,
            $statVelocity,
            $statGmroi,
            $statMargin,
        ];
    }
}