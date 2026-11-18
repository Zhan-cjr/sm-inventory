<?php

namespace App\Livewire;

use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class ImportProgressBar extends Component
{
    public static function idleCacheKey(int|string $userId): string
    {
        return "import_progress:idle:{$userId}";
    }

    public function render()
    {
        $activeImport = null;

        if (auth()->check()) {
            $userId = auth()->id();
            $cacheKey = static::idleCacheKey($userId);

            $isCachedIdle = false;
            try {
                $isCachedIdle = Cache::has($cacheKey);
            } catch (\Throwable) {
                $isCachedIdle = false;
            }

            if (! $isCachedIdle) {
                $activeImport = Import::where('user_id', $userId)
                    ->whereNull('completed_at')                       // belum selesai
                    ->where('total_rows', '>', 0)                     // sudah ada data baris
                    ->where('created_at', '>=', now()->subMinutes(30)) // max 30 menit
                    ->latest()
                    ->first();

                // Sembunyikan jika: sudah selesai tapi completed_at belum di-update
                if ($activeImport
                    && $activeImport->processed_rows >= $activeImport->total_rows) {
                    $activeImport = null;
                }

                // Sembunyikan jika: processed_rows masih 0 setelah 5 menit (job gagal/tidak jalan)
                if ($activeImport
                    && $activeImport->processed_rows === 0
                    && $activeImport->created_at->diffInMinutes(now()) > 5) {
                    // Tandai sebagai selesai agar tidak muncul lagi
                    $activeImport->update(['completed_at' => now()]);
                    $activeImport = null;
                }

                if (! $activeImport) {
                    try {
                        Cache::put($cacheKey, true, 60);
                    } catch (\Throwable) {
                        // Abaikan jika cache sementara tidak tersedia
                    }
                }
            }
        }

        return view('livewire.import-progress-bar', [
            'import' => $activeImport,
        ]);
    }
}


