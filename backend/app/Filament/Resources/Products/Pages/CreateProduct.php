<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('cancel')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.cancel.label'))
            ->url($this->getResource()::getUrl('index'))
            ->color('gray');
    }

    protected function afterCreate(): void
    {
        $user = auth()->user();
        
        // Jika pembuat produk adalah user cabang, otomatis daftarkan produk di cabangnya
        if ($user && $user->branch_id) {
            \App\Models\Stock::updateOrCreate(
                [
                    'branch_id' => $user->branch_id,
                    'product_id' => $this->record->id,
                ],
                [
                    'quantity_on_hand' => 0,
                    'cost_price' => $this->record->cost_price,
                    'selling_price' => $this->record->selling_price,
                ]
            );
        }
    }
}
