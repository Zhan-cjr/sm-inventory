<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CustomerAddress extends Model
{
    use HasUuids;

    protected $fillable = [
        'customer_id',
        'label',
        'recipient_name',
        'recipient_phone',
        'full_address',
        'biteship_area_id',
        'latitude',
        'longitude',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
