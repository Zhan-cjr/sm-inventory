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
            ExportColumn::make('final_amount')
                ->label('Penjualan Bersih')
                ->state(function (Transaction $record) {
                    $pointPayment = 0.0;
                    if (!empty($record->payment_details)) {
                        $details = $record->payment_details;
                        if (is_string($details)) $details = json_decode($details, true);
                        if (is_array($details)) {
                            $pointPayment = (float) collect($details)->where('method', 'POINT')->sum('amount');
                        }
                    } elseif (strtoupper($record->payment_method) === 'POINT') {
                        $pointPayment = (float) $record->final_amount;
                    }
                    return $record->final_amount - $pointPayment;
                }),
            ExportColumn::make('cogs')->label('Total HPP')->state(fn (Transaction $record): float => $record->cogs),
            ExportColumn::make('gross_profit')
                ->label('Laba Kotor')
                ->state(function (Transaction $record) {
                    $pointPayment = 0.0;
                    if (!empty($record->payment_details)) {
                        $details = $record->payment_details;
                        if (is_string($details)) $details = json_decode($details, true);
                        if (is_array($details)) {
                            $pointPayment = (float) collect($details)->where('method', 'POINT')->sum('amount');
                        }
                    } elseif (strtoupper($record->payment_method) === 'POINT') {
                        $pointPayment = (float) $record->final_amount;
                    }
                    return ($record->final_amount - $pointPayment) - $record->cogs;
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export completed.';
    }
}
