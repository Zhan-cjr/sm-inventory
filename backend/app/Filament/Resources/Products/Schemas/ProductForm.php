<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $isBranchUser = \Illuminate\Support\Facades\Auth::user()?->branch_id !== null;

        return $schema
            ->components([
                // ========================================================
                // 1. IDENTITAS & KLASIFIKASI DATA MASTER
                // ========================================================
                Section::make('Identitas & Klasifikasi Data Master')
                    ->description('Kelola data pokok, barcode, kategori, dan suplier utama produk.')
                    ->schema([
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
                            ->default(function () {
                                $prefix = 'SKU-' . date('dmy');
                                $lastProduct = \App\Models\Product::where('sku', 'like', $prefix . '%')
                                    ->orderBy('sku', 'desc')
                                    ->first();
                                
                                if ($lastProduct) {
                                    $lastNumber = (int) substr($lastProduct->sku, -4);
                                    $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                                    return $prefix . $newNumber;
                                }
                                
                                return $prefix . '0001';
                            })
                            ->readOnly(fn (string $operation): bool => $operation === 'edit')
                            ->disabled($isBranchUser),
                        TextInput::make('barcode')
                            ->label('Barcode Utama')
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Barcode ini sudah digunakan oleh produk lain.',
                            ])
                            ->rules([
                                function ($get, $record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                        if (blank($value)) return;
                                        $code = trim($value);
                                        $recordId = $record?->id;
                                        $sku = trim($get('sku') ?? '');

                                        if (!empty($sku) && strtolower($code) === strtolower($sku)) {
                                            $fail('Barcode tidak boleh sama dengan SKU produk ini.');
                                            return;
                                        }

                                        $exists = \App\Models\Product::where('id', '!=', $recordId)
                                            ->where(function ($q) use ($code) {
                                                $q->where('barcode', $code)
                                                  ->orWhere('sku', $code)
                                                  ->orWhereJsonContains('metadata->additional_barcodes', $code)
                                                  ->orWhere('metadata->additional_barcodes', 'LIKE', '%' . $code . '%');
                                            })->first();

                                        if ($exists) {
                                            $fail("Barcode '{$code}' sudah digunakan oleh produk '{$exists->name}' (SKU/Barcode/Multi Barcode).");
                                        }
                                    };
                                }
                            ])
                            ->disabled($isBranchUser),
                        TagsInput::make('metadata.additional_barcodes')
                            ->label('Barcode Tambahan (Multi Barcode)')
                            ->placeholder('Ketik barcode lalu tekan enter')
                            ->splitKeys(['Enter', 'Tab', ','])
                            ->rules([
                                function ($get, $record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                        if (empty($value)) return;
                                        $tags = is_array($value) ? $value : array_map('trim', explode(',', (string) $value));
                                        $recordId = $record?->id;
                                        $primaryBarcode = trim($get('barcode') ?? '');
                                        $sku = trim($get('sku') ?? '');

                                        $seen = [];
                                        foreach ($tags as $tag) {
                                            $code = trim($tag);
                                            if (empty($code)) continue;

                                            $lowerCode = strtolower($code);
                                            if (in_array($lowerCode, $seen)) {
                                                $fail("Multi Barcode '{$code}' terduplikasi dalam daftar yang Anda masukkan.");
                                                return;
                                            }
                                            $seen[] = $lowerCode;

                                            if (!empty($primaryBarcode) && strtolower($code) === strtolower($primaryBarcode)) {
                                                $fail("Multi Barcode '{$code}' tidak boleh sama dengan Barcode Utama produk ini.");
                                                return;
                                            }

                                            if (!empty($sku) && strtolower($code) === strtolower($sku)) {
                                                $fail("Multi Barcode '{$code}' tidak boleh sama dengan SKU produk ini.");
                                                return;
                                            }

                                            $exists = \App\Models\Product::where('id', '!=', $recordId)
                                                ->where(function ($q) use ($code) {
                                                    $q->where('barcode', $code)
                                                      ->orWhere('sku', $code)
                                                      ->orWhereJsonContains('metadata->additional_barcodes', $code)
                                                      ->orWhere('metadata->additional_barcodes', 'LIKE', '%' . $code . '%');
                                                })->first();

                                            if ($exists) {
                                                $fail("Multi Barcode '{$code}' sudah digunakan oleh produk '{$exists->name}' (SKU/Barcode/Multi Barcode).");
                                                return;
                                            }
                                        }
                                    };
                                }
                            ])
                            ->disabled($isBranchUser),
                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->columnSpanFull()
                            ->disabled($isBranchUser),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled($isBranchUser),
                        Select::make('sub_category')
                            ->label('Sub Kategori')
                            ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                $options = \App\Models\Product::whereNotNull('sub_category')
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
                            ->createOptionUsing(function (array $data) {
                                return $data['sub_category'];
                            })
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
                        Select::make('ppob_provider')
                            ->label('Provider PPOB')
                            ->options([
                                'digiflazz' => 'Digiflazz',
                                'ama' => 'AMA',
                            ])
                            ->default('digiflazz')
                            ->visible(fn ($get) => $get('product_type') === 'digital')
                            ->disabled($isBranchUser),
                        TextInput::make('ppob_sku')
                            ->label('Kode SKU Provider (PPOB SKU)')
                            ->visible(fn ($get) => $get('product_type') === 'digital')
                            ->helperText('Contoh: xld10 (Lihat daftar harga di Digiflazz atau AMA)')
                            ->disabled($isBranchUser),
                        Select::make('supplier_id')
                            ->relationship(
                                name: 'supplier', 
                                titleAttribute: 'name', 
                                modifyQueryUsing: fn ($query, $record) => $query->where(function ($q) use ($record) {
                                    $q->where('is_active', true);
                                    if ($record && $record->supplier_id) {
                                        $q->orWhere('id', $record->supplier_id);
                                    }
                                })
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('supplier_division_id', null))
                            ->disabled($isBranchUser),
                        Select::make('supplier_division_id')
                            ->label('Sub Divisi Pemasok')
                            ->placeholder('Tanpa Sub Divisi (Pemasok Global)')
                            ->options(function (callable $get) {
                                $supplierId = $get('supplier_id');
                                if (!$supplierId) {
                                    return [];
                                }
                                return \App\Models\SupplierDivision::where('supplier_id', $supplierId)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->disabled($isBranchUser),
                        TextInput::make('weight_in_grams')
                            ->label('Berat (Gram)')
                            ->numeric()
                            ->default(1000)
                            ->helperText('Digunakan untuk menghitung ongkos kirim (misal: 1000 = 1 Kg).')
                            ->disabled($isBranchUser),
                        TextInput::make('unit_of_measure')
                            ->label('Satuan')
                            ->required()
                            ->default('pcs')
                            ->disabled($isBranchUser),
                    ])
                    ->columns(2),

                // ========================================================
                // 2. PARAMETER RANTAI PASOK DSD & BATAS STOK
                // ========================================================
                Section::make('Parameter Rantai Pasok DSD & Batas Stok')
                    ->description('Atur ambang batas peringatan stok menipis, saran pemesanan ulang, dan analisis tren barang.')
                    ->schema([
                        TextInput::make('reorder_point')
                            ->label('Batas Reorder (Min Stok)')
                            ->required()
                            ->numeric()
                            ->default(10)
                            ->helperText('Peringatan otomatis muncul saat stok di bawah angka ini')
                            ->disabled($isBranchUser),
                        TextInput::make('reorder_qty')
                            ->label('Qty Saran Reorder')
                            ->required()
                            ->numeric()
                            ->default(50)
                            ->helperText('Saran jumlah pemesanan kembali ke pemasok')
                            ->disabled($isBranchUser),
                        TextInput::make('lead_time_days')
                            ->label('Lead Time Suplier (Hari)')
                            ->required()
                            ->numeric()
                            ->default(5)
                            ->helperText('Estimasi waktu suplier memproses dan mengirim barang')
                            ->disabled($isBranchUser),

                        Placeholder::make('retail_intelligence_hub')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->content(function ($record) {
                                if (!$record) {
                                    return null;
                                }
                                $widget = new \App\Filament\Resources\Products\Widgets\ProductRetailIntelligenceWidget();
                                $widget->record = $record;
                                return view('filament.products.widgets.product-retail-intelligence', $widget->getViewData());
                            }),

                        Toggle::make('is_taxable')
                            ->label('Kena PPN (11%)')
                            ->default(true)
                            ->required()
                            ->disabled($isBranchUser),
                        Toggle::make('is_active')
                            ->label('Status Aktif di Sistem')
                            ->default(true)
                            ->required()
                            ->disabled($isBranchUser),
                        Toggle::make('is_ecommerce_active')
                            ->label('Tampilkan di Web E-Commerce')
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
                                
                                return \App\Models\Category::where('is_active', true)
                                    ->pluck('name', 'name')
                                    ->toArray();
                            })
                            ->visible(fn ($get) => $get('is_ecommerce_active'))
                            ->placeholder('Pilih Kategori E-Commerce')
                            ->searchable()
                            ->preload()
                            ->columnSpanFull()
                            ->disabled($isBranchUser),
                    ])
                    ->columns(3),

                // ========================================================
                // 4. STRUKTUR HARGA BERTINGKAT & MARGIN
                // ========================================================
                Section::make('Struktur Harga Bertingkat & Margin Multi-Golongan')
                    ->description('Kalkulasi margin otomatis berdasarkan HPP modal faktur terhadap harga jual ecer, grosir, dan partai.')
                    ->columns(1)
                    ->schema([
                        Group::make([
                            TextInput::make('cost_price')
                                ->label('Harga Modal Faktur (HPP)')
                                ->rupiah()
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    $isTaxable = $get('is_taxable');
                                    $taxRate = \App\Services\RetailIntelligenceService::getActiveTaxRate();
                                    $ppn = $isTaxable ? (1 + ($taxRate / 100)) : 1.0;
                                    $costWithTax = round($cost * $ppn, 2);
                                    $set('cost_price_tax', str_replace('.', ',', (string)$costWithTax));
                                    
                                    $margin1 = (float) str_replace(',', '.', str_replace('.', '', (string) $get('margin_gol_1')));
                                    if ($margin1 > 0) {
                                        $price1 = round($costWithTax * (1 + ($margin1 / 100)), 2);
                                        $set('harga_jual_1', str_replace('.', ',', (string)$price1));
                                        $set('selling_price', $price1);
                                    }
                                }),
                            TextInput::make('cost_price_tax')
                                ->label('HPP + PPN (11%)')
                                ->rupiah()
                                ->readOnly()
                                ->helperText('Dihitung otomatis dari HPP + PPN'),
                            Hidden::make('qty_min_gol_1')->default(1),
                            TextInput::make('margin_gol_1')
                                ->label('Margin Gol 1 (Ecer %)')
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $margin = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    $price = round($cost * (1 + ($margin / 100)), 2);
                                    $set('harga_jual_1', str_replace('.', ',', (string)$price));
                                    $set('selling_price', $price);
                                }),
                            TextInput::make('harga_jual_1')
                                ->label('Harga Jual Gol 1 (Eceran)')
                                ->rupiah()
                                ->required()
                                ->live(onBlur: true)
                                ->rules([
                                    fn (\Filament\Schemas\Components\Utilities\Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $cost = (float) str_replace(',', '.', str_replace('.', '', (string) $get('cost_price_tax')));
                                        $price = (float) str_replace(',', '.', str_replace('.', '', (string) $value));
                                        if ($cost > 0 && $price < $cost) {
                                            $fail('Harga Jual tidak boleh lebih kecil dari Harga Beli + PPN.');
                                        }
                                    }
                                ])
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $hpp = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $harga = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    if ($hpp > 0) {
                                        if ($harga > 0) {
                                            $new_margin = round((($harga - $hpp) / $hpp) * 100, 2);
                                            $set('margin_gol_1', str_replace('.', ',', (string)$new_margin));
                                        } else {
                                            $set('margin_gol_1', '0');
                                        }
                                    }
                                    $set('selling_price', $harga);
                                }),
                        ])->columns(4)->columnSpanFull(),

                        Group::make([
                            TextInput::make('qty_min_gol_2')->label('Min Qty Gol 2')->numeric(),
                            TextInput::make('margin_gol_2')->label('Margin Gol 2 (%)')->numeric()->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $margin = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    $price = round($cost * (1 + ($margin / 100)), 2);
                                    $set('harga_jual_2', str_replace('.', ',', (string)$price));
                                }),
                            TextInput::make('harga_jual_2')->label('Harga Jual Gol 2 (Grosir)')->rupiah()->live(onBlur: true)
                                ->rules([
                                    fn (\Filament\Schemas\Components\Utilities\Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if (! $value) return;
                                        $cost = (float) str_replace(',', '.', str_replace('.', '', (string) $get('cost_price_tax')));
                                        $price = (float) str_replace(',', '.', str_replace('.', '', (string) $value));
                                        if ($cost > 0 && $price < $cost) {
                                            $fail('Harga Jual tidak boleh lebih kecil dari Harga Beli + PPN.');
                                        }
                                    }
                                ])
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $hpp = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $harga = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    if ($hpp > 0) {
                                        if ($harga > 0) {
                                            $margin = round((($harga - $hpp) / $hpp) * 100, 2);
                                            $set('margin_gol_2', str_replace('.', ',', (string)$margin));
                                        } else {
                                            $set('margin_gol_2', '0');
                                        }
                                    }
                                }),
                        ])->columns(3)->columnSpanFull(),

                        Group::make([
                            TextInput::make('qty_min_gol_3')->label('Min Qty Gol 3')->numeric(),
                            TextInput::make('margin_gol_3')->label('Margin Gol 3 (%)')->numeric()->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $cost = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $margin = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    $price = round($cost * (1 + ($margin / 100)), 2);
                                    $set('harga_jual_3', str_replace('.', ',', (string)$price));
                                }),
                            TextInput::make('harga_jual_3')->label('Harga Jual Gol 3 (Partai)')->rupiah()->live(onBlur: true)
                                ->rules([
                                    fn (\Filament\Schemas\Components\Utilities\Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if (! $value) return;
                                        $cost = (float) str_replace(',', '.', str_replace('.', '', (string) $get('cost_price_tax')));
                                        $price = (float) str_replace(',', '.', str_replace('.', '', (string) $value));
                                        if ($cost > 0 && $price < $cost) {
                                            $fail('Harga Jual tidak boleh lebih kecil dari Harga Beli + PPN.');
                                        }
                                    }
                                ])
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                    $hpp = (float) str_replace(',', '.', str_replace('.', '', $get('cost_price_tax')));
                                    $harga = (float) str_replace(',', '.', str_replace('.', '', $state));
                                    if ($hpp > 0) {
                                        if ($harga > 0) {
                                            $margin = round((($harga - $hpp) / $hpp) * 100, 2);
                                            $set('margin_gol_3', str_replace('.', ',', (string)$margin));
                                        } else {
                                            $set('margin_gol_3', '0');
                                        }
                                    }
                                }),
                        ])->columns(3)->columnSpanFull(),

                        Hidden::make('selling_price')->default(0)
                    ])
                    ->disabled($isBranchUser),

                // ========================================================
                // 5. AUTO-UNPACKING (KONVERSI PECAH BARANG)
                // ========================================================
                Section::make('Auto-Unpacking (Konversi Pecah Barang)')
                    ->description('Isi jika barang ini bisa dipecah menjadi satuan kecil saat stok satuan tersebut habis di POS kasir.')
                    ->schema([
                        Repeater::make('conversions')
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
                    ])
                    ->collapsed()
                    ->disabled($isBranchUser),
            ]);
    }
}
