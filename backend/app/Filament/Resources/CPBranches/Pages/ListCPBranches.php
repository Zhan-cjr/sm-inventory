<?php

namespace App\Filament\Resources\CPBranches\Pages;

use App\Filament\Resources\CPBranches\CPBranchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCPBranches extends ListRecords
{
    protected static string $resource = CPBranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ImportAction::make()
                ->importer(\App\Filament\Imports\CPBranchImporter::class),
            CreateAction::make(),
        ];
    }
}
