<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockAuditInspection extends Model
{
    use HasUuids;

    protected $fillable = [
        'audit_number',
        'branch_id',
        'organization_id',
        'auditor_id',
        'inspection_date',
        'inspection_mode',
        'status',
        'total_items_checked',
        'total_items_discrepancy',
        'total_loss_nominal',
        'total_gain_nominal',
        'acknowledged_by',
        'notes',
        'adjustment_id',
    ];

    protected $casts = [
        'inspection_date'      => 'date',
        'total_items_checked'  => 'integer',
        'total_items_discrepancy' => 'integer',
        'total_loss_nominal'   => 'decimal:2',
        'total_gain_nominal'   => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }

    public function items()
    {
        return $this->hasMany(StockAuditInspectionItem::class, 'inspection_id');
    }

    /**
     * Hitung ulang total summary dan simpan
     */
    public function recalculateSummary(): void
    {
        $items = $this->items()->get();
        $totalChecked = $items->count();
        $totalDisc = 0;
        $totalLoss = 0.0;
        $totalGain = 0.0;

        foreach ($items as $item) {
            if ($item->status !== 'MATCH') {
                $totalDisc++;
            }
            if ($item->discrepancy_cost < 0) {
                $totalLoss += abs((float) $item->discrepancy_cost);
            } elseif ($item->discrepancy_cost > 0) {
                $totalGain += (float) $item->discrepancy_cost;
            }
        }

        $this->update([
            'total_items_checked'     => $totalChecked,
            'total_items_discrepancy' => $totalDisc,
            'total_loss_nominal'      => $totalLoss,
            'total_gain_nominal'      => $totalGain,
        ]);
    }
}

