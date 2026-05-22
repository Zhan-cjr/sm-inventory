<?php

namespace App\Filament\Resources\EcommerceSettings\Pages;

use App\Filament\Resources\EcommerceSettings\EcommerceSettingResource;
use Filament\Resources\Pages\ListRecords;

class ListEcommerceSettings extends ListRecords
{
    protected static string $resource = EcommerceSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No Create action to prevent creating organization records from here
        ];
    }
}
