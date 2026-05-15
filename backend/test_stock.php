<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;

$b = Branch::first();
$p = Product::first();

if ($b && $p) {
    try {
        $s = Stock::create([
            'branch_id' => $b->id,
            'product_id' => $p->id,
            'quantity_on_hand' => 0,
            'min_qty' => 0,
            'max_qty' => 0,
            'cost_price' => 0,
            'selling_price' => 0,
        ]);
        echo "Stock created with ID: " . $s->id . "\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Branch or Product not found.\n";
}
