<?php

namespace App\Filament\Resources\MemberTiers\Pages;

use App\Filament\Resources\MemberTiers\MemberTierResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMemberTier extends ViewRecord
{
    protected static string $resource = MemberTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
