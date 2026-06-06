<?php

namespace App\Filament\Resources\PurchaseReturns\Pages;

use App\Filament\Resources\PurchaseReturns\PurchaseReturnResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseReturn extends EditRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected string $view = 'filament.resources.purchase-return.pages.pos-view';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
