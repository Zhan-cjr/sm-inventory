<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockQuarantineAudit extends Model
{
    use HasUuids;

    protected $table = 'stock_quarantine_audits';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'audit_number',
        'audit_date',
        'status',
        'total_system_damaged',
        'total_actual_damaged',
        'total_discrepancy_qty',
        'total_discrepancy_cost',
        'notes',
        'audited_by',
        'approved_by',
        'completed_at',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'completed_at' => 'datetime',
        'total_system_damaged' => 'decimal:2',
        'total_actual_damaged' => 'decimal:2',
        'total_discrepancy_qty' => 'decimal:2',
        'total_discrepancy_cost' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audited_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockQuarantineAuditItem::class, 'audit_id');
    }
}

