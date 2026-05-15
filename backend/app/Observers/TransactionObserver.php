<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\Stock;
use App\Models\StockBatch;
use App\Models\StockBatchDeduction;

class TransactionObserver
{
    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        // If transaction is voided
        if ($transaction->isDirty('is_voided') && $transaction->is_voided) {
            foreach ($transaction->items as $item) {
                // Return stock to main inventory
                $stock = Stock::where('branch_id', $transaction->branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($stock) {
                    $stock->log_type = 'SALE_VOID';
                    $stock->reason_code = 'POS_VOID';
                    $stock->reference_doc_type = 'TRANSACTION';
                    $stock->reference_doc_id = $transaction->id;
                    $stock->notes = 'Void Penjualan ' . $transaction->local_transaction_id;
                    
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
    }
    /**
     * Handle the Transaction "deleting" event.
     */
    public function deleting(Transaction $transaction): void
    {
        // If the transaction was NOT voided, return the stock
        // If it was already voided, stock has been returned previously
        if (!$transaction->is_voided) {
            foreach ($transaction->items as $item) {
                // By calling delete() on the item, we trigger TransactionItemObserver::deleting
                $item->delete();
            }
        } else {
            // Just delete items without triggering stock return (since already voided)
            // We can bypass the observer by using the query builder
            $transaction->items()->delete();
        }
    }
}
