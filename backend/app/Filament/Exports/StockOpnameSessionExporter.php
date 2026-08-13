<?php

namespace App\Filament\Exports;

use App\Models\StockOpnameSession;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class StockOpnameSessionExporter extends Exporter
{
    protected static ?string $model = StockOpnameSession::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('session_number'),
            ExportColumn::make('branch_id'),
            ExportColumn::make('organization_id'),
            ExportColumn::make('opname_date'),
            ExportColumn::make('status'),
            ExportColumn::make('notes'),
            ExportColumn::make('created_by'),
            ExportColumn::make('approved_by'),
            ExportColumn::make('completed_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Ringkas Stok Opname selesai. ' . \Illuminate\Support\Number::format($export->successful_rows) . ' baris berhasil diexport.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . \Illuminate\Support\Number::format($failedRowsCount) . ' baris gagal diexport.';
        }

        return $body;
    }
}
