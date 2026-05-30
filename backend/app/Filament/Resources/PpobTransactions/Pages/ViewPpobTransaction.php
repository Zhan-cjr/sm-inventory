<?php

namespace App\Filament\Resources\PpobTransactions\Pages;

use App\Filament\Resources\PpobTransactions\PpobTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPpobTransaction extends ViewRecord
{
    protected static string $resource = PpobTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
