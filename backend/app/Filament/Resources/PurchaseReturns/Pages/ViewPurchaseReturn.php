<?php

namespace App\Filament\Resources\PurchaseReturns\Pages;

use App\Filament\Resources\PurchaseReturns\PurchaseReturnResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseReturn extends ViewRecord
{
    protected static string $resource = PurchaseReturnResource::class;
    protected string $view = 'filament.resources.purchase-return.pages.pos-view';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
