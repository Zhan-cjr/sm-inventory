<?php

require __DIR__.'/vendor/autoload.php';

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Get a random active product
$product = \App\Models\Product::where('is_active', true)->first();
if (!$product) {
    die("Error: Tidak ada produk aktif di database untuk dites.\n");
}

// Get the super admin or any user
$user = \App\Models\User::first();
if (!$user) {
    die("Error: Tidak ada user di database.\n");
}

// Authenticate the user for this request
\Illuminate\Support\Facades\Auth::login($user);

// Get the user's branch or a random branch
$branchId = $user->branch_id ?? \App\Models\Branch::first()->id ?? null;
if (!$branchId) {
    die("Error: Tidak ada cabang (branch) di database.\n");
}

echo "=== MEMULAI SIMULASI SINKRONISASI OFFLINE ===\n";
echo "Kasir: {$user->name}\n";
echo "Produk yang dibeli: {$product->name} (Harga: Rp " . number_format($product->selling_price, 0, ',', '.') . ")\n\n";

$offlineTrxId = 'OFFLINE-' . strtoupper(uniqid());

// Construct the payload
$payload = [
    'deviceId' => 'SIMULATOR-001',
    'branchId' => $branchId,
    'transactions' => [
        [
            'localId' => $offlineTrxId,
            'type' => 'retail',
            'customerName' => 'Pembeli Walk-in',
            'cashierId' => $user->id,
            'paymentMethod' => 'cash',
            'subTotal' => $product->selling_price * 2,
            'taxAmount' => 0,
            'discountAmount' => 0,
            'totalAmount' => $product->selling_price * 2,
            'finalAmount' => $product->selling_price * 2,
            'receivedAmount' => $product->selling_price * 2,
            'changeAmount' => 0,
            'notes' => 'Testing Offline Sync Script',
            'createdAt' => now()->subHours(2)->toIso8601String(),
            'items' => [
                [
                    'productId' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'quantity' => 2,
                    'unitPrice' => $product->selling_price,
                    'discountAmount' => 0,
                    'subTotal' => $product->selling_price * 2,
                ]
            ],
            'payments' => []
        ]
    ]
];

echo "Mengirim Payload (Struk Kasir Tertunda):\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Mock the request
$syncRequest = \Illuminate\Http\Request::create('/api/v1/transactions/batch-sync', 'POST', $payload);
$syncRequest->setUserResolver(function () use ($user) {
    return $user;
});

// Call the controller directly
$controller = new \App\Http\Controllers\Api\V1\SyncController();

try {
    $response = $controller->batchSync($syncRequest);
    echo "=== HASIL DARI SERVER ===\n";
    echo "Status HTTP: " . $response->getStatusCode() . "\n";
    echo "Response JSON:\n";
    echo json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT) . "\n\n";

    echo "=== CEK DATABASE ===\n";
    $trx = \App\Models\Transaction::where('local_transaction_id', $offlineTrxId)->first();
    if ($trx) {
        echo "✅ BERHASIL! Transaksi masuk ke database dengan ID: {$trx->id}\n";
        echo "Receipt Number: {$trx->receipt_number}\n";
        echo "Total Item: " . $trx->items()->count() . "\n";
    } else {
        echo "❌ GAGAL! Transaksi tidak ditemukan di database.\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
