<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KontrabonDeduction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'kontrabon_id',
        'supplier_deduction_id',
        'amount_applied',
    ];

    public function kontrabon()
    {
        return $this->belongsTo(Kontrabon::class);
    }

    public function supplierDeduction()
    {
        return $this->belongsTo(SupplierDeduction::class);
    }
}
