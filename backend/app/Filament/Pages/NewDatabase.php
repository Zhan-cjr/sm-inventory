<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class NewDatabase extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $navigationLabel = 'NEW DATABASE';
    protected static ?string $title = 'NEW DATABASE';
    protected static string|\UnitEnum|null $navigationGroup = 'FILE';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.new-database';

    public string $db_name = '';

    public function create()
    {
        $this->db_name = trim($this->db_name);

        if (empty($this->db_name)) {
            Notification::make()
                ->title('Nama database tidak boleh kosong')
                ->danger()
                ->send();
            return;
        }

        // Validate name to prevent SQL injection and folder creation issues
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->db_name)) {
            Notification::make()
                ->title('Nama database hanya boleh mengandung huruf, angka, dan underscore')
                ->danger()
                ->send();
            return;
        }

        // Check if database already exists
        $exists = false;
        try {
            $databases = DB::select("SHOW DATABASES LIKE '{$this->db_name}'");
            if (count($databases) > 0) {
                $exists = true;
            }
        } catch (\Exception $e) {
            Log::error('Gagal mengecek database: ' . $e->getMessage());
        }

        if ($exists) {
            Notification::make()
                ->title("Database '{$this->db_name}' sudah ada")
                ->danger()
                ->send();
            return;
        }

        try {
            // 1. Create the database
            DB::statement("CREATE DATABASE `{$this->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // 2. Setup dynamic connection configuration
            $defaultConn = config('database.connections.mysql');
            $dynamicConfig = array_merge($defaultConn, ['database' => $this->db_name]);
            config(['database.connections.dynamic' => $dynamicConfig]);
            DB::purge('dynamic');

            // 3. Run migrations on the dynamic connection
            Artisan::call('migrate', [
                '--database' => 'dynamic',
                '--force' => true,
            ]);

            // 4. Run seeders on the dynamic connection
            Artisan::call('db:seed', [
                '--database' => 'dynamic',
                '--force' => true,
            ]);

            // 5. Copy roles and permissions from master DB
            $roles = DB::connection('mysql')->table('roles')->get()->map(fn($item) => (array)$item)->toArray();
            if (!empty($roles)) DB::connection('dynamic')->table('roles')->insertOrIgnore($roles);

            $permissions = DB::connection('mysql')->table('permissions')->get()->map(fn($item) => (array)$item)->toArray();
            if (!empty($permissions)) DB::connection('dynamic')->table('permissions')->insertOrIgnore($permissions);

            $roleHasPermissions = DB::connection('mysql')->table('role_has_permissions')->get()->map(fn($item) => (array)$item)->toArray();
            if (!empty($roleHasPermissions)) DB::connection('dynamic')->table('role_has_permissions')->insertOrIgnore($roleHasPermissions);

            // 6. Create default super admin user
            $adminUser = [
                'name' => 'Super Admin',
                'email' => 'admin@selamat.id',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            DB::connection('dynamic')->table('users')->updateOrInsert(
                ['email' => 'admin@selamat.id'],
                $adminUser
            );

            $user = DB::connection('dynamic')->table('users')->where('email', 'admin@selamat.id')->first();
            $superAdminRole = DB::connection('dynamic')->table('roles')->where('name', 'super_admin')->first();
            
            if ($user && $superAdminRole) {
                DB::connection('dynamic')->table('model_has_roles')->insertOrIgnore([
                    'role_id' => $superAdminRole->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $user->id,
                ]);
            }

            // 6. Switch default connection in session to the new database
            session(['active_database' => $this->db_name]);

            Notification::make()
                ->title("Database '{$this->db_name}' berhasil dibuat, dimigrasi, dan diaktifkan!")
                ->success()
                ->send();

            return redirect()->to('/admin');

        } catch (\Exception $e) {
            Log::error('Error saat membuat database baru: ' . $e->getMessage());
            Notification::make()
                ->title('Gagal membuat database baru: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
