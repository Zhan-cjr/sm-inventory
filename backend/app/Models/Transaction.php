<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Transaction extends Model
{
    use HasUuids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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
            if (!$item->product_id) {
                return 0;
            }

            // 1. Cek stock_batch_deductions (FIFO Riil)
            $batchCogs = \Illuminate\Support\Facades\DB::table('stock_batch_deductions')
                ->join('stock_batches', 'stock_batch_deductions.stock_batch_id', '=', 'stock_batches.id')
                ->where('stock_batch_deductions.transaction_item_id', $item->id)
                ->sum(\Illuminate\Support\Facades\DB::raw('stock_batch_deductions.quantity * stock_batches.cost_price'));

            if ($batchCogs > 0) {
                return (float) $batchCogs;
            }

            // 2. Fallback seperti di Laporan HPP
            $stock = \App\Models\Stock::where('product_id', $item->product_id)
                ->where('branch_id', $this->branch_id)
                ->first();

            $stCostPriceTax = $stock ? $stock->cost_price_tax : 0;
            $stCostPrice = $stock ? $stock->cost_price : 0;
            $pCostPriceTax = $item->product ? $item->product->cost_price_tax : 0;
            $pCostPrice = $item->product ? $item->product->cost_price : 0;
            
            // Prioritas: st.cost_price_tax > st.cost_price > p.cost_price_tax > p.cost_price
            $fallbackPrice = $stCostPriceTax > 0 ? $stCostPriceTax : 
                ($stCostPrice > 0 ? $stCostPrice : 
                ($pCostPriceTax > 0 ? $pCostPriceTax : 
                ($pCostPrice > 0 ? $pCostPrice : 0)));

            return $item->quantity * (float) $fallbackPrice;
        });
    }

    public function getGrossProfitAttribute()
    {
        return $this->final_amount - $this->cogs;
    }

    /**
     * No transaksi versi pendek untuk tampilan di tabel.
     * Prioritas: receipt_number > local_transaction_id > 8 karakter UUID
     */
    public function getShortIdAttribute(): string
    {
        if (!empty($this->receipt_number)) {
            return $this->receipt_number;
        }
        if (!empty($this->local_transaction_id)) {
            return $this->local_transaction_id;
        }
        return strtoupper(substr($this->id, 0, 8));
    }

    public function ppobTransactions()
    {
        return $this->hasMany(PpobTransaction::class);
    }
}
