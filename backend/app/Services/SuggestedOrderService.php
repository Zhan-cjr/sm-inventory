<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SuggestedOrderService
{
    const FORECAST_DAYS = 30;

    protected array $aiCache = [];
    protected array $stockCalculationCache = [];

    protected function fetchFromAI(string $branchId): array
    {
        if (isset($this->aiCache[$branchId])) {
            return $this->aiCache[$branchId];
        }

        $aiUrl = env('AI_SERVICE_URL', 'http://127.0.0.1:8001');

        try {
            $response = Http::timeout(2)->get($aiUrl . '/api/v1/ai/restock-suggestions', [
                'branch_id' => $branchId
            ]);

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                $indexed = [];
                foreach ($data as $item) {
                    $indexed[$item['product_id']] = $item;
                }
                $this->aiCache[$branchId] = $indexed;
                return $indexed;
            }
        } catch (\Exception $e) {
            Log::warning('AI Restock Service unavailable (' . $aiUrl . '): ' . $e->getMessage() . '. Falling back to database ADS calculation.');
        }

        $this->aiCache[$branchId] = [];
        return [];
    }

    public function calculateForBranch(string $branchId, array $filters = []): array
    {
        $aiData = $this->fetchFromAI($branchId);
        $suggestions = array_values($aiData);

        if (!empty($filters['product_id'])) {
            $suggestions = array_filter($suggestions, fn($item) => $item['product_id'] === $filters['product_id']);
        }
        if (!empty($filters['supplier_id'])) {
            $products = Product::where('supplier_id', $filters['supplier_id'])->pluck('id')->toArray();
            $suggestions = array_filter($suggestions, fn($item) => in_array($item['product_id'], $products));
        }

        return array_values($suggestions);
    }

    public function calculateForStock(Stock $stock): array
    {
        $cacheKey = $stock->id ?? ($stock->branch_id . '_' . $stock->product_id);
        if (isset($this->stockCalculationCache[$cacheKey])) {
            return $this->stockCalculationCache[$cacheKey];
        }

        $aiData = $this->fetchFromAI($stock->branch_id);
        
        if (isset($aiData[$stock->product_id])) {
            $aiItem = $aiData[$stock->product_id];
            $result = [
                'product_id' => $stock->product_id,
                'supplier_id' => $stock->product->supplier_id ?? null,
                'sku' => $stock->product->sku ?? '-',
                'name' => $stock->product->name ?? '-',
                'current_qty' => (float)$aiItem['current_qty'],
                'ads' => (float)$aiItem['ads'],
                'reorder_point' => (float)$aiItem['reorder_point'],
                'target_days' => (int)($aiItem['target_days'] ?? 30),
                'suggested_qty' => (float)$aiItem['suggested_qty'],
                'status' => $aiItem['status'],
                'lead_time' => (int)($aiItem['lead_time'] ?? 7),
            ];
            $this->stockCalculationCache[$cacheKey] = $result;
            return $result;
        }

        // Fallback: Smart Database Calculation for ADS & Reorder Point
        $current_qty = (float)$stock->quantity_on_hand;
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        $sales30Days = (float)DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.branch_id', $stock->branch_id)
            ->where('transaction_items.product_id', $stock->product_id)
            ->where('transactions.created_at', '>=', $thirtyDaysAgo)
            ->sum('transaction_items.quantity');

        $ads = round($sales30Days / 30, 2);
        $leadTime = (int)($stock->product->lead_time_days ?? 7);
        $targetDays = (int)($stock->desired_inventory_days ?? 30);
        $safetyStock = (float)ceil($ads * 3);
        $reorderPoint = (float)max(1, ceil(($ads * $leadTime) + $safetyStock));
        $targetQty = (float)ceil($ads * $targetDays);
        
        $suggested_qty = max(0, (float)ceil($targetQty - $current_qty));
        $status = 'OK';
        
        if ($current_qty <= 0) {
            $suggested_qty = max(1, abs($current_qty) + ($targetQty > 0 ? $targetQty : 10));
            $status = 'CRITICAL';
        } else if ($current_qty <= $reorderPoint || $suggested_qty > 0) {
            $status = 'REORDER';
        }

        $result = [
            'product_id' => $stock->product_id,
            'supplier_id' => $stock->product->supplier_id ?? null,
            'sku' => $stock->product->sku ?? '-',
            'name' => $stock->product->name ?? '-',
            'current_qty' => $current_qty,
            'ads' => $ads,
            'reorder_point' => $reorderPoint,
            'target_days' => $targetDays,
            'suggested_qty' => $suggested_qty,
            'status' => $status,
            'lead_time' => $leadTime,
        ];

        $this->stockCalculationCache[$cacheKey] = $result;
        return $result;
    }
}
