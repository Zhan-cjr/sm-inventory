<?php

namespace App\Filament\Imports;

use App\Models\Service;
use App\Models\Organization;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ServiceImporter extends Importer
{
    protected static ?string $model = Service::class;

    public static function getOptionsFormComponents(): array
    {
        return [
            \Filament\Forms\Components\Checkbox::make('overwrite')
                ->label('Timpa data yang sudah ada')
                ->helperText('Jika dicentang, data jasa dengan kode yang sama akan diperbarui.'),
        ];
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('organization_id')
                ->label('ID Organisasi')
                ->requiredMapping()
                ->fillRecordUsing(function (Service $record, ?string $state): void {
                    $org = Organization::where('id', $state)->orWhere('code', $state)->first();
                    $record->organization_id = $org?->id ?? $state;
                }),

            ImportColumn::make('code')
                ->label('Kode Jasa')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:50']),

            ImportColumn::make('name')
                ->label('Nama Jasa')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('price')
                ->label('Harga')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric', 'min:0']),

            ImportColumn::make('description')
                ->label('Deskripsi')
                ->rules(['nullable', 'string']),

            ImportColumn::make('is_active')
                ->label('Aktif (1/0)')
                ->boolean()
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): ?Service
    {
        $org = Organization::where('id', $this->data['organization_id'])
            ->orWhere('code', $this->data['organization_id'])
            ->first();

        if (!$org) return null;

        $record = Service::firstOrNew([
            'organization_id' => $org->id,
            'code' => $this->data['code'],
        ]);

        if ($record->exists && ! ($this->options['overwrite'] ?? false)) {
            throw new \Exception('Data sudah ada. Centang opsi "Timpa data" untuk memperbarui.');
        }

        return $record;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import jasa selesai. ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
