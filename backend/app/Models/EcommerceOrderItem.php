<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EcommerceOrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'ecommerce_order_id',
        'product_id',
        'quantity',
        'price',
        'subtotal'
    ];

    public function order()
    {
        return $this->belongsTo(EcommerceOrder::class, 'ecommerce_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
