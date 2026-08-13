<?php
namespace App\Filament\Exports;
use App\Models\StockAdjustment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StockAdjustmentExporter extends Exporter
{
    protected static ?string $model = StockAdjustment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('adjustment_number')->label('No. Koreksi'),
            ExportColumn::make('adjustment_date')->label('Tgl Koreksi'),
            ExportColumn::make('branch.name')->label('Cabang'),
            ExportColumn::make('adjustmentReason.name')->label('Sifat/Alasan'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('total_value_plus')
                ->label('Total Plus')
                ->state(function ($record) {
                    $type = $record->adjustmentReason ? $record->adjustmentReason->type : '';
                    return strtoupper($type) === 'PLUS' ? $record->total_value : null;
                }),
            ExportColumn::make('total_value_minus')
                ->label('Total Minus')
                ->state(function ($record) {
                    $type = $record->adjustmentReason ? $record->adjustmentReason->type : '';
                    return strtoupper($type) === 'MINUS' ? $record->total_value : null;
                }),
            ExportColumn::make('notes')->label('Catatan'),
        ];
    }
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Koreksi Stok selesai. ' . \Illuminate\Support\Number::format($export->successful_rows) . ' baris berhasil diexport.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . \Illuminate\Support\Number::format($failedRowsCount) . ' baris gagal diexport.';
        }

        return $body;
    }
}
