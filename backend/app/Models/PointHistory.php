<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointHistory extends Model
{
    use HasUuids;

    protected $fillable = [
        'customer_id',
        'points',
        'before_points',
        'after_points',
        'reference_type',
        'reference_id',
        'description',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
