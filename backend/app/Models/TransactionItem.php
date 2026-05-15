<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TransactionItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'transaction_id', 'product_id', 'service_id', 'quantity', 
        'unit_price', 'discount_per_item'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_per_item' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
