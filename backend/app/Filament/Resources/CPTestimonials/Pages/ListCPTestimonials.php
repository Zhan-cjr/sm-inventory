<?php

namespace App\Filament\Resources\CPTestimonials\Pages;

use App\Filament\Resources\CPTestimonials\CPTestimonialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCPTestimonials extends ListRecords
{
    protected static string $resource = CPTestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
