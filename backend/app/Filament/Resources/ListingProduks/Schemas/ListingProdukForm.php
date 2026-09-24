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
                    ->description('Data kesepakatan kerjasama listing produk baru dari pemasok')
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
                            ->label('Pemasok (Supplier)')
                            ->relationship('supplier', 'name', fn ($query) => $query->where('is_active', true))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('supplier_division_id', null)),

                        Select::make('supplier_division_id')
                            ->label('Sub Divisi Pemasok (Opsional)')
                            ->placeholder('Tanpa Sub Divisi')
                            ->options(function (callable $get) {
                                $supplierId = $get('supplier_id');
                                if (!$supplierId) return [];
                                return SupplierDivision::where('supplier_id', $supplierId)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->disabled(fn (callable $get) => blank($get('supplier_id'))),

                        Placeholder::make('supplier_type_info')
                            ->label('Tipe Kerjasama Pemasok')
                            ->content(function (callable $get) {
                                $supplierId = $get('supplier_id');
                                if (!$supplierId) return 'Pilih pemasok terlebih dahulu';
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
                            ->label('Otomatis Potong Tagihan Kontrabon Pemasok')
                            ->helperText('Jika aktif, total listing fee otomatis dicatat sebagai potongan terbuka pada pembayaran kontrabon pemasok ini.')
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
                            ->placeholder('Contoh: Target penjualan minimal 2 pcs/toko/hari. Jika dalam 3 bulan tidak memenuhi target, sisa stok diretur 100% ke pemasok.')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),

                Section::make('Daftar Produk yang Diajukan')
                    ->description('Daftarkan satu atau beberapa produk yang masuk dalam perjanjian listing ini')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('products')
                            ->relationship('products')
                            ->label('Item Produk')
                            ->addActionLabel('+ Tambah Baris Produk')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->compact()
                            ->table([
                                TableColumn::make('SKU')->width('135px')->markAsRequired(),
                                TableColumn::make('Barcode')->width('130px'),
                                TableColumn::make('Nama Produk')->markAsRequired(),
                                TableColumn::make('Kategori')->width('180px')->markAsRequired(),
                                TableColumn::make('Sub Kategori')->width('160px'),
                                TableColumn::make('Satuan')->width('85px')->markAsRequired(),
                                TableColumn::make('Modal (HPP)')->width('135px')->markAsRequired(),
                                TableColumn::make('HPP + PPN (11%)')->width('135px'),
                                TableColumn::make('Harga Jual (Eceran)')->width('135px'),
                            ])
                            ->schema([
                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->hiddenLabel()
                                    ->required()
                                    ->default(function () {
                                        $prefix = 'SKU-' . date('dmy');
                                        $lastProduct = Product::where('sku', 'like', $prefix . '%')
                                            ->orderBy('sku', 'desc')
                                            ->first();
                                        
                                        if ($lastProduct) {
                                            $lastNumber = (int) substr($lastProduct->sku, -4);
                                            return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                                        }
                                        
                                        return $prefix . '0001';
                                    })
                                    ->placeholder('Otomatis'),

                                TextInput::make('barcode')
                                    ->label('Barcode')
                                    ->hiddenLabel()
                                    ->nullable()
                                    ->placeholder('Scan / ketik'),

                                TextInput::make('name')
                                    ->label('Nama Produk')
                                    ->hiddenLabel()
                                    ->required()
                                    ->placeholder('Nama produk...'),

                                Select::make('category_id')
                                    ->label('Kategori')
                                    ->hiddenLabel()
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('sub_category')
                                    ->label('Sub Kategori')
                                    ->hiddenLabel()
                                    ->placeholder('Pilih / ketik')
                                    ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                        $options = Product::whereNotNull('sub_category')
                                            ->where('sub_category', '!=', '')
                                            ->distinct()
                                            ->pluck('sub_category', 'sub_category')
                                            ->toArray();
                                            
                                        $current = $get('sub_category');
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

                                TextInput::make('unit_of_measure')
                                    ->label('Satuan')
                                    ->hiddenLabel()
                                    ->required()
                                    ->default('pcs'),

                                TextInput::make('cost_price')
                                    ->label('Harga Modal (HPP)')
                                    ->hiddenLabel()
                                    ->required()
                                    ->rupiah()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                        $cost = (float) str_replace(',', '.', str_replace('.', '', (string)$state));
                                        $taxed_cost = round($cost * 1.11, 2);
                                        $set('cost_price_tax', str_replace('.', ',', (string)$taxed_cost));
                                        foreach([1, 2, 3] as $i) {
                                            $harga_jual_state = $get("harga_jual_{$i}");
                                            $price = (float) str_replace(',', '.', str_replace('.', '', (string)$harga_jual_state));
                                            if ($taxed_cost > 0 && $price > 0) {
                                                $new_margin = round((($price - $taxed_cost) / $taxed_cost) * 100, 2);
                                                $set("margin_gol_{$i}", str_replace('.', ',', (string)$new_margin));
                                            }
                                        }
                                        $price1 = (float) str_replace(',', '.', str_replace('.', '', (string) $get('harga_jual_1')));
                                        $set('selling_price', $price1);
                                    }),

                                TextInput::make('cost_price_tax')
                                    ->label('Harga Beli + PPN')
                                    ->hiddenLabel()
                                    ->rupiah(),

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
                                            $set("margin_gol_1", str_replace('.', ',', (string)$new_margin));
                                        }
                                        $set('selling_price', $harga);
                                    }),

                                Hidden::make('margin_gol_1')->default(0),
                                Hidden::make('qty_min_gol_1')->default(1),
                                Hidden::make('selling_price')->default(0),
                                Hidden::make('is_taxable')->default(true),
                                Hidden::make('is_active')->default(true),
                            ]),
                    ]),
            ]);
    }
}
