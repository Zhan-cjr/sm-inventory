<?php

namespace App\Filament\Exports;

use App\Models\StockOpnameItem;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class StockOpnameItemExporter extends Exporter
{
    protected static ?string $model = StockOpnameItem::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('session_id'),
            ExportColumn::make('rack_session_id'),
            ExportColumn::make('product_id'),
            ExportColumn::make('system_quantity'),
            ExportColumn::make('count1_quantity'),
            ExportColumn::make('count1_at'),
            ExportColumn::make('count2_quantity'),
            ExportColumn::make('count2_at'),
            ExportColumn::make('discrepancy_1_2'),
            ExportColumn::make('final_quantity'),
            ExportColumn::make('final_by'),
            ExportColumn::make('final_at'),
            ExportColumn::make('final_notes'),
            ExportColumn::make('status'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your stock opname item export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
