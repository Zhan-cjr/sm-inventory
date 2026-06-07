<?php

namespace App\Filament\Resources\PurchasePayments\Pages;

use App\Filament\Resources\PurchasePayments\PurchasePaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchasePayment extends EditRecord
{
    protected static string $resource = PurchasePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
