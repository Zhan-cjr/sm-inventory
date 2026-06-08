<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupplierDeduction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'supplier_id',
        'branch_id',
        'deduction_type',
        'reference_id',
        'amount',
        'claimed_amount',
        'status',
        'notes',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function kontrabonDeductions()
    {
        return $this->hasMany(KontrabonDeduction::class);
    }
}
