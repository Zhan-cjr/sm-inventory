<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Stock extends Model
{
    use HasUuids;
    
    public $log_type;
    public $reason_code;
    public $notes;
    public $reference_doc_type;
    public $reference_doc_id;

    protected $fillable = [
        'branch_id', 'product_id', 'cost_price', 'selling_price', 'quantity_on_hand', 
        'quantity_reserved', 'last_count_date', 'min_qty', 
        'max_qty', 'version'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
