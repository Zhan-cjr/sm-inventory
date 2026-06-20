<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$role = \Spatie\Permission\Models\Role::whereIn('name', ['super_admin', 'superadmin'])->first();
if ($role) {
    $role->givePermissionTo('akses_backend_admin');
    echo "Permission given to " . $role->name . "\n";
} else {
    echo "Super admin role not found\n";
}

$adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
if ($adminRole) {
    $adminRole->givePermissionTo('akses_backend_admin');
    echo "Permission given to " . $adminRole->name . "\n";
} else {
    echo "Admin role not found\n";
}
