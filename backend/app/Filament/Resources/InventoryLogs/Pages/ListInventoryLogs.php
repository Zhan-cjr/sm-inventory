<?php

namespace App\Filament\Resources\InventoryLogs\Pages;

use App\Filament\Resources\InventoryLogs\InventoryLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventoryLogs extends ListRecords
{
    protected static string $resource = InventoryLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
