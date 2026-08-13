<?php
namespace App\Filament\Exports;
use App\Models\GoodsReceipt;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class GoodsReceiptExporter extends Exporter
{
    protected static ?string $model = GoodsReceipt::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('receipt_number')->label('No. Terima'),
            ExportColumn::make('receipt_date')->label('Tgl Terima'),
            ExportColumn::make('purchaseOrder.po_number')->label('No. PO'),
            ExportColumn::make('supplier.name')->label('Supplier'),
            ExportColumn::make('branch.name')->label('Cabang'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('total_amount')->label('Total'),
        ];
    }
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Penerimaan Barang selesai. ' . \Illuminate\Support\Number::format($export->successful_rows) . ' baris berhasil diexport.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . \Illuminate\Support\Number::format($failedRowsCount) . ' baris gagal diexport.';
        }

        return $body;
    }
}
