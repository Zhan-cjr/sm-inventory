<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
echo "User role: {$user->role}, User branch: {$user->branch_id}\n";

$request = \Illuminate\Http\Request::create('/api/v1/bi/apriori', 'GET', ['branch_id' => $user->branch_id]);
$request->setUserResolver(function() use ($user) { return $user; });

$controller = new \App\Http\Controllers\Api\V1\BIDashboardController();
$response = $controller->apriori($request);

echo "API Response:\n";
echo json_encode($response->getData(), JSON_PRETTY_PRINT);
