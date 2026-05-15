<?php

namespace App\Filament\Resources\InventoryLogs\Pages;

use App\Filament\Resources\InventoryLogs\InventoryLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryLog extends CreateRecord
{
    protected static string $resource = InventoryLogResource::class;
}
