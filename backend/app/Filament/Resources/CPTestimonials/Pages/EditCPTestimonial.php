<?php

namespace App\Filament\Resources\CPTestimonials\Pages;

use App\Filament\Resources\CPTestimonials\CPTestimonialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCPTestimonial extends EditRecord
{
    protected static string $resource = CPTestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
