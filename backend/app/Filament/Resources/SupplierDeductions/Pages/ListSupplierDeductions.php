<?php

namespace App\Filament\Resources\SupplierDeductions\Pages;

use App\Filament\Resources\SupplierDeductions\SupplierDeductionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierDeductions extends ListRecords
{
    protected static string $resource = SupplierDeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
