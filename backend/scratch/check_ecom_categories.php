<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Organization;

$orgs = Organization::all();
foreach ($orgs as $org) {
    echo "Organization ID: {$org->id}, Name: {$org->name}\n";
    echo "Ecommerce Categories: " . json_encode($org->ecommerce_categories) . "\n";
}
