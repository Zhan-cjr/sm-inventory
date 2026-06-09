<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAssembly extends Model
{
    protected $fillable = [
        'parent_product_id',
        'child_product_id',
        'quantity',
    ];

    public function parentProduct()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function childProduct()
    {
        return $this->belongsTo(Product::class, 'child_product_id');
    }
}
