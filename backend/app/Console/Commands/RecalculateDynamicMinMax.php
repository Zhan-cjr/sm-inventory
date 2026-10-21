<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Product;
use App\Services\DynamicMinMaxService;
use Illuminate\Console\Command;

class RecalculateDynamicMinMax extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:recalculate-min-max 
                            {--branch= : Branch ID or Branch Code} 
                            {--product= : Product ID or Barcode} 
                            {--months=12 : Number of historical months to evaluate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate dynamic Min-Max stock levels and safety stock using statistical moving average and standard deviation (Sheet 6 Forecast_PO)';

    /**
     * Execute the console command.
     */
    public function handle(DynamicMinMaxService $service): int
    {
        $branchOpt = $this->option('branch');
        $productOpt = $this->option('product');
        $months = (int)($this->option('months') ?: 12);

        $productId = null;
        if ($productOpt) {
            $product = Product::where('id', $productOpt)
                ->orWhere('barcode', $productOpt)
                ->orWhere('sku', $productOpt)
                ->first();
            if (!$product) {
                $this->error("Product not found for: {$productOpt}");
                return 1;
            }
            $productId = $product->id;
            $this->info("Target Product: {$product->name} ({$product->barcode})");
        }

        $branches = collect();
        if ($branchOpt) {
            $branch = Branch::where('id', $branchOpt)
                ->orWhere('code', $branchOpt)
                ->orWhere('name', 'like', "%{$branchOpt}%")
                ->first();
            if (!$branch) {
                $this->error("Branch not found for: {$branchOpt}");
                return 1;
            }
            $branches->push($branch);
        } else {
            $branches = Branch::all();
        }

        $this->info("Recalculating Dynamic Min-Max for " . $branches->count() . " branch(es)... [Window: {$months} months]");

        foreach ($branches as $branch) {
            $this->line("Processing branch: <comment>{$branch->name}</comment> ({$branch->id})");
            try {
                $result = $service->recalculateForBranch($branch->id, $productId, $months);
                $this->info("  -> Evaluated {$result['total_stocks_evaluated']} stocks, updated {$result['total_stocks_updated']} records.");
                $this->line("  -> Earliest live POS date: {$result['earliest_live_pos_date']}");

                if (!empty($result['sample_results'])) {
                    $tableRows = [];
                    foreach (array_slice($result['sample_results'], 0, 10) as $sample) {
                        $prod = Product::find($sample['product_id']);
                        $tableRows[] = [
                            $prod ? substr($prod->name, 0, 25) : $sample['product_id'],
                            $sample['avg_monthly_sales'],
                            $sample['ads'],
                            $sample['std_dev'],
                            $sample['safety_stock'],
                            "{$sample['old_min']} -> {$sample['new_min']}",
                            "{$sample['old_max']} -> {$sample['new_max']}",
                        ];
                    }
                    $this->table(
                        ['Product', 'Avg/Mo', 'ADS', 'SD', 'SS', 'Min (Old->New)', 'Max (Old->New)'],
                        $tableRows
                    );
                }
            } catch (\Throwable $e) {
                $this->error("  Error processing branch {$branch->name}: " . $e->getMessage());
            }
        }

        $this->info("Dynamic Min-Max recalculation completed!");
        return 0;
    }
}
