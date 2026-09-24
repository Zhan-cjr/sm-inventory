<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuggestedStockTransferService
{
    protected array $calculationCache = [];

    /**
     * Hitung peluang transfer/mutasi untuk sebuah record Stock di cabang tujuan (destinasi).
     */
    public function calculateForStock(Stock $stock, ?string $forceFromBranchId = null): ?array
    {
        $cacheKey = "stock_transfer_{$stock->id}_" . ($forceFromBranchId ?: 'auto');
        if (isset($this->calculationCache[$cacheKey])) {
            return $this->calculationCache[$cacheKey];
        }

        $allBranchesCount = Branch::where('is_active', true)->count();
        if ($allBranchesCount <= 1) {
            return null;
        }

        $destBranchId = $stock->branch_id;
        $destQoh = (float) $stock->quantity_on_hand;
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        // 1. Kecepatan jual di cabang tujuan (Destinasi)
        $destSales30Days = (float) DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.branch_id', $destBranchId)
            ->where('transaction_items.product_id', $stock->product_id)
            ->where('transactions.created_at', '>=', $thirtyDaysAgo)
            ->where('transactions.is_voided', false)
            ->sum('transaction_items.quantity');

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
        $donorQuery = Stock::with('branch')
            ->where('product_id', $stock->product_id)
            ->where('branch_id', '!=', $destBranchId)
            ->where('is_active', true)
            ->where('quantity_on_hand', '>', 0);

        if (!empty($forceFromBranchId)) {
            $donorQuery->where('branch_id', $forceFromBranchId);
        }

        $donorStocks = $donorQuery->get();
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

            // Kecepatan jual di cabang donor
            $donorSales30Days = (float) DB::table('transaction_items')
                ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
                ->where('transactions.branch_id', $donorStock->branch_id)
                ->where('transaction_items.product_id', $stock->product_id)
                ->where('transactions.created_at', '>=', $thirtyDaysAgo)
                ->where('transactions.is_voided', false)
                ->sum('transaction_items.quantity');

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
        $allBranchesCount = Branch::where('is_active', true)->count();
        if ($allBranchesCount <= 1) {
            return [];
        }

        $query = DB::table('stocks as dest')
            ->join('stocks as donor', function ($join) {
                $join->on('dest.product_id', '=', 'donor.product_id')
                     ->whereColumn('dest.branch_id', '!=', 'donor.branch_id');
            })
            ->join('products', 'dest.product_id', '=', 'products.id')
            ->where('dest.is_active', true)
            ->where('donor.is_active', true)
            ->where('products.is_active', true)
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
            return [];
        }

        $candidateStocks = Stock::whereIn('id', $destStockIds)->with(['product', 'branch'])->get();
        $matchedStockIds = [];

        foreach ($candidateStocks as $stock) {
            $calc = $this->calculateForStock($stock, $fromBranchId);
            if ($calc && $calc['suggested_qty'] > 0) {
                if (empty($fromBranchId) || $fromBranchId === 'all' || $calc['source_branch_id'] === $fromBranchId) {
                    $matchedStockIds[] = $stock->id;
                }
            }
        }

        return $matchedStockIds;
    }

    /**
     * Hitung ringkasan armada/muatan untuk kumpulan rekomendasi.
     */
    public function getFleetSummary(Collection $records, ?string $fromBranchId = null): array
    {
        $totalItems = 0;
        $totalUnits = 0;
        $totalCapitalFreed = 0;
        $routes = [];

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

        return [
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
