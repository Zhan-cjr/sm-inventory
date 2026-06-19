<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$query = \App\Models\Transaction::where('transaction_type', 'SALE')
    ->where('is_voided', false)
    ->where('transaction_date', '>=', \Carbon\Carbon::now()->subDays(30));

$count = $query->count();
echo "Transactions count (last 30 days): $count\n";

$transactionIds = $query->pluck('id');

if ($transactionIds->isEmpty()) {
    echo "No transactions.\n";
    exit;
}

$itemsQuery = \App\Models\TransactionItem::with('product:id,name')
    ->whereIn('transaction_id', $transactionIds)
    ->select('transaction_id', 'product_id')
    ->get();

$groupedItems = $itemsQuery->groupBy('transaction_id');
$multiItemTransactions = 0;
$pairCounts = [];
$itemCounts = [];

foreach ($groupedItems as $trxId => $items) {
    if ($items->count() >= 2) {
        $multiItemTransactions++;
    }
    
    if ($items->count() < 2) continue;

    $productIds = $items->pluck('product_id')->unique()->values()->all();
    
    foreach ($productIds as $pid) {
        if (!isset($itemCounts[$pid])) $itemCounts[$pid] = 0;
        $itemCounts[$pid]++;
    }

    for ($i = 0; $i < count($productIds); $i++) {
        for ($j = $i + 1; $j < count($productIds); $j++) {
            $p1 = $productIds[$i];
            $p2 = $productIds[$j];
            
            if ($p1 > $p2) {
                $temp = $p1;
                $p1 = $p2;
                $p2 = $temp;
            }

            $key = "{$p1}|{$p2}";
            if (!isset($pairCounts[$key])) {
                $pairCounts[$key] = 0;
            }
            $pairCounts[$key]++;
        }
    }
}
echo "Transactions with >= 2 items: $multiItemTransactions\n";

arsort($pairCounts);
$topPairs = array_slice($pairCounts, 0, 4, true);

$allProductIds = collect(array_keys($itemCounts));
$productNames = \App\Models\Product::whereIn('id', $allProductIds)->pluck('name', 'id');

$rules = [];
foreach ($topPairs as $key => $count) {
    list($p1, $p2) = explode('|', $key);
    $confidence = round(($count / $itemCounts[$p1]) * 100);
    $rules[] = [
        'product_id_1' => $p1,
        'product_id_2' => $p2,
        'item1' => $productNames[$p1] ?? 'Produk ' . substr($p1, 0, 5),
        'item2' => $productNames[$p2] ?? 'Produk ' . substr($p2, 0, 5),
        'confidence' => $confidence . '%',
        'occurrences' => $count
    ];
}

echo "Rules generated: \n";
echo json_encode($rules, JSON_PRETTY_PRINT);
