<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$branch = \App\Models\Branch::where('name', 'like', '%pasirhayam%')->first();
if ($branch) {
    echo "Found Pasirhayam Branch ID: {$branch->id}\n";
    $oldBranchId = '019ed043-2865-7206-a9ab-770a338937f5'; // SELAMAT BLK
    
    // Update transactions
    $updatedCount = \App\Models\Transaction::where('branch_id', $oldBranchId)->update(['branch_id' => $branch->id]);
    echo "Updated {$updatedCount} transactions from SELAMAT BLK to Pasirhayam.\n";
    
    // Also update transaction_items if they have branch_id? (usually they don't, but let's check)
    // Actually, check if the seeder sets terminal_id as well. We might need to update terminal_id if terminals belong to branches.
    $terminals = \App\Models\Terminal::where('branch_id', $branch->id)->get();
    if ($terminals->isNotEmpty()) {
        $terminal = $terminals->first();
        $updatedTermCount = \App\Models\Transaction::where('branch_id', $branch->id)->update(['terminal_id' => $terminal->id]);
        echo "Updated terminal_id to {$terminal->id} for {$updatedTermCount} transactions.\n";
    }
} else {
    echo "Pasirhayam branch not found.\n";
}
