<?php

namespace App\Filament\Resources\Promotions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama Promosi')
                    ->schema([
                        Select::make('organization_id')
                            ->label('Organisasi')
                            ->relationship('organization', 'name')
                            ->required(),
                        TextInput::make('name')
                            ->label('Nama Promosi')
                            ->placeholder('Contoh: Promo Akhir Pekan Hemat')
                            ->required(),
                        Select::make('promo_type')
                            ->label('Jenis Promosi')
                            ->options([
                                'PERCENTAGE' => 'Diskon Persentase Fixed',
                                'FIXED' => 'Diskon Nominal Fixed',
                                'PERCENTAGE_PER_ITEM' => 'Diskon Persentase Per Item',
                                'NOMINAL_PER_ITEM' => 'Diskon Nominal Per Item',
                                'BUNDLING' => 'Beli X Gratis Y (Bundling)',
                                'TIERED' => 'Bertingkat (Min. Belanja)',
                            ])
                            ->required()
                            ->live()
                            ->helperText(function (callable $get) {
                                $type = $get('promo_type');
                                if ($type === 'PERCENTAGE') {
                                    return 'Diskon akan dipotong berdasarkan persentase dari keseluruhan total keranjang. Contoh: Promo 10%. Belanja total 100.000 diskon 10.000.';
                                }
                                if ($type === 'FIXED') {
                                    return 'Diskon flat nominal untuk KESELURUHAN keranjang belanja. Contoh: Promo Fixed 3.000. Beli 1 Vinda diskon 3.000, beli 5 Vinda total diskon tetap 3.000.';
                                }
                                if ($type === 'PERCENTAGE_PER_ITEM') {
                                    return 'Diskon persentase yang dihitung untuk setiap satuan item. Contoh: Promo 10% untuk Vinda. Jika harga Vinda 10.000, maka akan mendapat diskon 1.000 per pcs.';
                                }
                                if ($type === 'NOMINAL_PER_ITEM') {
                                    return 'Diskon nominal rupiah yang dihitung untuk setiap satuan item. Contoh: Diskon 3.000 per Item. Beli 1 diskon 3.000, Beli 2 total diskon jadi 6.000.';
                                }
                                if ($type === 'BUNDLING') {
                                    return 'Promo beli produk A gratis produk B. Contoh: Beli 2 Susu gratis 1 Gelas.';
                                }
                                if ($type === 'TIERED') {
                                    return 'Diskon bertingkat berdasarkan minimum belanja. Contoh: Belanja 100rb diskon 5rb, belanja 200rb diskon 12rb.';
                                }
                                return null;
                            }),
                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Parameter Nilai Diskon')
                    ->visible(fn (callable $get) => in_array($get('promo_type'), ['PERCENTAGE', 'FIXED', 'PERCENTAGE_PER_ITEM', 'NOMINAL_PER_ITEM']))
                    ->schema([
                        TextInput::make('discount_value')
                            ->label(fn (callable $get) => in_array($get('promo_type'), ['FIXED', 'NOMINAL_PER_ITEM']) ? 'Nilai Diskon (Rupiah)' : 'Persentase Diskon')
                            ->required()
                            ->numeric()
                            ->prefix(fn (callable $get) => in_array($get('promo_type'), ['FIXED', 'NOMINAL_PER_ITEM']) ? 'Rp' : null)
                            ->suffix(fn (callable $get) => in_array($get('promo_type'), ['PERCENTAGE', 'PERCENTAGE_PER_ITEM']) ? '%' : null)
                            ->dehydrateStateUsing(fn ($state, callable $get) => in_array($get('promo_type'), ['PERCENTAGE', 'FIXED', 'PERCENTAGE_PER_ITEM', 'NOMINAL_PER_ITEM']) ? $state : 0),
                        TextInput::make('min_purchase_amount')
                            ->label('Minimal Pembelian')
                            ->numeric()
                            ->prefix('Rp')
                            ->visible(fn (callable $get) => in_array($get('promo_type'), ['PERCENTAGE', 'FIXED']))
                            ->helperText('Minimal total belanja untuk mengaktifkan diskon ini.'),
                        Select::make('promo_config.discount_limit_type')
                            ->label('Tipe Batas Maksimal Diskon')
                            ->options([
                                'PER_TRANSACTION' => 'Per Transaksi (Total Keseluruhan)',
                                'PER_ITEM' => 'Per Item / Produk',
                            ])
                            ->default('PER_TRANSACTION')
                            ->visible(fn (callable $get) => in_array($get('promo_type'), ['PERCENTAGE', 'PERCENTAGE_PER_ITEM', 'NOMINAL_PER_ITEM']))
                            ->helperText('Pilih apakah batas maksimal diskon berlaku untuk total 1 struk, atau untuk setiap item/produk.'),
                        TextInput::make('max_discount_per_transaction')
                            ->label('Maksimal Diskon (Nominal)')
                            ->numeric()
                            ->prefix('Rp')
                            ->visible(fn (callable $get) => in_array($get('promo_type'), ['PERCENTAGE', 'PERCENTAGE_PER_ITEM', 'NOMINAL_PER_ITEM']))
                            ->helperText('Kosongkan jika tidak ada batas maksimal diskon.'),
                    ])
                    ->columns(2),

                Section::make('Target Penerapan Diskon')
                    ->visible(fn (callable $get) => $get('promo_type') !== 'BUNDLING')
                    ->schema([
                        Select::make('applicable_to')
                            ->label('Berlaku Untuk')
                            ->options([
                                'ALL' => 'Seluruh Transaksi / Semua Produk',
                                'PRODUCT' => 'Produk Tertentu Saja',
                                'CATEGORY' => 'Kategori Tertentu Saja',
                            ])
                            ->required()
                            ->live()
                            ->dehydrateStateUsing(fn ($state, callable $get) => $get('promo_type') === 'BUNDLING' ? 'ALL' : $state),
                        Select::make('target_ids')
                            ->label('Target (Produk/Kategori)')
                            ->multiple()
                            ->searchable()
                            ->visible(fn (callable $get) => $get('promo_type') !== 'BUNDLING' && in_array($get('applicable_to'), ['PRODUCT', 'CATEGORY']))
                            ->options(function (callable $get) {
                                $type = $get('applicable_to');
                                if ($type === 'PRODUCT') {
                                    return \App\Models\Product::pluck('name', 'id');
                                }
                                if ($type === 'CATEGORY') {
                                    return \App\Models\Category::pluck('name', 'id');
                                }
                                return [];
                            }),
                        Select::make('branches')
                            ->relationship('branches', 'name')
                            ->multiple()
                            ->preload()
                            ->label('Berlaku di Cabang')
                            ->helperText('Pilih cabang mana saja promosi ini berlaku. Jika tidak ada yang dipilih, promosi dianggap tidak berlaku di cabang manapun (atau sesuaikan dengan kebijakan sistem).')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Aturan Khusus Bundling (Beli X Gratis Y)')
                    ->visible(fn (callable $get) => $get('promo_type') === 'BUNDLING')
                    ->schema([
                        Repeater::make('promo_config.rules')
                            ->label('Produk dalam Paket Bundling')
                            ->schema([
                                Select::make('productId')
                                    ->label('Pilih Produk')
                                    ->options(\App\Models\Product::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('minQty')
                                    ->label('Jumlah Minimum (Qty)')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->required(),
                        TextInput::make('promo_config.bundleDiscount')
                            ->label('Total Diskon Paket (Nominal Rupiah)')
                            ->helperText('Jumlah potongan harga ketika seluruh syarat kuantitas produk terpenuhi.')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                    ]),

                Section::make('Aturan Khusus Diskon Bertingkat (Tiers)')
                    ->visible(fn (callable $get) => $get('promo_type') === 'TIERED')
                    ->schema([
                        Repeater::make('promo_config.tiers')
                            ->label('Tingkatan Diskon')
                            ->schema([
                                TextInput::make('minAmount')
                                    ->label('Minimal Pembelian')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                                TextInput::make('discountPercent')
                                    ->label('Persentase Diskon')
                                    ->numeric()
                                    ->suffix('%')
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(100),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->required(),
                    ]),

                Section::make('Jadwal & Kriteria Tambahan (Opsional)')
                    ->collapsed()
                    ->schema([
                        Select::make('promo_config.member_tiers')
                            ->label('Target Tingkat Member Pelanggan')
                            ->multiple()
                            ->options([
                                'BRONZE' => 'Bronze',
                                'SILVER' => 'Silver',
                                'GOLD' => 'Gold',
                                'PLATINUM' => 'Platinum',
                            ])
                            ->helperText('Jika kosong, promo berlaku untuk semua pelanggan / non-member.'),
                        Select::make('promo_config.applicable_days')
                            ->label('Hari Berlaku')
                            ->multiple()
                            ->options([
                                'MONDAY' => 'Senin',
                                'TUESDAY' => 'Selasa',
                                'WEDNESDAY' => 'Rabu',
                                'THURSDAY' => 'Kamis',
                                'FRIDAY' => 'Jumat',
                                'SATURDAY' => 'Sabtu',
                                'SUNDAY' => 'Minggu',
                            ])
                            ->helperText('Pilih hari-hari tertentu ketika promo aktif. Jika kosong, berlaku setiap hari.'),
                        Select::make('promo_config.payment_methods')
                            ->label('Metode Pembayaran Khusus')
                            ->multiple()
                            ->options([
                                'CASH' => 'Tunai (Cash)',
                                'DEBIT' => 'Kartu Debit',
                                'CREDIT' => 'Kartu Kredit',
                                'QRIS' => 'QRIS',
                                'TRANSFER' => 'Transfer Bank',
                            ])
                            ->helperText('Promo hanya berlaku jika menggunakan metode pembayaran tertentu. Biarkan kosong jika berlaku untuk semua metode.'),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('promo_config.max_usage_limit')
                                    ->label('Batas Kuota Penggunaan')
                                    ->numeric()
                                    ->placeholder('Contoh: 100')
                                    ->helperText('Maksimal promo ini dapat digunakan (Total dari seluruh transaksi). Kosongkan jika tanpa batas.'),
                                TextInput::make('promo_config.max_usage_per_user')
                                    ->label('Batas Penggunaan Per Member')
                                    ->numeric()
                                    ->placeholder('Contoh: 1')
                                    ->helperText('Maksimal promo digunakan oleh satu member yang sama. Kosongkan jika tanpa batas.'),
                            ]),
                    ]),

                Section::make('Tanggungan Supplier (Promo Rafaksi / Klaim)')
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Pemasok / Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih supplier jika promo ini ditanggung oleh supplier (Rafaksi).'),
                        TextInput::make('supplier_sponsorship_percent')
                            ->label('Persentase Tanggungan Supplier')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Berapa persen dari total diskon yang akan ditagihkan ke supplier? (Ketik 100 jika ditanggung penuh)'),
                        Toggle::make('is_settled')
                            ->label('Sudah Di-settle (Ditutup)')
                            ->default(false)
                            ->disabled()
                            ->helperText('Akan dicentang otomatis saat proses settlement dilakukan.'),
                    ])
                    ->columns(2),

                Section::make('Periode Validitas')
                    ->schema([
                        DateTimePicker::make('valid_from')
                            ->label('Berlaku Dari')
                            ->required()
                            ->default(now()),
                        DateTimePicker::make('valid_until')
                            ->label('Berlaku Sampai')
                            ->required()
                            ->default(now()->addMonth()),
                    ])
                    ->columns(2),
            ]);
    }
}
