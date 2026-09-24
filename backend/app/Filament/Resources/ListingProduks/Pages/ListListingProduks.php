<?php

namespace App\Filament\Resources\ListingProduks\Pages;

use App\Filament\Resources\ListingProduks\ListingProdukResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListListingProduks extends ListRecords
{
    protected static string $resource = ListingProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Perjanjian Listing Baru')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
