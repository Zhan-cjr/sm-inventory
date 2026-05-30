<?php

namespace App\Filament\Resources\PpobTransactions\Pages;

use App\Filament\Resources\PpobTransactions\PpobTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPpobTransactions extends ListRecords
{
    protected static string $resource = PpobTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
