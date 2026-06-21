<?php

namespace App\Filament\Imports;

use App\Models\Branch;
use App\Models\Organization;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class BranchImporter extends Importer
{
    protected static ?string $model = Branch::class;
    
    public static function getOptionsFormComponents(): array
    {
        return [
            \Filament\Forms\Components\Checkbox::make('overwrite')
                ->label('Timpa data yang sudah ada')
                ->helperText('Jika dicentang, data cabang dengan kode yang sama akan diperbarui.'),
        ];
    }
    
    protected static array $orgCache = [];

    public static function getColumns(): array
    {
        $numericCaster = function (?string $state): ?string {
            if (blank($state)) return null;
            $val = trim((string) $state);
            if (preg_match('/^-?[0-9]+$/', $val)) return $val;
            
            $val = preg_replace('/[^0-9\.,-]/', '', $val);
            if (str_contains($val, ',') && str_contains($val, '.')) {
                if (strrpos($val, ',') > strrpos($val, '.')) {
                    $val = str_replace('.', '', $val);
                    $val = str_replace(',', '.', $val);
                } else {
                    $val = str_replace(',', '', $val);
                }
            } elseif (str_contains($val, ',')) {
                $val = str_replace(',', '.', $val);
            } else {
                if (preg_match('/^-?[0-9]+\.[0-9]{3}$/', $val)) {
                    $val = str_replace('.', '', $val);
                }
            }
            return $val;
        };

        $booleanCaster = function (?string $state): ?bool {
            if (blank($state)) return null;
            $val = strtolower(trim($state));
            if (in_array($val, ['1', 'true', 'yes', 'y', 'ya', 'aktif', 'on'])) return true;
            if (in_array($val, ['0', 'false', 'no', 'n', 'tidak', 'nonaktif', 'off', 'non aktif'])) return false;
            return (bool) $val;
        };

        return [
            ImportColumn::make('organization_id')
                ->label('ID Organisasi')
                ->requiredMapping()
                ->example('ORG-001')
                ->fillRecordUsing(function (Branch $record, ?string $state): void {
                    $org = Organization::where('id', $state)->orWhere('code', $state)->first();
                    $record->organization_id = $org?->id ?? $state;
                }),

            ImportColumn::make('code')
                ->label('Kode Cabang')
                ->requiredMapping()
                ->example('CAB-001')
                ->rules(['required', 'string', 'max:50']),

            ImportColumn::make('name')
                ->label('Nama Cabang')
                ->requiredMapping()
                ->example('Cabang Jakarta')
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('address')
                ->label('Alamat')
                ->example('Jl. Sudirman No 1 Jakarta')
                ->rules(['nullable', 'string']),

            ImportColumn::make('phone')
                ->label('Telepon')
                ->example('08123456789')
                ->rules(['nullable', 'string', 'max:50']),

            ImportColumn::make('latitude')
                ->label('Latitude')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('-6.6500')
                ->rules(['nullable', 'numeric']),

            ImportColumn::make('longitude')
                ->label('Longitude')
                ->numeric()
                ->castStateUsing($numericCaster)
                ->example('106.8500')
                ->rules(['nullable', 'numeric']),

            ImportColumn::make('is_active')
                ->label('Aktif (1/0)')
                ->boolean()
                ->castStateUsing($booleanCaster)
                ->example('1')
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): ?Branch
    {
        $orgState = $this->data['organization_id'];
        if (!isset(static::$orgCache[$orgState])) {
            static::$orgCache[$orgState] = Organization::where('id', $orgState)
                ->orWhere('code', $orgState)
                ->value('id');
        }
        
        $resolvedOrgId = static::$orgCache[$orgState];

        if (!$resolvedOrgId) return null;

        $record = Branch::firstOrNew([
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
        $body = 'Import cabang selesai. ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
