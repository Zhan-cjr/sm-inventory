<?php

namespace App\Filament\Resources\EcommerceOrders\Pages;

use App\Filament\Resources\EcommerceOrders\EcommerceOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEcommerceOrders extends ManageRecords
{
    protected static string $resource = EcommerceOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
