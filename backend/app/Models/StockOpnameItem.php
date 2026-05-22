<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockOpnameItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id', 'rack_session_id', 'product_id',
        'system_quantity',
        'count1_quantity', 'count1_at',
        'count2_quantity', 'count2_at',
        'discrepancy_1_2',
        'final_quantity', 'final_by', 'final_at', 'final_notes',
        'status',
    ];

    protected $casts = [
        'system_quantity'  => 'decimal:4',
        'count1_quantity'  => 'decimal:4',
        'count2_quantity'  => 'decimal:4',
        'discrepancy_1_2'  => 'decimal:4',
        'final_quantity'   => 'decimal:4',
        'count1_at'        => 'datetime',
        'count2_at'        => 'datetime',
        'final_at'         => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(StockOpnameSession::class, 'session_id');
    }

    public function rackSession()
    {
        return $this->belongsTo(StockOpnameRackSession::class, 'rack_session_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function finalUser()
    {
        return $this->belongsTo(User::class, 'final_by');
    }

    /**
     * Deteksi selisih antar pengecek di level rak ini.
     * Selisih global per produk dihitung di StockOpnameSession::getProductSummary()
     */
    public function detectDiscrepancy(): void
    {
        if ($this->count1_quantity !== null && $this->count2_quantity !== null) {
            $diff = $this->count2_quantity - $this->count1_quantity;
            $this->discrepancy_1_2 = $diff;

            // Toleransi selisih 0 = DISCREPANCY, ≠ 0 = perlu SPV
            $this->status = ($diff == 0) ? 'COUNT2_DONE' : 'DISCREPANCY';
            $this->save();

            // Re-evaluate selisih lintas rak untuk produk yang sama
            $this->evaluateCrossRackDiscrepancy();
        }
    }

    /**
     * Evaluasi apakah produk ini masih DISCREPANCY setelah mempertimbangkan semua rak.
     * Jika total count1 == total count2 lintas rak, anggap tidak ada selisih.
     */
    protected function evaluateCrossRackDiscrepancy(): void
    {
        $siblingsC1 = StockOpnameItem::where('session_id', $this->session_id)
            ->where('product_id', $this->product_id)
            ->whereNotNull('count1_quantity')
            ->sum('count1_quantity');

        $siblingsC2 = StockOpnameItem::where('session_id', $this->session_id)
            ->where('product_id', $this->product_id)
            ->whereNotNull('count2_quantity')
            ->sum('count2_quantity');

        $siblingsCount = StockOpnameItem::where('session_id', $this->session_id)
            ->where('product_id', $this->product_id)
            ->count();

        $doneCount = StockOpnameItem::where('session_id', $this->session_id)
            ->where('product_id', $this->product_id)
            ->whereIn('status', ['COUNT2_DONE', 'DISCREPANCY'])
            ->count();

        // Hanya evaluasi jika semua rak untuk produk ini sudah count2
        if ($siblingsCount === $doneCount) {
            $crossRackDiff = $siblingsC2 - $siblingsC1;

            if ($crossRackDiff == 0) {
                // Tidak ada selisih lintas rak → ubah semua DISCREPANCY jadi COUNT2_DONE
                StockOpnameItem::where('session_id', $this->session_id)
                    ->where('product_id', $this->product_id)
                    ->where('status', 'DISCREPANCY')
                    ->update(['status' => 'COUNT2_DONE', 'discrepancy_1_2' => 0]);
            }
        }
    }
}
