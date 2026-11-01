<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockOpnameSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_number', 'branch_id', 'organization_id', 'opname_date',
        'status', 'session_token', 'notes', 'created_by', 'approved_by', 'completed_at',
    ];

    protected $casts = [
        'opname_date'  => 'date',
        'completed_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rackSessions()
    {
        return $this->hasMany(StockOpnameRackSession::class, 'session_id');
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class, 'session_id');
    }

    protected ?\Illuminate\Support\Collection $memoizedProductSummary = null;
    protected ?array $memoizedDiscrepancyNominals = null;

    /**
     * Progress penghitung 1: berapa rak sudah COUNT1_DONE
     */
    public function getCount1ProgressAttribute(): array
    {
        if ($this->relationLoaded('rackSessions')) {
            $total = $this->rackSessions->count();
            $done  = $this->rackSessions->where('count1_status', 'DONE')->count();
            return ['done' => $done, 'total' => $total];
        }
        $total = $this->rackSessions()->count();
        $done  = $this->rackSessions()->where('count1_status', 'DONE')->count();
        return ['done' => $done, 'total' => $total];
    }

    /**
     * Progress pengecek 2
     */
    public function getCount2ProgressAttribute(): array
    {
        if ($this->relationLoaded('rackSessions')) {
            $total = $this->rackSessions->count();
            $done  = $this->rackSessions->where('count2_status', 'DONE')->count();
            return ['done' => $done, 'total' => $total];
        }
        $total = $this->rackSessions()->count();
        $done  = $this->rackSessions()->where('count2_status', 'DONE')->count();
        return ['done' => $done, 'total' => $total];
    }

    /**
     * Jumlah item dengan selisih (DISCREPANCY)
     */
    public function getDiscrepancyCountAttribute(): int
    {
        return $this->getProductSummary()->where('is_discrepancy', true)->count();
    }

    /**
     * Ringkasan per produk lintas rak (untuk rekonsiliasi akhir)
     * Menggabungkan count1 & count2 dari semua rak untuk produk yang sama
     */
    public function getProductSummary()
    {
        if ($this->memoizedProductSummary !== null) {
            return $this->memoizedProductSummary;
        }

        $branchId = $this->branch_id;
        $itemsGrouped = ($this->relationLoaded('items')
            ? $this->items
            : $this->items()->with(['product', 'rackSession.rack'])->get()
        )->groupBy('product_id');

        $existingProductIds = \App\Models\Stock::where('branch_id', $branchId)
            ->whereIn('product_id', $itemsGrouped->keys()->filter())
            ->pluck('product_id')
            ->flip()
            ->all();

        // Ambil seluruh mutasi eksternal (penjualan kasir, penerimaan, dll) selama sesi SO berlangsung
        $start = $this->created_at;
        $end = $this->completed_at ?? now();

        $externalLogs = \App\Models\InventoryLog::where('branch_id', $branchId)
            ->whereBetween('created_at', [$start, $end])
            ->where(function ($q) {
                $q->whereNull('reference_doc_type')
                  ->orWhere('reference_doc_type', '!=', 'STOCK_OPNAME');
            })
            ->where(function ($q) {
                $q->whereNull('reason_code')
                  ->orWhere('reason_code', '!=', 'STOCK_OPNAME');
            })
            ->whereIn('product_id', $itemsGrouped->keys()->filter())
            ->get()
            ->groupBy('product_id');

        return $this->memoizedProductSummary = $itemsGrouped->map(function ($items, $pid) use ($existingProductIds, $externalLogs) {
            $product    = $items->first()->product;
            $totalC1    = (float) $items->whereNotNull('count1_quantity')->sum('count1_quantity');
            $totalC2    = (float) $items->whereNotNull('count2_quantity')->sum('count2_quantity');
            $totalFinal = (float) $items->whereNotNull('final_quantity')->sum('final_quantity');
            $systemQty  = (float) ($items->first()->system_quantity ?? 0);
            $hasFinal   = $items->where('status', 'FINAL_DONE')->count() > 0;

            // Mutasi eksternal selama SO (misal penjualan: -1.0)
            $deltaDuringSo = (float) ($externalLogs->get($pid)?->sum('quantity_change') ?? 0);
            $adjustedSystemQty = $systemQty + $deltaDuringSo;

            $effectiveQty = $hasFinal ? $totalFinal : ($totalC2 ?: $totalC1);
            $finalDisc    = $effectiveQty - $adjustedSystemQty;

            // is_discrepancy: cek status DISCREPANCY (final check) ATAU ada selisih hitung vs sistem
            $hasDiscrepancyStatus = $items->where('status', 'DISCREPANCY')->count() > 0;
            $hasNumericDisc       = $finalDisc != 0;

            $productId     = $product?->id;
            $isNewToBranch = $productId ? !isset($existingProductIds[$productId]) : false;

            return [
                'product_id'          => $productId,
                'sku'                 => $product?->sku,
                'name'                => $product?->name,
                'is_new_to_branch'    => $isNewToBranch,
                'effective_qty'       => $effectiveQty,
                'system_qty'          => $systemQty,
                'delta_during_so'     => $deltaDuringSo,
                'adjusted_system_qty' => $adjustedSystemQty,
                'total_count1'        => $totalC1,
                'total_count2'        => $totalC2,
                'total_final'         => $totalFinal,
                'final_disc'          => $finalDisc,
                'is_discrepancy'      => $hasDiscrepancyStatus || $hasNumericDisc || $isNewToBranch,
                'racks'               => $items->map(fn ($i) => [
                    'rack_code'       => $i->rackSession?->rack?->rack_code,
                    'count1_quantity' => $i->count1_quantity,
                    'count2_quantity' => $i->count2_quantity,
                    'final_quantity'  => $i->final_quantity,
                    'status'          => $i->status,
                ]),
            ];
        });
    }

    /**
     * Hitung total nominal selisih (+) dan (-) dalam 1 pass kalkulasi ter-memoize
     */
    public function getDiscrepancyNominals(): array
    {
        if ($this->memoizedDiscrepancyNominals !== null) {
            return $this->memoizedDiscrepancyNominals;
        }

        $summary = $this->getProductSummary();
        $totalPlus = 0.0;
        $totalMinus = 0.0;

        $stocks = [];
        if ($this->branch_id) {
            $productIds = $summary->pluck('product_id')->filter()->toArray();
            if (!empty($productIds)) {
                $stocks = \App\Models\Stock::where('branch_id', $this->branch_id)
                    ->whereIn('product_id', $productIds)
                    ->with('product')
                    ->get()
                    ->keyBy('product_id');
            }
        }

        foreach ($summary as $prodSummary) {
            $diff = (float) $prodSummary['final_disc'];
            if ($diff == 0.0) continue;

            $pid = $prodSummary['product_id'];
            $price = 0.0;
            if ($this->branch_id && isset($stocks[$pid])) {
                $st = $stocks[$pid];
                $price = (float) ($st->cost_price_tax > 0 ? $st->cost_price_tax : ($st->product->cost_price_tax ?? $st->product->cost_price ?? 0));
            } else {
                $p = \App\Models\Product::find($pid);
                if ($p) {
                    $price = (float) ($p->cost_price_tax ?? $p->cost_price ?? 0);
                }
            }

            if ($diff > 0) {
                $totalPlus += ($diff * $price);
            } else {
                $totalMinus += (abs($diff) * $price);
            }
        }

        return $this->memoizedDiscrepancyNominals = [
            'plus' => $totalPlus > 0 ? $totalPlus : null,
            'minus' => $totalMinus > 0 ? $totalMinus : null,
        ];
    }
}
