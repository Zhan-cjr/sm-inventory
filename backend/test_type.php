<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$invalidTypes = \App\Models\Account::whereNotIn('type', ['asset', 'liability', 'equity', 'revenue', 'expense'])->get();
echo 'Count: ' . $invalidTypes->count() . "\n";
foreach($invalidTypes as $a) echo $a->account_code . ' - ' . $a->type . "\n";
