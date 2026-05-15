<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$payload = [
    'transactions' => [
        [
            'localId' => 'test-123',
            'totalAmount' => 1000,
            'paymentMethod' => 'CASH',
            'items' => [
                [
                    'productId' => 'dummy',
                    'quantity' => 1,
                    'unitPrice' => 1000,
                ]
            ]
        ]
    ],
    'deviceId' => '00000000-0000-0000-0000-000000000002',
    'branchId' => '00000000-0000-0000-0000-000000000002'
];
$request = Illuminate\Http\Request::create('/api/v1/transactions/batch-sync', 'POST', $payload);
$response = $kernel->handle($request);
echo $response->getContent();
