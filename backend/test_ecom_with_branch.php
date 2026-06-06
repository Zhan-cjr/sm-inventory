<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$branch = \App\Models\Branch::where('name', 'SUKABUMI')->first();
$branchId = $branch ? $branch->id : null;
echo "Testing with Branch ID: " . $branchId . "\n";

$request = \Illuminate\Http\Request::create('/api/ecommerce/products', 'GET', [
    'branch_id' => $branchId
]);

$controller = new \App\Http\Controllers\Api\V1\EcommerceController();
$response = $controller->getProducts($request);
$data = json_decode($response->getContent(), true);

$promoProducts = array_filter($data, function($p) {
    return $p['is_promo'] ?? false;
});

echo "Total Products: " . count($data) . "\n";
echo "Promo Products: " . count($promoProducts) . "\n";
if (count($promoProducts) > 0) {
    foreach ($promoProducts as $p) {
        echo " - " . $p['name'] . " (Promo: " . ($p['applied_promo']['name'] ?? '') . ")\n";
    }
} else {
    echo "NO PROMO PRODUCTS FOUND!\n";
}
