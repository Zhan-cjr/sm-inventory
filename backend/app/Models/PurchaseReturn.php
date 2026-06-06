<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PurchaseReturn extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'supplier_id',
        'goods_receipt_id',
        'return_number',
        'return_date',
        'status',
        'total_amount',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
