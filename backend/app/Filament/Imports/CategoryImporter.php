<?php

namespace App\Filament\Imports;

use App\Models\Category;
use App\Models\Organization;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CategoryImporter extends Importer
{
    protected static ?string $model = Category::class;

    public static function getOptionsFormComponents(): array
    {
        return [
            \Filament\Forms\Components\Checkbox::make('overwrite')
                ->label('Timpa data yang sudah ada')
                ->helperText('Jika dicentang, data dengan ID/Kode yang sama akan diperbarui dengan data dari file import.'),
        ];
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('organization_id')
                ->label('ID Organisasi')
                ->requiredMapping()
                ->example('ORG-001')
                ->fillRecordUsing(function (Category $record, ?string $state): void {
                    $org = Organization::where('id', $state)->orWhere('code', $state)->first();
                    $record->organization_id = $org?->id ?? $state;
                }),

            ImportColumn::make('code')
                ->label('Kode Kategori')
                ->requiredMapping()
                ->example('KAT-001')
                ->rules(['required', 'string', 'max:50']),

            ImportColumn::make('name')
                ->label('Nama Kategori')
                ->requiredMapping()
                ->example('Makanan Ringan')
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('description')
                ->label('Deskripsi')
                ->example('Kategori untuk makanan ringan dan snack')
                ->rules(['nullable', 'string']),

            ImportColumn::make('is_active')
                ->label('Aktif (1/0)')
                ->boolean()
                ->example('1')
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): ?Category
    {
        $org = Organization::where('id', $this->data['organization_id'])
            ->orWhere('code', $this->data['organization_id'])
            ->first();

        if (!$org) return null;

        $record = Category::firstOrNew([
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
        $body = 'Import kategori selesai. ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
