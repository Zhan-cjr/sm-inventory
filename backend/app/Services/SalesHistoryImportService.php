<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductMonthlySalesHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesHistoryImportService
{
    /**
     * Preview files before actually storing into the database.
     */
    public function preview(string|array $filePaths, string $branchId): array
    {
        return $this->processFiles($filePaths, $branchId, false);
    }

    /**
     * Import and persist parsed historical sales data.
     */
    public function import(string|array $filePaths, string $branchId): array
    {
        return $this->processFiles($filePaths, $branchId, true);
    }

    /**
     * Core processing engine for multi-file and multi-sheet legacy reports.
     */
    protected function processFiles(string|array $filePaths, string $branchId, bool $persist): array
    {
        $files = is_array($filePaths) ? $filePaths : [$filePaths];

        // Eager load product lookup map: barcode -> Product, sku -> Product
        $products = Product::select('id', 'name', 'barcode', 'sku')->get();
        $barcodeMap = [];
        $skuMap = [];

        foreach ($products as $p) {
            if (!empty($p->barcode)) {
                $barcodeMap[trim((string)$p->barcode)] = $p;
            }
            if (!empty($p->sku)) {
                $skuMap[trim((string)$p->sku)] = $p;
            }
        }

        $summary = [
            'total_files' => count($files),
            'total_sheets' => 0,
            'total_rows_parsed' => 0,
            'matched_records_count' => 0,
            'unmatched_records_count' => 0,
            'periods_detected' => [],
            'sheets_detail' => [],
            'unmatched_samples' => [],
            'persisted' => $persist,
        ];

        $recordsToPersist = [];

        foreach ($files as $filePath) {
            if (!file_exists($filePath)) {
                continue;
            }

            try {
                $spreadsheet = IOFactory::load($filePath);
                $sheetNames = $spreadsheet->getSheetNames();

                foreach ($sheetNames as $sheetName) {
                    $sheet = $spreadsheet->getSheetByName($sheetName);
                    if (!$sheet) {
                        continue;
                    }

                    $sheetSummary = $this->parseSheet($sheet, $sheetName, $barcodeMap, $skuMap, $branchId);
                    if ($sheetSummary['total_rows'] === 0) {
                        continue; // Skip empty sheets
                    }

                    $summary['total_sheets']++;
                    $summary['total_rows_parsed'] += $sheetSummary['total_rows'];
                    $summary['matched_records_count'] += count($sheetSummary['matched_records']);
                    $summary['unmatched_records_count'] += count($sheetSummary['unmatched_records']);

                    $periodKey = sprintf('%04d-%02d', $sheetSummary['year'], $sheetSummary['month']);
                    if (!in_array($periodKey, $summary['periods_detected'])) {
                        $summary['periods_detected'][] = $periodKey;
                    }

                    $summary['sheets_detail'][] = [
                        'file' => basename($filePath),
                        'sheet_name' => $sheetName,
                        'period' => $periodKey,
                        'total_rows' => $sheetSummary['total_rows'],
                        'matched' => count($sheetSummary['matched_records']),
                        'unmatched' => count($sheetSummary['unmatched_records']),
                    ];

                    foreach ($sheetSummary['matched_records'] as $rec) {
                        $recordsToPersist[] = $rec;
                    }

                    // Keep up to 20 unmatched sample rows for user diagnosis
                    if (count($summary['unmatched_samples']) < 20 && !empty($sheetSummary['unmatched_records'])) {
                        foreach ($sheetSummary['unmatched_records'] as $unmatched) {
                            if (count($summary['unmatched_samples']) >= 20) break;
                            $summary['unmatched_samples'][] = $unmatched;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error("SalesHistoryImportService error processing {$filePath}: " . $e->getMessage());
                $summary['errors'][] = basename($filePath) . ': ' . $e->getMessage();
            }
        }

        sort($summary['periods_detected']);

        // Persist to database if requested using ultra-fast bulk upsert
        if ($persist && !empty($recordsToPersist)) {
            $now = now();
            $upsertData = [];
            foreach ($recordsToPersist as $rec) {
                $upsertData[] = [
                    'id' => (string)\Illuminate\Support\Str::uuid(),
                    'branch_id' => $rec['branch_id'],
                    'product_id' => $rec['product_id'],
                    'year' => $rec['year'],
                    'month' => $rec['month'],
                    'quantity_sold' => $rec['quantity_sold'],
                    'source' => 'MIGRATION',
                    'notes' => 'Imported from legacy report',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Bulk upsert in chunks of 1000
            foreach (array_chunk($upsertData, 1000) as $chunk) {
                ProductMonthlySalesHistory::upsert(
                    $chunk,
                    ['branch_id', 'product_id', 'year', 'month'],
                    ['quantity_sold', 'source', 'notes', 'updated_at']
                );
            }

            $summary['saved_to_database'] = count($upsertData);
        }

        return $summary;
    }

    /**
     * Parse a single worksheet.
     */
    protected function parseSheet(
        Worksheet $sheet,
        string $sheetName,
        array &$barcodeMap,
        array &$skuMap,
        string $branchId
    ): array {
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        // 1. Detect Year & Month from sheet content (rows 1-7) or sheet name
        [$year, $month] = $this->detectPeriod($sheet, $sheetName);

        // 2. Find table header row
        $headerRowIndex = null;
        $colMap = [
            'sku' => null,
            'barcode' => null,
            'name' => null,
            'qty' => null,
        ];

        for ($row = 1; $row <= min(15, $highestRow); $row++) {
            $rowValues = [];
            for ($col = 'A'; $col <= 'Z'; $col++) {
                $val = trim((string)$sheet->getCell($col . $row)->getValue());
                if (!empty($val)) {
                    $rowValues[$col] = strtolower($val);
                }
                if ($col === $highestCol) break;
            }

            // Check if this row looks like the header
            $hasSku = false;
            $hasBarcode = false;
            $hasQty = false;

            foreach ($rowValues as $col => $val) {
                if (preg_match('/^(kd\s*brg|kode\s*barang|sku|kode)$/i', $val)) {
                    $colMap['sku'] = $col;
                    $hasSku = true;
                }
                if (preg_match('/^(barcode|kode\s*barcode)$/i', $val)) {
                    $colMap['barcode'] = $col;
                    $hasBarcode = true;
                }
                if (preg_match('/^(nm\s*brg|nama\s*barang|nama\s*produk|produk)$/i', $val)) {
                    $colMap['name'] = $col;
                }
                if (preg_match('/^(qty|jumlah|kuantitas|terjual)$/i', $val)) {
                    $colMap['qty'] = $col;
                    $hasQty = true;
                }
            }

            if (($hasBarcode || $hasSku) && $hasQty) {
                $headerRowIndex = $row;
                break;
            }
        }

        // Fallback default column positions matching user's legacy report format:
        // Col A = Kd Brg, Col B = Barcode, Col C = Nm Brg, Col E = Qty
        if (!$headerRowIndex) {
            $headerRowIndex = 8;
            $colMap = [
                'sku' => 'A',
                'barcode' => 'B',
                'name' => 'C',
                'qty' => 'E',
            ];
        } else {
            if (!$colMap['barcode'] && !$colMap['sku']) $colMap['barcode'] = 'B';
            if (!$colMap['qty']) $colMap['qty'] = 'E';
        }

        $matchedRecords = [];
        $unmatchedRecords = [];
        $totalRows = 0;

        // Group quantities by product_id in case a product appears multiple times
        $productQtyAccumulator = [];

        for ($row = $headerRowIndex + 1; $row <= $highestRow; $row++) {
            $skuVal = $colMap['sku'] ? trim((string)$sheet->getCell($colMap['sku'] . $row)->getValue()) : '';
            $barcodeVal = $colMap['barcode'] ? trim((string)$sheet->getCell($colMap['barcode'] . $row)->getValue()) : '';
            $nameVal = $colMap['name'] ? trim((string)$sheet->getCell($colMap['name'] . $row)->getValue()) : '';
            $qtyRaw = $colMap['qty'] ? $sheet->getCell($colMap['qty'] . $row)->getValue() : null;

            // Stop or skip if row is blank or a "Total" summary line
            if (empty($skuVal) && empty($barcodeVal)) {
                continue;
            }
            if (preg_match('/^(total|grand\s*total|subtotal)/i', $skuVal) ||
                preg_match('/^(total|grand\s*total|subtotal)/i', $barcodeVal) ||
                preg_match('/^(total|grand\s*total|subtotal)/i', $nameVal)) {
                break;
            }

            $totalRows++;
            $qty = $this->sanitizeQty($qtyRaw);

            // Attempt match by Barcode first, then by SKU
            $matchedProduct = null;
            if (!empty($barcodeVal) && isset($barcodeMap[$barcodeVal])) {
                $matchedProduct = $barcodeMap[$barcodeVal];
            } elseif (!empty($skuVal) && isset($skuMap[$skuVal])) {
                $matchedProduct = $skuMap[$skuVal];
            } elseif (!empty($barcodeVal)) {
                // Try trimming leading zeroes if raw has them or vice versa
                $trimmedBarcode = ltrim($barcodeVal, '0');
                if (!empty($trimmedBarcode) && isset($barcodeMap[$trimmedBarcode])) {
                    $matchedProduct = $barcodeMap[$trimmedBarcode];
                }
            }

            if ($matchedProduct) {
                $pId = $matchedProduct->id;
                if (!isset($productQtyAccumulator[$pId])) {
                    $productQtyAccumulator[$pId] = [
                        'branch_id' => $branchId,
                        'product_id' => $pId,
                        'year' => $year,
                        'month' => $month,
                        'quantity_sold' => 0.0,
                    ];
                }
                $productQtyAccumulator[$pId]['quantity_sold'] += $qty;
            } else {
                $unmatchedRecords[] = [
                    'row' => $row,
                    'sku' => $skuVal,
                    'barcode' => $barcodeVal,
                    'name' => $nameVal,
                    'qty' => $qty,
                ];
            }
        }

        $matchedRecords = array_values($productQtyAccumulator);

        return [
            'year' => $year,
            'month' => $month,
            'total_rows' => $totalRows,
            'matched_records' => $matchedRecords,
            'unmatched_records' => $unmatchedRecords,
        ];
    }

    /**
     * Auto-detect Period (Year, Month) from sheet headers or sheet name.
     */
    public function detectPeriod(Worksheet $sheet, string $sheetName): array
    {
        // 1. Search header rows 1 to 7 for "Periode Laporan : 01-09-2026 - 30-09-2026"
        for ($row = 1; $row <= 7; $row++) {
            for ($col = 'A'; $col <= 'G'; $col++) {
                $val = (string)$sheet->getCell($col . $row)->getValue();
                if (empty($val)) continue;

                // Match DD-MM-YYYY or DD/MM/YYYY
                if (preg_match('/(?:periode|tanggal).*?(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/i', $val, $matches)) {
                    $month = (int)$matches[2];
                    $year = (int)$matches[3];
                    if ($month >= 1 && $month <= 12 && $year >= 2000 && $year <= 2100) {
                        return [$year, $month];
                    }
                }
            }
        }

        // 2. Parse from Sheet Name, e.g. "09-2026", "2026-09", "September 2026", "Jan 2026"
        $name = trim($sheetName);

        // Pattern: MM-YYYY or YYYY-MM
        if (preg_match('/(\d{1,2})[-_](\d{4})/', $name, $m)) {
            $month = (int)$m[1];
            $year = (int)$m[2];
            if ($month >= 1 && $month <= 12) return [$year, $month];
        }
        if (preg_match('/(\d{4})[-_](\d{1,2})/', $name, $m)) {
            $year = (int)$m[1];
            $month = (int)$m[2];
            if ($month >= 1 && $month <= 12) return [$year, $month];
        }

        // Pattern with Month Names (Indonesian & English)
        $monthPatterns = [
            'januari|january|jan' => 1,
            'februari|february|feb' => 2,
            'maret|march|mar' => 3,
            'april|apr' => 4,
            'mei|may' => 5,
            'juni|june|jun' => 6,
            'juli|july|jul' => 7,
            'agustus|august|ags|agu' => 8,
            'september|sep' => 9,
            'oktober|october|okt|oct' => 10,
            'november|nov' => 11,
            'desember|december|des|dec' => 12,
        ];

        foreach ($monthPatterns as $pattern => $mNum) {
            if (preg_match('/(' . $pattern . ')\D*(\d{4})/i', $name, $m)) {
                $year = (int)$m[2];
                return [$year, $mNum];
            }
            if (preg_match('/(\d{4})\D*(' . $pattern . ')/i', $name, $m)) {
                $year = (int)$m[1];
                return [$year, $mNum];
            }
        }

        // Default fallback to previous month if undetected
        $lastMonth = Carbon::now()->subMonth();
        return [$lastMonth->year, $lastMonth->month];
    }

    /**
     * Sanitize Indonesian decimal and thousand separator numbers.
     * E.g. "2,00" -> 2.0, "1.500,00" -> 1500.0, "1,5" -> 1.5, "2" -> 2.0
     */
    protected function sanitizeQty(mixed $raw): float
    {
        if (is_numeric($raw)) {
            return (float)$raw;
        }

        if (empty($raw)) {
            return 0.0;
        }

        $str = trim((string)$raw);
        // Replace thousand dots: "1.500,00" -> "1500,00"
        $str = preg_replace('/(?<=\d)\.(?=\d{3}(?:[,\.]|$))/', '', $str);
        // Convert decimal comma to dot: "1500,00" -> "1500.00"
        $str = str_replace(',', '.', $str);
        // Strip any remaining non-numeric characters except dot and minus
        $str = preg_replace('/[^\d\.\-]/', '', $str);

        return is_numeric($str) ? (float)$str : 0.0;
    }
}
