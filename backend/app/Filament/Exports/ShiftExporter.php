<?php

namespace App\Filament\Exports;

use App\Models\Shift;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ShiftExporter extends Exporter
{
    protected static ?string $model = Shift::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('branch.name')->label('Cabang'),
            ExportColumn::make('terminal.name')->label('Kassa'),
            ExportColumn::make('user.name')->label('Kasir'),
            ExportColumn::make('start_time')->label('Mulai'),
            ExportColumn::make('end_time')->label('Selesai'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('starting_cash')->label('Kas Awal'),
            ExportColumn::make('total_sales')
                ->label('Pendapatan')
                ->state(fn (Shift $record): float => ($record->total_cash_sales ?? 0) + ($record->total_card_sales ?? 0)),
            ExportColumn::make('expected_ending_cash')
                ->label('Kas Harapan')
                ->state(fn (Shift $record): float => ($record->starting_cash ?? 0) + ($record->total_cash_sales ?? 0)),
            ExportColumn::make('actual_cash')->label('Kas Aktual'),
            ExportColumn::make('difference')->label('Selisih'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export completed.';
    }
}
