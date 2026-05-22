<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$users = User::all();
foreach ($users as $user) {
    echo "User ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Org ID: {$user->organization_id}, Branch ID: {$user->branch_id}\n";
}
