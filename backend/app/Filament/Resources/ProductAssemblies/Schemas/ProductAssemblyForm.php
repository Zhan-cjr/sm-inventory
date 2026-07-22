<?php

namespace App\Filament\Resources\ProductAssemblies\Schemas;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ProductAssemblyForm
{
    public static function schema(): array
    {
        return [
            // ─── Identitas Produk Paket ─────────────────────────────────────────
            Section::make('Identitas Produk Paket')
                ->description('Data produk yang akan dijual sebagai paket/bundling.')
                ->columns(2)
                ->schema([
                    TextInput::make('sku')
                        ->label('SKU Paket')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->validationMessages(['unique' => 'SKU ini sudah digunakan.']),

                    TextInput::make('name')
                        ->label('Nama Paket')
                        ->required(),

                    Select::make('organization_id')
                        ->relationship('organization', 'name')
                        ->required()
                        ->default(fn () => \Illuminate\Support\Facades\Auth::user()->organization_id)
                        ->disabled(fn () => \Illuminate\Support\Facades\Auth::user()->organization_id !== null)
                        ->dehydrated(),

                    Select::make('category_id')
                        ->relationship('category', 'name')
                        ->label('Kategori')
                        ->searchable()
                        ->preload(),

                    TextInput::make('unit_of_measure')
                        ->label('Satuan')
                        ->required()
                        ->default('pcs'),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),

                    Toggle::make('is_taxable')
                        ->label('Kena PPN')
                        ->default(false),

                    // Fields wajib Product tapi tidak relevan untuk paket — disembunyikan
                    Hidden::make('product_type')->default('physical'),
                    Hidden::make('reorder_point')->default(0),
                    Hidden::make('reorder_qty')->default(0),
                    Hidden::make('lead_time_days')->default(0),
                    Hidden::make('selling_price')->default(0),
                    Hidden::make('cost_price')->default(0),
                    Hidden::make('cost_price_tax')->default(0),
                    Hidden::make('harga_jual_1')->default(0),
                    Hidden::make('margin_gol_1')->default(0),
                    Hidden::make('qty_min_gol_1')->default(1),
                    Hidden::make('qty_min_gol_2')->default(0),
                    Hidden::make('qty_min_gol_3')->default(0),
                    Hidden::make('harga_jual_2')->default(0),
                    Hidden::make('harga_jual_3')->default(0),
                    Hidden::make('margin_gol_2')->default(0),
                    Hidden::make('margin_gol_3')->default(0),
                ]),

            // ─── Komponen Paket (berlaku untuk semua cabang) ────────────────────
            Section::make('Komponen Paket')
                ->description('Daftarkan produk-produk yang menjadi isi dari paket ini. HPP total dihitung otomatis sebagai referensi penetapan harga per cabang.')
                ->schema([
                    Repeater::make('assemblies')
                        ->relationship()
                        ->label('Daftar Komponen')
                        ->schema([
                            Select::make('child_product_id')
                                ->label('Produk Komponen')
                                ->options(fn () => Product::where('product_type', 'physical')
                                    ->orderBy('name')
                                    ->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                    if (!$state) return;
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('unit_cost_tax', number_format((float) $product->cost_price_tax, 0, ',', '.'));
                                        $set('unit_name', $product->unit_of_measure ?? 'pcs');
                                    }
                                })
                                ->columnSpan(3),

                            TextInput::make('quantity')
                                ->label('Qty')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(0.01)
                                ->live(onBlur: true)
                                ->columnSpan(1),

                            TextInput::make('unit_cost_tax')
                                ->label('HPP+PPN / satuan')
                                ->prefix('Rp')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(2),

                            TextInput::make('unit_name')
                                ->label('Satuan')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(1),
                        ])
                        ->columns(7)
                        ->defaultItems(1)
                        ->addActionLabel('+ Tambah Komponen')
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            $total = self::hitungTotalHppTax($state ?? []);
                            $set('_total_hpp_tax', $total);
                            $set('_total_hpp', $total > 0 ? round($total / 1.11, 2) : 0);
                        })
                        ->columnSpanFull(),

                    // Summary HPP org-level, informatif saja
                    TextInput::make('_total_hpp')
                        ->label('Total HPP Komponen (tanpa PPN)')
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Gunakan angka ini sebagai referensi pengisian HPP di setiap cabang di bawah.'),

                    TextInput::make('_total_hpp_tax')
                        ->label('Total HPP Komponen (+PPN 11%)')
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            // ─── Harga Per Cabang ───────────────────────────────────────────────
            Section::make('Harga Per Cabang')
                ->description(
                    'Atur HPP dan harga jual paket untuk setiap cabang secara individual. ' .
                    'Data ini disimpan langsung ke tabel stok masing-masing cabang, bukan ke tabel produk.'
                )
                ->schema([
                    Repeater::make('branch_prices')
                        ->label('Daftar Harga Cabang')
                        ->schema([
                            Select::make('branch_id')
                                ->label('Cabang')
                                ->options(fn () => Branch::orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->columnSpan(2),

                            TextInput::make('cost_price_tax')
                                ->label('HPP+PPN (Rp)')
                                ->prefix('Rp')
                                ->numeric()
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, Get $get, $hpp) {
                                    $margin = (float) ($get('margin_gol_1') ?? 20);
                                    $hpp = (float) $hpp;
                                    if ($hpp > 0) {
                                        $set('harga_jual_1', round($hpp * (1 + $margin / 100), 0));
                                    }
                                })
                                ->helperText('Bisa berbeda antar cabang.')
                                ->columnSpan(2),

                            TextInput::make('margin_gol_1')
                                ->label('Margin (%)')
                                ->numeric()
                                ->default(20)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, Get $get, $margin) {
                                    $hpp = (float) ($get('cost_price_tax') ?? 0);
                                    if ($hpp > 0) {
                                        $set('harga_jual_1', round($hpp * (1 + (float)$margin / 100), 0));
                                    }
                                })
                                ->columnSpan(2),

                            TextInput::make('harga_jual_1')
                                ->label('Harga Jual (Rp)')
                                ->prefix('Rp')
                                ->numeric()
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, Get $get, $harga) {
                                    $hpp = (float) ($get('cost_price_tax') ?? 0);
                                    $harga = (float) $harga;
                                    if ($hpp > 0) {
                                        if ($harga > 0) {
                                            $set('margin_gol_1', round((($harga - $hpp) / $hpp) * 100, 2));
                                        } else {
                                            $set('margin_gol_1', 0);
                                        }
                                    }
                                })
                                ->columnSpan(2),
                        ])
                        ->columns(8)
                        ->defaultItems(0)
                        ->addActionLabel('+ Tambah Harga Cabang')
                        ->dehydrated(false) // Tidak disimpan otomatis — diproses manual di halaman Create/Edit
                        ->columnSpanFull(),
                ]),
        ];
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Helper Methods
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Hitung total HPP+PPN dari array komponen repeater.
     */
    public static function hitungTotalHppTax(array $components): float
    {
        $total = 0.0;
        foreach ($components as $row) {
            $childId = $row['child_product_id'] ?? null;
            $qty     = (float) ($row['quantity'] ?? 0);
            if ($childId && $qty > 0) {
                $product = Product::find($childId);
                if ($product) {
                    $total += ((float) $product->cost_price_tax) * $qty;
                }
            }
        }
        return round($total, 2);
    }

    /**
     * Simpan/update harga paket per cabang ke tabel stocks.
     * Dipanggil dari Create/Edit page setelah record disimpan.
     */
    public static function syncBranchPrices(Product $product, array $branchPrices): void
    {
        foreach ($branchPrices as $row) {
            $branchId = $row['branch_id'] ?? null;
            if (!$branchId) continue;

            $hppTax    = (float) ($row['cost_price_tax'] ?? 0);
            $hppBersih = $hppTax > 0 ? round($hppTax / 1.11, 2) : 0;
            $hargaJual = (float) ($row['harga_jual_1'] ?? 0);
            $margin    = (float) ($row['margin_gol_1'] ?? 0);

            Stock::updateOrCreate(
                [
                    'branch_id'  => $branchId,
                    'product_id' => $product->id,
                ],
                [
                    'cost_price'     => $hppBersih,
                    'cost_price_tax' => $hppTax,
                    'selling_price'  => $hargaJual,
                    'harga_jual_1'   => $hargaJual,
                    'margin_gol_1'   => $margin,
                    'qty_min_gol_1'  => 1,
                    // Tidak overwrite quantity_on_hand agar stok fisik tidak terganggu
                ]
            );
        }
    }

    /**
     * Muat data harga per cabang dari tabel stocks untuk ditampilkan di form Edit.
     */
    public static function loadBranchPrices(Product $product): array
    {
        return Stock::where('product_id', $product->id)
            ->get()
            ->map(fn (Stock $s) => [
                'branch_id'      => $s->branch_id,
                'cost_price_tax' => (float) $s->cost_price_tax,
                'margin_gol_1'   => (float) $s->margin_gol_1,
                'harga_jual_1'   => (float) $s->harga_jual_1,
            ])
            ->toArray();
    }
}
