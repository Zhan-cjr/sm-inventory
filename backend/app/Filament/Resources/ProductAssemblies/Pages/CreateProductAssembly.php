<?php

namespace App\Filament\Resources\ProductAssemblies\Pages;

use App\Filament\Resources\ProductAssemblies\ProductAssemblyResource;
use App\Filament\Resources\ProductAssemblies\Schemas\ProductAssemblyForm;
use Filament\Resources\Pages\CreateRecord;

class CreateProductAssembly extends CreateRecord
{
    protected static string $resource = ProductAssemblyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['product_type']   = 'physical';
        $data['reorder_point']  = $data['reorder_point']  ?? 0;
        $data['reorder_qty']    = $data['reorder_qty']    ?? 0;
        $data['lead_time_days'] = $data['lead_time_days'] ?? 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Ambil data branch_prices dari form state (tidak di-dehydrate ke model)
        $branchPrices = $this->data['branch_prices'] ?? [];

        if (!empty($branchPrices)) {
            ProductAssemblyForm::syncBranchPrices($this->record, $branchPrices);
        }
    }
}
