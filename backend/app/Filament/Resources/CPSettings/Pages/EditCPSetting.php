<?php

namespace App\Filament\Resources\CPSettings\Pages;

use App\Filament\Resources\CPSettings\CPSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCPSetting extends EditRecord
{
    protected static string $resource = CPSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
