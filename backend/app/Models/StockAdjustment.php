<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StockAdjustment extends Model
{
    use HasUuids, LogsActivity, \App\Models\Traits\HasApprovals {
        \App\Models\Traits\HasApprovals::approve as traitApprove;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'adjustment_number', 'adjustment_date', 'branch_id', 'adjustment_reason_id',
        'notes', 'recorded_by', 'status', 'total_value'
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

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approve($userId, $notes = null)
    {
        $result = $this->traitApprove($userId, $notes);

        // Update actual stock
        foreach ($this->items as $item) {
            $stockRec = \App\Models\Stock::firstOrCreate([
                'product_id' => $item->product_id,
                'branch_id' => $this->branch_id
            ], [
                'quantity_on_hand' => 0
            ]);
            $stockRec->quantity_on_hand = $item->new_quantity;
            $stockRec->save();
        }

        // Update status to COMPLETED after approval
        $this->update(['status' => 'COMPLETED']);

        return $result;
    }
}
