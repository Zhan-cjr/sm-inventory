<?php

/**
 * Script Update Harga SKU PPOB Provider AMA
 * Mengupdate HPP (cost_price, cost_price_tax) dan Harga Jual (selling_price, harga_jual_1, margin_gol_1)
 * Nama produk dipertahankan sesuai data yang sudah ada di database.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

$isDryRun = in_array('--dry-run', $argv);
$isExecute = in_array('--execute', $argv);

if (!$isDryRun && !$isExecute) {
    echo "==========================================================" . PHP_EOL;
    echo "  Script Update Harga PPOB AMA" . PHP_EOL;
    echo "  Gunakan salah satu argumen:" . PHP_EOL;
    echo "    php update_ppob_ama_prices.php --dry-run" . PHP_EOL;
    echo "    php update_ppob_ama_prices.php --execute" . PHP_EOL;
    echo "==========================================================" . PHP_EOL;
    exit(0);
}

$excelFile = __DIR__ . '/UPDATE_SELAMAT_20261022.xlsx';
if (!file_exists($excelFile)) {
    // Coba alternatif path
    $altPath = dirname(__DIR__) . '/DATA IMPORT /UPDATE_SELAMAT_20261022.xlsx';
    if (file_exists($altPath)) {
        $excelFile = $altPath;
    } else {
        echo "ERROR: File Excel {$excelFile} tidak ditemukan!" . PHP_EOL;
        exit(1);
    }
}

echo "=== MEMPROSES UPDATE HARGA PPOB AMA ===" . PHP_EOL;
echo "File Excel: {$excelFile}" . PHP_EOL;
echo "Mode      : " . ($isDryRun ? "DRY-RUN (Simulasi saja, tidak mengubah data)" : "EXECUTE (Menerapkan ke Database)") . PHP_EOL;
echo "----------------------------------------------------------" . PHP_EOL;

$spreadsheet = IOFactory::load($excelFile);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestRow();

$excelItems = [];
for ($r = 2; $r <= $highestRow; $r++) {
    $sku = trim((string)$sheet->getCell('A' . $r)->getValue());
    if (empty($sku) || $sku === 'Noted :') {
        continue;
    }

    $hppVal = $sheet->getCell('D' . $r)->getValue();
    $jualVal = $sheet->getCell('E' . $r)->getValue();

    if ($hppVal === null || $jualVal === null || !is_numeric($hppVal) || !is_numeric($jualVal)) {
        continue;
    }

    $excelItems[$sku] = [
        'row' => $r,
        'sku' => $sku,
        'hpp' => (float)$hppVal,
        'jual' => (float)$jualVal,
    ];
}

echo "Total baris valid di Excel: " . count($excelItems) . " SKU." . PHP_EOL;

$dbProducts = Product::where('ppob_provider', 'ama')->get()->keyBy('ppob_sku');
echo "Total produk AMA di Database: " . $dbProducts->count() . " SKU." . PHP_EOL;

$matched = 0;
$notFound = [];
$changes = [];

foreach ($excelItems as $sku => $item) {
    if (!$dbProducts->has($sku)) {
        $notFound[] = $sku;
        continue;
    }

    $matched++;
    $product = $dbProducts->get($sku);
    $oldHpp = (float)$product->cost_price;
    $oldJual = (float)$product->selling_price;
    $oldMargin = (float)$product->margin_gol_1;

    $newHpp = $item['hpp'];
    $newJual = $item['jual'];
    $newMargin = ($newJual > 0 && $newHpp > 0)
        ? round((($newJual - $newHpp) / $newJual) * 100, 2)
        : 0;

    $hppChanged = abs($oldHpp - $newHpp) > 0.01;
    $jualChanged = abs($oldJual - $newJual) > 0.01;

    $changes[] = [
        'product' => $product,
        'sku' => $sku,
        'name' => $product->name, // Tetap gunakan nama dari DB
        'old_hpp' => $oldHpp,
        'new_hpp' => $newHpp,
        'old_jual' => $oldJual,
        'new_jual' => $newJual,
        'old_margin' => $oldMargin,
        'new_margin' => $newMargin,
        'hpp_changed' => $hppChanged,
        'jual_changed' => $jualChanged,
        'has_change' => ($hppChanged || $jualChanged),
    ];
}

$totalWithChanges = count(array_filter($changes, fn($c) => $c['has_change']));
$totalHppChanges = count(array_filter($changes, fn($c) => $c['hpp_changed']));
$totalJualChanges = count(array_filter($changes, fn($c) => $c['jual_changed']));

echo "SKU cocok dengan DB         : {$matched}" . PHP_EOL;
echo "SKU tidak ditemukan di DB   : " . count($notFound) . PHP_EOL;
echo "SKU yang mengalami perubahan: {$totalWithChanges}" . PHP_EOL;
echo "  - HPP berubah             : {$totalHppChanges}" . PHP_EOL;
echo "  - Harga Jual berubah      : {$totalJualChanges}" . PHP_EOL;
echo "----------------------------------------------------------" . PHP_EOL;

if ($isExecute) {
    DB::beginTransaction();
    try {
        $updatedCount = 0;
        foreach ($changes as $item) {
            $p = $item['product'];
            $p->update([
                'cost_price' => $item['new_hpp'],
                'cost_price_tax' => $item['new_hpp'],
                'selling_price' => $item['new_jual'],
                'harga_jual_1' => $item['new_jual'],
                'margin_gol_1' => $item['new_margin'],
            ]);
            $updatedCount++;
        }

        DB::commit();
        echo "BERHASIL: {$updatedCount} produk berhasil diupdate di database!" . PHP_EOL;

        // Bersihkan cache POS & E-commerce
        $branches = Branch::where('is_active', true)->get();
        foreach ($branches as $b) {
            Cache::forget('pos_products_json_gz_branch_' . $b->id);
            Cache::forget('ecommerce_products_' . $b->id);
        }
        Cache::forget('ecommerce_products_all');
        echo "BERHASIL: Seluruh cache POS dan katalog cabang telah dibersihkan." . PHP_EOL;

    } catch (\Throwable $e) {
        DB::rollBack();
        echo "ERROR: Terjadi kesalahan saat update data. Rollback berhasil dilakukan." . PHP_EOL;
        echo "Pesan: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
} else {
    echo "Simulasi selesai. Data tidak diubah. Jalankan dengan flag --execute untuk menerapkan." . PHP_EOL;
}
