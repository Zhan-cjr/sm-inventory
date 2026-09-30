<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Bank extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'code',
        'type',
        'min_transaction_amount',
        'is_active',
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'min_transaction_amount' => 'float',
    ];
}
