<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$device = \App\Models\PosDevice::first();
if ($device) {
    echo "Found Device UUID: {$device->device_uuid}\n";
} else {
    echo "No PosDevice found.\n";
}
