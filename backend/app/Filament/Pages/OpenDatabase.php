<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class OpenDatabase extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationLabel = 'OPEN DATABASE';
    protected static ?string $title = 'OPEN DATABASE';
    protected static string|\UnitEnum|null $navigationGroup = 'FILE';
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.open-database';

    public string $selected_db = '';

    public function mount(): void
    {
        $this->selected_db = session('active_database') ?: config('database.connections.mysql.database');
    }

    public function getAvailableDatabases(): array
    {
        try {
            $databases = DB::select('SHOW DATABASES');
            return collect($databases)
                ->map(fn ($db) => ((array) $db)['Database'] ?? ((array) $db)['database'] ?? null)
                ->filter(fn ($name) => $name && !in_array($name, [
                    'information_schema',
                    'mysql',
                    'performance_schema',
                    'sys'
                ]))
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Gagal mengambil daftar database: ' . $e->getMessage());
            return [];
        }
    }

    public function open()
    {
        $available = $this->getAvailableDatabases();

        if (!in_array($this->selected_db, $available)) {
            Notification::make()
                ->title('Database yang dipilih tidak valid atau tidak ditemukan')
                ->danger()
                ->send();
            return;
        }

        // UX/Security Check: Pastikan user saat ini ada di database target
        $currentUser = auth()->user();
        if ($currentUser) {
            $defaultConn = config('database.connections.mysql');
            $targetConfig = array_merge($defaultConn, ['database' => $this->selected_db]);
            config(['database.connections.temp_target' => $targetConfig]);
            DB::purge('temp_target');

            try {
                $userExists = DB::connection('temp_target')
                    ->table('users')
                    ->where('email', $currentUser->email)
                    ->exists();

                if (!$userExists) {
                    Notification::make()
                        ->title("Gagal beralih: Email Anda ({$currentUser->email}) tidak terdaftar di database '{$this->selected_db}'")
                        ->danger()
                        ->send();
                    return;
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title("Gagal beralih: Database '{$this->selected_db}' tidak valid atau belum di-migrate")
                    ->danger()
                    ->send();
                return;
            }
        }

        // Set ke session
        $defaultDb = env('DB_DATABASE', 'hypermarket_pos');
        if ($this->selected_db === $defaultDb) {
            session()->forget('active_database');
        } else {
            session(['active_database' => $this->selected_db]);
        }

        Notification::make()
            ->title("Berhasil beralih ke database '{$this->selected_db}'")
            ->success()
            ->send();

        return redirect()->to('/admin');
    }
}
