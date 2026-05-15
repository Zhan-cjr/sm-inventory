<?php

namespace App\Filament\Resources\PosSettings\Pages;

use App\Filament\Resources\PosSettings\PosSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPosSetting extends EditRecord
{
    protected static string $resource = PosSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
