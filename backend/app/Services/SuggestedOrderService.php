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

        $aiUrl = env('AI_SERVICE_URL', 'http://ai-service:8001');

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
        $query = Stock::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereHas('product', fn($q) => $q->where('is_active', true));

        if (!empty($filters['supplier_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('supplier_id', $filters['supplier_id']);
                if (!empty($filters['supplier_division_id'])) {
                    $q->where('supplier_division_id', $filters['supplier_division_id']);
                }
            });
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        $stocks = $query->with(['product'])->get();
        $suggestions = [];

        foreach ($stocks as $stock) {
            $res = $this->calculateForStock($stock);
            if ($res['suggested_qty'] > 0 || $res['status'] !== 'OK') {
                $suggestions[] = $res;
            }
        }

        return $suggestions;
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
        $reorderPoint = (float)max(0, ceil(($ads * $leadTime) + $safetyStock));
        $targetQty = (float)ceil($ads * $targetDays);
        
        $suggested_qty = 0;
        $status = 'OK';
        
        if ($current_qty < 0) {
            $suggested_qty = abs($current_qty) + $targetQty;
            $status = 'CRITICAL';
        } else if ($current_qty == 0) {
            if ($ads > 0) {
                $suggested_qty = $targetQty > 0 ? $targetQty : 1;
                $status = 'CRITICAL';
            } else {
                $suggested_qty = 0;
                $status = 'OK';
            }
        } else if ($current_qty <= $reorderPoint && $ads > 0) {
            $suggested_qty = max(0, $targetQty - $current_qty);
            $status = $suggested_qty > 0 ? 'REORDER' : 'OK';
        } else {
            $suggested_qty = 0;
            $status = 'OK';
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

    public function getRestockNeededStockIds(?string $branchId = null): array
    {
        $query = Stock::query()
            ->where('is_active', true)
            ->whereHas('product', fn($q) => $q->where('is_active', true));

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $stocks = $query->get();
        $neededStockIds = [];

        foreach ($stocks as $stock) {
            $result = $this->calculateForStock($stock);
            if ($result['suggested_qty'] > 0 || $result['status'] !== 'OK') {
                $neededStockIds[] = $stock->id;
            }
        }

        return $neededStockIds;
    }
}
