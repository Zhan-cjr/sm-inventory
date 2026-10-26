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
    protected array $branchSales90DaysCache = [];

    public function clearCache(?string $branchId = null): void
    {
        $this->aiCache = [];
        $this->stockCalculationCache = [];
        $this->branchSales30DaysCache = [];
        $this->branchSales90DaysCache = [];

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

        // Fallback cerdas: Jika ada produk yang belum memiliki transaksi POS (misal cabang baru migrasi),
        // gunakan rata-rata bulanan dari histori import 3 bulan terakhir
        if ($branchId) {
            $latestPeriods = DB::table('product_monthly_sales_histories')
                ->where('branch_id', $branchId)
                ->select('year', 'month')
                ->distinct()
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->take(3)
                ->get();

            if ($latestPeriods->isNotEmpty()) {
                $histSales = DB::table('product_monthly_sales_histories')
                    ->where('branch_id', $branchId)
                    ->where(function ($q) use ($latestPeriods) {
                        foreach ($latestPeriods as $p) {
                            $q->orWhere(function ($sq) use ($p) {
                                $sq->where('year', $p->year)->where('month', $p->month);
                            });
                        }
                    })
                    ->groupBy('product_id')
                    ->select('product_id', DB::raw('ROUND(AVG(quantity_sold), 1) as avg_monthly'))
                    ->pluck('avg_monthly', 'product_id')
                    ->toArray();

                foreach ($histSales as $prodId => $avgQty) {
                    if (!isset($sales[$prodId]) || (float)$sales[$prodId] <= 0) {
                        $sales[$prodId] = (float)$avgQty;
                    }
                }
            }
        }

        $this->branchSales30DaysCache[$cacheKey] = $sales;
        return $sales;
    }

    protected function getBranchSales90Days(?string $branchId = null): array
    {
        $cacheKey = $branchId ?: 'all';
        if (isset($this->branchSales90DaysCache[$cacheKey])) {
            return $this->branchSales90DaysCache[$cacheKey];
        }

        $ninetyDaysAgo = Carbon::now()->subDays(90);
        $query = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.created_at', '>=', $ninetyDaysAgo)
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

        // Continuous Time-Series Stitching:
        // Only pull pre-migration months from ProductMonthlySalesHistory if the 90-day window reaches back before earliest live transaction
        if ($branchId) {
            $earliestTx = DB::table('transactions')
                ->where('branch_id', $branchId)
                ->where(function ($q) {
                    $q->where('is_voided', 0)->orWhereNull('is_voided');
                })
                ->min('created_at');
            $earliestLive = $earliestTx ? Carbon::parse($earliestTx) : null;

            // If branch is NOT live yet, or has been live for less than 90 days:
            if (!$earliestLive || $earliestLive->gt($ninetyDaysAgo)) {
                $refDate = Carbon::now();
                $preLiveCutoff = $earliestLive ? $earliestLive->copy()->startOfMonth() : $refDate;

                $preLivePeriods = [];
                for ($i = 1; $i <= 3; $i++) {
                    $dt = $refDate->copy()->subMonths($i);
                    if ($dt->lt($preLiveCutoff)) {
                        $preLivePeriods[] = ['year' => $dt->year, 'month' => $dt->month];
                    }
                }

                if (!empty($preLivePeriods)) {
                    $histQuery = DB::table('product_monthly_sales_histories')
                        ->where('branch_id', $branchId)
                        ->where(function ($q) use ($preLivePeriods) {
                            foreach ($preLivePeriods as $p) {
                                $q->orWhere(function ($sq) use ($p) {
                                    $sq->where('year', $p['year'])->where('month', $p['month']);
                                });
                            }
                        })
                        ->groupBy('product_id')
                        ->select('product_id', DB::raw('SUM(quantity_sold) as hist_qty'))
                        ->pluck('hist_qty', 'product_id');

                    foreach ($histQuery as $pId => $qty) {
                        $sales[$pId] = ((float)($sales[$pId] ?? 0)) + (float)$qty;
                    }
                }
            }
        }

        $this->branchSales90DaysCache[$cacheKey] = $sales;
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
        $sales90Days = 0;
        $isOosWithHistory = false;
        
        if ($current_qty < 0) {
            $suggested_qty = abs($current_qty) + $targetQty;
            $status = 'CRITICAL';
        } else if ($current_qty == 0) {
            if ($ads > 0) {
                $suggested_qty = $targetQty > 0 ? $targetQty : 1;
                $status = 'CRITICAL';
            } else {
                // Fallback Cerdas: Pasokan Macet (OOS) - Hanya jika ada penjualan aktif signifikan di 90 hari (>= 10 pcs)
                $branchSales90 = $this->getBranchSales90Days($stock->branch_id);
                $sales90Days = (float)($branchSales90[$stock->product_id] ?? 0);
                if ($sales90Days >= 10) {
                    $isOosWithHistory = true;
                    $activeAds = round($sales90Days / 90, 2);
                    $targetQty = (float)ceil($activeAds * $targetDays);
                    $suggested_qty = max(1, $targetQty);
                    $status = 'CRITICAL';
                } else {
                    $suggested_qty = 0;
                    $status = 'OK';
                }
            }
        } else if ($current_qty <= $reorderPoint && $ads > 0) {
            $suggested_qty = max(0, $targetQty - $current_qty);
            $status = $suggested_qty > 0 ? 'REORDER' : 'OK';
        } else if ($ads == 0) {
            // Ketika 30 hari penjualan = 0, tetapi stok menipis/kritis (di bawah min_qty, safety_stock, atau reorder_point)
            $isCritical = ($stock->min_qty > 0 && $current_qty <= (float)$stock->min_qty) 
                || ($current_qty <= $reorderPoint)
                || ($stock->safety_stock > 0 && $current_qty <= (float)$stock->safety_stock);
                
            if ($isCritical) {
                $branchSales90 = $this->getBranchSales90Days($stock->branch_id);
                $sales90Days = (float)($branchSales90[$stock->product_id] ?? 0);
                if ($sales90Days >= 10) {
                    $isOosWithHistory = true;
                    $activeAds = round($sales90Days / 90, 2);
                    $targetQty = (float)ceil($activeAds * $targetDays);
                    $suggested_qty = max(0, $targetQty - $current_qty);
                    $status = $suggested_qty > 0 ? 'REORDER' : 'OK';
                } else {
                    $suggested_qty = 0;
                    $status = 'OK';
                }
            } else {
                $suggested_qty = 0;
                $status = 'OK';
            }
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
            'sales_30d' => $sales30Days,
            'sales_90d' => $sales90Days,
            'is_oos_with_history' => $isOosWithHistory,
            'reorder_point' => $reorderPoint,
            'target_days' => $targetDays,
            'suggested_qty' => $suggested_qty,
            'status' => $status,
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
