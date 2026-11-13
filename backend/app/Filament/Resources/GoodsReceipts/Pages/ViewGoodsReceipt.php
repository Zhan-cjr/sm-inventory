<?php

namespace App\Filament\Resources\GoodsReceipts\Pages;

use App\Filament\Resources\GoodsReceipts\GoodsReceiptResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGoodsReceipt extends ViewRecord
{
    protected static string $resource = GoodsReceiptResource::class;
    protected string $view = 'filament.resources.goods-receipt.pages.pos-view';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

