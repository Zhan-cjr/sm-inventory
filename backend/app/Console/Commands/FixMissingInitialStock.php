<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Stock;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\DB;

#[Signature('app:fix-missing-initial-stock')]
#[Description('Fix missing INITIAL_STOCK inventory logs for imported items')]
class FixMissingInitialStock extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to check for missing initial stock logs...');

        $stocks = Stock::all();
        $fixedCount = 0;

        foreach ($stocks as $stock) {
            // Get the first inventory log for this stock (chronological)
            $firstLog = InventoryLog::where('branch_id', $stock->branch_id)
                ->where('product_id', $stock->product_id)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc') // Fallback order
                ->first();

            $needsFix = false;
            $initialQty = 0;
            $createdAt = $stock->created_at;

            if (!$firstLog) {
                // No logs at all, but we have stock > 0
                if ($stock->quantity_on_hand > 0) {
                    $needsFix = true;
                    $initialQty = $stock->quantity_on_hand;
                }
            } else {
                // If there's a log, check if it's NOT an INITIAL_STOCK or ADJUSTMENT that represents the initial entry
                if (!in_array($firstLog->log_type, ['INITIAL_STOCK', 'INITIALIZATION', 'ADJUSTMENT']) || $firstLog->notes !== 'Updated via Stock Management') {
                    // It means the first log was a transaction (like SALE)
                    // The initial quantity before this transaction would be `quantity_before`
                    if ($firstLog->quantity_before > 0) {
                        $needsFix = true;
                        $initialQty = $firstLog->quantity_before;
                        $createdAt = $firstLog->created_at->subSecond();
                    }
                }
            }

            if ($needsFix && $initialQty > 0) {
                // Create the missing initial stock log
                InventoryLog::insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'branch_id' => $stock->branch_id,
                    'product_id' => $stock->product_id,
                    'log_type' => 'INITIAL_STOCK',
                    'quantity_before' => 0,
                    'quantity_change' => $initialQty,
                    'quantity_after' => $initialQty,
                    'reason_code' => 'INITIALIZATION',
                    'reference_doc_type' => null,
                    'reference_doc_id' => null,
                    'recorded_by' => '00000000-0000-0000-0000-000000000000', // System
                    'notes' => 'Auto-generated missing initial stock from import',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $fixedCount++;
                $this->line("Fixed missing log for Product ID: {$stock->product_id} at Branch ID: {$stock->branch_id} (Qty: {$initialQty})");
            }
        }

        $this->info("Finished! Fixed {$fixedCount} missing stock logs.");
    }
}
