<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$panel = \Filament\Facades\Filament::getCurrentPanel();
echo $panel ? "Panel ID: " . $panel->getId() : "NULL";
