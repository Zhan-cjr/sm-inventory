<?php

namespace App\Filament\Resources\ListingProduks\Schemas;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierDivision;
use App\Services\ProductListingParserService;
use App\Services\RetailIntelligenceService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

                        FileUpload::make('document_path')
                            ->label('Berkas / Foto Formulir Listing (Bukti Dokumen Fisik)')
                            ->disk('public')
                            ->directory('listing-documents')
                            ->visibility('public')
                            ->acceptedFileTypes([
                                'image/jpeg', 'image/png', 'image/webp', 'image/jpg',
                                'application/pdf',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                            ])
                            ->openable()
                            ->downloadable()
                            ->previewable()
                            ->columnSpanFull()
                            ->helperText('File bukti fisik formulir pengajuan/registrasi dari supplier. Tersimpan permanen untuk arsip & kroscek di kemudian hari.'),
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
                    ->headerActions([
                        Action::make('upload_document')
                            ->label('Upload Dokumen Listing')
                            ->icon('heroicon-o-arrow-up-tray')
                            ->color('primary')
                            ->modalHeading('Upload Dokumen Registrasi Listing Produk')
                            ->modalDescription('Unggah formulir registrasi produk atau pengajuan supplier (Foto/Scan JPG, PNG, PDF, atau File Excel XLSX/CSV). Sistem otomatis mendeteksi barcode, nama barang, harga satuan (DPP), menghitung harga jual dengan persentase margin (default 10%), dan membuat SKU baru.')
                            ->modalSubmitActionLabel('Proses & Masukkan ke Tabel')
                            ->modalWidth('xl')
                            ->form([
                                FileUpload::make('document_file')
                                    ->label('File Formulir / Dokumen Listing')
                                    ->acceptedFileTypes([
                                        'image/jpeg', 'image/png', 'image/webp', 'image/jpg',
                                        'application/pdf',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'application/vnd.ms-excel',
                                        'text/csv',
                                    ])
                                    ->disk('public')
                                    ->directory('listing-documents')
                                    ->visibility('public')
                                    ->required()
                                    ->helperText('Format: Foto Formulir (JPG/PNG), Scan PDF, atau Spreadsheet Excel/CSV. File otomatis tersimpan permanen sebagai bukti dokumen.'),

                                TextInput::make('default_margin')
                                    ->label('Persentase Margin Default (%)')
                                    ->numeric()
                                    ->default(10)
                                    ->suffix('%')
                                    ->required()
                                    ->helperText('Harga jual otomatis dihitung: Modal DPP + PPN 11% + Margin ini. Default: 10%. Masih bisa diedit di tabel.'),

                                Toggle::make('append_mode')
                                    ->label('Tambahkan ke baris produk yang sudah ada (tidak menimpa)')
                                    ->default(false),
                            ])
                            ->action(function (array $data, callable $set, callable $get) {
                                $relativePath = $data['document_file'];
                                $filePath = Storage::disk('public')->path($relativePath);
                                $margin = (float) ($data['default_margin'] ?? 10);
                                $orgId = $get('organization_id') ?? Auth::user()?->organization_id ?? Organization::first()?->id;

                                $parser = app(ProductListingParserService::class);
                                try {
                                    $result = $parser->parseDocument($filePath, $margin, $orgId);
                                } catch (\Throwable $e) {
                                    Notification::make()
                                        ->title('Gagal Membaca Dokumen')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                if (empty($result['items'])) {
                                    Notification::make()
                                        ->title('Tidak Ada Produk Ditemukan')
                                        ->body('Sistem tidak menemukan baris tabel produk pada dokumen yang diunggah.')
                                        ->warning()
                                        ->send();
                                    return;
                                }

                                // Simpan file sebagai berkas lampiran resmi di form
                                $set('document_path', $relativePath);

                                // Yang dipakai difokuskan pada daftar barangnya saja
                                $newItems = $result['items'];
                                if (!empty($data['append_mode'])) {
                                    $current = $get('products') ?? [];
                                    $combined = array_merge($current, $newItems);
                                    $set('products', $combined);
                                } else {
                                    $set('products', $newItems);
                                }

                                $summary = $result['summary'];
                                Notification::make()
                                    ->title('Dokumen Listing Berhasil Diproses & Disimpan')
                                    ->body("Total {$summary['total_items']} produk dimuat. Berkas formulir telah disimpan dan dilampirkan untuk kroscek di masa mendatang.")
                                    ->success()
                                    ->duration(8000)
                                    ->send();
                            }),
                    ])
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
                                    ->placeholder('Pilih / Cari Sub Kategori...')
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
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('sub_category')->label('Sub Kategori Baru')->required(),
                                    ])
                                    ->createOptionUsing(fn (array $data) => strtoupper(trim($data['sub_category'])))
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

                                // Pencegahan Tabrakan / Penanganan Barcode Sudah Ada
                                $barcode = trim($data['barcode'] ?? '');
                                if (!empty($barcode)) {
                                    $existing = Product::where('organization_id', $orgId)
                                        ->where(function ($q) use ($barcode) {
                                            $q->where('barcode', $barcode)
                                              ->orWhere('sku', $barcode)
                                              ->orWhereJsonContains('metadata->additional_barcodes', $barcode);
                                        })
                                        ->first();

                                    if ($existing) {
                                        // Barcode ditemukan: jangan error! Update status & data produk lama
                                        $existing->update([
                                            'listing_status' => 'TRIAL',
                                            'trial_start_date' => $data['trial_start_date'] ?? $existing->trial_start_date,
                                            'trial_end_date' => $data['trial_end_date'] ?? $existing->trial_end_date,
                                            'allowed_branch_ids' => $data['allowed_branch_ids'] ?? $existing->allowed_branch_ids,
                                            'cost_price' => $data['cost_price'] ?? $existing->cost_price,
                                            'cost_price_tax' => $data['cost_price_tax'] ?? $existing->cost_price_tax,
                                            'margin_gol_1' => $data['margin_gol_1'] ?? $existing->margin_gol_1,
                                            'harga_jual_1' => $data['harga_jual_1'] ?? $existing->harga_jual_1,
                                            'selling_price' => $data['selling_price'] ?? $existing->selling_price,
                                        ]);

                                        $data['id'] = $existing->id;
                                        $data['sku'] = $existing->sku;
                                    }
                                }

                                // Jika barcode baru dan SKU belum ada: otomatis buat SKU baru
                                if (empty($data['sku'])) {
                                    $data['sku'] = Product::generateNextSku($orgId);
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
                                        $data['sku'] = Product::generateNextSku($orgId);
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
                                TableColumn::make('Produk & Identitas')->width('280px')->markAsRequired(),
                                TableColumn::make('Kategori (Beda)')->width('210px'),
                                TableColumn::make('Satuan')->width('65px')->markAsRequired(),
                                TableColumn::make('Modal (DPP)')->width('115px')->markAsRequired(),
                                TableColumn::make('HPP + PPN (11%)')->width('115px'),
                                TableColumn::make('Margin (%)')->width('90px')->markAsRequired(),
                                TableColumn::make('Harga Jual')->width('120px')->markAsRequired(),
                            ])
                            ->schema([
                                // Kolom 1: Produk & Identitas (Nama Produk + Barcode/SKU)
                                Group::make([
                                    Placeholder::make('badge_status_barang')
                                        ->hiddenLabel()
                                        ->content(function (callable $get) {
                                            $isExisting = (bool) $get('is_existing');
                                            $id = $get('id');

                                            if (!$isExisting && !empty($id)) {
                                                $product = Product::find($id);
                                                if ($product) {
                                                    $isExisting = $product->is_existing_listing;
                                                }
                                            }

                                            if ($isExisting) {
                                                return new \Illuminate\Support\HtmlString(
                                                    '<div class="flex items-center gap-1.5 mb-1.5" style="display:flex; align-items:center; gap:6px; margin-bottom: 5px;">' .
                                                    '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-700 shadow-sm" style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:9999px; font-size:0.68rem; font-weight:700; background-color:#dcfce7; color:#15803d; border:1px solid #86efac;">' .
                                                    '<svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" style="width:11px; height:11px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>' .
                                                    'PRODUK SUDAH ADA (Pantauan Listing)' .
                                                    '</span>' .
                                                    '</div>'
                                                );
                                            }

                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="flex items-center gap-1.5 mb-1.5" style="display:flex; align-items:center; gap:6px; margin-bottom: 5px;">' .
                                                '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-300 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-700 shadow-sm" style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:9999px; font-size:0.68rem; font-weight:700; background-color:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe;">' .
                                                '✨ BARANG BARU (SKU Baru)' .
                                                '</span>' .
                                                '</div>'
                                            );
                                        }),

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
                                            ->extraAttributes(['style' => 'font-family: monospace; font-size: 0.75rem;'])
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                                $barcode = trim((string) $state);
                                                if (empty($barcode)) {
                                                    $set('id', null);
                                                    $set('is_existing', false);
                                                    return;
                                                }

                                                $orgId = $get('organization_id') ?? $get('../../organization_id') ?? Auth::user()?->organization_id ?? Organization::first()?->id;

                                                $existing = Product::where('organization_id', $orgId)
                                                    ->where(function ($q) use ($barcode) {
                                                        $q->where('barcode', $barcode)
                                                          ->orWhere('sku', $barcode)
                                                          ->orWhereJsonContains('metadata->additional_barcodes', $barcode);
                                                    })
                                                    ->first();

                                                if ($existing) {
                                                    $set('id', $existing->id);
                                                    $set('is_existing', true);
                                                    if (blank($get('name'))) {
                                                        $set('name', $existing->name);
                                                    }
                                                    $set('sku', $existing->sku);
                                                    if (!empty($existing->unit_of_measure)) {
                                                        $set('unit_of_measure', $existing->unit_of_measure);
                                                    }
                                                    if (!empty($existing->category_id) && blank($get('category_id'))) {
                                                        $set('category_id', $existing->category_id);
                                                    }
                                                    if (!empty($existing->sub_category) && blank($get('sub_category'))) {
                                                        $set('sub_category', $existing->sub_category);
                                                    }

                                                    $currentCost = (float) str_replace(',', '.', str_replace('.', '', (string) $get('cost_price')));
                                                    if ($currentCost <= 0 && $existing->cost_price > 0) {
                                                        $set('cost_price', $existing->cost_price);
                                                        $set('cost_price_tax', $existing->cost_price_tax);
                                                        $set('margin_gol_1', $existing->margin_gol_1 ?? 10);
                                                        $set('harga_jual_1', $existing->harga_jual_1 ?? $existing->selling_price);
                                                        $set('selling_price', $existing->harga_jual_1 ?? $existing->selling_price);
                                                    }

                                                    Notification::make()
                                                        ->title('Barcode Terdaftar di Database')
                                                        ->body("Barcode '{$barcode}' adalah '{$existing->name}'. Produk akan dimasukkan ke dalam pantauan listing tanpa membuat duplikasi.")
                                                        ->info()
                                                        ->send();
                                                } else {
                                                    $set('id', null);
                                                    $set('is_existing', false);
                                                    if (blank($get('sku'))) {
                                                        $newSku = Product::generateNextSku($orgId);
                                                        $set('sku', $newSku);
                                                    }
                                                }
                                            }),
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

                                    Select::make('sub_category')
                                        ->label('Sub Kategori')
                                        ->hiddenLabel()
                                        ->placeholder(fn (callable $get) => empty($get('category_id') ?: $get('../../default_category_id')) ? 'Pilih Kategori...' : 'Pilih / Cari Sub Kategori...')
                                        ->options(function (callable $get) {
                                            $catId = $get('category_id') ?: $get('../../default_category_id');
                                            if (blank($catId)) {
                                                $current = $get('sub_category');
                                                return $current ? [$current => $current] : [];
                                            }

                                            $options = Product::where('category_id', $catId)
                                                ->whereNotNull('sub_category')
                                                ->where('sub_category', '!=', '')
                                                ->distinct()
                                                ->orderBy('sub_category')
                                                ->pluck('sub_category', 'sub_category')
                                                ->toArray();

                                            $current = $get('sub_category');
                                            if (!empty($current) && !isset($options[$current])) {
                                                $options[$current] = $current;
                                            }

                                            return $options;
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->createOptionForm([
                                            TextInput::make('sub_category')
                                                ->label('Sub Kategori Baru')
                                                ->required(),
                                        ])
                                        ->createOptionUsing(fn (array $data) => strtoupper(trim($data['sub_category']))),
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
                                        $taxRate = RetailIntelligenceService::getActiveTaxRate();
                                        $taxed_cost = $isTaxable ? round($cost * (1 + ($taxRate / 100)), 2) : $cost;
                                        $set('cost_price_tax', str_replace('.', ',', (string)$taxed_cost));

                                        $margin = (float) str_replace(',', '.', str_replace('.', '', (string) $get('margin_gol_1')));
                                        if ($margin <= 0) {
                                            $margin = 10;
                                            $set('margin_gol_1', 10);
                                        }

                                        if ($taxed_cost > 0) {
                                            $autoJual = ceil(($taxed_cost * (1 + ($margin / 100))) / 100) * 100;
                                            $set('harga_jual_1', $autoJual);
                                            $set('selling_price', $autoJual);
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
                                            $taxRate = RetailIntelligenceService::getActiveTaxRate();
                                            $dpp = round($taxed_cost / (1 + ($taxRate / 100)), 2);
                                            $set('cost_price', str_replace('.', ',', (string)$dpp));

                                            $margin = (float) str_replace(',', '.', str_replace('.', '', (string) $get('margin_gol_1')));
                                            if ($margin <= 0) {
                                                $margin = 10;
                                                $set('margin_gol_1', 10);
                                            }

                                            $autoJual = ceil(($taxed_cost * (1 + ($margin / 100))) / 100) * 100;
                                            $set('harga_jual_1', $autoJual);
                                            $set('selling_price', $autoJual);
                                        }
                                    }),

                                // Kolom 6: Margin (%) - Default 10%
                                TextInput::make('margin_gol_1')
                                    ->label('Margin (%)')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->default(10)
                                    ->suffix('%')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                        $hpp = (float) str_replace(',', '.', str_replace('.', '', (string)$get('cost_price_tax')));
                                        if ($hpp <= 0) {
                                            $cost = (float) str_replace(',', '.', str_replace('.', '', (string)$get('cost_price')));
                                            $isTaxable = (bool) ($get('is_taxable') ?? true);
                                            $taxRate = RetailIntelligenceService::getActiveTaxRate();
                                            $hpp = $isTaxable ? round($cost * (1 + ($taxRate / 100)), 2) : $cost;
                                            $set('cost_price_tax', str_replace('.', ',', (string)$hpp));
                                        }

                                        $margin = (float) str_replace(',', '.', str_replace('.', '', (string)$state));
                                        if ($hpp > 0) {
                                            $autoJual = ceil(($hpp * (1 + ($margin / 100))) / 100) * 100;
                                            $set('harga_jual_1', $autoJual);
                                            $set('selling_price', $autoJual);
                                        }
                                    }),

                                // Kolom 7: Harga Jual (Eceran)
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

                                Hidden::make('id')->nullable(),
                                Hidden::make('is_existing')->default(false),
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
