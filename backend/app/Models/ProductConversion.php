<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductConversion extends Model
{
    protected $fillable = [
        'source_product_id',
        'target_product_id',
        'conversion_qty',
        'auto_convert',
    ];

    protected $casts = [
        'auto_convert' => 'boolean',
    ];

    public function sourceProduct()
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }

    public function targetProduct()
    {
        return $this->belongsTo(Product::class, 'target_product_id');
    }
}
