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

    protected static function booted()
    {
        static::saving(function ($promotion) {
            if ($promotion->discount_value === null || $promotion->discount_value === '') {
                $promotion->discount_value = 0;
            }
            if ($promotion->applicable_to === null || $promotion->applicable_to === '') {
                $promotion->applicable_to = 'ALL';
            }
        });
    }

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
