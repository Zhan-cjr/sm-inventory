<?php

namespace App\Filament\Imports;

use App\Models\Stock;
use App\Models\Branch;
use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class StockImporter extends Importer
{
    protected static ?string $model = Stock::class;

    public static function getOptionsFormComponents(): array
    {
        return [
            \Filament\Forms\Components\Checkbox::make('overwrite')
                ->label('Timpa data yang sudah ada')
                ->helperText('Jika dicentang, data stok untuk cabang & produk yang sama akan diperbarui.'),
        ];
    }

    protected static array $branchCache = [];
    protected static array $productCache = [];

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

        return [
            ImportColumn::make('branch_id')
                ->label('Cabang (ID/Kode/Nama)')
                ->requiredMapping()
                ->example('SMI')
                ->fillRecordUsing(function (Stock $record, ?string $state): void {
                    if (!$state) return;
                    if (!isset(static::$branchCache[$state])) {
                        static::$branchCache[$state] = Branch::where('id', $state)
                            ->orWhere('code', $state)
                            ->orWhere('name', $state)
                            ->value('id');
                    }
                    $record->branch_id = static::$branchCache[$state] ?? $state;
                }),
            ImportColumn::make('product_id')
                ->label('Produk (SKU/Barcode/ID)')
                ->requiredMapping()
                ->example('SKU-001')
                ->fillRecordUsing(function (Stock $record, ?string $state): void {
                    if (!$state) return;
                    if (!isset(static::$productCache[$state])) {
                        static::$productCache[$state] = Product::where('sku', $state)
                            ->orWhere('barcode', $state)
                            ->orWhere('id', $state)
                            ->orWhereJsonContains('metadata->additional_barcodes', $state)
                            ->value('id');
                    }
                    $record->product_id = static::$productCache[$state] ?? $state;
                }),
            ImportColumn::make('cost_price')
                ->label('Harga Modal Cabang')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('25000')
                ->rules(['nullable', 'numeric']),

            ImportColumn::make('cost_price_tax')
                ->label('Harga Modal + PPN Cabang')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('27750')
                ->rules(['nullable', 'numeric']),

            ImportColumn::make('qty_min_gol_1')
                ->label('Min Qty Gol 1 Cabang')
                ->numeric()
                ->castStateUsing($integerCaster)
                ->example('1')
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('harga_jual_1')
                ->label('Harga Jual Gol 1 Cabang')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('33300')
                ->rules(['nullable', 'numeric']),

            ImportColumn::make('qty_min_gol_2')
                ->label('Min Qty Gol 2 Cabang')
                ->numeric()
                ->castStateUsing($integerCaster)
                ->example('12')
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('harga_jual_2')
                ->label('Harga Jual Gol 2 Cabang')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('31910')
                ->rules(['nullable', 'numeric']),

            ImportColumn::make('qty_min_gol_3')
                ->label('Min Qty Gol 3 Cabang')
                ->numeric()
                ->castStateUsing($integerCaster)
                ->example('50')
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('harga_jual_3')
                ->label('Harga Jual Gol 3 Cabang')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('30520')
                ->rules(['nullable', 'numeric']),

            ImportColumn::make('selling_price')
                ->label('Harga Jual Cabang (Default)')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('30000')
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('quantity_on_hand')
                ->label('Stok Saat Ini')
                ->requiredMapping()
                ->numeric()
                ->castStateUsing($integerCaster)
                ->example('100')
                ->rules(['required', 'integer']),

            ImportColumn::make('min_qty')
                ->label('Min Stok')
                ->numeric()
                ->castStateUsing($integerCaster)
                ->example('10')
                ->rules(['nullable', 'integer'])
                ->fillRecordUsing(fn ($record, $state) => $record->min_qty = $state ?? 0),
            ImportColumn::make('max_qty')
                ->label('Max Stok')
                ->numeric()
                ->castStateUsing($integerCaster)
                ->example('500')
                ->rules(['nullable', 'integer'])
                ->fillRecordUsing(fn ($record, $state) => $record->max_qty = $state ?? 0),
            ImportColumn::make('lead_time')
                ->label('Lead Time (Hari)')
                ->numeric()
                ->castStateUsing($integerCaster)
                ->example('3')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('safety_stock')
                ->label('Safety Stock')
                ->numeric()
                ->castStateUsing($integerCaster)
                ->example('10')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('desired_inventory_days')
                ->label('Target Hari Persediaan')
                ->numeric()
                ->castStateUsing($integerCaster)
                ->example('14')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('rack')
                ->label('Rak (Nama/Kode)')
                ->example('Rak Depan A1')
                ->rules(['nullable', 'string', 'max:255'])
                ->fillRecordUsing(function (Stock $record, ?string $state): void {
                    // Kolom 'rack' tidak ada di tabel stocks.
                    // Nilai ini dipakai di afterSave() untuk relasi StockOpnameRack.
                    // Sengaja dikosongkan agar tidak ada SQL error.
                }),
        ];
    }

    public function resolveRecord(): ?Stock
    {
        $branchState = $this->data['branch_id'] ?? null;
        $productState = $this->data['product_id'] ?? null;

        if (!$branchState || !$productState) return null;

        if (!isset(static::$branchCache[$branchState])) {
            static::$branchCache[$branchState] = Branch::where('id', $branchState)
                ->orWhere('code', $branchState)
                ->orWhere('name', $branchState)
                ->value('id');
        }
        $branchId = static::$branchCache[$branchState];

        if (!isset(static::$productCache[$productState])) {
            static::$productCache[$productState] = Product::where('sku', $productState)
                ->orWhere('barcode', $productState)
                ->orWhere('id', $productState)
                ->orWhereJsonContains('metadata->additional_barcodes', $productState)
                ->value('id');
        }
        $productId = static::$productCache[$productState];
        
        if (!$branchId || !$productId) return null;

        $record = Stock::firstOrNew([
            'branch_id' => $branchId,
            'product_id' => $productId,
        ]);
        
        if ($record->exists && ! ($this->options['overwrite'] ?? false)) {
            throw new \Exception('Data sudah ada. Centang opsi "Timpa data" untuk memperbarui.');
        }
        
        return $record;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import stok selesai. ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }

    protected function beforeSave(): void
    {
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

    protected function afterSave(): void
    {
        $rackState = $this->data['rack'] ?? null;
        if (!$rackState || !$this->record) return;
        
        $branchId = $this->record->branch_id;
        
        $rack = \App\Models\StockOpnameRack::firstOrCreate(
            ['branch_id' => $branchId, 'rack_name' => $rackState],
            ['rack_code' => \Illuminate\Support\Str::slug($rackState), 'is_active' => true]
        );
        
        $this->record->racks()->syncWithoutDetaching([$rack->id]);
    }
}
