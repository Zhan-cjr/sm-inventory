<?php

namespace App\Filament\Exports;

use App\Models\PpobTransaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class PpobTransactionExporter extends Exporter
{
    protected static ?string $model = PpobTransaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('created_at')->label('Tanggal'),
            ExportColumn::make('transaction.receipt_number')->label('No. Struk'),
            ExportColumn::make('transaction.branch.name')->label('Cabang'),
            ExportColumn::make('ref_id')->label('Ref ID'),
            ExportColumn::make('customer_no')->label('No. Tujuan'),
            ExportColumn::make('customer_name')->label('Nama Customer'),
            ExportColumn::make('buyer_sku_code')->label('SKU Provider'),
            ExportColumn::make('provider')->label('Provider'),
            ExportColumn::make('price')
                ->label('Harga Modal')
                ->state(function (PpobTransaction $record) {
                    return strtolower($record->status ?? '') === 'gagal' ? 0 : $record->price;
                }),
            ExportColumn::make('selling_price')
                ->label('Harga Jual')
                ->state(function (PpobTransaction $record) {
                    $isFailed = strtolower($record->status ?? '') === 'gagal';
                    if ($isFailed) return 0;
                    return $record->transaction ? $record->transaction->final_amount : ($record->ecommerceOrder ? $record->ecommerceOrder->total_amount : 0);
                }),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('sn')->label('SN / Token'),
            ExportColumn::make('message')->label('Keterangan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your ppob transaction export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
