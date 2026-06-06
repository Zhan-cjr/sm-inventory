<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::first();
dump("User roles:");
dump($user->roles->pluck('name'));
dump("User permissions:");
dump($user->getAllPermissions()->pluck('name'));
dump("Can access_pos?");
dump($user->can('access_pos'));
