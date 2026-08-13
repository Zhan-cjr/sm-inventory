<?php

namespace App\Filament\Exports;

use App\Models\AllSalesItem;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class AllSalesItemExporter extends Exporter
{
    protected static ?string $model = AllSalesItem::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('source')->label('Sumber'),
            ExportColumn::make('transaction_date')->label('Tanggal'),
            ExportColumn::make('transaction_number')->label('No Transaksi'),
            ExportColumn::make('branch.name')->label('Cabang'),
            ExportColumn::make('product.sku')->label('SKU'),
            ExportColumn::make('product.barcode')->label('Barcode'),
            ExportColumn::make('product.name')->label('Nama Barang'),
            ExportColumn::make('quantity')->label('Qty'),
            ExportColumn::make('unit_price')->label('Harga Jual'),
            ExportColumn::make('discount_per_item')->label('Diskon/Item'),
            ExportColumn::make('subtotal')->label('Subtotal (Net)'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Laporan Barang Dijual selesai. ' . \Illuminate\Support\Number::format($export->successful_rows) . ' baris berhasil diexport.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . \Illuminate\Support\Number::format($failedRowsCount) . ' baris gagal diexport.';
        }

        return $body;
    }
}
