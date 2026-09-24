<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use App\Services\SuggestedStockTransferService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuggestedStockTransferStats extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $service = app(SuggestedStockTransferService::class);
        $candidateStockIds = $service->getTransferCandidateStockIds();
        
        if (empty($candidateStockIds)) {
            return [
                Stat::make("Disparitas SKU", "0 SKU")
                    ->description("Stok semua cabang seimbang")
                    ->descriptionIcon("heroicon-m-check-circle")
                    ->color("success"),
                Stat::make("Kapasitas Muatan", "0 Pcs")
                    ->description("Tidak ada muatan armada")
                    ->descriptionIcon("heroicon-m-truck")
                    ->color("gray"),
                Stat::make("Modal Diselamatkan", "Rp 0")
                    ->description("Seluruh stok terkelola baik")
                    ->descriptionIcon("heroicon-m-banknotes")
                    ->color("gray"),
                Stat::make("Konektivitas Cabang", "0 Rute")
                    ->description("Jaringan P2P Store-to-Store")
                    ->descriptionIcon("heroicon-m-map-pin")
                    ->color("gray"),
            ];
        }

        $records = Stock::whereIn("id", $candidateStockIds)->with(["product", "branch"])->get();
        $summary = $service->getFleetSummary($records);

        $skuCount = (int) ($summary["total_items"] ?? 0);
        $unitCount = (int) ($summary["total_units"] ?? 0);
        $capital = (float) ($summary["total_capital_freed"] ?? 0);
        $routeCount = (int) ($summary["total_routes"] ?? 0);

        return [
            Stat::make("Disparitas SKU", number_format($skuCount, 0, ",", ".") . " SKU")
                ->description("Laris vs Macet antar-cabang")
                ->descriptionIcon("heroicon-m-arrows-right-left")
                ->color("primary")
                ->chart([3, 5, 4, 7, 5, 8, max(1, $skuCount)]),

            Stat::make("Kapasitas Muatan", number_format($unitCount, 0, ",", ".") . " Pcs")
                ->description("Siap angkut armada operasional")
                ->descriptionIcon("heroicon-m-truck")
                ->color("info")
                ->chart([100, 250, 400, 550, 700, max(10, $unitCount)]),

            Stat::make("Modal Diselamatkan", "Rp " . number_format($capital, 0, ",", "."))
                ->description("Hemat kas tanpa PO supplier")
                ->descriptionIcon("heroicon-m-banknotes")
                ->color("success")
                ->chart([500000, 1000000, 1800000, 2200000, max(100000, (int)$capital)]),

            Stat::make("Konektivitas Cabang", number_format($routeCount, 0, ",", ".") . " Rute")
                ->description("Jalur P2P aktif siap gerak")
                ->descriptionIcon("heroicon-m-map-pin")
                ->color("warning")
                ->chart([1, 1, 2, 2, 2, max(1, $routeCount)]),
        ];
    }
}
