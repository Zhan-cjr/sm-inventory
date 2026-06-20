<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$role = \Spatie\Permission\Models\Role::where('name', 'kasir')->first();
if ($role) {
    if (! $role->hasPermissionTo('akses_backend_admin')) {
        $role->givePermissionTo('akses_backend_admin');
    }
    if (! $role->hasPermissionTo('AksesBackendAdmin')) {
        $role->givePermissionTo('AksesBackendAdmin');
    }
    echo "Permissions assigned to kasir role.\n";
} else {
    echo "Kasir role not found.\n";
}
