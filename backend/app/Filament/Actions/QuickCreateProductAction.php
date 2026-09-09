<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
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
            ->modalDescription('Fitur penambahan produk massal dengan pilihan mode Tabel dan Copy-Paste Teks. Kode SKU & Barcode akan dibuatkan otomatis berurutan.')
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
                    ->description('Pengaturan utama untuk kelompok barang yang akan ditambahkan (Pemasok, Cabang, Kategori & Tax Default)')
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
                                ->helperText('Stok awal barang akan otomatis di-assign ke cabang ini.'),
                            Select::make('supplier_id')
                                ->label('Pemasok (Supplier)')
                                ->options(Supplier::where('is_active', true)->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->preload(),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('default_category_id')
                                ->label('Kategori Default')
                                ->options(Category::where('is_active', true)->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->preload(),
                            Select::make('default_sub_category')
                                ->label('Sub Kategori Default')
                                ->options(function () {
                                    return Product::whereNotNull('sub_category')
                                        ->where('sub_category', '!=', '')
                                        ->distinct()
                                        ->pluck('sub_category', 'sub_category')
                                        ->toArray();
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

                Section::make('2. Import dari Copy-Paste Teks (Opsional)')
                    ->description('Tempelkan teks daftar barang dari chat/catatan, lalu klik "Proses Teks" untuk memasukkannya ke tabel di bawah.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Textarea::make('paste_text')
                            ->label('Teks Daftar Barang')
                            ->rows(6)
                            ->placeholder("Contoh Format 1 (3 Kolom):\nUI. CAPIT KUKU BESAR | 6000 | 7500\nUI. CAPIT KUKU KECIL | 2000 | 2500\n\nContoh Format 2 (5 Kolom Beda Kategori):\nUI. CAPIT KUKU BESAR | 6000 | 7500 | KONSINYASI | KONSINYASI @BLN\nUI. DOMPET CANTIK | 15000 | 20000 | AKSESORIS | FASHION"),
                        
                        Actions::make([
                            Action::make('parse_text_action')
                                ->label('Proses Teks ke Tabel')
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

                                    $lines = explode("\n", str_replace("\r", "", $text));
                                    $existingItems = $get('items') ?? [];
                                    $parsedCount = 0;

                                    $currentName = null;
                                    $currentM = null;
                                    $currentJual = null;

                                    foreach ($lines as $line) {
                                        $trimmed = trim($line);
                                        if (empty($trimmed)) continue;
                                        if (str_starts_with($trimmed, '*') || str_starts_with($trimmed, '#') || str_starts_with(strtoupper($trimmed), 'SUPPLIER:')) {
                                            continue;
                                        }

                                        // Check pipe delimited format
                                        if (str_contains($trimmed, '|')) {
                                            $parts = array_map('trim', explode('|', $trimmed));
                                            if (count($parts) >= 3) {
                                                $name = $parts[0];
                                                $m = static::cleanNumber($parts[1]);
                                                $jual = static::cleanNumber($parts[2]);
                                                $catId = isset($parts[3]) && !empty($parts[3]) ? static::findCategoryId($parts[3]) : null;
                                                $subCat = isset($parts[4]) && !empty($parts[4]) ? $parts[4] : null;

                                                $existingItems[] = [
                                                    'name' => $name,
                                                    'cost_price' => $m,
                                                    'selling_price' => $jual,
                                                    'category_id' => $catId,
                                                    'sub_category' => $subCat,
                                                ];
                                                $parsedCount++;
                                            }
                                            continue;
                                        }

                                        // Check multi-line M : 6000 / JUAL : 7500 format
                                        if (preg_match('/^M\s*:\s*(.+)$/i', $trimmed, $matches)) {
                                            $currentM = static::cleanNumber($matches[1]);
                                        } elseif (preg_match('/^JUAL\s*:\s*(.+)$/i', $trimmed, $matches)) {
                                            $currentJual = static::cleanNumber($matches[1]);
                                            if (!empty($currentName) && $currentM !== null && $currentJual !== null) {
                                                $existingItems[] = [
                                                    'name' => $currentName,
                                                    'cost_price' => $currentM,
                                                    'selling_price' => $currentJual,
                                                    'category_id' => null,
                                                    'sub_category' => null,
                                                ];
                                                $parsedCount++;
                                                $currentName = null;
                                                $currentM = null;
                                                $currentJual = null;
                                            }
                                        } else {
                                            $currentName = $trimmed;
                                        }
                                    }

                                    $set('items', $existingItems);
                                    Notification::make()
                                        ->title('Teks Berhasil Diproses')
                                        ->body("{$parsedCount} produk berhasil ditambahkan ke tabel isian di bawah.")
                                        ->success()
                                        ->send();
                                }),
                        ]),
                    ]),

                Section::make('3. Tabel Daftar Produk Massal')
                    ->description('Isi atau edit daftar barang yang akan dibuat. Kategori & Sub Kategori opsional terisi otomatis dari Header jika dikosongkan.')
                    ->schema([
                        Repeater::make('items')
                            ->label('Item Produk')
                            ->schema([
                                Grid::make(5)->schema([
                                    TextInput::make('name')
                                        ->label('Nama Produk')
                                        ->required()
                                        ->columnSpan(2),
                                    TextInput::make('cost_price')
                                        ->label('Harga Beli (M)')
                                        ->numeric()
                                        ->required()
                                        ->default(0),
                                    TextInput::make('selling_price')
                                        ->label('Harga Jual')
                                        ->numeric()
                                        ->required()
                                        ->default(0),
                                    Select::make('category_id')
                                        ->label('Kategori (Beda)')
                                        ->placeholder('Ikuti Header')
                                        ->options(Category::where('is_active', true)->pluck('name', 'id'))
                                        ->searchable(),
                                ]),
                                Grid::make(2)->schema([
                                    TextInput::make('sub_category')
                                        ->label('Sub Kategori (Beda)')
                                        ->placeholder('Ikuti Header'),
                                ]),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Baris Barang')
                            ->reorderable(false)
                            ->columns(1)
                            ->required(),
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
                $lastSkuNum = 0;
                if ($lastSkuRecord) {
                    $lastSkuNum = (int) substr($lastSkuRecord->sku, -4);
                }

                $lastBarcodeRecord = Product::where('barcode', 'like', $prefixBarcode . '%')
                    ->orderBy('barcode', 'desc')
                    ->first();
                $lastBarcodeNum = 0;
                if ($lastBarcodeRecord) {
                    $lastBarcodeNum = (int) substr($lastBarcodeRecord->barcode, -4);
                }

                $maxCounter = max($lastSkuNum, $lastBarcodeNum);

                $createdCount = 0;
                $errors = [];

                DB::beginTransaction();
                try {
                    foreach ($items as $item) {
                        $name = trim($item['name'] ?? '');
                        if (empty($name)) continue;

                        $costPrice = static::cleanNumber($item['cost_price'] ?? 0);
                        $sellingPrice = static::cleanNumber($item['selling_price'] ?? 0);
                        $catId = !empty($item['category_id']) ? $item['category_id'] : $defaultCatId;
                        $subCat = !empty($item['sub_category']) ? $item['sub_category'] : $defaultSubCat;

                        $maxCounter++;
                        $seqStr = str_pad($maxCounter, 4, '0', STR_PAD_LEFT);
                        $sku = $prefixSku . $seqStr;
                        $barcode = $prefixBarcode . $seqStr;

                        $costTax = $isTaxable ? round($costPrice * 1.11, 2) : $costPrice;
                        $margin = ($costTax > 0 && $sellingPrice > 0) 
                            ? round((($sellingPrice - $costTax) / $costTax) * 100, 2) 
                            : 0;

                        $product = Product::create([
                            'organization_id'      => $orgId,
                            'sku'                  => $sku,
                            'barcode'              => $barcode,
                            'name'                 => $name,
                            'category_id'          => $catId,
                            'sub_category'          => $subCat,
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

                        $createdCount++;
                    }

                    DB::commit();

                    Notification::make()
                        ->title('Berhasil Menambahkan Produk')
                        ->body("Sebanyak {$createdCount} produk baru berhasil dibuat dan didaftarkan ke cabang.")
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
