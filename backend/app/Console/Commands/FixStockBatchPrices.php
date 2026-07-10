<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StockBatch;
use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceipt;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class FixStockBatchPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:stock-batch-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix historical stock_batches cost_price to include discount and tax from Goods Receipts, or sync with stocks for other types';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai perbaikan harga HPP pada Stock Batches...');

        $taxRate = Organization::first()->tax_rate ?? 11;
        $taxMultiplier = 1 + ($taxRate / 100);

        $batches = StockBatch::all();
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($batches as $batch) {
                $newCostPrice = null;

                if ($batch->reference_doc_type === 'GOODS_RECEIPT') {
                    // Ambil detail penerimaan barang
                    $grItem = GoodsReceiptItem::where('goods_receipt_id', $batch->reference_doc_id)
                                ->where('product_id', $batch->product_id)
                                ->first();

                    if ($grItem) {
                        $gr = GoodsReceipt::find($batch->reference_doc_id);
                        if ($gr) {
                            $qty = $grItem->quantity_received > 0 ? $grItem->quantity_received : 1;
                            $netPrice = (float)$grItem->subtotal / $qty;
                            
                            $newCostPrice = $gr->include_tax ? round($netPrice * $taxMultiplier, 2) : round($netPrice, 2);
                        }
                    }
                }

                // Jika bukan dari Goods Receipt, atau item GR tidak ketemu, sync dengan tabel stocks
                if ($newCostPrice === null) {
                    $stock = Stock::where('branch_id', $batch->branch_id)
                                ->where('product_id', $batch->product_id)
                                ->first();

                    if ($stock) {
                        $newCostPrice = $stock->cost_price_tax > 0 ? $stock->cost_price_tax : $stock->cost_price;
                    } else {
                        // Fallback ke tabel products
                        $product = Product::find($batch->product_id);
                        if ($product) {
                            $newCostPrice = $product->cost_price_tax > 0 ? $product->cost_price_tax : $product->cost_price;
                        }
                    }
                }

                if ($newCostPrice !== null && round($batch->cost_price, 2) != round($newCostPrice, 2)) {
                    $batch->cost_price = $newCostPrice;
                    $batch->save();
                    $updatedCount++;
                }
            }

            DB::commit();
            $this->info("Selesai! Berhasil memperbarui {$updatedCount} data stock_batches.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
