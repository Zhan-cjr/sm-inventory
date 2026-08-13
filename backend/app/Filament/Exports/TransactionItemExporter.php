<?php

namespace App\Filament\Exports;

use App\Models\TransactionItem;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TransactionItemExporter extends Exporter
{
    protected static ?string $model = TransactionItem::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('transaction.transaction_date')->label('Tanggal'),
            ExportColumn::make('transaction.local_transaction_id')->label('No Transaksi'),
            ExportColumn::make('transaction.branch.name')->label('Cabang'),
            ExportColumn::make('product.sku')->label('SKU'),
            ExportColumn::make('product.name')->label('Nama Barang'),
            ExportColumn::make('quantity')->label('Qty'),
            ExportColumn::make('unit_price')->label('Harga Satuan'),
            ExportColumn::make('discount_per_item')->label('Diskon/Item'),
            ExportColumn::make('subtotal')->label('Subtotal (Net)')->state(fn (TransactionItem $record): float => ($record->unit_price - $record->discount_per_item) * $record->quantity),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Laporan Jasa Terjual selesai. ' . \Illuminate\Support\Number::format($export->successful_rows) . ' baris berhasil diexport.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . \Illuminate\Support\Number::format($failedRowsCount) . ' baris gagal diexport.';
        }

        return $body;
    }
}
