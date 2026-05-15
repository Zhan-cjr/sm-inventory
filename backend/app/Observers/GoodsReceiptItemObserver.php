<?php

namespace App\Observers;

use App\Models\GoodsReceiptItem;
use App\Models\Stock;
use App\Models\StockBatch;

class GoodsReceiptItemObserver
{
    public function created(GoodsReceiptItem $item): void
    {
        $gr = $item->goodsReceipt;
        if (!$gr) return;

        // Update Stock
        $stock = Stock::firstOrCreate(
            ['branch_id' => $gr->branch_id, 'product_id' => $item->product_id],
            ['quantity_on_hand' => 0]
        );

        $stock->log_type = 'PURCHASE';
        $stock->reason_code = 'GR_ITEM_ADD';
        $stock->reference_doc_type = 'GOODS_RECEIPT';
        $stock->reference_doc_id = $gr->id;
        $stock->notes = 'Penerimaan Barang ' . $gr->receipt_number;
        
        $stock->increment('quantity_on_hand', $item->quantity_received);

        // Create Stock Batch for FIFO
        StockBatch::create([
            'product_id' => $item->product_id,
            'branch_id' => $gr->branch_id,
            'reference_doc_type' => 'GOODS_RECEIPT',
            'reference_doc_id' => $gr->id,
            'initial_quantity' => $item->quantity_received,
            'remaining_quantity' => $item->quantity_received,
            'cost_price' => $item->unit_price,
            'entry_date' => $gr->receipt_date->format('Y-m-d') . ' ' . date('H:i:s'),
        ]);
    }

    public function deleting(GoodsReceiptItem $item): void
    {
        $gr = $item->goodsReceipt;
        if (!$gr) return;

        // Update Stock
        $stock = Stock::where('branch_id', $gr->branch_id)
            ->where('product_id', $item->product_id)
            ->first();

        if ($stock) {
            $stock->log_type = 'PURCHASE_CANCEL';
            $stock->reason_code = 'GR_ITEM_REMOVE';
            $stock->reference_doc_type = 'GOODS_RECEIPT';
            $stock->reference_doc_id = $gr->id;
            $stock->notes = 'Hapus Item Penerimaan ' . $gr->receipt_number;
            
            $stock->decrement('quantity_on_hand', $item->quantity_received);
        }

        // Remove associated batches
        StockBatch::where('reference_doc_type', 'GOODS_RECEIPT')
            ->where('reference_doc_id', $gr->id)
            ->where('product_id', $item->product_id)
            ->delete();
    }
}
