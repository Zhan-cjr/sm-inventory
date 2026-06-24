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
        'branch_id', 'product_id', 'cost_price', 'cost_price_tax', 'selling_price', 
        'margin_gol_1', 'harga_jual_1', 'qty_min_gol_1',
        'margin_gol_2', 'harga_jual_2', 'qty_min_gol_2',
        'margin_gol_3', 'harga_jual_3', 'qty_min_gol_3',
        'quantity_on_hand', 
        'quantity_reserved', 'last_count_date', 'min_qty', 
        'max_qty', 'lead_time', 'safety_stock', 'desired_inventory_days', 'version', 'is_active'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'cost_price_tax' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'margin_gol_1' => 'decimal:2',
        'harga_jual_1' => 'decimal:2',
        'margin_gol_2' => 'decimal:2',
        'harga_jual_2' => 'decimal:2',
        'margin_gol_3' => 'decimal:2',
        'harga_jual_3' => 'decimal:2',
        'qty_min_gol_1' => 'integer',
        'qty_min_gol_2' => 'integer',
        'qty_min_gol_3' => 'integer',
        'lead_time' => 'integer',
        'safety_stock' => 'integer',
        'desired_inventory_days' => 'integer',
        'is_active' => 'boolean',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
