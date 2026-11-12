<?php

namespace App\Filament\Resources\ListingProduks\Pages;

use App\Filament\Resources\ListingProduks\ListingProdukResource;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\SupplierDeduction;
use App\Services\ProductListingParserService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ListListingProduks extends ListRecords
{
    protected static string $resource = ListingProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload_listing_document')
                ->label('Upload Dokumen Listing')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Upload Dokumen Listing Produk Baru')
                ->modalDescription('Unggah dokumen formulir registrasi produk (Foto/Scan JPG, PNG, PDF, atau File Excel XLSX/CSV). Sistem otomatis mendeteksi barcode, nama barang, harga satuan (DPP), menghitung harga jual dengan persentase margin default 10%, membuat SKU unik baru (atau memasukkan ke pantauan listing jika barcode sudah ada tanpa error), dan otomatis membuat perjanjian listing baru.')
                ->modalSubmitActionLabel('Proses & Buat Listing')
                ->modalWidth('2xl')
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
                        ->helperText('Format: Foto Formulir (JPG/PNG), File PDF, atau Spreadsheet Excel/CSV. File akan tersimpan permanen sebagai bukti dokumen.'),

                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->options(fn () => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->placeholder('Pilih Supplier...')
                        ->helperText('Pilih supplier untuk dokumen listing ini.'),

                    TextInput::make('default_margin')
                        ->label('Persentase Margin Default (%)')
                        ->numeric()
                        ->default(10)
                        ->suffix('%')
                        ->required()
                        ->helperText('Margin keuntungan untuk harga jual dihitung dari HPP + PPN 11%. Default: 10%. Masih bisa diedit.'),

                    Select::make('default_category_id')
                        ->label('Kategori Default')
                        ->options(fn () => Category::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('default_sub_category', null))
                        ->default(fn () => Category::where('name', 'FOOD')->first()?->id ?? Category::first()?->id)
                        ->required()
                        ->helperText('Kategori dasar untuk produk-produk baru yang didaftarkan.'),

                    Select::make('default_sub_category')
                        ->label('Sub Kategori Default (Opsional)')
                        ->placeholder(fn (callable $get) => empty($get('default_category_id')) ? 'Pilih Kategori terlebih dahulu...' : 'Pilih / Cari Sub Kategori...')
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
                        ->nullable()
                        ->createOptionForm([
                            TextInput::make('sub_category')->label('Sub Kategori Baru')->required(),
                        ])
                        ->createOptionUsing(fn (array $data) => strtoupper(trim($data['sub_category'])))
                        ->helperText('Sub kategori yang otomatis diterapkan jika produk belum memiliki sub kategori.'),

                    CheckboxList::make('allowed_branch_ids')
                        ->label('Cabang Pilot yang Diizinkan Order (Kuota Uji Coba)')
                        ->options(fn () => Branch::where('is_active', true)->pluck('name', 'id'))
                        ->columns(2)
                        ->default(fn () => Branch::where('is_active', true)->pluck('id')->toArray())
                        ->required()
                        ->helperText('Pilih cabang-cabang yang berhak memesan dan memajang produk selama masa uji coba.'),
                ])
                ->action(function (array $data, \Livewire\Component $livewire) {
                    $relativePath = $data['document_file'];
                    $filePath = Storage::disk('public')->path($relativePath);
                    $margin = (float) ($data['default_margin'] ?? 10);
                    $orgId = Auth::user()?->organization_id ?? Organization::first()?->id;

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

                    // Tentukan supplier_id
                    $supplierId = !empty($data['supplier_id'])
                        ? $data['supplier_id']
                        : Supplier::where('is_active', true)->first()?->id;

                    if (empty($supplierId)) {
                        Notification::make()
                            ->title('Supplier Belum Ditentukan')
                            ->body('Tidak dapat membuat listing karena supplier belum dipilih dan tidak terdeteksi dari dokumen.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $branchIds = array_values(array_filter((array) ($data['allowed_branch_ids'] ?? [])));
                    $now = now();
                    $listingNumber = !empty($result['listing_number'])
                        ? $result['listing_number']
                        : ('LST-' . date('ymd') . '-' . rand(100, 999));

                    // Buat Perjanjian ProductListing
                    $listing = ProductListing::create([
                        'organization_id' => $orgId,
                        'listing_number' => $listingNumber,
                        'supplier_id' => $supplierId,
                        'listing_fee' => (float) ($result['listing_fee'] ?? 0),
                        'trial_start_date' => $now->toDateString(),
                        'trial_end_date' => $now->copy()->addMonths(3)->toDateString(),
                        'allowed_branch_ids' => $branchIds,
                        'status' => 'TRIAL',
                        'notes' => $result['notes'] ?? 'Dibuat otomatis dari Upload Dokumen Listing.',
                        'document_path' => $relativePath,
                        'created_by' => Auth::id(),
                    ]);

                    $defaultCatId = $data['default_category_id'];

                    // Proses setiap item produk
                    foreach ($result['items'] as $item) {
                        $productId = $item['id'] ?? null;
                        $product = null;

                        if (!empty($productId)) {
                            $product = Product::find($productId);
                        } elseif (!empty($item['barcode'])) {
                            $product = Product::where('organization_id', $orgId)
                                ->where('barcode', $item['barcode'])
                                ->first();
                        }

                        if ($product) {
                            // Produk sudah ada: jangan error, masukkan ke pantauan listing
                            $meta = $product->metadata ?? [];
                            $meta['is_existing_listing'] = true;
                            $product->update([
                                'metadata' => $meta,
                                'organization_id' => $orgId,
                                'supplier_id' => $supplierId,
                                'product_listing_id' => $listing->id,
                                'listing_status' => 'TRIAL',
                                'trial_start_date' => $listing->trial_start_date,
                                'trial_end_date' => $listing->trial_end_date,
                                'allowed_branch_ids' => $branchIds,
                                'cost_price' => $item['cost_price'],
                                'cost_price_tax' => $item['cost_price_tax'],
                                'margin_gol_1' => $item['margin_gol_1'],
                                'harga_jual_1' => $item['harga_jual_1'],
                                'selling_price' => $item['selling_price'],
                            ]);
                        } else {
                            // Produk baru: buat SKU baru dan simpan
                            $product = Product::create([
                                'organization_id' => $orgId,
                                'supplier_id' => $supplierId,
                                'product_listing_id' => $listing->id,
                                'listing_status' => 'TRIAL',
                                'sku' => $item['sku'],
                                'barcode' => $item['barcode'],
                                'name' => $item['name'],
                                'category_id' => $item['category_id'] ?? $defaultCatId,
                                'sub_category' => $item['sub_category'] ?? ($data['default_sub_category'] ?? null),
                                'unit_of_measure' => $item['unit_of_measure'] ?? 'pcs',
                                'cost_price' => $item['cost_price'],
                                'cost_price_tax' => $item['cost_price_tax'],
                                'margin_gol_1' => $item['margin_gol_1'],
                                'harga_jual_1' => $item['harga_jual_1'],
                                'selling_price' => $item['selling_price'],
                                'qty_min_gol_1' => 1,
                                'trial_start_date' => $listing->trial_start_date,
                                'trial_end_date' => $listing->trial_end_date,
                                'allowed_branch_ids' => $branchIds,
                                'is_taxable' => true,
                                'is_active' => true,
                            ]);
                        }

                        // Buat data stok untuk setiap cabang pilot
                        foreach ($branchIds as $bId) {
                            Stock::firstOrCreate(
                                [
                                    'branch_id' => $bId,
                                    'product_id' => $product->id,
                                ],
                                [
                                    'quantity_on_hand' => 0,
                                    'quantity_reserved' => 0,
                                    'cost_price' => $product->cost_price,
                                    'cost_price_tax' => $product->cost_price_tax,
                                    'selling_price' => $product->selling_price,
                                    'margin_gol_1' => $product->margin_gol_1,
                                    'harga_jual_1' => $product->harga_jual_1,
                                    'qty_min_gol_1' => 1,
                                    'is_active' => true,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ]
                            );
                        }
                    }

                    // Jika ada biaya listing, buat potongan tagihan kontrabon supplier
                    if ((float) $listing->listing_fee > 0) {
                        SupplierDeduction::create([
                            'supplier_id' => $supplierId,
                            'branch_id' => null,
                            'deduction_type' => 'LISTING_FEE',
                            'reference_id' => $listing->id,
                            'amount' => $listing->listing_fee,
                            'claimed_amount' => 0,
                            'status' => 'OPEN',
                            'notes' => "Listing Fee Perjanjian: {$listing->listing_number} - Masa Uji Coba 3 Bulan",
                        ]);
                    }

                    // Bersihkan cache
                    Cache::forget('ecommerce_products_all');
                    foreach ($branchIds as $bId) {
                        Cache::forget('ecommerce_products_' . $bId);
                        Cache::forget('pos_products_json_gz_branch_' . $bId);
                    }

                    $summary = $result['summary'];
                    Notification::make()
                        ->title('Perjanjian Listing Berhasil Dibuat')
                        ->body("Perjanjian '{$listing->listing_number}' dibuat dengan {$summary['total_items']} produk ({$summary['new_items_count']} produk baru, {$summary['existing_items_count']} produk dialihkan ke pantauan listing).")
                        ->success()
                        ->duration(8000)
                        ->send();

                    // Buka halaman edit listing untuk ditinjau / diedit lebih lanjut
                    $livewire->redirect(ListingProdukResource::getUrl('edit', ['record' => $listing->id]));
                }),

            CreateAction::make()
                ->label('Buat Perjanjian Listing Manual')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
