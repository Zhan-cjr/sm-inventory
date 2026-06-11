<?php

namespace App\Filament\Resources\CPFacilities\Pages;

use App\Filament\Resources\CPFacilities\CPFacilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCPFacilities extends ListRecords
{
    protected static string $resource = CPFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
