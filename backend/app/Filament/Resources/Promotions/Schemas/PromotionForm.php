<?php

namespace App\Filament\Resources\Promotions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->label('Organisasi')
                    ->relationship('organization', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label('Nama Promosi')
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
                    ->required(),
                TextInput::make('discount_value')
                    ->label('Nilai Diskon')
                    ->required()
                    ->numeric(),
                TextInput::make('min_purchase_amount')
                    ->label('Minimal Pembelian')
                    ->numeric()
                    ->prefix('Rp'),
                Select::make('applicable_to')
                    ->label('Berlaku Untuk')
                    ->options([
                        'ALL' => 'Seluruh Transaksi / Semua Produk',
                        'PRODUCT' => 'Produk Tertentu Saja',
                        'CATEGORY' => 'Kategori Tertentu Saja',
                    ])
                    ->required()
                    ->live(),
                Select::make('target_ids')
                    ->label('Target (Produk/Kategori)')
                    ->multiple()
                    ->searchable()
                    ->visible(fn (callable $get) => in_array($get('applicable_to'), ['PRODUCT', 'CATEGORY']))
                    ->options(function (callable $get) {
                        $type = $get('applicable_to');
                        if ($type === 'PRODUCT') {
                            return \App\Models\Product::pluck('name', 'id');
                        }
                        // For CATEGORY, we might need a Category model. 
                        // For now let's just use Product as a placeholder if Categories aren't implemented.
                        return []; 
                    }),
                DateTimePicker::make('valid_from')
                    ->label('Berlaku Dari')
                    ->required()
                    ->default(now()),
                DateTimePicker::make('valid_until')
                    ->label('Berlaku Sampai')
                    ->required()
                    ->default(now()->addMonth()),
                TextInput::make('max_discount_per_transaction')
                    ->label('Maksimal Diskon')
                    ->numeric()
                    ->prefix('Rp')
                    ->helperText('Kosongkan jika tidak ada batas maksimal diskon.'),
                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true),
                Section::make('Advanced Config')
                    ->collapsed()
                    ->schema([
                        KeyValue::make('promo_config')
                            ->label('Konfigurasi Tambahan')
                            ->helperText('Gunakan untuk aturan kustom dalam format JSON key-value.'),
                    ]),
            ]);
    }
}
