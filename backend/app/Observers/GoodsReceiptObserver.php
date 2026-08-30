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

    /**
     * Handle the GoodsReceipt "deleted" event.
     */
    public function deleted(GoodsReceipt $goodsReceipt): void
    {
        if ($goodsReceipt->purchase_order_id) {
            $po = \App\Models\PurchaseOrder::with('items')->find($goodsReceipt->purchase_order_id);
            if ($po) {
                $allReceived = true;
                $anyReceived = false;

                foreach ($po->items as $poItem) {
                    $newQtyReceived = (float) \App\Models\GoodsReceiptItem::whereHas('goodsReceipt', function ($q) use ($goodsReceipt) {
                            $q->where('purchase_order_id', $goodsReceipt->purchase_order_id)
                              ->where('status', '!=', 'CANCELLED')
                              ->where('id', '!=', $goodsReceipt->id);
                        })
                        ->where('product_id', $poItem->product_id)
                        ->sum('quantity_received');

                    $poItem->update(['quantity_received' => $newQtyReceived]);

                    if ($newQtyReceived < $poItem->quantity_ordered) {
                        $allReceived = false;
                    }
                    if ($newQtyReceived > 0) {
                        $anyReceived = true;
                    }
                }

                if ($allReceived && $po->items->count() > 0) {
                    $po->update(['status' => 'RECEIVED']);
                } elseif ($anyReceived) {
                    $po->update(['status' => 'PARTIALLY_RECEIVED']);
                } else {
                    $po->update(['status' => 'APPROVED']);
                }

                $checks = \App\Models\WarehouseCheck::where('purchase_order_id', $goodsReceipt->purchase_order_id)->get();
                foreach ($checks as $check) {
                    $check->syncStatus();
                }
            }
        }

        if ($goodsReceipt->warehouse_check_id) {
            $check = \App\Models\WarehouseCheck::find($goodsReceipt->warehouse_check_id);
            $check?->syncStatus();
        }
    }
}
