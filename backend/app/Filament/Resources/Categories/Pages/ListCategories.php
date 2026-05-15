<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Imports\CategoryImporter;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label('Import Excel')
                ->importer(CategoryImporter::class)
                ->icon('heroicon-o-arrow-up-tray'),
            CreateAction::make(),
        ];
    }
}
