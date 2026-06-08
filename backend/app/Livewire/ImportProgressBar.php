<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Actions\Imports\Models\Import;

class ImportProgressBar extends Component
{
    public function render()
    {
        $activeImport = null;

        if (auth()->check()) {
            $activeImport = Import::where('user_id', auth()->id())
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
        }

        return view('livewire.import-progress-bar', [
            'import' => $activeImport,
        ]);
    }
}


