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
        $stocks = Stock::with('product')
            ->where('branch_id', $branchId)
            ->when(isset($filters['product_id']), fn($q) => $q->where('product_id', $filters['product_id']))
            ->when(isset($filters['supplier_id']), function($q) use ($filters) {
                $q->whereHas('product', fn($pq) => $pq->where('supplier_id', $filters['supplier_id']));
            })
            ->get();

        $suggestions = [];

        foreach ($stocks as $stock) {
            $suggestions[] = $this->calculateForStock($stock);
        }

        return $suggestions;
    }

    public function calculateForStock(Stock $stock): array
    {
        // Calculate ADS (Average Daily Sales) based on last 90 days
        $startDate = Carbon::now()->subDays(self::LOOKBACK_DAYS);
        
        $totalSold = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.branch_id', $stock->branch_id)
            ->where('transaction_items.product_id', $stock->product_id)
            ->where('transactions.transaction_date', '>=', $startDate)
            ->sum('transaction_items.quantity');

        $ads = $totalSold / self::LOOKBACK_DAYS;
        
        // Use stock-specific settings or defaults
        $leadTime = $stock->lead_time ?? 3;
        $safetyStock = $stock->safety_stock ?? 0;
        $desiredDays = $stock->desired_inventory_days ?? 14;

        // Reorder Point = (ADS * Lead Time) + Safety Stock
        $reorderPoint = ($ads * $leadTime) + $safetyStock;

        $suggestedQty = 0;
        $status = 'OK';

        if ($stock->quantity_on_hand <= $reorderPoint) {
            // Suggest enough to cover desired days plus lead time
            $targetQty = ($ads * ($desiredDays + $leadTime)) + $safetyStock;
            $suggestedQty = ceil(max(0, $targetQty - $stock->quantity_on_hand));
            $status = $stock->quantity_on_hand <= ($ads * 1) ? 'CRITICAL' : 'REORDER';
        }

        return [
            'product_id' => $stock->product_id,
            'supplier_id' => $stock->product->supplier_id,
            'sku' => $stock->product->sku,
            'name' => $stock->product->name,
            'current_qty' => (float)$stock->quantity_on_hand,
            'ads' => round($ads, 2),
            'reorder_point' => round($reorderPoint, 2),
            'suggested_qty' => (float)$suggestedQty,
            'status' => $status,
            'lead_time' => $leadTime,
        ];
    }
}
