<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Kontrabon extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'kontrabon_number',
        'tanggal_kontrabon',
        'tanggal_jatuh_tempo',
        'supplier_id',
        'supplier_division_id',
        'branch_id',
        'total_amount',
        'paid_amount',
        'status',
        'notes',
        'created_by_id',
    ];

    public function items()
    {
        return $this->hasMany(KontrabonItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function division()
    {
        return $this->belongsTo(SupplierDivision::class, 'supplier_division_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function kontrabonDeductions()
    {
        return $this->hasMany(KontrabonDeduction::class);
    }

    public function goodsReceipts()
    {
        return $this->belongsToMany(GoodsReceipt::class, 'kontrabon_items', 'kontrabon_id', 'goods_receipt_id')
            ->withPivot('amount');
    }
}
