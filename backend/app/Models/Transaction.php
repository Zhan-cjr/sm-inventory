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

    /**
     * Hitung total COGS (HPP) untuk sekumpulan ID transaksi secara batch dalam 1 query SQL
     * (menghilangkan N+1 pada Dashboard, StatsOverview, dan Laporan).
     */
    public static function calculateTotalCogsForIds(array $transactionIds): float
    {
        if (empty($transactionIds)) {
            return 0.0;
        }

        $total = 0.0;

        foreach (array_chunk(array_values(array_unique($transactionIds)), 1000) as $chunk) {
            $batchSub = \Illuminate\Support\Facades\DB::table('stock_batch_deductions as sbd')
                ->join('stock_batches as sb', 'sbd.stock_batch_id', '=', 'sb.id')
                ->join('transaction_items as ti_sub', 'sbd.transaction_item_id', '=', 'ti_sub.id')
                ->whereIn('ti_sub.transaction_id', $chunk)
                ->groupBy('sbd.transaction_item_id')
                ->selectRaw('sbd.transaction_item_id, SUM(sbd.quantity * sb.cost_price) as batch_cogs');

            $chunkTotal = \Illuminate\Support\Facades\DB::table('transaction_items as ti')
                ->join('transactions as t', 'ti.transaction_id', '=', 't.id')
                ->leftJoinSub($batchSub, 'bd', function ($join) {
                    $join->on('ti.id', '=', 'bd.transaction_item_id');
                })
                ->leftJoin('stocks as st', function ($join) {
                    $join->on('st.product_id', '=', 'ti.product_id')
                        ->on('st.branch_id', '=', 't.branch_id');
                })
                ->leftJoin('products as p', 'p.id', '=', 'ti.product_id')
                ->whereIn('ti.transaction_id', $chunk)
                ->whereNotNull('ti.product_id')
                ->selectRaw('COALESCE(SUM(CASE
                    WHEN COALESCE(bd.batch_cogs, 0) > 0 THEN bd.batch_cogs
                    ELSE ti.quantity * (CASE
                        WHEN COALESCE(st.cost_price_tax, 0) > 0 THEN st.cost_price_tax
                        WHEN COALESCE(st.cost_price, 0) > 0 THEN st.cost_price
                        WHEN COALESCE(p.cost_price_tax, 0) > 0 THEN p.cost_price_tax
                        WHEN COALESCE(p.cost_price, 0) > 0 THEN p.cost_price
                        ELSE 0
                    END)
                END), 0) as total_cogs')
                ->value('total_cogs');

            $total += (float) ($chunkTotal ?? 0);
        }

        return $total;
    }

    public function getCogsAttribute()
    {
        if (empty($this->id)) {
            return 0.0;
        }

        return static::calculateTotalCogsForIds([$this->id]);
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
