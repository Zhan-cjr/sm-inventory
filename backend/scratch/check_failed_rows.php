<?php

use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Imports\Models\FailedImportRow;

// Cek import stok terbaru
echo "=== IMPORT STOK TERBARU ===\n";
$imports = Import::latest()->limit(5)->get();
foreach ($imports as $imp) {
    $failed = $imp->getFailedRowsCount();
    echo "ID: {$imp->id} | {$imp->processed_rows}/{$imp->total_rows} | failed: {$failed} | completed: " . ($imp->completed_at ?? 'NULL') . "\n";
}

echo "\n=== FAILED ROWS (import terakhir) ===\n";
$lastImport = Import::latest()->first();
if ($lastImport) {
    $failedRows = FailedImportRow::where('import_id', $lastImport->id)->limit(10)->get();
    echo "Import ID: {$lastImport->id}, Total failed: " . $lastImport->getFailedRowsCount() . "\n\n";
    foreach ($failedRows as $row) {
        echo "Row data: " . json_encode($row->data, JSON_UNESCAPED_UNICODE) . "\n";
        echo "Error    : " . $row->validation_error . "\n";
        echo "---\n";
    }
}
