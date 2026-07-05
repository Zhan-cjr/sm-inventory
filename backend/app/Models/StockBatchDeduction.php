<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockBatchDeduction extends Model
{
    use HasUuids;

    protected $fillable = [
        'stock_batch_id', 
        'transaction_item_id',
        'ecommerce_order_item_id',
        'quantity'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function stockBatch()
    {
        return $this->belongsTo(StockBatch::class);
    }

    public function transactionItem()
    {
        return $this->belongsTo(TransactionItem::class);
    }
}
