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
                                'PERCENTAGE' => 'Diskon Persentase (%)',
                                'FIXED' => 'Diskon Nominal Rupiah (Rp)',
                                'BUNDLING' => 'Beli X Gratis Y (Bundling)',
                                'FLASH_SALE' => 'Flash Sale (Produk Spesifik)',
                                'TIERED' => 'Bertingkat (Min. Belanja)',
                            ])
                            ->required()
                            ->live(),
                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Parameter Nilai Diskon')
                    ->visible(fn (callable $get) => in_array($get('promo_type'), ['PERCENTAGE', 'FIXED', 'FLASH_SALE']))
                    ->schema([
                        TextInput::make('discount_value')
                            ->label(fn (callable $get) => $get('promo_type') === 'FIXED' ? 'Nilai Diskon (Rupiah)' : 'Persentase Diskon')
                            ->required()
                            ->numeric()
                            ->prefix(fn (callable $get) => $get('promo_type') === 'FIXED' ? 'Rp' : null)
                            ->suffix(fn (callable $get) => in_array($get('promo_type'), ['PERCENTAGE', 'FLASH_SALE']) ? '%' : null)
                            ->dehydrateStateUsing(fn ($state, callable $get) => in_array($get('promo_type'), ['PERCENTAGE', 'FIXED', 'FLASH_SALE']) ? $state : 0),
                        TextInput::make('min_purchase_amount')
                            ->label('Minimal Pembelian')
                            ->numeric()
                            ->prefix('Rp')
                            ->visible(fn (callable $get) => in_array($get('promo_type'), ['PERCENTAGE', 'FIXED']))
                            ->helperText('Minimal total belanja untuk mengaktifkan diskon ini.'),
                        TextInput::make('max_discount_per_transaction')
                            ->label('Maksimal Diskon')
                            ->numeric()
                            ->prefix('Rp')
                            ->visible(fn (callable $get) => in_array($get('promo_type'), ['PERCENTAGE', 'FLASH_SALE']))
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
                        Grid::make(2)
                            ->schema([
                                TextInput::make('promo_config.start_time')
                                    ->label('Jam Mulai Berlaku')
                                    ->placeholder('Contoh: 14:00')
                                    ->helperText('Format HH:MM (24 jam)'),
                                TextInput::make('promo_config.end_time')
                                    ->label('Jam Selesai Berlaku')
                                    ->placeholder('Contoh: 17:00')
                                    ->helperText('Format HH:MM (24 jam)'),
                            ]),
                    ]),

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
