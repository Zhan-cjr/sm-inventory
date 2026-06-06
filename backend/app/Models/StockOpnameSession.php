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
        return $this->items()
            ->with(['product'])
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                $product   = $items->first()->product;
                $totalC1   = $items->whereNotNull('count1_quantity')->sum('count1_quantity');
                $totalC2   = $items->whereNotNull('count2_quantity')->sum('count2_quantity');
                $totalFinal = $items->whereNotNull('final_quantity')->sum('final_quantity');
                $systemQty = $items->first()->system_quantity; // sama untuk semua rak
                $hasFinal  = $items->where('status', 'FINAL_DONE')->count() > 0;

                $effectiveQty = $hasFinal ? $totalFinal : ($totalC2 ?: $totalC1);
                $finalDisc    = $effectiveQty - $systemQty;

                // is_discrepancy: cek status DISCREPANCY (final check) ATAU ada selisih hitung vs sistem
                $hasDiscrepancyStatus = $items->where('status', 'DISCREPANCY')->count() > 0;
                $hasNumericDisc       = $finalDisc != 0 && $effectiveQty > 0;

                return [
                    'product_id'     => $product?->id,
                    'sku'            => $product?->sku,
                    'name'           => $product?->name,
                    'system_qty'     => $systemQty,
                    'total_count1'   => $totalC1,
                    'total_count2'   => $totalC2,
                    'total_final'    => $totalFinal,
                    'final_disc'     => $finalDisc,
                    'is_discrepancy' => $hasDiscrepancyStatus || $hasNumericDisc,
                    'racks'          => $items->map(fn ($i) => [
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
