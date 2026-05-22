<?php

namespace App\Filament\Resources\StockTransfers\Pages;

use App\Filament\Resources\StockTransfers\StockTransferResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStockTransfer extends EditRecord
{
    protected static string $resource = StockTransferResource::class;
    protected string $view = 'filament.resources.stock-transfer.pages.pos-view';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
