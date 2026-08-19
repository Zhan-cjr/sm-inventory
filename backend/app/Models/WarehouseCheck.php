<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\HasApprovals;

class WarehouseCheck extends Model
{
    use HasUuids, HasApprovals;

    protected $fillable = [
        'purchase_order_id',
        'branch_id',
        'checked_by',
        'status',
        'notes',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseCheckItem::class);
    }

    public function syncStatus(): void
    {
        if (in_array($this->status, ['pending', 'pending_approval', 'rejected'])) {
            return;
        }

        $items = $this->items;
        $totalScanned = (float) $items->sum('qty_scanned');
        if ($totalScanned <= 0) {
            return;
        }

        $grIds = GoodsReceipt::where('status', '!=', 'CANCELLED')
            ->where(function ($q) {
                $q->where('warehouse_check_id', $this->id);
                if ($this->purchase_order_id) {
                    $q->orWhere(function ($subQ) {
                        $subQ->where('purchase_order_id', $this->purchase_order_id)
                             ->whereNull('warehouse_check_id');
                    });
                }
            })
            ->pluck('id');

        if ($grIds->isEmpty()) {
            $newStatus = 'approved';
        } else {
            $totalReceivedForCheckItems = 0;
            foreach ($items as $checkItem) {
                if ($checkItem->qty_scanned <= 0) {
                    continue;
                }

                $alreadyReceived = GoodsReceiptItem::whereIn('goods_receipt_id', $grIds)
                    ->where('product_id', $checkItem->product_id)
                    ->sum('quantity_received');

                $totalReceivedForCheckItems += min((float) $checkItem->qty_scanned, (float) $alreadyReceived);
            }

            if ($totalReceivedForCheckItems >= $totalScanned) {
                $newStatus = 'processed';
            } elseif ($totalReceivedForCheckItems > 0) {
                $newStatus = 'partially_processed';
            } else {
                $newStatus = 'approved';
            }
        }

        if ($this->status !== $newStatus) {
            $this->update(['status' => $newStatus]);
        }
    }
}
