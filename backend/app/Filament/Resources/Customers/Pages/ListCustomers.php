<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Imports\CustomerImporter;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label('Import Excel')
                ->importer(CustomerImporter::class)
                ->icon('heroicon-o-arrow-up-tray'),
            CreateAction::make(),
        ];
    }
}
