<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\StockOpnameSession;
use Illuminate\Support\Facades\View;

$record = StockOpnameSession::first();
if (!$record) {
    echo "No StockOpnameSession record found.\n";
    exit(1);
}

$mockThis = new class($record) {
    public $record;
    public function __construct($record) {
        $this->record = $record;
    }
};

try {
    $html = View::make('filament.pages.view-stock-opname-session', [
        'this' => $mockThis
    ])->render();
    
    file_put_contents(__DIR__ . '/rendered.html', $html);
    echo "SUCCESS: View compiled successfully to scratch/rendered.html\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
