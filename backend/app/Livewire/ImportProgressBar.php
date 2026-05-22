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
                ->whereNull('completed_at')
                ->latest()
                ->first();
        }

        return view('livewire.import-progress-bar', [
            'import' => $activeImport,
        ]);
    }
}
