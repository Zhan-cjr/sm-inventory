<?php

namespace App\Filament\Resources\WarehouseChecks\Pages;

use App\Filament\Resources\WarehouseChecks\WarehouseCheckResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWarehouseChecks extends ManageRecords
{
    protected static string $resource = WarehouseCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
