<?php

namespace App\Observers;

use App\Models\Stock;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\Auth;

class StockObserver
{
    /**
     * Handle the Stock "updated" event.
     */
    public function updated(Stock $stock): void
    {
        if ($stock->isDirty('quantity_on_hand')) {
            $oldQty = $stock->getOriginal('quantity_on_hand');
            $newQty = $stock->quantity_on_hand;
            $change = $newQty - $oldQty;

            $logData = [
                'branch_id' => $stock->branch_id,
                'product_id' => $stock->product_id,
                'log_type' => $stock->log_type ?? 'ADJUSTMENT',
                'quantity_before' => $oldQty,
                'quantity_change' => $change,
                'quantity_after' => $newQty,
                'reason_code' => $stock->reason_code ?? 'MANUAL_UPDATE',
                'reference_doc_type' => $stock->reference_doc_type ?? null,
                'reference_doc_id' => $stock->reference_doc_id ?? null,
                'recorded_by' => $stock->recorded_by ?? Auth::id() ?? '00000000-0000-0000-0000-000000000000',
                'notes' => $stock->notes ?? 'Updated via Stock Management',
                'balance' => $newQty,
            ];

            if (!empty($stock->log_date)) {
                $logData['created_at'] = $stock->log_date;
                $logData['updated_at'] = $stock->log_date;
            }

            InventoryLog::create($logData);
        }
    }

    /**
     * Handle the Stock "created" event.
     */
    public function created(Stock $stock): void
    {
        if ($stock->quantity_on_hand != 0) {
            $logData = [
                'branch_id' => $stock->branch_id,
                'product_id' => $stock->product_id,
                'log_type' => $stock->log_type ?? 'INITIAL_STOCK',
                'quantity_before' => 0,
                'quantity_change' => $stock->quantity_on_hand,
                'quantity_after' => $stock->quantity_on_hand,
                'reason_code' => $stock->reason_code ?? 'INITIALIZATION',
                'reference_doc_type' => $stock->reference_doc_type ?? null,
                'reference_doc_id' => $stock->reference_doc_id ?? null,
                'recorded_by' => $stock->recorded_by ?? Auth::id() ?? '00000000-0000-0000-0000-000000000000',
                'notes' => $stock->notes ?? 'Updated via Stock Management',
                'balance' => $stock->quantity_on_hand,
            ];

            if (!empty($stock->log_date)) {
                $logData['created_at'] = $stock->log_date;
                $logData['updated_at'] = $stock->log_date;
            }

            InventoryLog::create($logData);
        }
    }
}
