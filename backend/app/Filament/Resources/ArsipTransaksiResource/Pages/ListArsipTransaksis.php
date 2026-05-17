<?php

namespace App\Filament\Resources\ArsipTransaksiResource\Pages;

use App\Filament\Resources\ArsipTransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArsipTransaksis extends ListRecords
{
    protected static string $resource = ArsipTransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for archives
        ];
    }
}
