<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$po = \App\Models\PurchaseOrder::where('status', 'rejected')->latest()->first();
if ($po) {
    dump($po->approvals()->latest()->first()->toArray());
}

$so = \App\Models\StockAdjustment::where('status', 'rejected')->latest()->first();
if ($so) {
    dump($so->approvals()->latest()->first()->toArray());
}
