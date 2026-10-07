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

        $permissions = [
            'ViewAny:ProductListing',
            'View:ProductListing',
            'Create:ProductListing',
            'Update:ProductListing',
            'Delete:ProductListing',
            'DeleteAny:ProductListing',
            'Restore:ProductListing',
            'RestoreAny:ProductListing',
            'ForceDelete:ProductListing',
            'ForceDeleteAny:ProductListing',
            'Replicate:ProductListing',
            'Reorder:ProductListing',
            'View:PerformaListing',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $superAdminRoles = Role::whereIn('name', ['super_admin', 'superadmin', 'super-admin'])->get();
        foreach ($superAdminRoles as $role) {
            $role->givePermissionTo($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'ViewAny:ProductListing',
            'View:ProductListing',
            'Create:ProductListing',
            'Update:ProductListing',
            'Delete:ProductListing',
            'DeleteAny:ProductListing',
            'Restore:ProductListing',
            'RestoreAny:ProductListing',
            'ForceDelete:ProductListing',
            'ForceDeleteAny:ProductListing',
            'Replicate:ProductListing',
            'Reorder:ProductListing',
            'View:PerformaListing',
        ];

        Permission::whereIn('name', $permissions)->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
