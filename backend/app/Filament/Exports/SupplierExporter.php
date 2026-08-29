<?php

namespace App\Filament\Exports;

use App\Models\Supplier;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class SupplierExporter extends Exporter
{
    protected static ?string $model = Supplier::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('organization.name')
                ->label('Organisasi'),
            ExportColumn::make('organization.code')
                ->label('Kode Organisasi'),
            ExportColumn::make('code')
                ->label('Kode Pemasok'),
            ExportColumn::make('name')
                ->label('Nama Pemasok'),
            ExportColumn::make('npwp')
                ->label('NPWP'),
            ExportColumn::make('contact_person')
                ->label('Kontak Person'),
            ExportColumn::make('phone')
                ->label('Telepon'),
            ExportColumn::make('email')
                ->label('Email'),
            ExportColumn::make('address')
                ->label('Alamat'),
            ExportColumn::make('default_due_days')
                ->label('Jatuh Tempo (Hari)'),
            ExportColumn::make('default_po_expired_days')
                ->label('PO Expired (Hari)'),
            ExportColumn::make('payment_method')
                ->label('Cara Pembayaran'),
            ExportColumn::make('is_active')
                ->label('Aktif'),
            ExportColumn::make('is_consignment')
                ->label('Konsinyasi'),
            ExportColumn::make('created_at')
                ->label('Dibuat Pada'),
            ExportColumn::make('updated_at')
                ->label('Diperbarui Pada'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor data pemasok telah selesai. ' . Number::format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }
}
