<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockQuarantineMovement extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'product_id',
        'movement_type',
        'quantity',
        'reason',
        'reference_doc_type',
        'reference_doc_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
