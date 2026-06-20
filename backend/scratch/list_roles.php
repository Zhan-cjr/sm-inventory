<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(\App\Models\User::all() as $u) {
    echo $u->username . ' | Role DB: ' . $u->role . ' | Spatie Roles: ' . $u->roles->pluck('name')->implode(',') . "\n";
}
