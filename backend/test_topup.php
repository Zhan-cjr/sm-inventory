<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$username = env('DIGIFLAZZ_USERNAME');
$apiKey = 'fake-key';
$sign = md5($username . $apiKey . 'pricelist');

$payload = [
    'cmd' => 'prepaid',
    'username' => $username,
    'sign' => $sign
];

$response = Illuminate\Support\Facades\Http::post('https://api.digiflazz.com/v1/price-list', $payload);
echo json_encode($response->json(), JSON_PRETTY_PRINT);
