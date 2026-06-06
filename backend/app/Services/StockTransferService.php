<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\Stock;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class StockTransferService
{
    /**
     * Mark transfer as in transit and deduct stock from source branch
     */
    public function markAsInTransit(StockTransfer $transfer, $userId)
    {
        if ($transfer->status !== 'pending') {
            throw new Exception("Hanya transfer berstatus pending yang dapat dikirim.");
        }

        DB::beginTransaction();
        try {
            $transfer->status = 'in_transit';
            $transfer->save();

            foreach ($transfer->items as $item) {
                $stock = Stock::firstOrCreate(
                    [
                        'branch_id' => $transfer->from_branch_id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'quantity_on_hand' => 0,
                    ]
                );

                if ($stock->quantity_on_hand < $item->quantity) {
                    throw new Exception("Stok tidak mencukupi untuk produk ID: {$item->product_id}");
                }

                $stock->quantity_on_hand -= $item->quantity;
                $stock->log_type = 'TRANSFER_OUT';
                $stock->reason_code = 'STOCK_TRANSFER';
                $stock->reference_doc_type = 'STOCK_TRANSFER';
                $stock->reference_doc_id = $transfer->id;
                $stock->recorded_by = $userId;
                $stock->notes = "Transfer out to Branch ID: {$transfer->to_branch_id}";
                $stock->save();
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark transfer as received and add stock to destination branch
     */
    public function markAsReceived(StockTransfer $transfer, $userId)
    {
        if ($transfer->status !== 'in_transit') {
            throw new Exception("Hanya transfer berstatus in_transit yang dapat diterima.");
        }

        DB::beginTransaction();
        try {
            $transfer->status = 'received';
            $transfer->received_date = now()->toDateString();
            $transfer->received_by = $userId;
            $transfer->save();

            foreach ($transfer->items as $item) {
                $stock = Stock::firstOrCreate(
                    [
                        'branch_id' => $transfer->to_branch_id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'quantity_on_hand' => 0,
                    ]
                );

                $stock->quantity_on_hand += $item->quantity;
                $stock->log_type = 'TRANSFER_IN';
                $stock->reason_code = 'STOCK_TRANSFER';
                $stock->reference_doc_type = 'STOCK_TRANSFER';
                $stock->reference_doc_id = $transfer->id;
                $stock->recorded_by = $userId;
                $stock->notes = "Transfer in from Branch ID: {$transfer->from_branch_id}";
                $stock->save();
            }

            // Catat jurnal akuntansi
            app(\App\Services\AccountingService::class)->recordStockTransferJournal($transfer);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel or reject transfer
     */
    public function rejectTransfer(StockTransfer $transfer, $userId)
    {
        if (!in_array($transfer->status, ['pending', 'in_transit'])) {
            throw new Exception("Transfer ini tidak dapat dibatalkan/ditolak pada status saat ini.");
        }

        DB::beginTransaction();
        try {
            $oldStatus = $transfer->status;
            
            $transfer->status = 'rejected';
            $transfer->save();

            // Jika sebelumnya sudah in_transit (barang sudah dipotong), maka harus dikembalikan ke asal
            if ($oldStatus === 'in_transit') {
                foreach ($transfer->items as $item) {
                    $stock = Stock::where('branch_id', $transfer->from_branch_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($stock) {
                        $stock->quantity_on_hand += $item->quantity;
                        $stock->log_type = 'TRANSFER_REJECTED';
                        $stock->reason_code = 'STOCK_TRANSFER';
                        $stock->reference_doc_type = 'STOCK_TRANSFER';
                        $stock->reference_doc_id = $transfer->id;
                        $stock->recorded_by = $userId;
                        $stock->notes = "Transfer ditolak, stok dikembalikan";
                        $stock->save();
                    }
                }
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
