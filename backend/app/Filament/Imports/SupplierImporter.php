<?php

namespace App\Filament\Imports;

use App\Models\Supplier;
use App\Models\Organization;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class SupplierImporter extends Importer
{
    protected static ?string $model = Supplier::class;
    
    public static function getOptionsFormComponents(): array
    {
        return [
            \Filament\Forms\Components\Checkbox::make('overwrite')
                ->label('Timpa data yang sudah ada')
                ->helperText('Jika dicentang, data pemasok dengan kode yang sama akan diperbarui.'),
        ];
    }
    
    protected static array $orgCache = [];

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('organization_id')
                ->label('ID Organisasi')
                ->requiredMapping()
                ->example('ORG-001')
                ->fillRecordUsing(function (Supplier $record, ?string $state): void {
                    $org = Organization::where('id', $state)->orWhere('code', $state)->first();
                    $record->organization_id = $org?->id ?? $state;
                }),

            ImportColumn::make('code')
                ->label('Kode Pemasok')
                ->requiredMapping()
                ->example('SUP-001')
                ->rules(['required', 'string', 'max:50']),

            ImportColumn::make('name')
                ->label('Nama Pemasok')
                ->requiredMapping()
                ->example('PT Indofood')
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('contact_person')
                ->label('Kontak Person')
                ->example('Budi Santoso')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('phone')
                ->label('Telepon')
                ->example('08123456789')
                ->rules(['nullable', 'string', 'max:50']),

            ImportColumn::make('email')
                ->label('Email')
                ->example('budi@indofood.com')
                ->rules(['nullable', 'email', 'max:255']),

            ImportColumn::make('address')
                ->label('Alamat')
                ->example('Jl. Sudirman No 1 Jakarta')
                ->rules(['nullable', 'string']),

            ImportColumn::make('is_active')
                ->label('Aktif (1/0)')
                ->boolean()
                ->example('1')
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): ?Supplier
    {
        $orgState = $this->data['organization_id'];
        if (!isset(static::$orgCache[$orgState])) {
            static::$orgCache[$orgState] = Organization::where('id', $orgState)
                ->orWhere('code', $orgState)
                ->value('id');
        }
        
        $resolvedOrgId = static::$orgCache[$orgState];

        if (!$resolvedOrgId) return null;

        $record = Supplier::firstOrNew([
            'organization_id' => $resolvedOrgId,
            'code' => $this->data['code'],
        ]);

        if ($record->exists && ! ($this->options['overwrite'] ?? false)) {
            throw new \Exception('Data sudah ada. Centang opsi "Timpa data" untuk memperbarui.');
        }

        return $record;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import pemasok selesai. ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
