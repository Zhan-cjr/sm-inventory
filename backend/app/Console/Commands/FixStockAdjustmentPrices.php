<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StockAdjustment;
use App\Models\StockOpnameSession;
use App\Models\Stock;
use App\Models\Product;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class FixStockAdjustmentPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:stock-adjustment-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memperbaiki HPP pada item Koreksi Stok lama dan meregenerasi jurnal akuntansinya.';

    /**
     * Execute the console command.
     */
    public function handle(AccountingService $accounting)
    {
        $this->info('Memulai perbaikan HPP Koreksi Stok & Stok Opname...');
        
        $countAdj = 0;
        $countOpname = 0;

        DB::beginTransaction();

        try {
            // ==========================================
            // 1. Perbaiki Stock Adjustment (Koreksi Stok)
            // ==========================================
            $this->info('Memproses Stock Adjustment...');
            $adjustments = StockAdjustment::with('items')->get();
            $bar = $this->output->createProgressBar(count($adjustments));

            foreach ($adjustments as $adj) {
                $totalValue = 0;
                foreach ($adj->items as $item) {
                    $product = Product::find($item->product_id);
                    if (!$product) continue;
                    
                    $stock = Stock::where('product_id', $product->id)
                        ->where('branch_id', $adj->branch_id)
                        ->first();
                        
                    // Logika HPP Cost+Tax dari tabel Stock > Product > Cost Price
                    $unitCost = ($stock && $stock->cost_price_tax > 0) 
                        ? $stock->cost_price_tax 
                        : ($product->cost_price_tax > 0 ? $product->cost_price_tax : ($product->cost_price ?? 0));
                    
                    $diff = abs($item->new_quantity - $item->previous_quantity);
                    $totalCost = $diff * $unitCost;
                    
                    // Update item dengan HPP yang benar
                    $item->update([
                        'unit_cost' => $unitCost,
                        'total_cost' => $totalCost
                    ]);
                    
                    $totalValue += $totalCost;
                }
                
                // Update total nominal di tabel transaksi
                $adj->update(['total_value' => $totalValue]);
                
                // Hapus jurnal lama & Buat ulang dengan nilai HPP yang benar
                if (in_array(strtoupper($adj->status), ['COMPLETED', 'APPROVED'])) {
                    $accounting->recordStockAdjustmentJournal($adj);
                    $countAdj++;
                }
                
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();

            // ==========================================
            // 2. Perbaiki Jurnal Stock Opname Session
            // ==========================================
            $this->info('Memproses Stock Opname Session...');
            $opnames = StockOpnameSession::where('status', 'COMPLETED')->get();
            $bar2 = $this->output->createProgressBar(count($opnames));

            foreach ($opnames as $opname) {
                $accounting->recordStockOpnameJournal($opname);
                $countOpname++;
                $bar2->advance();
            }
            $bar2->finish();
            $this->newLine();
            
            DB::commit();
            $this->info("BERHASIL! {$countAdj} Koreksi Stok dan {$countOpname} Stok Opname telah diperbaiki HPP dan Jurnalnya.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
