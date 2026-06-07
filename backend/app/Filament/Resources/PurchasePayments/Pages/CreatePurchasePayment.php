<?php

namespace App\Filament\Resources\PurchasePayments\Pages;

use App\Filament\Resources\PurchasePayments\PurchasePaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchasePayment extends CreateRecord
{
    protected static string $resource = PurchasePaymentResource::class;

    protected static bool $canCreateAnother = false;
    protected string $view = 'filament.resources.purchase-payments.pages.create-payment';
}
