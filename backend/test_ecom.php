<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/api/ecommerce/products', 'GET', [
    'branch_id' => '019e90ba-0654-7164-8848-0d1fc0d7159c' // I don't know the branch ID, let's omit it first to see if it shows up globally
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
