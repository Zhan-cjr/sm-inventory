<?php

namespace App\Filament\Resources\PosSettings\Pages;

use App\Filament\Resources\PosSettings\PosSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPosSettings extends ListRecords
{
    protected static string $resource = PosSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
