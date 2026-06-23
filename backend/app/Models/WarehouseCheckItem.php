<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseCheckItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'warehouse_check_id',
        'product_id',
        'qty_po',
        'qty_scanned',
    ];

    public function warehouseCheck(): BelongsTo
    {
        return $this->belongsTo(WarehouseCheck::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
