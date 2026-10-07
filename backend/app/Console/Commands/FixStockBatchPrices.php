<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StockBatch;
use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceipt;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Organization;
use App\Models\Kontrabon;
use Illuminate\Support\Facades\DB;

class FixStockBatchPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:stock-batch-prices {--kontrabon= : Nomor Kontrabon spesifik yang ingin dihitung ulang total tagihannya}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix historical stock_batches cost_price to exclude tax for non-taxable products, and recalculate consignment kontrabon totals.';

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
            // 1. Sinkronisasi master produk dan stok cabang Non-PPN agar cost_price_tax = cost_price
            $nonTaxableProducts = Product::where('is_taxable', false)->get();
            foreach ($nonTaxableProducts as $p) {
                if (round($p->cost_price_tax, 2) != round($p->cost_price, 2)) {
                    $p->update(['cost_price_tax' => $p->cost_price]);
                }
            }
            Stock::whereHas('product', function ($q) {
                $q->where('is_taxable', false);
            })->get()->each(function ($st) {
                if (round($st->cost_price_tax, 2) != round($st->cost_price, 2)) {
                    $st->update(['cost_price_tax' => $st->cost_price]);
                }
            });

            // 2. Koreksi tabel stock_batches
            foreach ($batches as $batch) {
                $newCostPrice = null;
                $product = Product::find($batch->product_id);
                $isTaxable = (bool) ($product?->is_taxable ?? true);

                if ($batch->reference_doc_type === 'GOODS_RECEIPT') {
                    $grItem = GoodsReceiptItem::where('goods_receipt_id', $batch->reference_doc_id)
                                ->where('product_id', $batch->product_id)
                                ->first();

                    if ($grItem) {
                        $gr = GoodsReceipt::find($batch->reference_doc_id);
                        if ($gr) {
                            $qty = $grItem->quantity_received > 0 ? $grItem->quantity_received : 1;
                            $netPrice = (float)$grItem->subtotal / $qty;
                            
                            $newCostPrice = ($gr->include_tax && $isTaxable) ? round($netPrice * $taxMultiplier, 2) : round($netPrice, 2);
                        }
                    }
                }

                // Jika bukan dari Goods Receipt, atau item GR tidak ketemu, sync dengan tabel stocks/products
                if ($newCostPrice === null) {
                    $stock = Stock::where('branch_id', $batch->branch_id)
                                ->where('product_id', $batch->product_id)
                                ->first();

                    if (!$isTaxable) {
                        $newCostPrice = ($stock && $stock->cost_price > 0) ? $stock->cost_price : ($product ? $product->cost_price : $batch->cost_price);
                    } else {
                        if ($stock) {
                            $newCostPrice = $stock->cost_price_tax > 0 ? $stock->cost_price_tax : $stock->cost_price;
                        } else {
                            if ($product) {
                                $newCostPrice = $product->cost_price_tax > 0 ? $product->cost_price_tax : $product->cost_price;
                            }
                        }
                    }
                }

                if ($newCostPrice !== null && round($batch->cost_price, 2) != round($newCostPrice, 2)) {
                    $batch->cost_price = $newCostPrice;
                    $batch->save();
                    $updatedCount++;
                }
            }

            // 3. Hitung ulang total tagihan Kontrabon Konsinyasi (KBC)
            $specificKb = $this->option('kontrabon');
            $kontrabons = $specificKb 
                ? Kontrabon::where('kontrabon_number', $specificKb)->get()
                : Kontrabon::where('kontrabon_number', 'LIKE', 'KBC-%')->where('status', 'UNPAID')->get();

            foreach ($kontrabons as $kb) {
                $posItems = \App\Models\TransactionItem::where('kontrabon_id', $kb->id)->get();
                $ecomItems = \App\Models\EcommerceOrderItem::where('kontrabon_id', $kb->id)->get();
                $allProductIds = $posItems->pluck('product_id')->merge($ecomItems->pluck('product_id'))->unique();

                $totalAmount = 0;
                foreach ($allProductIds as $pId) {
                    $p = Product::find($pId);
                    $stock = Stock::where('product_id', $pId)->where('branch_id', $kb->branch_id)->first();
                    
                    // Prioritas fallback jika transaksi tidak memiliki potongan batch FIFO: HPP dari Stok Cabang
                    if ($stock && (float) $stock->cost_price > 0) {
                        $fallback = (float) ((!$p || !$p->is_taxable) 
                            ? $stock->cost_price 
                            : ($stock->cost_price_tax > 0 ? $stock->cost_price_tax : $stock->cost_price));
                    } else {
                        // Cadangan terakhir: Master Produk
                        $fallback = (float) ((!$p || !$p->is_taxable) 
                            ? ($p?->cost_price ?? 0) 
                            : ($p->cost_price_tax > 0 ? $p->cost_price_tax : ($p->cost_price ?? 0)));
                    }

                    $pItems = $posItems->where('product_id', $pId);
                    foreach ($pItems as $it) {
                        $batchCogs = (float) DB::table('stock_batch_deductions as sbd')
                            ->join('stock_batches as sb', 'sbd.stock_batch_id', '=', 'sb.id')
                            ->where('sbd.transaction_item_id', $it->id)
                            ->sum(DB::raw('sbd.quantity * sb.cost_price'));

                        if ($it->quantity > 0) {
                            $totalAmount += $batchCogs > 0 ? $batchCogs : ($it->quantity * $fallback);
                        } else {
                            $totalAmount -= $batchCogs > 0 ? $batchCogs : (abs($it->quantity) * $fallback);
                        }
                    }

                    $ecItems = $ecomItems->where('product_id', $pId);
                    foreach ($ecItems as $it) {
                        $batchCogs = (float) DB::table('stock_batch_deductions as sbd')
                            ->join('stock_batches as sb', 'sbd.stock_batch_id', '=', 'sb.id')
                            ->where('sbd.ecommerce_order_item_id', $it->id)
                            ->sum(DB::raw('sbd.quantity * sb.cost_price'));

                        if ($it->quantity > 0) {
                            $totalAmount += $batchCogs > 0 ? $batchCogs : ($it->quantity * $fallback);
                        } else {
                            $totalAmount -= $batchCogs > 0 ? $batchCogs : (abs($it->quantity) * $fallback);
                        }
                    }
                }

                if (round($kb->total_amount, 2) != round($totalAmount, 2)) {
                    $old = $kb->total_amount;
                    $kb->total_amount = max(0, $totalAmount);
                    $kb->save();
                    $this->info("Kontrabon {$kb->kontrabon_number} diperbarui: Rp " . number_format($old, 0) . " -> Rp " . number_format($kb->total_amount, 0));
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
