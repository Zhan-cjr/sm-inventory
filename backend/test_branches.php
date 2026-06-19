<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$counts = \App\Models\Transaction::where('transaction_type', 'SALE')
    ->where('is_voided', false)
    ->where('transaction_date', '>=', \Carbon\Carbon::now()->subDays(30))
    ->selectRaw('branch_id, count(*) as c')
    ->groupBy('branch_id')
    ->get();

echo "Transactions by branch:\n";
foreach($counts as $c) {
    echo "Branch {$c->branch_id}: {$c->c}\n";
}
