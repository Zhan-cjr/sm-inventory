<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$branch = \App\Models\Branch::find('019ed043-2865-7206-a9ab-770a338937f5');
if ($branch) {
    echo "Branch ID: {$branch->id}\nName: {$branch->name}\n";
} else {
    echo "Branch not found.\n";
}
