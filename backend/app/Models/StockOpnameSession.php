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

    /**
     * Progress penghitung 1: berapa rak sudah COUNT1_DONE
     */
    public function getCount1ProgressAttribute(): array
    {
        $total = $this->rackSessions()->count();
        $done  = $this->rackSessions()->where('count1_status', 'DONE')->count();
        return ['done' => $done, 'total' => $total];
    }

    /**
     * Progress pengecek 2
     */
    public function getCount2ProgressAttribute(): array
    {
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
        $branchId = $this->branch_id;
        $itemsGrouped = $this->items()
            ->with(['product'])
            ->get()
            ->groupBy('product_id');

        $existingProductIds = \App\Models\Stock::where('branch_id', $branchId)
            ->whereIn('product_id', $itemsGrouped->keys()->filter())
            ->pluck('product_id')
            ->flip()
            ->all();

        return $itemsGrouped->map(function ($items) use ($existingProductIds) {
            $product    = $items->first()->product;
            $totalC1    = (float) $items->whereNotNull('count1_quantity')->sum('count1_quantity');
            $totalC2    = (float) $items->whereNotNull('count2_quantity')->sum('count2_quantity');
            $totalFinal = (float) $items->whereNotNull('final_quantity')->sum('final_quantity');
            $systemQty  = (float) ($items->first()->system_quantity ?? 0);
            $hasFinal   = $items->where('status', 'FINAL_DONE')->count() > 0;

            $effectiveQty = $hasFinal ? $totalFinal : ($totalC2 ?: $totalC1);
            $finalDisc    = $effectiveQty - $systemQty;

            // is_discrepancy: cek status DISCREPANCY (final check) ATAU ada selisih hitung vs sistem
            $hasDiscrepancyStatus = $items->where('status', 'DISCREPANCY')->count() > 0;
            $hasNumericDisc       = $finalDisc != 0;

            $productId     = $product?->id;
            $isNewToBranch = $productId ? !isset($existingProductIds[$productId]) : false;

            return [
                'product_id'       => $productId,
                'sku'              => $product?->sku,
                'name'             => $product?->name,
                'is_new_to_branch' => $isNewToBranch,
                'effective_qty'    => $effectiveQty,
                'system_qty'       => $systemQty,
                'total_count1'     => $totalC1,
                'total_count2'     => $totalC2,
                'total_final'      => $totalFinal,
                'final_disc'       => $finalDisc,
                'is_discrepancy'   => $hasDiscrepancyStatus || $hasNumericDisc || $isNewToBranch,
                'racks'            => $items->map(fn ($i) => [
                    'rack_code'       => $i->rackSession?->rack?->rack_code,
                    'count1_quantity' => $i->count1_quantity,
                    'count2_quantity' => $i->count2_quantity,
                    'final_quantity'  => $i->final_quantity,
                    'status'          => $i->status,
                ]),
            ];
        });
    }
}
