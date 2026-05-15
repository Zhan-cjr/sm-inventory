<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockBatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id', 
        'branch_id', 
        'reference_doc_type', 
        'reference_doc_id', 
        'initial_quantity', 
        'remaining_quantity', 
        'cost_price', 
        'entry_date'
    ];

    protected $casts = [
        'entry_date' => 'datetime',
        'initial_quantity' => 'decimal:4',
        'remaining_quantity' => 'decimal:4',
        'cost_price' => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
