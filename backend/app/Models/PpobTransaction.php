<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PpobTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'transaction_id', 'ref_id', 'customer_no', 'customer_name', 'buyer_sku_code',
        'price', 'status', 'rc', 'sn', 'message', 'raw_response'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'raw_response' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
