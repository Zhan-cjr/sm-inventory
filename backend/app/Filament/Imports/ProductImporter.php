<?php

namespace App\Filament\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Organization;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;
    
    public static function getOptionsFormComponents(): array
    {
        return [
            \Filament\Forms\Components\Checkbox::make('overwrite')
                ->label('Timpa data yang sudah ada')
                ->helperText('Jika dicentang, data dengan SKU yang sama akan diperbarui dengan data dari file import.'),
        ];
    }
    
    protected static array $orgCache = [];
    protected static array $catCache = [];
    protected static array $supCache = [];


    public static function getColumns(): array
    {
        $numericCaster = function (?string $state): string {
            if (blank($state)) return '0';
            $val = trim((string) $state);
            if (preg_match('/^-?[0-9]+$/', $val)) return $val;
            
            $val = preg_replace('/[^0-9\.,-]/', '', $val);
            if (str_contains($val, ',') && str_contains($val, '.')) {
                if (strrpos($val, ',') > strrpos($val, '.')) {
                    $val = str_replace('.', '', $val);
                    $val = str_replace(',', '.', $val);
                } else {
                    $val = str_replace(',', '', $val);
                }
            } elseif (str_contains($val, ',')) {
                $val = str_replace(',', '.', $val);
            } else {
                if (preg_match('/^-?[0-9]+\.[0-9]{3}$/', $val)) {
                    $val = str_replace('.', '', $val);
                }
            }
            return $val;
        };

        $integerCaster = function (?string $state) use ($numericCaster): int {
            $val = $numericCaster($state);
            if ($val === null || $val === '') return 0;
            return (int) round((float) $val);
        };

        $booleanCaster = function (?string $state): bool {
            if (blank($state)) return false;
            $val = strtolower(trim($state));
            if (in_array($val, ['1', 'true', 'yes', 'y', 'ya', 'aktif', 'on'])) return true;
            if (in_array($val, ['0', 'false', 'no', 'n', 'tidak', 'nonaktif', 'off', 'non aktif'])) return false;
            return (bool) $val;
        };

        $booleanCasterTrue = function (?string $state): bool {
            if (blank($state)) return true;
            $val = strtolower(trim($state));
            if (in_array($val, ['1', 'true', 'yes', 'y', 'ya', 'aktif', 'on'])) return true;
            if (in_array($val, ['0', 'false', 'no', 'n', 'tidak', 'nonaktif', 'off', 'non aktif'])) return false;
            return (bool) $val;
        };

        return [
            ImportColumn::make('organization_id')
                ->label('ID Organisasi')
                ->requiredMapping()
                ->example('ORG-001')
                ->fillRecordUsing(function (Product $record, ?string $state): void {
                    if (!$state) return;
                    if (!isset(static::$orgCache[$state])) {
                        static::$orgCache[$state] = Organization::where('id', $state)->orWhere('code', $state)->value('id');
                    }
                    $record->organization_id = static::$orgCache[$state] ?? $state;
                }),

            ImportColumn::make('sku')
                ->label('SKU')
                ->requiredMapping()
                ->example('SKU-001')
                ->rules(['required', 'string', 'max:100']),

            ImportColumn::make('barcode')
                ->label('Barcode')
                ->example('899123456789')
                ->rules(['nullable', 'string', 'max:100']),

            ImportColumn::make('name')
                ->label('Nama Produk')
                ->requiredMapping()
                ->example('Indomie Goreng')
                ->rules(['required', 'string', 'max:255']),

            // Set null dulu agar tidak melanggar FK; UUID-nya diset di beforeSave()
            ImportColumn::make('category_id')
                ->label('Kategori (ID/Kode/Nama)')
                ->example('Makanan')
                ->fillRecordUsing(function (Product $record): void {
                    $record->category_id = null;
                }),

            ImportColumn::make('sub_category')
                ->label('Sub Kategori')
                ->example('Minuman Ringan')
                ->rules(['nullable', 'string', 'max:255']),

            // Set null dulu agar tidak melanggar FK; UUID-nya diset di beforeSave()
            ImportColumn::make('supplier_id')
                ->label('Pemasok (ID/Kode/Nama)')
                ->example('PT Indofood')
                ->fillRecordUsing(function (Product $record): void {
                    $record->supplier_id = null;
                }),

            ImportColumn::make('cost_price')
                ->label('Harga Modal (HPP)')
                ->requiredMapping()
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('2500')
                ->rules(['required', 'numeric', 'min:0'])
                ->fillRecordUsing(fn ($record, $state) => $record->cost_price = $state ?? 0),

            ImportColumn::make('cost_price_tax')
                ->label('Harga Beli + PPN')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('2775')
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('qty_min_gol_1')
                ->label('Min Qty Gol 1')
                ->numeric()
                ->castStateUsing($integerCaster)
                ->example('1')
                ->fillRecordUsing(fn ($record, $state) => $record->qty_min_gol_1 = $state ?? 1)
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('harga_jual_1')
                ->label('Harga Jual Gol 1')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('3330')
                ->fillRecordUsing(fn ($record, $state) => $record->harga_jual_1 = $state ?? 0)
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('qty_min_gol_2')
                ->label('Min Qty Gol 2')
                ->numeric()
                ->castStateUsing($integerCaster)
                ->example('12')
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('harga_jual_2')
                ->label('Harga Jual Gol 2')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('3191')
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('qty_min_gol_3')
                ->label('Min Qty Gol 3')
                ->numeric()
                ->castStateUsing($integerCaster)
                ->example('50')
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('harga_jual_3')
                ->label('Harga Jual Gol 3')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('3052')
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('selling_price')
                ->label('Harga Jual (Default)')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('3000')
                ->fillRecordUsing(fn ($record, $state) => $record->selling_price = $state ?? 0)
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('unit_of_measure')
                ->label('Satuan')
                ->example('pcs')
                ->rules(['nullable', 'string', 'max:50']),

            ImportColumn::make('reorder_point')
                ->label('Titik Pesan Ulang')
                ->integer()
                ->castStateUsing($integerCaster)
                ->example('10')
                ->fillRecordUsing(fn ($record, $state) => $record->reorder_point = $state ?? 10)
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('reorder_qty')
                ->label('Jumlah Pesan Ulang')
                ->integer()
                ->castStateUsing($integerCaster)
                ->example('50')
                ->fillRecordUsing(fn ($record, $state) => $record->reorder_qty = $state ?? 50)
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('lead_time_days')
                ->label('Lead Time (Hari)')
                ->integer()
                ->castStateUsing($integerCaster)
                ->example('2')
                ->fillRecordUsing(fn ($record, $state) => $record->lead_time_days = $state ?? 5)
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('is_active')
                ->label('Aktif (1/0)')
                ->boolean()
                ->castStateUsing($booleanCasterTrue)
                ->example('1')
                ->rules(['nullable', 'boolean'])
                ->fillRecordUsing(fn ($record, $state) => $record->is_active = $state ?? true),

            ImportColumn::make('is_taxable')
                ->label('Kena PPN (1/0)')
                ->boolean()
                ->castStateUsing($booleanCaster)
                ->example('1')
                ->rules(['nullable', 'boolean'])
                ->fillRecordUsing(fn ($record, $state) => $record->is_taxable = $state ?? false),

            ImportColumn::make('is_ecommerce_active')
                ->label('Tampil di E-Commerce (1/0)')
                ->boolean()
                ->castStateUsing($booleanCaster)
                ->example('0')
                ->rules(['nullable', 'boolean'])
                ->fillRecordUsing(fn ($record, $state) => $record->is_ecommerce_active = $state ?? false),

            ImportColumn::make('ecommerce_category')
                ->label('Kategori E-Commerce')
                ->example('Minuman')
                ->rules(['nullable', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Product
    {
        $orgState = $this->data['organization_id'];
        if (!isset(static::$orgCache[$orgState])) {
            static::$orgCache[$orgState] = Organization::where('id', $orgState)
                ->orWhere('code', $orgState)
                ->value('id');
        }
        
        $resolvedOrgId = static::$orgCache[$orgState];

        if (!$resolvedOrgId) return null;

        $record = Product::firstOrNew([
            'organization_id' => $resolvedOrgId,
            'sku' => $this->data['sku'],
        ]);
        
        if ($record->exists && ! ($this->options['overwrite'] ?? false)) {
            throw new \Exception('Data sudah ada. Centang opsi "Timpa data" untuk memperbarui.');
        }
        
        return $record;
    }

    /**
     * Dijalankan setelah semua kolom diisi ke $record.
     * Pada titik ini organization_id sudah pasti terisi sehingga aman
     * untuk membuat kategori/supplier baru jika belum ada.
     */
    protected function beforeSave(): void
    {
        $orgId = $this->record->organization_id;

        // Baca nilai mentah dari data CSV asli (tersimpan di $this->data)
        $catRaw = $this->data['category_id'] ?? null;
        if ($catRaw && $orgId) {
            $cacheKey = $orgId . '|' . $catRaw;
            if (!isset(static::$catCache[$cacheKey])) {
                $cat = Category::where('organization_id', $orgId)
                    ->where(function ($q) use ($catRaw) {
                        $q->where('id', $catRaw)
                          ->orWhere('code', $catRaw)
                          ->orWhere('name', $catRaw);
                    })->first();

                if (!$cat) {
                    $cat = Category::create([
                        'organization_id' => $orgId,
                        'name'            => $catRaw,
                        'is_active'       => true,
                    ]);
                }
                static::$catCache[$cacheKey] = $cat->id;
            }
            $this->record->category_id = static::$catCache[$cacheKey];
        } else {
            $this->record->category_id = null;
        }

        // Baca nilai mentah dari data CSV asli (tersimpan di $this->data)
        $supRaw = $this->data['supplier_id'] ?? null;
        if ($supRaw && $orgId) {
            $cacheKey = $orgId . '|' . $supRaw;
            if (!isset(static::$supCache[$cacheKey])) {
                $sup = Supplier::where('organization_id', $orgId)
                    ->where(function ($q) use ($supRaw) {
                        $q->where('id', $supRaw)
                          ->orWhere('code', $supRaw)
                          ->orWhere('name', $supRaw);
                    })->first();

                if (!$sup) {
                    $sup = Supplier::create([
                        'organization_id' => $orgId,
                        'name'            => $supRaw,
                        'is_active'       => true,
                    ]);
                }
                static::$supCache[$cacheKey] = $sup->id;
            }
            $this->record->supplier_id = static::$supCache[$cacheKey];
        } else {
            $this->record->supplier_id = null;
        }

        // Auto-calculate margin
        $cost = $this->record->cost_price_tax > 0 ? (float) $this->record->cost_price_tax : (float) $this->record->cost_price;
        if ($cost > 0) {
            if ($this->record->harga_jual_1 > 0) {
                $this->record->margin_gol_1 = round((((float) $this->record->harga_jual_1 - $cost) / $cost) * 100, 2);
            }
            if ($this->record->harga_jual_2 > 0) {
                $this->record->margin_gol_2 = round((((float) $this->record->harga_jual_2 - $cost) / $cost) * 100, 2);
            }
            if ($this->record->harga_jual_3 > 0) {
                $this->record->margin_gol_3 = round((((float) $this->record->harga_jual_3 - $cost) / $cost) * 100, 2);
            }
        }

        if (empty($this->record->selling_price) && $this->record->harga_jual_1 > 0) {
            $this->record->selling_price = $this->record->harga_jual_1;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import produk selesai. ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
