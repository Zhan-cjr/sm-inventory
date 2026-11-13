<?php

namespace App\Filament\Resources\StockTransfers\Pages;

use App\Filament\Resources\StockTransfers\StockTransferResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStockTransfer extends ViewRecord
{
    protected static string $resource = StockTransferResource::class;
    protected string $view = 'filament.resources.stock-transfer.pages.pos-view';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

