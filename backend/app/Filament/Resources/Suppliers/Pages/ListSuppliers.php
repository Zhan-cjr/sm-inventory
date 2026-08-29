<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Exports\SupplierExporter;
use App\Filament\Imports\SupplierImporter;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Ekspor Excel / CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $filename = 'Pemasok_' . now()->format('Y-m-d_His') . '.csv';

                    return new StreamedResponse(function () {
                        $handle = fopen('php://output', 'w');
                        // UTF-8 BOM for Microsoft Excel compatibility
                        fwrite($handle, "\xEF\xBB\xBF");

                        // Headers matching SupplierImporter columns for seamless round-trip
                        fputcsv($handle, [
                            'ID Organisasi',
                            'Kode Pemasok',
                            'Nama Pemasok',
                            'NPWP',
                            'Kontak Person',
                            'Telepon',
                            'Email',
                            'Alamat',
                            'Jatuh Tempo Default (Hari)',
                            'PO Expired Default (Hari)',
                            'Cara Pembayaran Default',
                            'Aktif (1/0)',
                            'Konsinyasi (1/0)',
                        ]);

                        Supplier::with('organization')->orderBy('name', 'asc')->chunk(200, function ($suppliers) use ($handle) {
                            foreach ($suppliers as $s) {
                                fputcsv($handle, [
                                    $s->organization?->code ?? $s->organization_id,
                                    $s->code,
                                    $s->name,
                                    $s->npwp ?? '',
                                    $s->contact_person ?? '',
                                    $s->phone ?? '',
                                    $s->email ?? '',
                                    $s->address ?? '',
                                    (int) ($s->default_due_days ?? 0),
                                    (int) ($s->default_po_expired_days ?? 0),
                                    $s->payment_method ?? 'transfer',
                                    $s->is_active ? '1' : '0',
                                    $s->is_consignment ? '1' : '0',
                                ]);
                            }
                        });

                        fclose($handle);
                    }, 200, [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                    ]);
                }),
            ImportAction::make()
                ->label('Import Excel')
                ->importer(SupplierImporter::class)
                ->icon('heroicon-o-arrow-up-tray'),
            CreateAction::make(),
        ];
    }
}

