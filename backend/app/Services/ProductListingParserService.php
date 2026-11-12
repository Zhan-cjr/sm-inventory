<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductListingParserService
{
    /**
     * Parse uploaded listing document (Image, PDF, Excel, or CSV)
     * and return structured data ready for the Listing Produk form/database.
     */
    public function parseDocument(
        string $filePath,
        float $defaultMargin = 10.0,
        ?string $organizationId = null
    ): array {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File dokumen tidak ditemukan di path: {$filePath}");
        }

        $orgId = $organizationId ?? Auth::user()?->organization_id ?? Organization::first()?->id;
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['xlsx', 'xls', 'csv'])) {
            $raw = $this->parseSpreadsheet($filePath);
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'pdf'])) {
            $raw = $this->parseImageOrPdf($filePath);
        } else {
            // Attempt spreadsheet first, then image/pdf fallback
            try {
                $raw = $this->parseSpreadsheet($filePath);
            } catch (\Throwable $e) {
                $raw = $this->parseImageOrPdf($filePath);
            }
        }

        return $this->processExtractedData($raw, $defaultMargin, $orgId);
    }

    /**
     * Parse Spreadsheet (.xlsx, .xls, .csv)
     */
    protected function parseSpreadsheet(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $highestRow = $worksheet->getHighestRow();
        $highestCol = $worksheet->getHighestColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestCol);

        $headerRow = null;
        $colMap = [
            'barcode' => null,
            'name' => null,
            'unit_price' => null,
            'carton_price' => null,
            'pack_qty' => null,
            'uom' => null,
        ];

        $metadata = [
            'supplier_name' => null,
            'listing_number' => null,
            'listing_fee' => 0,
            'notes' => null,
        ];

        // 1. Scan rows 1 to 20 for header and metadata
        $scanLimit = min(25, $highestRow);
        for ($row = 1; $row <= $scanLimit; $row++) {
            $rowValues = [];
            for ($col = 1; $col <= min(20, $highestColIndex); $col++) {
                $val = trim((string) $worksheet->getCell([$col, $row])->getValue());
                if (!empty($val)) {
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $rowValues[$colLetter] = $val;

                    // Detect metadata in top header rows
                    $lowerVal = strtolower($val);
                    if (preg_match('/(supplier|distributor|perusahaan)\s*[:=]\s*(.+)/i', $val, $m)) {
                        $metadata['supplier_name'] = trim($m[2]);
                    } elseif (preg_match('/no(?:mor)?\s*[:=]\s*([^\n\r]+)/i', $val, $m)) {
                        $metadata['listing_number'] = trim($m[1]);
                    } elseif (preg_match('/listing\s*fee|biaya\s*listing/i', $lowerVal)) {
                        // Look at next column or in text for numeric value
                        $nextVal = trim((string) $worksheet->getCell([$col + 1, $row])->getValue());
                        $fee = $this->cleanNumber($nextVal ?: $val);
                        if ($fee > 0) $metadata['listing_fee'] = $fee;
                    }
                }
            }

            // Check if this row looks like the table header
            $detected = [
                'barcode' => null,
                'name' => null,
                'unit_price' => null,
                'carton_price' => null,
                'pack_qty' => null,
                'uom' => null,
            ];

            foreach ($rowValues as $colLetter => $val) {
                $lower = strtolower($val);
                if (preg_match('/(barcode|no\.?\s*barcode|ean|plu|kode)/i', $lower) && !$detected['barcode']) {
                    $detected['barcode'] = $colLetter;
                } elseif (preg_match('/(nama\s*barang|nama\s*produk|nama|deskripsi|item|product)/i', $lower) && !$detected['name']) {
                    $detected['name'] = $colLetter;
                } elseif (preg_match('/(harga\s*satuan|satuan|dpp|harga\s*pcs|unit\s*price|harga\s*sat)/i', $lower) && !$detected['unit_price']) {
                    $detected['unit_price'] = $colLetter;
                } elseif (preg_match('/(harga\s*\/karton|harga\s*karton|karton|box|ctn)/i', $lower) && !$detected['carton_price']) {
                    $detected['carton_price'] = $colLetter;
                } elseif (preg_match('/(isi|pack|qty\s*ctn|pcs\/ctn)/i', $lower) && !$detected['pack_qty']) {
                    $detected['pack_qty'] = $colLetter;
                } elseif (preg_match('/(satuan|uom|kemasan)/i', $lower) && !$detected['uom']) {
                    $detected['uom'] = $colLetter;
                }
            }

            if (($detected['barcode'] || $detected['name']) && ($detected['unit_price'] || $detected['carton_price'])) {
                $headerRow = $row;
                $colMap = array_merge($colMap, array_filter($detected));
                break;
            }
        }

        // Fallbacks if some columns were not detected
        if (!$headerRow) {
            $headerRow = 1;
            $colMap['barcode'] = 'A';
            $colMap['name'] = 'B';
            $colMap['unit_price'] = 'C';
        }

        // 2. Read table items
        $items = [];
        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $barcode = $colMap['barcode'] ? trim((string) $worksheet->getCell([Coordinate::columnIndexFromString($colMap['barcode']), $row])->getValue()) : '';
            $name = $colMap['name'] ? trim((string) $worksheet->getCell([Coordinate::columnIndexFromString($colMap['name']), $row])->getValue()) : '';
            $unitPriceRaw = $colMap['unit_price'] ? trim((string) $worksheet->getCell([Coordinate::columnIndexFromString($colMap['unit_price']), $row])->getValue()) : '';
            $cartonPriceRaw = $colMap['carton_price'] ? trim((string) $worksheet->getCell([Coordinate::columnIndexFromString($colMap['carton_price']), $row])->getValue()) : '';
            $packQtyRaw = $colMap['pack_qty'] ? trim((string) $worksheet->getCell([Coordinate::columnIndexFromString($colMap['pack_qty']), $row])->getValue()) : '';
            $uom = $colMap['uom'] ? trim((string) $worksheet->getCell([Coordinate::columnIndexFromString($colMap['uom']), $row])->getValue()) : 'pcs';

            // Clean barcode (remove .0 if parsed as float)
            $barcode = preg_replace('/\.0+$/', '', $barcode);
            $barcode = preg_replace('/[^\w\-]/', '', $barcode);

            $unitPrice = $this->cleanNumber($unitPriceRaw);
            $cartonPrice = $this->cleanNumber($cartonPriceRaw);
            $packQty = (int) $this->cleanNumber($packQtyRaw);

            // If unit_price is 0 but carton_price and pack_qty are given, calculate unit_price
            if ($unitPrice <= 0 && $cartonPrice > 0 && $packQty > 0) {
                $unitPrice = round($cartonPrice / $packQty, 2);
            }

            if (empty($barcode) && empty($name)) {
                continue;
            }

            // Exclude signature/footer rows
            if (preg_match('/(disetujui|pemohon|materai|catatan|total)/i', $name)) {
                continue;
            }

            $items[] = [
                'barcode' => $barcode,
                'name' => $name,
                'unit_price' => $unitPrice,
                'carton_price' => $cartonPrice > 0 ? $cartonPrice : null,
                'pack_qty' => $packQty > 0 ? $packQty : null,
                'unit_of_measure' => !empty($uom) ? strtolower($uom) : 'pcs',
            ];
        }

        return [
            'supplier_name' => $metadata['supplier_name'],
            'listing_number' => $metadata['listing_number'],
            'listing_fee' => $metadata['listing_fee'],
            'notes' => $metadata['notes'],
            'items' => $items,
        ];
    }

    /**
     * Parse Image or PDF using Gemini AI Vision OCR
     */
    protected function parseImageOrPdf(string $filePath): array
    {
        $mimeType = match (strtolower(pathinfo($filePath, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        // 1. Try Gemini REST API directly
        try {
            $geminiResult = $this->callGeminiApi($filePath, $mimeType);
            if (!empty($geminiResult) && !empty($geminiResult['items'])) {
                return $geminiResult;
            }
        } catch (\Throwable $e) {
            Log::warning("Gemini Vision direct API call failed: " . $e->getMessage());
        }

        // 2. Fallback to AI Service if configured
        try {
            $aiServiceResult = $this->callAiService($filePath);
            if (!empty($aiServiceResult) && !empty($aiServiceResult['items'])) {
                return $aiServiceResult;
            }
        } catch (\Throwable $e) {
            Log::warning("AI Service fallback call failed: " . $e->getMessage());
        }

        throw new \RuntimeException(
            "Gagal mengekstrak data dari dokumen gambar/PDF. Pastikan kunci GEMINI_API_KEY telah dikonfigurasi pada server, atau unggah dokumen dalam format Excel (.xlsx / .csv)."
        );
    }

    /**
     * Direct call to Google Gemini Vision REST API
     */
    protected function callGeminiApi(string $filePath, string $mimeType): ?array
    {
        $apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            Log::warning("GEMINI_API_KEY tidak dikonfigurasi.");
            return null;
        }

        $configuredModel = config('services.gemini.model') ?? env('GEMINI_MODEL', 'gemini-2.5-flash');
        $modelsToTry = array_unique([$configuredModel, 'gemini-2.5-flash', 'gemini-1.5-flash', 'gemini-3.1-flash-lite']);

        $fileBytes = file_get_contents($filePath);
        $base64Data = base64_encode($fileBytes);

        $prompt = <<<PROMPT
You are a specialized AI for Indonesian retail inventory systems and document OCR.
Analyze this document (Formulir Registrasi Produk / Surat Pengajuan Listing Baru / New Product Listing Form).

Extract the following data:
1. Header metadata (if visible):
   - "supplier_name": String (Company / Distributor name, e.g. "PT SAYAP MAS UTAMA" or "PT CIANJUR ALAM UTAMA")
   - "listing_number": String (Document number / No Formulir, e.g. "194 / DT / FL / 2026")
   - "listing_fee": Number (Total listing fee in Rupiah, or 0 if free / dash / Rp 0)
   - "notes": String (Any payment terms, TOP, trial duration, e.g. "TOP 3 bulan")
2. Table rows under "PRODUK YANG DIUSULKAN" / product table:
   For EACH product row, extract:
   - "barcode": String (Digits of No. Barcode / EAN / PLU, e.g. "8998866205658". Keep exact numeric string without spaces)
   - "name": String (Full product name as written, e.g. "GOLDA BTL 200ML AREN LATTE MT")
   - "unit_price": Number (HARGA SATUAN / DPP for 1 piece/bottle/pack, e.g. 3050. CRITICAL: This must be the SINGLE unit price, NOT the carton price)
   - "carton_price": Number or null (Harga per karton, e.g. 36600)
   - "pack_qty": Number or null (Isi / pack per carton, e.g. 12)
   - "unit_of_measure": String (e.g. "pcs", "btl", "sct", "bag", default to "pcs")

CRITICAL INSTRUCTIONS:
- The "unit_price" is the DPP (Harga Satuan). In Indonesian documents, thousand separators are dots (e.g. 3.050 means 3050, 36.600 means 36600). Convert them to valid numbers.
- Return ONLY valid JSON with this exact structure:
{
  "supplier_name": "...",
  "listing_number": "...",
  "listing_fee": 0,
  "notes": "...",
  "items": [
    {
      "barcode": "8998866205658",
      "name": "GOLDA BTL 200ML AREN LATTE MT",
      "unit_price": 3050,
      "carton_price": 36600,
      "pack_qty": 12,
      "unit_of_measure": "pcs"
    }
  ]
}
PROMPT;

        foreach ($modelsToTry as $model) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($endpoint, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $base64Data,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($text) {
                    $json = $this->cleanAndParseJson($text);
                    if (!empty($json) && !empty($json['items'])) {
                        return $json;
                    }
                }
            } else {
                Log::warning("Gemini model {$model} returned {$response->status()}: " . $response->body());
            }
        }

        return null;
    }

    /**
     * Fallback call to internal python AI Service
     */
    protected function callAiService(string $filePath): ?array
    {
        $aiUrl = env('AI_SERVICE_URL', 'http://ai-service:8001');
        $endpoint = rtrim($aiUrl, '/') . '/api/v1/ai/scan-listing';

        $response = Http::timeout(60)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post($endpoint);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Process extracted raw data into final format:
     * - Checks existing barcodes against database (Product model)
     * - If barcode found: does NOT error, marks as existing product & puts into TRIAL status
     * - If barcode not found: generates new unique SKU automatically
     * - Uses unit_price as DPP (cost_price)
     * - Applies tax (11%) to determine cost_price_tax
     * - Applies margin percentage (default 10%) to determine harga_jual_1 & selling_price
     */
    protected function processExtractedData(array $raw, float $defaultMargin, string $organizationId): array
    {
        $rawItems = $raw['items'] ?? [];
        $taxRate = RetailIntelligenceService::getActiveTaxRate();

        // Yang dipakai hanya daftar barangnya saja (supplier dipilih pada form)
        $supplierId = null;

        $processedItems = [];
        $reservedSkus = [];
        $existingCount = 0;
        $newCount = 0;

        foreach ($rawItems as $item) {
            $rawBarcode = trim((string) ($item['barcode'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $uom = trim((string) ($item['unit_of_measure'] ?? 'pcs')) ?: 'pcs';

            if (empty($rawBarcode) && empty($name)) {
                continue;
            }

            // Check if barcode already exists in the system
            $existingProduct = null;
            if (!empty($rawBarcode)) {
                $existingProduct = Product::where(function ($q) use ($organizationId) {
                    if ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    }
                })
                ->where(function ($q) use ($rawBarcode) {
                    $q->where('barcode', $rawBarcode)
                      ->orWhere('sku', $rawBarcode)
                      ->orWhereJsonContains('metadata->additional_barcodes', $rawBarcode);
                })
                ->first();

                if (!$existingProduct) {
                    // Fallback cek global jika organization_id berbeda
                    $existingProduct = Product::where('barcode', $rawBarcode)
                        ->orWhere('sku', $rawBarcode)
                        ->orWhereJsonContains('metadata->additional_barcodes', $rawBarcode)
                        ->first();
                }
            }

            // Calculate DPP, HPP with Tax, Margin, and Selling Price
            // "harga yang tertera yang diambil adalah harga satuan, dan anggap itu harga dpp"
            $costPrice = $unitPrice > 0 ? $unitPrice : ($existingProduct?->cost_price ?? 0);
            $costPriceTax = round($costPrice * (1 + ($taxRate / 100)), 2);

            $margin = $defaultMargin > 0 ? $defaultMargin : 10.0;
            // Harga jual default = ceil((cost_price_tax * (1 + margin/100)) / 100) * 100 (bulatkan ke ratusan terdekat)
            $hargaJual = $costPriceTax > 0 ? ceil(($costPriceTax * (1 + ($margin / 100))) / 100) * 100 : 0;

            if ($existingProduct) {
                // BARCODE SUDAH DITEMUKAN:
                // Tidak error! Mempertahankan ID & SKU produk lama, status produk dialihkan ke pantauan listing
                $existingCount++;
                $processedItems[] = [
                    'id' => $existingProduct->id,
                    'sku' => $existingProduct->sku,
                    'barcode' => $rawBarcode ?: $existingProduct->barcode,
                    'name' => !empty($name) ? $name : $existingProduct->name,
                    'category_id' => $existingProduct->category_id,
                    'sub_category' => $existingProduct->sub_category,
                    'unit_of_measure' => !empty($uom) && $uom !== 'pcs' ? $uom : ($existingProduct->unit_of_measure ?? 'pcs'),
                    'cost_price' => $costPrice,
                    'cost_price_tax' => $costPriceTax,
                    'margin_gol_1' => $margin,
                    'harga_jual_1' => $hargaJual > 0 ? $hargaJual : ($existingProduct->harga_jual_1 ?? 0),
                    'selling_price' => $hargaJual > 0 ? $hargaJual : ($existingProduct->selling_price ?? 0),
                    'qty_min_gol_1' => 1,
                    'is_taxable' => true,
                    'is_active' => true,
                    'is_existing' => true,
                ];
            } else {
                // BARCODE BARU:
                // Otomatis buat SKU baru
                $newCount++;
                $newSku = Product::generateNextSku($organizationId, $reservedSkus);
                $reservedSkus[] = $newSku;

                // Fallback barcode jika barcode dari dokumen kosong
                $barcodeToUse = !empty($rawBarcode) ? $rawBarcode : date('dmy') . str_pad($newCount, 4, '0', STR_PAD_LEFT);

                $processedItems[] = [
                    'id' => null,
                    'sku' => $newSku,
                    'barcode' => $barcodeToUse,
                    'name' => $name ?: 'Produk Listing Baru ' . $newSku,
                    'category_id' => null, // akan diisi massal oleh header kategori form
                    'sub_category' => null,
                    'unit_of_measure' => $uom,
                    'cost_price' => $costPrice,
                    'cost_price_tax' => $costPriceTax,
                    'margin_gol_1' => $margin,
                    'harga_jual_1' => $hargaJual,
                    'selling_price' => $hargaJual,
                    'qty_min_gol_1' => 1,
                    'is_taxable' => true,
                    'is_active' => true,
                    'is_existing' => false,
                ];
            }
        }

        return [
            'supplier_id' => $supplierId,
            'supplier_name' => $raw['supplier_name'] ?? null,
            'listing_number' => $raw['listing_number'] ?? null,
            'listing_fee' => (float) ($raw['listing_fee'] ?? 0),
            'notes' => $raw['notes'] ?? null,
            'items' => $processedItems,
            'summary' => [
                'total_items' => count($processedItems),
                'new_items_count' => $newCount,
                'existing_items_count' => $existingCount,
            ],
        ];
    }

    /**
     * Clean numeric string from Indonesian formats (e.g. 36.600 -> 36600, 3.050 -> 3050)
     */
    protected function cleanNumber(?string $val): float
    {
        if (blank($val)) return 0;
        $val = trim((string) $val);
        // Remove currency symbols, spaces, quotes
        $val = preg_replace('/[^\d.,]/', '', $val);
        if (empty($val)) return 0;

        // Indonesian formatting: 1.500.000,50 or 3.050
        if (str_contains($val, '.') && str_contains($val, ',')) {
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
        } elseif (str_contains($val, '.')) {
            // Check if dot is thousand separator (e.g. 3.050 or 36.600 or 150.000)
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $val)) {
                $val = str_replace('.', '', $val);
            }
        } elseif (str_contains($val, ',')) {
            // Single comma: decimal separator (e.g. 3,5)
            $val = str_replace(',', '.', $val);
        }

        return (float) $val;
    }

    /**
     * Safely parse JSON text from AI response
     */
    protected function cleanAndParseJson(string $text): ?array
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $matches)) {
                $text = trim($matches[1]);
            }
        }

        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return null;
    }
}

