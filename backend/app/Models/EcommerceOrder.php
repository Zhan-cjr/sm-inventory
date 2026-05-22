<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EcommerceOrder extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'customer_name',
        'customer_phone',
        'delivery_method',
        'delivery_address',
        'status',
        'total_amount',
        'notes'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(EcommerceOrderItem::class, 'ecommerce_order_id');
    }
}
