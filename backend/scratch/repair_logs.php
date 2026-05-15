<?php

use App\Models\Stock;
use App\Models\InventoryLog;

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (Stock::all() as $stock) {
    $logs = InventoryLog::where('branch_id', $stock->branch_id)
        ->where('product_id', $stock->product_id)
        ->orderBy('created_at', 'desc')
        ->orderBy('id', 'desc')
        ->get();
    
    $current = $stock->quantity_on_hand;
    
    foreach ($logs as $log) {
        $log->quantity_after = $current;
        $log->quantity_before = $current - $log->quantity_change;
        $log->save();
        $current = $log->quantity_before;
    }
    echo "Repaired " . $logs->count() . " logs for stock " . $stock->id . "\n";
}
