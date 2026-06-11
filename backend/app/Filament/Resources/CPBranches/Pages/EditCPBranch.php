<?php

namespace App\Filament\Resources\CPBranches\Pages;

use App\Filament\Resources\CPBranches\CPBranchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCPBranch extends EditRecord
{
    protected static string $resource = CPBranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
