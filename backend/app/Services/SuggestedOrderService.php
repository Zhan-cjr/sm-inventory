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
    protected array $branchOnOrderCache = [];
    protected array $branchWmaSalesCache = [];

    public function clearCache(?string $branchId = null): void
    {
        $this->aiCache = [];
        $this->stockCalculationCache = [];
        $this->branchSales30DaysCache = [];
        $this->branchSales90DaysCache = [];
        $this->branchOnOrderCache = [];
        $this->branchWmaSalesCache = [];

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

    /**
     * Ekstrak isi kemasan karton/dus dari metadata atau teks nama produk (misal: /24, /12, P10/25, ISI 40).
     */
    public static function extractCartonQuantity($product): ?int
    {
        if (!$product) return null;

        // 1. Cek metadata eksplisit
        $meta = is_array($product->metadata) ? $product->metadata : (json_decode($product->metadata ?? '[]', true) ?? []);
        if (!empty($meta['carton_quantity']) && (int)$meta['carton_quantity'] > 1) {
            return (int)$meta['carton_quantity'];
        }
        if (!empty($meta['pcs_per_carton']) && (int)$meta['pcs_per_carton'] > 1) {
            return (int)$meta['pcs_per_carton'];
        }

        // 2. Deteksi dari nama produk
        $name = $product->name ?? '';

        // Pola A: Angka dalam kurung/siku di dekat akhir nama: misal (24), [40], (12)*, (30)
        // Hindari kode produk 4 digit seperti (5078) atau (2184)
        if (preg_match('/[\(\[]\s*(\d{1,3})\s*[\)\]]\s*\*?\s*(?:\(\d+\))?\s*$/i', $name, $m)) {
            $val = (int)$m[1];
            if ($val >= 2 && $val <= 250) {
                return $val;
            }
        }

        // Pola B: Slash atau asterisk di dekat akhir nama: misal /24, /12, /6, /72, P10/25, *24
        // Jika ada beberapa slash (misal 900/380/12), ambil slash TERAKHIR yang merupakan kemasan dus
        if (preg_match_all('/(?:\/|\*)\s*(\d{1,3})\s*(?:PCS)?\s*(?:\(\d+\)|\*|\s*$)/i', $name, $allM)) {
            $last = end($allM[1]);
            $val = (int)$last;
            if ($val >= 2 && $val <= 250) {
                return $val;
            }
        }

        // Pola C: Keyword eksplisit: ISI 24, @24, X 24
        if (preg_match('/\b(?:isi|x|@)\s*(\d{1,3})\s*(?:pcs)?\b/i', $name, $m)) {
            $val = (int)$m[1];
            if ($val >= 2 && $val <= 250) {
                return $val;
            }
        }

        return null;
    }

    /**
     * Hitung total kuantitas barang yang sedang dipesan (PO On-Order / Dalam Perjalanan / Belum Diterima).
     */
    public function getBranchOnOrderQuantities(?string $branchId = null): array
    {
        $cacheKey = $branchId ?: 'all';
        if (isset($this->branchOnOrderCache[$cacheKey])) {
            return $this->branchOnOrderCache[$cacheKey];
        }

        $query = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->whereIn('purchase_orders.status', ['approved', 'pending_approval']);

        if ($branchId) {
            $query->where('purchase_orders.branch_id', $branchId);
        }

        $onOrder = $query->groupBy('purchase_order_items.product_id')
            ->select(
                'purchase_order_items.product_id',
                DB::raw('SUM(GREATEST(0, purchase_order_items.quantity_ordered - COALESCE(purchase_order_items.quantity_received, 0))) as total_on_order')
            )
            ->pluck('total_on_order', 'product_id')
            ->map(fn($v) => (float)$v)
            ->toArray();

        $this->branchOnOrderCache[$cacheKey] = $onOrder;
        return $onOrder;
    }

    /**
     * Hitung Weighted Moving Average (WMA 3 Bulan) sesuai Sheet 2 Forecast_PO.xlsx:
     * P1 (30 hari terakhir): Bobot 3 (50%)
     * P2 (31-60 hari lalu):  Bobot 2 (33.3%)
     * P3 (61-90 hari lalu):  Bobot 1 (16.7%)
     * WMA Monthly = (3*P1 + 2*P2 + 1*P3) / 6
     * ADS = WMA Monthly / 30
     */
    public function getBranchWmaSales(?string $branchId = null): array
    {
        $cacheKey = $branchId ?: 'all';
        if (isset($this->branchWmaSalesCache[$cacheKey])) {
            return $this->branchWmaSalesCache[$cacheKey];
        }

        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);
        $ninetyDaysAgo = $now->copy()->subDays(90);

        // Ambil transaksi live POS 3 periode
        $queryBase = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where(function ($q) {
                $q->where('transactions.is_voided', 0)->orWhereNull('transactions.is_voided');
            });
        if ($branchId) {
            $queryBase->where('transactions.branch_id', $branchId);
        }

        $p1Sales = (clone $queryBase)
            ->whereBetween('transactions.created_at', [$thirtyDaysAgo, $now])
            ->groupBy('transaction_items.product_id')
            ->select('transaction_items.product_id', DB::raw('SUM(transaction_items.quantity) as qty'))
            ->pluck('qty', 'product_id')
            ->toArray();

        $p2Sales = (clone $queryBase)
            ->whereBetween('transactions.created_at', [$sixtyDaysAgo, $thirtyDaysAgo])
            ->groupBy('transaction_items.product_id')
            ->select('transaction_items.product_id', DB::raw('SUM(transaction_items.quantity) as qty'))
            ->pluck('qty', 'product_id')
            ->toArray();

        $p3Sales = (clone $queryBase)
            ->whereBetween('transactions.created_at', [$ninetyDaysAgo, $sixtyDaysAgo])
            ->groupBy('transaction_items.product_id')
            ->select('transaction_items.product_id', DB::raw('SUM(transaction_items.quantity) as qty'))
            ->pluck('qty', 'product_id')
            ->toArray();

        // Sambung histori Excel sebelum migrasi jika cabang aktif < 90 hari
        if ($branchId) {
            $earliestTx = DB::table('transactions')
                ->where('branch_id', $branchId)
                ->where(function ($q) {
                    $q->where('is_voided', 0)->orWhereNull('is_voided');
                })
                ->min('created_at');
            $earliestLive = $earliestTx ? Carbon::parse($earliestTx) : null;

            if (!$earliestLive || $earliestLive->gt($ninetyDaysAgo)) {
                $preLiveCutoff = $earliestLive ? $earliestLive->copy()->startOfMonth() : $now;

                for ($m = 1; $m <= 3; $m++) {
                    $dt = $now->copy()->subMonths($m);
                    if ($dt->lt($preLiveCutoff)) {
                        $hist = DB::table('product_monthly_sales_histories')
                            ->where('branch_id', $branchId)
                            ->where('year', $dt->year)
                            ->where('month', $dt->month)
                            ->groupBy('product_id')
                            ->select('product_id', DB::raw('SUM(quantity_sold) as qty'))
                            ->pluck('qty', 'product_id');

                        foreach ($hist as $pId => $qty) {
                            if ($m == 1 && empty($p1Sales[$pId])) {
                                $p1Sales[$pId] = (float)$qty;
                            } elseif ($m == 2 && empty($p2Sales[$pId])) {
                                $p2Sales[$pId] = (float)$qty;
                            } elseif ($m == 3 && empty($p3Sales[$pId])) {
                                $p3Sales[$pId] = (float)$qty;
                            }
                        }
                    }
                }
            }
        }

        $allProductIds = array_unique(array_merge(
            array_keys($p1Sales),
            array_keys($p2Sales),
            array_keys($p3Sales)
        ));

        $wmaResult = [];
        foreach ($allProductIds as $pId) {
            $v1 = (float)($p1Sales[$pId] ?? 0);
            $v2 = (float)($p2Sales[$pId] ?? 0);
            $v3 = (float)($p3Sales[$pId] ?? 0);

            // Rumus Sheet 2: WMA 3 Periode dengan Bobot 3, 2, 1
            $wmaMonthly = round(((3 * $v1) + (2 * $v2) + (1 * $v3)) / 6, 2);
            $ads = round($wmaMonthly / 30, 2);

            $wmaResult[$pId] = [
                'p1' => $v1,
                'p2' => $v2,
                'p3' => $v3,
                'total_90d' => round($v1 + $v2 + $v3, 1),
                'wma_monthly' => $wmaMonthly,
                'ads' => $ads,
            ];
        }

        $this->branchWmaSalesCache[$cacheKey] = $wmaResult;
        return $wmaResult;
    }

    protected function fetchFromAI(?string $branchId = null): array
    {
        return [];
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

        // Ambil kuantitas PO yang sedang berjalan (On-Order) dan estimasi isi karton
        $onOrderMap = $this->getBranchOnOrderQuantities($stock->branch_id);
        $onOrderQty = (float)($onOrderMap[$stock->product_id] ?? 0);
        $cartonSize = self::extractCartonQuantity($stock->product);

        // Smart Calculation: WMA 3 Bulan (Sesuai Sheet 2 Weight Moving Average di Forecast_PO.xlsx)
        $current_qty = (float)$stock->quantity_on_hand;
        $wmaSales = $this->getBranchWmaSales($stock->branch_id);
        $prodWma = $wmaSales[$stock->product_id] ?? null;

        $ads = $prodWma ? (float)$prodWma['ads'] : 0.0;
        $sales30Days = $prodWma ? (float)$prodWma['p1'] : 0.0;
        $sales90Days = $prodWma ? (float)$prodWma['total_90d'] : 0.0;
        $wmaMonthly = $prodWma ? (float)$prodWma['wma_monthly'] : 0.0;

        $leadTime = (int)($stock->lead_time > 0 ? $stock->lead_time : ($stock->product->lead_time_days ?? 3));
        $targetDays = (int)($stock->desired_inventory_days > 0 ? $stock->desired_inventory_days : 14);

        $safetyStock = (float)ceil($ads * max(2, $leadTime));
        $reorderPoint = (float)max(0, ceil(($ads * $leadTime) + $safetyStock));
        $targetQty = (float)round($ads * $targetDays);

        $suggested_qty = 0;
        $status = 'OK';
        $isOosWithHistory = false;

        // 1. FILTER KETAT: Jika produk TIDAK ADA PENJUALAN dalam 90 hari terakhir (WMA = 0 / 90d = 0),
        //    maka pada mode WMA saran order = 0 (Dead Stock tidak disarankan reorder).
        if ($sales90Days <= 0 && $ads <= 0) {
            $suggested_qty = 0;
            $status = 'OK';
        }
        // 2. FILTER SLOW MOVING: Jika 30 hari terakhir TIDAK ADA PENJUALAN (sales30Days = 0),
        //    produk HANYA boleh disarankan order jika riwayat 90 hari aktif signifikan (>= 10 pcs, Pasokan Macet/OOS).
        //    Jika sales 90 hari < 10 pcs (slow moving / penjualan lesu), saran order = 0.
        elseif ($sales30Days <= 0 && $sales90Days < 10) {
            $suggested_qty = 0;
            $status = 'OK';
        }
        // 3. JIKA STOK MINUS (Koreksi Selisih Kasir)
        elseif ($current_qty < 0) {
            $grossNeed = abs($current_qty) + max(1, $targetQty);
            $suggested_qty = max(0, $grossNeed - $onOrderQty);
            $status = $suggested_qty > 0 ? 'CRITICAL' : 'OK';
        }
        // 4. JIKA STOK 0 (HABIS)
        elseif ($current_qty == 0) {
            if ($sales30Days > 0 && $ads > 0) {
                // Ada penjualan aktif di 30 hari terakhir
                $grossNeed = max(1, $targetQty);
                $suggested_qty = max(0, $grossNeed - $onOrderQty);
                $status = $suggested_qty > 0 ? 'CRITICAL' : 'OK';
            } elseif ($sales90Days >= 10) {
                // Pasokan Macet (OOS): 30 hari 0 karena barang kosong, tapi 90 hari terbukti aktif
                $isOosWithHistory = true;
                $activeAds = round($sales90Days / 90, 2);
                $targetOos = (float)max(1, round($activeAds * $targetDays));
                $suggested_qty = max(0, $targetOos - $onOrderQty);
                $status = $suggested_qty > 0 ? 'CRITICAL' : 'OK';
            } else {
                $suggested_qty = 0;
                $status = 'OK';
            }
        }
        // 5. JIKA STOK MASIH ADA TETAPI DI BAWAH REORDER POINT
        elseif ($current_qty <= $reorderPoint && $ads > 0 && $sales30Days > 0) {
            $grossNeed = max(0, $targetQty - $current_qty);
            $suggested_qty = max(0, $grossNeed - $onOrderQty);
            $status = $suggested_qty > 0 ? 'REORDER' : 'OK';
        }
        // 6. STOK CUKUP
        else {
            $suggested_qty = 0;
            $status = 'OK';
        }

        // Format Saran Kemasan Dus / Karton
        $cartonSuggestedText = null;
        if ($suggested_qty > 0 && $cartonSize && $cartonSize > 1) {
            $dusCount = (int)ceil($suggested_qty / $cartonSize);
            $cartonSuggestedText = "{$dusCount} Dus (" . ($dusCount * $cartonSize) . " pcs)";
        }

        $result = [
            'product_id' => $stock->product_id,
            'supplier_id' => $effectiveSupplierId,
            'supplier_division_id' => $effectiveDivisionId,
            'sku' => $stock->product->sku ?? '-',
            'name' => $stock->product->name ?? '-',
            'current_qty' => $current_qty,
            'on_order_qty' => $onOrderQty,
            'carton_size' => $cartonSize,
            'carton_suggested_text' => $cartonSuggestedText,
            'ads' => $ads,
            'wma_monthly' => $wmaMonthly,
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
