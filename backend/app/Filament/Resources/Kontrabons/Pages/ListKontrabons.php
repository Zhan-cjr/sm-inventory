<?php

namespace App\Filament\Resources\Kontrabons\Pages;

use App\Filament\Resources\Kontrabons\KontrabonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKontrabons extends ListRecords
{
    protected static string $resource = KontrabonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
