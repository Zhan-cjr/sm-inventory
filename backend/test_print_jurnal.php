<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$req = \Illuminate\Http\Request::create('/api/print/jurnal_umum?start_date=2026-06-01&end_date=2026-06-30', 'GET');
$controller = app(\App\Http\Controllers\ReportPrintController::class);
try {
    $response = $controller->print('jurnal_umum', $req);
    if (method_exists($response, 'render')) {
        echo 'Length: ' . strlen($response->render());
    } else {
        echo get_class($response) . "\n";
    }
} catch (\Exception $e) {
    echo $e->getMessage();
}
