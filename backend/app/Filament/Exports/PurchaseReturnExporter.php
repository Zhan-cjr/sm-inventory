<?php
namespace App\Filament\Exports;
use App\Models\PurchaseReturn;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PurchaseReturnExporter extends Exporter
{
    protected static ?string $model = PurchaseReturn::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('return_number')->label('No. Retur'),
            ExportColumn::make('return_date')->label('Tgl Retur'),
            ExportColumn::make('supplier.name')->label('Supplier'),
            ExportColumn::make('branch.name')->label('Cabang'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('total_amount')->label('Total'),
        ];
    }
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Retur Pembelian selesai. ' . \Illuminate\Support\Number::format($export->successful_rows) . ' baris berhasil diexport.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . \Illuminate\Support\Number::format($failedRowsCount) . ' baris gagal diexport.';
        }

        return $body;
    }
}
