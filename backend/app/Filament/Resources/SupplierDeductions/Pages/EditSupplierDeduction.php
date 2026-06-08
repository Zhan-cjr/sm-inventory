<?php

namespace App\Filament\Resources\SupplierDeductions\Pages;

use App\Filament\Resources\SupplierDeductions\SupplierDeductionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierDeduction extends EditRecord
{
    protected static string $resource = SupplierDeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
