<?php

namespace App\Filament\Resources\EcommerceSettings\Pages;

use App\Filament\Resources\EcommerceSettings\EcommerceSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditEcommerceSetting extends EditRecord
{
    protected static string $resource = EcommerceSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No delete action to prevent deleting organizations from here
        ];
    }
}
