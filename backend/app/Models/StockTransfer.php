<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockTransfer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reference_number',
        'from_branch_id',
        'to_branch_id',
        'status',
        'transfer_date',
        'received_date',
        'created_by',
        'received_by',
        'notes',
        'total_amount',
    ];

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
