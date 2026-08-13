<?php
namespace App\Filament\Exports;
use App\Models\StockTransfer;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StockTransferExporter extends Exporter
{
    protected static ?string $model = StockTransfer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('transfer_number')->label('No. Transfer'),
            ExportColumn::make('transfer_date')->label('Tgl Transfer'),
            ExportColumn::make('sourceBranch.name')->label('Cabang Asal'),
            ExportColumn::make('destinationBranch.name')->label('Cabang Tujuan'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('total_amount')->label('Total'),
        ];
    }
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Stock Transfer selesai. ' . \Illuminate\Support\Number::format($export->successful_rows) . ' baris berhasil diexport.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . \Illuminate\Support\Number::format($failedRowsCount) . ' baris gagal diexport.';
        }

        return $body;
    }
}
