<?php

namespace App\Filament\Resources\MemberTiers\Pages;

use App\Filament\Resources\MemberTiers\MemberTierResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMemberTier extends EditRecord
{
    protected static string $resource = MemberTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
