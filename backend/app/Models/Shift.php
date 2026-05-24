<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'branch_id', 'terminal_id', 'shift_name', 'start_time', 
        'end_time', 'starting_cash', 'total_cash_in', 'total_cash_out', 'total_cash_sales', 
        'total_card_sales', 'total_voucher_sales', 'total_cash_returns', 'total_card_returns', 'actual_cash', 'difference', 
        'status', 'notes'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(Terminal::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }
}
