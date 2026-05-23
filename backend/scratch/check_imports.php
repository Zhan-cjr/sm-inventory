<?php

use Filament\Actions\Imports\Models\Import;

$imports = Import::whereNull('completed_at')->get(['id','user_id','total_rows','processed_rows','created_at','file_name']);

echo "=== IMPORT RECORDS (completed_at IS NULL) ===\n";
echo "Total: " . $imports->count() . "\n\n";

foreach ($imports as $imp) {
    echo "ID: {$imp->id}\n";
    echo "  File       : " . basename($imp->file_name) . "\n";
    echo "  User ID    : {$imp->user_id}\n";
    echo "  Total Rows : {$imp->total_rows}\n";
    echo "  Processed  : {$imp->processed_rows}\n";
    echo "  Created    : {$imp->created_at}\n";
    echo "  Status     : " . ($imp->total_rows > 0 && $imp->processed_rows >= $imp->total_rows ? 'DONE (stuck)' : 'RUNNING/STUCK') . "\n";
    echo "---\n";
}

echo "\n=== ALL IMPORTS (last 5) ===\n";
$all = Import::latest()->limit(5)->get(['id','user_id','total_rows','processed_rows','created_at','completed_at','file_name']);
foreach ($all as $imp) {
    echo "ID: {$imp->id} | {$imp->processed_rows}/{$imp->total_rows} | completed: " . ($imp->completed_at ?? 'NULL') . "\n";
}
