<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Organization;

try {
    $org = Organization::first();
    if (!$org) die("No org\n");

    $cat = Category::create([
        'organization_id' => $org->id,
        'name' => 'TEST_BABY',
        'is_active' => true,
    ]);

    echo "Category created: " . $cat->id . "\n";
    
    // Check if it exists in DB immediately
    $exists = DB::table('categories')->where('id', $cat->id)->exists();
    echo "Exists in DB: " . ($exists ? 'Yes' : 'No') . "\n";

} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}
