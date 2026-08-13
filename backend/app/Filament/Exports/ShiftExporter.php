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
                ->state(fn (Shift $record): float => (float) (
                    $record->transactions()->where('is_voided', false)->sum('final_amount')
                    - $record->transactions()->where('is_voided', false)->get()->sum(function ($tx) {
                        $pointPayment = 0.0;
                        if (!empty($tx->payment_details)) {
                            $details = $tx->payment_details;
                            if (is_string($details)) $details = json_decode($details, true);
                            if (is_array($details)) {
                                $pointPayment = (float) collect($details)->where('method', 'POINT')->sum('amount');
                            }
                        } elseif (strtoupper($tx->payment_method) === 'POINT') {
                            $pointPayment = (float) $tx->final_amount;
                        }
                        return $pointPayment;
                    })
                )),
            ExportColumn::make('expected_ending_cash')
                ->label('Kas Harapan')
                ->state(fn (Shift $record): float => 
                    (float) ($record->starting_cash ?? 0) 
                    + (float) ($record->total_cash_sales ?? 0) 
                    - (float) ($record->total_cash_returns ?? 0) 
                    + (float) ($record->total_cash_in ?? 0) 
                    - (float) ($record->total_cash_out ?? 0)
                ),
            ExportColumn::make('actual_cash')->label('Kas Aktual'),
            ExportColumn::make('difference')->label('Selisih'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Laporan Shift Kasir selesai. ' . \Illuminate\Support\Number::format($export->successful_rows) . ' baris berhasil diexport.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . \Illuminate\Support\Number::format($failedRowsCount) . ' baris gagal diexport.';
        }

        return $body;
    }
}
