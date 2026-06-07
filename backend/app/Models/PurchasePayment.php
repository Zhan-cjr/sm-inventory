<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PurchasePayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'payment_number',
        'payment_date',
        'supplier_id',
        'branch_id',
        'payment_method',
        'reference_number',
        'total_amount',
        'notes',
        'status',
        'created_by_id',
    ];

    public function items()
    {
        return $this->hasMany(PurchasePaymentItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
