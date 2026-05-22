<?php

namespace App\Filament\Resources\StockTransfers\Pages;

use App\Filament\Resources\StockTransfers\StockTransferResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockTransfer extends CreateRecord
{
    protected static string $resource = StockTransferResource::class;
    protected string $view = 'filament.resources.stock-transfer.pages.pos-view';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = \Illuminate\Support\Facades\Auth::id();
        return $data;
    }
}
