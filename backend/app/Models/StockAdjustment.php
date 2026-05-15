<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'adjustment_number', 'adjustment_date', 'branch_id', 'adjustment_reason_id',
        'notes', 'recorded_by', 'status'
    ];

    public function adjustmentReason()
    {
        return $this->belongsTo(AdjustmentReason::class);
    }

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
