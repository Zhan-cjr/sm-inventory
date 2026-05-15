<?php

namespace App\Filament\Imports;

use App\Models\Customer;
use App\Models\Organization;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CustomerImporter extends Importer
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('organization_id')
                ->label('ID Organisasi')
                ->requiredMapping()
                ->fillRecordUsing(function (Customer $record, ?string $state): void {
                    $org = Organization::where('id', $state)->orWhere('code', $state)->first();
                    $record->organization_id = $org?->id ?? $state;
                }),

            ImportColumn::make('name')
                ->label('Nama Pelanggan')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('email')
                ->label('Email')
                ->rules(['nullable', 'email', 'max:255']),

            ImportColumn::make('phone')
                ->label('Telepon')
                ->rules(['nullable', 'string', 'max:50']),

            ImportColumn::make('address')
                ->label('Alamat')
                ->rules(['nullable', 'string']),

            ImportColumn::make('member_tier')
                ->label('Member Tier')
                ->rules(['nullable', 'string', 'max:50']),

            ImportColumn::make('points')
                ->label('Poin')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('is_active')
                ->label('Aktif (1/0)')
                ->boolean()
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): ?Customer
    {
        $org = Organization::where('id', $this->data['organization_id'])
            ->orWhere('code', $this->data['organization_id'])
            ->first();

        if (!$org) return null;

        // Upsert by email + organization_id if email exists
        if (! empty($this->data['email'])) {
            return Customer::firstOrNew([
                'organization_id' => $org->id,
                'email' => $this->data['email'],
            ]);
        }
        
        if (! empty($this->data['phone'])) {
            return Customer::firstOrNew([
                'organization_id' => $org->id,
                'phone' => $this->data['phone'],
            ]);
        }

        $customer = new Customer();
        $customer->organization_id = $org->id;
        return $customer;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import pelanggan selesai. ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
