<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Transaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id', 'branch_id', 'terminal_id', 'shift_id', 'transaction_type', 
        'transaction_date', 'cashier_id', 'customer_id', 'total_amount', 
        'discount_amount', 'manual_discount', 'promo_discount', 'final_amount', 'payment_method', 
        'bank_id', 'received_amount', 'change_amount',
        'is_voided', 'void_reason', 'void_date', 'voided_by', 
        'sync_status', 'local_transaction_id', 'receipt_number', 'payment_details'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'is_voided' => 'boolean',
        'void_date' => 'datetime',
        'payment_details' => 'array',
    ];

    public function terminal()
    {
        return $this->belongsTo(Terminal::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function shift(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getCogsAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->product ? ($item->product->cost_price * $item->quantity) : 0;
        });
    }

    public function getGrossProfitAttribute()
    {
        return $this->final_amount - $this->cogs;
    }
}
