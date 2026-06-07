<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/api/print/laporan_keuangan', 'GET', ['start_date' => '2026-06-01', 'end_date' => '2026-06-30']);
// mock auth
$user = \App\Models\User::first();
auth()->login($user);

$controller = app()->make(\App\Http\Controllers\ReportPrintController::class);
try {
    $response = $controller->print('laporan_keuangan', $request);
    echo "SUCCESS: " . strlen($response->render()) . " bytes\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " on line " . $e->getLine() . " of " . $e->getFile() . "\n";
}
