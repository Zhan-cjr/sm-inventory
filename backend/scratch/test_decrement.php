<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Stock;
use App\Models\InventoryLog;

$stock = Stock::first();
if ($stock) {
    $beforeLogsCount = InventoryLog::count();
    echo "Current Qty: " . $stock->quantity_on_hand . "\n";
    echo "Logs count before: " . $beforeLogsCount . "\n";
    
    // Simulate updating quantity via decrement
    $stock->log_type = 'SALE';
    $stock->reason_code = 'ECOMMERCE_SALE';
    $stock->reference_doc_type = 'ECOMMERCE_ORDER';
    $stock->reference_doc_id = 'test-order-id';
    $stock->notes = 'Checkout E-Commerce: John Doe';
    
    $stock->decrement('quantity_on_hand', 3);
    
    $afterLogsCount = InventoryLog::count();
    echo "New Qty: " . $stock->quantity_on_hand . "\n";
    echo "Logs count after: " . $afterLogsCount . "\n";
    
    if ($afterLogsCount > $beforeLogsCount) {
        echo "SUCCESS: StockObserver logged the decrement!\n";
        $latestLog = InventoryLog::latest()->first();
        echo "Log details: " . json_encode($latestLog, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "FAILURE: StockObserver did NOT log the decrement.\n";
    }
} else {
    echo "No stock found\n";
}
