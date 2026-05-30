<?php

namespace App\Observers;

use App\Models\EcommerceOrder;
use App\Services\WhatsappService;

class EcommerceOrderObserver
{
    /**
     * Handle the EcommerceOrder "updated" event.
     */
    public function updated(EcommerceOrder $order): void
    {
        if ($order->isDirty('status')) {
            $status = $order->status;
            $phone = $order->customer_phone;
            $name = $order->customer_name;
            $orderId = strtoupper(substr($order->id, 0, 8)); // Short display ID
            $total = number_format($order->total_amount, 0, ',', '.');

            $message = '';

            switch ($status) {
                case 'PROCESSING':
                    $message = "Halo *{$name}*,\n\nPesanan Anda dengan nomor ID *#{$orderId}* (Total: Rp {$total}) *sedang diproses* oleh Toserba Selamat.\n\nKami akan mengabari Anda setelah pesanan Anda siap. Terima kasih!";
                    break;
                case 'COMPLETED':
                    $deliveryText = ($order->delivery_method === 'PICKUP') 
                        ? 'telah siap untuk diambil di cabang.' 
                        : 'telah selesai diproses dan sedang dalam perjalanan/pengiriman ke alamat Anda.';
                    
                    $message = "Halo *{$name}*,\n\nKabar gembira! Pesanan Anda dengan nomor ID *#{$orderId}* {$deliveryText}\n\nTerima kasih telah berbelanja di Toserba Selamat!";
                    break;
                case 'CANCELLED':
                    $message = "Halo *{$name}*,\n\nInformasi: Pesanan Anda dengan nomor ID *#{$orderId}* *telah dibatalkan*.\n\nSilakan hubungi admin atau customer service kami jika Anda memerlukan informasi lebih lanjut. Terima kasih.";
                    
                    $this->restoreStockAndPoints($order, 'Pembatalan');
                    break;
            }

            if (!empty($message) && !empty($phone)) {
                WhatsappService::sendMessage($phone, $message);
            }
        }
    }

    /**
     * Handle the EcommerceOrder "deleting" event.
     */
    public function deleting(EcommerceOrder $order): void
    {
        // Jika order dihapus (bukan di-cancel sebelumnya), kembalikan stok dan poin
        if ($order->status !== 'CANCELLED') {
            $this->restoreStockAndPoints($order, 'Penghapusan');
        }
    }

    /**
     * Kembalikan stok dan poin untuk pesanan yang dibatalkan atau dihapus.
     */
    protected function restoreStockAndPoints(EcommerceOrder $order, string $actionName): void
    {
        $orderId = strtoupper(substr($order->id, 0, 8));

        // Kembalikan stok ke cabang & restore FIFO batches
        if ($order->branch_id) {
            foreach ($order->items as $item) {
                $stock = \App\Models\Stock::where('branch_id', $order->branch_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();
                if ($stock) {
                    // Set atribut pelacakan untuk StockObserver
                    $stock->log_type = 'RESTOCK';
                    $stock->reason_code = 'ECOMMERCE_CANCEL';
                    $stock->reference_doc_type = 'ECOMMERCE_ORDER';
                    $stock->reference_doc_id = $order->id;
                    $stock->notes = "{$actionName} E-Commerce: " . $order->customer_name;
                    
                    $stock->quantity_on_hand += $item->quantity;
                    $stock->save();
                }

                // Return stock to batches
                $deductions = \App\Models\StockBatchDeduction::where('ecommerce_order_item_id', $item->id)->get();
                foreach ($deductions as $deduction) {
                    $batch = \App\Models\StockBatch::find($deduction->stock_batch_id);
                    if ($batch) {
                        $batch->increment('remaining_quantity', $deduction->quantity);
                    }
                }
            }
        }

        // Kembalikan poin pelanggan jika terdaftar sebagai member
        $customer = \App\Models\Customer::where('phone', $order->customer_phone)->first();
        if ($customer) {
            $pointConversionRate = $customer->organization?->point_conversion_rate 
                ?? \App\Models\Organization::first()?->point_conversion_rate 
                ?? 1000;
            
            $earnedPoints = floor(($order->total_amount + ($order->points_redeemed_discount ?? 0)) / $pointConversionRate);
            if ($earnedPoints > 0) {
                $customer->deductPoints($earnedPoints, 'CANCEL', $order->id, "{$actionName} E-Commerce (Batal Poin Belanja): #{$orderId}");
            }

            if ($order->points_redeemed > 0) {
                $customer->addPoints($order->points_redeemed, 'CANCEL', $order->id, "{$actionName} E-Commerce (Kembali Poin Ditukar): #{$orderId}");
            }
        }
    }
}
