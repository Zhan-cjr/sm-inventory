<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MarketBasketRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'antecedent_id',
        'consequent_id',
        'antecedent_name',
        'consequent_name',
        'support',
        'confidence',
        'lift',
    ];

    public function antecedent()
    {
        return $this->belongsTo(Product::class, 'antecedent_id');
    }

    public function consequent()
    {
        return $this->belongsTo(Product::class, 'consequent_id');
    }
}
