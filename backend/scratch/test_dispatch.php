<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/admin/password-reset/request', 'GET');
$response = $app->make('router')->dispatch($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Exception: " . ($response->exception ? $response->exception->getMessage() : 'None') . "\n";
