<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

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
foreach ($groupedItems as $trxId => $items) {
    if ($items->count() >= 2) {
        $multiItemTransactions++;
    }
}
echo "Transactions with >= 2 items: $multiItemTransactions\n";

$controller = new \App\Http\Controllers\Api\V1\BIDashboardController();
$req = \Illuminate\Http\Request::create('/api/v1/bi/apriori', 'GET');
$req->setUserResolver(function() { return \App\Models\User::first(); });
$res = $controller->apriori($req);
echo "Rules generated: \n";
echo json_encode($res->getData(), JSON_PRETTY_PRINT);
