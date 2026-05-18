<?php

namespace App\Filament\Resources\ArsipReturPenjualanResource\Pages;

use App\Filament\Resources\ArsipReturPenjualanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArsipReturPenjualans extends ListRecords
{
    protected static string $resource = ArsipReturPenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for archives
        ];
    }
}
