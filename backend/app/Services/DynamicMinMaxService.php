<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DynamicMinMaxService
{
    const DEFAULT_LEAD_TIME_DAYS = 3;
    const MAX_HOLDING_DAYS = 45; // Plafon atas anti-overstock (maks 45 hari ADS)
    const MAX_MIN_DAYS = 20;     // Plafon atas untuk min (maks 20 hari ADS)

    /**
     * Calculate and update Min-Max dynamically for all products in a branch, or a specific product/supplier.
     *
     * @param string $branchId
     * @param string|null $productId
     * @param int $monthsCount Number of months to evaluate (default 12)
     * @param string|null $supplierId
     * @param string|null $supplierDivisionId
     * @return array Summary of recalculation
     */
    public function recalculateForBranch(
        string $branchId, 
        ?string $productId = null, 
        int $monthsCount = 12,
        ?string $supplierId = null,
        ?string $supplierDivisionId = null
    ): array
    {
        $branch = Branch::find($branchId);
        if (!$branch) {
            throw new \InvalidArgumentException("Branch not found: {$branchId}");
        }

        // 1. Determine the earliest live transaction date in SM Inventory for this branch
        $earliestLiveTx = DB::table('transactions')
            ->where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('is_voided', 0)->orWhereNull('is_voided');
            })
            ->min('created_at');

        $earliestLiveDate = $earliestLiveTx ? Carbon::parse($earliestLiveTx)->startOfMonth() : null;

        // 2. Build the list of evaluation periods (e.g. past 12 completed months)
        $periods = [];
        $current = Carbon::now()->startOfMonth();
        for ($i = 1; $i <= $monthsCount; $i++) {
            $dt = $current->copy()->subMonths($i);
            $periods[] = [
                'year' => $dt->year,
                'month' => $dt->month,
                'is_live' => $earliestLiveDate && $dt->greaterThanOrEqualTo($earliestLiveDate),
            ];
        }

        // 3. Aggregate monthly sales for each period across products
        // Structure: $productMonthlySales[product_id][period_index] = qty
        $productMonthlySales = [];

        foreach ($periods as $idx => $p) {
            $year = $p['year'];
            $month = $p['month'];

            if ($p['is_live']) {
                // Fetch from live SM Inventory POS transactions
                $query = DB::table('transaction_items')
                    ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
                    ->where('transactions.branch_id', $branchId)
                    ->where(function ($q) {
                        $q->where('transactions.is_voided', 0)->orWhereNull('transactions.is_voided');
                    })
                    ->whereYear('transactions.created_at', $year)
                    ->whereMonth('transactions.created_at', $month)
                    ->select('transaction_items.product_id', DB::raw('SUM(transaction_items.quantity) as total_qty'))
                    ->groupBy('transaction_items.product_id');

                if ($productId) {
                    $query->where('transaction_items.product_id', $productId);
                }

                $rows = $query->get();
                foreach ($rows as $r) {
                    $productMonthlySales[$r->product_id][$idx] = (float)$r->total_qty;
                }
            } else {
                // Fetch from historical migration table
                $query = DB::table('product_monthly_sales_histories')
                    ->where('branch_id', $branchId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->select('product_id', DB::raw('SUM(quantity_sold) as total_qty'))
                    ->groupBy('product_id');

                if ($productId) {
                    $query->where('product_id', $productId);
                }

                $rows = $query->get();
                foreach ($rows as $r) {
                    $productMonthlySales[$r->product_id][$idx] = (float)$r->total_qty;
                }
            }
        }

        // 4. Query stocks to update
        $stocksQuery = Stock::where('stocks.branch_id', $branchId)->with(['product']);
        if ($productId) {
            $stocksQuery->where('stocks.product_id', $productId);
        }
        if ($supplierId) {
            $stocksQuery->where(function ($q) use ($supplierId, $supplierDivisionId) {
                $q->where(function ($sq) use ($supplierId, $supplierDivisionId) {
                    $sq->where('stocks.supplier_id', $supplierId);
                    if ($supplierDivisionId) {
                        $sq->where('stocks.supplier_division_id', $supplierDivisionId);
                    }
                })->orWhere(function ($sq) use ($supplierId, $supplierDivisionId) {
                    $sq->whereNull('stocks.supplier_id')
                       ->whereHas('product', function ($pq) use ($supplierId, $supplierDivisionId) {
                           $pq->where('supplier_id', $supplierId);
                           if ($supplierDivisionId) {
                               $pq->where('supplier_division_id', $supplierDivisionId);
                           }
                       });
                });
            });
        }
        $stocks = $stocksQuery->get();

        $updatedCount = 0;
        $itemsRecalculated = [];
        $bulkUpdates = [];

        foreach ($stocks as $stock) {
            $pId = $stock->product_id;

            // Fill 0 for any missing months in the evaluation period
            $salesSeries = [];
            for ($idx = 0; $idx < $monthsCount; $idx++) {
                $salesSeries[] = $productMonthlySales[$pId][$idx] ?? 0.0;
            }

            // Lead time & Target Inventory Days mengacu ke hierarki Supplier / Sub-Divisi / Cabang
            $effSup = $stock->supplier_id ?: ($stock->product?->supplier_id ?? null);
            $effDiv = $stock->supplier_id ? $stock->supplier_division_id : ($stock->product?->supplier_division_id ?? null);

            if ($effSup) {
                $cycle = SuggestedOrderService::resolveInventoryCycle($effSup, $effDiv, $branchId);
                $leadTimeDays = (int)$cycle['lead_time'];
                $targetDays = (int)$cycle['target_days'];
            } else {
                $leadTimeDays = $stock->lead_time > 0 ? (int)$stock->lead_time : self::DEFAULT_LEAD_TIME_DAYS;
                $targetDays = $stock->desired_inventory_days > 0 ? (int)$stock->desired_inventory_days : 14;
            }

            // Calculate Dynamic Statistics (Standard Retail Periodic Review Formula)
            $calc = $this->calculateDynamicStats($salesSeries, $leadTimeDays, $targetDays);

            $oldMin = (int)$stock->min_qty;
            $oldMax = (int)$stock->max_qty;

            // Collect update payload for high-performance bulk update
            $bulkUpdates[] = [
                'id' => $stock->id,
                'min_qty' => $calc['min_qty'],
                'max_qty' => $calc['max_qty'],
                'safety_stock' => $calc['safety_stock'],
                'lead_time' => $leadTimeDays,
                'desired_inventory_days' => $targetDays,
            ];

            $updatedCount++;

            if ($updatedCount <= 50 || $stock->product_id === $productId) {
                $itemsRecalculated[] = [
                    'product_id' => $stock->product_id,
                    'lead_time' => $leadTimeDays,
                    'target_days' => $targetDays,
                    'avg_monthly_sales' => $calc['avg_monthly_sales'],
                    'ads' => $calc['ads'],
                    'std_dev' => $calc['std_dev'],
                    'safety_stock' => $calc['safety_stock'],
                    'old_min' => $oldMin,
                    'new_min' => $calc['min_qty'],
                    'old_max' => $oldMax,
                    'new_max' => $calc['max_qty'],
                ];
            }
        }

        // Ultra-fast bulk updates in chunks of 500 (replaces 5,000+ individual round-trip queries over remote MySQL network)
        if (!empty($bulkUpdates)) {
            $now = now()->toDateTimeString();
            foreach (array_chunk($bulkUpdates, 500) as $chunk) {
                $ids = [];
                $minCases = '';
                $maxCases = '';
                $safetyCases = '';
                $ltCases = '';
                $targetCases = '';
                $minBindings = [];
                $maxBindings = [];
                $safetyBindings = [];
                $ltBindings = [];
                $targetBindings = [];

                foreach ($chunk as $item) {
                    $ids[] = $item['id'];

                    $minCases .= "WHEN ? THEN ? ";
                    $minBindings[] = $item['id'];
                    $minBindings[] = $item['min_qty'];

                    $maxCases .= "WHEN ? THEN ? ";
                    $maxBindings[] = $item['id'];
                    $maxBindings[] = $item['max_qty'];

                    $safetyCases .= "WHEN ? THEN ? ";
                    $safetyBindings[] = $item['id'];
                    $safetyBindings[] = $item['safety_stock'];

                    $ltCases .= "WHEN ? THEN ? ";
                    $ltBindings[] = $item['id'];
                    $ltBindings[] = $item['lead_time'];

                    $targetCases .= "WHEN ? THEN ? ";
                    $targetBindings[] = $item['id'];
                    $targetBindings[] = $item['desired_inventory_days'];
                }

                $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));
                $bindings = array_merge($minBindings, $maxBindings, $safetyBindings, $ltBindings, $targetBindings, $ids);

                DB::update("
                    UPDATE stocks 
                    SET 
                        min_qty = CASE id {$minCases} END,
                        max_qty = CASE id {$maxCases} END,
                        safety_stock = CASE id {$safetyCases} END,
                        lead_time = CASE id {$ltCases} END,
                        desired_inventory_days = CASE id {$targetCases} END,
                        updated_at = '{$now}'
                    WHERE id IN ({$idPlaceholders})
                ", $bindings);
            }
        }

        // Clear suggested order cache
        app(SuggestedOrderService::class)->clearCache($branchId);

        return [
            'branch_id' => $branchId,
            'branch_name' => $branch->name,
            'total_stocks_evaluated' => $stocks->count(),
            'total_stocks_updated' => $updatedCount,
            'months_evaluated' => $monthsCount,
            'earliest_live_pos_date' => $earliestLiveDate ? $earliestLiveDate->format('Y-m') : 'None (All Migration)',
            'sample_results' => $itemsRecalculated,
        ];
    }

    /**
     * Hitung ulang Min-Max Dinamis untuk seluruh produk milik Supplier / Sub-Divisi tertentu
     * pada satu cabang atau semua cabang.
     */
    public function recalculateForSupplier(string $supplierId, ?string $divisionId = null, ?string $branchId = null, int $monthsCount = 12): array
    {
        $branches = $branchId ? Branch::where('id', $branchId)->get() : Branch::all();
        $totalEvaluated = 0;
        $totalUpdated = 0;

        foreach ($branches as $branch) {
            $res = $this->recalculateForBranch($branch->id, null, $monthsCount, $supplierId, $divisionId);
            $totalEvaluated += $res['total_stocks_evaluated'];
            $totalUpdated += $res['total_stocks_updated'];
        }

        return [
            'total_stocks_evaluated' => $totalEvaluated,
            'total_stocks_updated' => $totalUpdated,
        ];
    }

    /**
     * Compute statistical values adapting Standard Retail Periodic Review (s, S) policy:
     *
     * Avg = Sum / N
     * ADS = Avg / 30
     * SD  = sqrt( Sum((x - Avg)^2) / N )  [Population STDEV]
     * LT_month = LT / 30
     * SS  = ceil( SD * sqrt(LT_month) )
     * Min = ceil( (Avg * LT_month) + SS )   [Reorder Point for Lead Time]
     * Max = Min + ceil( ADS * TargetDays )   [Order-Up-To Level for Target Inventory Cycle]
     */
    public function calculateDynamicStats(array $monthlySales, int $leadTimeDays, int $targetInventoryDays = 14): array
    {
        $n = count($monthlySales);
        if ($n === 0) {
            return [
                'avg_monthly_sales' => 0.0,
                'ads' => 0.0,
                'std_dev' => 0.0,
                'safety_stock' => 0,
                'min_qty' => 0,
                'max_qty' => 0,
            ];
        }

        // 1. Average Monthly Sales
        $sum = array_sum($monthlySales);
        $avg = $sum / $n;
        $ads = $avg / 30.0;

        // 2. Standard Deviation (Population STDEV.P)
        $varianceSum = 0.0;
        foreach ($monthlySales as $val) {
            $varianceSum += pow($val - $avg, 2);
        }
        $stdDev = sqrt($varianceSum / $n);

        // If product has zero sales across all months
        if ($avg <= 0.0001) {
            return [
                'avg_monthly_sales' => 0.0,
                'ads' => 0.0,
                'std_dev' => 0.0,
                'safety_stock' => 0,
                'min_qty' => 0,
                'max_qty' => 0,
            ];
        }

        // 3. Lead Time Factor (in months)
        $ltFactor = $leadTimeDays / 30.0;

        // 4. Safety Stock (SS) = SD * sqrt(LT / 30)
        $safetyStock = (int)ceil($stdDev * sqrt($ltFactor));

        // 5. Raw Min = (Avg * (LT / 30)) + SS [Mengamankan toko selama waktu kirim Lead Time]
        $rawMin = (int)ceil(($avg * $ltFactor) + $safetyStock);

        // 6. Raw Max = Min + Kebutuhan Penjualan selama Siklus Target Inventori (Order Cycle)
        $cycleDemand = (int)ceil($ads * max(1, $targetInventoryDays));
        $rawMax = $rawMin + max(1, $cycleDemand);

        // 7. Safety Rails (Plafon Pengaman Anti-Overstock)
        // Minimum cannot be 0 for active selling product
        $minQty = max(1, $rawMin);
        $maxQty = max($minQty + 1, $rawMax);

        // Upper bound: Max should not exceed maximum holding days
        $effectiveMaxCapDays = max(self::MAX_HOLDING_DAYS, $targetInventoryDays + $leadTimeDays + 7);
        $maxCap = (int)ceil($ads * $effectiveMaxCapDays);
        if ($maxCap > 0) {
            $maxQty = min($maxQty, max($minQty + 1, $maxCap));
        }

        // Upper bound: Min should not exceed 20 days of ADS
        $minCap20Days = (int)ceil($ads * self::MAX_MIN_DAYS);
        if ($minCap20Days > 0) {
            $minQty = min($minQty, max(1, $minCap20Days));
            // Ensure Max is still >= Min + 1
            $maxQty = max($minQty + 1, $maxQty);
        }

        return [
            'avg_monthly_sales' => round($avg, 2),
            'ads' => round($ads, 2),
            'std_dev' => round($stdDev, 2),
            'safety_stock' => $safetyStock,
            'min_qty' => $minQty,
            'max_qty' => $maxQty,
        ];
    }
}
