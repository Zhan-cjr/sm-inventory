<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Stock;
use App\Models\Product;

class AutoDiscontinueProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:auto-discontinue {--dry-run : Run in simulation mode without updating database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically set slow-moving, out-of-stock products to inactive based on rules.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Auto-Discontinue Check...");
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn("RUNNING IN DRY-RUN MODE (SIMULATION). Database will not be modified.");
        }

        // Configuration: 180 days threshold
        $thresholdDays = config('inventory.discontinue_threshold_days', 180);
        $thresholdDate = Carbon::now()->subDays($thresholdDays);

        // Find stocks that are currently active and have 0 quantity
        $stocksToDiscontinue = Stock::with('product', 'branch')
            ->where('is_active', true)
            ->where('quantity_on_hand', '<=', 0)
            ->get();

        $discontinuedCount = 0;
        $report = [];

        foreach ($stocksToDiscontinue as $stock) {
            // Check if there was any recent sale
            $recentSale = DB::table('transaction_items')
                ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->where('transaction_items.product_id', $stock->product_id)
                ->where('transactions.branch_id', $stock->branch_id)
                ->where('transactions.transaction_date', '>=', $thresholdDate)
                ->exists();

            if ($recentSale) {
                continue;
            }

            // Check if there was any recent restock/adjustment
            $recentLog = DB::table('inventory_logs')
                ->where('product_id', $stock->product_id)
                ->where('branch_id', $stock->branch_id)
                ->where('created_at', '>=', $thresholdDate)
                ->exists();

            if ($recentLog) {
                continue;
            }

            // If it's a very new product stock (created < 180 days ago), don't discontinue
            if ($stock->created_at >= $thresholdDate) {
                continue;
            }

            // If we reach here, the product stock is eligible for discontinuation
            $report[] = [
                'branch_code' => $stock->branch->code ?? 'Unknown',
                'sku' => $stock->product->sku ?? 'Unknown',
                'product_name' => $stock->product->name ?? 'Unknown',
            ];

            if (!$isDryRun) {
                $stock->is_active = false;
                $stock->save();
                $discontinuedCount++;
            }
        }

        // Global Discontinue Check
        $globalDiscontinuedCount = 0;
        if (!$isDryRun) {
            // Find products that are active but have NO active stocks in ANY branch, 
            // AND the product is older than the threshold (e.g., 6 months).
            // This prevents newly imported master products from being instantly disabled.
            $inactiveProducts = DB::table('products')
                ->where('is_active', true)
                ->where('created_at', '<', $thresholdDate)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('stocks')
                        ->whereColumn('stocks.product_id', 'products.id')
                        ->where('stocks.is_active', true);
                })
                ->get();

            foreach ($inactiveProducts as $prod) {
                DB::table('products')->where('id', $prod->id)->update(['is_active' => false]);
                $globalDiscontinuedCount++;
            }
        }

        // Print Report
        $this->info("Scan complete.");
        if (count($report) > 0) {
            $this->table(
                ['Branch Code', 'SKU', 'Product Name'],
                $report
            );
        }

        if ($isDryRun) {
            $this->info("[SIMULATION] Would have discontinued " . count($report) . " branch stocks.");
        } else {
            $this->info("Successfully discontinued {$discontinuedCount} branch stocks.");
            if ($globalDiscontinuedCount > 0) {
                $this->info("Successfully globally discontinued {$globalDiscontinuedCount} products.");
            }
        }
        
        // TODO: In the future, we can save this report to a dedicated table or send via email to Manager
    }
}
