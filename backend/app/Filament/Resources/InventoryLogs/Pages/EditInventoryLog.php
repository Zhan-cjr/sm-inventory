<?php

namespace App\Filament\Resources\InventoryLogs\Pages;

use App\Filament\Resources\InventoryLogs\InventoryLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInventoryLog extends EditRecord
{
    protected static string $resource = InventoryLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
