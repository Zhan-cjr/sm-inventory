<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InventoryLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id', 'product_id', 'log_type', 'quantity_before', 'quantity_change', 'quantity_after',
        'reason_code', 'reference_doc_type', 'reference_doc_id',
        'recorded_by', 'notes'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
