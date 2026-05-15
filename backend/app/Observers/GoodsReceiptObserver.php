<?php

namespace App\Observers;

use App\Models\GoodsReceipt;
use App\Models\Stock;
use App\Models\StockBatch;

class GoodsReceiptObserver
{
    /**
     * Handle the GoodsReceipt "deleting" event.
     */
    public function deleting(GoodsReceipt $goodsReceipt): void
    {
        foreach ($goodsReceipt->items as $item) {
            // Find the main stock
            $stock = Stock::where('branch_id', $goodsReceipt->branch_id)
                ->where('product_id', $item->product_id)
                ->first();

            if ($stock) {
                $stock->log_type = 'PURCHASE_CANCEL';
                $stock->reason_code = 'GR_DELETE';
                $stock->reference_doc_type = 'GOODS_RECEIPT';
                $stock->reference_doc_id = $goodsReceipt->id;
                $stock->notes = 'Pembatalan Penerimaan ' . $goodsReceipt->receipt_number;
                
                $stock->decrement('quantity_on_hand', $item->quantity_received);
            }

            // Remove associated batches
            StockBatch::where('reference_doc_type', 'GOODS_RECEIPT')
                ->where('reference_doc_id', $goodsReceipt->id)
                ->where('product_id', $item->product_id)
                ->delete();
        }
    }
}
