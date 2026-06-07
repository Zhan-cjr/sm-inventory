<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$inactiveLines = \App\Models\JournalEntryLine::whereHas('account', function($q) { 
    $q->where('is_active', false); 
})->whereHas('journalEntry', function($q) { 
    $q->where('status', 'posted'); 
})->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();

echo 'Inactive Account Debits: ' . ($inactiveLines->d ?? 0) . "\n";
echo 'Inactive Account Credits: ' . ($inactiveLines->c ?? 0) . "\n";
