<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = env('TELEGRAM_BOT_TOKEN');
if ($token) {
    $response = Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
        'url' => 'https://admin.toserbaselamat.id/api/telegram/webhook'
    ]);
    echo "Response: " . $response->body() . "\n";
} else {
    echo "NO TOKEN FOUND\n";
}
