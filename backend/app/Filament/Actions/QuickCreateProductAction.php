<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Actions;
use Filament\Notifications\Notification;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class QuickCreateProductAction
{
    public static function make(): Action
    {
        return Action::make('quick_create_product')
            ->label('Tambah Produk Cepat (Batch)')
            ->icon('heroicon-o-bolt')
            ->color('warning')
            ->modalWidth('7xl')
            ->modalHeading('Tambah Produk Cepat (Batch / Paste Teks)')
            ->modalDescription('Fitur penambahan produk massal dengan deteksi otomatis master lama vs baru, pencegahan duplikasi, dan tinjauan tabel sebelum disimpan.')
            ->visible(function () {
                $user = Auth::user();
                if (!$user) return false;
                if ($user->branch_id !== null) return false; // Branch user not allowed
                return $user->hasRole(['superadmin', 'super_admin', 'super-admin']) 
                    || $user->hasCustomAuthorization('QUICK_CREATE_PRODUCT') 
                    || $user->can('Create:Product');
            })
            ->form([
                Section::make('1. Header Default Batch')
                    ->description('Pengaturan utama untuk kelompok barang yang akan ditambahkan (Supplier, Cabang, Kategori & Tax Default)')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('organization_id')
                                ->label('Organisasi')
                                ->options(Organization::pluck('name', 'id'))
                                ->default(fn() => Auth::user()?->organization_id ?? Organization::first()?->id)
                                ->required()
                                ->searchable(),
                            Select::make('branch_id')
                                ->label('Cabang Target')
                                ->options(Branch::pluck('name', 'id'))
                                ->default(fn() => Auth::user()?->branch_id ?? Branch::first()?->id)
                                ->required()
                                ->searchable()
                                ->helperText('Stok awal dan harga cabang akan diterapkan ke cabang ini.'),
                            Select::make('supplier_id')
                                ->label('Supplier')
                                ->options(Supplier::where('is_active', true)->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->helperText('Katalog supplier ini akan dijadikan acuan pencocokan master lama.'),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('default_category_id')
                                ->label('Kategori Default')
                                ->options(Category::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('default_sub_category', null)),
                            Select::make('default_sub_category')
                                ->label('Sub Kategori Default')
                                ->placeholder(fn (\Filament\Schemas\Components\Utilities\Get $get) => empty($get('default_category_id')) ? 'Pilih Kategori...' : 'Pilih Sub Kategori')
                                ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                    $categoryId = $get('default_category_id');

                                    if (blank($categoryId)) {
                                        $current = $get('default_sub_category');
                                        return $current ? [$current => $current] : [];
                                    }

                                    $options = Product::where('category_id', $categoryId)
                                        ->whereNotNull('sub_category')
                                        ->where('sub_category', '!=', '')
                                        ->distinct()
                                        ->orderBy('sub_category')
                                        ->pluck('sub_category', 'sub_category')
                                        ->toArray();

                                    $current = $get('default_sub_category');
                                    if ($current && !isset($options[$current])) {
                                        $options[$current] = $current;
                                    }

                                    return $options;
                                })
                                ->searchable()
                                ->createOptionForm([
                                    TextInput::make('sub_category')
                                        ->label('Sub Kategori Baru')
                                        ->required(),
                                ])
                                ->createOptionUsing(fn (array $data) => $data['sub_category']),
                            Toggle::make('is_taxable')
                                ->label('Kena PPN (Pajak Aktif)')
                                ->default(false)
                                ->helperText('Matikan toggle ini jika ini barang Konsinyasi (Non-PPN).'),
                        ]),
                    ]),

                Section::make('2. Import dari Copy-Paste Teks')
                    ->description('Tempelkan teks daftar barang dari chat/catatan (Format: NAMA | HPP | JUAL), lalu klik tombol "Proses Teks ke Tabel & Deteksi Master".')
                    ->collapsible()
                    ->schema([
                        Textarea::make('paste_text')
                            ->label('Teks Daftar Barang')
                            ->rows(6)
                            ->placeholder("Contoh Format 1 (3 Kolom):\nKRAOS BASO | 10000 | 12000\nKRAOS CIKI BALL KEJU | 11000 | 13500\n\nContoh Format 2 (5 Kolom Beda Kategori):\nKRAOS BASO | 10000 | 12000 | KONSINYASI | KONSINYASI @BLN"),
                        
                        Actions::make([
                            Action::make('parse_text_action')
                                ->label('Proses Teks ke Tabel & Deteksi Master')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('info')
                                ->action(function (callable $get, callable $set) {
                                    $text = $get('paste_text');
                                    if (blank($text)) {
                                        Notification::make()
                                            ->title('Teks Kosong')
                                            ->body('Silakan tempel teks daftar barang terlebih dahulu.')
                                            ->warning()
                                            ->send();
                                        return;
                                    }

                                    $supplierId = $get('supplier_id');
                                    $defaultCatId = $get('default_category_id');
                                    $defaultSubCat = $get('default_sub_category');

                                    // Ambil master produk yang sudah ada di supplier ini
                                    $existingProducts = Product::query()
                                        ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
                                        ->get(['id', 'sku', 'barcode', 'name', 'category_id', 'sub_category', 'supplier_id']);

                                    $normMap = [];
                                    foreach ($existingProducts as $p) {
                                        $normKey = static::normalizeProductName($p->name);
                                        if (!isset($normMap[$normKey])) {
                                            $normMap[$normKey] = $p;
                                        }
                                    }

                                    // Siapkan prefix Barcode & SKU hari ini
                                    $prefixSku = 'SKU-' . date('dmy');
                                    $prefixBarcode = date('dmy');

                                    $lastSkuRecord = Product::where('sku', 'like', $prefixSku . '%')
                                        ->orderBy('sku', 'desc')
                                        ->first();
                                    $lastSkuNum = $lastSkuRecord ? (int) substr($lastSkuRecord->sku, -4) : 0;

                                    $lastBarcodeRecord = Product::where('barcode', 'like', $prefixBarcode . '%')
                                        ->orderBy('barcode', 'desc')
                                        ->first();
                                    $lastBarcodeNum = $lastBarcodeRecord ? (int) substr($lastBarcodeRecord->barcode, -4) : 0;

                                    $nextSeq = max($lastSkuNum, $lastBarcodeNum);

                                    $lines = explode("\n", str_replace("\r", "", $text));
                                    $existingItems = $get('items') ?? [];
                                    $matchedCount = 0;
                                    $newCount = 0;

                                    $currentName = null;
                                    $currentM = null;
                                    $currentJual = null;

                                    foreach ($lines as $line) {
                                        $trimmed = trim($line);
                                        if (empty($trimmed)) continue;
                                        if (str_starts_with($trimmed, '*') || str_starts_with($trimmed, '#') || str_starts_with(strtoupper($trimmed), 'SUPPLIER:')) {
                                            continue;
                                        }

                                        $name = null;
                                        $m = 0.0;
                                        $jual = 0.0;
                                        $catId = null;
                                        $subCat = null;

                                        // Format pemisah pipa (pipe)
                                        if (str_contains($trimmed, '|')) {
                                            $parts = array_map('trim', explode('|', $trimmed));
                                            if (count($parts) >= 3) {
                                                $name = $parts[0];
                                                $m = static::cleanNumber($parts[1]);
                                                $jual = static::cleanNumber($parts[2]);
                                                $catId = isset($parts[3]) && !empty($parts[3]) ? static::findCategoryId($parts[3]) : null;
                                                $subCat = isset($parts[4]) && !empty($parts[4]) ? $parts[4] : null;
                                            }
                                        }
                                        // Format multi-line M : 6000 / JUAL : 7500
                                        elseif (preg_match('/^M\s*:\s*(.+)$/i', $trimmed, $matches)) {
                                            $currentM = static::cleanNumber($matches[1]);
                                        } elseif (preg_match('/^JUAL\s*:\s*(.+)$/i', $trimmed, $matches)) {
                                            $currentJual = static::cleanNumber($matches[1]);
                                            if (!empty($currentName) && $currentM !== null && $currentJual !== null) {
                                                $name = $currentName;
                                                $m = $currentM;
                                                $jual = $currentJual;
                                                $currentName = null;
                                                $currentM = null;
                                                $currentJual = null;
                                            }
                                        } else {
                                            $currentName = $trimmed;
                                        }

                                        if (empty($name)) continue;

                                        // Deteksi kecocokan dengan master lama menggunakan Smart Matcher
                                        $matchResult = static::findBestMatch($name, $existingProducts);

                                        if ($matchResult) {
                                            $matched = $matchResult['product'];
                                            $score = $matchResult['score'];
                                            $existingItems[] = [
                                                'id' => (string) Str::uuid(),
                                                'name' => $name,
                                                'cost_price' => $m,
                                                'selling_price' => $jual,
                                                'category_id' => $catId ?: $matched->category_id ?: $defaultCatId,
                                                'sub_category' => $subCat ?: $matched->sub_category ?: $defaultSubCat,
                                                'status' => 'MATCHED',
                                                'product_id' => $matched->id,
                                                'matched_name' => $matched->name,
                                                'match_score' => $score,
                                                'barcode' => $matched->barcode,
                                                'sku' => $matched->sku,
                                                'force_new' => false,
                                            ];
                                            $matchedCount++;
                                        } else {
                                            $nextSeq++;
                                            $seqStr = str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
                                            $barcode = $prefixBarcode . $seqStr;
                                            $sku = $prefixSku . $seqStr;

                                            $existingItems[] = [
                                                'id' => (string) Str::uuid(),
                                                'name' => $name,
                                                'cost_price' => $m,
                                                'selling_price' => $jual,
                                                'category_id' => $catId ?: $defaultCatId,
                                                'sub_category' => $subCat ?: $defaultSubCat,
                                                'status' => 'NEW',
                                                'product_id' => null,
                                                'matched_name' => null,
                                                'match_score' => 0,
                                                'barcode' => $barcode,
                                                'sku' => $sku,
                                                'force_new' => false,
                                            ];
                                            $newCount++;
                                        }
                                    }

                                    $set('items', $existingItems);
                                    Notification::make()
                                        ->title('Hasil Analisa Batch Selesai')
                                        ->body("Ditemukan {$matchedCount} produk cocok dengan master lama, dan {$newCount} produk master baru. Silakan tinjau tabel di bawah.")
                                        ->success()
                                        ->send();
                                }),
                        ]),
                    ]),

                Section::make('3. Tabel Daftar Produk & Review Deteksi')
                    ->description('Tinjau kode lama vs kode baru hasil pencocokan sistem. Anda dapat mengedit nama/harga langsung di tabel atau mengubah status jika barang berbeda.')
                    ->schema([
                        ViewField::make('items')
                            ->label('')
                            ->view('filament.forms.components.quick-create-items-table')
                            ->default([]),
                    ]),
            ])
            ->action(function (array $data) {
                $orgId = $data['organization_id'];
                $branchId = $data['branch_id'];
                $supplierId = $data['supplier_id'];
                $defaultCatId = $data['default_category_id'];
                $defaultSubCat = $data['default_sub_category'] ?? null;
                $isTaxable = (bool) ($data['is_taxable'] ?? false);
                $items = $data['items'] ?? [];

                if (empty($items)) {
                    Notification::make()
                        ->title('Gagal')
                        ->body('Daftar item produk tidak boleh kosong.')
                        ->danger()
                        ->send();
                    return;
                }

                $prefixSku = 'SKU-' . date('dmy');
                $prefixBarcode = date('dmy');

                // Find highest existing index for SKU and Barcode today
                $lastSkuRecord = Product::where('sku', 'like', $prefixSku . '%')
                    ->orderBy('sku', 'desc')
                    ->first();
                $lastSkuNum = $lastSkuRecord ? (int) substr($lastSkuRecord->sku, -4) : 0;

                $lastBarcodeRecord = Product::where('barcode', 'like', $prefixBarcode . '%')
                    ->orderBy('barcode', 'desc')
                    ->first();
                $lastBarcodeNum = $lastBarcodeRecord ? (int) substr($lastBarcodeRecord->barcode, -4) : 0;

                $maxCounter = max($lastSkuNum, $lastBarcodeNum);

                $createdCount = 0;
                $linkedCount = 0;

                DB::beginTransaction();
                try {
                    $taxRate = \App\Services\RetailIntelligenceService::getActiveTaxRate();

                    foreach ($items as $item) {
                        $name = trim($item['name'] ?? '');
                        if (empty($name)) continue;

                        $costPrice = static::cleanNumber($item['cost_price'] ?? 0);
                        $sellingPrice = static::cleanNumber($item['selling_price'] ?? 0);
                        $catId = !empty($item['category_id']) ? $item['category_id'] : $defaultCatId;
                        $subCat = !empty($item['sub_category']) ? $item['sub_category'] : $defaultSubCat;

                        $costTax = $isTaxable ? round($costPrice * (1 + ($taxRate / 100)), 2) : $costPrice;
                        $margin = ($costTax > 0 && $sellingPrice > 0) 
                            ? round((($sellingPrice - $costTax) / $costTax) * 100, 2) 
                            : 0;

                        $isMatched = ($item['status'] ?? '') === 'MATCHED' && empty($item['force_new']) && !empty($item['product_id']);

                        if ($isMatched) {
                            $product = Product::find($item['product_id']);
                            if ($product) {
                                // Update atau Buat record Stok di cabang target
                                Stock::updateOrCreate(
                                    [
                                        'branch_id'  => $branchId,
                                        'product_id' => $product->id,
                                    ],
                                    [
                                        'cost_price'        => $costPrice,
                                        'cost_price_tax'    => $costTax,
                                        'selling_price'     => $sellingPrice,
                                        'harga_jual_1'      => $sellingPrice,
                                        'qty_min_gol_1'     => 1,
                                        'margin_gol_1'      => $margin,
                                        'harga_jual_2'      => 0,
                                        'qty_min_gol_2'     => 0,
                                        'margin_gol_2'      => 0,
                                        'harga_jual_3'      => 0,
                                        'qty_min_gol_3'     => 0,
                                        'margin_gol_3'      => 0,
                                        'is_active'         => true,
                                    ]
                                );
                                $linkedCount++;
                                continue;
                            }
                        }

                        // Jika Master Baru (atau Dipaksa Baru)
                        $maxCounter++;
                        $seqStr = str_pad($maxCounter, 4, '0', STR_PAD_LEFT);
                        $sku = $prefixSku . $seqStr;
                        $barcode = $prefixBarcode . $seqStr;

                        $product = Product::create([
                            'organization_id'      => $orgId,
                            'sku'                  => $sku,
                            'barcode'              => $barcode,
                            'name'                 => $name,
                            'category_id'          => $catId,
                            'sub_category'         => $subCat,
                            'supplier_id'          => $supplierId,
                            'cost_price'           => $costPrice,
                            'cost_price_tax'       => $costTax,
                            'selling_price'        => $sellingPrice,
                            'harga_jual_1'         => $sellingPrice,
                            'qty_min_gol_1'        => 1,
                            'margin_gol_1'         => $margin,
                            'harga_jual_2'         => 0,
                            'qty_min_gol_2'        => 0,
                            'margin_gol_2'         => 0,
                            'harga_jual_3'         => 0,
                            'qty_min_gol_3'        => 0,
                            'margin_gol_3'         => 0,
                            'is_taxable'           => $isTaxable,
                            'unit_of_measure'      => 'pcs',
                            'reorder_point'        => 10,
                            'reorder_qty'          => 50,
                            'lead_time_days'       => 5,
                            'is_active'            => true,
                            'is_ecommerce_active'  => false,
                            'product_type'         => 'physical',
                            'weight_in_grams'      => 1000,
                        ]);

                        Stock::create([
                            'branch_id'         => $branchId,
                            'product_id'        => $product->id,
                            'cost_price'        => $costPrice,
                            'cost_price_tax'    => $costTax,
                            'selling_price'     => $sellingPrice,
                            'harga_jual_1'      => $sellingPrice,
                            'qty_min_gol_1'     => 1,
                            'margin_gol_1'      => $margin,
                            'harga_jual_2'      => 0,
                            'qty_min_gol_2'     => 0,
                            'margin_gol_2'      => 0,
                            'harga_jual_3'      => 0,
                            'qty_min_gol_3'     => 0,
                            'margin_gol_3'      => 0,
                            'quantity_on_hand'  => 0,
                            'quantity_reserved' => 0,
                            'is_active'         => true,
                        ]);

                        // Pastikan cabang lain juga memiliki record stock dengan 0 QOH
                        $otherBranches = Branch::where('id', '!=', $branchId)->get();
                        foreach ($otherBranches as $ob) {
                            Stock::firstOrCreate(
                                ['branch_id' => $ob->id, 'product_id' => $product->id],
                                [
                                    'cost_price'        => $costPrice,
                                    'cost_price_tax'    => $costTax,
                                    'selling_price'     => $sellingPrice,
                                    'harga_jual_1'      => $sellingPrice,
                                    'qty_min_gol_1'     => 1,
                                    'margin_gol_1'      => $margin,
                                    'quantity_on_hand'  => 0,
                                    'quantity_reserved' => 0,
                                    'is_active'         => true,
                                ]
                            );
                        }

                        $createdCount++;
                    }

                    DB::commit();

                    Notification::make()
                        ->title('Batch Berhasil Disimpan')
                        ->body("Sebanyak {$createdCount} master baru berhasil dibuat, dan {$linkedCount} produk berhasil ditautkan ke cabang.")
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    DB::rollBack();
                    Notification::make()
                        ->title('Gagal Menyimpan Produk')
                        ->body('Terjadi kesalahan: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function findBestMatch(string $inputName, $products): ?array
    {
        $normInput = static::normalizeProductName($inputName);
        
        $inputWords = array_values(array_filter(
            explode(' ', $normInput), 
            fn($w) => strlen($w) > 1 && !in_array($w, ['KRAOS', 'SUPPLIER'])
        ));
        
        $bestMatch = null;
        $highestScore = 0;

        foreach ($products as $p) {
            $normProd = static::normalizeProductName($p->name);
            
            // 1. Exact normalized match
            if ($normProd === $normInput) {
                return [
                    'product' => $p,
                    'score'   => 100,
                    'type'    => 'EXACT',
                ];
            }

            $prodWords = array_values(array_filter(
                explode(' ', $normProd), 
                fn($w) => strlen($w) > 1 && !in_array($w, ['KRAOS', 'SUPPLIER'])
            ));
            
            $matchedWords = array_intersect($inputWords, $prodWords);
            $matchCount = count($matchedWords);
            
            if (count($inputWords) > 0) {
                $inputRatio = $matchCount / count($inputWords);
                $prodRatio = $matchCount / max(1, count($prodWords));
                
                // 2. All content words of query exist in target (e.g. RAMBO in RAMBO KUMBANG PDS, or KRP SEBLAK in KRP PDS SEBLAK)
                if ($inputRatio == 1.0) {
                    similar_text($normInput, $normProd, $sim);
                    $score = 80 + ($prodRatio * 15) + ($sim * 0.05);
                    if ($score > $highestScore) {
                        $highestScore = $score;
                        $bestMatch = [
                            'product' => $p,
                            'score'   => round($score, 1),
                            'type'    => 'SUBSET',
                        ];
                    }
                } else {
                    // 3. High fuzzy similarity
                    similar_text($normInput, $normProd, $sim);
                    if ($sim >= 85 && $sim > $highestScore) {
                        $highestScore = $sim;
                        $bestMatch = [
                            'product' => $p,
                            'score'   => round($sim, 1),
                            'type'    => 'FUZZY',
                        ];
                    }
                }
            }
        }

        return $highestScore >= 75 ? $bestMatch : null;
    }

    public static function normalizeProductName(string $name): string
    {
        $name = strtoupper(trim($name));
        $name = preg_replace('/[\-_\/.,]/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $replacements = [
            'CHIKI'   => 'CIKI',
            'SETIK'   => 'STIK',
            'BANTAT'  => 'BANTAL',
            'KERUPUK' => 'KRP',
            'KRPK'    => 'KRP',
            'PEDAS'   => 'PDS',
        ];
        $words = explode(' ', $name);
        $res = [];
        foreach ($words as $w) {
            $res[] = $replacements[$w] ?? $w;
        }
        return implode(' ', $res);
    }

    protected static function cleanNumber($val): float
    {
        if (is_null($val) || $val === '') return 0.0;
        if (is_numeric($val)) return (float) $val;
        $str = (string) $val;
        $str = preg_replace('/[^0-9\.,-]/', '', $str);
        if (str_contains($str, ',') && str_contains($str, '.')) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        } elseif (str_contains($str, ',')) {
            $str = str_replace(',', '.', $str);
        }
        return (float) $str;
    }

    protected static function findCategoryId(string $nameOrId): ?string
    {
        $cat = Category::where('id', $nameOrId)
            ->orWhere('name', 'like', '%' . $nameOrId . '%')
            ->orWhere('code', $nameOrId)
            ->first();
        return $cat ? $cat->id : null;
    }
}
