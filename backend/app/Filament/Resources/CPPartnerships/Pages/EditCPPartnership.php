<?php

namespace App\Filament\Resources\CPPartnerships\Pages;

use App\Filament\Resources\CPPartnerships\CPPartnershipResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCPPartnership extends EditRecord
{
    protected static string $resource = CPPartnershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
