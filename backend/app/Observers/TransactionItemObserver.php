<?php

namespace App\Observers;

use App\Models\TransactionItem;
use App\Models\Stock;
use App\Models\StockBatch;
use App\Models\StockBatchDeduction;
use Illuminate\Support\Facades\DB;

class TransactionItemObserver
{
    public function created(TransactionItem $item): void
    {
        $tx = $item->transaction;
        if (!$tx) return;

        // Skip if service
        if ($item->service_id) return;

        // Update Stock
        $stock = Stock::where('branch_id', $tx->branch_id)
            ->where('product_id', $item->product_id)
            ->first();

        if ($stock) {
            $stock->log_type = 'SALE';
            $stock->reason_code = 'POS_SALE_ITEM';
            $stock->reference_doc_type = 'TRANSACTION';
            $stock->reference_doc_id = $tx->id;
            
            $stock->decrement('quantity_on_hand', $item->quantity);
        }

        // FIFO Deduction from Stock Batches
        $qtyToDeduct = $item->quantity;
        $batches = StockBatch::where('product_id', $item->product_id)
            ->where('branch_id', $tx->branch_id)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('entry_date', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($qtyToDeduct <= 0) break;

            $deduction = min($qtyToDeduct, $batch->remaining_quantity);
            $batch->decrement('remaining_quantity', $deduction);
            
            // Record deduction
            StockBatchDeduction::create([
                'stock_batch_id' => $batch->id,
                'transaction_item_id' => $item->id,
                'quantity' => $deduction,
            ]);

            $qtyToDeduct -= $deduction;
        }
    }

    public function deleting(TransactionItem $item): void
    {
        $tx = $item->transaction;
        if (!$tx) return;

        // Return stock to main inventory
        $stock = Stock::where('branch_id', $tx->branch_id)
            ->where('product_id', $item->product_id)
            ->first();

        if ($stock) {
            $stock->log_type = 'SALE_CANCEL';
            $stock->reason_code = 'POS_ITEM_REMOVE';
            $stock->reference_doc_type = 'TRANSACTION';
            $stock->reference_doc_id = $tx->id;
            
            $stock->increment('quantity_on_hand', $item->quantity);
        }

        // Return stock to batches
        $deductions = StockBatchDeduction::where('transaction_item_id', $item->id)->get();
        foreach ($deductions as $deduction) {
            $batch = StockBatch::find($deduction->stock_batch_id);
            if ($batch) {
                $batch->increment('remaining_quantity', $deduction->quantity);
            }
        }
    }
}
