<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InventoryLog;

$count = InventoryLog::where('reason_code', 'ECOMMERCE_SALE')->count();
echo "Total E-Commerce Sale Logs: " . $count . "\n";

$logs = InventoryLog::where('reason_code', 'ECOMMERCE_SALE')->latest()->limit(5)->get();
foreach ($logs as $log) {
    echo "Log ID: {$log->id}, Product ID: {$log->product_id}, Before: {$log->quantity_before}, Change: {$log->quantity_change}, After: {$log->quantity_after}, Notes: {$log->notes}\n";
}
