<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;

echo "Mencari duplikat SKU...\n";
$skuDuplicates = Product::select('sku', DB::raw('count(*) as total'))
    ->whereNotNull('sku')
    ->where('sku', '!=', '')
    ->groupBy('sku')
    ->having('total', '>', 1)
    ->get();

foreach ($skuDuplicates as $dup) {
    echo "SKU '{$dup->sku}' ditemukan sebanyak {$dup->total} kali.\n";
    $products = Product::where('sku', $dup->sku)->get(['id', 'name']);
    foreach ($products as $p) {
        echo "  - Product ID: {$p->id} | Name: {$p->name}\n";
    }
}

echo "\nMencari duplikat Barcode Utama...\n";
$barcodeDuplicates = Product::select('barcode', DB::raw('count(*) as total'))
    ->whereNotNull('barcode')
    ->where('barcode', '!=', '')
    ->groupBy('barcode')
    ->having('total', '>', 1)
    ->get();

foreach ($barcodeDuplicates as $dup) {
    echo "Barcode '{$dup->barcode}' ditemukan sebanyak {$dup->total} kali.\n";
    $products = Product::where('barcode', $dup->barcode)->get(['id', 'name']);
    foreach ($products as $p) {
        echo "  - Product ID: {$p->id} | Name: {$p->name}\n";
    }
}

echo "\nMencari duplikat di Additional Barcodes (Multi Barcode)...\n";
// Karena additional_barcodes disimpan di metadata->additional_barcodes (sebagai JSON array),
// Agak kompleks dengan raw query MySQL untuk cari duplikat lintas row, jadi kita proses di PHP.
$products = Product::whereNotNull('metadata')->get(['id', 'name', 'metadata']);

$barcodeMap = [];

foreach ($products as $p) {
    if (isset($p->metadata['additional_barcodes'])) {
        $barcodes = is_array($p->metadata['additional_barcodes']) 
            ? $p->metadata['additional_barcodes'] 
            : array_map('trim', explode(',', $p->metadata['additional_barcodes']));
        
        foreach ($barcodes as $code) {
            $code = trim($code);
            if (empty($code)) continue;
            
            $lowerCode = strtolower($code);
            if (!isset($barcodeMap[$lowerCode])) {
                $barcodeMap[$lowerCode] = [];
            }
            $barcodeMap[$lowerCode][] = ['id' => $p->id, 'name' => $p->name, 'field' => 'additional_barcode'];
        }
    }
}

// Cek juga yang tabrakan antar SKU, Barcode Utama, dan Additional Barcodes
$allProducts = Product::select('id', 'name', 'sku', 'barcode', 'metadata')->get();
$globalBarcodeMap = [];

foreach ($allProducts as $p) {
    $identifiers = [];
    if (!empty($p->sku)) $identifiers[] = ['value' => $p->sku, 'type' => 'SKU'];
    if (!empty($p->barcode)) $identifiers[] = ['value' => $p->barcode, 'type' => 'Barcode Utama'];
    
    if (isset($p->metadata['additional_barcodes'])) {
        $barcodes = is_array($p->metadata['additional_barcodes']) 
            ? $p->metadata['additional_barcodes'] 
            : array_map('trim', explode(',', $p->metadata['additional_barcodes']));
        foreach ($barcodes as $code) {
            $code = trim($code);
            if (!empty($code)) {
                $identifiers[] = ['value' => $code, 'type' => 'Multi Barcode'];
            }
        }
    }
    
    foreach ($identifiers as $idData) {
        $val = strtolower($idData['value']);
        if (!isset($globalBarcodeMap[$val])) {
            $globalBarcodeMap[$val] = [];
        }
        // Hindari menambah id yg sama dari row yg sama kecuali untuk menampilkan error "sku sama dgn barcode sendiri"
        $globalBarcodeMap[$val][] = [
            'id' => $p->id,
            'name' => $p->name,
            'type' => $idData['type'],
            'original' => $idData['value']
        ];
    }
}

echo "Mencari konflik global (SKU / Barcode Utama / Multi Barcode tabrakan)...\n";
$foundGlobalConflict = false;
foreach ($globalBarcodeMap as $val => $occurrences) {
    if (count($occurrences) > 1) {
        $foundGlobalConflict = true;
        $count = count($occurrences);
        echo "\nNilai '{$val}' digunakan {$count} kali:\n";
        foreach ($occurrences as $occ) {
            echo "  - Product ID: {$occ['id']} | Name: {$occ['name']} | Di kolom: {$occ['type']} ({$occ['original']})\n";
        }
    }
}

if (!$foundGlobalConflict) {
    echo "Tidak ada konflik global yang ditemukan.\n";
}

echo "\nSelesai.\n";
