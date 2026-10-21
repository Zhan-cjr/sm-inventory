<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'View:MigrasiHistoriPenjualan',
            'guard_name' => 'web',
        ]);

        $roles = Role::whereIn('name', ['super_admin', 'superadmin', 'super-admin', 'admin', 'manager'])->get();
        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::where('name', 'View:MigrasiHistoriPenjualan')->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
