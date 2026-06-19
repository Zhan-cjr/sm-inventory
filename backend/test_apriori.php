<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

$controller = new \App\Http\Controllers\Api\V1\BIDashboardController();
$req = \Illuminate\Http\Request::create('/api/v1/bi/apriori', 'GET');
$req->setUserResolver(function() { return \App\Models\User::first(); });
$res = $controller->apriori($req);
echo json_encode($res->getData(), JSON_PRETTY_PRINT);
