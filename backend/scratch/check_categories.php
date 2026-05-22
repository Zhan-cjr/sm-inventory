<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Category;

$cats = Category::all();
echo "Total Standard Categories: " . $cats->count() . "\n";
foreach ($cats as $cat) {
    echo "Category: {$cat->name} (ID: {$cat->id})\n";
}
