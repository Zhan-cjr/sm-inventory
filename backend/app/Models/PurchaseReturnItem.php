<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PurchaseReturnItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'purchase_return_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'reason',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
