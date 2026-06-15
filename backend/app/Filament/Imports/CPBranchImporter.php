<?php

namespace App\Filament\Imports;

use App\Models\CPBranch;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CPBranchImporter extends Importer
{
    protected static ?string $model = CPBranch::class;
    
    public static function getOptionsFormComponents(): array
    {
        return [
            \Filament\Forms\Components\Checkbox::make('overwrite')
                ->label('Timpa data yang sudah ada')
                ->helperText('Jika dicentang, data cabang dengan nama yang sama akan diperbarui.'),
        ];
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Nama Cabang')
                ->requiredMapping()
                ->example('Cabang Ciawi')
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('address')
                ->label('Alamat')
                ->requiredMapping()
                ->example('Jl Raya Ciawi No 123')
                ->rules(['required', 'string']),

            ImportColumn::make('open_hours')
                ->label('Jam Buka')
                ->example('08:00 - 22:00')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('lat')
                ->label('Latitude')
                ->example('-6.6500')
                ->rules(['nullable', 'numeric']),

            ImportColumn::make('lng')
                ->label('Longitude')
                ->example('106.8500')
                ->rules(['nullable', 'numeric']),
        ];
    }

    public function resolveRecord(): ?CPBranch
    {
        $record = CPBranch::firstOrNew([
            'name' => $this->data['name'],
        ]);

        if ($record->exists && ! ($this->options['overwrite'] ?? false)) {
            throw new \Exception('Data sudah ada. Centang opsi "Timpa data" untuk memperbarui.');
        }

        return $record;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import profil cabang selesai. ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
