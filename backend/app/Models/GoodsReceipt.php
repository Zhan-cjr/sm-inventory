<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use HasUuids;

    protected $fillable = [
        'warehouse_check_id', 'purchase_order_id', 'supplier_id', 'supplier_division_id', 'branch_id', 
        'receipt_number', 'receipt_date', 'received_by', 'faktur_image',
        'faktur_supplier', 'total_amount', 'include_tax', 'tax_amount', 'status', 'notes',
        'due_date', 'payment_status', 'paid_amount', 'payment_method'
    ];

    protected $casts = [
        'receipt_date' => 'datetime',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'include_tax' => 'boolean',
        'faktur_image' => 'array',
    ];

    public function warehouseCheck(): BelongsTo
    {
        return $this->belongsTo(WarehouseCheck::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(SupplierDivision::class, 'supplier_division_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function purchasePaymentItems()
    {
        return $this->hasMany(PurchasePaymentItem::class);
    }

    public function kontrabonItems()
    {
        return $this->hasMany(KontrabonItem::class);
    }

    protected static function booted()
    {
        static::created(function ($goodsReceipt) {
            // Sync status Pengecekan Gudang if it exists for this PO
            if ($goodsReceipt->purchase_order_id) {
                \App\Models\WarehouseCheck::where('purchase_order_id', $goodsReceipt->purchase_order_id)
                    ->where('status', '!=', 'processed')
                    ->update(['status' => 'processed']);
            }
        });
    }
}
