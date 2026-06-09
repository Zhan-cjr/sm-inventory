<?php

namespace App\Observers;

use App\Models\TransactionItem;
use App\Models\Stock;
use App\Models\StockBatch;
use App\Models\StockBatchDeduction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionItemObserver
{
    /**
     * Deduct stock and FIFO batches for a given product and quantity.
     */
    private function deductStock(TransactionItem $item, string $branchId, string $productId, float $quantity, string $docId): void
    {
        $stock = Stock::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->log_type         = 'SALE';
            $stock->reason_code      = 'POS_SALE_ITEM';
            $stock->reference_doc_type = 'TRANSACTION';
            $stock->reference_doc_id = $docId;
            $stock->log_date         = $item->transaction->transaction_date ?? now();
            $stock->quantity_on_hand -= $quantity;
            $stock->save();
        }

        // FIFO Deduction from Stock Batches
        $qtyToDeduct = $quantity;
        $batches = StockBatch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('entry_date', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($qtyToDeduct <= 0) break;

            $deduction = min($qtyToDeduct, $batch->remaining_quantity);
            $batch->decrement('remaining_quantity', $deduction);

            StockBatchDeduction::create([
                'stock_batch_id'      => $batch->id,
                'transaction_item_id' => $item->id,
                'quantity'            => $deduction,
            ]);

            $qtyToDeduct -= $deduction;
        }
    }

    /**
     * Restore stock and FIFO batches for a given product and quantity.
     */
    private function restoreStock(TransactionItem $item, string $branchId, string $productId, float $quantity, string $docId): void
    {
        $stock = Stock::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->log_type         = 'SALE_CANCEL';
            $stock->reason_code      = 'POS_ITEM_REMOVE';
            $stock->reference_doc_type = 'TRANSACTION';
            $stock->reference_doc_id = $docId;
            $stock->quantity_on_hand += $quantity;
            $stock->save();
        }

        // Return stock to FIFO batches
        $deductions = StockBatchDeduction::where('transaction_item_id', $item->id)->get();
        foreach ($deductions as $deduction) {
            $batch = StockBatch::find($deduction->stock_batch_id);
            if ($batch) {
                $batch->increment('remaining_quantity', $deduction->quantity);
            }
        }
    }

    public function created(TransactionItem $item): void
    {
        $tx = $item->transaction;
        if (!$tx) return;

        // Skip if service
        if ($item->service_id) return;

        // Skip if digital product
        if ($item->product && $item->product->product_type === 'digital') return;

        // Skip if this is a PARENT ASSEMBLY PACKAGE:
        // The parent item itself doesn't hold physical stock;
        // its assembly components (is_assembly_component=true) handle stock deduction.
        if ($item->product && $item->product->assemblies()->exists()) {
            Log::debug("[Observer] Skipping parent assembly product: {$item->product_id}");
            return;
        }

        // For regular items AND assembly component items, deduct stock normally.
        $this->deductStock($item, $tx->branch_id, $item->product_id, $item->quantity, $tx->id);
    }

    public function deleting(TransactionItem $item): void
    {
        $tx = $item->transaction;
        if (!$tx) return;

        // Skip if service
        if ($item->service_id) return;

        // Skip if digital product
        if ($item->product && $item->product->product_type === 'digital') return;

        // Skip if parent assembly package
        if ($item->product && $item->product->assemblies()->exists()) {
            Log::debug("[Observer] Skipping restore for parent assembly product: {$item->product_id}");
            return;
        }

        // Restore stock for regular items and assembly components
        $this->restoreStock($item, $tx->branch_id, $item->product_id, $item->quantity, $tx->id);
    }
}
