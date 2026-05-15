<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SuggestedOrderService
{
    const LOOKBACK_DAYS = 90;
    const FORECAST_DAYS = 30;

    public function calculateForBranch(string $branchId, array $filters = []): array
    {
        $products = Product::where('is_active', true)->limit(100)->get();
        $suggestions = [];

        foreach ($products as $product) {
            $stock = Stock::where('branch_id', $branchId)->where('product_id', $product->id)->first();
            if (!$stock) continue;

            $suggestions[] = $this->calculateForProduct($product, $stock, $branchId);
        }

        return $suggestions;
    }

    private function calculateForProduct(Product $product, Stock $stock, string $branchId): array
    {
        $avgDailySales = 5; // Mock data since real data isn't seeded
        $safetyStock = 10;
        $reorderPoint = ($avgDailySales * $product->lead_time_days) + $safetyStock;

        $suggestedQty = 0;
        if ($stock->quantity_on_hand <= $reorderPoint) {
            $suggestedQty = max(50, $avgDailySales * self::FORECAST_DAYS);
        }

        return [
            'productId' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'currentQty' => $stock->quantity_on_hand,
            'suggestedQty' => $suggestedQty
        ];
    }
}
