<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\TransactionItem;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PromoParserService
{
    /**
     * Dapatkan daftar sheet dari file Excel.
     */
    public function getSheetNames(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        return $reader->listWorksheetNames($filePath);
    }

    /**
     * Deteksi kolom header otomatis dari baris-baris awal sheet.
     */
    public function detectHeaders(string $filePath, ?string $sheetName = null): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        if ($sheetName) {
            $reader->setLoadSheetsOnly([$sheetName]);
        }

        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $highestRow = min(15, $worksheet->getHighestRow());
        $highestCol = $worksheet->getHighestColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestCol);

        $detectedCols = [
            'barcode_col' => null,
            'promo_col' => null,
            'name_col' => null,
            'header_row' => 1,
            'columns' => [],
        ];

        // Cari baris header yang mengandung kata kunci
        for ($row = 1; $row <= $highestRow; $row++) {
            $colsInRow = [];
            $rowBarcodeCol = null;
            $rowPromoCol = null;
            $rowNameCol = null;

            for ($col = 1; $col <= min(20, $highestColIndex); $col++) {
                $val = trim((string)$worksheet->getCell([$col, $row])->getValue());
                if (!empty($val)) {
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $colsInRow[$colLetter] = $val;

                    $lowerVal = strtolower($val);
                    if (preg_match('/(barcode|ean|plu|kode|mid)/i', $lowerVal)) {
                        if (!$rowBarcodeCol) $rowBarcodeCol = $colLetter;
                    }
                    if (preg_match('/(mekanisme|promo|disc|diskon|potongan|pot|on faktur)/i', $lowerVal)) {
                        if (!$rowPromoCol) $rowPromoCol = $colLetter;
                    }
                    if (preg_match('/(nama|sku|item|deskripsi|description)/i', $lowerVal)) {
                        if (!$rowNameCol) $rowNameCol = $colLetter;
                    }
                }
            }

            // Header tabel valid harus memiliki minimal 2 kolom dan memiliki kata kunci barcode atau promo/mekanisme
            if (count($colsInRow) >= 2 && ($rowBarcodeCol || ($rowPromoCol && count($colsInRow) >= 3))) {
                $detectedCols['header_row'] = $row;
                $detectedCols['columns'] = $colsInRow;
                $detectedCols['barcode_col'] = $rowBarcodeCol;
                $detectedCols['promo_col'] = $rowPromoCol;
                $detectedCols['name_col'] = $rowNameCol;
                break;
            }
        }

        // Jika belum ketemu, ambil kolom dari baris 1 atau 3
        if (empty($detectedCols['columns'])) {
            for ($col = 1; $col <= min(15, $highestColIndex); $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $val = trim((string)$worksheet->getCell([$col, 1])->getValue());
                $detectedCols['columns'][$colLetter] = !empty($val) ? $val : "Kolom {$colLetter}";
            }
            $detectedCols['barcode_col'] = 'A';
            $detectedCols['promo_col'] = 'D';
            $detectedCols['name_col'] = 'C';
        }

        // Default fallback jika salah satu kolom belum tertebak
        if (!$detectedCols['barcode_col']) $detectedCols['barcode_col'] = array_key_first($detectedCols['columns']) ?? 'A';
        if (!$detectedCols['promo_col']) {
            $keys = array_keys($detectedCols['columns']);
            $detectedCols['promo_col'] = $keys[min(3, count($keys) - 1)] ?? 'D';
        }

        return $detectedCols;
    }

    /**
     * Parse isi Excel sesuai kolom pemetaan.
     */
    public function parseExcel(
        string $filePath,
        ?string $sheetName = null,
        string $barcodeCol = 'A',
        string $promoCol = 'D',
        ?string $nameCol = 'C',
        int $startRow = 2
    ): array {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        if ($sheetName) {
            $reader->setLoadSheetsOnly([$sheetName]);
        }

        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $highestRow = $worksheet->getHighestRow();
        $results = [];

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $rawBarcode = trim((string)$worksheet->getCell("{$barcodeCol}{$row}")->getValue());
            $rawPromo = trim((string)$worksheet->getCell("{$promoCol}{$row}")->getValue());
            $rawName = $nameCol ? trim((string)$worksheet->getCell("{$nameCol}{$row}")->getValue()) : '';

            // Bersihkan format saintifik atau desimal pada barcode (misal 8.99887E+12)
            if (is_numeric($rawBarcode)) {
                $rawBarcode = number_format((float)$rawBarcode, 0, '', '');
            }

            if (empty($rawBarcode) || empty($rawPromo)) {
                continue;
            }

            $parsedMekanisme = $this->extractPromoDetails($rawPromo);

            $results[] = [
                'barcode' => $rawBarcode,
                'name_from_file' => $rawName,
                'promo_raw' => $rawPromo,
                'disc_percent' => $parsedMekanisme['disc_percent'],
                'promo_note' => $parsedMekanisme['promo_note'],
                'is_nominal_or_box' => $parsedMekanisme['is_nominal_or_box'],
            ];
        }

        return $results;
    }

    /**
     * Parse chat WhatsApp berformat teks.
     */
    public function parseWhatsAppText(string $rawText): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $rawText);
        $results = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Bersihkan karakter formatting WA seperti *, _, ~
            $cleanLine = str_replace(['*', '_', '~'], '', $line);

            // 1. Ekstrak Barcode (utamakan 13-14 digit, atau 8 digit)
            $barcode = null;
            if (preg_match('/\b(\d{8,14})\b/', $cleanLine, $barcodeMatch)) {
                $barcode = $barcodeMatch[1];
            } elseif (preg_match('/\b(\d{5,7})\b/', $cleanLine, $barcodeMatch)) {
                // Secondary MID / PLU 5-7 digit
                $barcode = $barcodeMatch[1];
            }

            if (!$barcode) {
                // Baris tanpa barcode/kode (misal kalimat salam atau deskripsi header) -> abaikan
                continue;
            }

            // 2. Ekstrak Mekanisme Promo
            $promoRaw = '';
            if (preg_match('/(disc\.?[^\n\r]*|pot\.?[^\n\r]*|\d+[\.,]?\d*\s*%[^\n\r]*|potongan[^\n\r]*)/i', $cleanLine, $promoMatch)) {
                $promoRaw = trim($promoMatch[1]);
            }

            if (empty($promoRaw)) {
                continue;
            }

            // 3. Nama produk dari teks (teks antara barcode dan mekanisme promo)
            $namePart = $cleanLine;
            $namePart = str_replace($barcode, '', $namePart);
            $namePart = str_replace($promoRaw, '', $namePart);
            $namePart = trim(preg_replace('/^[\s\-\:\.\t]+|[\s\-\:\.\t]+$/', '', $namePart));

            $parsedMekanisme = $this->extractPromoDetails($promoRaw);

            $results[] = [
                'barcode' => $barcode,
                'name_from_file' => $namePart,
                'promo_raw' => $promoRaw,
                'disc_percent' => $parsedMekanisme['disc_percent'],
                'promo_note' => $parsedMekanisme['promo_note'],
                'is_nominal_or_box' => $parsedMekanisme['is_nominal_or_box'],
            ];
        }

        return $results;
    }

    /**
     * Analisis teks promo: apakah persentase atau potongan nominal / box.
     */
    public function extractPromoDetails(string $promoRaw): array
    {
        $clean = trim($promoRaw);
        $discPercent = 0.0;
        $isNominalOrBox = false;

        // Cek jika terdapat kata box / karton / pot / rupiah
        if (preg_match('/(box|karton|ctn|pot\.?|potongan|rp)/i', $clean)) {
            $isNominalOrBox = true;
        }

        // Cari nilai persentase murni (misal: "Disc. 10%", "Disc. 7,3%", "15%")
        if (preg_match('/(\d+[\.,]?\d*)\s*%/i', $clean, $pctMatch)) {
            $numStr = str_replace(',', '.', $pctMatch[1]);
            $discPercent = (float)$numStr;
        }

        return [
            'disc_percent' => $discPercent,
            'promo_note' => $clean,
            'is_nominal_or_box' => $isNominalOrBox,
        ];
    }

    /**
     * Hubungkan data promo dengan database produk, stok, saran order, dan keranjang PO saat ini.
     */
    public function matchWithInventory(array $parsedItems, ?string $branchId, array $currentCart = []): array
    {
        $barcodes = array_filter(array_column($parsedItems, 'barcode'));
        if (empty($barcodes)) {
            return [];
        }

        // Ambil produk yang cocok dengan barcode, sku, atau additional_barcodes
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($barcodes) {
                $q->whereIn('barcode', $barcodes)
                  ->orWhereIn('sku', $barcodes);
                foreach ($barcodes as $bc) {
                    $q->orWhereJsonContains('metadata->additional_barcodes', $bc);
                }
            })
            ->with(['stocks' => function ($sq) use ($branchId) {
                if ($branchId) {
                    $sq->where('branch_id', $branchId);
                }
            }])
            ->get();

        // Eager-query penjualan 30 hari & 90 hari untuk semua produk yang cocok di cabang ini
        $matchedProductIds = $products->pluck('id')->filter()->unique()->toArray();
        $sales30d = [];
        $sales90d = [];
        if (!empty($matchedProductIds) && $branchId) {
            $sales30d = TransactionItem::whereIn('product_id', $matchedProductIds)
                ->whereHas('transaction', fn($q) => $q->where('branch_id', $branchId)->where('created_at', '>=', now()->subDays(30)))
                ->selectRaw('product_id, SUM(quantity) as total_qty')
                ->groupBy('product_id')
                ->pluck('total_qty', 'product_id')
                ->toArray();

            $sales90d = TransactionItem::whereIn('product_id', $matchedProductIds)
                ->whereHas('transaction', fn($q) => $q->where('branch_id', $branchId)->where('created_at', '>=', now()->subDays(90)))
                ->selectRaw('product_id, SUM(quantity) as total_qty')
                ->groupBy('product_id')
                ->pluck('total_qty', 'product_id')
                ->toArray();
        }

        $matchedList = [];

        // Buat map barcode -> product
        $productMap = [];
        foreach ($products as $prod) {
            if ($prod->barcode) $productMap[$prod->barcode] = $prod;
            if ($prod->sku) $productMap[$prod->sku] = $prod;
            $metaBarcodes = $prod->metadata['additional_barcodes'] ?? [];
            if (is_array($metaBarcodes)) {
                foreach ($metaBarcodes as $mb) {
                    $productMap[trim($mb)] = $prod;
                }
            }
        }

        // Buat map keranjang saat ini
        $cartMap = [];
        foreach ($currentCart as $idx => $cartItem) {
            $cartMap[$cartItem['product_id']] = $idx;
        }

        foreach ($parsedItems as $item) {
            $bc = $item['barcode'];
            $product = $productMap[$bc] ?? null;

            if ($product) {
                $stockRec = $product->stocks->first();
                $currentStock = $stockRec ? (float)$stockRec->quantity_on_hand : 0;
                $minQty = $stockRec ? (float)$stockRec->min_qty : 0;
                $maxQty = $stockRec ? (float)$stockRec->max_qty : 0;
                $avgBln = (float)($sales30d[$product->id] ?? 0);
                $s90 = (float)($sales90d[$product->id] ?? 0);
                $isOosWithHistory = false;

                // Hitung Qty Saran: Min-Max, riwayat penjualan 30 hari, atau Fallback 90 hari saat stok kosong
                $qtySaran = 0;
                if ($minQty > 0 && $maxQty > 0) {
                    if ($currentStock < $minQty) {
                        $qtySaran = max(1, $maxQty - $currentStock);
                    }
                } elseif ($avgBln > 0) {
                    $dailyAvg = $avgBln / 30;
                    $needQty = ceil($dailyAvg * 14);
                    if ($currentStock < $needQty) {
                        $qtySaran = max(1, $needQty - $currentStock);
                    }
                } elseif (($currentStock <= 0 || ($minQty > 0 && $currentStock <= $minQty)) && $s90 >= 10) {
                    // Fallback Pasokan Macet (OOS): Toko kehabisan / menipis stok, hitung kebutuhan dari riwayat 90 hari (hanya jika >= 10 pcs)
                    $isOosWithHistory = true;
                    $dailyAvg = $s90 / 90;
                    $needQty = ceil($dailyAvg * 14);
                    $qtySaran = max(1, $needQty - $currentStock);
                }

                $inCart = isset($cartMap[$product->id]);

                // Tentukan status rekomendasi
                $recommendation = 'need_order';
                if ($qtySaran > 0) {
                    $recommendation = 'need_order'; // Hijau: butuh order
                } elseif (($currentStock > 0 && $avgBln <= 2 && !$isOosWithHistory) || ($currentStock <= 0 && $s90 < 10)) {
                    $recommendation = 'slow_moving'; // Merah: lambat laku & jangan otomatis diorder
                } else {
                    $recommendation = 'stock_sufficient'; // Kuning: stok masih aman
                }

                $matchedList[] = [
                    'barcode' => $bc,
                    'file_name' => $item['name_from_file'],
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_cost' => (float)$product->cost_price,
                    'promo_raw' => $item['promo_raw'],
                    'disc_percent' => $item['disc_percent'],
                    'promo_note' => $item['promo_note'],
                    'is_nominal_or_box' => $item['is_nominal_or_box'],
                    'current_stock' => $currentStock,
                    'avg_bln' => $avgBln,
                    'qty_saran' => $qtySaran,
                    'in_cart' => $inCart,
                    'recommendation' => $recommendation,
                    'selected' => ($inCart || $qtySaran > 0),
                    'is_matched' => true,
                ];
            } else {
                $matchedList[] = [
                    'barcode' => $bc,
                    'file_name' => $item['name_from_file'],
                    'product_id' => null,
                    'product_name' => null,
                    'unit_cost' => 0,
                    'promo_raw' => $item['promo_raw'],
                    'disc_percent' => $item['disc_percent'],
                    'promo_note' => $item['promo_note'],
                    'is_nominal_or_box' => $item['is_nominal_or_box'],
                    'current_stock' => 0,
                    'avg_bln' => 0,
                    'qty_saran' => 0,
                    'in_cart' => false,
                    'recommendation' => 'unmatched',
                    'selected' => false,
                    'is_matched' => false,
                ];
            }
        }

        return $matchedList;
    }
}
