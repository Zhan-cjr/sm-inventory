<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Imports\SupplierImporter;
use App\Filament\Resources\Suppliers\SupplierResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label('Import Excel')
                ->importer(SupplierImporter::class)
                ->icon('heroicon-o-arrow-up-tray'),
            CreateAction::make(),
        ];
    }
}
