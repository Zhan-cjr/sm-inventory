<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StockOpnameSession;

$sessions = StockOpnameSession::all();
foreach ($sessions as $session) {
    echo "Session ID: {$session->id}, Number: {$session->session_number}, Status: {$session->status}, Token: {$session->session_token}\n";
}
