<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SuggestedOrderService
{
    const FORECAST_DAYS = 30;

    protected array $aiCache = [];
    protected array $stockCalculationCache = [];
    protected array $branchSales30DaysCache = [];

    public function clearCache(?string $branchId = null): void
    {
        $this->aiCache = [];
        $this->stockCalculationCache = [];
        $this->branchSales30DaysCache = [];

        if ($branchId) {
            Cache::forget("suggested_orders_ai_{$branchId}");
            Cache::forget("restock_needed_stock_ids_{$branchId}");
        } else {
            Cache::forget("suggested_orders_ai_all");
            Cache::forget("restock_needed_stock_ids_all");
            try {
                $branchIds = \App\Models\Branch::pluck('id');
                foreach ($branchIds as $bId) {
                    Cache::forget("suggested_orders_ai_{$bId}");
                    Cache::forget("restock_needed_stock_ids_{$bId}");
                }
            } catch (\Exception $e) {
                // Ignore if database connection is not ready
            }
        }
    }

    protected function getBranchSales30Days(?string $branchId = null): array
    {
        $cacheKey = $branchId ?: 'all';
        if (isset($this->branchSales30DaysCache[$cacheKey])) {
            return $this->branchSales30DaysCache[$cacheKey];
        }

        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $query = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.created_at', '>=', $thirtyDaysAgo)
            ->where(function ($q) {
                $q->where('transactions.is_voided', 0)->orWhereNull('transactions.is_voided');
            });

        if ($branchId) {
            $query->where('transactions.branch_id', $branchId);
        }

        $sales = $query->groupBy('transaction_items.product_id')
            ->select('transaction_items.product_id', DB::raw('SUM(transaction_items.quantity) as total_qty'))
            ->pluck('total_qty', 'product_id')
            ->toArray();

        $this->branchSales30DaysCache[$cacheKey] = $sales;
        return $sales;
    }

    protected function fetchFromAI(?string $branchId = null): array
    {
        $cacheBranchKey = $branchId ?: 'all';
        if (isset($this->aiCache[$cacheBranchKey])) {
            return $this->aiCache[$cacheBranchKey];
        }

        $cachedData = Cache::remember("suggested_orders_ai_{$cacheBranchKey}", now()->addMinutes(30), function () use ($branchId) {
            $aiUrl = env('AI_SERVICE_URL', 'http://ai-service:8001');

            try {
                $params = [];
                if ($branchId) {
                    $params['branch_id'] = $branchId;
                }
                $response = Http::timeout(10)->get($aiUrl . '/api/v1/ai/restock-suggestions', $params);

                if ($response->successful()) {
                    $data = $response->json()['data'] ?? [];
                    $indexed = [];
                    foreach ($data as $item) {
                        $indexed[$item['product_id']] = $item;
                    }
                    return $indexed;
                }
            } catch (\Exception $e) {
                Log::warning('AI Restock Service unavailable (' . $aiUrl . '): ' . $e->getMessage() . '. Falling back to database ADS calculation.');
            }

            return [];
        });

        $this->aiCache[$cacheBranchKey] = $cachedData;
        return $cachedData;
    }

    public function calculateForBranch(string $branchId, array $filters = []): array
    {
        $query = Stock::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereHas('product', fn($q) => $q->where('is_active', true));

        if (!empty($filters['supplier_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where(function ($sq) use ($filters) {
                    $sq->where('stocks.supplier_id', $filters['supplier_id']);
                    if (!empty($filters['supplier_division_id'])) {
                        $sq->where('stocks.supplier_division_id', $filters['supplier_division_id']);
                    }
                })->orWhere(function ($sq) use ($filters) {
                    $sq->whereNull('stocks.supplier_id')
                       ->whereHas('product', function ($pq) use ($filters) {
                           $pq->where('supplier_id', $filters['supplier_id']);
                           if (!empty($filters['supplier_division_id'])) {
                               $pq->where('supplier_division_id', $filters['supplier_division_id']);
                           }
                       });
                });
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

        $effectiveSupplierId = $stock->supplier_id ?: ($stock->product->supplier_id ?? null);
        $effectiveDivisionId = $stock->supplier_id ? $stock->supplier_division_id : ($stock->product->supplier_division_id ?? null);

        $aiData = $this->fetchFromAI($stock->branch_id);
        
        if (isset($aiData[$stock->product_id])) {
            $aiItem = $aiData[$stock->product_id];
            $result = [
                'product_id' => $stock->product_id,
                'supplier_id' => $effectiveSupplierId,
                'supplier_division_id' => $effectiveDivisionId,
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

        // Fallback: Smart Database Calculation for ADS & Reorder Point (Bulk Optimized)
        $current_qty = (float)$stock->quantity_on_hand;
        $branchSales = $this->getBranchSales30Days($stock->branch_id);
        $sales30Days = (float)($branchSales[$stock->product_id] ?? 0);

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
            'supplier_id' => $effectiveSupplierId,
            'supplier_division_id' => $effectiveDivisionId,
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
        $cacheKey = "restock_needed_stock_ids_" . ($branchId ?: 'all');
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($branchId) {
            $query = Stock::query()
                ->where('is_active', true)
                ->whereHas('product', fn($q) => $q->where('is_active', true));

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $stocks = $query->with(['product'])->get();
            $neededStockIds = [];

            foreach ($stocks as $stock) {
                $result = $this->calculateForStock($stock);
                if ($result['suggested_qty'] > 0 || $result['status'] !== 'OK') {
                    $neededStockIds[] = $stock->id;
                }
            }

            return $neededStockIds;
        });
    }
}
