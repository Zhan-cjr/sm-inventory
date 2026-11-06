<?php

namespace App\Filament\Resources\ListingProduks\Schemas;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierDivision;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ListingProdukForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Perjanjian Listing Produk')
                    ->description('Data kesepakatan kerjasama listing produk baru dari supplier')
                    ->columns(3)
                    ->schema([
                        Hidden::make('organization_id')
                            ->default(fn() => Auth::user()?->organization_id ?? Organization::first()?->id),

                        TextInput::make('listing_number')
                            ->label('Nomor Listing / Referensi')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'LST-' . date('ymd') . '-' . rand(100, 999))
                            ->helperText('Nomor unik perjanjian listing (otomatis)'),

                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name', fn ($query) => $query->where('is_active', true))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('supplier_division_id', null)),

                        Select::make('supplier_division_id')
                            ->label('Sub Divisi Supplier (Opsional)')
                            ->placeholder('Tanpa Sub Divisi')
                            ->options(function (callable $get) {
                                $supplierId = $get('supplier_id');
                                if (!$supplierId) return [];
                                return SupplierDivision::where('supplier_id', $supplierId)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->disabled(fn (callable $get) => blank($get('supplier_id'))),

                        Placeholder::make('supplier_type_info')
                            ->label('Tipe Kerjasama Supplier')
                            ->content(function (callable $get) {
                                $supplierId = $get('supplier_id');
                                if (!$supplierId) return 'Pilih supplier terlebih dahulu';
                                $supplier = Supplier::find($supplierId);
                                if (!$supplier) return '-';
                                return $supplier->is_consignment 
                                    ? '📦 KONSINYASI (Titip Jual - Bayar saat laku terjual)' 
                                    : '🛒 BELI PUTUS (Pembelian reguler via PO)';
                            }),

                        TextInput::make('listing_fee')
                            ->label('Total Biaya Listing (Listing Fee)')
                            ->rupiah()
                            ->default(0)
                            ->helperText('Isi Rp 0 jika konsinyasi tanpa biaya listing. 1 nominal ini mencakup seluruh produk dalam perjanjian ini.')
                            ->live(),

                        Toggle::make('create_deduction_claim')
                            ->label('Otomatis Potong Tagihan Kontrabon Supplier')
                            ->helperText('Jika aktif, total listing fee otomatis dicatat sebagai potongan terbuka pada pembayaran kontrabon supplier ini.')
                            ->default(true)
                            ->visible(fn (callable $get) => ((float) str_replace(',', '.', str_replace('.', '', (string)$get('listing_fee')))) > 0)
                            ->dehydrated(false),
                    ]),

                Section::make('Masa Uji Coba & Kuota Cabang Pilot')
                    ->description('Tentukan masa uji coba dan pilih cabang-cabang yang berhak memesan/menjual selama periode awal')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('trial_start_date')
                            ->label('Tanggal Mulai Uji Coba')
                            ->default(now())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $set('trial_end_date', \Carbon\Carbon::parse($state)->addMonths(3)->format('Y-m-d'));
                                }
                            }),

                        DatePicker::make('trial_end_date')
                            ->label('Tanggal Selesai Uji Coba (Evaluasi)')
                            ->default(now()->addMonths(3))
                            ->required()
                            ->helperText('Default masa uji coba adalah 3 bulan (90 hari).'),

                        CheckboxList::make('allowed_branch_ids')
                            ->label('Cabang Pilot yang Diizinkan Order (Kuota Uji Coba)')
                            ->options(fn () => Branch::where('is_active', true)->pluck('name', 'id'))
                            ->columns(3)
                            ->columnSpanFull()
                            ->required()
                            ->helperText('Centang cabang-cabang yang boleh order & pajang barang ini. Berlaku untuk semua produk di perjanjian ini.'),

                        Textarea::make('notes')
                            ->label('Catatan Kesepakatan / Target Penjualan')
                            ->placeholder('Contoh: Target penjualan minimal 2 pcs/toko/hari. Jika dalam 3 bulan tidak memenuhi target, sisa stok diretur 100% ke supplier.')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),

                Section::make('Daftar Produk yang Diajukan')
                    ->description('Daftarkan satu atau beberapa produk yang masuk dalam perjanjian listing ini. Kategori massal akan otomatis digunakan jika baris produk dikosongkan.')
                    ->columnSpanFull()
                    ->schema([
                        // 1. Header Pemilihan Kategori & Sub Kategori Massal
                        Grid::make(3)
                            ->schema([
                                Select::make('default_category_id')
                                    ->label('Kategori Default (Massal)')
                                    ->placeholder('Pilih Kategori untuk Semua Item...')
                                    ->options(Category::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn (callable $set) => $set('default_sub_category', null))
                                    ->dehydrated(false)
                                    ->default(function ($record) {
                                        return $record?->products?->first()?->category_id ?? Category::where('name', 'FOOD')->first()?->id;
                                    })
                                    ->helperText('Otomatis diterapkan ke seluruh baris di bawah.'),

                                Select::make('default_sub_category')
                                    ->label('Sub Kategori Default (Massal)')
                                    ->placeholder('Pilih / Ketik Sub Kategori...')
                                    ->options(function (callable $get) {
                                        $catId = $get('default_category_id');
                                        if (blank($catId)) return [];
                                        return Product::where('category_id', $catId)
                                            ->whereNotNull('sub_category')
                                            ->where('sub_category', '!=', '')
                                            ->distinct()
                                            ->orderBy('sub_category')
                                            ->pluck('sub_category', 'sub_category')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->createOptionForm([
                                        TextInput::make('sub_category')->label('Sub Kategori Baru')->required(),
                                    ])
                                    ->createOptionUsing(fn (array $data) => $data['sub_category'])
                                    ->live()
                                    ->dehydrated(false)
                                    ->default(function ($record) {
                                        return $record?->products?->first()?->sub_category ?? 'MAKANAN KALENG & OLAHAN';
                                    })
                                    ->helperText('Otomatis diterapkan ke seluruh baris di bawah.'),

                                Placeholder::make('info_kategori_massal')
                                    ->label('Panduan Pengisian')
                                    ->content(new \Illuminate\Support\HtmlString(
                                        '<div style="font-size: 0.8125rem; color: #64748b; line-height: 1.45; padding-top: 0.2rem;">' .
                                        '<span style="display:inline-block; margin-bottom: 3px;">✅ <strong>Kategori Massal:</strong> Mengisi otomatis kategori semua barang.</span><br>' .
                                        '<span>💡 <strong>Kategori Berbeda:</strong> Cukup pilih di kolom <em>Kategori (Beda)</em> pada baris barang terkait jika ada item yang berbeda.</span>' .
                                        '</div>'
                                    )),
                            ]),

                        // 2. Custom CSS Injection untuk Dark Navy Header & Premium Table Grid
                        Placeholder::make('listing_repeater_custom_css')
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString('
                                <style>
                                    .listing-products-table-repeater table {
                                        width: 100% !important;
                                        border-collapse: separate !important;
                                        border-spacing: 0 !important;
                                        border: 1px solid #cbd5e1 !important;
                                        border-radius: 0.65rem !important;
                                        overflow: hidden !important;
                                        background: #ffffff !important;
                                        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05) !important;
                                    }
                                    .dark .listing-products-table-repeater table {
                                        border-color: #334155 !important;
                                        background: #0f172a !important;
                                    }
                                    .listing-products-table-repeater table thead tr {
                                        background: #1e293b !important;
                                        color: #ffffff !important;
                                        border-bottom: 2px solid #0f172a !important;
                                    }
                                    .listing-products-table-repeater table thead th {
                                        background: #1e293b !important;
                                        color: #f8fafc !important;
                                        font-size: 0.72rem !important;
                                        font-weight: 700 !important;
                                        text-transform: uppercase !important;
                                        letter-spacing: 0.05em !important;
                                        padding: 0.8rem 0.65rem !important;
                                        border: none !important;
                                        border-right: 1px solid #334155 !important;
                                        vertical-align: middle !important;
                                    }
                                    .listing-products-table-repeater table thead th:last-child {
                                        border-right: none !important;
                                    }
                                    .listing-products-table-repeater table thead th sup {
                                        color: #f87171 !important;
                                        font-weight: bold !important;
                                    }
                                    .listing-products-table-repeater table tbody tr {
                                        border-bottom: 1px solid #f1f5f9 !important;
                                        transition: background-color 0.15s ease !important;
                                    }
                                    .dark .listing-products-table-repeater table tbody tr {
                                        border-bottom-color: #1e293b !important;
                                    }
                                    .listing-products-table-repeater table tbody tr:nth-child(even) {
                                        background: #f8fafc !important;
                                    }
                                    .dark .listing-products-table-repeater table tbody tr:nth-child(even) {
                                        background: #131d31 !important;
                                    }
                                    .listing-products-table-repeater table tbody tr:hover {
                                        background: #f1f5f9 !important;
                                    }
                                    .dark .listing-products-table-repeater table tbody tr:hover {
                                        background: #1e293b !important;
                                    }
                                    .listing-products-table-repeater table tbody td {
                                        padding: 0.55rem 0.6rem !important;
                                        vertical-align: middle !important;
                                        border-right: 1px solid #f8fafc !important;
                                    }
                                    .dark .listing-products-table-repeater table tbody td {
                                        border-right-color: #1e293b !important;
                                    }
                                    .listing-products-table-repeater .fi-input-wrp {
                                        border-radius: 0.45rem !important;
                                        box-shadow: none !important;
                                    }
                                    .listing-products-table-repeater input,
                                    .listing-products-table-repeater select {
                                        font-size: 0.8125rem !important;
                                    }
                                    .listing-products-table-repeater .fi-fo-table-repeater-add {
                                        margin-top: 0.75rem !important;
                                    }
                                    .listing-products-table-repeater .fi-fo-table-repeater-add button {
                                        background: #4f46e5 !important;
                                        color: #ffffff !important;
                                        font-weight: 700 !important;
                                        border-radius: 0.5rem !important;
                                        padding: 0.45rem 1rem !important;
                                        box-shadow: 0 1px 3px rgba(79, 70, 229, 0.3) !important;
                                    }
                                    .listing-products-table-repeater .fi-fo-table-repeater-add button:hover {
                                        background: #4338ca !important;
                                    }
                                </style>
                            ')),

                        // 3. Tabel Repeater Produk
                        Repeater::make('products')
                            ->relationship('products')
                            ->extraAttributes(['class' => 'listing-products-table-repeater'])
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, \Filament\Schemas\Components\Utilities\Get $get): array {
                                $orgId = $data['organization_id'] ?? $get('organization_id') ?? Auth::user()?->organization_id ?? Organization::first()?->id;
                                $data['organization_id'] = $orgId;
                                $data['supplier_id'] = $get('supplier_id');
                                $data['supplier_division_id'] = $get('supplier_division_id');
                                $data['listing_status'] = 'TRIAL';
                                $data['trial_start_date'] = $get('trial_start_date');
                                $data['trial_end_date'] = $get('trial_end_date');
                                $data['allowed_branch_ids'] = $get('allowed_branch_ids') ?? [];

                                // Fallback Kategori & Sub Kategori dari Header Massal
                                $defaultCat = $get('default_category_id') ?? $get('../../default_category_id');
                                $defaultSubCat = $get('default_sub_category') ?? $get('../../default_sub_category');

                                if (empty($data['category_id']) && !empty($defaultCat)) {
                                    $data['category_id'] = $defaultCat;
                                }
                                if (empty(trim($data['sub_category'] ?? '')) && !empty($defaultSubCat)) {
                                    $data['sub_category'] = $defaultSubCat;
                                }

                                // Pencegahan Tabrakan SKU Otomatis
                                $prefixSku = 'SKU-' . date('dmy');
                                $sku = trim($data['sku'] ?? '');

                                if (empty($sku) || Product::where('sku', $sku)->where('organization_id', $orgId)->exists()) {
                                    $lastSkuRecord = Product::where('sku', 'like', $prefixSku . '%')
                                        ->where('organization_id', $orgId)
                                        ->orderBy('sku', 'desc')
                                        ->first();
                                    $lastNum = $lastSkuRecord ? (int) substr($lastSkuRecord->sku, -4) : 0;

                                    do {
                                        $lastNum++;
                                        $candidateSku = $prefixSku . str_pad($lastNum, 4, '0', STR_PAD_LEFT);
                                    } while (Product::where('sku', $candidateSku)->where('organization_id', $orgId)->exists());

                                    $data['sku'] = $candidateSku;
                                }

                                // Pencegahan Barcode Kosong
                                if (empty(trim($data['barcode'] ?? ''))) {
                                    $prefixBarcode = date('dmy');
                                    $lastBarcodeRecord = Product::where('barcode', 'like', $prefixBarcode . '%')
                                        ->orderBy('barcode', 'desc')
                                        ->first();
                                    $lastBNum = $lastBarcodeRecord ? (int) substr($lastBarcodeRecord->barcode, -4) : 0;

                                    do {
                                        $lastBNum++;
                                        $candidateBarcode = $prefixBarcode . str_pad($lastBNum, 4, '0', STR_PAD_LEFT);
                                    } while (Product::where('barcode', $candidateBarcode)->exists());

                                    $data['barcode'] = $candidateBarcode;
                                }

                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, \Filament\Schemas\Components\Utilities\Get $get): array {
                                $orgId = $data['organization_id'] ?? $get('organization_id') ?? Auth::user()?->organization_id ?? Organization::first()?->id;
                                $data['organization_id'] = $orgId;
                                $data['supplier_id'] = $get('supplier_id');
                                $data['supplier_division_id'] = $get('supplier_division_id');
                                $data['trial_start_date'] = $get('trial_start_date');
                                $data['trial_end_date'] = $get('trial_end_date');
                                $data['allowed_branch_ids'] = $get('allowed_branch_ids') ?? [];

                                // Fallback Kategori & Sub Kategori dari Header Massal
                                $defaultCat = $get('default_category_id') ?? $get('../../default_category_id');
                                $defaultSubCat = $get('default_sub_category') ?? $get('../../default_sub_category');

                                if (empty($data['category_id']) && !empty($defaultCat)) {
                                    $data['category_id'] = $defaultCat;
                                }
                                if (empty(trim($data['sub_category'] ?? '')) && !empty($defaultSubCat)) {
                                    $data['sub_category'] = $defaultSubCat;
                                }

                                $sku = trim($data['sku'] ?? '');
                                if (!empty($sku) && !empty($data['id'])) {
                                    $conflict = Product::where('sku', $sku)
                                        ->where('organization_id', $orgId)
                                        ->where('id', '!=', $data['id'])
                                        ->exists();

                                    if ($conflict) {
                                        $prefixSku = 'SKU-' . date('dmy');
                                        $lastSkuRecord = Product::where('sku', 'like', $prefixSku . '%')
                                            ->where('organization_id', $orgId)
                                            ->orderBy('sku', 'desc')
                                            ->first();
                                        $lastNum = $lastSkuRecord ? (int) substr($lastSkuRecord->sku, -4) : 0;

                                        do {
                                            $lastNum++;
                                            $candidateSku = $prefixSku . str_pad($lastNum, 4, '0', STR_PAD_LEFT);
                                        } while (Product::where('sku', $candidateSku)->where('organization_id', $orgId)->exists());

                                        $data['sku'] = $candidateSku;
                                    }
                                }

                                return $data;
                            })
                            ->label('Item Produk')
                            ->addActionLabel('+ Tambah Baris Produk')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->compact()
                            ->table([
                                TableColumn::make('Produk & Identitas')->width('320px')->markAsRequired(),
                                TableColumn::make('Kategori (Beda)')->width('210px'),
                                TableColumn::make('Satuan')->width('75px')->markAsRequired(),
                                TableColumn::make('Modal (DPP)')->width('125px')->markAsRequired(),
                                TableColumn::make('HPP + PPN (11%)')->width('125px'),
                                TableColumn::make('Harga Jual')->width('125px')->markAsRequired(),
                            ])
                            ->schema([
                                // Kolom 1: Produk & Identitas (Nama Produk + Barcode/SKU)
                                Group::make([
                                    TextInput::make('name')
                                        ->label('Nama Produk')
                                        ->hiddenLabel()
                                        ->required()
                                        ->placeholder('Nama produk lengkap...'),
                                    Grid::make(2)->schema([
                                        TextInput::make('barcode')
                                            ->label('Barcode')
                                            ->hiddenLabel()
                                            ->nullable()
                                            ->placeholder('Barcode / Scan')
                                            ->extraAttributes(['style' => 'font-family: monospace; font-size: 0.75rem;']),
                                        TextInput::make('sku')
                                            ->label('SKU')
                                            ->hiddenLabel()
                                            ->nullable()
                                            ->placeholder('SKU: Otomatis')
                                            ->extraAttributes(['style' => 'font-family: monospace; font-size: 0.75rem; color: #64748b;']),
                                    ]),
                                ]),

                                // Kolom 2: Kategori & Sub Kategori Opsional (Jika Berbeda dari Header)
                                Group::make([
                                    Select::make('category_id')
                                        ->label('Kategori')
                                        ->hiddenLabel()
                                        ->placeholder('Ikuti Kategori Massal')
                                        ->options(Category::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->live()
                                        ->afterStateUpdated(fn (callable $set) => $set('sub_category', null)),

                                    TextInput::make('sub_category')
                                        ->label('Sub Kategori')
                                        ->hiddenLabel()
                                        ->nullable()
                                        ->placeholder('Ikuti Sub Kategori Massal'),
                                ]),

                                // Kolom 3: Satuan
                                TextInput::make('unit_of_measure')
                                    ->label('Satuan')
                                    ->hiddenLabel()
                                    ->required()
                                    ->default('pcs')
                                    ->placeholder('pcs'),

                                // Kolom 4: Modal DPP (HPP Pokok)
                                TextInput::make('cost_price')
                                    ->label('Harga Modal (DPP)')
                                    ->hiddenLabel()
                                    ->required()
                                    ->rupiah()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                        $cost = (float) str_replace(',', '.', str_replace('.', '', (string)$state));
                                        $isTaxable = (bool) ($get('is_taxable') ?? true);
                                        $taxRate = \App\Services\RetailIntelligenceService::getActiveTaxRate();
                                        $taxed_cost = $isTaxable ? round($cost * (1 + ($taxRate / 100)), 2) : $cost;
                                        $set('cost_price_tax', str_replace('.', ',', (string)$taxed_cost));

                                        $currentJual = (float) str_replace(',', '.', str_replace('.', '', (string) $get('harga_jual_1')));
                                        if ($currentJual <= 0 && $taxed_cost > 0) {
                                            $autoJual = ceil(($taxed_cost * 1.10) / 100) * 100;
                                            $set('harga_jual_1', $autoJual);
                                            $set('selling_price', $autoJual);
                                            $new_margin = round((($autoJual - $taxed_cost) / $taxed_cost) * 100, 2);
                                            $set('margin_gol_1', $new_margin);
                                        } elseif ($taxed_cost > 0 && $currentJual > 0) {
                                            $new_margin = round((($currentJual - $taxed_cost) / $taxed_cost) * 100, 2);
                                            $set('margin_gol_1', $new_margin);
                                        }
                                    }),

                                // Kolom 5: HPP + PPN (11%)
                                TextInput::make('cost_price_tax')
                                    ->label('Harga Beli + PPN')
                                    ->hiddenLabel()
                                    ->rupiah()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                        $taxed_cost = (float) str_replace(',', '.', str_replace('.', '', (string)$state));
                                        if ($taxed_cost > 0) {
                                            $dpp = round($taxed_cost / 1.11, 2);
                                            $set('cost_price', str_replace('.', ',', (string)$dpp));

                                            $currentJual = (float) str_replace(',', '.', str_replace('.', '', (string) $get('harga_jual_1')));
                                            if ($currentJual <= 0) {
                                                $autoJual = ceil(($taxed_cost * 1.10) / 100) * 100;
                                                $set('harga_jual_1', $autoJual);
                                                $set('selling_price', $autoJual);
                                                $new_margin = round((($autoJual - $taxed_cost) / $taxed_cost) * 100, 2);
                                                $set('margin_gol_1', $new_margin);
                                            } else {
                                                $new_margin = round((($currentJual - $taxed_cost) / $taxed_cost) * 100, 2);
                                                $set('margin_gol_1', $new_margin);
                                            }
                                        }
                                    }),

                                // Kolom 6: Harga Jual (Eceran)
                                TextInput::make('harga_jual_1')
                                    ->label('Harga Jual')
                                    ->hiddenLabel()
                                    ->rupiah()
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                        $hpp = (float) str_replace(',', '.', str_replace('.', '', (string)$get('cost_price_tax')));
                                        $harga = (float) str_replace(',', '.', str_replace('.', '', (string)$state));
                                        if ($hpp > 0) {
                                            $new_margin = $harga > 0 ? round((($harga - $hpp) / $hpp) * 100, 2) : 0;
                                            $set('margin_gol_1', $new_margin);
                                        }
                                        $set('selling_price', $harga);
                                    }),

                                Hidden::make('margin_gol_1')->default(0),
                                Hidden::make('qty_min_gol_1')->default(1),
                                Hidden::make('selling_price')->default(0),
                                Hidden::make('is_taxable')->default(true),
                                Hidden::make('is_active')->default(true),
                                Hidden::make('organization_id')->default(fn () => Auth::user()?->organization_id ?? Organization::first()?->id),
                            ]),
                    ]),
            ]);
    }
}
