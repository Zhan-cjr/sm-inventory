<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuggestedStockTransferService
{
    protected array $calculationCache = [];
    protected array $candidateResultsCache = [];
    protected array $fleetSummaryCache = [];
    protected ?array $sales30DaysMap = null;
    protected ?int $allBranchesCount = null;
    protected ?Collection $donorsByProduct = null;

    /**
     * Cache jumlah cabang aktif
     */
    public function getAllBranchesCount(): int
    {
        if ($this->allBranchesCount === null) {
            $this->allBranchesCount = Branch::where('is_active', true)->count();
        }
        return $this->allBranchesCount;
    }

    /**
     * Agregasi seluruh penjualan 30 hari dalam 1 query terindeks (transaksi_date) berkecepatan tinggi
     */
    public function getSales30DaysMap(): array
    {
        if ($this->sales30DaysMap !== null) {
            return $this->sales30DaysMap;
        }

        $this->sales30DaysMap = Cache::remember('transfer_service_sales_30d_map_v2', 600, function () {
            $thirtyDaysAgo = Carbon::now()->subDays(30)->toDateString();

            $sales = DB::table('transactions as t')
                ->join('transaction_items as ti', 'ti.transaction_id', '=', 't.id')
                ->where('t.transaction_date', '>=', $thirtyDaysAgo)
                ->where('t.is_voided', false)
                ->groupBy('t.branch_id', 'ti.product_id')
                ->selectRaw('t.branch_id, ti.product_id, SUM(ti.quantity) as total_qty')
                ->get();

            $map = [];
            foreach ($sales as $row) {
                $map[$row->branch_id][$row->product_id] = (float) $row->total_qty;
            }
            return $map;
        });

        return $this->sales30DaysMap;
    }

    /**
     * Preload stok donor untuk sekumpulan produk sekaligus (mencegah N+1 query)
     */
    public function preloadDonorsForProducts(array $productIds, ?string $forceFromBranchId = null): void
    {
        if (empty($productIds)) {
            $this->donorsByProduct = collect();
            return;
        }

        $query = Stock::with('branch')
            ->whereIn('product_id', $productIds)
            ->where('is_active', true)
            ->where('quantity_on_hand', '>', 0);

        if (!empty($forceFromBranchId) && $forceFromBranchId !== 'all') {
            $query->where('branch_id', $forceFromBranchId);
        }

        $donors = $query->get();

        if ($this->donorsByProduct === null) {
            $this->donorsByProduct = $donors->groupBy('product_id');
        } else {
            foreach ($donors->groupBy('product_id') as $pid => $items) {
                $this->donorsByProduct[$pid] = $items;
            }
        }
    }

    /**
     * Ambil stok donor untuk produk tertentu dari memory / fallback query
     */
    public function getDonorStocksForProduct($productId, $destBranchId, ?string $forceFromBranchId = null): Collection
    {
        if ($this->donorsByProduct !== null && isset($this->donorsByProduct[$productId])) {
            return $this->donorsByProduct[$productId]->filter(function ($ds) use ($destBranchId, $forceFromBranchId) {
                if ($ds->branch_id == $destBranchId) return false;
                if (!empty($forceFromBranchId) && $forceFromBranchId !== 'all' && $ds->branch_id != $forceFromBranchId) return false;
                return $ds->quantity_on_hand > 0;
            });
        }

        $donorQuery = Stock::with('branch')
            ->where('product_id', $productId)
            ->where('branch_id', '!=', $destBranchId)
            ->where('is_active', true)
            ->where('quantity_on_hand', '>', 0);

        if (!empty($forceFromBranchId) && $forceFromBranchId !== 'all') {
            $donorQuery->where('branch_id', $forceFromBranchId);
        }

        return $donorQuery->get();
    }

    /**
     * Hitung peluang transfer/mutasi untuk sebuah record Stock di cabang tujuan (destinasi).
     */
    public function calculateForStock(Stock $stock, ?string $forceFromBranchId = null): ?array
    {
        $fromKey = (!empty($forceFromBranchId) && $forceFromBranchId !== 'all') ? $forceFromBranchId : 'auto';
        $cacheKey = "stock_transfer_{$stock->id}_{$fromKey}";
        if (isset($this->calculationCache[$cacheKey])) {
            return $this->calculationCache[$cacheKey];
        }

        if ($this->getAllBranchesCount() <= 1) {
            return null;
        }

        $destBranchId = $stock->branch_id;
        $destQoh = (float) $stock->quantity_on_hand;

        // 1. Kecepatan jual di cabang tujuan (Destinasi) via Pre-aggregated Map
        $salesMap = $this->getSales30DaysMap();
        $destSales30Days = (float) ($salesMap[$destBranchId][$stock->product_id] ?? 0);

        $destAds = round($destSales30Days / 30, 2);
        $leadTime = (int) ($stock->product->lead_time_days ?? 3);
        $criticalDays = max($leadTime + 4, 7);
        $destDoh = ($destQoh > 0 && $destAds > 0) ? (int) round($destQoh / $destAds) : ($destQoh > 0 ? 999 : 0);

        // Kriteria Cabang Tujuan: Butuh Stok (Defisit atau Kritis)
        $isDestDeficit = ($destQoh <= 0 && $destSales30Days > 0) || ($destAds > 0 && $destDoh <= $criticalDays) || ($destQoh < 0);
        if (!$isDestDeficit) {
            $this->calculationCache[$cacheKey] = null;
            return null;
        }

        // 2. Cari Cabang Asal (Donor) yang memiliki surplus stok
        $donorStocks = $this->getDonorStocksForProduct($stock->product_id, $destBranchId, $forceFromBranchId);
        if ($donorStocks->isEmpty()) {
            $this->calculationCache[$cacheKey] = null;
            return null;
        }

        $bestDonor = null;
        $bestSurplus = 0;
        $bestSuggestedQty = 0;
        $bestRemainingDonorStock = 0;
        $bestDonorAds = 0;
        $bestDonorDoh = 0;

        foreach ($donorStocks as $donorStock) {
            $donorQoh = (float) $donorStock->quantity_on_hand;

            // Kecepatan jual di cabang donor via Pre-aggregated Map
            $donorSales30Days = (float) ($salesMap[$donorStock->branch_id][$stock->product_id] ?? 0);
            $donorAds = round($donorSales30Days / 30, 2);
            $donorDoh = $donorAds > 0 ? (int) round($donorQoh / $donorAds) : 999;

            // Cadangan aman cabang donor: minimal 2 pcs atau cukup untuk 30 hari ke depan
            $donorSafetyBuffer = (float) max(2, ceil($donorAds * 30));
            $availableSurplus = max(0, $donorQoh - $donorSafetyBuffer);

            if ($availableSurplus >= 1) {
                // Kuantitas transfer yang disarankan
                $targetNeeded = (int) max(1, ($stock->product->reorder_qty ?: 10));
                if ($destQoh < 0) {
                    $targetNeeded += (int) abs($destQoh);
                }
                $suggestedQty = (int) min($availableSurplus, $targetNeeded);

                if ($suggestedQty >= 1 && $availableSurplus > $bestSurplus) {
                    $bestDonor = $donorStock;
                    $bestSurplus = $availableSurplus;
                    $bestSuggestedQty = $suggestedQty;
                    $bestRemainingDonorStock = (float) ($donorQoh - $suggestedQty);
                    $bestDonorAds = $donorAds;
                    $bestDonorDoh = $donorDoh;
                }
            }
        }

        if (!$bestDonor || $bestSuggestedQty <= 0) {
            $this->calculationCache[$cacheKey] = null;
            return null;
        }

        $costPrice = (float) ($stock->product->cost_price_tax ?: $stock->product->cost_price ?: $stock->product->selling_price ?: 0);
        $capitalFreed = $bestSuggestedQty * $costPrice;

        $result = [
            'stock_id' => $stock->id,
            'product_id' => $stock->product_id,
            'product_name' => $stock->product->name,
            'barcode' => $stock->product->barcode ?: '-',
            'sku' => $stock->product->sku ?: '-',
            'unit' => $stock->product->unit_of_measure ?: 'PCS',
            'cost_price' => $costPrice,
            'capital_freed' => $capitalFreed,

            // Cabang Tujuan (Penerima / Butuh Pasokan)
            'destination_branch_id' => $destBranchId,
            'destination_branch_name' => $stock->branch?->name ?? 'Cabang Tujuan',
            'destination_qoh' => $destQoh,
            'destination_ads' => $destAds,
            'destination_doh' => $destDoh,
            'destination_status' => $destQoh <= 0 ? 'HABIS' : 'KRITIS',

            // Cabang Asal (Donor / Surplus)
            'source_branch_id' => $bestDonor->branch_id,
            'source_branch_name' => $bestDonor->branch?->name ?? 'Cabang Asal',
            'source_qoh' => (float) $bestDonor->quantity_on_hand,
            'source_ads' => $bestDonorAds,
            'source_doh' => $bestDonorDoh,
            'source_status' => $bestDonorAds == 0 ? 'DEAD STOCK' : 'SLOW MOVING',

            // Rekomendasi & Justifikasi Angka
            'suggested_qty' => $bestSuggestedQty,
            'remaining_source_qoh' => $bestRemainingDonorStock,
            'justification' => "Stok di {$stock->branch?->name} {$destQoh} pcs (DOH {$destDoh} hr). {$bestDonor->branch?->name} surplus {$bestSurplus} pcs (DOH {$bestDonorDoh} hr). Aman mutasi {$bestSuggestedQty} pcs, sisa donor {$bestRemainingDonorStock} pcs.",
            'route_key' => "{$bestDonor->branch_id}_to_{$destBranchId}",
            'route_label' => ($bestDonor->branch?->name ?? 'Asal') . " ➔ " . ($stock->branch?->name ?? 'Tujuan'),
        ];

        $this->calculationCache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Mengambil ID Stock yang memiliki peluang mutasi/transfer.
     */
    public function getTransferCandidateStockIds(?string $fromBranchId = null, ?string $toBranchId = null): array
    {
        $memoKey = ($fromBranchId ?: 'all') . '_' . ($toBranchId ?: 'all');
        if (isset($this->candidateResultsCache[$memoKey])) {
            return $this->candidateResultsCache[$memoKey];
        }

        if ($this->getAllBranchesCount() <= 1) {
            return $this->candidateResultsCache[$memoKey] = [];
        }

        // 1. Dapatkan sales map dan kumpulkan produk yang aktif terjual dalam 30 hari
        $salesMap = $this->getSales30DaysMap();
        $activeProductIds = [];
        foreach ($salesMap as $bId => $pMap) {
            if (!empty($toBranchId) && $toBranchId !== 'all' && $bId != $toBranchId) continue;
            foreach ($pMap as $pId => $qty) {
                if ($qty > 0) {
                    $activeProductIds[$pId] = true;
                }
            }
        }
        $validProductIds = array_keys($activeProductIds);

        // 2. Query kandidat yang hanya relevan (menghindari load ribuan dead non-selling products)
        $query = DB::table('stocks as dest')
            ->join('stocks as donor', function ($join) {
                $join->on('dest.product_id', '=', 'donor.product_id')
                     ->whereColumn('dest.branch_id', '!=', 'donor.branch_id');
            })
            ->join('products', 'dest.product_id', '=', 'products.id')
            ->where('dest.is_active', true)
            ->where('donor.is_active', true)
            ->where('products.is_active', true)
            ->where(function ($q) use ($validProductIds) {
                if (!empty($validProductIds)) {
                    $q->whereIn('dest.product_id', $validProductIds)
                      ->orWhere('dest.quantity_on_hand', '<', 0);
                } else {
                    $q->where('dest.quantity_on_hand', '<', 0);
                }
            })
            ->where('dest.quantity_on_hand', '<=', 10)
            ->where('donor.quantity_on_hand', '>', 2);

        if (!empty($toBranchId) && $toBranchId !== 'all') {
            $query->where('dest.branch_id', $toBranchId);
        }

        if (!empty($fromBranchId) && $fromBranchId !== 'all') {
            $query->where('donor.branch_id', $fromBranchId);
        }

        $destStockIds = $query->distinct()->pluck('dest.id')->all();
        if (empty($destStockIds)) {
            return $this->candidateResultsCache[$memoKey] = [];
        }

        $candidateStocks = Stock::whereIn('id', $destStockIds)->with(['product', 'branch'])->get();
        
        // Preload donor stocks sekaligus sebelum evaluasi
        $productIds = $candidateStocks->pluck('product_id')->unique()->all();
        $this->preloadDonorsForProducts($productIds, $fromBranchId);

        $matchedStockIds = [];

        foreach ($candidateStocks as $stock) {
            $calc = $this->calculateForStock($stock, $fromBranchId);
            if ($calc && $calc['suggested_qty'] > 0) {
                if (empty($fromBranchId) || $fromBranchId === 'all' || $calc['source_branch_id'] === $fromBranchId) {
                    $matchedStockIds[] = $stock->id;
                }
            }
        }

        return $this->candidateResultsCache[$memoKey] = $matchedStockIds;
    }

    /**
     * Hitung ringkasan armada/muatan untuk kumpulan rekomendasi.
     */
    public function getFleetSummary(Collection $records, ?string $fromBranchId = null): array
    {
        $memoKey = ($fromBranchId ?: 'all') . '_' . md5($records->pluck('id')->implode(','));
        if (isset($this->fleetSummaryCache[$memoKey])) {
            return $this->fleetSummaryCache[$memoKey];
        }

        $totalItems = 0;
        $totalUnits = 0;
        $totalCapitalFreed = 0;
        $routes = [];

        // Preload jika belum terisi
        $productIds = $records->pluck('product_id')->unique()->all();
        $this->preloadDonorsForProducts($productIds, $fromBranchId);
        $this->getSales30DaysMap();

        foreach ($records as $record) {
            $calc = $this->calculateForStock($record, $fromBranchId);
            if ($calc && $calc['suggested_qty'] > 0) {
                $totalItems++;
                $totalUnits += $calc['suggested_qty'];
                $totalCapitalFreed += $calc['capital_freed'];

                $rKey = $calc['route_key'];
                if (!isset($routes[$rKey])) {
                    $routes[$rKey] = [
                        'route_label' => $calc['route_label'],
                        'item_count' => 0,
                        'total_units' => 0,
                        'capital_freed' => 0,
                    ];
                }
                $routes[$rKey]['item_count']++;
                $routes[$rKey]['total_units'] += $calc['suggested_qty'];
                $routes[$rKey]['capital_freed'] += $calc['capital_freed'];
            }
        }

        return $this->fleetSummaryCache[$memoKey] = [
            'total_items' => $totalItems,
            'total_units' => $totalUnits,
            'total_capital_freed' => $totalCapitalFreed,
            'total_routes' => count($routes),
            'routes' => $routes,
        ];
    }

    /**
     * Eksekusi pembuatan transaksi StockTransfer secara programmatic (Single / Bulk).
     */
    public function executeTransfer(string $fromBranchId, string $toBranchId, array $items, int $userId, ?string $notes = null): StockTransfer
    {
        return DB::transaction(function () use ($fromBranchId, $toBranchId, $items, $userId, $notes) {
            $transfer = StockTransfer::create([
                'reference_number' => 'TRF-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'status' => 'pending',
                'transfer_date' => now()->toDateString(),
                'created_by' => $userId,
                'notes' => $notes ?: 'Rekomendasi Mutasi Otomatis (Retail Intelligence Inter-Branch Rebalancing)',
                'total_amount' => 0,
            ]);

            $totalAmount = 0;
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                $qty = (float) ($item['quantity'] ?? 1);
                $price = (float) ($product?->cost_price_tax ?: $product?->cost_price ?: $product?->selling_price ?: 0);
                $subtotal = $qty * $price;

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? 'Mutasi Redistribusi Stok Cerdas',
                ]);

                $totalAmount += $subtotal;
            }

            $transfer->update(['total_amount' => $totalAmount]);

            return $transfer;
        });
    }
}
