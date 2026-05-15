<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Imports\ServiceImporter;
use App\Filament\Resources\Services\ServiceResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;

class ManageServices extends ManageRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label('Import Excel')
                ->importer(ServiceImporter::class)
                ->icon('heroicon-o-arrow-up-tray'),
            CreateAction::make(),
        ];
    }
}
