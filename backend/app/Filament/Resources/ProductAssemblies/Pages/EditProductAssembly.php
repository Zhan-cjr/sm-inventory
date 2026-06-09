<?php

namespace App\Filament\Resources\ProductAssemblies\Pages;

use App\Filament\Resources\ProductAssemblies\ProductAssemblyResource;
use App\Filament\Resources\ProductAssemblies\Schemas\ProductAssemblyForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductAssembly extends EditRecord
{
    protected static string $resource = ProductAssemblyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus Paket')
                ->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Inject data harga per cabang dari tabel stocks ke dalam form state
        $data['branch_prices'] = ProductAssemblyForm::loadBranchPrices($this->record);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['product_type'] = 'physical';

        return $data;
    }

    protected function afterSave(): void
    {
        // Ambil branch_prices dari form state (tidak di-dehydrate ke model)
        $branchPrices = $this->data['branch_prices'] ?? [];

        if (!empty($branchPrices)) {
            ProductAssemblyForm::syncBranchPrices($this->record, $branchPrices);
        }
    }
}
