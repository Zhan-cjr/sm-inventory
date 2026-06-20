<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ref = new ReflectionMethod('\Filament\Auth\Pages\Login', 'authenticate');
echo $ref->getReturnType()->getName();
