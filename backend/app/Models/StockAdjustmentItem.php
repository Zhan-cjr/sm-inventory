<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'stock_adjustment_id', 'product_id', 'previous_quantity', 
        'adjustment_quantity', 'new_quantity', 'reason_code'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
