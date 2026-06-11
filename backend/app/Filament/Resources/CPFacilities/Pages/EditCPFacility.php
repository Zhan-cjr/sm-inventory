<?php

namespace App\Filament\Resources\CPFacilities\Pages;

use App\Filament\Resources\CPFacilities\CPFacilityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCPFacility extends EditRecord
{
    protected static string $resource = CPFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
