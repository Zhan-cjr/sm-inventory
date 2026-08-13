<?php

namespace App\Filament\Exports;

use App\Models\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('local_transaction_id')->label('No Transaksi'),
            ExportColumn::make('branch.name')->label('Cabang'),
            ExportColumn::make('cashier.name')->label('Kasir'),
            ExportColumn::make('transaction_date')->label('Tanggal')->formatStateUsing(fn ($state) => $state->format('Y-m-d H:i')),
            ExportColumn::make('total_amount')->label('Total'),
            ExportColumn::make('discount_amount')->label('Diskon'),
            ExportColumn::make('final_amount')->label('Final Amount'),
            ExportColumn::make('payment_method')->label('Pembayaran'),
            ExportColumn::make('is_voided')->label('Status Void')->formatStateUsing(fn ($state) => $state ? 'Void' : 'Sukses'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Penjualan Kasir selesai. ' . \Illuminate\Support\Number::format($export->successful_rows) . ' baris berhasil diexport.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . \Illuminate\Support\Number::format($failedRowsCount) . ' baris gagal diexport.';
        }

        return $body;
    }
}
