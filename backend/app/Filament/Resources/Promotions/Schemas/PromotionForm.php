<?php

namespace App\Filament\Resources\Promotions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
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
                                'PWP' => 'Subsidi Silang / Tebus Murah (PWP)',
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
                                if ($type === 'PWP') {
                                    return 'Subsidi Silang (Purchase With Purchase). Beli produk pendorong margin (Trigger) untuk berhak tebus murah produk sensitif harga (Reward) dengan proteksi kuota tertentu.';
                                }
                                return null;
                            }),
                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Cakupan Cabang')
                    ->schema([
                        Select::make('branches')
                            ->relationship('branches', 'name')
                            ->multiple()
                            ->preload()
                            ->label('Berlaku di Cabang')
                            ->helperText('Pilih cabang mana saja promosi ini berlaku. Jika kosong, promosi berlaku di semua cabang.')
                            ->columnSpanFull(),
                    ]),

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
                    ->visible(fn (callable $get) => !in_array($get('promo_type'), ['BUNDLING', 'PWP']))
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
                            ->dehydrateStateUsing(fn ($state, callable $get) => in_array($get('promo_type'), ['BUNDLING', 'PWP']) ? 'ALL' : $state),
                        Select::make('target_ids')
                            ->label('Target (Produk/Kategori)')
                            ->multiple()
                            ->searchable()
                            ->visible(fn (callable $get) => !in_array($get('promo_type'), ['BUNDLING', 'PWP']) && in_array($get('applicable_to'), ['PRODUCT', 'CATEGORY']))
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

                Section::make('Aturan Khusus Subsidi Silang / Tebus Murah (PWP)')
                    ->visible(fn (callable $get) => $get('promo_type') === 'PWP')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('promo_config.pwp_trigger_product_id')
                                ->label('1. Produk Pemicu / Margin Generator (Trigger Product)')
                                ->options(\App\Models\Product::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->helperText(function (callable $get) {
                                    $prodId = $get('promo_config.pwp_trigger_product_id');
                                    if (!$prodId) return 'Pilih produk yang harus dibeli oleh pelanggan (produk bermargin tinggi).';
                                    $prod = \App\Models\Product::find($prodId);
                                    if (!$prod) return null;
                                    return "Harga Jual: Rp " . number_format((float)$prod->selling_price, 0, ',', '.') . 
                                           " | HPP/Modal: Rp " . number_format((float)$prod->cost_price, 0, ',', '.');
                                }),
                            TextInput::make('promo_config.pwp_trigger_min_qty')
                                ->label('Min. Qty Pembelian Trigger')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->live()
                                ->helperText('Jumlah pembelian produk pemicu yang dibutuhkan agar hak tebus murah aktif.'),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('promo_config.pwp_reward_product_id')
                                ->label('2. Produk Disubsidi / Tebus Murah (Reward Product)')
                                ->options(\App\Models\Product::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->helperText(function (callable $get) {
                                    $prodId = $get('promo_config.pwp_reward_product_id');
                                    if (!$prodId) return 'Pilih produk yang disubsidi harganya (sensitif harga).';
                                    $prod = \App\Models\Product::find($prodId);
                                    if (!$prod) return null;
                                    return "Harga Jual Normal: Rp " . number_format((float)$prod->selling_price, 0, ',', '.') . 
                                           " | HPP/Modal: Rp " . number_format((float)$prod->cost_price, 0, ',', '.');
                                }),
                            Select::make('promo_config.pwp_discount_type')
                                ->label('Jenis Potongan Reward')
                                ->options([
                                    'SPECIAL_PRICE' => 'Harga Tebus Murah (Fixed Price)',
                                    'DISCOUNT_NOMINAL' => 'Potongan Nominal Rupiah',
                                    'DISCOUNT_PERCENT' => 'Potongan Persentase (%)',
                                ])
                                ->default('SPECIAL_PRICE')
                                ->required()
                                ->live(),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('promo_config.pwp_discount_value')
                                ->label(fn (callable $get) => match ($get('promo_config.pwp_discount_type')) {
                                    'SPECIAL_PRICE' => 'Harga Tebus Murah (Rupiah)',
                                    'DISCOUNT_NOMINAL' => 'Nilai Potongan (Rupiah)',
                                    'DISCOUNT_PERCENT' => 'Persentase Diskon',
                                    default => 'Nilai Diskon / Harga Tebus',
                                })
                                ->prefix(fn (callable $get) => $get('promo_config.pwp_discount_type') === 'DISCOUNT_PERCENT' ? null : 'Rp')
                                ->suffix(fn (callable $get) => $get('promo_config.pwp_discount_type') === 'DISCOUNT_PERCENT' ? '%' : null)
                                ->numeric()
                                ->required()
                                ->live()
                                ->helperText(fn (callable $get) => $get('promo_config.pwp_discount_type') === 'SPECIAL_PRICE' 
                                    ? 'Contoh: Diisi 10.000 artinya produk tebus dibayar Rp 10.000/pcs.' 
                                    : 'Besaran diskon yang dipotongkan.'),
                            TextInput::make('promo_config.pwp_reward_qty_per_trigger')
                                ->label('Maks. Tebus Per Kelipatan Trigger')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->live()
                                ->helperText('Kuantitas tebus murah per 1 kelipatan syarat trigger.'),
                            TextInput::make('promo_config.pwp_max_reward_per_transaction')
                                ->label('Maks. Tebus Per Transaksi (Anti-Hoarding)')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->helperText('Batas tebus murah maksimal per struk (kosongkan jika tanpa batas). Mencegah borongan.'),
                        ]),

                        Placeholder::make('pwp_margin_simulation')
                            ->label('📊 Simulasi Profit & Margin Gabungan (Subsidi Silang)')
                            ->columnSpanFull()
                            ->content(function (callable $get) {
                                $triggerId = $get('promo_config.pwp_trigger_product_id');
                                $rewardId = $get('promo_config.pwp_reward_product_id');

                                if (!$triggerId || !$rewardId) {
                                    return 'Pilih Produk Trigger (Pemicu) dan Produk Reward (Disubsidi) untuk melihat simulasi margin gabungan.';
                                }

                                $trigger = \App\Models\Product::find($triggerId);
                                $reward = \App\Models\Product::find($rewardId);

                                if (!$trigger || !$reward) {
                                    return 'Data produk tidak ditemukan.';
                                }

                                $triggerMinQty = max(1, (int) ($get('promo_config.pwp_trigger_min_qty') ?: 1));
                                $rewardQty = max(1, (int) ($get('promo_config.pwp_reward_qty_per_trigger') ?: 1));

                                $triggerSelling = (float) $trigger->selling_price;
                                $triggerCost = (float) $trigger->cost_price;
                                $triggerProfit = ($triggerSelling - $triggerCost) * $triggerMinQty;

                                $rewardSelling = (float) $reward->selling_price;
                                $rewardCost = (float) $reward->cost_price;

                                $discountType = $get('promo_config.pwp_discount_type') ?: 'SPECIAL_PRICE';
                                $discountVal = (float) ($get('promo_config.pwp_discount_value') ?: 0);

                                $rewardFinalPrice = $rewardSelling;
                                if ($discountType === 'SPECIAL_PRICE') {
                                    $rewardFinalPrice = $discountVal;
                                } elseif ($discountType === 'DISCOUNT_NOMINAL') {
                                    $rewardFinalPrice = max(0, $rewardSelling - $discountVal);
                                } elseif ($discountType === 'DISCOUNT_PERCENT') {
                                    $rewardFinalPrice = max(0, $rewardSelling * (1 - ($discountVal / 100)));
                                }

                                $rewardProfit = ($rewardFinalPrice - $rewardCost) * $rewardQty;
                                $totalRevenue = ($triggerSelling * $triggerMinQty) + ($rewardFinalPrice * $rewardQty);
                                $totalCost = ($triggerCost * $triggerMinQty) + ($rewardCost * $rewardQty);
                                $netProfit = $triggerProfit + $rewardProfit;
                                $netMarginPercent = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

                                $statusColor = $netProfit >= 0 ? '#16a34a' : '#dc2626';
                                $statusBadge = $netProfit >= 0 ? '✅ Margin Sehat (Paket Menguntungkan)' : '⚠️ Margin Negatif (Toko Berpotensi Rugi)';

                                return new \Illuminate\Support\HtmlString("
                                    <div style='padding: 14px; border-radius: 8px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.08);'>
                                        <div style='font-weight: 600; color: {$statusColor}; margin-bottom: 8px;'>{$statusBadge}</div>
                                        <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 0.875rem;'>
                                            <div>
                                                <strong>Produk Pemicu ({$trigger->name} x {$triggerMinQty}):</strong><br/>
                                                Harga Jual: Rp " . number_format($triggerSelling * $triggerMinQty, 0, ',', '.') . "<br/>
                                                HPP/Modal: Rp " . number_format($triggerCost * $triggerMinQty, 0, ',', '.') . "<br/>
                                                Laba Pemicu: <span style='color: #16a34a; font-weight: 600;'>+Rp " . number_format($triggerProfit, 0, ',', '.') . "</span>
                                            </div>
                                            <div>
                                                <strong>Produk Tebus ({$reward->name} x {$rewardQty}):</strong><br/>
                                                Harga Tebus: Rp " . number_format($rewardFinalPrice * $rewardQty, 0, ',', '.') . " (Normal: Rp " . number_format($rewardSelling * $rewardQty, 0, ',', '.') . ")<br/>
                                                HPP/Modal: Rp " . number_format($rewardCost * $rewardQty, 0, ',', '.') . "<br/>
                                                Laba/Rugi Tebus: <span style='color: " . ($rewardProfit >= 0 ? '#16a34a' : '#dc2626') . "; font-weight: 600;'>" . ($rewardProfit >= 0 ? '+Rp ' : '-Rp ') . number_format(abs($rewardProfit), 0, ',', '.') . "</span>
                                            </div>
                                            <div style='border-left: 2px solid rgba(0,0,0,0.08); padding-left: 12px;'>
                                                <strong>Hasil Gabungan Paket Keranjang:</strong><br/>
                                                Total Omzet Paket: Rp " . number_format($totalRevenue, 0, ',', '.') . "<br/>
                                                Total Modal Paket: Rp " . number_format($totalCost, 0, ',', '.') . "<br/>
                                                <strong>Laba Bersih Paket: <span style='color: {$statusColor}; font-size: 1rem;'>Rp " . number_format($netProfit, 0, ',', '.') . " (" . number_format($netMarginPercent, 1) . "%)</span></strong>
                                            </div>
                                        </div>
                                    </div>
                                ");
                            }),
                    ]),

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
