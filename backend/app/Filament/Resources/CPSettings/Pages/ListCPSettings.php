<?php

namespace App\Filament\Resources\CPSettings\Pages;

use App\Filament\Resources\CPSettings\CPSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCPSettings extends ListRecords
{
    protected static string $resource = CPSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
