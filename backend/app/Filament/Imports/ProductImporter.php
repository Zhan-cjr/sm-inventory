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

            ImportColumn::make('category_id')
                ->label('Kategori (ID/Kode/Nama)')
                ->example('Makanan')
                ->fillRecordUsing(function (Product $record, ?string $state): void {
                    if (!$state) return;
                    if (!isset(static::$catCache[$state])) {
                        static::$catCache[$state] = Category::where('id', $state)
                            ->orWhere('code', $state)
                            ->orWhere('name', $state)
                            ->value('id');
                    }
                    $record->category_id = static::$catCache[$state];
                }),

            ImportColumn::make('sub_category')
                ->label('Sub Kategori')
                ->example('Minuman Ringan')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('supplier_id')
                ->label('Pemasok (ID/Kode/Nama)')
                ->example('PT Indofood')
                ->fillRecordUsing(function (Product $record, ?string $state): void {
                    if (!$state) return;
                    if (!isset(static::$supCache[$state])) {
                        static::$supCache[$state] = Supplier::where('id', $state)
                            ->orWhere('code', $state)
                            ->orWhere('name', $state)
                            ->value('id');
                    }
                    $record->supplier_id = static::$supCache[$state];
                }),

            ImportColumn::make('cost_price')
                ->label('Harga Beli')
                ->requiredMapping()
                ->numeric()
                ->example('2500')
                ->rules(['required', 'numeric', 'min:0']),

            ImportColumn::make('selling_price')
                ->label('Harga Jual')
                ->requiredMapping()
                ->numeric()
                ->example('3000')
                ->rules(['required', 'numeric', 'min:0']),

            ImportColumn::make('unit_of_measure')
                ->label('Satuan')
                ->example('pcs')
                ->rules(['nullable', 'string', 'max:50']),

            ImportColumn::make('reorder_point')
                ->label('Titik Pesan Ulang')
                ->integer()
                ->example('10')
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('reorder_qty')
                ->label('Jumlah Pesan Ulang')
                ->integer()
                ->example('50')
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('lead_time_days')
                ->label('Lead Time (Hari)')
                ->integer()
                ->example('2')
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('is_active')
                ->label('Aktif (1/0)')
                ->boolean()
                ->example('1')
                ->rules(['nullable', 'boolean']),

            ImportColumn::make('is_taxable')
                ->label('Kena PPN (1/0)')
                ->boolean()
                ->example('1')
                ->rules(['nullable', 'boolean']),

            ImportColumn::make('is_ecommerce_active')
                ->label('Tampil di E-Commerce (1/0)')
                ->boolean()
                ->example('0')
                ->rules(['nullable', 'boolean']),

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

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import produk selesai. ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
