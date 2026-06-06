<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$branchId = '00000000-0000-0000-0000-000000000002';
$last7Days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = \Carbon\Carbon::today()->subDays($i);
    $last7Days[] = [
        'date' => $date->format('Y-m-d'),
        'day_name' => $date->format('D'),
        'sales' => 0,
    ];
}

$weeklySales = \App\Models\Transaction::where('branch_id', $branchId)
    ->whereDate('transaction_date', '>=', \Carbon\Carbon::today()->subDays(6))
    ->select(\Illuminate\Support\Facades\DB::raw('DATE(transaction_date) as date'), \Illuminate\Support\Facades\DB::raw('SUM(total_amount) as total'))
    ->groupBy('date')
    ->get()
    ->keyBy('date');

foreach ($last7Days as &$day) {
    if (isset($weeklySales[$day['date']])) {
        $day['sales'] = (int) $weeklySales[$day['date']]->total;
    }
}

echo json_encode($last7Days, JSON_PRETTY_PRINT);
