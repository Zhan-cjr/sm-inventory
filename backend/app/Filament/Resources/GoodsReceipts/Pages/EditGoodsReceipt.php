<?php

namespace App\Filament\Resources\GoodsReceipts\Pages;

use App\Filament\Resources\GoodsReceipts\GoodsReceiptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGoodsReceipt extends EditRecord
{
    protected static string $resource = GoodsReceiptResource::class;
    protected string $view = 'filament.resources.goods-receipt.pages.pos-view';

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth | string | null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
