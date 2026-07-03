<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseReturn;
use App\Models\GoodsReceipt;
use App\Models\Organization;
use App\Models\Stock;
use App\Models\Product;

class FixHistoricalPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-historical-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix historical Goods Receipt and Purchase Return prices to use net (after discount) prices.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting historical data fix...');

        $org = Organization::first();
        $taxRate = $org->tax_rate ?? 11;

        // 1. Fix Goods Receipts (only Product/Stock cost_price)
        $this->info('Fixing Goods Receipts...');
        $receipts = GoodsReceipt::with('items')->get();
        foreach ($receipts as $gr) {
            $taxMultiplier = $gr->include_tax ? (1 + ($taxRate / 100)) : 1;
            foreach ($gr->items as $item) {
                $netPrice = $item->quantity_received > 0 ? ($item->subtotal / $item->quantity_received) : $item->unit_price;
                $costPriceTax = $gr->include_tax ? round($netPrice * $taxMultiplier, 2) : $netPrice;
                
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->update([
                        'cost_price' => $netPrice,
                        'cost_price_tax' => $costPriceTax
                    ]);
                }
                
                if ($gr->branch_id) {
                    $stock = Stock::where('product_id', $item->product_id)->where('branch_id', $gr->branch_id)->first();
                    if ($stock) {
                        $stock->update([
                            'cost_price' => $netPrice,
                            'cost_price_tax' => $costPriceTax
                        ]);
                    }
                }
            }
        }

        // 2. Fix Purchase Returns
        $this->info('Fixing Purchase Returns...');
        $returns = PurchaseReturn::with('items', 'goodsReceipt.items')->get();
        foreach ($returns as $pr) {
            $gr = $pr->goodsReceipt;
            if (!$gr) continue;
            
            $taxMultiplier = $gr->include_tax ? (1 + ($taxRate / 100)) : 1;
            
            $newTotal = 0;
            foreach ($pr->items as $prItem) {
                $grItem = $gr->items->where('product_id', $prItem->product_id)->first();
                if ($grItem) {
                    $netUnitPrice = $grItem->quantity_received > 0 ? ($grItem->subtotal / $grItem->quantity_received) : $grItem->unit_price;
                    $returnPrice = round($netUnitPrice * $taxMultiplier, 2);
                    
                    $prItem->unit_price = $returnPrice;
                    $prItem->subtotal = round($returnPrice * $prItem->quantity, 2);
                    $prItem->save();
                    
                    $newTotal += $prItem->subtotal;
                }
            }
            
            if ($pr->total_amount != $newTotal) {
                $pr->total_amount = $newTotal;
                $pr->save();
                
                // Update deduction
                $deduction = \App\Models\SupplierDeduction::where('deduction_type', 'PURCHASE_RETURN')
                    ->where('reference_id', $pr->id)
                    ->first();
                if ($deduction) {
                    $deduction->amount = $newTotal;
                    $deduction->save();
                }
                
                // Re-record journal
                \App\Models\JournalEntry::where('journalable_id', $pr->id)
                    ->where('journalable_type', PurchaseReturn::class)
                    ->delete();
                $accountingService = new \App\Services\AccountingService();
                $accountingService->recordPurchaseReturnJournal($pr);
            }
        }

        $this->info('Historical data fix completed successfully!');
    }
}
