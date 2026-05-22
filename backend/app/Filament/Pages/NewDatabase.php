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

            // 5. Copy current logged-in user
            $user = auth()->user();
            if ($user) {
                $userData = (array) DB::connection('mysql')->table('users')->where('id', $user->id)->first();
                DB::connection('dynamic')->table('users')->insertOrIgnore($userData);

                $userRoles = DB::connection('mysql')->table('model_has_roles')->where('model_id', $user->id)->get();
                foreach ($userRoles as $role) {
                    $roleData = (array) DB::connection('mysql')->table('roles')->where('id', $role->role_id)->first();
                    if ($roleData) {
                        DB::connection('dynamic')->table('roles')->insertOrIgnore($roleData);
                    }
                    DB::connection('dynamic')->table('model_has_roles')->insertOrIgnore((array) $role);
                }
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
