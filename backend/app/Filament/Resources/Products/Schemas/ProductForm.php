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
                    ->default(function () {
                        $prefix = 'SKU-' . date('dmy');
                        $lastProduct = \App\Models\Product::where('sku', 'like', $prefix . '%')
                            ->orderBy('sku', 'desc')
                            ->first();
                        
                        if ($lastProduct) {
                            // Extract the last 4 digits
                            $lastNumber = (int) substr($lastProduct->sku, -4);
                            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                            return $prefix . $newNumber;
                        }
                        
                        return $prefix . '0001';
                    })
                    ->readOnly(fn (string $operation): bool => $operation === 'edit')
                    ->disabled($isBranchUser),
                TextInput::make('barcode')
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
                                    $fail("Barcode tidak boleh sama dengan SKU produk ini.");
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
                \Filament\Forms\Components\TagsInput::make('metadata.additional_barcodes')
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
                    ->required()
                    ->disabled($isBranchUser),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled($isBranchUser),
                Select::make('sub_category')
                    ->label('Sub Kategori')
                    ->options(function () {
                        return \App\Models\Product::whereNotNull('sub_category')
                            ->where('sub_category', '!=', '')
                            ->distinct()
                            ->pluck('sub_category', 'sub_category')
                            ->toArray();
                    })
                    ->searchable()
                    ->createOptionForm([
                        \Filament\Forms\Components\TextInput::make('sub_category')
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
                TextInput::make('weight_in_grams')
                    ->label('Berat (Gram)')
                    ->numeric()
                    ->default(1000)
                    ->helperText('Berat produk dalam satuan gram (Contoh: 1000 untuk 1 Kg). Digunakan untuk menghitung ongkos kirim.')
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
                                    $price1 = (float) str_replace(',', '.', str_replace('.', '', (string) $get('harga_jual_1')));
                                    $set('selling_price', $price1);
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
                                            $new_margin = round((($harga - $hpp) / $hpp) * 100, 2);
                                            $set("margin_gol_1", str_replace('.', ',', (string)$new_margin));
                                        } else {
                                            $set("margin_gol_1", "0");
                                        }
                                    }
                                    $set('selling_price', $harga);
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
