<?php

namespace App\Filament\Exports;

use App\Models\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LabaRugiExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('local_transaction_id')->label('No Transaksi'),
            ExportColumn::make('transaction_date')->label('Tanggal'),
            ExportColumn::make('branch.name')->label('Cabang'),
            ExportColumn::make('final_amount')->label('Penjualan Bersih'),
            ExportColumn::make('cogs')->label('Total HPP')->state(fn (Transaction $record): float => $record->cogs),
            ExportColumn::make('gross_profit')->label('Laba Kotor')->state(fn (Transaction $record): float => $record->gross_profit),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export completed.';
    }
}
