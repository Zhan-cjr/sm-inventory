<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'name',
        'nominal_value',
        'valid_until',
        'is_used',
        'used_at',
        'transaction_id',
    ];

    protected $casts = [
        'nominal_value' => 'decimal:2',
        'valid_until' => 'datetime',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
