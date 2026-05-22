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
                            ->value('id');
                    }
                    $record->product_id = static::$productCache[$state] ?? $state;
                }),
            ImportColumn::make('cost_price')
                ->label('Harga Beli Cabang')
                ->numeric()
                ->example('25000')
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('selling_price')
                ->label('Harga Jual Cabang')
                ->numeric()
                ->example('30000')
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('quantity_on_hand')
                ->label('Stok Saat Ini')
                ->requiredMapping()
                ->numeric()
                ->example('100')
                ->rules(['required', 'integer']),
            ImportColumn::make('min_qty')
                ->label('Min Stok')
                ->numeric()
                ->example('10')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('max_qty')
                ->label('Max Stok')
                ->numeric()
                ->example('500')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('lead_time')
                ->label('Lead Time (Hari)')
                ->numeric()
                ->example('3')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('safety_stock')
                ->label('Safety Stock')
                ->numeric()
                ->example('10')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('desired_inventory_days')
                ->label('Target Hari Persediaan')
                ->numeric()
                ->example('14')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('rack')
                ->label('Rak (Nama/Kode)')
                ->example('Rak Depan A1')
                ->rules(['nullable', 'string', 'max:255']),
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
