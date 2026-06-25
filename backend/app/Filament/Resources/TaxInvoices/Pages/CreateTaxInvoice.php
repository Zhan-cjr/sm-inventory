<?php

namespace App\Filament\Resources\TaxInvoices\Pages;

use App\Filament\Resources\TaxInvoices\TaxInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxInvoice extends CreateRecord
{
    protected static string $resource = TaxInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['organization_id'])) {
            $data['organization_id'] = \Illuminate\Support\Facades\Auth::user()->organization_id ?? 1;
        }
        return $data;
    }
}
