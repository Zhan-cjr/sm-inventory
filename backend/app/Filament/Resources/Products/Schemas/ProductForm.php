<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $isBranchUser = \Illuminate\Support\Facades\Auth::user()?->branch_id !== null;

        return $schema
            ->components([
                FileUpload::make('image_path')
                    ->label('Foto Produk')
                    ->image()
                    ->disk('public')
                    ->directory('products')
                    ->columnSpanFull()
                    ->disabled($isBranchUser),
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required()
                    ->default(fn() => \Illuminate\Support\Facades\Auth::user()->organization_id)
                    ->disabled(fn() => \Illuminate\Support\Facades\Auth::user()->organization_id !== null || $isBranchUser)
                    ->dehydrated(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'SKU ini sudah digunakan oleh produk lain.',
                    ])
                    ->readOnly(fn (string $operation): bool => $operation === 'edit')
                    ->disabled($isBranchUser),
                TextInput::make('barcode')
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Barcode ini sudah digunakan oleh produk lain.',
                    ])
                    ->disabled($isBranchUser),
                TextInput::make('name')
                    ->required()
                    ->disabled($isBranchUser),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled($isBranchUser),
                TextInput::make('sub_category')
                    ->label('Sub Kategori')
                    ->disabled($isBranchUser),
                Select::make('product_type')
                    ->label('Tipe Produk')
                    ->options([
                        'physical' => 'Fisik (Barang)',
                        'digital' => 'Digital (PPOB/Pulsa)',
                    ])
                    ->default('physical')
                    ->required()
                    ->live()
                    ->disabled($isBranchUser),
                TextInput::make('ppob_sku')
                    ->label('Kode SKU Digiflazz (PPOB SKU)')
                    ->visible(fn ($get) => $get('product_type') === 'digital')
                    ->helperText('Contoh: xld10 (Lihat daftar harga di Digiflazz)')
                    ->disabled($isBranchUser),
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled($isBranchUser),
                \Filament\Schemas\Components\Section::make('Harga Bertingkat & Margin')
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Schemas\Components\Group::make([
                            \Filament\Forms\Components\TextInput::make('cost_price')
                                ->label('Harga Modal (HPP)')
                                ->required()
                                ->rupiah()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    $taxed_cost = round($cost * 1.11, 2);
                                    $set('cost_price_tax', str_replace('.', ',', (string)$taxed_cost));
                                    foreach([1, 2, 3] as $i) {
                                        $harga_jual_state = $get("harga_jual_{$i}");
                                        $price = (float) str_replace(',', '.', str_replace('.', '', $harga_jual_state));
                                        if ($taxed_cost > 0 && $price > 0) {
                                            $new_margin = round((($price - $taxed_cost) / $taxed_cost) * 100, 2);
                                            $set("margin_gol_{$i}", str_replace('.', ',', (string)$new_margin));
                                        }
                                    }
                                    $set('selling_price', $get('harga_jual_1'));
                                }),
                            \Filament\Forms\Components\TextInput::make('cost_price_tax')
                                ->label('Harga Beli + PPN')
                                ->rupiah(),
                        ])->columnSpanFull()->columns(2),
                        \Filament\Schemas\Components\Group::make([
                            \Filament\Forms\Components\TextInput::make('qty_min_gol_1')->label('Min Qty Gol 1')->numeric()->default(1)->required(),
                            \Filament\Forms\Components\TextInput::make('margin_gol_1')->label('Margin Gol 1 (%)')->numeric()->default(0)->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $margin = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    $price = round($cost * (1 + ($margin / 100)), 2);
                                    $set('harga_jual_1', str_replace('.', ',', (string)$price));
                                    $set('selling_price', $price);
                                }),
                            \Filament\Forms\Components\TextInput::make('harga_jual_1')->label('Harga Jual Gol 1')->rupiah()->default(0)->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $price = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    if ($cost > 0) {
                                        $margin = round((($price - $cost) / $cost) * 100, 2);
                                        $set('margin_gol_1', str_replace('.', ',', (string)$margin));
                                    }
                                    $set('selling_price', $price);
                                }),
                        ])->columns(3)->columnSpanFull(),
                        \Filament\Schemas\Components\Group::make([
                            \Filament\Forms\Components\TextInput::make('qty_min_gol_2')->label('Min Qty Gol 2')->numeric(),
                            \Filament\Forms\Components\TextInput::make('margin_gol_2')->label('Margin Gol 2 (%)')->numeric()->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $margin = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    $price = round($cost * (1 + ($margin / 100)), 2);
                                    $set('harga_jual_2', str_replace('.', ',', (string)$price));
                                }),
                            \Filament\Forms\Components\TextInput::make('harga_jual_2')->label('Harga Jual Gol 2')->rupiah()->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    if ($cost > 0) {
                                        $price = (float) str_replace(',', '.', str_replace('.', '', $state));
                                        $margin = round((($price - $cost) / $cost) * 100, 2);
                                        $set('margin_gol_2', str_replace('.', ',', (string)$margin));
                                    }
                                }),
                        ])->columns(3)->columnSpanFull(),
                        \Filament\Schemas\Components\Group::make([
                            \Filament\Forms\Components\TextInput::make('qty_min_gol_3')->label('Min Qty Gol 3')->numeric(),
                            \Filament\Forms\Components\TextInput::make('margin_gol_3')->label('Margin Gol 3 (%)')->numeric()->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $margin = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    $price = round($cost * (1 + ($margin / 100)), 2);
                                    $set('harga_jual_3', str_replace('.', ',', (string)$price));
                                }),
                            \Filament\Forms\Components\TextInput::make('harga_jual_3')->label('Harga Jual Gol 3')->rupiah()->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    if ($cost > 0) {
                                        $price = (float) str_replace(',', '.', str_replace('.', '', $state));
                                        $margin = round((($price - $cost) / $cost) * 100, 2);
                                        $set('margin_gol_3', str_replace('.', ',', (string)$margin));
                                    }
                                }),
                        ])->columns(3)->columnSpanFull(),
                        \Filament\Forms\Components\Hidden::make('selling_price')->default(0)
                    ])
                    ->disabled($isBranchUser),
                Toggle::make('is_taxable')
                    ->label('Kena PPN')
                    ->default(true)
                    ->required()
                    ->disabled($isBranchUser),
                TextInput::make('unit_of_measure')
                    ->required()
                    ->default('pcs')
                    ->disabled($isBranchUser),
                TextInput::make('reorder_point')
                    ->required()
                    ->numeric()
                    ->default(10)
                    ->disabled($isBranchUser),
                TextInput::make('reorder_qty')
                    ->required()
                    ->numeric()
                    ->default(50)
                    ->disabled($isBranchUser),
                TextInput::make('lead_time_days')
                    ->required()
                    ->numeric()
                    ->default(5)
                    ->disabled($isBranchUser),
                Toggle::make('is_active')
                    ->required()
                    ->disabled($isBranchUser),
                Toggle::make('is_ecommerce_active')
                    ->label('Tampilkan di E-Commerce')
                    ->default(false)
                    ->reactive()
                    ->disabled($isBranchUser),
                Select::make('ecommerce_category')
                    ->label('Kategori E-Commerce')
                    ->options(function () {
                        $orgId = \Illuminate\Support\Facades\Auth::user()->organization_id;
                        $org = $orgId ? \App\Models\Organization::find($orgId) : \App\Models\Organization::first();
                        if ($org && is_array($org->ecommerce_categories) && count($org->ecommerce_categories) > 0) {
                            return array_combine($org->ecommerce_categories, $org->ecommerce_categories);
                        }
                        
                        // Fallback: tampilkan semua kategori produk standar yang aktif jika kategori e-commerce belum dikustomisasi
                        return \App\Models\Category::where('is_active', true)
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->visible(fn ($get) => $get('is_ecommerce_active'))
                    ->placeholder('Pilih Kategori E-Commerce')
                    ->searchable()
                    ->preload()
                    ->disabled($isBranchUser),
                \Filament\Schemas\Components\Section::make('Auto-Unpacking (Konversi Pecah Barang)')
                    ->description('Isi jika barang ini bisa "dipecah" menjadi barang satuan lain saat stok satuan tersebut habis. (Contoh: Produk ini "Beras Karung 15kg" dipecah menjadi "Beras Curah 1kg").')
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('conversions')
                            ->relationship()
                            ->schema([
                                Select::make('target_product_id')
                                    ->label('Produk Pecahan (Satuan Kecil)')
                                    ->options(fn () => \App\Models\Product::pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),
                                TextInput::make('conversion_qty')
                                    ->label('Hasil Pecahan (Qty)')
                                    ->helperText('Contoh: 1 Karung = 15 Curah, maka isi 15.')
                                    ->numeric()
                                    ->required()
                                    ->default(1),
                                Toggle::make('auto_convert')
                                    ->label('Auto-Unpack di POS')
                                    ->default(true),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                    ])->collapsed()
                    ->disabled($isBranchUser),
                TextInput::make('metadata')
                    ->disabled($isBranchUser),
            ]);
    }
}
