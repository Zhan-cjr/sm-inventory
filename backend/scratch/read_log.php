<?php
// Baca laravel.log dan tampilkan error terbaru
$logPath = base_path('../storage/logs/laravel.log');
if (!file_exists($logPath)) {
    $logPath = storage_path('logs/laravel.log');
}

$content = file_get_contents($logPath);

// Ambil semua error log entries
preg_match_all('/\[2026-05-22 21:2[0-9].*?\] production\.(ERROR|INFO).*?(?=\[2026-05-22|\z)/s', $content, $matches);

echo "=== ERROR TERBARU ===\n";
$errors = array_filter($matches[0], fn($m) => str_contains($m, 'ERROR'));
$errors = array_slice(array_values($errors), -3); // 3 error terakhir

foreach ($errors as $err) {
    // Ambil hanya 800 karakter pertama per error
    echo substr($err, 0, 800) . "\n";
    echo "================\n";
}

if (empty($errors)) {
    echo "Tidak ada ERROR ditemukan. Coba cari 'Data sudah ada':\n";
    preg_match_all('/\[2026-05-22 21:2[0-9].*?\].*?Data sudah ada.+/m', $content, $m2);
    foreach (array_slice($m2[0], -5) as $line) {
        echo $line . "\n";
    }
}
