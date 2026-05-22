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
    public $recorded_by;
    public $log_date;

    protected $fillable = [
        'branch_id', 'product_id', 'cost_price', 'selling_price', 'quantity_on_hand', 
        'quantity_reserved', 'last_count_date', 'min_qty', 
        'max_qty', 'lead_time', 'safety_stock', 'desired_inventory_days', 'version'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'lead_time' => 'integer',
        'safety_stock' => 'integer',
        'desired_inventory_days' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function racks(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(StockOpnameRack::class, 'stock_stock_opname_rack', 'stock_id', 'rack_id');
    }
}
