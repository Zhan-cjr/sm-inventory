<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Promotion extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id', 'name', 'promo_type', 'discount_value',
        'min_purchase_amount', 'applicable_to', 'target_ids',
        'valid_from', 'valid_until', 'max_discount_per_transaction',
        'is_active', 'promo_config'
    ];

    protected $casts = [
        'target_ids' => 'array',
        'promo_config' => 'array',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'discount_value' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'max_discount_per_transaction' => 'decimal:2',
    ];

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
