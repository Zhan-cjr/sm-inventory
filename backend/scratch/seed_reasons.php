<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AdjustmentReason;

$reasons = [
    ['name' => 'Koreksi Plus', 'type' => 'PLUS'],
    ['name' => 'Koreksi Minus', 'type' => 'MINUS'],
    ['name' => 'Retur', 'type' => 'MINUS'],
    ['name' => 'Barang Rusak', 'type' => 'MINUS'],
    ['name' => 'Kehilangan', 'type' => 'MINUS'],
];

foreach ($reasons as $reason) {
    AdjustmentReason::firstOrCreate(['name' => $reason['name']], $reason);
}

echo "Adjustment reasons seeded successfully.\n";
