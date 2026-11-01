<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockQuarantineAuditItem extends Model
{
    use HasUuids;

    protected $table = 'stock_quarantine_audit_items';

    protected $fillable = [
        'audit_id',
        'product_id',
        'system_damaged_qty',
        'actual_damaged_qty',
        'discrepancy_qty',
        'cost_price',
        'total_cost',
        'action_taken',
        'notes',
    ];

    protected $casts = [
        'system_damaged_qty' => 'decimal:2',
        'actual_damaged_qty' => 'decimal:2',
        'discrepancy_qty' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(StockQuarantineAudit::class, 'audit_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
