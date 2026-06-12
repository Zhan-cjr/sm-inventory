<?php

namespace App\Filament\Resources\CPPartnerships\Pages;

use App\Filament\Resources\CPPartnerships\CPPartnershipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCPPartnerships extends ListRecords
{
    protected static string $resource = CPPartnershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
