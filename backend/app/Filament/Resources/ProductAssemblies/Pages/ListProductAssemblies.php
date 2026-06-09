<?php

namespace App\Filament\Resources\ProductAssemblies\Pages;

use App\Filament\Resources\ProductAssemblies\ProductAssemblyResource;
use App\Filament\Resources\ProductAssemblies\Schemas\ProductAssemblyForm;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductAssemblies extends ListRecords
{
    protected static string $resource = ProductAssemblyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Paket Baru'),
        ];
    }
}
