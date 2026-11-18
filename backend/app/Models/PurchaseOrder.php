<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseOrder extends Model
{
    use HasUuids, LogsActivity, \App\Models\Traits\HasApprovals;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'organization_id', 'branch_id', 'supplier_id', 'supplier_division_id', 'po_number', 
        'po_date', 'faktur', 'expected_delivery_date', 'status', 
        'total_amount', 'include_tax', 'tax_amount', 'notes', 'over_order_reason', 'created_by',
        'po_expired_days', 'expired_date'
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery_date' => 'date',
        'expired_date' => 'date',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'include_tax' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($po) {
            if ($po->isDirty('expired_date') || $po->isDirty('po_date')) {
                if ($po->expired_date && $po->po_date) {
                    $po->po_expired_days = $po->po_date->diffInDays($po->expired_date, false);
                }
            }
        });
    }

    public function onApproved()
    {
        if ($this->po_expired_days > 0) {
            $this->update([
                'expired_date' => now()->addDays($this->po_expired_days)->format('Y-m-d')
            ]);
        }
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(SupplierDivision::class, 'supplier_division_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function warehouseChecks(): HasMany
    {
        return $this->hasMany(WarehouseCheck::class);
    }
public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function remainingQuantity(): float
    {
        $totalOrdered = $this->items()->sum('quantity_ordered');
        
        $totalChecked = WarehouseCheckItem::whereHas('warehouseCheck', function ($q) {
            $q->where('purchase_order_id', $this->id)
              ->whereIn('status', ['pending', 'pending_approval', 'approved', 'processed']);
        })->sum('qty_scanned');

        return max(0, $totalOrdered - $totalChecked);
    }

    /**
     * Generate a unique, collision-free PO number: PO-{BRANCH_CODE}-{YYMMDD}-{SEQ4}.
     */
    public static function generatePoNumber(?string $branchId = null, ?string $poDate = null): string
    {
        $branchCode = 'PST';
        if (!empty($branchId)) {
            $code = Branch::where('id', $branchId)->value('code');
            if (!empty($code)) {
                $branchCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
            }
        }

        $dateStr = $poDate ? \Carbon\Carbon::parse($poDate)->format('ymd') : now()->format('ymd');
        $prefix = "PO-{$branchCode}-{$dateStr}-";

        return \Illuminate\Support\Facades\Cache::lock("po_seq_{$branchCode}_{$dateStr}", 10)->block(5, function () use ($prefix) {
            $lastPo = self::where('po_number', 'LIKE', $prefix . '%')
                ->orderByRaw('CAST(SUBSTRING_INDEX(po_number, "-", -1) AS UNSIGNED) DESC')
                ->value('po_number');

            $nextSeq = 1;
            if ($lastPo) {
                $parts = explode('-', $lastPo);
                $nextSeq = ((int) end($parts)) + 1;
            }

            do {
                $candidate = $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
                $exists = self::where('po_number', $candidate)->exists();
                if ($exists) {
                    $nextSeq++;
                }
            } while ($exists);

            return $candidate;
        });
    }
}
