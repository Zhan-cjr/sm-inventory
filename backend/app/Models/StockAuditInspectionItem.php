<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockAuditInspectionItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'inspection_id',
        'product_id',
        'rack_id',
        'system_quantity',
        'delta_sales_quantity',
        'physical_quantity',
        'discrepancy_quantity',
        'unit_cost',
        'discrepancy_cost',
        'status',
        'clarification_notes',
    ];

    protected $casts = [
        'system_quantity'      => 'decimal:2',
        'delta_sales_quantity' => 'decimal:2',
        'physical_quantity'    => 'decimal:2',
        'discrepancy_quantity' => 'decimal:2',
        'unit_cost'            => 'decimal:2',
        'discrepancy_cost'     => 'decimal:2',
    ];

    public function inspection()
    {
        return $this->belongsTo(StockAuditInspection::class, 'inspection_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function rack()
    {
        return $this->belongsTo(StockOpnameRack::class, 'rack_id');
    }
}

