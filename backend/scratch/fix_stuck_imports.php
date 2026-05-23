<?php

use Filament\Actions\Imports\Models\Import;

// Tandai semua import stuck sebagai completed (processed=0, created > 30 menit lalu)
$stuck = Import::whereNull('completed_at')
    ->where('processed_rows', 0)
    ->where('created_at', '<', now()->subMinutes(5))
    ->get();

echo "Fixing " . $stuck->count() . " stuck import(s)...\n";

foreach ($stuck as $imp) {
    $imp->update(['completed_at' => now()]);
    echo "  Fixed ID: {$imp->id} - " . basename($imp->file_name) . "\n";
}

echo "Done!\n";
