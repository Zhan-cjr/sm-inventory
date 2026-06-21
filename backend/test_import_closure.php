<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$record = new \App\Models\Supplier();
$record->organization_id = \App\Models\Organization::first()->id;
$record->code = 'SUP-TEST-100';
$record->name = 'TEST SUPPLIER';
$record->is_active = true;

$state = false;
$closure = fn ($record, $state) => $record->is_consignment = $state ?? false;

// Simulate Filament's evaluate behavior for fillRecordUsing
$params = ['state' => $state, 'record' => $record];

$reflector = new \ReflectionFunction($closure);
$args = [];
foreach ($reflector->getParameters() as $param) {
    if (array_key_exists($param->name, $params)) {
        $args[] = $params[$param->name];
    } else {
        $args[] = null;
    }
}
$reflector->invokeArgs($args);

echo "Record is_consignment: ";
var_dump($record->is_consignment);

try {
    $record->save();
    echo "Saved successfully!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
