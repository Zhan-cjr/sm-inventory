<?php

namespace App\Filament\Resources\TaxInvoices\Pages;

use App\Filament\Resources\TaxInvoices\TaxInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxInvoice extends CreateRecord
{
    protected static string $resource = TaxInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['scan_qr_url']);
        if (empty($data['organization_id'])) {
            $data['organization_id'] = \Illuminate\Support\Facades\Auth::user()->organization_id ?? \App\Models\Organization::first()?->id;
        }
        return $data;
    }
}
