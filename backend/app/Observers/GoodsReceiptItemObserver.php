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
        $stock->reference_doc_id = $gr->receipt_number ?: $gr->id;
        $stock->notes = 'Penerimaan Barang ' . $gr->receipt_number;
        
        $stock->increment('quantity_on_hand', $item->quantity_received);

        $finalCostPrice = $this->calculateCostPrice($item, $gr);

        // Create Stock Batch for FIFO
        StockBatch::create([
            'product_id' => $item->product_id,
            'branch_id' => $gr->branch_id,
            'reference_doc_type' => 'GOODS_RECEIPT',
            'reference_doc_id' => $gr->id,
            'initial_quantity' => $item->quantity_received,
            'remaining_quantity' => $item->quantity_received,
            'cost_price' => $finalCostPrice,
            'entry_date' => $gr->receipt_date->format('Y-m-d') . ' ' . date('H:i:s'),
        ]);
    }

    public function updated(GoodsReceiptItem $item): void
    {
        $gr = $item->goodsReceipt;
        if (!$gr) return;

        $oldQty = (int) $item->getOriginal('quantity_received');
        $newQty = (int) $item->quantity_received;
        $diff = $newQty - $oldQty;

        if ($diff !== 0) {
            $stock = Stock::firstOrCreate(
                ['branch_id' => $gr->branch_id, 'product_id' => $item->product_id],
                ['quantity_on_hand' => 0]
            );

            if ($diff > 0) {
                $stock->log_type = 'PURCHASE';
                $stock->reason_code = 'GR_ITEM_ADJUST';
                $stock->reference_doc_type = 'GOODS_RECEIPT';
                $stock->reference_doc_id = $gr->receipt_number ?: $gr->id;
                $stock->notes = 'Penyesuaian Tambah Item Penerimaan ' . $gr->receipt_number;
                $stock->increment('quantity_on_hand', $diff);
            } else {
                $stock->log_type = 'PURCHASE_CANCEL';
                $stock->reason_code = 'GR_ITEM_ADJUST';
                $stock->reference_doc_type = 'GOODS_RECEIPT';
                $stock->reference_doc_id = $gr->receipt_number ?: $gr->id;
                $stock->notes = 'Penyesuaian Kurang Item Penerimaan ' . $gr->receipt_number;
                $stock->decrement('quantity_on_hand', abs($diff));
            }
        }

        $finalCostPrice = $this->calculateCostPrice($item, $gr);

        $batch = StockBatch::where('reference_doc_type', 'GOODS_RECEIPT')
            ->where('reference_doc_id', $gr->id)
            ->where('product_id', $item->product_id)
            ->first();

        if ($batch) {
            $batch->cost_price = $finalCostPrice;
            if ($diff !== 0) {
                $batch->initial_quantity = max(0, (int) $batch->initial_quantity + $diff);
                $batch->remaining_quantity = max(0, (int) $batch->remaining_quantity + $diff);
            }
            $batch->save();
        } elseif ($newQty > 0) {
            StockBatch::create([
                'product_id' => $item->product_id,
                'branch_id' => $gr->branch_id,
                'reference_doc_type' => 'GOODS_RECEIPT',
                'reference_doc_id' => $gr->id,
                'initial_quantity' => $newQty,
                'remaining_quantity' => $newQty,
                'cost_price' => $finalCostPrice,
                'entry_date' => $gr->receipt_date->format('Y-m-d') . ' ' . date('H:i:s'),
            ]);
        }
    }

    private function calculateCostPrice(GoodsReceiptItem $item, $gr): float
    {
        $taxRate = (float) \App\Services\RetailIntelligenceService::getActiveTaxRate();
        $taxMultiplier = 1 + ($taxRate / 100);

        $netPrice = $item->quantity_received > 0 ? ((float) $item->subtotal / $item->quantity_received) : $item->unit_price;
        $isTaxable = (bool) ($item->product?->is_taxable ?? true);

        return ($gr->include_tax && $isTaxable) ? round($netPrice * $taxMultiplier, 2) : (float) $netPrice;
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
            $stock->reference_doc_id = $gr->receipt_number ?: $gr->id;
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
