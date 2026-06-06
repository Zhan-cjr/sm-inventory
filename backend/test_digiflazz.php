<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ds = new App\Services\DigiflazzService();
$res = $ds->getPriceList();
echo json_encode($res, JSON_PRETTY_PRINT);
