<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasUuids;

    protected $attributes = [
        'quantity_received' => 0,
    ];

    protected $fillable = [
        'purchase_order_id', 'product_id', 'quantity_suggested', 'quantity_ordered', 
        'quantity_received', 'unit_cost', 'discount_1', 'discount_2', 'discount_3', 'subtotal'
    ];

    protected $casts = [
        'quantity_suggested' => 'decimal:2',
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'discount_1' => 'decimal:2',
        'discount_2' => 'decimal:2',
        'discount_3' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
