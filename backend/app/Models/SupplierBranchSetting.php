<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBranchSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'supplier_id',
        'supplier_division_id',
        'branch_id',
        'lead_time_days',
        'inventory_days',
        'notes',
    ];

    protected $casts = [
        'lead_time_days' => 'integer',
        'inventory_days' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(function (SupplierBranchSetting $setting) {
            \App\Services\SuggestedOrderService::syncBranchStocksCycle(
                $setting->supplier_id,
                $setting->supplier_division_id,
                $setting->branch_id
            );
        });

        static::deleted(function (SupplierBranchSetting $setting) {
            \App\Services\SuggestedOrderService::syncBranchStocksCycle(
                $setting->supplier_id,
                $setting->supplier_division_id,
                $setting->branch_id
            );
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(SupplierDivision::class, 'supplier_division_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

