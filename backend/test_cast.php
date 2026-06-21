<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$booleanCaster = function (?string $state): bool {
    if (blank($state)) return false;
    $val = strtolower(trim($state));
    if (in_array($val, ['1', 'true', 'yes', 'y', 'ya', 'aktif', 'on'])) return true;
    if (in_array($val, ['0', 'false', 'no', 'n', 'tidak', 'nonaktif', 'off', 'non aktif'])) return false;
    return (bool) $val;
};

$column = \Filament\Actions\Imports\ImportColumn::make('is_consignment')
    ->boolean()
    ->castStateUsing($booleanCaster);

var_dump($column->castState("0"));
var_dump($column->castState("1"));
var_dump($column->castState(""));
