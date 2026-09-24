<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductListing extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'product_listings';

    protected $fillable = [
        'organization_id',
        'listing_number',
        'supplier_id',
        'supplier_division_id',
        'listing_fee',
        'trial_start_date',
        'trial_end_date',
        'allowed_branch_ids',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'listing_fee' => 'decimal:2',
        'trial_start_date' => 'date',
        'trial_end_date' => 'date',
        'allowed_branch_ids' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierDivision(): BelongsTo
    {
        return $this->belongsTo(SupplierDivision::class, 'supplier_division_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_listing_id');
    }

    public function listingDeduction(): HasOne
    {
        return $this->hasOne(SupplierDeduction::class, 'reference_id')
            ->where('deduction_type', 'LISTING_FEE');
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->trial_end_date) {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays(Carbon::parse($this->trial_end_date)->startOfDay(), false);
    }

    public function isTrialActive(): bool
    {
        return $this->status === 'TRIAL' && ($this->trial_end_date === null || Carbon::parse($this->trial_end_date)->endOfDay()->isFuture());
    }

    public function isTrialExpired(): bool
    {
        return $this->status === 'TRIAL' && $this->trial_end_date !== null && Carbon::parse($this->trial_end_date)->endOfDay()->isPast();
    }

    public function isBranchAllowed(string $branchId): bool
    {
        if ($this->status === 'PASSED' || empty($this->allowed_branch_ids)) {
            return true;
        }
        return in_array($branchId, (array) $this->allowed_branch_ids);
    }

    public function scopeInTrial($query)
    {
        return $query->where('status', 'TRIAL');
    }

    public function scopePassed($query)
    {
        return $query->where('status', 'PASSED');
    }

    public function scopeDelisted($query)
    {
        return $query->where('status', 'DELISTED');
    }

    public function getPerformanceMetrics(): array
    {
        $products = $this->products()->get();
        $productIds = $products->pluck('id')->toArray();
        $branchIds = (array) ($this->allowed_branch_ids ?? []);
        $startDate = $this->trial_start_date ?? $this->created_at;

        if (empty($productIds) || empty($branchIds)) {
            return [
                'total_products' => count($products),
                'total_bought' => 0,
                'total_sold' => 0,
                'total_stock' => 0,
                'total_omset' => 0,
                'sell_through' => 0,
                'breakdown' => [],
            ];
        }

        // 1. Total Pembelian / Penerimaan Barang (GoodsReceiptItem) di cabang-cabang pilot
        $receivedData = \App\Models\GoodsReceiptItem::whereIn('product_id', $productIds)
            ->whereHas('goodsReceipt', function ($q) use ($branchIds, $startDate) {
                $q->whereIn('branch_id', $branchIds)
                  ->where('created_at', '>=', $startDate);
            })
            ->selectRaw('product_id, SUM(quantity_received) as total_received')
            ->groupBy('product_id')
            ->pluck('total_received', 'product_id');

        // 2. Total Penjualan (TransactionItem) di cabang-cabang pilot
        $soldData = \App\Models\TransactionItem::whereIn('product_id', $productIds)
            ->whereHas('transaction', function ($q) use ($branchIds, $startDate) {
                $q->whereIn('branch_id', $branchIds)
                  ->where('is_voided', false)
                  ->where('created_at', '>=', $startDate);
            })
            ->selectRaw('product_id, SUM(quantity) as total_qty, SUM(quantity * unit_price) as total_omset')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        // 3. Stok Saat Ini di cabang-cabang pilot
        $stockData = \App\Models\Stock::whereIn('product_id', $productIds)
            ->whereIn('branch_id', $branchIds)
            ->selectRaw('product_id, SUM(quantity_on_hand) as total_stock')
            ->groupBy('product_id')
            ->pluck('total_stock', 'product_id');

        // 4. Breakdown per Cabang & per Produk
        $branches = \App\Models\Branch::whereIn('id', $branchIds)->get();
        $breakdown = [];

        foreach ($branches as $branch) {
            foreach ($products as $prod) {
                $received = (float) \App\Models\GoodsReceiptItem::where('product_id', $prod->id)
                    ->whereHas('goodsReceipt', fn ($q) => $q->where('branch_id', $branch->id)->where('created_at', '>=', $startDate))
                    ->sum('quantity_received');

                $soldItem = \App\Models\TransactionItem::where('product_id', $prod->id)
                    ->whereHas('transaction', fn ($q) => $q->where('branch_id', $branch->id)->where('is_voided', false)->where('created_at', '>=', $startDate))
                    ->selectRaw('SUM(quantity) as qty, SUM(quantity * unit_price) as omset')
                    ->first();

                $sold = (float) ($soldItem->qty ?? 0);
                $omset = (float) ($soldItem->omset ?? 0);

                $currentStock = (float) \App\Models\Stock::where('product_id', $prod->id)
                    ->where('branch_id', $branch->id)
                    ->value('quantity_on_hand') ?? 0;

                $baseStock = ($received > 0) ? $received : ($sold + $currentStock);
                $sellThrough = $baseStock > 0 ? round(($sold / $baseStock) * 100, 1) : 0;

                $breakdown[] = [
                    'branch_name' => $branch->name,
                    'product_sku' => $prod->sku,
                    'product_name' => $prod->name,
                    'qty_received' => $received,
                    'qty_sold' => $sold,
                    'current_stock' => $currentStock,
                    'sell_through' => $sellThrough,
                    'omset' => $omset,
                ];
            }
        }

        $totalBought = (float) $receivedData->sum();
        $totalSold = (float) $soldData->sum('total_qty');
        $totalOmset = (float) $soldData->sum('total_omset');
        $totalStock = (float) $stockData->sum();
        $baseTotal = ($totalBought > 0) ? $totalBought : ($totalSold + $totalStock);
        $overallSellThrough = $baseTotal > 0 ? round(($totalSold / $baseTotal) * 100, 1) : 0;

        return [
            'total_products' => count($products),
            'total_bought' => $totalBought,
            'total_sold' => $totalSold,
            'total_stock' => $totalStock,
            'total_omset' => $totalOmset,
            'sell_through' => $overallSellThrough,
            'breakdown' => $breakdown,
        ];
    }
}
