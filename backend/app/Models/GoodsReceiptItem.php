<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'goods_receipt_id', 'product_id', 'quantity_ordered', 
        'quantity_received', 'unit_price', 'discount_1', 'discount_2', 'discount_3', 'subtotal'
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_1' => 'decimal:2',
        'discount_2' => 'decimal:2',
        'discount_3' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
