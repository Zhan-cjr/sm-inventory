<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$promos = \App\Models\Promotion::with('branches')->get();
$now = now();

foreach ($promos as $promo) {
    echo "ID: {$promo->id}\n";
    echo "Name: {$promo->name}\n";
    echo "Is Active: {$promo->is_active}\n";
    echo "Valid From: {$promo->valid_from}\n";
    echo "Valid Until: {$promo->valid_until}\n";
    echo "Now: {$now}\n";
    echo "App To: {$promo->applicable_to}\n";
    echo "Target IDs: " . json_encode($promo->target_ids) . "\n";
    echo "Branches: " . json_encode($promo->branches->pluck('name')) . "\n";
    echo "--------------------------\n";
}
